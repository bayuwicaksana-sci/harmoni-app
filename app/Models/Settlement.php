<?php

namespace App\Models;

use App\Enums\RequestItemStatus;
use App\Enums\RequestPaymentType;
use App\Enums\SettlementStatus;
use App\Services\SettlementDPRService;
use App\Services\SettlementFormDataService;
use App\Services\SettlementItemProcessingService;
use App\Services\SettlementNotificationService;
use App\Services\SettlementOffsetCalculationService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Settlement extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = [];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'submit_date' => 'datetime',
        'refund_confirmed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'status' => SettlementStatus::class,
    ];

    protected static function booted()
    {
        static::creating(function ($request) {
            // Auto-generate settlement number if not provided
            if (empty($request->settlement_number)) {
                $request->settlement_number = self::generateRequestNumber();
            }
        });
    }

    /**
     * Configuration for document sequence
     */
    const DOCUMENT_TYPE = 'payment_request_settlement';

    const DOCUMENT_PREFIX = 'SCI-FIN-SET';

    const RESET_PERIOD = 'none'; // Change to 'monthly' or 'none' as needed

    const NUMBER_LENGTH = 6;

    /**
     * Generate request number using DocumentSequence
     */
    public static function generateRequestNumber(): string
    {
        return DocumentSequence::generateNumber(
            self::DOCUMENT_TYPE,
            self::DOCUMENT_PREFIX,
            self::RESET_PERIOD,
            self::NUMBER_LENGTH
        );
    }

    // Relationships

    public function settlementItems(): HasManyThrough
    {
        return $this->hasManyThrough(RequestItem::class, SettlementReceipt::class);
    }

    public function settlementReceipts(): HasMany
    {
        return $this->hasMany(SettlementReceipt::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'submitter_id');
    }

    public function generatedPaymentRequest(): BelongsTo
    {
        return $this->belongsTo(DailyPaymentRequest::class, 'generated_payment_request_id');
    }

    public function refundConfirmer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'refund_confirmed_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'confirmed_by');
    }

    // Media Collections

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('request_item_settlement_attachments')
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf']);

        // Keep existing collection for refund receipts
        $this->addMediaCollection('refund_receipts')
            ->useDisk('public')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->performOnCollections('request_item_settlement_attachments');

        $this->addMediaConversion('medium')
            ->width(800)
            ->height(600)
            ->performOnCollections('request_item_settlement_attachments');
    }

    // New Attributes

    protected function approvedBudget(): Attribute
    {
        return new Attribute(
            get: fn () => $this->settlementItems->where('status', '!=', RequestItemStatus::Cancelled)->where('is_unplanned', '!=', true)->sum('total_amount')
        );
    }

    protected function spentBudget(): Attribute
    {
        return new Attribute(
            get: fn () => $this->settlementItems
                ->where('payment_type', '!=', RequestPaymentType::Offset)
                ->sum('total_act_amount')
        );
    }

    protected function budgetVariance(): Attribute
    {
        return new Attribute(
            get: fn () => $this->approvedBudget - $this->spentBudget
        );
    }

    protected function cancelledBudget(): Attribute
    {
        return new Attribute(
            get: fn () => $this->settlementItems
                ->where('status', RequestItemStatus::Cancelled)
                ->sum('total_amount')
        );
    }

    protected function newRequestBudget(): Attribute
    {
        return new Attribute(
            get: fn () => $this->settlementItems
                ->where('is_unplanned', true)
                ->sum('total_act_amount')
        );
    }

    /**
     * Reconstruct reconciliation data from saved RequestItems
     */
    public function getReconciliation(): array
    {
        // Load all items with relationships
        $allItems = $this->settlementItems()
            ->with(['coa', 'settleParent', 'settleChilds'])
            ->get();

        // Separate items by type
        $originalItems = $allItems->filter(fn ($item) => $item->settling_for === null && ! $item->is_unplanned);

        $newItems = $allItems->filter(fn ($item) => $item->is_unplanned);

        $offsetItems = $allItems->filter(function ($item) {
            return $item->payment_type !== null && $item->payment_type === RequestPaymentType::Offset;
        });

        // Group by COA
        $coaGroups = [];

        // Process original items
        foreach ($originalItems as $item) {
            $coaId = $item->coa_id;

            if (! isset($coaGroups[$coaId])) {
                $coaGroups[$coaId] = [
                    'coa_id' => $coaId,
                    'coa_name' => $item->coa->name ?? 'N/A',
                    'coa_code' => $item->coa->code ?? 'N/A',
                    'approved_budget' => 0,
                    'spent_budget' => 0,
                    'cancelled_budget' => 0,
                    'variance_budget' => 0,
                    'new_items_budget' => 0,
                    'offset_used' => 0,
                    'amount_to_return' => 0,
                    'amount_to_reimburse' => 0,
                    'items' => [],
                ];
            }

            $isCancelled = $item->status === RequestItemStatus::Cancelled;
            $requestTotal = $item->total_amount;
            $actualTotal = $item->total_act_amount;
            $variance = $requestTotal - $actualTotal;

            if (! $isCancelled) {
                $coaGroups[$coaId]['approved_budget'] += $requestTotal;
                $coaGroups[$coaId]['spent_budget'] += $actualTotal;

                if ($variance > 0) {
                    $coaGroups[$coaId]['variance_budget'] += $variance;
                }
            } else {
                $coaGroups[$coaId]['approved_budget'] += $requestTotal;
                $coaGroups[$coaId]['cancelled_budget'] += $requestTotal;
            }

            $coaGroups[$coaId]['items'][] = [
                'type' => $isCancelled ? 'cancelled' : 'realized',
                'description' => $item->description,
                'request_quantity' => $item->quantity,
                'actual_quantity' => $item->act_quantity,
                'unit' => $item->unit_quantity,
                'request_price' => $item->amount_per_item,
                'actual_price' => $item->act_amount_per_item,
                'request_total' => $requestTotal,
                'actual_total' => $actualTotal,
                'variance' => $variance,
                'status' => $item->status->getLabel(),
            ];
        }

        // Process new unplanned items
        foreach ($newItems as $item) {
            $coaId = $item->coa_id;

            if (! isset($coaGroups[$coaId])) {
                $coaGroups[$coaId] = [
                    'coa_id' => $coaId,
                    'coa_name' => $item->coa->name ?? 'N/A',
                    'coa_code' => $item->coa->code ?? 'N/A',
                    'approved_budget' => 0,
                    'spent_budget' => 0,
                    'cancelled_budget' => 0,
                    'variance_budget' => 0,
                    'new_items_budget' => 0,
                    'offset_used' => 0,
                    'amount_to_return' => 0,
                    'amount_to_reimburse' => 0,
                    'items' => [],
                ];
            }

            $totalPrice = $item->total_act_amount;

            $coaGroups[$coaId]['new_items_budget'] += $totalPrice;
            $coaGroups[$coaId]['spent_budget'] += $totalPrice;

            $coaGroups[$coaId]['items'][] = [
                'type' => 'new_unplanned',
                'description' => $item->description,
                'quantity' => $item->act_quantity,
                'unit' => $item->unit_quantity,
                'price' => $item->act_amount_per_item,
                'total' => $totalPrice,
                'status' => $item->status->getLabel(),
            ];
        }

        // Process offset items to track what was used
        foreach ($offsetItems as $offsetItem) {
            $coaId = $offsetItem->coa_id;

            if (isset($coaGroups[$coaId])) {
                // Use absolute value since offset represents amount used (always positive)
                $offsetAmount = abs($offsetItem->act_amount_per_item);
                $coaGroups[$coaId]['offset_used'] += $offsetAmount;

                // Find parent item info
                $parentItem = $offsetItem->settleParent;
                $parentDescription = $parentItem ? $parentItem->description : 'Unknown';

                $coaGroups[$coaId]['items'][] = [
                    'type' => 'offset',
                    'description' => "Offset from: {$parentDescription}",
                    'amount' => $offsetAmount,
                ];
            }
        }

        // Calculate final amounts per COA
        foreach ($coaGroups as $coaId => &$group) {
            // Amount to return: if spent less than approved (savings)
            $group['amount_to_return'] = max(0, $group['approved_budget'] - $group['spent_budget']);

            // Amount to reimburse: if spent more than approved (overspending)
            // This happens when new items exceed cancelled/variance budget
            $group['amount_to_reimburse'] = max(0, $group['spent_budget'] - $group['approved_budget']);

            // Net settlement for this COA (Positive = Employee owes, Negative = Company owes)
            $group['net_settlement'] = $group['amount_to_return'] - $group['amount_to_reimburse'];

            // Format summary for KeyValueEntry display with proper alignment
            $formatMoney = function ($amount) {
                return new HtmlString('<div class="flex justify-between items-center gap-4"><span>Rp</span><span class="tabular-nums">'.number_format($amount, 2, ',', '.').'</span></div>');
            };

            $group['summary'] = [
                'COA' => "{$group['coa_name']} ({$group['coa_code']})",
                'Anggaran Disetujui' => $formatMoney($group['approved_budget']),
                'Total Dibelanjakan' => $formatMoney($group['spent_budget']),
                'Item Dibatalkan' => $formatMoney($group['cancelled_budget']),
                'Item Baru (Unplanned)' => $formatMoney($group['new_items_budget']),
                'Selisih (Variance)' => $formatMoney($group['variance_budget']),
                'Offset Digunakan' => $formatMoney($group['offset_used']),
                'Dikembalikan ke Finance' => $formatMoney($group['amount_to_return']),
                'Reimbursement ke Employee' => $formatMoney($group['amount_to_reimburse']),
            ];
        }
        unset($group);

        // Calculate totals
        $totalAmountToReturn = collect($coaGroups)->sum('amount_to_return');
        $totalAmountToReimburse = collect($coaGroups)->sum('amount_to_reimburse');
        // Positive = Employee owes company, Negative = Company owes employee
        $netSettlement = $totalAmountToReturn - $totalAmountToReimburse;

        return [
            'reconciliations' => array_values($coaGroups),
            'total_amount_to_return' => $totalAmountToReturn,
            'total_amount_to_reimburse' => $totalAmountToReimburse,
            'net_settlement' => $netSettlement,
        ];
    }

    protected function reconciliation(): Attribute
    {
        return new Attribute(
            get: fn () => $this->getReconciliation()
        );
    }

    protected function totalAmountToReturn(): Attribute
    {
        return new Attribute(
            get: fn () => $this->getReconciliation()['total_amount_to_return']
        );
    }

    protected function totalAmountToReimburse(): Attribute
    {
        return new Attribute(
            get: fn () => $this->getReconciliation()['total_amount_to_reimburse']
        );
    }

    protected function netSettlement(): Attribute
    {
        return new Attribute(
            get: fn () => $this->getReconciliation()['net_settlement']
        );
    }

    // Query Scopes

    public function scopeDraft($query)
    {
        return $query->where('status', SettlementStatus::Draft);
    }

    public function scopePending($query)
    {
        return $query->where('status', SettlementStatus::Pending);
    }

    public function scopeWaitingDPRApproval($query)
    {
        return $query->where('status', SettlementStatus::WaitingDPRApproval);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', SettlementStatus::Approved);
    }

    public function scopeWaitingRefund($query)
    {
        return $query->where('status', SettlementStatus::WaitingRefund);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', SettlementStatus::Closed);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', SettlementStatus::Rejected);
    }

    // Workflow Methods

    public function submit(): void
    {
        $this->update(['status' => SettlementStatus::Pending]);
    }

    public function approve(): void
    {
        // Use centralized refund calculation with offset allocation logic
        $refundAmount = $this->calculateRefundAmount();

        if ($refundAmount > 0) {
            // Employee owes money - must return to company
            $this->update([
                'status' => SettlementStatus::WaitingRefund,
                'refund_amount' => $refundAmount,
            ]);
        } else {
            // No refund needed - company owes or break-even
            $this->update(['status' => SettlementStatus::WaitingConfirmation, 'refund_amount' => null]);

            app(SettlementNotificationService::class)
                ->notifyFinanceOperatorForConfirmation($this);
        }
    }

    public function confirmRefund(Employee $confirmer): void
    {
        DB::transaction(function () use ($confirmer) {
            // Update settlement status
            $this->update([
                'status' => SettlementStatus::Closed,
                'refund_confirmed_by' => $confirmer->id,
                'refund_confirmed_at' => now(),
                'confirmed_by' => $confirmer->id,
                'confirmed_at' => now(),
            ]);

            // Mark all settlement items as Closed
            $this->settlementItems()
                ->whereIn('status', [
                    RequestItemStatus::WaitingRefund,
                    RequestItemStatus::WaitingApproval,
                    RequestItemStatus::WaitingSettlementReview,
                ])
                ->update(['status' => RequestItemStatus::Closed]);
        });
    }

    public function confirmSettlement(Employee $confirmer): void
    {
        DB::transaction(function () use ($confirmer) {
            // Update settlement status
            $this->update([
                'status' => SettlementStatus::Closed,
                'confirmed_by' => $confirmer->id,
                'confirmed_at' => now(),
            ]);

            // Mark all settlement items as Closed
            $this->settlementItems()
                ->whereIn('status', [
                    RequestItemStatus::WaitingRefund,
                    RequestItemStatus::WaitingApproval,
                    RequestItemStatus::WaitingSettlementReview,
                ])
                ->update(['status' => RequestItemStatus::Closed]);
        });
    }

    public function requestRevision(string $reason): void
    {
        $this->update([
            'previous_status' => $this->status->value,
            'status' => SettlementStatus::Draft,
            'revision_notes' => $reason,
        ]);
    }

    public function resubmit(): void
    {
        DB::transaction(function () {
            $dprService = app(SettlementDPRService::class);

            // Clear revision notes
            $revisionData = ['revision_notes' => null];

            $settlementReceipts = $this->settlementReceipts()->with(['requestItems'])->get()->toArray();

            foreach ($settlementReceipts as $receiptIndex => $receipt) {
                $receipt['requestItems'] = $receipt['request_items'];

                $settlementReceipts[$receiptIndex]['requestItems'] = $receipt['request_items'];
                unset($settlementReceipts[$receiptIndex]['request_items']);
            }

            // dd(['settlementReceipts' => $settlementReceipts]);

            // Check if settlement needs DPR
            if ($dprService->requiresDPR($this)) {
                // Create DPR and route to WaitingDPRApproval
                // $dpr = $dprService->createDPRForSettlement($this);

                $formDataService = app(SettlementFormDataService::class);
                $structuredData = $formDataService->extractFormData(['settlementReceipts' => $settlementReceipts]);

                // Service 2: Process items from receipts
                $itemProcessor = app(\App\Services\SettlementItemProcessingService::class);
                $results = $itemProcessor->processReceipts(
                    $structuredData['receipts'],
                    $this,
                    false // create mode
                );

                // Service 3: Categorize items
                $categorized = $itemProcessor->categorizeItems(collect($results));

                // Service 4: Process settlement with same-COA reconciliation first
                $offsetService = app(\App\Services\SettlementOffsetCalculationService::class);
                $overspentResults = $categorized['overspent'] ? collect($categorized['overspent'])->flatten(1)->toArray() : [];

                // Use the new processSettlement method that handles same-COA internal reconciliation
                $processResult = $offsetService->processSettlement($categorized, $overspentResults, $this);

                // Collect all DPR items (offsets + reimbursements + new items)
                $dprItems = array_merge(
                    $processResult['offset_items'],
                    $processResult['reimbursement_items']
                );

                // Add new items (flatten COA groups)
                foreach ($categorized['new'] ?? [] as $coaId => $newItems) {
                    foreach ($newItems as $newResult) {
                        $dprItems[] = $newResult['item'];
                    }
                }

                // Check if DPR is needed using the new service method
                // $dprService = app(\App\Services\SettlementDPRService::class);
                $requiresDPR = $dprService->requiresDPRFromResults([
                    'categorized' => $categorized,
                    'reimbursement_items' => $processResult['reimbursement_items'],
                    'offset_items' => $processResult['offset_items'],
                ]);

                // Create DPR if there are items requiring approval
                if ($requiresDPR && ! empty($dprItems)) {
                    $dprService->createDPRForSettlement($this, $dprItems);
                } else {
                    // No DPR needed - set final status based on processed refund amount
                    $dprService->finalizeSettlementWithoutDPR($this, $processResult);
                }

                // Status already set by service
                $revisionData['previous_status'] = null;
                $this->update($revisionData);

                // Notify DPR approvers (reuse existing notification)

            } else {
                // No DPR needed - smart routing based on previous status
                if ($this->previous_status === SettlementStatus::WaitingConfirmation->value) {
                    $revisionData['status'] = SettlementStatus::WaitingConfirmation;
                    $revisionData['previous_status'] = null;

                    $this->update($revisionData);

                    app(SettlementNotificationService::class)
                        ->notifyFinanceOperatorForConfirmation($this);

                } elseif ($this->previous_status === SettlementStatus::WaitingRefund->value) {
                    // IMPORTANT: Recalculate refund amount before returning to WaitingRefund
                    $refundAmount = $this->calculateRefundAmount();

                    $revisionData['status'] = SettlementStatus::WaitingRefund;
                    $revisionData['refund_amount'] = $refundAmount;
                    $revisionData['previous_status'] = null;

                    $this->update($revisionData);

                } else {
                    // Fallback
                    $revisionData['status'] = SettlementStatus::WaitingConfirmation;
                    $revisionData['previous_status'] = null;

                    $this->update($revisionData);

                    app(SettlementNotificationService::class)
                        ->notifyFinanceOperatorForConfirmation($this);
                }
            }
        });
    }

    /**
     * Calculate current refund amount using offset allocation logic
     * Returns the actual amount employee must return after offset allocation
     */
    public function calculateRefundAmount(): float
    {
        // Get settlement items categorized by type
        $itemProcessor = app(SettlementItemProcessingService::class);
        $offsetService = app(SettlementOffsetCalculationService::class);

        // Load all settlement items
        $items = $this->settlementItems()->get();

        // Process items into results format
        $results = $items->map(function ($item) {
            $type = 'exact';

            if ($item->status === RequestItemStatus::Cancelled) {
                $type = 'cancelled';
            } elseif ($item->variance > 0) {
                $type = 'variance';
            } elseif ($item->variance < 0) {
                $type = 'overspent';
            } elseif ($item->is_unplanned) {
                $type = 'new';
            }

            return [
                'type' => $type,
                'item' => $item,
                'variance' => $item->variance ?? 0,
                'amount' => $item->total_amount ?? 0,
                'total_price' => $item->total_act_amount ?? 0,
            ];
        });

        // Categorize items
        $categorized = $itemProcessor->categorizeItems($results);

        // Process settlement with same-COA reconciliation first
        $overspentResults = $categorized['overspent'] ? collect($categorized['overspent'])->flatten(1)->toArray() : [];
        $processResult = $offsetService->processSettlementAfterDPRApproval($categorized, $overspentResults, $this);

        return max(0, $processResult['total_refund_amount'] ?? 0);
    }

    public function reject(): void
    {
        $this->update(['status' => SettlementStatus::Rejected]);
    }

    public function markWaitingDPRApproval(): void
    {
        $this->update(['status' => SettlementStatus::WaitingDPRApproval]);
    }
}

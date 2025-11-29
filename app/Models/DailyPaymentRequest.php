<?php

namespace App\Models;

use App\Enums\DPRStatus;
use App\Enums\RequestPaymentType;
use App\Services\DPRNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DailyPaymentRequest extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'request_number',
        'requester_id',
        'request_date',
        // 'total_amount',
        // 'bank_name',
        // 'bank_account_number',
        // 'bank_cust_name',
        // 'total_tax',
        // 'net_amount',
        'status',
    ];

    protected $casts = [
        'request_date' => 'date',
        // 'total_amount' => 'decimal:2',
        // 'total_tax' => 'decimal:2',
        // 'net_amount' => 'decimal:2',
        'status' => DPRStatus::class,
    ];

    protected $with = ['requester'];

    /**
     * Configuration for document sequence
     */
    const DOCUMENT_TYPE = 'daily_payment_request';

    const DOCUMENT_PREFIX = 'SCI-FIN-PAY';

    const RESET_PERIOD = 'none'; // Change to 'monthly' or 'none' as needed

    const NUMBER_LENGTH = 6;

    protected static function booted()
    {
        static::creating(function ($request) {
            // If creating directly with submitted status (skip draft)
            if ($request->status === DPRStatus::Pending && empty($request->request_number)) {
                $request->request_number = self::generateRequestNumber();
            }
        });

        // Generate request number when status changes to submitted
        static::updating(function ($request) {
            // If status is changing from draft to submitted
            if (
                $request->isDirty('status') &&
                $request->getOriginal('status') === DPRStatus::Draft &&
                $request->status === DPRStatus::Pending
            ) {

                // Generate request number if not already set
                if (empty($request->request_number)) {
                    $request->request_number = self::generateRequestNumber();
                }

                // $notificationService = app(DPRNotificationService::class);
                // $notificationService->sendRequestSubmittedNotification($request);
            }

            // // Update approved_at when approved
            // if ($request->isDirty('status') && $request->status === 'approved') {
            //     $request->approved_at = now();
            // }

            // // Update rejected_at when rejected
            // if ($request->isDirty('status') && $request->status === 'rejected') {
            //     $request->rejected_at = now();
            // }
        });
    }

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
    public function requester(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requester_id');
    }

    public function requestItems(): HasMany
    {
        return $this->hasMany(RequestItem::class);
    }

    public function approvalHistories(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(PaymentRequestAudit::class);
    }

    protected function totalRequestAmount(): Attribute
    {
        return new Attribute(
            get: function () {
                $total = $this->requestItems->where('payment_type', RequestPaymentType::Advance)->sum('total_amount') + $this->requestItems->where('payment_type', RequestPaymentType::Reimburse)->sum('total_act_amount') + $this->requestItems->where('payment_type', RequestPaymentType::Offset)->sum('total_act_amount');

                return $total > 0 ? $total : 0;
            }
        );
    }

    // public function requestAttachments(): HasMany
    // {
    //     return $this->hasMany(RequestAttachment::class);
    // }

    // Scopes
    public function scopeDraft(Builder $query): void
    {
        $query->where('status', DPRStatus::Draft);
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', DPRStatus::Pending);
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('status', DPRStatus::Approved);
    }

    public function scopeRejected(Builder $query): void
    {
        $query->where('status', DPRStatus::Rejected);
    }

    public function scopePaid(Builder $query): void
    {
        $query->where('status', DPRStatus::Paid);
    }

    public function scopeSettled(Builder $query): void
    {
        $query->where('status', DPRStatus::Settled);
    }

    // Helper Methods
    public function isDraft(): bool
    {
        return $this->status === DPRStatus::Draft;
    }

    public function isPending(): bool
    {
        return $this->status === DPRStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === DPRStatus::Approved;
    }

    public function isRejected(): bool
    {
        return $this->status === DPRStatus::Rejected;
    }

    public function isPaid(): bool
    {
        return $this->status === DPRStatus::Paid;
    }

    public function isSettled(): bool
    {
        return $this->status === DPRStatus::Settled;
    }

    public function canBeEdited(): bool
    {
        return $this->status === DPRStatus::Draft && $this->requester->id === Auth::user()?->employee->id;
    }

    public function canBeSubmit(): bool
    {
        return $this->status === DPRStatus::Draft && $this->requestItems()->count() > 0 && $this->requester->id === Auth::user()?->employee?->id;
    }

    public function hasAdvancePayments(): bool
    {
        return $this->requestItems()->where('payment_type', RequestPaymentType::Advance)->exists();
    }

    public function needsSettlement(): bool
    {
        return $this->isPaid() && $this->hasAdvancePayments();
    }

    // public function calculateTotals(): void
    // {
    //     $this->total_amount = $this->requestItems()->sum('amount');
    //     // $this->total_tax = $this->requestItems()->sum('tax_amount');
    //     // $this->net_amount = $this->requestItems()->sum('net_amount');
    //     $this->save();
    // }

    /**
     * Validate if request is ready for submission
     *
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateForSubmission(): array
    {
        $errors = [];

        // 1. Check basic request information
        // if (empty($this->requester_id)) {
        //     $errors[] = 'Requester is required';
        // }

        // if (empty($this->request_date)) {
        //     $errors[] = 'Request date is required';
        // }

        // 2. Check if request has items
        $itemsCount = $this->requestItems()->count();
        if ($itemsCount === 0) {
            $errors[] = 'Diperlukan setidaknya 1 item request';
        }

        // 3. Validate each request item
        if ($itemsCount > 0) {
            $items = $this->requestItems;
            foreach ($items as $index => $item) {
                // $itemNumber = $index + 1;
                $itemErrors = $this->validateRequestItem($item, $item->description);
                $errors = array_merge($errors, $itemErrors);
            }
        }

        // 4. Check totals are calculated
        if ($itemsCount > 0 && ($this->total_request_amount === null || $this->total_request_amount <= 0)) {
            $errors[] = 'Nominal request harus lebih besar dari 0';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate individual request item
     *
     * @param  RequestItem  $item
     */
    protected function validateRequestItem($item, string $itemDescription): array
    {
        $errors = [];
        $prefix = "{$itemDescription}: ";

        // Required fields
        if (empty($item->coa_id)) {
            $errors[] = $prefix.'COA diperlukan';
        }

        if (empty($item->payment_type)) {
            $errors[] = $prefix.'Tipe Request diperlukan';
        }

        // Advance payment specific validation
        // if ($item->payment_type === RequestPaymentType::Advance && empty($item->advance_percentage)) {
        //     $errors[] = $prefix . 'Advance percentage is required for advance payment';
        // }

        // if (empty($item->request_item_type_id)) {
        //     $errors[] = $prefix . 'Item type is required';
        // }

        // if (empty($item->tax_method)) {
        //     $errors[] = $prefix . 'Tax method is required';
        // }

        // Quantity and amount validation
        if (($item->payment_type === RequestPaymentType::Advance && (empty($item->quantity) || $item->quantity <= 0)) || (($item->payment_type === RequestPaymentType::Reimburse || $item->payment_type === RequestPaymentType::Offset) && (empty($item->act_quantity) || $item->act_quantity <= 0))) {
            $errors[] = $prefix.'Quantity harus lebih besar dari 0';
        }

        if (($item->payment_type === RequestPaymentType::Advance && (empty($item->amount_per_item) || $item->amount_per_item <= 0)) || (($item->payment_type === RequestPaymentType::Reimburse || $item->payment_type === RequestPaymentType::Offset) && (empty($item->act_amount_per_item) || $item->act_amount_per_item <= 0))) {
            $errors[] = $prefix.'Harga per item harus lebih besar dari 0';
        }

        if ($item->payment_type === RequestPaymentType::Reimburse && (! $item->hasMedia('request_item_attachments') || ! $item->hasMedia('request_item_image'))) {
            $errors[] = $prefix.'Lampiran dan Foto Item diperlukan';
        }

        if (empty($item->bank_name) || empty($item->bank_account) || empty($item->account_owner)) {
            $errors[] = $prefix.'Informasi tujuan transfer diperlukan';
        }

        // Calculated fields validation
        // if (empty($item->amount) || $item->amount <= 0) {
        //     $errors[] = $prefix . 'Total amount must be calculated and greater than zero';
        // }

        // Snapshot data validation (ensure COA data was captured)
        // if (empty($item->coa_code) || empty($item->coa_name)) {
        //     $errors[] = $prefix . 'COA data is incomplete. Please re-select the COA';
        // }

        return $errors;
    }

    /**
     * Check if request can be submitted
     */
    public function canBeSubmitted(): bool
    {
        $validation = $this->validateForSubmission();

        return $validation['valid'] && $this->status === DPRStatus::Draft;
    }

    /**
     * Get submission validation errors
     */
    public function getSubmissionErrors(): array
    {
        $validation = $this->validateForSubmission();

        return $validation['errors'];
    }

    public function getGroupedByBankAccount()
    {
        return $this->requestItems
            ->groupBy('bank_account')
            ->mapWithKeys(function ($items, $bankAccount) {
                $firstItem = $items->first();

                return [
                    $bankAccount => [
                        'bank_account' => $bankAccount,
                        'bank_name' => $firstItem->bank_name,
                        'account_owner' => $firstItem->account_owner,
                        'total_amount' => $items->sum(function ($item) {
                            return $item->payment_type === RequestPaymentType::Advance ? $item->total_amount : $item->total_act_amount;
                        }),
                    ],
                ];
            });
    }

    public function getGroupedByCoa()
    {
        return $this->requestItems
            ->groupBy('coa_id')
            ->mapWithKeys(function ($items, $coaId) {
                $firstItem = $items->first();
                $coaName = $firstItem->coa->name ?? 'Belum Memiliki COA';

                return [
                    $coaName => [
                        'coa_name' => $coaName,
                        'total_amount' => $items->sum(function ($item) {
                            return $item->payment_type === RequestPaymentType::Advance ? $item->total_amount : $item->total_act_amount;
                        }),
                        'items' => $items,
                    ],
                ];
            });
    }
}

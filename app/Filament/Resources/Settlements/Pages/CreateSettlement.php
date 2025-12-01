<?php

namespace App\Filament\Resources\Settlements\Pages;

use App\Enums\DPRStatus;
use App\Enums\RequestItemStatus;
use App\Enums\SettlementStatus;
use App\Filament\Resources\Settlements\Schemas\SettlementFormReview;
use App\Filament\Resources\Settlements\SettlementResource;
use App\Models\Coa;
use App\Models\DailyPaymentRequest;
use App\Models\RequestItem;
use App\Models\Settlement;
use App\Models\SettlementReceipt;
use App\Services\ApprovalService;
use App\Services\SettlementFormDataService;
use CreateSettlementReceipt;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

use function Symfony\Component\Clock\now;

class CreateSettlement extends CreateRecord
{
    use HasWizard;

    protected static string $resource = SettlementResource::class;

    protected static bool $canCreateAnother = false;

    protected ?array $settlementReceiptData;

    public function hasSkippableSteps(): bool
    {
        return true;
    }

    protected function getSteps(): array
    {
        return [
            Step::make('Formulir Settlement')
                ->schema([
                    Fieldset::make('Ringkasan Financial')
                        ->columnSpanFull()
                        ->columns(4)
                        ->schema([
                            TextInput::make('approved_request_amount')
                                ->label('Pengeluaran yang Disetujui')
                                ->prefix('Rp')
                                ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                ->readOnly()
                                ->dehydrated(false),
                            TextInput::make('cancelled_amount')
                                ->label('Nominal yang Dibatalkan')
                                ->prefix('Rp')
                                ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                ->readOnly()
                                ->dehydrated(false),
                            TextInput::make('spent_amount')
                                ->label('Nominal yang Dibelanjakan')
                                ->prefix('Rp')
                                ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                ->readOnly()
                                ->dehydrated(false),
                            TextInput::make('variance')
                                ->label('Selisih Nominal')
                                ->prefix('Rp')
                                ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                ->readOnly()
                                ->dehydrated(false),
                        ]),
                    CreateSettlementReceipt::make(),
                ]),
            // Step::make('Review')
            //     ->schema(SettlementFormReview::formFields()),
        ];
    }

    public static function recalculateFinancialSummary(Get $get, Set $set, string $pathPrefix = ''): void
    {
        $parseMoney = fn ($val) => (float) str_replace(['.', ','], ['', '.'], $val ?? '0');
        $formatMoney = fn ($num) => number_format($num, 2, ',', '.');

        $receiptsPath = $pathPrefix ? $pathPrefix.'settlementReceipts' : 'settlementReceipts';
        $receipts = $get($receiptsPath) ?? [];

        $approvedAmount = 0;
        $cancelledAmount = 0;
        $spentAmount = 0;

        foreach ($receipts as $receipt) {
            foreach ($receipt['requestItems'] ?? [] as $item) {
                $requestTotal = $parseMoney($item['request_total_price'] ?? '0');
                $approvedAmount += $requestTotal;

                $isRealized = $item['is_realized'] ?? true;
                if (! $isRealized) {
                    $cancelledAmount += $requestTotal;
                } else {
                    $spentAmount += $parseMoney($item['actual_total_price'] ?? '0');
                }
            }

            // // new_request_items only affects spent_amount, not approved_amount
            // foreach ($receipt['new_request_items'] ?? [] as $item) {
            //     $totalPrice = $parseMoney($item['total_price'] ?? '0');
            //     $spentAmount += $totalPrice;
            // }
        }

        $variance = $approvedAmount - $spentAmount;

        $set($pathPrefix.'approved_request_amount', $formatMoney($approvedAmount));
        $set($pathPrefix.'cancelled_amount', $formatMoney($cancelledAmount));
        $set($pathPrefix.'spent_amount', $formatMoney($spentAmount));
        $set($pathPrefix.'variance', $formatMoney($variance));
    }

    public static function getVarianceColor(?string $variance): string
    {
        if (! $variance) {
            return 'text-gray-600 dark:text-gray-400';
        }

        $amount = static::parseMoney($variance);

        if ($amount > 0) {
            return 'text-success-600 dark:text-success-400';
        } elseif ($amount < 0) {
            return 'text-danger-600 dark:text-danger-400';
        }

        return 'text-gray-600 dark:text-gray-400';
    }

    public static function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', '.');
    }

    public static function keyValueMoney(float $amount): HtmlString
    {
        return new HtmlString('<div class="flex justify-between items-center w-full"><div>Rp</div> <div>'.self::formatMoney($amount).'</div></div>');
    }

    public static function parseMoney(?string $value): float
    {
        return (float) str_replace(['.', ','], ['', '.'], $value ?? '0');
    }

    public static function calculateCoaReconciliation(Get $get): array
    {
        static $cachedResult = null;
        static $cachedHash = null;

        // Create a hash of the receipts data to detect changes
        $receipts = $get('settlementReceipts') ?? [];
        $currentHash = md5(json_encode($receipts));

        // Return cached result if data hasn't changed
        if ($cachedResult !== null && $cachedHash === $currentHash) {
            return $cachedResult;
        }

        $coaData = [];
        $parseMoney = fn ($val) => static::parseMoney($val);

        // Collect all RequestItem IDs and COA IDs for batch loading
        $requestItemIds = [];
        $coaIds = [];

        foreach ($receipts as $receipt) {
            foreach ($receipt['requestItems'] ?? [] as $item) {
                if (! empty($item['id'])) {
                    $requestItemIds[] = $item['id'];
                }
            }

            foreach ($receipt['new_request_items'] ?? [] as $newItem) {
                if (! empty($newItem['coa_id'])) {
                    $coaIds[] = $newItem['coa_id'];
                }
            }
        }

        // Batch load RequestItems with COA relationship (avoid N+1)
        $requestItems = RequestItem::query()
            ->with('coa:id,name,code')
            ->whereIn('id', array_unique($requestItemIds))
            ->get()
            ->keyBy('id');

        // Batch load COAs (avoid N+1)
        $coas = Coa::query()
            ->whereIn('id', array_unique($coaIds))
            ->get(['id', 'name', 'code'])
            ->keyBy('id');

        // Step 1: Calculate cancelled budget per COA from request_items
        foreach ($receipts as $receipt) {
            foreach ($receipt['requestItems'] ?? [] as $item) {
                $isRealized = $item['is_realized'] ?? true;

                if (! $isRealized && ! empty($item['id'])) {
                    $requestItem = $requestItems->get($item['id']);

                    if ($requestItem && $requestItem->coa) {
                        $coaId = $requestItem->coa->id;

                        if (! isset($coaData[$coaId])) {
                            $coaData[$coaId] = [
                                'id' => $coaId,
                                'name' => $requestItem->coa->name,
                                'code' => $requestItem->coa->code ?? '',
                                'cancelled_budget' => 0,
                                'new_items_spending' => 0,
                            ];
                        }

                        $cancelledAmount = $parseMoney($item['request_total_price'] ?? '0');
                        $coaData[$coaId]['cancelled_budget'] += $cancelledAmount;
                    }
                }
            }
        }

        // Step 2: Calculate new items spending per COA
        foreach ($receipts as $receipt) {
            foreach ($receipt['new_request_items'] ?? [] as $newItem) {
                $coaId = $newItem['coa_id'] ?? null;

                if ($coaId) {
                    if (! isset($coaData[$coaId])) {
                        $coa = $coas->get($coaId);
                        if ($coa) {
                            $coaData[$coaId] = [
                                'id' => $coaId,
                                'name' => $coa->name,
                                'code' => $coa->code ?? '',
                                'cancelled_budget' => 0,
                                'new_items_spending' => 0,
                            ];
                        }
                    }

                    if (isset($coaData[$coaId])) {
                        $newItemTotal = $parseMoney($newItem['total_price'] ?? '0');
                        $coaData[$coaId]['new_items_spending'] += $newItemTotal;
                    }
                }
            }
        }

        // Step 3: Calculate net offset and amount to return for each COA
        $totalCancelled = 0;
        $totalReused = 0;
        $netReturn = 0;

        foreach ($coaData as $coaId => &$coa) {
            $coa['net_offset'] = min($coa['cancelled_budget'], $coa['new_items_spending']);
            $coa['net_offset'] = min($coa['cancelled_budget'], $coa['new_items_spending']);
            $coa['amount_to_return'] = $coa['cancelled_budget'] - $coa['net_offset'];

            $totalCancelled += $coa['cancelled_budget'];
            $totalReused += $coa['net_offset'];
            $netReturn += $coa['amount_to_return'];
        }
        unset($coa);

        // Sort by COA code
        uasort($coaData, fn ($a, $b) => strcmp($a['code'], $b['code']));

        $cachedResult = [
            'coa_breakdown' => array_values($coaData),
            'total_cancelled' => $totalCancelled,
            'total_reused' => $totalReused,
            'net_return' => $netReturn,
        ];

        $cachedHash = $currentHash;

        // dd($cachedResult);

        return $cachedResult;
    }

    public static function calculateReviewData(Get $get): array
    {
        $receipts = $get('settlementReceipts') ?? [];

        if (empty($receipts)) {
            return [
                'reconciliations' => [],
                'total_amount_to_return' => 0,
                'total_amount_to_reimburse' => 0,
            ];
        }

        $parseMoney = fn ($val) => (float) str_replace(['.', ','], ['', '.'], $val ?? '0');

        // Step 1: Collect all request_item_ids for batch loading
        $requestItemIds = [];
        foreach ($receipts as $receipt) {
            foreach ($receipt['request_items'] ?? [] as $item) {
                if (! empty($item['request_item_id'])) {
                    $requestItemIds[] = $item['request_item_id'];
                }
            }
        }

        // Step 2: Batch load RequestItems with COA (avoid N+1 query problem)
        $requestItemsMap = [];
        if (! empty($requestItemIds)) {
            $requestItemsMap = \App\Models\RequestItem::query()
                ->with('coa:id,name,code')
                ->whereIn('id', array_unique($requestItemIds))
                ->get(['id', 'coa_id', 'description', 'quantity', 'unit_quantity', 'amount_per_item'])
                ->keyBy('id')
                ->toArray();
        }

        // Step 3: Group data by COA and calculate metrics
        $coaGroups = [];

        foreach ($receipts as $receiptIndex => $receipt) {
            // Process request_items
            foreach ($receipt['request_items'] ?? [] as $itemIndex => $item) {
                $requestItemId = $item['request_item_id'] ?? null;
                if (! $requestItemId || ! isset($requestItemsMap[$requestItemId])) {
                    continue;
                }

                $requestItem = $requestItemsMap[$requestItemId];
                $coaId = $requestItem['coa_id'] ?? null;

                if (! $coaId) {
                    continue;
                }

                // Initialize COA group if not exists
                if (! isset($coaGroups[$coaId])) {
                    $coaGroups[$coaId] = [
                        'coa_id' => $coaId,
                        'coa_name' => $requestItem['coa']['name'] ?? '',
                        'coa_code' => $requestItem['coa']['code'] ?? '',
                        'approved_budget' => 0,
                        'spent_budget' => 0,
                        'cancelled_budget' => 0,
                        'total_new_request_items' => 0,
                        'total_actual_spent_request_items' => 0,
                        'calculations' => [
                            'Anggaran Disetujui' => 0,
                            'Anggaran Dibatalkan' => 0,
                            'Total Realisasi' => 0,
                            'Total Item Baru' => 0,
                            'Total Pengeluaran' => 0,
                        ],
                        'items' => [],
                    ];
                }

                $isRealized = $item['is_realized'] ?? true;
                $requestTotal = $parseMoney($item['request_total_price'] ?? '0');
                $actualTotal = $parseMoney($item['actual_total_price'] ?? '0');

                // Calculate metrics
                $coaGroups[$coaId]['approved_budget'] += $requestTotal;
                $coaGroups[$coaId]['calculations']['Anggaran Disetujui'] = self::keyValueMoney($coaGroups[$coaId]['approved_budget']);

                if ($isRealized) {
                    $coaGroups[$coaId]['spent_budget'] += $actualTotal;
                    $coaGroups[$coaId]['calculations']['Total Pengeluaran'] = self::keyValueMoney($coaGroups[$coaId]['spent_budget']);
                    $coaGroups[$coaId]['total_actual_spent_request_items'] += $actualTotal;
                    $coaGroups[$coaId]['calculations']['Total Realisasi'] = self::keyValueMoney($coaGroups[$coaId]['total_actual_spent_request_items']);
                } else {
                    $coaGroups[$coaId]['cancelled_budget'] += $requestTotal;
                    $coaGroups[$coaId]['calculations']['Anggaran Dibatalkan'] = self::keyValueMoney($coaGroups[$coaId]['cancelled_budget']);
                }

                // Add item details
                $coaGroups[$coaId]['items'][] = [
                    'type' => 'request_item',
                    'receipt_index' => $receiptIndex,
                    'item_index' => $itemIndex,
                    'request_item_id' => $requestItemId,
                    'status' => $isRealized ? 'realized' : 'cancelled',
                    'description' => $requestItem['description'] ?? '',
                    'is_realized' => $isRealized,
                    'request_quantity' => $item['request_quantity'] ?? 0,
                    'actual_quantity' => $item['actual_quantity'] ?? 0,
                    'unit_quantity' => $item['request_unit_quantity'] ?? '',
                    'request_amount_per_item' => $item['request_amount_per_item'] ?? '0,00',
                    'actual_amount_per_item' => $item['actual_amount_per_item'] ?? '0,00',
                    'request_total_price' => $item['request_total_price'] ?? '0,00',
                    'actual_total_price' => $item['actual_total_price'] ?? '0,00',
                    'variance' => $item['variance'] ?? '0,00',
                ];
            }

            // Process new_request_items
            foreach ($receipt['new_request_items'] ?? [] as $itemIndex => $newItem) {
                $coaId = $newItem['coa_id'] ?? null;
                if (! $coaId) {
                    continue;
                }

                // Initialize COA group if not exists
                if (! isset($coaGroups[$coaId])) {
                    $coa = \App\Models\Coa::find($coaId, ['id', 'name', 'code']);
                    if (! $coa) {
                        continue;
                    }

                    $coaGroups[$coaId] = [
                        'coa_id' => $coaId,
                        'coa_name' => $coa->name ?? '',
                        'coa_code' => $coa->code ?? '',
                        'approved_budget' => 0,
                        'spent_budget' => 0,
                        'cancelled_budget' => 0,
                        'total_actual_spent_request_items' => 0,
                        'total_new_request_items' => 0,
                        'calculations' => [
                            'Anggaran Disetujui' => 0,
                            'Anggaran Dibatalkan' => 0,
                            'Total Realisasi' => 0,
                            'Total Item Baru' => 0,
                            'Total Pengeluaran' => 0,
                        ],
                        'items' => [],
                    ];
                }

                $totalPrice = $parseMoney($newItem['total_price'] ?? '0');

                // Calculate metrics
                $coaGroups[$coaId]['spent_budget'] += $totalPrice;
                $coaGroups[$coaId]['calculations']['Total Pengeluaran'] = self::keyValueMoney($coaGroups[$coaId]['spent_budget']);
                $coaGroups[$coaId]['total_new_request_items'] += $totalPrice;
                $coaGroups[$coaId]['calculations']['Total Item Baru'] = self::keyValueMoney($coaGroups[$coaId]['total_new_request_items']);

                // Add item details
                $coaGroups[$coaId]['items'][] = [
                    'type' => 'new_request_item',
                    'receipt_index' => $receiptIndex,
                    'item_index' => $itemIndex,
                    'coa_id' => $coaId,
                    'program_activity_id' => $newItem['program_activity_id'] ?? null,
                    'status' => 'new',
                    'description' => $newItem['item'] ?? '',
                    'actual_quantity' => $newItem['qty'] ?? 0,
                    'unit_quantity' => $newItem['unit_qty'] ?? '',
                    'actual_amount_per_item' => $newItem['base_price'] ?? '0,00',
                    'actual_total_price' => $newItem['total_price'] ?? '0,00',
                ];
            }
        }

        // Step 4: Calculate amount_to_return and amount_to_reimburse for each COA
        $totalAmountToReturn = 0;
        $totalAmountToReimburse = 0;

        foreach ($coaGroups as &$group) {
            // Amount to return: cancelled budget that wasn't used by new items
            $availableForReuse = $group['cancelled_budget'];
            $usedByNewItems = $group['total_new_request_items'];
            $group['amount_to_return'] = max(0, $group['approved_budget'] - $group['spent_budget']);
            $group['calculations']['Dikembalikan ke Finance'] = self::keyValueMoney(max(0, $group['approved_budget'] - $group['spent_budget']));
            // $group['amount_to_return'] = max(0, $availableForReuse - $usedByNewItems);
            // $group['calculations']['Dikembalikan ke Finance'] = max(0, $availableForReuse - $usedByNewItems);

            // Amount to reimburse: if spent more than approved (overspending)
            // This can happen when new items exceed cancelled budget
            $overspending = $group['spent_budget'] - $group['approved_budget'];
            $group['amount_to_reimburse'] = max(0, $overspending);
            $group['calculations']['Perlu Reimbursement'] = self::keyValueMoney(max(0, $overspending));

            $totalAmountToReturn += $group['amount_to_return'];
            $totalAmountToReimburse += $group['amount_to_reimburse'];
        }
        unset($group);

        // Step 5: Sort by COA code for better readability
        uasort($coaGroups, fn ($a, $b) => strcmp($a['coa_code'], $b['coa_code']));

        // dd([
        //     'reconciliations' => array_values($coaGroups),
        //     'total_amount_to_return' => $totalAmountToReturn,
        //     'total_amount_to_reimburse' => $totalAmountToReimburse,
        // ]);

        return [
            'reconciliations' => array_values($coaGroups),
            'total_amount_to_return' => $totalAmountToReturn,
            'total_amount_to_reimburse' => $totalAmountToReimburse,
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // dd($data);

        $approvedAmount = 0;
        $cancelledAmount = 0;
        $spentAmount = 0;
        $newRequestItemTotal = 0;

        foreach ($data['settlementReceipts'] as $receiptIndex => $receipt) {
            foreach ($receipt['requestItems'] as $itemIndex => $item) {
                if (empty($item['id'])) {
                    continue;
                }

                if ($item['id'] !== 'new') {
                    $requestItem = RequestItem::find($item['id']);

                    if (! $requestItem) {
                        // Log error or throw exception
                        Log::warning("RequestItem not found: {$item['id']}");

                        continue;
                    }

                    // Validate that RequestItem is in correct status
                    if ($requestItem->status !== RequestItemStatus::WaitingSettlement) {
                        throw new \Exception("RequestItem {$requestItem->id} is not in WaitingSettlement status");
                    }

                    // Calculate request_total_price from database values
                    $requestTotalPrice = $requestItem->quantity * $requestItem->amount_per_item;

                    // Add to $data for storage
                    $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['quantity'] = (int) $requestItem->quantity;
                    $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['unit_quantity'] = $requestItem->unit_quantity;
                    $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['amount_per_item'] = (float) $requestItem->amount_per_item;
                    $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['request_total_price'] = $requestTotalPrice;

                    // Add to approved amount
                    $approvedAmount += $requestTotalPrice;

                    // Check if realized
                    $isRealized = $item['is_realized'] ?? true;

                    if (! $isRealized) {
                        // If not realized, add to cancelled amount
                        $cancelledAmount += $requestTotalPrice;

                        // Set actual values to 0
                        $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['act_quantity'] = 0;
                        $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['act_amount_per_item'] = 0;
                        $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['actual_total_price'] = 0;
                        $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['variance'] = $requestTotalPrice;
                    } else {
                        foreach ($item['item_image'] ?? [] as $index => $file) {
                            $requestItem->addMedia($file)->toMediaCollection('request_item_image', 'local');
                        }
                        // Calculate actual_total_price from user input
                        $actualQuantity = $item['act_quantity'] ?? 0;
                        $actualAmountPerItem = $item['act_amount_per_item'] ?? 0;
                        $actualTotalPrice = $actualQuantity * $actualAmountPerItem;

                        // Validate that actual values are provided
                        if ($actualQuantity <= 0 || $actualAmountPerItem <= 0) {
                            throw new \Exception('Actual quantity and amount per item must be greater than 0 for realized items');
                        }

                        // Add to $data for storage
                        $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['actual_total_price'] = $actualTotalPrice;

                        // Add to spent amount
                        $spentAmount += $actualTotalPrice;

                        // Calculate variance
                        $variance = $requestTotalPrice - $actualTotalPrice;
                        $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['variance'] = $variance;
                    }
                } else {
                    // Calculate total_price
                    $qty = $item['act_quantity'];
                    $basePrice = $item['act_amount_per_item'];
                    $totalPrice = $qty * $basePrice;

                    // Add to $data for storage
                    $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['total_price'] = $totalPrice;

                    // Add to new_request_item_total
                    $newRequestItemTotal += $totalPrice;

                    // new_request_items only affect spent_amount
                    $spentAmount += $totalPrice;
                }
            }
        }

        // Calculate final variance
        $variance = $approvedAmount - $spentAmount;

        // Add financial summary to data
        $data['approved_request_amount'] = $approvedAmount;
        $data['cancelled_amount'] = $cancelledAmount;
        $data['spent_amount'] = $spentAmount;
        $data['new_request_item_total'] = $newRequestItemTotal;
        $data['variance'] = $variance;

        $this->settlementReceiptData = $data;

        unset($data);

        // dd($this->settlementReceiptData);

        $settlementData = [
            'submitter_id' => Auth::user()->employee->id,
            'submit_date' => now(),
            'status' => SettlementStatus::Draft,
        ];

        return $settlementData;
    }

    protected function afterCreate(): void
    {
        $settlement = $this->record;

        try {
            DB::transaction(function () use ($settlement) {
                foreach ($this->settlementReceiptData['settlementReceipts'] ?? [] as $index => $receipt) {
                    $createdReceipt = SettlementReceipt::create([
                        'settlement_id' => $settlement->id,
                        'realization_date' => $receipt['realization_date'] ?? null,
                    ]);

                    // Handle attachment media
                    if (isset($receipt['attachment'])) {
                        $createdReceipt->addMedia($receipt['attachment'])
                            ->toMediaCollection('settlement_receipt_attachments', 'local');
                    }

                    // Add the created SettlementReceipt ID to form data for processing
                    $this->settlementReceiptData['settlementReceipts'][$index]['id'] = $createdReceipt->id;
                }

                // Service 1: Extract form data (already done in mutateFormDataBeforeCreate, stored in $this->settlementData)
                $formDataService = app(SettlementFormDataService::class);
                $structuredData = $formDataService->extractFormData($this->settlementReceiptData);

                // Service 2: Process items from receipts
                $itemProcessor = app(\App\Services\SettlementItemProcessingService::class);
                $results = $itemProcessor->processReceipts(
                    $structuredData['receipts'],
                    $settlement,
                    false // create mode
                );

                // Service 3: Categorize items
                $categorized = $itemProcessor->categorizeItems(collect($results));

                // Service 4: Process settlement with same-COA reconciliation first
                $offsetService = app(\App\Services\SettlementOffsetCalculationService::class);
                $overspentResults = $categorized['overspent'] ? collect($categorized['overspent'])->flatten(1)->toArray() : [];

                // Use the new processSettlement method that handles same-COA internal reconciliation
                $processResult = $offsetService->processSettlement($categorized, $overspentResults, $settlement);

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
                $dprService = app(\App\Services\SettlementDPRService::class);
                $requiresDPR = $dprService->requiresDPRFromResults([
                    'categorized' => $categorized,
                    'reimbursement_items' => $processResult['reimbursement_items'],
                    'offset_items' => $processResult['offset_items'],
                ]);

                // Create DPR if there are items requiring approval
                if ($requiresDPR && ! empty($dprItems)) {
                    $this->createDPRForSettlement($settlement, $dprItems);
                } else {
                    // No DPR needed - set final status based on processed refund amount
                    $this->finalizeSettlementWithoutDPR($settlement, $processResult);
                }
            });
        } catch (\Exception $e) {
            // Delete the settlement if validation fails
            throw new \Exception($e);
            $settlement->delete();

            // Show validation error notification
            Notification::make()
                ->title('Validasi Gagal')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            // Redirect back to create page
            $this->halt();
        }
    }

    /**
     * Create DPR for settlement items
     */
    protected function createDPRForSettlement(Settlement $settlement, array $items): void
    {
        $dpr = DailyPaymentRequest::create([
            'request_date' => $settlement->submit_date,
            'status' => DPRStatus::Pending,
            'requester_id' => Auth::user()->employee->id,
        ]);

        // Link all items to DPR
        foreach ($items as $item) {
            $item->daily_payment_request_id = $dpr->id;
            $item->save();
        }

        // Submit through approval workflow
        app(ApprovalService::class)->submitRequest($dpr);

        // Link DPR to Settlement and set status
        $settlement->update([
            'generated_payment_request_id' => $dpr->id,
            'status' => SettlementStatus::WaitingDPRApproval,
        ]);
    }

    /**
     * Finalize settlement status when no DPR is needed
     */
    protected function finalizeSettlementWithoutDPR(
        Settlement $settlement,
        ?array $offsetResult = null
    ): void {
        // Use offset-calculated refund if available, fallback to net_settlement
        $refundAmount = $offsetResult['total_refund_amount'] ?? $settlement->net_settlement;

        if ($refundAmount > 0) {
            // Employee owes money - need refund confirmation
            $settlement->update([
                'status' => SettlementStatus::WaitingRefund,
                'refund_amount' => $refundAmount,
            ]);
        } else {
            // No refund needed - wait for FO confirmation
            $settlement->update([
                'status' => SettlementStatus::WaitingConfirmation,
            ]);

            app(\App\Services\SettlementNotificationService::class)
                ->notifyFinanceOperatorForConfirmation($settlement);
        }
    }
}

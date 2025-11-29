<?php

namespace App\Filament\Resources\Settlements\Schemas;

use App\Enums\RequestItemStatus;
use App\Enums\SettlementStatus;
use App\Models\RequestItem;
use App\Models\Settlement;
use App\Models\SettlementReceipt;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\DB;

class SettlementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                Repeater::make('settlementReceipts')
                    ->relationship()
                    ->label('Daftar Nota')
                    ->addActionLabel('Tambahkan Nota Baru')
                    ->collapsible()
                    ->itemLabel('Nota ke - ')
                    ->columnSpanFull()
                    ->itemNumbers()
                    ->columns(12)
                    ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                        // Load the SettlementReceipt model with requestItems
                        if (isset($data['id'])) {
                            $receipt = SettlementReceipt::with('requestItems')->find($data['id']);

                            if ($receipt) {
                                $requestItems = [];
                                $newRequestItems = [];

                                foreach ($receipt->requestItems as $item) {
                                    if ($item->is_unplanned) {
                                        // This is a new unplanned item
                                        $newRequestItems[] = [
                                            'unplanned_item_id' => $item->id,
                                            'coa_id' => $item->coa_id,
                                            'program_activity_id' => $item->program_activity_id,
                                            'program_activity_item_id' => $item->program_activity_item_id,
                                            'item' => str_replace('[New Item] ', '', $item->description),
                                            'qty' => $item->act_quantity,
                                            'unit_qty' => $item->unit_quantity,
                                            'base_price' => number_format($item->act_amount_per_item, 2, ',', '.'),
                                            'total_price' => number_format($item->act_quantity * $item->act_amount_per_item, 2, ',', '.'),
                                        ];
                                    } else {
                                        // This is an existing request item from DPR
                                        $requestItems[] = [
                                            'request_item_id' => $item->id,
                                            'is_realized' => $item->status !== RequestItemStatus::Cancelled,
                                            'request_quantity' => $item->quantity,
                                            'request_unit_quantity' => $item->unit_quantity,
                                            'request_amount_per_item' => number_format($item->amount_per_item, 2, ',', '.'),
                                            'request_total_price' => number_format($item->quantity * $item->amount_per_item, 2, ',', '.'),
                                            'actual_quantity' => $item->act_quantity,
                                            'actual_amount_per_item' => number_format($item->act_amount_per_item, 2, ',', '.'),
                                            'actual_total_price' => number_format($item->act_quantity * $item->act_amount_per_item, 2, ',', '.'),
                                            'variance' => number_format(
                                                ($item->quantity * $item->amount_per_item) - ($item->act_quantity * $item->act_amount_per_item),
                                                2,
                                                ',',
                                                '.'
                                            ),
                                        ];
                                    }
                                }

                                $data['request_items'] = $requestItems;
                                $data['new_request_items'] = $newRequestItems;

                                // dd($data);
                            }
                        }

                        return $data;
                    })
                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data, $rawState, $state, $record) {
                        // dd($data, $rawState, $state, $record);
                        $parsedRecords = self::parseCurrencyToFloat($state);

                        // dd($parsedRecords);
                        try {
                            DB::transaction(function () use ($record, $parsedRecords) {
                                $settlement = Settlement::find($record->settlement_id);

                                // Filament already saved settlementReceipts via relationship
                                // We only need to process the manually-submitted items (request_items, new_request_items)

                                $formData = [
                                    'settlementReceipts' => $parsedRecords,
                                ];

                                // Service 1: Extract form data
                                $formDataService = app(\App\Services\SettlementFormDataService::class);
                                $structuredData = $formDataService->extractFormData($formData);

                                // Service 2: Process items (with edit mode flag)
                                $itemProcessor = app(\App\Services\SettlementItemProcessingService::class);
                                $results = $itemProcessor->processReceipts(
                                    $structuredData['receipts'],
                                    $settlement,
                                    true // edit mode - deletes existing offset/reimburse items
                                );

                                // Track deletions: compare processed IDs to existing IDs
                                $processedIds = collect($results)->pluck('item.id')->filter()->toArray();
                                $existingIds = $settlement->settlementItems()->pluck('id')->toArray();
                                $deletedIds = array_diff($existingIds, $processedIds);

                                if (! empty($deletedIds)) {
                                    RequestItem::whereIn('id', $deletedIds)->delete();
                                }

                                // Service 3: Categorize items
                                $categorized = $itemProcessor->categorizeItems(collect($results));

                                // Service 4: Recalculate offsets
                                $offsetService = app(\App\Services\SettlementOffsetCalculationService::class);
                                $offsetResult = $offsetService->calculateOffsets($categorized, $settlement);

                                // Service 5: Create reimbursement items
                                $reimbursementItems = $offsetService->createReimbursementItems(
                                    $categorized['overspent'] ? collect($categorized['overspent'])->flatten(1)->toArray() : [],
                                    $settlement
                                );

                                // Update refund amount if in Draft status
                                if ($settlement->status === SettlementStatus::Draft) {
                                    $refundAmount = $offsetResult['total_refund_amount'] ?? 0;
                                    $settlement->update(['refund_amount' => max(0, $refundAmount)]);
                                }

                                // Note: resubmit() will handle DPR creation when "Save & Submit" is clicked
                            });
                            unset($data['request_items'], $data['new_request_items']);

                            return $data;
                        } catch (\Exception $e) {
                            // Show validation error notification
                            Notification::make()
                                ->title('Validasi Gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();

                            // Halt the save process
                        }

                    })
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('attachment')
                            ->collection('settlement_receipt_attachments')
                            ->required()
                            ->label('Upload Nota')
                            ->multiple(false)
                            ->previewable()
                            ->columnSpan(6),
                        DatePicker::make('realization_date')
                            ->required()
                            ->native(false)
                            ->label('Tanggal Realisasi')
                            ->belowLabel('Sesuai Nota')
                            ->displayFormat('j M Y')
                            ->columnSpan(6),
                        Repeater::make('request_items')
                            ->label('Pilih Item Request')
                            ->addActionLabel('Tambah Item Request')
                            ->compact()
                            ->addActionAlignment(Alignment::Start)
                            ->columnSpanFull()
                            // ->dehydrated(false)
                            ->addAction(
                                fn (Action $action) => $action->after(function (Get $get, Set $set) {
                                    static::recalculateFinancialSummary($get, $set, '../../');
                                })
                            )
                            ->deleteAction(
                                fn (Action $action) => $action->after(function (Get $get, Set $set) {
                                    static::recalculateFinancialSummary($get, $set, '../../');
                                })
                            )
                            ->extraAttributes([
                                'class' => 'overflow-x-auto p-0.5 pb-1.5 *:first:table-fixed *:first:[&_thead]:[&_th]:first:w-[46px] *:first:table-fixed *:first:[&_thead]:[&_th]:last:w-[46px]',
                            ])
                            ->table([
                                TableColumn::make('Request Item')->width('300px'),
                                // TableColumn::make('Request ID')->width('250px'),
                                TableColumn::make('Terealisasi ?')->width('150px'),
                                TableColumn::make('Foto Item/Produk')->width('350px'),
                                TableColumn::make('Qty (Request)')->width('150px'),
                                TableColumn::make('Qty (Aktual)')->width('150px'),
                                TableColumn::make('Unit Qty')->width('175px'),
                                TableColumn::make('Harga/item (Request)')->width('250px'),
                                TableColumn::make('Harga/item (Aktual)')->width('250px'),
                                TableColumn::make('Total Request')->width('250px'),
                                TableColumn::make('Total Aktual')->width('250px'),
                                TableColumn::make('Variasi')->width('250px'),
                            ])
                            ->schema([
                                Select::make('request_item_id')
                                    ->label('Pilih Item Request')
                                    ->requiredWith('actual_quantity,actual_amount_per_item')
                                    ->validationMessages([
                                        'required_with' => 'Item wajib dipilih',
                                    ])
                                    ->native(true)
                                    ->live()
                                    ->partiallyRenderComponentsAfterStateUpdated(['request_quantity', 'request_unit_quantity', 'request_amount_per_item'])
                                    ->options(function () {
                                        return RequestItem::query()
                                            ->where('request_items.status', '=', RequestItemStatus::WaitingSettlementReview->value)
                                            ->orWhere('request_items.status', '=', RequestItemStatus::Cancelled->value)
                                            ->orWhere('request_items.status', '=', RequestItemStatus::WaitingRefund->value)
                                            ->join('daily_payment_requests', 'request_items.daily_payment_request_id', '=', 'daily_payment_requests.id')
                                            ->get(['request_items.id', 'request_items.description', 'daily_payment_requests.request_number as daily_payment_request_number'])
                                            ->groupBy('daily_payment_request_number')
                                            ->map(fn ($items) => $items->pluck('description', 'id'))
                                            ->toArray();
                                    })
                                    ->disableOptionWhen(function ($value, $state, Get $get) {
                                        $parentData = $get('../../../../') ?? [];
                                        $allReceipts = $parentData['settlementReceipts'] ?? [];

                                        $selectedIds = collect($allReceipts)
                                            ->pluck('request_items.*.request_item_id')
                                            ->flatten()
                                            ->filter()
                                            ->all();

                                        return in_array($value, $selectedIds) && $value !== $state;
                                    })
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $parseMoney = fn ($val) => (float) str_replace(['.', ','], ['', '.'], $val ?? '0');
                                        $formatMoney = fn ($num) => number_format($num, 2, ',', '.');

                                        $currentRequestTotal = 0;

                                        if ($state) {
                                            $requestItem = RequestItem::with('dailyPaymentRequest:id,request_number')
                                                ->where('id', $state)
                                                ->first(['quantity', 'unit_quantity', 'amount_per_item', 'daily_payment_request_id']);

                                            $set('request_quantity', (int) $requestItem->quantity);
                                            $set('request_unit_quantity', $requestItem->unit_quantity);
                                            $set('request_amount_per_item', $formatMoney($requestItem->amount_per_item));

                                            $currentRequestTotal = $requestItem->quantity * $requestItem->amount_per_item;
                                            $set('request_total_price', $formatMoney($currentRequestTotal));
                                        } else {
                                            $set('request_quantity', null);
                                            $set('request_unit_quantity', null);
                                            $set('request_amount_per_item', null);
                                            $set('request_total_price', null);
                                        }

                                        // Recalculate financial summary
                                        $rootData = $get('../../../../') ?? [];
                                        $receipts = $rootData['settlementReceipts'] ?? [];

                                        $approvedAmount = 0;
                                        $cancelledAmount = 0;
                                        $spentAmount = 0;

                                        foreach ($receipts as $receipt) {
                                            foreach ($receipt['request_items'] ?? [] as $item) {
                                                // Check if this is current item
                                                $isCurrentItem = ($item['request_item_id'] ?? null) === $state;

                                                $requestTotal = $isCurrentItem ? $currentRequestTotal : $parseMoney($item['request_total_price'] ?? '0');
                                                $approvedAmount += $requestTotal;

                                                $isRealized = $item['is_realized'] ?? true;
                                                if (! $isRealized) {
                                                    $cancelledAmount += $requestTotal;
                                                } else {
                                                    $spentAmount += $parseMoney($item['actual_total_price'] ?? '0');
                                                }
                                            }

                                            // new_request_items only affects spent_amount
                                            foreach ($receipt['new_request_items'] ?? [] as $item) {
                                                $totalPrice = $parseMoney($item['total_price'] ?? '0');
                                                $spentAmount += $totalPrice;
                                            }
                                        }

                                        $variance = $approvedAmount - $spentAmount;

                                        $set('../../../../approved_request_amount', $formatMoney($approvedAmount));
                                        $set('../../../../cancelled_amount', $formatMoney($cancelledAmount));
                                        $set('../../../../spent_amount', $formatMoney($spentAmount));
                                        $set('../../../../variance', $formatMoney($variance));
                                    }),
                                Checkbox::make('is_realized')
                                    ->label('Terealisasi?')
                                    ->default(true)
                                    ->inline(false)
                                    ->extraAttributes(
                                        [
                                            'class' => 'mx-auto',
                                        ])
                                    ->live()
                                    ->afterStateUpdatedJs(<<<'JS'
                                        const formatMoney = (num) => {
                                            if (num === 0) return '0,00';
                                            const isNegative = num < 0;
                                            const absNum = Math.abs(num);
                                            const formatted = absNum.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                            return isNegative ? '-' + formatted : formatted;
                                        };
                                        const parseMoney = (val) => parseFloat((val ?? '0').toString().replace(/\./g, '').replace(',', '.')) || 0;
                                        
                                        // Set values first
                                        if (!$state) {
                                            $set('actual_quantity', 0);
                                            $set('actual_amount_per_item', '0');
                                            $set('actual_total_price', '0');
                                            const reqTotal = parseMoney($get('request_total_price'));
                                            $set('variance', formatMoney(0 - reqTotal));
                                        }
                                        
                                        // Store current item's known values for calculation
                                        const currentRequestTotal = parseMoney($get('request_total_price'));
                                        const currentActualTotal = $state ? parseMoney($get('actual_total_price')) : 0;
                                        const currentIsRealized = $state;
                                        const currentItemId = $get('request_item_id');
                                        
                                        // Recalculate financial summary
                                        const receipts = $get('../../../../settlementReceipts') ?? {};
                                        
                                        let approvedAmount = 0;
                                        let cancelledAmount = 0;
                                        let spentAmount = 0;
                                        
                                        Object.values(receipts).forEach(receipt => {
                                            const requestItems = receipt?.request_items ?? {};
                                            Object.values(requestItems).forEach(item => {
                                                const itemRequestItemId = item?.request_item_id;
                                                const requestTotal = parseMoney(item?.request_total_price);
                                                
                                                // Check if this is the current item being edited
                                                const isCurrentItem = itemRequestItemId && itemRequestItemId === currentItemId;
                                                
                                                approvedAmount += requestTotal;
                                                
                                                // Use known values for current item, otherwise use stored values
                                                const isRealized = isCurrentItem 
                                                    ? currentIsRealized 
                                                    : (item?.is_realized !== false && item?.is_realized !== 0 && item?.is_realized !== '0' && item?.is_realized !== null);
                                                const actualTotal = isCurrentItem ? currentActualTotal : parseMoney(item?.actual_total_price);
                                                
                                                if (!isRealized) {
                                                    cancelledAmount += requestTotal;
                                                } else {
                                                    spentAmount += actualTotal;
                                                }
                                            });
                                            
                                            // new_request_items only affects spent_amount
                                            const newRequestItems = receipt?.new_request_items ?? {};
                                            Object.values(newRequestItems).forEach(item => {
                                                const totalPrice = parseMoney(item?.total_price);
                                                spentAmount += totalPrice;
                                            });
                                        });
                                        
                                        const variance =  approvedAmount - spentAmount;
                                        
                                        $set('../../../../approved_request_amount', formatMoney(approvedAmount));
                                        $set('../../../../cancelled_amount', formatMoney(cancelledAmount));
                                        $set('../../../../spent_amount', formatMoney(spentAmount));
                                        $set('../../../../variance', formatMoney(variance));
                                    JS),
                                SpatieMediaLibraryFileUpload::make('item_image')
                                    ->label('Foto Item/Produk')
                                    ->collection('request_item_image')
                                    ->model(fn (Get $get) => $get('request_item_id') ? RequestItem::find($get('request_item_id')) : null)
                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'application/pdf'])
                                    ->multiple()
                                    ->appendFiles()
                                    ->maxSize(4096)
                                    ->columnSpanFull()
                                    ->previewable(true)
                                    ->openable(true)
                                    ->required(fn (Get $get) => $get('request_item_id') && $get('is_realized'))
                                    ->validationMessages([
                                        'required' => 'Foto Item/Produk diperlukan untuk Settlement',
                                    ]),
                                TextInput::make('request_quantity')
                                    ->label('Qty')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->numeric(),
                                TextInput::make('actual_quantity')
                                    ->label('Qty')
                                    ->requiredWith('request_item_id,actual_amount_per_item')
                                    ->validationMessages([
                                        'required_with' => 'Jumlah Aktual wajib diisi',
                                    ])
                                    ->numeric()
                                    ->readOnly(fn (Get $get) => ! $get('is_realized'))
                                    ->afterStateUpdatedJs(<<<'JS'
                                        const formatMoney = (num) => {
                                            if (num === 0) return '0,00';
                                            const isNegative = num < 0;
                                            const absNum = Math.abs(num);
                                            const formatted = absNum.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                            return isNegative ? '-' + formatted : formatted;
                                        };
                                        const parseMoney = (val) => parseFloat((val ?? '0').toString().replace(/\./g, '').replace(',', '.')) || 0;
                                        
                                        // Calculate actual_total_price
                                        const basePriceNum = parseMoney($get('actual_amount_per_item'));
                                        const qtyNum = parseFloat($state) || 0;
                                        const total = qtyNum * basePriceNum;

                                        $set('actual_total_price', formatMoney(total));
                                        
                                        // Calculate item variance
                                        const requestTotal = parseMoney($get('request_total_price'));
                                        $set('variance', formatMoney(requestTotal - total));
                                        
                                        // Store current item's known values
                                        const currentActualTotal = total;
                                        const currentItemId = $get('request_item_id');
                                        
                                        // Recalculate financial summary
                                        const receipts = $get('../../../../settlementReceipts') ?? {};
                                        
                                        let approvedAmount = 0;
                                        let cancelledAmount = 0;
                                        let spentAmount = 0;
                                        
                                        Object.values(receipts).forEach(receipt => {
                                            const requestItems = receipt?.request_items ?? {};
                                            Object.values(requestItems).forEach(item => {
                                                const itemRequestItemId = item?.request_item_id;
                                                const reqTotal = parseMoney(item?.request_total_price);
                                                
                                                const isCurrentItem = itemRequestItemId && itemRequestItemId === currentItemId;
                                                
                                                approvedAmount += reqTotal;
                                                
                                                const isRealized = item?.is_realized !== false && item?.is_realized !== 0 && item?.is_realized !== '0' && item?.is_realized !== null;
                                                const actualTotal = isCurrentItem ? currentActualTotal : parseMoney(item?.actual_total_price);
                                                
                                                if (!isRealized) {
                                                    cancelledAmount += reqTotal;
                                                } else {
                                                    spentAmount += actualTotal;
                                                }
                                            });
                                            
                                            const newRequestItems = receipt?.new_request_items ?? {};
                                            Object.values(newRequestItems).forEach(item => {
                                                const totalPrice = parseMoney(item?.total_price);
                                                spentAmount += totalPrice;
                                            });
                                        });
                                        
                                        const summaryVariance =  approvedAmount - spentAmount;
                                        
                                        $set('../../../../approved_request_amount', formatMoney(approvedAmount));
                                        $set('../../../../cancelled_amount', formatMoney(cancelledAmount));
                                        $set('../../../../spent_amount', formatMoney(spentAmount));
                                        $set('../../../../variance', formatMoney(summaryVariance));
                                    JS),
                                TextInput::make('request_unit_quantity')
                                    ->label('Unit Qty')
                                    ->readOnly()
                                    ->dehydrated(false),
                                TextInput::make('request_amount_per_item')
                                    ->label('Harga/item')
                                    ->prefix('Rp')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->afterStateUpdatedJs(<<<'JS'
                                        const cleanPrice = ($state ?? '0').toString().replace(/\./g, '').replace(',', '.');
                                        const priceNum = parseFloat(cleanPrice) || 0;
                                        const qtyNum = parseFloat($get('request_quantity')) || 0;
                                        const total = qtyNum * priceNum;

                                        if (total === 0) {
                                            $set('request_total_price', '');
                                        } else {
                                            const formatted = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                            $set('request_total_price', formatted);
                                        }
                                    JS),
                                TextInput::make('actual_amount_per_item')
                                    ->label('Harga/item')
                                    ->prefix('Rp')
                                    ->requiredWith('request_item_id,actual_quantity')
                                    ->readOnly(fn (Get $get) => ! $get('is_realized'))
                                    ->validationMessages([
                                        'required_with' => 'Harga per item wajib diisi',
                                    ])
                                    ->dehydrateStateUsing(fn ($rawState) => $rawState ? (float) str_replace(['.', ','], ['', '.'], $rawState) : null)
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                    ->afterStateUpdatedJs(<<<'JS'
                                        const formatMoney = (num) => {
                                            if (num === 0) return '0,00';
                                            const isNegative = num < 0;
                                            const absNum = Math.abs(num);
                                            const formatted = absNum.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                            return isNegative ? '-' + formatted : formatted;
                                        };
                                        const parseMoney = (val) => parseFloat((val ?? '0').toString().replace(/\./g, '').replace(',', '.')) || 0;
                                        
                                        // Calculate actual_total_price
                                        const priceNum = parseMoney($state);
                                        const qtyNum = parseFloat($get('actual_quantity')) || 0;
                                        const total = qtyNum * priceNum;

                                        $set('actual_total_price', formatMoney(total));
                                        
                                        // Calculate item variance
                                        const requestTotal = parseMoney($get('request_total_price'));
                                        $set('variance', formatMoney(requestTotal - total));
                                        
                                        // Store current item's known values
                                        const currentActualTotal = total;
                                        const currentItemId = $get('request_item_id');
                                        
                                        // Recalculate financial summary
                                        const receipts = $get('../../../../settlementReceipts') ?? {};
                                        
                                        let approvedAmount = 0;
                                        let cancelledAmount = 0;
                                        let spentAmount = 0;
                                        
                                        Object.values(receipts).forEach(receipt => {
                                            const requestItems = receipt?.request_items ?? {};
                                            Object.values(requestItems).forEach(item => {
                                                const itemRequestItemId = item?.request_item_id;
                                                const reqTotal = parseMoney(item?.request_total_price);
                                                
                                                const isCurrentItem = itemRequestItemId && itemRequestItemId === currentItemId;
                                                
                                                approvedAmount += reqTotal;
                                                
                                                const isRealized = item?.is_realized !== false && item?.is_realized !== 0 && item?.is_realized !== '0' && item?.is_realized !== null;
                                                const actualTotal = isCurrentItem ? currentActualTotal : parseMoney(item?.actual_total_price);
                                                
                                                if (!isRealized) {
                                                    cancelledAmount += reqTotal;
                                                } else {
                                                    spentAmount += actualTotal;
                                                }
                                            });
                                            
                                            const newRequestItems = receipt?.new_request_items ?? {};
                                            Object.values(newRequestItems).forEach(item => {
                                                const totalPrice = parseMoney(item?.total_price);
                                                spentAmount += totalPrice;
                                            });
                                        });
                                        
                                        const summaryVariance =  approvedAmount - spentAmount;
                                        
                                        $set('../../../../approved_request_amount', formatMoney(approvedAmount));
                                        $set('../../../../cancelled_amount', formatMoney(cancelledAmount));
                                        $set('../../../../spent_amount', formatMoney(spentAmount));
                                        $set('../../../../variance', formatMoney(summaryVariance));
                                    JS),
                                TextInput::make('request_total_price')
                                    ->label('Total')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                    ->readOnly()
                                    ->dehydrated(false),
                                TextInput::make('actual_total_price')
                                    ->label('Total')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->afterStateUpdatedJs(<<<'JS'
                                        const cleanActualTotalPrice = ($state ?? 0).toString().replace(/\./g, '').replace(',', '.');
                                        const cleanRequestTotalPrice = ($get('request_total_price') ?? '0').toString().replace(/\./g, '').replace(',', '.');

                                        $set('variance', (parseFloat(cleanRequestTotalPrice) - parseFloat(cleanActualTotalPrice)).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1'))
                                    JS),
                                TextInput::make('variance')
                                    ->label('Variasi')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                    ->readOnly()
                                    ->dehydrated(false),
                            ])
                            ->columns(12),
                        Repeater::make('new_request_items')
                            ->label('Daftarkan Item Request Baru (Jika ada)')
                            ->addActionLabel('Tambah Item Baru')
                            // ->dehydrated(false)
                            ->belowLabel('Menambahkan Item Baru akan membuat Payment Request baru')
                            ->compact()
                            ->addActionAlignment(Alignment::Start)
                            ->columnSpanFull()
                            ->addAction(
                                fn (Action $action) => $action->after(function (Get $get, Set $set) {
                                    static::recalculateFinancialSummary($get, $set, '../../');
                                })
                            )
                            ->deleteAction(
                                fn (Action $action) => $action->after(function (Get $get, Set $set) {
                                    static::recalculateFinancialSummary($get, $set, '../../');
                                })
                            )
                            ->extraAttributes([
                                'class' => 'overflow-x-auto p-0.5 pb-1.5 *:first:table-fixed *:first:[&_thead]:[&_th]:first:w-[46px] *:first:table-fixed *:first:[&_thead]:[&_th]:last:w-[46px]',
                            ])
                            ->table([
                                TableColumn::make('COA')->width('280px'),
                                TableColumn::make('Aktivitas')->width('280px'),
                                TableColumn::make('Item')->width('300px'),
                                TableColumn::make('Qty')->width('100px'),
                                TableColumn::make('Unit Qty')->width('150px'),
                                TableColumn::make('Base Price')->width('250px'),
                                TableColumn::make('Total Price')->width('250px'),
                                TableColumn::make('Foto Item/Produk')->width('350px'),
                            ])
                            ->schema([
                                Select::make('coa_id')
                                    ->label('Pilih COA')
                                    ->required()
                                    ->native(true)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $set('program_activity_id', null);
                                        $set('program_activity_item_id', null);
                                    }),
                                Select::make('program_activity_id')
                                    ->label('Pilih Aktivitas')
                                    ->required()
                                    ->native(true)
                                    ->live(onBlur: true)
                                    ->options(function (Get $get) {
                                        $coaId = $get('coa_id');
                                        if (! $coaId) {
                                            return [];
                                        }

                                        return \App\Models\ProgramActivity::where('coa_id', $coaId)
                                            ->with('programActivityItems')
                                            ->get()
                                            ->mapWithKeys(fn ($item) => [$item->id => $item->name])
                                            ->toArray();
                                    })
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $set('program_activity_item_id', null);
                                    }),
                                Select::make('program_activity_item_id')
                                    ->label('Pilih Item')
                                    ->required()
                                    ->native(true)
                                    ->live(onBlur: true)
                                    ->options(function (Get $get) {
                                        $activityId = $get('program_activity_id');
                                        if (! $activityId) {
                                            return [];
                                        }

                                        return \App\Models\ProgramActivityItem::where('program_activity_id', $activityId)
                                            ->get()
                                            ->mapWithKeys(fn ($item) => [$item->id => $item->item])
                                            ->toArray();
                                    }),
                                TextInput::make('item')
                                    ->label('Deskripsi Item')
                                    ->required()
                                    ->live(onBlur: true),
                                TextInput::make('qty')
                                    ->label('Qty')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->default(1)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $parseMoney = fn ($val) => (float) str_replace(['. ', ','], ['', '.'], $val ?? '0');
                                        $formatMoney = fn ($num) => number_format($num, 2, ',', '.');

                                        $quantity = (float) $state;
                                        $basePrice = $parseMoney($get('base_price') ?? '0');
                                        $total = $quantity * $basePrice;

                                        $set('total_price', $formatMoney($total));

                                        // Recalculate financial summary
                                        $rootData = $get('../../../../') ?? [];
                                        $receipts = $rootData['settlementReceipts'] ?? [];

                                        $approvedAmount = 0;
                                        $cancelledAmount = 0;
                                        $spentAmount = 0;

                                        foreach ($receipts as $receipt) {
                                            foreach ($receipt['request_items'] ?? [] as $item) {
                                                $requestTotal = $parseMoney($item['request_total_price'] ?? '0');
                                                $approvedAmount += $requestTotal;

                                                $isRealized = $item['is_realized'] ?? true;
                                                if (! $isRealized) {
                                                    $cancelledAmount += $requestTotal;
                                                } else {
                                                    $spentAmount += $parseMoney($item['actual_total_price'] ?? '0');
                                                }
                                            }

                                            // new_request_items only affects spent_amount
                                            foreach ($receipt['new_request_items'] ?? [] as $item) {
                                                $totalPrice = $parseMoney($item['total_price'] ?? '0');
                                                $spentAmount += $totalPrice;
                                            }
                                        }

                                        $variance = $approvedAmount - $spentAmount;

                                        $set('../../../../approved_request_amount', $formatMoney($approvedAmount));
                                        $set('../../../../cancelled_amount', $formatMoney($cancelledAmount));
                                        $set('../../../../spent_amount', $formatMoney($spentAmount));
                                        $set('../../../../variance', $formatMoney($variance));
                                    }),
                                TextInput::make('unit_qty')
                                    ->label('Unit Qty')
                                    ->required()
                                    ->live(onBlur: true),
                                TextInput::make('base_price')
                                    ->label('Harga/item')
                                    ->required()
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                    ->live(onBlur: true)
                                    ->dehydrateStateUsing(fn ($state) => (float) str_replace(['.', ','], ['', '.'], $state ?? '0'))
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $parseMoney = fn ($val) => (float) str_replace(['. ', ','], ['', '.'], $val ?? '0');
                                        $formatMoney = fn ($num) => number_format($num, 2, ',', '.');

                                        $price = (float) $state;
                                        $quantity = (float) ($get('qty') ?? 0);
                                        $total = $quantity * $price;

                                        $set('total_price', $formatMoney($total));

                                        // Recalculate financial summary
                                        $rootData = $get('../../../../') ?? [];
                                        $receipts = $rootData['settlementReceipts'] ?? [];

                                        $approvedAmount = 0;
                                        $cancelledAmount = 0;
                                        $spentAmount = 0;

                                        foreach ($receipts as $receipt) {
                                            foreach ($receipt['request_items'] ?? [] as $item) {
                                                $requestTotal = $parseMoney($item['request_total_price'] ?? '0');
                                                $approvedAmount += $requestTotal;

                                                $isRealized = $item['is_realized'] ?? true;
                                                if (! $isRealized) {
                                                    $cancelledAmount += $requestTotal;
                                                } else {
                                                    $spentAmount += $parseMoney($item['actual_total_price'] ?? '0');
                                                }
                                            }

                                            // new_request_items only affects spent_amount
                                            foreach ($receipt['new_request_items'] ?? [] as $item) {
                                                $totalPrice = $parseMoney($item['total_price'] ?? '0');
                                                $spentAmount += $totalPrice;
                                            }
                                        }

                                        $variance = $approvedAmount - $spentAmount;

                                        $set('../../../../approved_request_amount', $formatMoney($approvedAmount));
                                        $set('../../../../cancelled_amount', $formatMoney($cancelledAmount));
                                        $set('../../../../spent_amount', $formatMoney($spentAmount));
                                        $set('../../../../variance', $formatMoney($variance));
                                    }),
                                TextInput::make('total_price')
                                    ->label('Total')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                    ->readOnly()
                                    ->dehydrated(false),
                                SpatieMediaLibraryFileUpload::make('item_image')
                                    ->label('Foto Item/Produk')
                                    ->collection('request_item_image')
                                    ->model(fn (Get $get) => $get('unplanned_item_id') ? RequestItem::find($get('unplanned_item_id')) : null)
                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'application/pdf'])
                                    ->multiple()
                                    ->appendFiles()
                                    ->maxSize(4096)
                                    ->columnSpanFull()
                                    ->previewable(true)
                                    ->required()
                                    ->validationMessages([
                                        'required' => 'Foto Item/Produk diperlukan untuk Settlement',
                                    ]),
                            ])
                            ->columns(12),
                    ]),
            ]);
    }

    /**
     * Recalculate financial summary for receipts
     */
    protected static function recalculateFinancialSummary(Get $get, Set $set, string $pathPrefix = ''): void
    {
        $parseMoney = fn ($val) => (float) str_replace(['.', ','], ['', '.'], $val ?? '0');
        $formatMoney = fn ($num) => number_format($num, 2, ',', '.');

        $rootData = $get($pathPrefix) ?? [];
        $receipts = $rootData['settlementReceipts'] ?? [];

        $approvedAmount = 0;
        $cancelledAmount = 0;
        $spentAmount = 0;

        foreach ($receipts as $receipt) {
            foreach ($receipt['request_items'] ?? [] as $item) {
                $requestTotal = $parseMoney($item['request_total_price'] ?? '0');
                $approvedAmount += $requestTotal;

                $isRealized = $item['is_realized'] ?? true;
                if (! $isRealized) {
                    $cancelledAmount += $requestTotal;
                } else {
                    $spentAmount += $parseMoney($item['actual_total_price'] ?? '0');
                }
            }

            // new_request_items only affects spent_amount
            foreach ($receipt['new_request_items'] ?? [] as $item) {
                $totalPrice = $parseMoney($item['total_price'] ?? '0');
                $spentAmount += $totalPrice;
            }
        }

        $variance = $approvedAmount - $spentAmount;

        $set($pathPrefix.'approved_request_amount', $formatMoney($approvedAmount));
        $set($pathPrefix.'cancelled_amount', $formatMoney($cancelledAmount));
        $set($pathPrefix.'spent_amount', $formatMoney($spentAmount));
        $set($pathPrefix.'variance', $formatMoney($variance));
    }

    protected static function parseIdrToFloat(string $currency): float
    {
        // Remove thousand separator (.) and replace decimal separator (,) with (.)
        $parsed = str_replace('.', '', $currency);
        $parsed = str_replace(',', '.', $parsed);

        return (float) $parsed;
    }

    protected static function parseCurrencyToFloat(array $records): array
    {
        $currencyFields = [
            'request_amount_per_item',
            'request_total_price',
            'actual_amount_per_item',
            'actual_total_price',
            'variance',
        ];

        foreach ($records as $recordKey => $record) {
            // Parse request_items
            if (isset($record['request_items']) && is_array($record['request_items'])) {
                foreach ($record['request_items'] as $itemKey => $item) {
                    foreach ($currencyFields as $field) {
                        if (isset($item[$field]) && is_string($item[$field])) {
                            $records[$recordKey]['request_items'][$itemKey][$field] = self::parseIdrToFloat($item[$field]);
                        }
                    }
                }
            }

            // Parse new_request_items (if any)
            if (isset($record['new_request_items']) && is_array($record['new_request_items'])) {
                foreach ($record['new_request_items'] as $itemKey => $item) {
                    foreach ($currencyFields as $field) {
                        if (isset($item[$field]) && is_string($item[$field])) {
                            $records[$recordKey]['new_request_items'][$itemKey][$field] = self::parseIdrToFloat($item[$field]);
                        }
                    }
                }
            }
        }

        return $records;
    }
}

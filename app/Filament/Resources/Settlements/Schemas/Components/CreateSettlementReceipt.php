<?php

use App\Enums\RequestItemStatus;
use App\Models\Coa;
use App\Models\ProgramActivity;
use App\Models\ProgramActivityItem;
use App\Models\RequestItem;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Alignment;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CreateSettlementReceipt
{
    public static function make(): Repeater
    {
        return Repeater::make('settlementReceipts')
            ->label('Daftar Nota')
            ->addActionLabel('Tambahkan Nota Baru')
            ->collapsible()
            ->itemLabel('Nota ke - ')
            ->itemNumbers()
            ->columns(12)
            ->columnSpanFull()
                // ->addAction(
                //     fn (Action $action) => $action->after(function (Get $get, Set $set) {
                //         CreateSettlement::recalculateFinancialSummary($get, $set, '');
                //     })
                // )
                // ->deleteAction(
                //     fn (Action $action) => $action->after(function (Get $get, Set $set) {
                //         CreateSettlement::recalculateFinancialSummary($get, $set, '');
                //     })
                // )
                // ->extraItemActions([
                //     Action::make('sendEmail')
                //         ->icon(Heroicon::Envelope),
                // ])
            ->schema([
                SpatieMediaLibraryFileUpload::make('attachment')
                    ->required()
                    // ->collection('settlement_receipt_attachments')
                    ->dehydrated(true)
                    ->storeFiles(false)
                    ->label('Upload Nota')
                    ->multiple(false)
                    ->columnSpan(6),
                DatePicker::make('realization_date')
                    ->required()
                    ->native(false)
                    ->label('Tanggal Realisasi')
                    ->belowLabel('Sesuai Nota')
                    ->displayFormat('j M Y')
                    ->columnSpan(3),
                Repeater::make('requestItems')
                    ->label('Pilih Item Request')
                    ->addActionLabel('Tambah Item Request')
                    ->compact()
                    ->addActionAlignment(Alignment::Start)
                    ->columnSpanFull()
                    // ->mutateRelationshipDataBeforeCreateUsing(function ($data) {
                    //     if ($data['id'] === 'new') {
                    //         unset($data['id']);
                    //         return $data;
                    //     } else {
                    //         RequestItem::upd
                    //     }
                    // })
                    // ->addAction(
                    //     fn (Action $action) => $action->after(function (Get $get, Set $set) {
                    //         CreateSettlement::recalculateFinancialSummary($get, $set, '../../');
                    //     })
                    // )
                    // ->deleteAction(
                    //     fn (Action $action) => $action->after(function (Get $get, Set $set) {
                    //         CreateSettlement::recalculateFinancialSummary($get, $set, '../../');
                    //     })
                    // )
                    ->extraAttributes([
                        'class' => 'overflow-x-auto p-0.5 pb-1.5 *:first:table-fixed *:first:[&_thead]:[&_th]:first:w-[46px] *:first:table-fixed *:first:[&_thead]:[&_th]:last:w-[46px]',
                    ])
                    ->table([
                        TableColumn::make('Request Item')->width('300px'),
                        // TableColumn::make('Request ID')->width('250px'),
                        TableColumn::make('Terealisasi ?')->width('150px'),
                        TableColumn::make('COA')->width('300px'),
                        TableColumn::make('Aktivitas')->width('300px'),
                        TableColumn::make('Item')->width('300px'),
                        TableColumn::make('Qty (Request)')->width('150px'),
                        TableColumn::make('Qty (Aktual)')->width('150px'),
                        TableColumn::make('Unit Qty')->width('175px'),
                        TableColumn::make('Harga/item (Request)')->width('250px'),
                        TableColumn::make('Harga/item (Aktual)')->width('250px'),
                        TableColumn::make('Total Request')->width('250px'),
                        TableColumn::make('Total Aktual')->width('250px'),
                        TableColumn::make('Variasi')->width('250px'),
                        TableColumn::make('Foto Item/Produk')->width('350px'),
                    ])
                    ->schema([
                        Select::make('id')
                            ->label('Pilih Item Request')
                            ->requiredWith('act_quantity,act_amount_per_item')
                            ->validationMessages([
                                'required_with' => 'Item wajib dipilih',
                            ])
                            ->native(true)
                            ->live()
                            // ->partiallyRenderComponentsAfterStateUpdated(['request_quantity', 'request_unit_quantity', 'request_amount_per_item'])
                            ->options(function ($operation) {
                                $options = RequestItem::query()
                                    ->whereHas('dailyPaymentRequest', fn (Builder $query) => $query->where('requester_id', Auth::user()->employee->id))
                                    ->where('request_items.status', '=', RequestItemStatus::WaitingSettlement->value)
                                    ->join('daily_payment_requests', 'request_items.daily_payment_request_id', '=', 'daily_payment_requests.id')
                                    ->get(['request_items.id', 'request_items.description', 'daily_payment_requests.request_number as daily_payment_request_number'])
                                    ->groupBy('daily_payment_request_number')
                                    ->map(fn ($items) => $items->pluck('description', 'id'))
                                    ->toArray();

                                $options = [
                                    'new' => 'Item Baru',
                                    ...$options,
                                ];

                                return $options;
                            })
                            ->disableOptionWhen(function ($value, $state, Get $get) {
                                $parentData = $get('../../../../') ?? [];
                                $allReceipts = $parentData['settlementReceipts'] ?? [];

                                $selectedIds = collect($allReceipts)
                                    ->pluck('requestItems.*.id')
                                    ->flatten()
                                    ->filter(fn ($value) => $value !== 'new')
                                    ->all();

                                return ($value === 'new' || $state === 'new') ? false : in_array($value, $selectedIds) && $value !== $state;
                            })
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $parseMoney = fn ($val) => (float) str_replace(['.', ','], ['', '.'], $val ?? '0');
                                $formatMoney = fn ($num) => number_format($num, 2, ',', '.');

                                $currentRequestTotal = 0;

                                if ($state && $state !== 'new') {
                                    $requestItem = RequestItem::with(['dailyPaymentRequest:id,request_number', 'coa:id,name', 'programActivity:id,name'])
                                        ->where('id', $state)
                                        ->first(['quantity', 'unit_quantity', 'amount_per_item', 'daily_payment_request_id', 'coa_id', 'program_activity_id', 'description']);

                                    $set('coa_id', $requestItem->coa_id);
                                    $set('program_activity_id', $requestItem->program_activity_id);
                                    $set('description', $requestItem->description);
                                    $set('quantity', (int) $requestItem->quantity);
                                    $set('unit_quantity', $requestItem->unit_quantity);
                                    $set('amount_per_item', $formatMoney($requestItem->amount_per_item));

                                    $currentRequestTotal = $requestItem->quantity * $requestItem->amount_per_item;
                                    $set('request_total_price', $formatMoney($currentRequestTotal));
                                } elseif ($state === 'new') {
                                    $set('coa_id', null);
                                    $set('program_activity_id', null);
                                    $set('description', null);
                                    $set('is_realized', true);
                                    $set('quantity', 0);
                                    $set('act_quantity', 0);
                                    $set('unit_quantity', null);
                                    $set('amount_per_item', '0,00');
                                    $set('act_amount_per_item', '0,00');
                                    $set('request_total_price', '0,00');
                                } else {
                                    $set('coa_id', null);
                                    $set('program_activity_id', null);
                                    $set('description', null);
                                    $set('quantity', null);
                                    $set('unit_quantity', null);
                                    $set('amount_per_item', null);
                                    $set('request_total_price', null);
                                }

                                // Recalculate financial summary
                                // $rootData = $get('../../../../') ?? [];
                                // $receipts = $rootData['settlementReceipts'] ?? [];

                                // $approvedAmount = 0;
                                // $cancelledAmount = 0;
                                // $spentAmount = 0;

                                // foreach ($receipts as $receipt) {
                                //     foreach ($receipt['requestItems'] ?? [] as $item) {
                                //         // Check if this is current item
                                //         $isCurrentItem = ($item['id'] ?? null) === $state;

                                //         $requestTotal = $isCurrentItem ? $currentRequestTotal : $parseMoney($item['request_total_price'] ?? '0');
                                //         $approvedAmount += $requestTotal;

                                //         $isRealized = $item['is_realized'] ?? true;
                                //         if (! $isRealized) {
                                //             $cancelledAmount += $requestTotal;
                                //         } else {
                                //             $spentAmount += $parseMoney($item['actual_total_price'] ?? '0');
                                //         }
                                //     }

                                //     // // new_request_items only affects spent_amount
                                //     // foreach ($receipt['new_request_items'] ?? [] as $item) {
                                //     //     $totalPrice = $parseMoney($item['total_price'] ?? '0');
                                //     //     $spentAmount += $totalPrice;
                                //     // }
                                // }

                                // $variance = $approvedAmount - $spentAmount;

                                // $set('../../../../approved_request_amount', $formatMoney($approvedAmount));
                                // $set('../../../../cancelled_amount', $formatMoney($cancelledAmount));
                                // $set('../../../../spent_amount', $formatMoney($spentAmount));
                                // $set('../../../../variance', $formatMoney($variance));
                            }),
                        Checkbox::make('is_realized')
                            ->label('Terealisasi?')
                            ->default(true)
                            ->dehydrated(false)
                            ->disabled(fn (Get $get) => $get('id') === 'new')
                            ->formatStateUsing(fn ($operation, Get $get) => $operation === 'create' ? true : $get('act_quantity') > 0)
                            ->inline(false)
                            ->extraAttributes(
                                [
                                    'class' => 'mx-auto',
                                ]
                            )
                            ->live()
                            ->afterStateUpdatedJs(
                                <<<'JS'
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
                                                $set('act_quantity', 0);
                                                $set('act_amount_per_item', '0');
                                                $set('actual_total_price', '0');
                                                const reqTotal = parseMoney($get('request_total_price'));
                                                $set('variance', formatMoney(0 - reqTotal));
                                            } else {
                                                const reqTotal = parseMoney($get('request_total_price'));
                                                const actTotal = parseMoney($get('actual_total_price')) ?? 0;
                                                $set('variance', formatMoney(actTotal - reqTotal));
                                            }
                                            
                                            // Store current item's known values for calculation
                                            // const currentRequestTotal = parseMoney($get('request_total_price'));
                                            // const currentActualTotal = $state ? parseMoney($get('actual_total_price')) : 0;
                                            // const currentIsRealized = $state;
                                            // const currentItemId = $get('request_item_id');
                                            
                                            // // Recalculate financial summary
                                            // const receipts = $get('../../../../settlementReceipts') ?? {};
                                            
                                            // let approvedAmount = 0;
                                            // let cancelledAmount = 0;
                                            // let spentAmount = 0;
                                            
                                            // Object.values(receipts).forEach(receipt => {
                                            //     const requestItems = receipt?.request_items ?? {};
                                            //     Object.values(requestItems).forEach(item => {
                                            //         const itemRequestItemId = item?.request_item_id;
                                            //         const requestTotal = parseMoney(item?.request_total_price);
                                                    
                                            //         // Check if this is the current item being edited
                                            //         const isCurrentItem = itemRequestItemId && itemRequestItemId === currentItemId;
                                                    
                                            //         approvedAmount += requestTotal;
                                                    
                                            //         // Use known values for current item, otherwise use stored values
                                            //         const isRealized = isCurrentItem 
                                            //             ? currentIsRealized 
                                            //             : (item?.is_realized !== false && item?.is_realized !== 0 && item?.is_realized !== '0' && item?.is_realized !== null);
                                            //         const actualTotal = isCurrentItem ? currentActualTotal : parseMoney(item?.actual_total_price);
                                                    
                                            //         if (!isRealized) {
                                            //             cancelledAmount += requestTotal;
                                            //         } else {
                                            //             spentAmount += actualTotal;
                                            //         }
                                            //     });
                                                
                                            //     // new_request_items only affects spent_amount
                                            //     const newRequestItems = receipt?.new_request_items ?? {};
                                            //     Object.values(newRequestItems).forEach(item => {
                                            //         const totalPrice = parseMoney(item?.total_price);
                                            //         spentAmount += totalPrice;
                                            //     });
                                            // });
                                            
                                            // const variance =  approvedAmount - spentAmount;
                                            
                                            // $set('../../../../approved_request_amount', formatMoney(approvedAmount));
                                            // $set('../../../../cancelled_amount', formatMoney(cancelledAmount));
                                            // $set('../../../../spent_amount', formatMoney(spentAmount));
                                            // $set('../../../../variance', formatMoney(variance));
                                        JS
                            ),
                        Select::make('coa_id')
                            ->label('COA')
                            ->disabled(fn (Get $get) => $get('id') !== 'new')
                            ->dehydrated(fn (Get $get) => $get('id') === 'new')
                            ->options(Coa::query()->pluck('name', 'id'))
                            ->native(true)
                            ->live(),
                        // ->afterStateUpdatedJs(
                        //     <<<'JS'
                        //         $set('program_activity_id', null);
                        //     JS
                        // ),
                        Select::make('program_activity_id')
                            ->label('Aktivitas')
                            ->options(fn (Get $get) => $get('coa_id') ? ProgramActivity::query()->whereCoaId($get('coa_id'))->pluck('name', 'id') : ProgramActivity::query()->pluck('name', 'id'))
                            ->live()
                            ->native(true)
                            ->preload()
                            ->disabled(fn (Get $get) => $get('coa_id') === null || $get('id') !== 'new')
                            ->dehydrated(fn (Get $get) => $get('id') === 'new'),
                        TextInput::make('description')
                            ->required()
                            ->disabled(fn (Get $get) => $get('id') !== 'new')
                            ->dehydrated(fn (Get $get) => $get('id') === 'new')
                            ->validationMessages([
                                'required' => 'Deskripsi item wajib diisi',
                            ])
                            ->datalist(fn (Get $get) => ProgramActivityItem::query()->whereProgramActivityId($get('program_activity_id'))->pluck('description')->toArray())
                            ->live(debounce: 500),
                        TextInput::make('quantity')
                            ->readOnly()
                            ->dehydrated(fn (Get $get) => $get('id') === 'new')
                            ->numeric(),
                        // ->afterStateUpdatedJs(
                        //     <<<'JS'
                        //         const basePrice = ($get('amount_per_item') ?? '0').toString().replace(/\./g, '').replace(',', '.');
                        //         const basePriceNum = parseFloat(basePrice) || 0;
                        //         const qtyNum = parseFloat($state) || 0;
                        //         const total = qtyNum * basePriceNum;

                        //         if (total === 0) {
                        //             $set('request_total_price', '');
                        //         } else {
                        //             const formatted = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                        //             $set('request_total_price', formatted);
                        //         }
                        //     JS
                        // ),
                        TextInput::make('act_quantity')
                            ->label('Harga/item (Aktual)')
                            ->requiredWith('id,act_amount_per_item')
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
                                    const basePriceNum = parseMoney($get('act_amount_per_item'));
                                    const qtyNum = parseFloat($state) || 0;
                                    const total = qtyNum * basePriceNum;

                                    $set('actual_total_price', formatMoney(total));
                                    
                                    // Calculate item variance
                                    // const requestTotal = parseMoney($get('request_total_price'));
                                    // $set('variance', formatMoney(requestTotal - total));
                                    
                                    // // Store current item's known values
                                    // const currentActualTotal = total;
                                    // const currentItemId = $get('request_item_id');
                                    
                                    // // Recalculate financial summary
                                    // const receipts = $get('../../../../settlementReceipts') ?? {};
                                    
                                    // let approvedAmount = 0;
                                    // let cancelledAmount = 0;
                                    // let spentAmount = 0;
                                    
                                    // Object.values(receipts).forEach(receipt => {
                                    //     const requestItems = receipt?.request_items ?? {};
                                    //     Object.values(requestItems).forEach(item => {
                                    //         const itemRequestItemId = item?.request_item_id;
                                    //         const reqTotal = parseMoney(item?.request_total_price);
                                            
                                    //         const isCurrentItem = itemRequestItemId && itemRequestItemId === currentItemId;
                                            
                                    //         approvedAmount += reqTotal;
                                            
                                    //         const isRealized = item?.is_realized !== false && item?.is_realized !== 0 && item?.is_realized !== '0' && item?.is_realized !== null;
                                    //         const actualTotal = isCurrentItem ? currentActualTotal : parseMoney(item?.actual_total_price);
                                            
                                    //         if (!isRealized) {
                                    //             cancelledAmount += reqTotal;
                                    //         } else {
                                    //             spentAmount += actualTotal;
                                    //         }
                                    //     });
                                        
                                    //     const newRequestItems = receipt?.new_request_items ?? {};
                                    //     Object.values(newRequestItems).forEach(item => {
                                    //         const totalPrice = parseMoney(item?.total_price);
                                    //         spentAmount += totalPrice;
                                    //     });
                                    // });
                                    
                                    // const summaryVariance =  approvedAmount - spentAmount;
                                    
                                    // $set('../../../../approved_request_amount', formatMoney(approvedAmount));
                                    // $set('../../../../cancelled_amount', formatMoney(cancelledAmount));
                                    // $set('../../../../spent_amount', formatMoney(spentAmount));
                                    // $set('../../../../variance', formatMoney(summaryVariance));
                                JS),
                        TextInput::make('unit_quantity')
                            ->readOnly(fn (Get $get) => $get('id') !== 'new')
                            ->dehydrated(fn (Get $get) => $get('id') === 'new'),
                        TextInput::make('amount_per_item')
                            ->prefix('Rp')
                            ->readOnly()
                            ->dehydrated(fn (Get $get) => $get('id') === 'new')
                            ->dehydrateStateUsing(fn ($rawState) => (float) str_replace(['.', ','], ['', '.'], $rawState))
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)')),
                        // ->afterStateUpdatedJs(
                        //     <<<'JS'
                        //         const cleanPrice = ($state ?? '0').toString().replace(/\./g, '').replace(',', '.');
                        //         const priceNum = parseFloat(cleanPrice) || 0;
                        //         const qtyNum = parseFloat($get('quantity')) || 0;
                        //         const total = qtyNum * priceNum;

                        //         if (total === 0) {
                        //             $set('request_total_price', '');
                        //         } else {
                        //             const formatted = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                        //             $set('request_total_price', formatted);
                        //         }
                        //     JS
                        // ),
                        TextInput::make('act_amount_per_item')
                            ->requiredWith('id,act_quantity')
                            ->readOnly(fn (Get $get) => ! $get('is_realized'))
                            ->validationMessages([
                                'required_with' => 'Harga per item wajib diisi',
                            ])
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                            ->dehydrateStateUsing(fn ($rawState) => (float) str_replace(['.', ','], ['', '.'], $rawState))
                            ->stripCharacters(['.', ','])
                            ->afterStateUpdatedJs(
                                <<<'JS'
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
                                            const qtyNum = parseFloat($get('act_quantity')) || 0;
                                            const total = qtyNum * priceNum;

                                            $set('actual_total_price', formatMoney(total));
                                            
                                            // Calculate item variance
                                            const requestTotal = parseMoney($get('request_total_price') ?? 0);
                                            $set('variance', formatMoney(requestTotal - total));
                                            
                                            // Store current item's known values
                                            // const currentActualTotal = total;
                                            // const currentItemId = $get('request_item_id');
                                            
                                            // // Recalculate financial summary
                                            // const receipts = $get('../../../../settlementReceipts') ?? {};
                                            
                                            // let approvedAmount = 0;
                                            // let cancelledAmount = 0;
                                            // let spentAmount = 0;
                                            
                                            // Object.values(receipts).forEach(receipt => {
                                            //     const requestItems = receipt?.request_items ?? {};
                                            //     Object.values(requestItems).forEach(item => {
                                            //         const itemRequestItemId = item?.request_item_id;
                                            //         const reqTotal = parseMoney(item?.request_total_price);
                                                    
                                            //         const isCurrentItem = itemRequestItemId && itemRequestItemId === currentItemId;
                                                    
                                            //         approvedAmount += reqTotal;
                                                    
                                            //         const isRealized = item?.is_realized !== false && item?.is_realized !== 0 && item?.is_realized !== '0' && item?.is_realized !== null;
                                            //         const actualTotal = isCurrentItem ? currentActualTotal : parseMoney(item?.actual_total_price);
                                                    
                                            //         if (!isRealized) {
                                            //             cancelledAmount += reqTotal;
                                            //         } else {
                                            //             spentAmount += actualTotal;
                                            //         }
                                            //     });
                                                
                                            //     const newRequestItems = receipt?.new_request_items ?? {};
                                            //     Object.values(newRequestItems).forEach(item => {
                                            //         const totalPrice = parseMoney(item?.total_price);
                                            //         spentAmount += totalPrice;
                                            //     });
                                            // });
                                            
                                            // const summaryVariance =  approvedAmount - spentAmount;
                                            
                                            // $set('../../../../approved_request_amount', formatMoney(approvedAmount));
                                            // $set('../../../../cancelled_amount', formatMoney(cancelledAmount));
                                            // $set('../../../../spent_amount', formatMoney(spentAmount));
                                            // $set('../../../../variance', formatMoney(summaryVariance));
                                        JS
                            ),
                        TextInput::make('request_total_price')
                            ->label('Total Request')
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                            ->readOnly()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (Get $get) => number_format($get('amount_per_item') * $get('quantity'), 2, ',', '.')),
                        TextInput::make('actual_total_price')
                            ->label('Total Aktual')
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
                            ->label('Selisih')
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                            ->readOnly()
                            ->dehydrated(false),
                        SpatieMediaLibraryFileUpload::make('item_image')
                            // ->collection('request_item_image')
                            ->dehydrated(true)
                            ->storeFiles(false)
                            ->label('Foto Item/Produk')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'application/pdf'])
                            ->multiple()
                            ->appendFiles()
                            ->maxSize(4096)
                            ->columnSpanFull()
                            ->previewable(false)
                            ->openable(true)
                            ->required()
                            ->validationMessages([
                                'required' => 'Foto Item/Produk diperlukan untuk Settlement',
                            ]),
                    ]),

            ]);
    }
}

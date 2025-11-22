<?php

namespace App\Filament\Resources\Settlements\Schemas;

use App\Enums\RequestItemStatus;
use App\Models\Coa;
use App\Models\ProgramActivity;
use App\Models\ProgramActivityItem;
use App\Models\RequestItem;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\RawJs;

class SettlementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Repeater::make('settlements')
                    ->label('Daftar Settlement')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('attachment')
                            ->label('Bukti Realisasi Semua Item Dibawah')
                            ->multiple(false),
                        Repeater::make('request_items')
                            ->label('Daftar Item yang diajukan')
                            ->compact()
                            ->addActionAlignment(Alignment::Start)
                            ->extraAttributes([
                                'class' => 'overflow-x-auto p-0.5 pb-1.5 *:first:table-fixed *:first:[&_thead]:[&_th]:first:w-[46px] *:first:table-fixed *:first:[&_thead]:[&_th]:last:w-[46px]'
                            ])
                            ->table([
                                TableColumn::make('Request Item')->width('300px'),
                                TableColumn::make('Terealisasi ?')->width('150px'),
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
                                    ->native(false)
                                    ->options(function () {
                                        return RequestItem::query()
                                            ->where('request_items.status', '=', RequestItemStatus::WaitingSettlement->value)
                                            ->join('daily_payment_requests', 'request_items.daily_payment_request_id', '=', 'daily_payment_requests.id')
                                            ->get(['request_items.id', 'request_items.description', 'daily_payment_requests.request_number as daily_payment_request_number'])
                                            ->groupBy('daily_payment_request_number')
                                            ->map(fn($items) => $items->pluck('description', 'id'))
                                            ->toArray();
                                    })
                                    ->disableOptionWhen(function ($value, $state, Get $get) {
                                        // Prevent duplicate selection in same settlement
                                        $allProofs = $get('../../../../settlements') ?? [];
                                        $selectedIds = collect($allProofs)
                                            ->pluck('request_items')
                                            ->flatten(1)
                                            ->pluck('request_item_id')
                                            ->filter()
                                            ->toArray();

                                        return in_array($value, $selectedIds) && $value != $state;
                                    })
                                    ->live()
                                    ->partiallyRenderComponentsAfterStateUpdated(['request_quantity', 'request_unit_quantity', 'request_amount_per_item'])
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state) {
                                            $requestItem = RequestItem::find($state, ['quantity', 'unit_quantity', 'amount_per_item']);

                                            $set('request_quantity', (int) $requestItem->quantity);
                                            $set('request_unit_quantity', $requestItem->unit_quantity);
                                            $set('request_amount_per_item', number_format($requestItem->amount_per_item, 2, ',', '.'));
                                        } else {
                                            $set('request_quantity', null);
                                            $set('request_unit_quantity', null);
                                            $set('request_amount_per_item', null);
                                        }
                                    }),
                                Toggle::make('is_realized')
                                    ->default(true)
                                    ->inline(false)
                                    ->extraAlpineAttributes([
                                        'class' => 'mx-auto'
                                    ]),
                                TextInput::make('request_quantity')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->numeric(),
                                TextInput::make('actual_quantity')
                                    ->numeric()
                                    ->afterStateUpdatedJs(<<<'JS'
                                        const basePrice = ($get('actusl_amount_per_item') ?? '0').toString().replace(/\./g, '').replace(',', '.');
                                        const basePriceNum = parseFloat(basePrice) || 0;
                                        const qtyNum = parseFloat($state) || 0;
                                        const total = qtyNum * basePriceNum;

                                        if (total === 0) {
                                            $set('actual_total_price', '');
                                        } else {
                                            const formatted = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                            $set('actual_total_price', formatted);
                                        }
                                        JS),
                                TextInput::make('request_unit_quantity')
                                    ->readOnly()
                                    ->dehydrated(false),
                                TextInput::make('request_amount_per_item')
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
                                // ->afterStateUpdatedJs(<<<'JS'
                                //     const items = $get('../../requestItems') ?? {};
                                //     let allTotal = 0;

                                //     Object.values(items).forEach(item => {
                                //         if (item.total_price) {
                                //             const cleanTotal = item.total_price.toString().replace(/\./g, '').replace(',', '.');
                                //             const num = parseFloat(cleanTotal);
                                //             if (!isNaN(num)) {
                                //                 allTotal += num;
                                //             }
                                //         }
                                //     });

                                //     if (allTotal === 0) {
                                //         $set('../../total_request_amount', '');
                                //     } else {
                                //         const formatted = allTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                //         $set('../../total_request_amount', formatted);
                                //     }
                                // JS),
                                TextInput::make('actual_amount_per_item')
                                    // ->requiredWith('coa_id,item,qty,unit_qty')
                                    ->validationMessages([
                                        'required_with' => 'Harga per item wajib diisi',
                                    ])
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                    ->dehydrateStateUsing(fn($rawState) => (float) str_replace(['.', ','], ['', '.'], $rawState))
                                    ->stripCharacters(['.', ','])
                                    ->afterStateUpdatedJs(<<<'JS'
                                        const cleanPrice = ($state ?? '0').toString().replace(/\./g, '').replace(',', '.');
                                        const priceNum = parseFloat(cleanPrice) || 0;
                                        const qtyNum = parseFloat($get('actual_quantity')) || 0;
                                        const total = qtyNum * priceNum;

                                        if (total === 0) {
                                            $set('actual_total_price', '');
                                        } else {
                                            const formatted = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                            $set('actual_total_price', formatted);
                                        }
                                        JS)
                                    ->minValue(1),
                                TextInput::make('request_total_price')
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                    ->readOnly()
                                    ->dehydrated(false),
                                // ->afterStateUpdatedJs(<<<'JS'
                                //     const items = $get('../../requestItems') ?? {};
                                //     let allTotal = 0;

                                //     Object.values(items).forEach(item => {
                                //         if (item.total_price) {
                                //             const cleanTotal = item.total_price.toString().replace(/\./g, '').replace(',', '.');
                                //             const num = parseFloat(cleanTotal);
                                //             if (!isNaN(num)) {
                                //                 allTotal += num;
                                //             }
                                //         }
                                //     });

                                //     if (allTotal === 0) {
                                //         $set('../../total_request_amount', '');
                                //     } else {
                                //         const formatted = allTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                //         $set('../../total_request_amount', formatted);
                                //     }
                                // JS),
                                TextInput::make('actual_total_price')
                                    ->prefix('Rp')
                                    // ->placeholder(fn(Get $get) => number_format(ProgramActivityItem::whereDescription($get('item'))->value('total_item_planned_budget'), 2, ',', '.') ?? null)
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->afterStateUpdatedJs(<<<'JS'
                                        const cleanActualTotalPrice = ($state ?? '0').toString().replace(/\./g, '').replace(',', '.');
                                        const cleanRequestTotalPrice = ($get('request_total_price') ?? '0').toString().replace(/\./g, '').replace(',', '.');

                                        $set('variance', (parseFloat(cleanActualTotalPrice) - parseFloat(cleanRequestTotalPrice)).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1'))
                                        // const items = $get('../../request_items') ?? {};
                                        // let allTotal = 0;

                                        // Object.values(items).forEach(item => {
                                        //     if (itemactual_total_price) {
                                        //         const cleanTotal = itemactual_total_price.toString().replace(/\./g, '').replace(',', '.');
                                        //         const num = parseFloat(cleanTotal);
                                        //         if (!isNaN(num)) {
                                        //             allTotal += num;
                                        //         }
                                        //     }
                                        // });

                                        // if (allTotal === 0) {
                                        //     $set('../../total_request_amount', '');
                                        // } else {
                                        //     const formatted = allTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                        //     $set('../../total_request_amount', formatted);
                                        // }
                                    JS),
                                TextInput::make('variance')
                                    ->prefix('Rp')
                                    // ->placeholder(fn(Get $get) => number_format(ProgramActivityItem::whereDescription($get('item'))->value('total_item_planned_budget'), 2, ',', '.') ?? null)
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                    ->readOnly()
                                    ->dehydrated(false)
                            ]),
                        Repeater::make('new_request_items')
                            ->label('Daftar Item Baru')
                            ->compact()
                            ->addActionAlignment(Alignment::Start)
                            ->extraAttributes([
                                'class' => 'overflow-x-auto p-0.5 pb-1.5 *:first:table-fixed *:first:[&_thead]:[&_th]:first:w-[46px] *:first:table-fixed *:first:[&_thead]:[&_th]:last:w-[46px]'
                            ])
                            ->table([
                                TableColumn::make('COA')->width('280px'),
                                TableColumn::make('Aktivitas')->width('280px'),
                                TableColumn::make('Item')->width('300px'),
                                TableColumn::make('Qty')->width('100px'),
                                TableColumn::make('Unit Qty')->width('150px'),
                                TableColumn::make('Base Price')->width('250px'),
                                TableColumn::make('Total Price')->width('250px'),
                                // TableColumn::make('Tipe Request')->width('200px'),
                                // TableColumn::make('Lampiran')->width('350px'),
                                // TableColumn::make('Kirim Ke Rekening Sendiri ?')->width('225px')
                                //     ->alignment(Alignment::Center),
                                // TableColumn::make('Nama Bank')->width('200px'),
                                // TableColumn::make('Nomor Rekening')->width('200px'),
                                // TableColumn::make('Nama Pemilik Rekening')->width('200px'),
                            ])
                            ->schema([
                                Select::make('coa_id')
                                    ->options(Coa::query()->pluck('name', 'id'))
                                    ->native(false)
                                    ->live()
                                    ->requiredWith('item,qty,unit_qty,base_price')
                                    ->validationMessages([
                                        'required_with' => 'COA wajib diisi',
                                    ])
                                    ->afterStateUpdatedJs(
                                        <<<'JS'
                                            $set('program_activity_id', null);
                                        JS
                                    ),
                                Select::make('program_activity_id')
                                    ->options(fn(Get $get) => ProgramActivity::query()->whereCoaId($get('coa_id'))->pluck('name', 'id'))
                                    ->live()
                                    ->native(false)
                                    ->preload()
                                    ->searchable()
                                    ->disabled(fn(Get $get) => $get('coa_id') === null),
                                TextInput::make('item')
                                    ->requiredWith('coa_id,qty,unit_qty,base_price')
                                    ->validationMessages([
                                        'required_with' => 'Deskripsi item wajib diisi',
                                    ])
                                    ->datalist(fn(Get $get) => ProgramActivityItem::query()->whereProgramActivityId($get('program_activity_id'))->pluck('description')->toArray())
                                    ->live(debounce: 500),
                                TextInput::make('qty')
                                    ->requiredWith('coa_id,item,unit_qty,base_price')
                                    ->validationMessages([
                                        'required_with' => 'Jumlah item wajib diisi',
                                    ])
                                    ->numeric()
                                    ->minValue(1)
                                    // ->live()
                                    // ->partiallyRenderComponentsAfterStateUpdated(['coa_id'])
                                    ->placeholder(function (Get $get) {
                                        $activityItem = $get('program_activity_id') ? ProgramActivityItem::whereProgramActivityId($get('program_activity_id'))->whereDescription($get('item'))->get(['volume'])->first() : null;

                                        return $activityItem ? $activityItem->volume : null;
                                    })
                                    ->afterStateUpdatedJs(<<<'JS'
        const basePrice = ($get('base_price') ?? '0').toString().replace(/\./g, '').replace(',', '.');
        const basePriceNum = parseFloat(basePrice) || 0;
        const qtyNum = parseFloat($state) || 0;
        const total = qtyNum * basePriceNum;

        if (total === 0) {
            $set('total_price', '');
        } else {
            const formatted = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
            $set('total_price', formatted);
        }
        JS),
                                TextInput::make('unit_qty')
                                    ->requiredWith('coa_id,item,qty,base_price')
                                    ->validationMessages([
                                        'required_with' => 'Satuan item wajib diisi',
                                    ])
                                    ->trim()
                                    ->placeholder(function (Get $get) {
                                        $activityItem = $get('program_activity_id') ? ProgramActivityItem::whereProgramActivityId($get('program_activity_id'))->whereDescription($get('item'))?->get(['unit'])->first() : null;

                                        return $activityItem ? $activityItem->unit : null;
                                    }),
                                TextInput::make('base_price')
                                    ->requiredWith('coa_id,item,qty,unit_qty')
                                    ->validationMessages([
                                        'required_with' => 'Harga per item wajib diisi',
                                    ])
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                    ->dehydrateStateUsing(fn($rawState) => (float) str_replace(['.', ','], ['', '.'], $rawState))
                                    ->stripCharacters(['.', ','])
                                    // ->live()
                                    // ->partiallyRenderComponentsAfterStateUpdated(['coa_id'])
                                    ->placeholder(function (Get $get) {
                                        $activityItem = $get('program_activity_id') ? ProgramActivityItem::whereProgramActivityId($get('program_activity_id'))->whereDescription($get('item'))->get(['total_item_planned_budget', 'volume', 'frequency'])->first() : null;

                                        $plannedBudgetPerItem = $activityItem ? ((float)$activityItem->total_item_planned_budget / $activityItem->frequency / $activityItem->volume) : 0;

                                        return $plannedBudgetPerItem ? number_format($plannedBudgetPerItem, 2, ',', '.') : null;
                                    })
                                    ->afterStateUpdatedJs(<<<'JS'
        const cleanPrice = ($state ?? '0').toString().replace(/\./g, '').replace(',', '.');
        const priceNum = parseFloat(cleanPrice) || 0;
        const qtyNum = parseFloat($get('qty')) || 0;
        const total = qtyNum * priceNum;

        if (total === 0) {
            $set('total_price', '');
        } else {
            const formatted = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
            $set('total_price', formatted);
        }
        JS)
                                    ->minValue(1),
                                TextInput::make('total_price')
                                    ->prefix('Rp')
                                    // ->placeholder(fn(Get $get) => number_format(ProgramActivityItem::whereDescription($get('item'))->value('total_item_planned_budget'), 2, ',', '.') ?? null)
                                    ->placeholder(function (Get $get) {
                                        $activityItem = $get('program_activity_id') ? ProgramActivityItem::whereProgramActivityId($get('program_activity_id'))->whereDescription($get('item'))->get(['total_item_planned_budget', 'volume', 'frequency'])->first() : null;


                                        $plannedBudget = $activityItem ? ((float)$activityItem->total_item_planned_budget / $activityItem->frequency / $activityItem->volume) * $activityItem->volume : 0;

                                        return $plannedBudget ? number_format($plannedBudget, 2, ',', '.') : null;
                                    })
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                    ->readOnly()
                                    ->dehydrated(false)
                                //             ->afterStateUpdatedJs(<<<'JS'
                                //     const items = $get('../../requestItems') ?? {};
                                //     let allTotal = 0;

                                //     Object.values(items).forEach(item => {
                                //         if (item.total_price) {
                                //             const cleanTotal = item.total_price.toString().replace(/\./g, '').replace(',', '.');
                                //             const num = parseFloat(cleanTotal);
                                //             if (!isNaN(num)) {
                                //                 allTotal += num;
                                //             }
                                //         }
                                //     });

                                //     if (allTotal === 0) {
                                //         $set('../../total_request_amount', '');
                                //     } else {
                                //         const formatted = allTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                //         $set('../../total_request_amount', formatted);
                                //     }
                                // JS),
                            ])
                    ])
                    ->columnSpanFull()
            ]);
    }
}

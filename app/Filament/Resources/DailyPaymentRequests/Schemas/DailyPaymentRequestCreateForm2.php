<?php

namespace App\Filament\Resources\DailyPaymentRequests\Schemas;

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use App\Enums\RequestPaymentType;
use App\Models\Coa;
use App\Models\ProgramActivity;
use App\Models\ProgramActivityItem;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;

class DailyPaymentRequestCreateForm2
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(fn() => self::formFields());
    }

    public static function formFields(): array
    {
        return [
            Group::make([
                TextInput::make('total_request_amount')
                    // ->numeric()
                    ->readOnly()
                    ->dehydrated(false)
                    ->prefix('Rp')
                    ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
            ])
                ->columns(3)
                ->columnSpanFull(),
            Repeater::make('requestItems')
                ->hiddenLabel()
                ->defaultItems(8)
                ->compact()
                ->live(onBlur: 500)
                ->table(
                    [
                        TableColumn::make('COA')->width('280px'),
                        TableColumn::make('Aktivitas')->width('280px'),
                        TableColumn::make('Item')->width('300px'),
                        TableColumn::make('Qty')->width('100px'),
                        TableColumn::make('Unit Qty')->width('150px'),
                        TableColumn::make('Base Price')->width('250px'),
                        TableColumn::make('Total Price')->width('250px'),
                        TableColumn::make('Tipe Request')->width('200px'),
                        TableColumn::make('Lampiran')->width('350px'),
                        TableColumn::make('Kirim Ke Rekening Sendiri ?')->width('225px')
                            ->alignment(Alignment::Center),
                        TableColumn::make('Nama Bank')->width('200px'),
                        TableColumn::make('Nomor Rekening')->width('200px'),
                        TableColumn::make('Nama Pemilik Rekening')->width('200px'),

                    ]
                )
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
                        ->afterStateUpdatedJs(<<<'JS'
                            const items = $get('../../requestItems') ?? {};
                            let allTotal = 0;

                            Object.values(items).forEach(item => {
                                if (item.total_price) {
                                    const cleanTotal = item.total_price.toString().replace(/\./g, '').replace(',', '.');
                                    const num = parseFloat(cleanTotal);
                                    if (!isNaN(num)) {
                                        allTotal += num;
                                    }
                                }
                            });

                            if (allTotal === 0) {
                                $set('../../total_request_amount', '');
                            } else {
                                const formatted = allTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                $set('../../total_request_amount', formatted);
                            }
                        JS),
                    Select::make('payment_type')
                        ->label('Tipe Pengajuan')
                        ->options(RequestPaymentType::class)
                        ->default(RequestPaymentType::Advance)
                        ->selectablePlaceholder(false)
                        ->live()
                        ->partiallyRenderComponentsAfterStateUpdated(['attachments'])
                        // ->afterStateUpdatedJs(<<<'JS'
                        //     if ($state === 'reimburse') {
                        //         $set('self_account', true);
                        //     }
                        // JS)
                        // ->afterStateUpdated(fn(Set $set, $state) => $state === RequestPaymentType::Reimburse && $set('self_account', true))
                        ->native(false),
                    SpatieMediaLibraryFileUpload::make('attachments')
                        ->label('Lampiran')
                        ->image()
                        ->acceptedFileTypes(['image/png', 'image/jpeg'])
                        ->multiple()
                        ->appendFiles()
                        ->maxSize(4096)
                        ->storeFiles(false)
                        ->columnSpanFull()
                        ->previewable(false)
                        ->dehydrated(true)
                        ->required(fn(Get $get) => $get('payment_type') === RequestPaymentType::Reimburse)
                        ->validationMessages([
                            'required' => 'Lampiran diperlukan untuk Reimbursement',
                        ]),
                    Toggle::make('self_account')
                        ->inline(false)
                        ->default(true)
                        ->extraAlpineAttributes([
                            'class' => 'mx-auto'
                        ])
                        ->live()
                        // ->disabled(fn(Get $get) => $get('payment_type') === RequestPaymentType::Reimburse)
                        ->afterStateUpdated(function (Set $set, $state) {
                            if ($state) {
                                $set('bank_name', Auth::user()->employee?->bank_name);
                                $set('bank_account', Auth::user()->employee?->bank_account_number);
                                $set('account_owner', Auth::user()->employee?->bank_cust_name);
                            } else {
                                $set('bank_name', null);
                                $set('bank_account', null);
                                $set('account_owner', null);
                            }
                        }),
                    TextInput::make('bank_name')
                        ->default(Auth::user()->employee?->bank_name)
                        ->required()
                        ->validationMessages([
                            'required' => 'Nama Bank wajib diisi',
                        ])
                        ->readOnly(fn(Get $get) => $get('self_account')),
                    TextInput::make('bank_account')
                        ->numeric()
                        ->required()
                        ->validationMessages([
                            'required' => 'Nomor Rekening wajib diisi',
                        ])
                        ->default(Auth::user()->employee?->bank_account_number)
                        ->dehydrateStateUsing(fn($rawState) => (string) $rawState)
                        ->readOnly(fn(Get $get) => $get('self_account')),
                    TextInput::make('account_owner')
                        ->required()
                        ->validationMessages([
                            'required' => 'Nama Pemilik Rekening wajib diisi',
                        ])
                        ->default(Auth::user()->employee?->bank_cust_name)
                        ->readOnly(fn(Get $get) => $get('self_account')),
                ])
                ->columnSpanFull()
                ->addActionAlignment(Alignment::Start)
                ->extraAttributes([
                    'class' => 'overflow-x-auto p-0.5 pb-1.5 *:first:table-fixed *:first:[&_thead]:[&_th]:first:w-[46px] *:first:table-fixed *:first:[&_thead]:[&_th]:last:w-[46px]'
                ])
                ->mutateDehydratedStateUsing(function ($state) {
                    if (is_array($state)) {
                        // Filter out completely empty rows
                        return array_values(array_filter($state, function ($item) {
                            return !empty(array_filter($item ?? []));
                        }));
                    }
                    dd($state);
                    return $state;
                })
        ];
    }

    protected static function calculateItemTotalPrice(Set $set, Get $get): void
    {
        // dd($get('amount_per_item'));
        $quantity = (float) $get('qty') ?: 1;
        $amountPerItem = (float) $get('base_price') ?: 0;

        $totalAmount = $quantity * $amountPerItem;
        $set('total_price', $totalAmount);
    }

    protected static function calculateTotalAmount(Set $set, Get $get)
    {
        $baskets = $get('../../requestCoas') ?? [];
        $total = 0;

        foreach ($baskets as $basket) {
            $items = $basket['requestItems'] ?? [];

            foreach ($items as $item) {
                $quantity = (float) ($item['quantity'] ?? 0);
                $price = (float) ($item['amount_per_item'] ?? 0);
                $total += $quantity * $price;
            }
        }

        $set('../../total_amount', $total);
    }
}

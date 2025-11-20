<?php

namespace App\Filament\Resources\DailyPaymentRequests\Schemas;

use App\Enums\TaxMethod;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use App\Enums\COAType;
use App\Enums\RequestPaymentType;
use App\Models\Coa;
use App\Models\ProgramActivity;
use App\Models\ProgramActivityItem;
use App\Models\RequestItemType;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DailyPaymentRequestCreateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('Request Metadata')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('request_date')
                            ->label('Tanggal Pengajuan')
                            ->date('l, d M Y')
                            ->state(now()),
                        TextEntry::make('requester_name')
                            ->label('Nama Requester')
                            ->state(Auth::user()->name)
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ]),
                Section::make('Daftar Rincian Belanja')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('requestCoas')
                            ->hiddenLabel()
                            ->schema([
                                Select::make('coa_id')
                                    ->label('COA (Chart of Account)')
                                    ->options(Coa::query()->where('is_active', true)->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        // dd($state);
                                        if ($state) {
                                            $coa = Coa::find($state);
                                            // dd($coa);
                                            if ($coa) {
                                                // Snapshot COA data
                                                $set('coa_code', $coa->code);
                                                $set('coa_name', $coa->name);
                                                $set('coa_type', $coa->type);

                                                // Snapshot Program data if applicable
                                                if ($coa->type === COAType::Program && $coa->program) {
                                                    $program = $coa->program;
                                                    $set('program_id', $program->id);
                                                    $set('program_name', $program->name);
                                                    $set('program_code', $program->code);
                                                    $set('program_category_name', $program->programCategory->name);
                                                    $set('contract_year', $coa->contract_year);
                                                }
                                            }
                                        }
                                    })
                                    ->columnSpanFull(),
                                Repeater::make('requestItems')
                                    ->label('Daftar Items')
                                    ->disabled(fn(Get $get) => null === $get('coa_id'))
                                    ->schema([
                                        Grid::make()
                                            ->schema([
                                                Select::make('program_activity_id')
                                                    ->label('Pilih Aktivitas')
                                                    ->options(fn(Get $get) => ProgramActivity::query()->active()->whereCoaId($get('../../coa_id'))->pluck('name', 'id'))
                                                    ->required()
                                                    ->disabled(fn(Get $get) => $get('../../coa_id') === null)
                                                    ->searchable()
                                                    ->live()
                                                    ->partiallyRenderComponentsAfterStateUpdated(['program_activity_item_id'])
                                                    ->dehydrated(false),
                                                Select::make('program_activity_item_id')
                                                    ->label('Pilih Item Aktivitas')
                                                    ->options(fn(Get $get) => ProgramActivityItem::query()->whereProgramActivityId($get('program_activity_id'))->pluck('description', 'id'))
                                                    ->required()
                                                    ->disabled(fn(Get $get) => $get('program_activity_id') === null)
                                                    ->searchable()
                                                    ->createOptionForm([
                                                        TextInput::make('description')
                                                    ]),
                                            ])
                                            ->columnSpanFull(),
                                        Textarea::make('description')
                                            ->label('Deskripsi Item')
                                            ->required()
                                            ->rows(2)
                                            ->live(onBlur: true)
                                            ->columnSpanFull(),
                                        Select::make('payment_type')
                                            ->label('Tipe Pengajuan')
                                            ->options(RequestPaymentType::class)
                                            ->default(RequestPaymentType::Reimburse)
                                            ->selectablePlaceholder(false)
                                            ->required()
                                            ->live()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                            ]),

                                        TextInput::make('advance_percentage')
                                            ->label('Advance %')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(100)
                                            ->suffix('%')
                                            ->required(fn(Get $get) => $get('payment_type') === RequestPaymentType::Advance)
                                            ->hidden(fn(Get $get) => $get('payment_type') !== RequestPaymentType::Advance)
                                            ->live()
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 2,
                                                'xl' => 1
                                            ]),

                                        Select::make('request_item_type_id')
                                            ->label('Tipe Item Request')
                                            ->options(RequestItemType::query()->pluck('name', 'id'))
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, $state) {
                                                if ($state) {
                                                    $itemType = RequestItemType::find($state);
                                                    if ($itemType) {
                                                        // Snapshot tax data
                                                        $set('tax_type', $itemType->tax->name);
                                                        $set('tax_rate', $itemType->tax->value);
                                                        $set('item_type_name', $itemType->name);
                                                    }
                                                }
                                            })
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 3,
                                            ]),

                                        // Select::make('tax_method')
                                        //     ->label('Tax Method')
                                        //     ->options(TaxMethod::class)
                                        //     ->required()
                                        //     ->live()
                                        //     ->columnSpan(
                                        //         [
                                        //             'default' => 1,
                                        //             'md' => 4,
                                        //             'xl' => 2
                                        //         ]
                                        //     ),

                                        TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->required()
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(0.01)
                                            ->step(0.01)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                self::calculateAmount($set, $get);
                                                self::calculateTotalAmount($set, $get);
                                            })
                                            ->columnSpan(1),

                                        TextInput::make('unit_quantity')
                                            ->label('Unit')
                                            ->placeholder('pcs, kg, liter, etc.')
                                            ->maxLength(50)
                                            ->columnSpan(1),

                                        TextInput::make('amount_per_item')
                                            ->label('Price per Unit')
                                            ->required()
                                            ->prefix('Rp')
                                            ->numeric()
                                            ->live(onBlur: true)
                                            ->belowContent(fn($state) => "Rp " . number_format($state, 2, ',', '.'))
                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                self::calculateAmount($set, $get);
                                                self::calculateTotalAmount($set, $get);
                                            })
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 4,
                                                'xl' => 2
                                            ]),

                                        TextInput::make('amount')
                                            ->label('Total Amount')
                                            ->required()
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->aboveContent("Dihitung otomatis oleh sistem")
                                            ->helperText(fn($state) => "Rp " . number_format($state, 2, ',', '.'))
                                            ->columnSpan([
                                                'default' => 1,
                                                'md' => 4,
                                                'xl' => 3
                                            ]),
                                        // ->live()
                                        // ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateTotalAmount($get, $set)),

                                        // TextEntry::make('tax_calculation')
                                        //     ->label('Tax Calculation')
                                        //     ->state(function (Get $get) {
                                        //         $amount = (float) $get('amount');
                                        //         $taxRate = (float) $get('tax_rate');
                                        //         $taxMethod = $get('tax_method');
                                        //         $taxType = $get('tax_type');

                                        //         if (!$amount || !$taxRate) {
                                        //             return 'Enter amount and select item type to see calculation';
                                        //         }

                                        //         if ($taxMethod === TaxMethod::Withholding) {
                                        //             $tax = $amount * $taxRate;
                                        //             $net = $amount - $tax;
                                        //         } else {
                                        //             $gross = $amount / (1 - $taxRate);
                                        //             $tax = $gross - $amount;
                                        //             $net = $gross;
                                        //         }

                                        //         return sprintf(
                                        //             "%s (%.2f%%)\nTax: Rp %s\nNet: Rp %s",
                                        //             $taxType ?? 'N/A',
                                        //             $taxRate * 100,
                                        //             number_format($tax, 0, ',', '.'),
                                        //             number_format($net, 0, ',', '.')
                                        //         );
                                        //     })
                                        //     ->columnSpan([
                                        //         'default' => 1,
                                        //         'md' => 4,
                                        //         'xl' => 3
                                        //     ]),
                                        SpatieMediaLibraryFileUpload::make('attachments')
                                            ->label('Lampiran')
                                            ->image()
                                            ->acceptedFileTypes(['image/png', 'image/jpeg'])
                                            ->multiple()
                                            ->appendFiles()
                                            ->maxSize(4096)
                                            ->storeFiles(false)
                                            ->columnSpanFull()
                                            ->dehydrated(true)
                                            ->required(fn(Get $get) => $get('payment_type') === RequestPaymentType::Reimburse),

                                        // Hidden snapshot fields
                                        Hidden::make('tax_type'),
                                        Hidden::make('tax_rate'),
                                        Hidden::make('item_type_name'),
                                    ])
                                    ->reorderable(false)
                                    ->collapsible()
                                    ->itemLabel(fn(array $state): ?string => $state['description'] ?? 'New Item')
                                    ->addActionLabel('Tambah Item')
                                    ->deleteAction(
                                        fn(Action $action) => $action
                                            ->requiresConfirmation()
                                    )
                                    ->minItems(1)
                                    ->defaultItems(1)
                                    ->columns([
                                        'default' => 1,
                                        'md' => 4,
                                        'xl' => 6
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::calculateTotalAmount($set, $get);
                                    }),
                                // Hidden snapshot fields
                                Hidden::make('coa_code'),
                                Hidden::make('coa_name'),
                                Hidden::make('coa_type'),
                                Hidden::make('program_id'),
                                Hidden::make('program_name'),
                                Hidden::make('program_code'),
                                Hidden::make('program_category_name'),
                                Hidden::make('contract_year'),
                            ])
                            ->columns(1)
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => $state['coa_name'] ?? 'New COA')
                            ->addActionLabel('Tambah COA')
                            ->deleteAction(
                                fn(Action $action) => $action
                                    ->requiresConfirmation()
                            )
                            ->minItems(1)
                            ->defaultItems(1)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::calculateTotalAmount($set, $get);
                            }),
                    ]),
                Section::make('Ringkasan Request')
                    ->schema([
                        TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(false)
                            ->belowContent(fn($state) => "Rp " . number_format($state, 2, ',', '.')),
                    ]),
                Section::make('Rekening Tujuan')
                    ->schema([
                        Toggle::make('to_self_account')
                            ->label('Kirim ke Rekening Sendiri')
                            ->default(true)
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $set('bank_account_number', Auth::user()->employee->bank_account_number);
                                    $set('bank_name', Auth::user()->employee->bank_name);
                                    $set('bank_cust_name', Auth::user()->employee->bank_cust_name);
                                } else {
                                    $set('bank_account_number', null);
                                    $set('bank_name', null);
                                    $set('bank_cust_name', null);
                                }
                            })
                            ->partiallyRenderComponentsAfterStateUpdated(['bank_account_number', 'bank_name', 'bank_cust_name']),

                        TextInput::make('bank_account_number')
                            ->label('Nomor Rekening')
                            ->numeric()
                            ->readOnly(fn(Get $get) => $get('to_self_account'))
                            ->default(Auth::user()->employee->bank_account_number)
                            ->dehydrateStateUsing(fn($state) => (string) $state),
                        TextInput::make('bank_name')
                            ->label('Nama Bank')
                            ->readOnly(fn(Get $get) => $get('to_self_account'))
                            ->default(Auth::user()->employee->bank_name),
                        TextInput::make('bank_cust_name')
                            ->label('Nama Pemilik Bank')
                            ->readOnly(fn(Get $get) => $get('to_self_account'))
                            ->default(Auth::user()->employee->bank_cust_name),
                    ])
            ])
            ->columns(2);
    }

    protected static function calculateAmount(Set $set, Get $get): void
    {
        // dd($get('amount_per_item'));
        $quantity = (float) $get('quantity') ?: 1;
        $amountPerItem = (float) $get('amount_per_item') ?: 0;

        $totalAmount = $quantity * $amountPerItem;
        $set('amount', $totalAmount);
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

<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Models\ProgramCategory;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ClientCreateForm2
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Section::make('Informasi Klien')
                //     ->schema([
                //         TextInput::make('name')
                //             ->required()
                //             ->maxLength(255)
                //             ->label('Nama Klien')
                //             ->trim()
                //             ->columnSpanFull(),
                //         TextInput::make('code')
                //             ->required()
                //             ->label('Kode Unik Klien')
                //             ->unique(ignoreRecord: true)
                //             ->maxLength(50)
                //             ->placeholder('e.g., DMF')
                //             ->helperText('Kode Unik Klien sebagai referensi'),
                //     ])
                //     ->columns(2),
                // Section::make('Informasi Kontrak Kerjasama')
                //     ->schema([
                //         TextInput::make('contract_year')
                //             ->required()
                //             ->label('Tahun Kontrak')
                //             ->numeric()
                //             ->minValue(2020)
                //             ->maxValue(2050)
                //             ->default(now()->year)
                //             ->columnSpan([
                //                 'default' => 6,
                //                 'xl' => 2
                //             ]),
                //         DatePicker::make('start_date')
                //             ->required()
                //             ->native(false)
                //             ->label('Awal Periode')
                //             ->default(now())
                //             ->displayFormat('d/m/Y')
                //             ->columnSpan([
                //                 'default' => 6,
                //                 'xl' => 2
                //             ]),

                //         DatePicker::make('end_date')
                //             ->required()
                //             ->native(false)
                //             ->label('Akhir Periode')
                //             ->displayFormat('d/m/Y')
                //             ->default(now()->addYear())
                //             ->after('start_date')
                //             ->columnSpan([
                //                 'default' => 6,
                //                 'xl' => 2
                //             ]),
                //     ])->columns(6),
                // Section::make('Daftar Program')
                //     ->columnSpanFull()
                //     ->schema([
                //         Repeater::make('programs')
                //             ->hiddenLabel()
                //             ->schema([
                //                 Group::make()
                //                     ->schema([
                //                         TextInput::make('name')
                //                             ->label('Nama Program')
                //                             ->trim()
                //                             ->live(onBlur: true)
                //                             ->partiallyRenderComponentsAfterStateUpdated(['program_category_id', 'budget_amount', 'program_activities'])
                //                             // ->afterStateUpdated(function (Set $set, $state) {
                //                             //     if (null === $state) {
                //                             //         self::clearPrograms($set);
                //                             //     }
                //                             // })
                //                             ->dehydrated(fn($state) => null !== $state),
                //                         Select::make('program_category_id')
                //                             ->disabled(fn(Get $get) => null === $get('name'))
                //                             ->required(fn(Get $get) => null !== $get('name'))
                //                             ->label('Kategori Program')
                //                             ->options(ProgramCategory::query()->pluck('name', 'id'))
                //                     ])
                //                     ->columns(2),

                //                 Repeater::make('program_activities')
                //                     ->label('Rincian Aktivitas Program')
                //                     ->disabled(fn(Get $get) => null === $get('name'))
                //                     ->schema([
                //                         TextInput::make('name')
                //                             ->label('Nama Aktivitas')
                //                             ->live(onBlur: true)
                //                             ->partiallyRenderComponentsAfterStateUpdated(['program_activity_items'])
                //                             ->disabled(fn(Get $get) => null === $get('../../name'))
                //                             ->required(fn(Get $get) => null !== $get('../../name')),
                //                         Repeater::make('program_activity_items')
                //                             ->label('Item Aktivitas')
                //                             ->schema([
                //                                 TextInput::make('description')
                //                                     ->label('Deskripsi Item')
                //                                     ->live(onBlur: true)
                //                                     ->partiallyRenderComponentsAfterStateUpdated(['volume', 'unit', 'frequency', 'total_item_budget', 'total_item_planned_budget'])
                //                                     ->disabled(fn(Get $get) => null === $get('../../name'))
                //                                     ->required(fn(Get $get) => null !== $get('../../name')),
                //                                 Group::make()
                //                                     ->schema([
                //                                         TextInput::make('volume')
                //                                             ->label('Volume')
                //                                             ->numeric()
                //                                             ->minValue(1)
                //                                             ->disabled(fn(Get $get) => null === $get('description'))
                //                                             ->required(fn(Get $get) => null !== $get('description')),
                //                                         TextInput::make('unit')
                //                                             ->label('Satuan')
                //                                             ->disabled(fn(Get $get) => null === $get('description'))
                //                                             ->required(fn(Get $get) => null !== $get('description')),
                //                                         TextInput::make('frequency')
                //                                             ->label('Frekuensi')
                //                                             ->numeric()
                //                                             ->minValue(1)
                //                                             ->default(1)
                //                                             ->disabled(fn(Get $get) => null === $get('description'))
                //                                             ->required(fn(Get $get) => null !== $get('description')),
                //                                     ])
                //                                     ->columns(3),
                //                                 TextInput::make('total_item_budget')
                //                                     ->label('Nilai Kontrak Item')
                //                                     ->numeric()
                //                                     ->minValue(0)
                //                                     ->prefix('Rp')
                //                                     ->disabled(fn(Get $get) => null === $get('description'))
                //                                     ->required(fn(Get $get) => null !== $get('description'))
                //                                     ->live(onBlur: true)
                //                                     ->partiallyRenderAfterStateUpdated()
                //                                     ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateBudget($get, $set)),
                //                                 TextInput::make('total_item_planned_budget')
                //                                     ->label('Nilai Planned Item')
                //                                     ->numeric()
                //                                     ->minValue(0)
                //                                     ->prefix('Rp')
                //                                     ->disabled(fn(Get $get) => null === $get('description'))
                //                                     ->required(fn(Get $get) => null !== $get('description'))
                //                                     ->live(onBlur: true)
                //                                     ->partiallyRenderAfterStateUpdated()
                //                                     ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateBudget($get, $set)),
                //                             ])
                //                             ->defaultItems(2)
                //                             ->reorderable(false)
                //                             ->collapsible()
                //                             ->itemLabel(fn(array $state): ?string => $state['description'] ?? 'Item Baru')
                //                             ->grid(2)
                //                     ])
                //                     ->reorderable(false)
                //                     ->collapsible()
                //                     ->itemLabel(fn(array $state): ?string => $state['name'] ?? 'Aktifitas Baru'),

                //                 Group::make()
                //                     ->schema([
                //                         TextInput::make('budget_amount')
                //                             ->label('Nilai Kontrak Program')
                //                             ->disabled()
                //                             ->dehydrated(false)
                //                             ->prefix('Rp')
                //                             ->numeric()
                //                             ->default(0),
                //                         TextInput::make('planned_budget')
                //                             ->label('Nilai Planned Program')
                //                             ->disabled()
                //                             ->dehydrated(false)
                //                             ->prefix('Rp')
                //                             ->numeric()
                //                             ->default(0)
                //                     ])
                //                     ->columns(2)
                //             ])
                //             ->reorderable(false)
                //             ->collapsible()
                //             ->itemLabel(fn(array $state): ?string => $state['name'] ?? 'Program Baru'),
                //     ]),
                Wizard::make([
                    Step::make('Detail Klien dan Kontrak Kerja')
                        ->schema([
                            Grid::make()
                                ->schema([
                                    Fieldset::make('Detail Klien')
                                        ->columns(1)
                                        ->schema([
                                            TextInput::make('name')
                                                // ->required()
                                                ->maxLength(255)
                                                ->label('Nama Klien')
                                                ->trim(),

                                            TextInput::make('code')
                                                // ->required()
                                                ->label('Kode Unik Klien')
                                                ->unique(ignoreRecord: true)
                                                ->live(debounce: 700)
                                                ->maxLength(50)
                                                ->placeholder('e.g., DMF')
                                                ->helperText('Kode Unik Klien sebagai referensi')
                                        ]),
                                    Fieldset::make('Informasi Kontrak Kerja')
                                        ->columns(1)
                                        ->schema([
                                            TextInput::make('contract_year')
                                                // ->required()
                                                ->label('Tahun Kontrak')
                                                ->numeric()
                                                ->live(debounce: 700)
                                                ->minValue(2020)
                                                ->maxValue(2050)
                                                ->default(now()->year),

                                            Grid::make()
                                                ->schema([
                                                    DatePicker::make('start_date')
                                                        // ->required()
                                                        ->native(false)
                                                        ->label('Awal Periode')
                                                        ->default(now())
                                                        ->displayFormat('d/m/Y'),

                                                    DatePicker::make('end_date')
                                                        // ->required()
                                                        ->native(false)
                                                        ->label('Akhir Periode')
                                                        ->displayFormat('d/m/Y')
                                                        ->default(now()->addYear())
                                                        ->after('start_date')
                                                ]),
                                        ]),
                                ])
                        ]),
                    Step::make('Daftar Program')
                        ->schema([
                            Repeater::make('programs')
                                ->hiddenLabel()
                                ->live(debounce: 700)
                                ->table([
                                    TableColumn::make('Nama Program'),
                                    TableColumn::make('Kategori Program'),
                                ])
                                ->compact()
                                ->schema([
                                    TextInput::make('name')
                                        ->trim()
                                        ->placeholder('Ketikan nama program')
                                        ->live(debounce: 700),
                                    // ->partiallyRenderComponentsAfterStateUpdated(['program_category_id', 'budget_amount', 'program_activities'])
                                    // ->dehydrated(fn($state) => null !== $state),
                                    Select::make('program_category_id')
                                        // ->disabled(fn(Get $get) => null === $get('name'))
                                        // ->required(fn(Get $get) => null !== $get('name'))
                                        ->options(ProgramCategory::query()->pluck('name', 'id'))
                                        ->default(1)
                                        ->selectablePlaceholder(false)
                                ])
                                ->columns(2)
                                ->reorderable(false)
                        ]),
                    Step::make('Daftar Aktivitas Program')
                        ->schema([
                            Repeater::make('program_activities')
                                ->hiddenLabel()
                                ->table([
                                    TableColumn::make('Nama Aktivitas'),
                                    TableColumn::make('Program'),
                                ])
                                ->compact()
                                ->live(debounce: 700)
                                ->schema([
                                    TextInput::make('name')
                                        ->trim()
                                        ->placeholder('Ketikan nama aktivitas')
                                        ->live(debounce: 700),
                                    // ->partiallyRenderComponentsAfterStateUpdated(['program_category_id', 'budget_amount', 'program_activities'])
                                    // ->dehydrated(fn($state) => null !== $state),
                                    Select::make('program_code')
                                        // ->disabled(fn(Get $get) => null === $get('name'))
                                        // ->required(fn(Get $get) => null !== $get('name'))
                                        ->live(debounce: 700)
                                        ->options(function (Get $get) {
                                            $programs = $get('../../programs');
                                            $options = [];
                                            foreach ($programs as $program) {
                                                $programCode = implode('-', [$get('../../code'), str_replace(' ', '-', $program['name'])]);
                                                if (!empty($program['name'])) {
                                                    $options[$programCode] = $program['name'];
                                                }
                                            }

                                            return $options;
                                        })
                                ])
                                ->columns(2)
                                ->reorderable(false)
                        ]),
                    Step::make('Rincian Item Aktivitas')
                        ->schema([
                            Repeater::make('program_activity_items')
                                ->hiddenLabel()
                                ->live(debounce: 700)
                                ->table([
                                    TableColumn::make('Deskripsi')->width('200px'),
                                    TableColumn::make('Aktivitas')->width('150px'),
                                    TableColumn::make('Kuantitas'),
                                    TableColumn::make('Satuan'),
                                    TableColumn::make('Frekuensi'),
                                    TableColumn::make('Nilai Kontrak Item')->width('300px'),
                                    TableColumn::make('Nilai Planned Item')->width('300px'),
                                ])
                                ->compact()
                                ->schema([
                                    TextInput::make('description')
                                        ->trim()
                                        ->placeholder('Ketikan deskripsi item')
                                        ->live(onBlur: true)
                                        ->partiallyRenderComponentsAfterStateUpdated(['program_activity_code']),
                                    // ->live(onBlur: true)
                                    // ->partiallyRenderComponentsAfterStateUpdated(['program_category_id', 'budget_amount', 'program_activities'])
                                    // ->dehydrated(fn($state) => null !== $state),
                                    Select::make('program_activity_code')
                                        // ->disabled(fn(Get $get) => null === $get('name'))
                                        // ->required(fn(Get $get) => null !== $get('name'))
                                        ->options(function (Get $get) {
                                            $activities = $get('../../program_activities');
                                            $options = [];
                                            foreach ($activities as $activity) {
                                                $coaCode = implode("-", [$activity['program_code'], (string)$get('../../contract_year')]);
                                                $activityCode = implode("-", [$coaCode, Str::slug($activity['name'])]);
                                                if (!empty($activity['name'])) {
                                                    $options[$activityCode] = $activity['name'];
                                                }
                                            }

                                            return $options;
                                        }),
                                    TextInput::make('quantity')
                                        ->numeric()
                                        ->minValue(1),
                                    TextInput::make('unit')
                                        ->trim()
                                        ->placeholder('Kg, pcs, dll.'),
                                    TextInput::make('frequency')
                                        ->numeric()
                                        ->minValue(1),
                                    TextInput::make('total_item_budget')
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->minValue(1),
                                    TextInput::make('total_item_planned_budget')
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->minValue(1),
                                ])
                                ->columns(2)
                                ->reorderable(false)
                        ]),

                    Step::make('Review')
                        ->schema([
                            Fieldset::make('Detail Klien dan Kontrak Kerja')
                                ->columns(5)
                                ->schema([
                                    TextEntry::make('client_name')
                                        ->label('Nama Klien')
                                        ->state(fn(Get $get) => $get('name') . ' (' . $get('code') . ')'),
                                    TextEntry::make('contract_year_review')
                                        ->label('Tahun Kontrak')
                                        ->state(fn(Get $get) => $get('contract_year')),
                                    TextEntry::make('start_date_review')
                                        ->label('Awal Periode')
                                        ->state(fn(Get $get) => $get('start_date'))
                                        ->date('j M y'),
                                    TextEntry::make('end_date_review')
                                        ->label('Akhir Periode')
                                        ->state(fn(Get $get) => $get('end_date'))
                                        ->date('j M y'),
                                    TextEntry::make('total_contract_value')
                                        ->label('Total Nilai Kontrak')
                                        ->state(function (Get $get) {
                                            $activityItems = $get('program_activity_items') ?? [];

                                            $totalContractValue = 0;
                                            foreach ($activityItems as $itemIndex => $item) {
                                                $totalContractValue += (float) $item['total_item_budget'];
                                            }

                                            return $totalContractValue;
                                        })
                                        ->money(currency: 'IDR', locale: 'id')
                                ]),
                            RepeatableEntry::make('programs_review')
                                ->label('Daftar Program')
                                ->state(function (Get $get) {
                                    $programs = $get('programs');
                                    $programActivities = $get('program_activities');
                                    $programActivityItems = $get('program_activity_items');

                                    $results = [];

                                    // Loop through each program
                                    foreach ($programs as $program) {
                                        $programResult = [
                                            'name' => $program['name'],
                                            'code' => implode('-', [$get('code'), str_replace(' ', '-', $program['name'])]),
                                            'program_activity_items_review' => []
                                        ];

                                        // Find all activities for this program
                                        foreach ($programActivities as $activity) {
                                            $coaCode = implode("-", [$activity['program_code'], (string)$get('contract_year')]);
                                            $activityCode = implode("-", [$coaCode, Str::slug($activity['name'])]);
                                            if ($activity['program_code'] === $programResult['code']) {
                                                // Find all items for this activity
                                                foreach ($programActivityItems as $item) {
                                                    if ($item['program_activity_code'] === $activityCode) {
                                                        // Add the item with the activity name
                                                        $item['program_activity_name'] = $activity['name'];
                                                        $programResult['program_activity_items_review'][] = $item;
                                                    }
                                                }
                                            }
                                        }

                                        $results[] = $programResult;
                                    }

                                    return $results;
                                })
                                ->schema([
                                    Flex::make([
                                        TextEntry::make('name')
                                            ->grow(false)
                                            ->hiddenLabel()
                                            ->weight(FontWeight::ExtraBold),
                                        TextEntry::make('program_category_id')
                                            ->grow(false)
                                            ->hiddenLabel()
                                            ->formatStateUsing(fn($state) => ProgramCategory::find($state)->name)
                                    ])->from('md'),
                                    RepeatableEntry::make('program_activity_items_review')
                                        ->label('Rincian Item')
                                        ->table([
                                            TableColumn::make('Deskripsi'),
                                            TableColumn::make('Aktivitas'),
                                            TableColumn::make('Qty'),
                                            TableColumn::make('Unit Qty'),
                                            TableColumn::make('Frekuensi'),
                                            TableColumn::make('Nilai Kontrak Item'),
                                            TableColumn::make('Nilai Planned Item'),
                                        ])
                                        ->schema([
                                            TextEntry::make('description'),
                                            TextEntry::make('program_activity_name'),
                                            TextEntry::make('quantity'),
                                            TextEntry::make('unit'),
                                            TextEntry::make('frequency'),
                                            TextEntry::make('total_item_budget')
                                                ->money(currency: 'IDR', locale: 'id'),
                                            TextEntry::make('total_item_planned_budget')
                                                ->money(currency: 'IDR', locale: 'id'),
                                        ])
                                        ->contained(false)
                                ])
                        ]),
                ])
                    ->columnSpanFull()
                    ->submitAction(new HtmlString(Blade::render(<<<BLADE
    <x-filament::button
        type="submit"
        size="sm"
    >
        Submit
    </x-filament::button>
BLADE)))
            ]);
    }

    // protected static function clearPrograms(Set $set)
    // {
    //     $set('program_category_id', null);
    //     $set('budget_amount', null);
    //     $set('program_activities', null);
    // }

    protected static function calculateBudget(Get $get, Set $set)
    {
        $data = $get('../../../../program_activities');

        $totalContractBudgetAmount = 0;
        $totalPlannedBudgetAmount = 0;

        foreach ($data as $index => $programActivities) {
            foreach ($programActivities['program_activity_items'] as $index2 => $activityItems) {
                $totalContractBudgetAmount += (float) $activityItems['total_item_budget'];
                $totalPlannedBudgetAmount += (float) $activityItems['total_item_planned_budget'];
            }
        }

        $set('../../../../budget_amount', $totalContractBudgetAmount);
        $set('../../../../planned_budget', $totalPlannedBudgetAmount);
    }
}

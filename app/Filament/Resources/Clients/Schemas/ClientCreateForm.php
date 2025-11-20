<?php

namespace App\Filament\Resources\Clients\Schemas;

use App\Models\ProgramCategory;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use phpDocumentor\Reflection\PseudoTypes\True_;

class ClientCreateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Klien')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Nama Klien')
                            ->trim()
                            ->columnSpanFull(),
                        TextInput::make('code')
                            ->required()
                            ->label('Kode Unik Klien')
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('e.g., DMF')
                            ->helperText('Kode Unik Klien sebagai referensi'),
                    ])
                    ->columns(2),
                Section::make('Informasi Kontrak Kerjasama')
                    ->schema([
                        TextInput::make('contract_year')
                            ->required()
                            ->label('Tahun Kontrak')
                            ->numeric()
                            ->minValue(2020)
                            ->maxValue(2050)
                            ->default(now()->year)
                            ->columnSpan([
                                'default' => 6,
                                'xl' => 2
                            ]),
                        DatePicker::make('start_date')
                            ->required()
                            ->native(false)
                            ->label('Awal Periode')
                            ->default(now())
                            ->displayFormat('d/m/Y')
                            ->columnSpan([
                                'default' => 6,
                                'xl' => 2
                            ]),

                        DatePicker::make('end_date')
                            ->required()
                            ->native(false)
                            ->label('Akhir Periode')
                            ->displayFormat('d/m/Y')
                            ->default(now()->addYear())
                            ->after('start_date')
                            ->columnSpan([
                                'default' => 6,
                                'xl' => 2
                            ]),
                    ])->columns(6),
                Section::make('Daftar Program')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('programs')
                            ->hiddenLabel()
                            ->schema([
                                Group::make()
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Nama Program')
                                            ->trim()
                                            ->live(onBlur: true)
                                            ->partiallyRenderComponentsAfterStateUpdated(['program_category_id', 'budget_amount', 'program_activities'])
                                            // ->afterStateUpdated(function (Set $set, $state) {
                                            //     if (null === $state) {
                                            //         self::clearPrograms($set);
                                            //     }
                                            // })
                                            ->dehydrated(fn($state) => null !== $state),
                                        Select::make('program_category_id')
                                            ->disabled(fn(Get $get) => null === $get('name'))
                                            ->required(fn(Get $get) => null !== $get('name'))
                                            ->label('Kategori Program')
                                            ->options(ProgramCategory::query()->pluck('name', 'id'))
                                    ])
                                    ->columns(2),

                                Repeater::make('program_activities')
                                    ->label('Rincian Aktivitas Program')
                                    ->disabled(fn(Get $get) => null === $get('name'))
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Nama Aktivitas')
                                            ->live(onBlur: true)
                                            ->partiallyRenderComponentsAfterStateUpdated(['program_activity_items'])
                                            ->disabled(fn(Get $get) => null === $get('../../name'))
                                            ->required(fn(Get $get) => null !== $get('../../name')),
                                        Repeater::make('program_activity_items')
                                            ->label('Item Aktivitas')
                                            ->schema([
                                                TextInput::make('description')
                                                    ->label('Deskripsi Item')
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderComponentsAfterStateUpdated(['volume', 'unit', 'frequency', 'total_item_budget', 'total_item_planned_budget'])
                                                    ->disabled(fn(Get $get) => null === $get('../../name'))
                                                    ->required(fn(Get $get) => null !== $get('../../name')),
                                                Group::make()
                                                    ->schema([
                                                        TextInput::make('volume')
                                                            ->label('Volume')
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->disabled(fn(Get $get) => null === $get('description'))
                                                            ->required(fn(Get $get) => null !== $get('description')),
                                                        TextInput::make('unit')
                                                            ->label('Satuan')
                                                            ->disabled(fn(Get $get) => null === $get('description'))
                                                            ->required(fn(Get $get) => null !== $get('description')),
                                                        TextInput::make('frequency')
                                                            ->label('Frekuensi')
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->default(1)
                                                            ->disabled(fn(Get $get) => null === $get('description'))
                                                            ->required(fn(Get $get) => null !== $get('description')),
                                                    ])
                                                    ->columns(3),
                                                TextInput::make('total_item_budget')
                                                    ->label('Nilai Kontrak Item')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->prefix('Rp')
                                                    ->disabled(fn(Get $get) => null === $get('description'))
                                                    ->required(fn(Get $get) => null !== $get('description'))
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateBudget($get, $set)),
                                                TextInput::make('total_item_planned_budget')
                                                    ->label('Nilai Planned Item')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->prefix('Rp')
                                                    ->disabled(fn(Get $get) => null === $get('description'))
                                                    ->required(fn(Get $get) => null !== $get('description'))
                                                    ->live(onBlur: true)
                                                    ->partiallyRenderAfterStateUpdated()
                                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateBudget($get, $set)),
                                            ])
                                            ->defaultItems(2)
                                            ->reorderable(false)
                                            ->collapsible()
                                            ->itemLabel(fn(array $state): ?string => $state['description'] ?? 'Item Baru')
                                            ->grid(2)
                                    ])
                                    ->reorderable(false)
                                    ->collapsible()
                                    ->itemLabel(fn(array $state): ?string => $state['name'] ?? 'Aktifitas Baru'),

                                Group::make()
                                    ->schema([
                                        TextInput::make('budget_amount')
                                            ->label('Nilai Kontrak Program')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->prefix('Rp')
                                            ->numeric()
                                            ->default(0),
                                        TextInput::make('planned_budget')
                                            ->label('Nilai Planned Program')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->prefix('Rp')
                                            ->numeric()
                                            ->default(0)
                                    ])
                                    ->columns(2)
                            ])
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => $state['name'] ?? 'Program Baru'),
                    ])
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

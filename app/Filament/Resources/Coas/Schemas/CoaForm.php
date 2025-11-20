<?php

namespace App\Filament\Resources\Coas\Schemas;

use App\Enums\COAType;
use App\Models\Coa;
use App\Models\PartnershipContract;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CoaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi COA')
                    ->schema([
                        // TextInput::make('code')
                        //     ->label('Kode COA')
                        //     ->required()
                        //     ->unique(ignoreRecord: true)
                        //     ->maxLength(100)
                        //     ->placeholder('e.g., PTM-2025-HEALTH-001 or EXP-TRAVEL-001')
                        //     ->columnSpan([
                        //         'default' => 6,
                        //         'lg' => 2
                        //     ]),
                        TextInput::make('name')
                            ->label('Nama COA')
                            ->required()
                            ->maxLength(255)
                            ->trim()
                            ->columnSpan([
                                'default' => 6,
                                'lg' => 2
                            ]),

                        Select::make('type')
                            ->label('Tipe COA')
                            ->required()
                            ->options(COAType::class)
                            ->default(COAType::Program)
                            ->live()
                            ->partiallyRenderComponentsAfterStateUpdated(['program_id', 'partnership_contract_id', 'contract_year'])
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state === COAType::NonProgram) {
                                    $set('program_id', null);
                                    $set('partnership_contract_id', null);
                                    $set('contract_year', null);
                                }
                            })->columnSpan([
                                'default' => 6,
                                'lg' => 2
                            ]),

                        Select::make('program_id')
                            ->label('Program')
                            ->relationship('program', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->partiallyRenderComponentsAfterStateUpdated(['partnership_contract_id'])
                            ->required(fn(Get $get) => $get('type') === COAType::Program)
                            ->hidden(fn(Get $get) => $get('type') === COAType::NonProgram)
                            ->helperText('Required if type is Program')->columnSpan([
                                'default' => 6,
                                'lg' => 2
                            ]),

                        Select::make('partnership_contract_id')
                            ->label('Kontrak Kerja')
                            ->options(function (Get $get) {
                                $coas = Coa::where('program_id', $get('program_id'))
                                    ->select('contract_year')
                                    ->distinct()
                                    ->pluck('contract_year');

                                return PartnershipContract::query()->whereHas('programs', function ($query) use ($get) {
                                    $query->where('programs.id', $get('program_id'));
                                })
                                    ->where(function ($query) use ($coas) {
                                        if ($coas->isEmpty()) {
                                            return;
                                        }
                                        $query->whereNotIn('contract_year', $coas);
                                    })
                                    ->pluck('contract_number', 'id');
                            })
                            ->searchable()
                            ->required(fn(Get $get) => $get('type') === COAType::Program)
                            ->hidden(fn(Get $get) => $get('type') === COAType::NonProgram)
                            ->live(onBlur: true)
                            ->partiallyRenderComponentsAfterStateUpdated(['contract_year'])
                            ->afterStateUpdated(fn($state, Set $set) => $state === null ? $set('contract_year', null) :  $set('contract_year', PartnershipContract::find((int)$state)->contract_year))
                            ->helperText('Required if type is Program')
                            ->columnSpan([
                                'default' => 6,
                                'lg' => 2
                            ]),

                        TextInput::make('contract_year')
                            ->disabled(fn(Get $get) => $get('type') === COAType::Program)
                            ->hidden(fn(Get $get) => $get('type') === COAType::NonProgram)
                            ->dehydrated(fn(Get $get) => $get('type') === COAType::Program)
                            ->required(fn(Get $get) => $get('type') === COAType::Program)
                            ->label('Tahun Kontrak')
                            ->numeric()
                            ->minValue(2020)
                            ->maxValue(2050)
                            ->columnSpan([
                                'default' => 6,
                                'lg' => 2
                            ]),

                        TextInput::make('budget_amount')
                            ->label('Budget')
                            ->numeric()
                            ->minValue(1)
                            ->prefix('Rp')
                            ->required()
                            ->live(debounce: 1000)
                            ->partiallyRenderAfterStateUpdated(true)
                            ->belowContent(fn($state) => 'Rp ' . number_format($state, 2, ',', '.'))
                            ->columnSpan([
                                'default' => 6,
                                'lg' => 2
                            ]),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false),
                    ])
                    ->columns(6)
                    ->columnSpanFull(),
            ]);
    }
}

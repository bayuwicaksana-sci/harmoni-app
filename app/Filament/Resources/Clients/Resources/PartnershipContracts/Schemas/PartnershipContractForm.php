<?php

namespace App\Filament\Resources\Clients\Resources\PartnershipContracts\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PartnershipContractForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kontrak Kerjasama')
                    ->schema([
                        TextInput::make('contract_number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->label('Nomor Kontrak')
                            ->placeholder('e.g., PTM/CSR/2025/001')
                            ->columnSpan([
                                'default' => 4,
                                'md' => 1
                            ]),

                        TextInput::make('contract_year')
                            ->required()
                            ->numeric()
                            ->minValue(2020)
                            ->maxValue(2050)
                            ->default(now()->year)
                            ->helperText('Tahun Kontrak')
                            ->columnSpan([
                                'default' => 4,
                                'md' => 1
                            ]),
                        DatePicker::make('start_date')
                            ->required()
                            ->native(false)
                            ->label('Dari')
                            ->displayFormat('d/m/Y')
                            ->columnSpan([
                                'default' => 4,
                                'md' => 1
                            ]),

                        DatePicker::make('end_date')
                            ->required()
                            ->native(false)
                            ->label('Hingga')
                            ->displayFormat('d/m/Y')
                            ->after('start_date')
                            ->columnSpan([
                                'default' => 4,
                                'md' => 1
                            ]),
                        TextInput::make('contract_value')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->label('Nilai Kontrak')
                            ->belowContent(fn($state) => "Rp " . number_format($state, 2, ',', '.'))
                            ->columnSpan([
                                'default' => 4,
                                'md' => 2
                            ]),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
            ]);
    }
}

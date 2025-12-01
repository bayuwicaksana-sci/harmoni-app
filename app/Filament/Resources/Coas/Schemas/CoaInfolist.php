<?php

namespace App\Filament\Resources\Coas\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CoaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail COA')
                    ->schema([
                        TextEntry::make('code')
                            ->label('Kode COA')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('name')->label('Nama COA'),
                        TextEntry::make('type')
                            ->label('Tipe COA')
                            ->badge(),
                        TextEntry::make('program.name')
                            ->label('Program')
                            ->placeholder('N/A'),
                        IconEntry::make('is_active')
                            ->label('Status Aktif')
                            ->boolean(),
                    ])
                    ->columns(3),

                Section::make('Usage Statistics')
                    ->schema([
                        // TextEntry::make('total_allocated')
                        //     ->label('Total Allocated/Used')
                        //     ->money('IDR')
                        //     ->getStateUsing(fn($record) => $record->getTotalSpent()),

                        TextEntry::make('transaction_count')
                            ->label('Number of Transactions')
                            ->getStateUsing(fn ($record) => $record->requestItems()->count()),
                    ])
                    ->columns(2),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Programs\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProgramInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Program Details')
                    ->schema([
                        TextEntry::make('code')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('name'),
                        TextEntry::make('programCategory.name')
                            ->badge(),
                        TextEntry::make('partnershipContract.contract_number'),
                        TextEntry::make('partnershipContract.contract_year')
                            ->badge(),
                    ])
                    ->columns(3),
            ]);
    }
}

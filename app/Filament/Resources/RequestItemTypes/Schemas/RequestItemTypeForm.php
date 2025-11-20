<?php

namespace App\Filament\Resources\RequestItemTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RequestItemTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Item Type Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('e.g., Professional Services, Consultant Fee'),

                        Select::make('tax_id')
                            ->relationship('tax', 'name')
                            ->createOptionForm([
                                TextInput::make('code')
                                    ->unique(table: 'taxes', column: 'code')
                                    ->required(),
                                TextInput::make('name')
                                    ->required(),
                                TextInput::make('value')
                                    ->label('Tax Rate (%)')
                                    ->numeric()
                                    ->required()
                                    ->email(),
                            ])
                            ->required(),
                    ])
                    ->columns(3),
            ]);
    }
}

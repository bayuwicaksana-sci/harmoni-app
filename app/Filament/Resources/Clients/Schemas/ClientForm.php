<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientForm
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
                            ->label('Nama Klien'),
                        TextInput::make('code')
                            ->required()
                            ->label('Kode Unik Klien')
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('e.g., DMF')
                            ->helperText('Kode Unik Klien sebagai referensi'),
                    ])
                    ->columns(2),
            ])
            ->columns(1);
    }
}

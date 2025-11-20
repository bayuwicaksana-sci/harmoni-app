<?php

namespace App\Filament\Resources\JobTitles\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JobTitleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Job Title Information')
                    ->schema([
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(100),

                        Select::make('job_level_id')
                            ->required()
                            ->relationship('jobLevel', 'name')
                    ])
                    ->columns(3),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal Information')
                    ->schema([
                        Group::make([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('email')
                                ->email()
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),
                            TextInput::make('password')
                                ->password()
                                ->revealable()
                                ->dehydrateStateUsing(fn($state) => Hash::make($state))
                                ->dehydrated(fn($state) => filled($state))
                                ->required(fn(string $context): bool => $context === 'create')
                                ->helperText('Leave blank to keep current password')
                                ->hiddenOn('view'),
                        ])
                            ->relationship('user')
                    ]),
                Section::make('Organization')
                    ->schema([

                        Select::make('job_title_id')
                            ->relationship(
                                'jobTitle',
                                'name'
                            )
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('job_grade_id')
                            ->relationship('jobGrade', 'grade')
                            ->required()
                            ->preload(),
                    ])
                    ->columns(3),

                Section::make('Supervisor')
                    ->description('Select direct supervisor for this employee')
                    ->schema([
                        Select::make('supervisor_id')
                            ->relationship('supervisor', 'user.id')
                            ->searchable()
                            ->preload()
                            ->helperText('Leave empty if this is a top-level position (e.g., CEO)'),
                    ]),
                Section::make('Informasi Tambahan')
                    ->schema([
                        TextInput::make('bank_name'),
                        TextInput::make('bank_account_number'),
                        TextInput::make('bank_cust_name'),
                    ])

            ]);
    }
}

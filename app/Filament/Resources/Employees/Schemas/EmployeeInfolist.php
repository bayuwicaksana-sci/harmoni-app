<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal Information')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email')
                            ->copyable(),
                    ])
                    ->columns(2),

                Section::make('Organization Structure')
                    ->schema([
                        TextEntry::make('jobTitle.name'),
                        TextEntry::make('jobTitle.jobLevel.level')
                            ->label('Job Level')
                            ->badge()
                            ->formatStateUsing(fn(int $state): string => "Level {$state}"),
                        TextEntry::make('jobGrade.grade')
                            ->badge(),
                    ])
                    ->columns(4),

                Section::make('Hierarchy')
                    ->schema([
                        TextEntry::make('supervisor.user.name')
                            ->label('Reports To')
                            ->placeholder('No supervisor (Top-level position)'),

                        TextEntry::make('subordinates_count')
                            ->label('Direct Reports')
                            ->getStateUsing(fn($record) => $record->subordinates()->count()),
                    ])
                    ->columns(2),
                TextEntry::make('bank_name')
                    ->placeholder('-'),
                TextEntry::make('bank_account_number')
                    ->placeholder('-'),
                TextEntry::make('bank_cust_name')
                    ->placeholder('-'),
            ]);
    }
}

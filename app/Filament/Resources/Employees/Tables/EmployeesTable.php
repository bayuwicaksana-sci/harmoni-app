<?php

namespace App\Filament\Resources\Employees\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('user.email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('jobTitle.title')
                    ->label('Job Title')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('jobTitle.jobLevel.level')
                    ->label('Level')
                    ->badge()
                    ->color(fn(int $state): string => match ($state) {
                        5 => 'danger',
                        4 => 'warning',
                        3 => 'info',
                        2 => 'success',
                        1 => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('jobGrade.grade')
                    ->label('Grade')
                    ->badge()
                    ->color('info'),

                TextColumn::make('supervisor.user.name')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('subordinates_count')
                    ->counts('subordinates')
                    ->label('Team Size')
                    ->badge()
                    ->color('success')
                    ->toggleable(),
                TextColumn::make('bank_name')
                    ->searchable(),
                TextColumn::make('bank_account_number')
                    ->searchable(),
                TextColumn::make('bank_cust_name')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('job_title')
                    ->relationship('jobTitle', 'title')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('job_grade')
                    ->relationship('jobGrade', 'grade')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

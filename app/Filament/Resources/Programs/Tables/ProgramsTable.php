<?php

namespace App\Filament\Resources\Programs\Tables;

use App\Models\PartnershipContract;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProgramsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('programCategory.name')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Health' => 'danger',
                        'Education' => 'info',
                        'Economy' => 'success',
                        'Environment' => 'warning',
                        'Social and Culture' => 'primary',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('programCategory')
                    ->label('Kategori')
                    ->relationship('programCategory', 'name')
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

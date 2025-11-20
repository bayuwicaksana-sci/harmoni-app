<?php

namespace App\Filament\Resources\RequestItemTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RequestItemTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('tax.name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('warning'),

                TextColumn::make('tax.value')
                    ->label('Tax Rate')
                    ->formatStateUsing(fn($state) => number_format($state * 100, 2) . '%')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('requestItems_count')
                    ->counts('requestItems')
                    ->label('Usage Count')
                    ->badge()
                    ->color('success')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // SelectFilter::make('tax_type')
                //     ->options(function () {
                //         return \App\Models\RequestItemType::distinct()
                //             ->pluck('tax_type', 'tax_type')
                //             ->toArray();
                //     }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

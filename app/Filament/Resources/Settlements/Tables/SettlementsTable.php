<?php

namespace App\Filament\Resources\Settlements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SettlementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('settlement_number')
                    ->label('Nomor Settlement')
                    ->badge()
                    ->searchable(),
                TextColumn::make('submitter.user.name')
                    ->label('Submitter')
                    ->searchable(),
                TextColumn::make('submit_date')
                    ->label('Tanggal Submit')
                    ->dateTime(format: 'd M Y H:i', timezone: 'Asia/Jakarta')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
            ])
            ->filters([
                //
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

<?php

namespace App\Filament\Resources\Settlements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SettlementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // TextColumn::make('dailyPaymentRequest.request_number')
                //     ->label('Request ID'),
                TextColumn::make('coa.name')
                    ->label('COA')
                    ->searchable(),
                TextColumn::make('programActivity.name')
                    ->label('Aktivitas')
                    ->placeholder('N/A'),
                TextColumn::make('description')
                    ->label('Deskripsi'),
                TextColumn::make('payment_type')
                    ->label('Tipe Request')
                    ->badge()
                    ->searchable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('amount_per_item')
                    ->label('Harga per Item')
                    ->numeric()
                    ->money(currency: "IDR", locale: "id")
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->getStateUsing(fn($record) => $record->total_amount)
                    ->label('Total Harga Item')
                    ->numeric()
                    ->money(currency: "IDR", locale: "id"),
                SpatieMediaLibraryImageColumn::make('attachments')
                    ->collection('request_item_attachments'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultGroup('dailyPaymentRequest.request_number');
    }
}

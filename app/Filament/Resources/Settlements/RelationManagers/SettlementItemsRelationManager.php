<?php

namespace App\Filament\Resources\Settlements\RelationManagers;

use App\Filament\Resources\DailyPaymentRequests\DailyPaymentRequestResource;
use App\Models\RequestItem;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SettlementItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'settlementItems';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('description')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('dailyPaymentRequest.request_number')
                    ->label('Request ID')
                    ->badge()
                    ->url((fn (RequestItem $record): string => DailyPaymentRequestResource::getUrl('view', ['record' => $record->dailyPaymentRequest->id]))),
                TextColumn::make('coa.name')
                    ->label('COA')
                    ->badge(),
                TextColumn::make('description')
                    ->label('Deskripsi Item')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('Qty (Request)')
                    ->numeric(thousandsSeparator: '.'),
                TextColumn::make('act_quantity')
                    ->label('Qty (Aktual)')
                    ->numeric(thousandsSeparator: '.'),
                TextColumn::make('unit_quantity')
                    ->label('Unit Qty'),
                TextColumn::make('amount_per_item')
                    ->label('Harga/item (Request)')
                    ->money(currency: 'IDR', locale: 'id'),
                TextColumn::make('act_amount_per_item')
                    ->label('Harga/item (Aktual)')
                    ->money(currency: 'IDR', locale: 'id'),
                TextColumn::make('variance')
                    ->label('Selisih')
                    ->money(currency: 'IDR', locale: 'id'),
                TextColumn::make('total_amount')
                    ->label('Total Request')
                    ->money(currency: 'IDR', locale: 'id'),
                TextColumn::make('total_act_amount')
                    ->label('Total Aktual')
                    ->money(currency: 'IDR', locale: 'id'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Removed: All item manipulation must go through EditSettlement page
                // to ensure DPR detection and revision workflow
            ])
            ->recordActions([
                // Read-only: Use Edit button in ViewSettlement header to modify items
            ])
            ->toolbarActions([
                // No bulk actions in read-only mode
            ]);
    }
}

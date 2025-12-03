<?php

namespace App\Filament\Resources\Settlements\Widgets;

use App\Enums\RequestItemStatus;
use App\Models\RequestItem;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RequestItemToSettle extends TableWidget
{
    public function getColumns(): int|array|null
    {
        return 1;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => RequestItem::query()->where('status', RequestItemStatus::WaitingSettlement))
            ->columns([
                TextColumn::make('dailyPaymentRequest.id')
                    ->searchable(),
                TextColumn::make('coa.name')
                    ->searchable(),
                TextColumn::make('programActivity.name')
                    ->searchable(),
                TextColumn::make('programActivityItem.id')
                    ->searchable(),
                // TextColumn::make('payment_type')
                //     ->badge()
                //     ->searchable(),
                // TextColumn::make('advance_percentage')
                //     ->numeric()
                //     ->sortable(),
                // TextColumn::make('requestItemType.name')
                //     ->searchable(),
                // TextColumn::make('quantity')
                //     ->numeric()
                //     ->sortable(),
                // TextColumn::make('amount_per_item')
                //     ->numeric()
                //     ->sortable(),
                // TextColumn::make('act_quantity')
                //     ->numeric()
                //     ->sortable(),
                // TextColumn::make('act_amount_per_item')
                //     ->numeric()
                //     ->sortable(),
                // TextColumn::make('tax_method')
                //     ->badge()
                //     ->searchable(),
                // IconColumn::make('self_account')
                //     ->boolean(),
                // TextColumn::make('bank_name')
                //     ->searchable(),
                // TextColumn::make('bank_account')
                //     ->searchable(),
                // TextColumn::make('account_owner')
                //     ->searchable(),
                // TextColumn::make('status')
                //     ->badge()
                //     ->searchable(),
                // IconColumn::make('is_taxed')
                //     ->boolean(),
                // TextColumn::make('tax.name')
                //     ->searchable(),
                // IconColumn::make('is_unplanned')
                //     ->boolean(),
                // TextColumn::make('settlement.id')
                //     ->searchable(),
                // TextColumn::make('receipt_id')
                //     ->searchable(),
                // TextColumn::make('settling_for')
                //     ->numeric()
                //     ->sortable(),
                // TextColumn::make('coa_code')
                //     ->searchable(),
                // TextColumn::make('coa_name')
                //     ->searchable(),
                // TextColumn::make('coa_type')
                //     ->badge()
                //     ->searchable(),
                // TextColumn::make('program_id')
                //     ->numeric()
                //     ->sortable(),
                // TextColumn::make('program_name')
                //     ->searchable(),
                // TextColumn::make('program_code')
                //     ->searchable(),
                // TextColumn::make('program_category_name')
                //     ->searchable(),
                // TextColumn::make('contract_year'),
                // TextColumn::make('tax_type')
                //     ->searchable(),
                // TextColumn::make('tax_rate')
                //     ->numeric()
                //     ->sortable(),
                // TextColumn::make('item_type_name')
                //     ->searchable(),
                // TextColumn::make('realization_date')
                //     ->date()
                //     ->sortable(),
                // TextColumn::make('settled_at')
                //     ->dateTime()
                //     ->sortable(),
                // TextColumn::make('created_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // TextColumn::make('deleted_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}

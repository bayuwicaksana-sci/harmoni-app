<?php

namespace App\Filament\Resources\Settlements\Widgets;

use App\Enums\RequestItemStatus;
use App\Enums\RequestPaymentType;
use App\Filament\Resources\DailyPaymentRequests\DailyPaymentRequestResource;
use App\Models\RequestItem;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RequestItemToSettle extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                if (Auth::user()->employee->jobTitle->code === 'CEO' || Auth::user()->employee->jobTitle->department->code === 'FIN') {
                    return RequestItem::query()->where('status', RequestItemStatus::WaitingSettlement);
                } else {
                    return RequestItem::where('status', RequestItemStatus::WaitingSettlement)->whereHas('dailyPaymentRequest', function (Builder $query) {
                        $query->where('requester_id', Auth::user()->employee->id);
                    });
                }
            })
            ->heading('Request Item menunggu Settlement')
            ->searchable(false)
            ->columns([
                TextColumn::make('dailyPaymentRequest.request_number')
                    ->label('Request ID')
                    ->badge()
                    ->url(fn ($record) => DailyPaymentRequestResource::getUrl('view', ['record' => $record->dailyPaymentRequest->id])),
                TextColumn::make('dailyPaymentRequest.requester.user.name')
                    ->label('Requester'),
                TextColumn::make('description')
                    ->label('Deskripsi Item'),
                TextColumn::make('request_quantity')
                    ->label('Qty')
                    ->getStateUsing(fn ($record) => ($record->payment_type === RequestPaymentType::Reimburse ? (string) number_format($record->act_quantity, 0, ',', '.') : (string) number_format($record->quantity, 0, ',', '.')).' '.$record->unit_quantity),
                TextColumn::make('request_amount_per_item')
                    ->label('Harga/item')
                    ->getStateUsing(fn ($record) => $record->payment_type === RequestPaymentType::Reimburse ? $record->act_amount_per_item : $record->amount_per_item)
                    ->money(currency: 'IDR', locale: 'id'),
                TextColumn::make('request_total_amount')
                    ->label('Total Nominal Request')
                    ->getStateUsing(fn ($record) => $record->total_amount)
                    ->money(currency: 'IDR', locale: 'id'),
                TextColumn::make('due_date')
                    ->label('Tenggat Waktu Realisasi')
                    ->date('d M Y', 'Asia/Jakarta'),
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

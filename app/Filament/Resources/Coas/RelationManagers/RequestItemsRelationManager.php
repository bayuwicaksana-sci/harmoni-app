<?php

namespace App\Filament\Resources\Coas\RelationManagers;

use App\Enums\RequestPaymentType;
use App\Filament\Resources\DailyPaymentRequests\DailyPaymentRequestResource;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RequestItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'requestItems';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('description')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('description'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->paid()->whereNot('payment_type', RequestPaymentType::Offset))
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('dailyPaymentRequest.request_number')
                    ->label('Request ID')
                    ->badge()
                    ->url(fn ($record) => DailyPaymentRequestResource::getUrl('view', ['record' => $record->dailyPaymentRequest->id])),
                TextColumn::make('description')
                    ->label('Deskripsi Item')
                    ->searchable(),
                TextColumn::make('act_quantity')
                    ->label('Qty')
                    ->formatStateUsing(fn ($state, $record) => (string) (int) $state." {$record->unit_quantity}"),
                TextColumn::make('act_amount_per_item')
                    ->label('Harga/item')
                    ->money(currency: 'IDR', locale: 'id'),
                TextColumn::make('total_act_amount')
                    ->label('Nilai Transaksi')
                    ->money(currency: 'IDR', locale: 'id'),
            ])
            ->filters([
                // TrashedFilter::make(),
            ])
            ->headerActions([
                // CreateAction::make(),
                // AssociateAction::make(),
            ])
            ->recordActions([
                // ViewAction::make(),
                // // EditAction::make(),
                // // DissociateAction::make(),
                // // DeleteAction::make(),
                // // ForceDeleteAction::make(),
                // // RestoreAction::make(),
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DissociateBulkAction::make(),
                //     DeleteBulkAction::make(),
                //     ForceDeleteBulkAction::make(),
                //     RestoreBulkAction::make(),
                // ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]));
    }
}

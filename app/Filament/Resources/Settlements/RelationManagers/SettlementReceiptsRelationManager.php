<?php

namespace App\Filament\Resources\Settlements\RelationManagers;

use App\Filament\Resources\Settlements\Resources\SettlementReceipts\SettlementReceiptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class SettlementReceiptsRelationManager extends RelationManager
{
    protected static string $relationship = 'settlementReceipts';

    protected static ?string $relatedResource = SettlementReceiptResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}

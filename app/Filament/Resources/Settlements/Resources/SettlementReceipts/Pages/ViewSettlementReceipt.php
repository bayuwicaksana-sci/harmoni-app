<?php

namespace App\Filament\Resources\Settlements\Resources\SettlementReceipts\Pages;

use App\Filament\Resources\Settlements\Resources\SettlementReceipts\SettlementReceiptResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSettlementReceipt extends ViewRecord
{
    protected static string $resource = SettlementReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

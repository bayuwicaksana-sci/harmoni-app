<?php

namespace App\Filament\Resources\Settlements\Resources\SettlementReceipts\Pages;

use App\Filament\Resources\Settlements\Resources\SettlementReceipts\SettlementReceiptResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSettlementReceipt extends EditRecord
{
    protected static string $resource = SettlementReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

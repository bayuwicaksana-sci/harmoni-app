<?php

namespace App\Filament\Resources\Settlements\Resources\SettlementReceipts\Pages;

use App\Filament\Resources\Settlements\Resources\SettlementReceipts\SettlementReceiptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSettlementReceipt extends CreateRecord
{
    protected static string $resource = SettlementReceiptResource::class;
}

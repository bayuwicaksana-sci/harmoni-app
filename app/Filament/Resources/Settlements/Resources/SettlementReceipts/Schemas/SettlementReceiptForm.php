<?php

namespace App\Filament\Resources\Settlements\Resources\SettlementReceipts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Schema;

class SettlementReceiptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DateTimePicker::make('realization_date')
                    ->required(),
            ]);
    }
}

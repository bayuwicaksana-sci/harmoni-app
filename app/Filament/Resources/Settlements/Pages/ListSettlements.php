<?php

namespace App\Filament\Resources\Settlements\Pages;

use App\Filament\Resources\Settlements\SettlementResource;
use App\Filament\Resources\Settlements\Widgets\RequestItemToSettle;
use App\Filament\Resources\Settlements\Widgets\SettlementOverview;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSettlements extends ListRecords
{
    protected static string $resource = SettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SettlementOverview::class,
            RequestItemToSettle::class,
        ];
    }
}

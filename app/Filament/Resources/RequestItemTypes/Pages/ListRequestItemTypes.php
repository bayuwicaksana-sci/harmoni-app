<?php

namespace App\Filament\Resources\RequestItemTypes\Pages;

use App\Filament\Resources\RequestItemTypes\RequestItemTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRequestItemTypes extends ListRecords
{
    protected static string $resource = RequestItemTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

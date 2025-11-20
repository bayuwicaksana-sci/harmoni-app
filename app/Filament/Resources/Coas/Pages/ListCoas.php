<?php

namespace App\Filament\Resources\Coas\Pages;

use App\Filament\Resources\Coas\CoaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCoas extends ListRecords
{
    protected static string $resource = CoaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

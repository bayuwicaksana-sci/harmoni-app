<?php

namespace App\Filament\Resources\Coas\Pages;

use App\Filament\Resources\Coas\CoaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCoa extends ViewRecord
{
    protected static string $resource = CoaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

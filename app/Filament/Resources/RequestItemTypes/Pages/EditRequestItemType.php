<?php

namespace App\Filament\Resources\RequestItemTypes\Pages;

use App\Filament\Resources\RequestItemTypes\RequestItemTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRequestItemType extends EditRecord
{
    protected static string $resource = RequestItemTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

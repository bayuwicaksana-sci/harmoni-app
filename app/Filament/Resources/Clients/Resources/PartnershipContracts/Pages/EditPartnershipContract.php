<?php

namespace App\Filament\Resources\Clients\Resources\PartnershipContracts\Pages;

use App\Filament\Resources\Clients\Resources\PartnershipContracts\PartnershipContractResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPartnershipContract extends EditRecord
{
    protected static string $resource = PartnershipContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

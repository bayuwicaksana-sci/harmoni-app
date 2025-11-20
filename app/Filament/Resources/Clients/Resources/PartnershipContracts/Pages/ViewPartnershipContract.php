<?php

namespace App\Filament\Resources\Clients\Resources\PartnershipContracts\Pages;

use App\Filament\Resources\Clients\Resources\PartnershipContracts\PartnershipContractResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPartnershipContract extends ViewRecord
{
    protected static string $resource = PartnershipContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

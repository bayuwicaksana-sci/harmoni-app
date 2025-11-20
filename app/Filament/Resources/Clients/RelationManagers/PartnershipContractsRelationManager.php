<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Clients\Resources\PartnershipContracts\PartnershipContractResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class PartnershipContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'partnershipContracts';

    protected static ?string $relatedResource = PartnershipContractResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Clients\Resources\PartnershipContracts;

use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Clients\Resources\PartnershipContracts\Pages\CreatePartnershipContract;
use App\Filament\Resources\Clients\Resources\PartnershipContracts\Pages\EditPartnershipContract;
use App\Filament\Resources\Clients\Resources\PartnershipContracts\Pages\ViewPartnershipContract;
use App\Filament\Resources\Clients\Resources\PartnershipContracts\Schemas\PartnershipContractForm;
use App\Filament\Resources\Clients\Resources\PartnershipContracts\Schemas\PartnershipContractInfolist;
use App\Filament\Resources\Clients\Resources\PartnershipContracts\Tables\PartnershipContractsTable;
use App\Models\PartnershipContract;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PartnershipContractResource extends Resource
{
    protected static ?string $model = PartnershipContract::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $parentResource = ClientResource::class;

    protected static ?string $recordTitleAttribute = 'contract_number';

    public static function form(Schema $schema): Schema
    {
        return PartnershipContractForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PartnershipContractInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PartnershipContractsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'create' => CreatePartnershipContract::route('/create'),
            'view' => ViewPartnershipContract::route('/{record}'),
            'edit' => EditPartnershipContract::route('/{record}/edit'),
        ];
    }
}

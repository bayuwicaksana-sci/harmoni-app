<?php

namespace App\Filament\Resources\RequestItemTypes;

use App\Filament\Resources\RequestItemTypes\Pages\CreateRequestItemType;
use App\Filament\Resources\RequestItemTypes\Pages\EditRequestItemType;
use App\Filament\Resources\RequestItemTypes\Pages\ListRequestItemTypes;
use App\Filament\Resources\RequestItemTypes\Schemas\RequestItemTypeForm;
use App\Filament\Resources\RequestItemTypes\Tables\RequestItemTypesTable;
use App\Models\RequestItemType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RequestItemTypeResource extends Resource
{
    protected static ?string $model = RequestItemType::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $recordTitleAttribute = 'name';
    protected static string | \UnitEnum | null $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Request Item Types';

    public static function form(Schema $schema): Schema
    {
        return RequestItemTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RequestItemTypesTable::configure($table);
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
            'index' => ListRequestItemTypes::route('/'),
            'create' => CreateRequestItemType::route('/create'),
            'edit' => EditRequestItemType::route('/{record}/edit'),
        ];
    }
}

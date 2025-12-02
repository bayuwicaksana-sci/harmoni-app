<?php

namespace App\Filament\Resources\Settlements;

use App\Filament\Resources\Settlements\Pages\CreateSettlement;
use App\Filament\Resources\Settlements\Pages\EditSettlement;
use App\Filament\Resources\Settlements\Pages\ListSettlements;
use App\Filament\Resources\Settlements\Pages\ViewSettlement;
use App\Filament\Resources\Settlements\RelationManagers\SettlementReceiptsRelationManager;
use App\Filament\Resources\Settlements\Schemas\SettlementForm;
use App\Filament\Resources\Settlements\Schemas\SettlementInfolist;
use App\Filament\Resources\Settlements\Tables\SettlementsTable;
use App\Models\Settlement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SettlementResource extends Resource
{
    protected static ?string $model = Settlement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Transactions';

    protected static ?string $recordTitleAttribute = 'settlement_number';

    public static function form(Schema $schema): Schema
    {
        return SettlementForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SettlementInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SettlementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SettlementReceiptsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSettlements::route('/'),
            'create' => CreateSettlement::route('/create'),
            'view' => ViewSettlement::route('/{record}'),
            'edit' => EditSettlement::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Settlements;

use App\Enums\RequestItemStatus;
use App\Filament\Resources\Settlements\Pages\CreateSettlement;
use App\Filament\Resources\Settlements\Pages\EditSettlement;
use App\Filament\Resources\Settlements\Pages\ListSettlements;
use App\Filament\Resources\Settlements\Schemas\SettlementForm;
use App\Filament\Resources\Settlements\Tables\SettlementsTable;
use App\Models\RequestItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class SettlementResource extends Resource
{
    protected static ?string $model = RequestItem::class;
    protected static ?string $modelLabel = 'Settlement';
    protected static ?string $pluralModelLabel = 'Settlements';
    protected static ?string $navigationLabel = 'Settlement';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static string | UnitEnum | null $navigationGroup = 'Transactions';
    protected static ?string $slug = 'settlements';

    public static function form(Schema $schema): Schema
    {
        return SettlementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SettlementsTable::configure($table);
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
            'index' => ListSettlements::route('/'),
            // 'create' => CreateSettlement::route('/create'),
            'edit' => EditSettlement::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('dailyPaymentRequest', function (Builder $query) {
            $query->whereRequesterId(Auth::user()->employee->id);
        })->where('request_items.status', RequestItemStatus::WaitingSettlement);
    }
}

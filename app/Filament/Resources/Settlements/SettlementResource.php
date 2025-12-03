<?php

namespace App\Filament\Resources\Settlements;

use App\Enums\RequestItemStatus;
use App\Filament\Resources\Settlements\Pages\CreateSettlement;
use App\Filament\Resources\Settlements\Pages\EditSettlement;
use App\Filament\Resources\Settlements\Pages\ListSettlements;
use App\Filament\Resources\Settlements\Pages\ViewSettlement;
use App\Filament\Resources\Settlements\RelationManagers\SettlementReceiptsRelationManager;
use App\Filament\Resources\Settlements\Schemas\SettlementForm;
use App\Filament\Resources\Settlements\Schemas\SettlementInfolist;
use App\Filament\Resources\Settlements\Tables\SettlementsTable;
use App\Filament\Resources\Settlements\Widgets\RequestItemToSettle;
use App\Filament\Resources\Settlements\Widgets\SettlementOverview;
use App\Models\RequestItem;
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

    public static function getNavigationBadge(): ?string
    {
        // $count = RequestItem::query()->where('status', RequestItemStatus::WaitingSettlement)->whereNot('requester_id', Auth::user()?->employee?->id)->whereHas('approvalHistories', function ($q) {
        //     // Only show requests where this employee is the CURRENT pending approver
        //     $q->where('approver_id', Auth::user()?->employee?->id)
        //         ->where('action', 'pending')
        //         ->whereRaw('sequence = (
        //               SELECT MIN(sequence)
        //               FROM approval_histories ah2
        //               WHERE ah2.daily_payment_request_id = approval_histories.daily_payment_request_id
        //               AND ah2.action = "pending"
        //           )');
        // })->count();
        $count = RequestItem::query()->where('status', RequestItemStatus::WaitingSettlement)->count();

        return $count > 0 ? $count : null;
    }

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
            // SettlementReceiptsRelationManager::class,
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

    public static function getWidgets(): array
    {
        return [
            SettlementOverview::class,
            RequestItemToSettle::class,
        ];
    }
}

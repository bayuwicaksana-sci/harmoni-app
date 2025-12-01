<?php

namespace App\Filament\Resources\Settlements\Resources\SettlementReceipts;

use App\Filament\Resources\Settlements\Resources\SettlementReceipts\Pages\CreateSettlementReceipt;
use App\Filament\Resources\Settlements\Resources\SettlementReceipts\Pages\EditSettlementReceipt;
use App\Filament\Resources\Settlements\Resources\SettlementReceipts\Pages\ViewSettlementReceipt;
use App\Filament\Resources\Settlements\Resources\SettlementReceipts\Schemas\SettlementReceiptForm;
use App\Filament\Resources\Settlements\Resources\SettlementReceipts\Schemas\SettlementReceiptInfolist;
use App\Filament\Resources\Settlements\Resources\SettlementReceipts\Tables\SettlementReceiptsTable;
use App\Filament\Resources\Settlements\SettlementResource;
use App\Models\SettlementReceipt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SettlementReceiptResource extends Resource
{
    protected static ?string $model = SettlementReceipt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $parentResource = SettlementResource::class;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return SettlementReceiptForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SettlementReceiptInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SettlementReceiptsTable::configure($table);
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
            'create' => CreateSettlementReceipt::route('/create'),
            'view' => ViewSettlementReceipt::route('/{record}'),
            'edit' => EditSettlementReceipt::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Coas;

use App\Filament\Resources\Coas\Pages\CreateCoa;
use App\Filament\Resources\Coas\Pages\EditCoa;
use App\Filament\Resources\Coas\Pages\ListCoas;
use App\Filament\Resources\Coas\Pages\ViewCoa;
use App\Filament\Resources\Coas\Schemas\CoaForm;
use App\Filament\Resources\Coas\Schemas\CoaInfolist;
use App\Filament\Resources\Coas\Tables\CoasTable;
use App\Models\Coa;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CoaResource extends Resource
{
    protected static ?string $model = Coa::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedChartBar;
    protected static string | \UnitEnum | null $navigationGroup = 'Program Driver';
    protected static ?string $navigationLabel = 'Chart of Accounts';
    protected static ?string $pluralModelLabel = 'Chart of Accounts';
    protected static ?string $modelLabel = 'COA';

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return CoaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CoaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CoasTable::configure($table);
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
            'index' => ListCoas::route('/'),
            'create' => CreateCoa::route('/create'),
            'view' => ViewCoa::route('/{record}'),
            'edit' => EditCoa::route('/{record}/edit'),
        ];
    }
}

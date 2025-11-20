<?php

namespace App\Filament\Resources\JobTitles;

use App\Filament\Resources\JobTitles\Pages\CreateJobTitle;
use App\Filament\Resources\JobTitles\Pages\EditJobTitle;
use App\Filament\Resources\JobTitles\Pages\ListJobTitles;
use App\Filament\Resources\JobTitles\Schemas\JobTitleForm;
use App\Filament\Resources\JobTitles\Tables\JobTitlesTable;
use App\Models\JobTitle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class JobTitleResource extends Resource
{
    protected static ?string $model = JobTitle::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedIdentification;
    protected static string | \UnitEnum | null $navigationGroup = 'Organization';

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return JobTitleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JobTitlesTable::configure($table);
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
            'index' => ListJobTitles::route('/'),
            'create' => CreateJobTitle::route('/create'),
            'edit' => EditJobTitle::route('/{record}/edit'),
        ];
    }
}

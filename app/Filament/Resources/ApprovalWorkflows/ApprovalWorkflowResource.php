<?php

namespace App\Filament\Resources\ApprovalWorkflows;

use App\Filament\Resources\ApprovalWorkflows\RelationManagers\ApprovalRulesRelationManager;
use App\Filament\Resources\ApprovalWorkflows\Pages\CreateApprovalWorkflow;
use App\Filament\Resources\ApprovalWorkflows\Pages\EditApprovalWorkflow;
use App\Filament\Resources\ApprovalWorkflows\Pages\ListApprovalWorkflows;
use App\Filament\Resources\ApprovalWorkflows\Pages\ViewApprovalWorkflow;
use App\Filament\Resources\ApprovalWorkflows\Schemas\ApprovalWorkflowForm;
use App\Filament\Resources\ApprovalWorkflows\Schemas\ApprovalWorkflowInfolist;
use App\Filament\Resources\ApprovalWorkflows\Tables\ApprovalWorkflowsTable;
use App\Models\ApprovalWorkflow;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ApprovalWorkflowResource extends Resource
{
    protected static ?string $model = ApprovalWorkflow::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedArrowPath;
    protected static string | \UnitEnum | null $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Approval Workflows';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ApprovalWorkflowForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ApprovalWorkflowInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApprovalWorkflowsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ApprovalRulesRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApprovalWorkflows::route('/'),
            'create' => CreateApprovalWorkflow::route('/create'),
            'view' => ViewApprovalWorkflow::route('/{record}'),
            'edit' => EditApprovalWorkflow::route('/{record}/edit'),
        ];
    }
}

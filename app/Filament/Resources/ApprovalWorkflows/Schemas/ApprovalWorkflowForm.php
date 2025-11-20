<?php

namespace App\Filament\Resources\ApprovalWorkflows\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ApprovalWorkflowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Workflow Information')
                    ->description('Configure approval workflow for payment requests')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('e.g., Standard Payment Request Approval')
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Only one workflow should be active at a time'),
                    ]),

                Section::make('Approval Rules')
                    ->description('Rules will be managed after creating the workflow')
                    ->schema([
                        TextEntry::make('rules_placeholder')
                            ->label('')
                            ->state('After creating this workflow, you can add approval rules using the "Approval Rules" tab.')
                            ->hiddenOn('edit'),

                        TextEntry::make('rules_info')
                            ->label('')
                            ->state(fn($record) => "This workflow has {$record->approvalRules()->count()} rules configured.")
                            ->hiddenOn('create'),
                    ])
                    ->collapsible(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\ApprovalWorkflows\RelationManagers;

use App\Enums\ApproverType;
use App\Enums\DPRApprovalRuleCondition;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\RawJs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApprovalRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'approvalRules';

    protected static ?string $title = 'Approval Rules';

    protected static ?string $recordTitleAttribute = 'sequence';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rule Configuration')
                    ->description('Define the approval rule for this workflow')
                    ->schema([
                        TextInput::make('sequence')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(99)
                            ->helperText('Order in which this approval is required (e.g., 1 = first, 2 = second)')
                            ->unique(
                                table: 'approval_rules',
                                column: 'sequence',
                                ignoreRecord: true,
                                modifyRuleUsing: function ($rule) {
                                    return $rule->where('approval_workflow_id', $this->getOwnerRecord()->id);
                                }
                            ),

                        Select::make('condition_type')
                            ->required()
                            ->options([
                                DPRApprovalRuleCondition::Always->value => 'Always Required',
                                DPRApprovalRuleCondition::Amount->value => 'Required if Amount Threshold Met',
                            ])
                            ->live()
                            ->helperText('When should this approval be required?'),

                        TextInput::make('condition_value')
                            ->label('Amount Threshold')
                            ->numeric()
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->required(fn(Get $get) => $get('condition_type') === 'amount')
                            ->hidden(fn(Get $get) => $get('condition_type') !== 'amount')
                            ->helperText('Approval required if request amount is greater than or equal to this value'),
                    ])
                    ->columns(3),

                Section::make('Approver Configuration')
                    ->description('Who should approve at this stage?')
                    ->schema([
                        Select::make('approver_type')
                            ->required()
                            ->options([
                                ApproverType::Supervisor->value => 'Requester\'s Direct Supervisor',
                                ApproverType::JobLevel->value => 'Any Employee with Specific Job Level',
                                ApproverType::JobTitle->value => 'Employee with Specific Job Title',
                            ])
                            ->live()
                            ->helperText('Select how to determine the approver'),

                        Select::make('approver_job_level_id')
                            ->label('Required Job Level')
                            ->relationship('approverJobLevel', 'name')
                            ->searchable()
                            ->preload()
                            // ->options([
                            //     1 => 'Level 1 - Support/Assistant',
                            //     2 => 'Level 2 - Officer/Staff/Specialist',
                            //     3 => 'Level 3 - Senior/Coordinator/Lead',
                            //     4 => 'Level 4 - Manager/Head of Unit',
                            //     5 => 'Level 5 - Director/Head of Department/Chief',
                            // ])
                            ->required(fn(Get $get) => $get('approver_type') === ApproverType::JobLevel)
                            ->hidden(fn(Get $get) => $get('approver_type') !== ApproverType::JobLevel)
                            ->helperText('Any employee with this job level can approve'),

                        Select::make('approver_job_title_id')
                            ->label('Required Job Title')
                            ->relationship('approverJobTitle', 'name')
                            ->searchable()
                            ->preload()
                            ->required(fn(Get $get) => $get('approver_type') === ApproverType::JobTitle)
                            ->hidden(fn(Get $get) => $get('approver_type') !== ApproverType::JobTitle)
                            ->helperText('Employee with this specific job title must approve'),

                        TextEntry::make('supervisor_info')
                            ->label('')
                            ->state('The system will automatically route to the requester\'s direct supervisor.')
                            ->hidden(fn(Get $get) => $get('approver_type') !== ApproverType::Supervisor),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sequence')
            ->columns([
                TextColumn::make('sequence')
                    ->label('Seq')
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->size('lg')
                    ->weight('bold'),

                TextColumn::make('condition')
                    ->label('Condition')
                    ->getStateUsing(function ($record) {
                        if ($record->condition_type === DPRApprovalRuleCondition::Always) {
                            return 'Always Required';
                        }
                        return 'If Amount >= Rp ' . number_format($record->condition_value, 0, ',', '.');
                    })
                    ->wrap(),

                TextColumn::make('approver_type')
                    ->label('Approver Type')
                    ->badge(),

                TextColumn::make('approver_detail')
                    ->label('Approver Detail')
                    ->getStateUsing(function ($record) {
                        switch ($record->approver_type) {
                            case ApproverType::Supervisor:
                                return 'Direct Supervisor';
                            case ApproverType::JobLevel:
                                return 'Level ' . $record->approverJobLevel->level;
                            case ApproverType::JobTitle:
                                return $record->approverJobTitle?->title ?? 'N/A';
                            default:
                                return 'N/A';
                        }
                    })
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()->modalWidth(Width::ThreeExtraLarge),
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sequence')
            ->defaultSort('sequence');
    }
}

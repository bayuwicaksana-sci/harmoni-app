<?php

namespace App\Filament\Resources\ApprovalWorkflows\Tables;

use Illuminate\Support\HtmlString;
use App\Models\ApprovalWorkflow;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ApprovalWorkflowsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn($record) => $record->approvalRules()->count() . ' rules configured'),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedXCircle)
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                TextColumn::make('approval_rules_count')
                    ->counts('approvalRules')
                    ->label('Rules')
                    ->badge()
                    ->color('info'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only')
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('test_workflow')
                    ->label('Test')
                    ->icon(Heroicon::OutlinedBeaker)
                    ->color('warning')
                    ->schema([
                        TextInput::make('test_amount')
                            ->label('Request Amount (Rp)')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->placeholder('5000000')
                            ->helperText('Enter an amount to see which approvers will be required'),
                    ])
                    ->action(function (array $data, ApprovalWorkflow $record): void {
                        // This will be handled in the modal
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(function (array $data, ApprovalWorkflow $record) {
                        $amount = $data['test_amount'] ?? 0;
                        $rules = $record->getApplicableRules($amount);

                        $html = '<div class="space-y-4">';
                        $html .= '<div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">';
                        $html .= '<h3 class="text-lg font-bold mb-2">Test Amount: Rp ' . number_format($amount, 0, ',', '.') . '</h3>';
                        $html .= '<p class="text-sm text-gray-600 dark:text-gray-400">Applicable Approval Rules:</p>';
                        $html .= '</div>';

                        if ($rules->isEmpty()) {
                            $html .= '<div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">';
                            $html .= '<p class="text-yellow-800 dark:text-yellow-200">⚠️ No rules match this amount</p>';
                            $html .= '</div>';
                        } else {
                            foreach ($rules as $rule) {
                                $html .= '<div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border-l-4 border-blue-500">';
                                $html .= '<div class="flex items-start gap-3">';
                                $html .= '<div class="flex-shrink-0 w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold">' . $rule->sequence . '</div>';
                                $html .= '<div class="flex-1">';
                                $html .= '<h4 class="font-semibold text-gray-900 dark:text-gray-100">Sequence ' . $rule->sequence . '</h4>';
                                $html .= '<p class="text-sm text-gray-600 dark:text-gray-400 mt-1">';
                                $html .= '<strong>Condition:</strong> ' . ucfirst($rule->condition_type);
                                if ($rule->condition_value) {
                                    $html .= ' (>= Rp ' . number_format($rule->condition_value, 0, ',', '.') . ')';
                                }
                                $html .= '</p>';
                                $html .= '<p class="text-sm text-gray-600 dark:text-gray-400">';
                                $html .= '<strong>Approver:</strong> ' . $rule->getApproverDescription();
                                $html .= '</p>';
                                $html .= '</div>';
                                $html .= '</div>';
                                $html .= '</div>';
                            }
                        }

                        $html .= '</div>';

                        return new HtmlString($html);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

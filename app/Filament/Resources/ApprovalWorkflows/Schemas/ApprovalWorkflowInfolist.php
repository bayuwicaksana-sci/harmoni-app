<?php

namespace App\Filament\Resources\ApprovalWorkflows\Schemas;

use App\Enums\DPRApprovalRuleCondition;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ApprovalWorkflowInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Workflow Details')
                    ->schema([
                        TextEntry::make('name')
                            ->size('lg')
                            ->weight('bold'),

                        IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean()
                            ->trueIcon(Heroicon::OutlinedCheckCircle)
                            ->falseIcon(Heroicon::OutlinedXCircle)
                            ->trueColor('success')
                            ->falseColor('danger'),

                        TextEntry::make('approvalRules_count')
                            ->label('Total Rules')
                            ->getStateUsing(fn($record) => $record->approvalRules()->count())
                            ->badge()
                            ->color('info'),
                    ])
                    ->columns(3),

                Section::make('Approval Rules Overview')
                    ->schema([
                        RepeatableEntry::make('approvalRules')
                            ->label('')
                            ->schema([
                                TextEntry::make('sequence')
                                    ->badge()
                                    ->color('primary')
                                    ->label('Seq'),

                                TextEntry::make('condition_type')
                                    ->label('Condition')
                                    ->formatStateUsing(function ($state, $record) {
                                        if ($state === DPRApprovalRuleCondition::Always) {
                                            return 'Always Required';
                                        }
                                        return 'Amount >= Rp ' . number_format($record->condition_value, 0, ',', '.');
                                    }),

                                TextEntry::make('approver_description')
                                    ->label('Approver')
                                    ->getStateUsing(fn($record) => $record->getApproverDescription())
                                    ->badge()
                                    ->color('success'),
                            ])
                            ->columns(3)
                            ->grid(1),
                    ]),

                Section::make('Timestamps')
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}

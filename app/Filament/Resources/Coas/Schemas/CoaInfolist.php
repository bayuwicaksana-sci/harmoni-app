<?php

namespace App\Filament\Resources\Coas\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class CoaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.components.per-coa-usage')
                    ->columnSpanFull()
                    ->viewData(function ($record) {
                        $totalCoaPlannedBudget = $record->planned_budget;
                        $totalSpent = $record->actual_spent;
                        $variance = $totalCoaPlannedBudget - $totalSpent;

                        $percentage = $totalCoaPlannedBudget > 0 ? min(100, max(0, (abs($variance) / $totalCoaPlannedBudget) * 100)) : 0;
                        $progressPercentage = $totalCoaPlannedBudget > 0 ? min(100, max(0, ($totalSpent / $totalCoaPlannedBudget) * 100)) : 0;
                        $isNegative = $variance < 0;

                        // Determine color based on variance
                        $colorClass = $isNegative ? 'bg-red-500' : 'bg-green-500';
                        $textColorClass = $isNegative ? 'text-red-600' : 'text-green-600';

                        return [
                            'totalCoaPlannedBudget' => $totalCoaPlannedBudget,
                            'totalSpent' => $totalSpent,
                            'variance' => $variance,
                            'percentage' => $percentage,
                            'progressPercentage' => $progressPercentage,
                            'isNegative' => $isNegative,
                            'colorClass' => $colorClass,
                            'textColorClass' => $textColorClass,
                        ];
                    }),
                Section::make('Detail COA')
                    ->schema([
                        TextEntry::make('code')
                            ->label('Kode COA')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('name')->label('Nama COA'),
                        TextEntry::make('type')
                            ->label('Tipe COA')
                            ->badge(),
                        TextEntry::make('program.name')
                            ->label('Program')
                            ->placeholder('N/A'),
                        IconEntry::make('is_active')
                            ->label('Status Aktif')
                            ->boolean(),
                    ])
                    ->columns(3),

                Section::make('Usage Statistics')
                    ->schema([
                        // TextEntry::make('total_allocated')
                        //     ->label('Total Allocated/Used')
                        //     ->money('IDR')
                        //     ->getStateUsing(fn($record) => $record->getTotalSpent()),

                        TextEntry::make('transaction_count')
                            ->label('Number of Transactions')
                            ->getStateUsing(fn ($record) => $record->requestItems()->count()),
                    ])
                    ->columns(2),
            ]);
    }
}

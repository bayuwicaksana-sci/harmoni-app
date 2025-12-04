<?php

namespace App\Filament\Resources\Coas\Widgets;

use App\Enums\COAType;
use App\Models\Coa;
use App\Models\RequestItem;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class CoaUsageOverview extends StatsOverviewWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        return [
            Stat::make('COA Usage Overview', function () {
                // $totalRequested = RequestItem::disbursed()->get()->sum('total_amount');
                $totalCoaPlannedBudget = Coa::find($this->record->id)->programActivityItems()->get()->sum('total_item_planned_budget');
                $totalSpent = Coa::find($this->record->id)->requestItems()->paid()->get()->sum('total_act_amount');
                $variance = $totalCoaPlannedBudget - $totalSpent;

                $percentage = $totalCoaPlannedBudget > 0 ? min(100, max(0, (abs($variance) / $totalCoaPlannedBudget) * 100)) : 0;
                $progressPercentage = $totalCoaPlannedBudget > 0 ? min(100, max(0, ($totalSpent / $totalCoaPlannedBudget) * 100)) : 0;
                $isNegative = $variance < 0;

                // Determine color based on variance
                $colorClass = $isNegative ? 'bg-red-500' : 'bg-green-500';
                $textColorClass = $isNegative ? 'text-red-600' : 'text-green-600';

                return new HtmlString('<div class="flex flex-col gap-3 w-full">
                    <div class="flex justify-between flex-wrap flex-row-reverse gap-2 text-sm">
                        <div class="flex flex-col">
                            <span class="text-gray-500 text-xs">Total Planned Budget</span>
                            <span class="font-semibold">Rp '.number_format($totalCoaPlannedBudget, 2, ',', '.').'</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-gray-500 text-xs">Total Spent</span>
                            <span class="font-semibold">Rp '.number_format($totalSpent, 2, ',', '.').'</span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                        <div class="'.$colorClass.' h-3 rounded-full transition-all duration-300" style="width: '.$progressPercentage.'%"></div>
                    </div>
                    <div class="flex justify-between items-center">
                        <div class="text-xs text-gray-500">'.($isNegative ? 'Over Budget' : 'Under Budget').'</div>
                        <div class="'.$textColorClass.' font-semibold text-sm">'.($isNegative ? '-' : '+').round($percentage, 2).' %</div>
                    </div>
                    <div class="text-right">
                        <div class="'.$textColorClass.' font-semibold">Rp '.number_format(abs($variance), 2, ',', '.').'</div>
                    </div>
                </div>');
            })
                ->hidden($this->record->type !== COAType::Program),
        ];
    }

    public function getColumns(): int|array|null
    {
        return 1;
    }
}

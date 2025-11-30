<?php

namespace App\Filament\Resources\Settlements\Widgets;

use App\Enums\RequestPaymentType;
use App\Models\RequestItem;
use App\Models\Settlement;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class SettlementOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Request vs Settlement Overview', function () {
                // $totalRequested = RequestItem::disbursed()->get()->sum('total_amount');
                $totalRequested = RequestItem::disbursed()->get()->sum('total_amount') + RequestItem::disbursed()->where('payment_type', RequestPaymentType::Reimburse)->get()->sum('total_act_amount');
                $totalSettled = RequestItem::disbursed()->get()->sum('total_act_amount');
                $variance = $totalRequested - $totalSettled;

                $percentage = $totalRequested > 0 ? min(100, max(0, (abs($variance) / $totalRequested) * 100)) : 0;
                $progressPercentage = $totalRequested > 0 ? min(100, max(0, ($totalSettled / $totalRequested) * 100)) : 0;
                $isNegative = $variance < 0;

                // Determine color based on variance
                $colorClass = $isNegative ? 'bg-red-500' : 'bg-green-500';
                $textColorClass = $isNegative ? 'text-red-600' : 'text-green-600';

                return new HtmlString('<div class="flex flex-col gap-3 w-full">
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div class="flex flex-col">
                            <span class="text-gray-500 text-xs">Total Requested</span>
                            <span class="font-semibold">Rp '.number_format($totalRequested, 2, ',', '.').'</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-gray-500 text-xs">Total Settled</span>
                            <span class="font-semibold">Rp '.number_format($totalSettled, 2, ',', '.').'</span>
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
            }),
            Stat::make('Jumlah Settlement', Settlement::count())
                ->extraAttributes([
                    'class' => 'h-fit',
                ]),
            Stat::make('Nilai Pajak', function () {
                $totalPPh21 = RequestItem::where('tax_id', 1)->get()->sum('tax_amount');
                $totalPPh23 = RequestItem::where('tax_id', 2)->get()->sum('tax_amount');

                return new HtmlString('<div class="flex flex-col gap-5">
                    <div class="flex-col gap-1">
                        <div class="text-base">PPh 21</div>
                        <div>Rp '.number_format($totalPPh21, 2, ',', '.').'</div>
                    </div>
                    <div class="flex-col gap-1">
                        <div class="text-base">PPh 23</div>
                        <div>Rp '.number_format($totalPPh23, 2, ',', '.').'</div>
                    </div>
                </div>');
            })
                ->extraAttributes([
                    'class' => 'h-fit',
                ]),
        ];
    }

    public function getColumns(): int|array|null
    {
        return 3;
    }
}

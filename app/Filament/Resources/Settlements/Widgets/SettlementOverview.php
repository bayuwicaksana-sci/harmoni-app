<?php

namespace App\Filament\Resources\Settlements\Widgets;

use App\Enums\RequestPaymentType;
use App\Models\Employee;
use App\Models\RequestItem;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\View;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class SettlementOverview extends Widget implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'volt-livewire::filament.resources.settlements.widgets.settlement-overview';

    public function tabComponent(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Settlement overview')
                    ->tabs([
                        Tab::make('Settlement Overview')
                            ->schema([
                                View::make('livewire.filament.resources.settlements.widgets.settlement-overview-content')
                                    ->viewData(function () {
                                        $totalRequested = RequestItem::disbursed()->get()->sum('total_amount') + RequestItem::disbursed()->where('payment_type', RequestPaymentType::Reimburse)->get()->sum('total_act_amount');
                                        $totalSettled = RequestItem::disbursed()->get()->sum('total_act_amount');
                                        $variance = $totalRequested - $totalSettled;

                                        $percentage = $totalRequested > 0 ? min(100, max(0, (abs($variance) / $totalRequested) * 100)) : 0;
                                        $progressPercentage = $totalRequested > 0 ? min(100, max(0, ($totalSettled / $totalRequested) * 100)) : 0;
                                        $isNegative = $variance < 0;

                                        // Determine color based on variance
                                        $colorClass = $isNegative ? 'bg-red-500' : 'bg-green-500';
                                        $textColorClass = $isNegative ? 'text-red-600' : 'text-green-600';

                                        return [
                                            'totalRequested' => $totalRequested,
                                            'totalSettled' => $totalSettled,
                                            'variance' => $variance,
                                            'percentage' => $percentage,
                                            'progressPercentage' => $progressPercentage,
                                            'colorClass' => $colorClass,
                                            'textColorClass' => $textColorClass,
                                            'isNegative' => $isNegative,
                                        ];
                                    }),
                            ]),
                        Tab::make('Health Index Score')
                            ->extraAttributes([
                                'class' => 'px-1',
                            ])
                            ->schema([
                                Tabs::make('health-index-score')
                                    ->contained(false)
                                    ->tabs([
                                        Tab::make('Score saya')
                                            ->schema([
                                                Text::make(Auth::user()->employee->settlementHealthIndexScore),
                                            ]),
                                        Tab::make('Score Team')
                                            ->schema([
                                                RepeatableEntry::make('team-health-index-score')
                                                    ->hiddenLabel()
                                                    ->state(fn () => Employee::query()->with('user')->get())
                                                    ->table([
                                                        TableColumn::make('Nama'),
                                                        TableColumn::make('Total Request Item'),
                                                        TableColumn::make('Total Settled'),
                                                        TableColumn::make('Total On-Time'),
                                                        TableColumn::make('Score'),
                                                    ])
                                                    ->schema([
                                                        TextEntry::make('user.name')
                                                            ->label('Nama'),
                                                        TextEntry::make('disbursed_request_item_count')
                                                            ->counts('disbursedRequestItem')
                                                            ->label('Total Request Item')
                                                            ->placeholder('N/A'),
                                                        TextEntry::make('closed_request_count')
                                                            ->counts('closedRequest')
                                                            ->label('Total Settled')
                                                            ->placeholder('N/A'),
                                                        TextEntry::make('ontime_request_count')
                                                            ->counts('ontimeRequest')
                                                            ->label('Total On-Time')
                                                            ->placeholder('N/A'),
                                                        TextEntry::make('settlementHealthIndexScore')
                                                            ->label('Score')
                                                            ->placeholder('N/A')
                                                            ->formatStateUsing(function ($state) {
                                                                $percentage = (float) $state;
                                                                $textColor = '';

                                                                if ($percentage >= 80) {
                                                                    $textColor = 'text-green-700';
                                                                } elseif ($percentage >= 50) {
                                                                    $textColor = 'text-amber-600';
                                                                } else {
                                                                    $textColor = 'text-red-500';
                                                                }

                                                                return new HtmlString(
                                                                    '<div class="flex items-baseline gap-0.5">
                                                                    <span class="text-xl font-semibold '.$textColor.'">'.$state.'</span>
                                                                    <span class="text-xs text-neutral-400">/100</span>
                                                                </div>'
                                                                );
                                                            }),
                                                    ]),
                                            ])
                                            ->visible(fn () => Auth::user()->employee->jobTitle->jobLevel->level === 5 || Auth::user()->employee->jobTitle->department->code === 'FIN'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    // public function table(Table $table): Table
    // {
    //     return $table
    //         ->query(Product::query())
    //         ->columns([
    //             TextColumn::make('name'),
    //         ])
    //         ->filters([
    //             // ...
    //         ])
    //         ->recordActions([
    //             // ...
    //         ])
    //         ->toolbarActions([
    //             // ...
    //         ]);
    // }
}

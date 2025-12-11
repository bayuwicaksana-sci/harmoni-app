<?php

namespace App\Filament\Resources\Settlements\Pages;

use App\Enums\SettlementStatus;
use App\Filament\Resources\Settlements\SettlementResource;
use App\Filament\Resources\Settlements\Widgets\RequestItemToSettle;
use App\Filament\Resources\Settlements\Widgets\SettlementOverview;
use App\Filament\Resources\Settlements\Widgets\SettlementOverviewOld;
use App\Models\Settlement;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListSettlements extends ListRecords
{
    protected static string $resource = SettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->authorize('create')
                ->authorizationTooltip(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SettlementOverview::class,
            SettlementOverviewOld::class,
            RequestItemToSettle::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'my_settlement';
    }

    public function getTabs(): array
    {
        return [
            'require_confirmation' => Tab::make('Permintaan Konfirmasi')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SettlementStatus::WaitingConfirmation)->whereNot('submitter_id', Auth::user()?->employee?->id))
                ->badge(fn () => Settlement::where('status', SettlementStatus::WaitingConfirmation)->whereNot('submitter_id', Auth::user()?->employee?->id)->count())
                ->badgeColor('info')
                ->visible(Auth::user()->employee->jobTitle->code === 'FO'),
            'all' => Tab::make('Semua Settlement')
                ->badge(function () {
                    return Settlement::count();
                })
                ->visible(Auth::user()->employee->jobTitle->department->code === 'FIN' || Auth::user()->employee->jobTitle->jobLevel->level === 5),

            'my_settlement' => Tab::make('Settlement Saya')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('submitter_id', Auth::user()?->employee?->id))
                ->badge(function () {

                    $count = Settlement::where('submitter_id', Auth::user()->employee->id)->count();

                    return $count > 0 ? $count : null;
                }),

            // 'require_approval' => Tab::make('Permintaan Approval')
            //     ->modifyQueryUsing(fn (Builder $query) => $query->where('status', DPRStatus::Pending)->whereNot('requester_id', Auth::user()?->employee?->id)
            //         ->whereHas('approvalHistories', function ($q) {
            //             // Only show requests where this employee is the CURRENT pending approver
            //             $q->where('approver_id', Auth::user()?->employee?->id)
            //                 ->where('action', 'pending')
            //                 ->whereRaw('sequence = (
            //           SELECT MIN(sequence)
            //           FROM approval_histories ah2
            //           WHERE ah2.daily_payment_request_id = approval_histories.daily_payment_request_id
            //           AND ah2.action = "pending"
            //       )');
            //         }))
            //     ->badge(fn () => DailyPaymentRequest::where('status', DPRStatus::Pending)->whereNot('requester_id', Auth::user()?->employee?->id)->whereHas('approvalHistories', function ($q) {
            //         // Only show requests where this employee is the CURRENT pending approver
            //         $q->where('approver_id', Auth::user()?->employee?->id)
            //             ->where('action', 'pending')
            //             ->whereRaw('sequence = (
            //           SELECT MIN(sequence)
            //           FROM approval_histories ah2
            //           WHERE ah2.daily_payment_request_id = approval_histories.daily_payment_request_id
            //           AND ah2.action = "pending"
            //       )');
            //     })->count())
            //     ->badgeColor('info'),

            // 'draft' => Tab::make('Draft')
            //     ->modifyQueryUsing(fn (Builder $query) => $query->where('status', DPRStatus::Draft)->where('requester_id', Auth::user()?->employee?->id))
            //     ->badge(fn () => DailyPaymentRequest::where('status', DPRStatus::Draft)->where('requester_id', Auth::user()?->employee?->id)->count()),

            // 'pending' => Tab::make('Menunggu Approval')
            //     ->modifyQueryUsing(fn (Builder $query) => $query->where('status', DPRStatus::Pending)->where('requester_id', Auth::user()?->employee?->id))
            //     ->badge(fn () => DailyPaymentRequest::where('status', DPRStatus::Pending)->where('requester_id', Auth::user()?->employee?->id)->count())
            //     ->badgeColor('warning'),

            // 'approved' => Tab::make('Approved')
            //     ->modifyQueryUsing(fn (Builder $query) => $query->where('status', DPRStatus::Approved)->where('requester_id', Auth::user()?->employee?->id))
            //     ->badge(fn () => DailyPaymentRequest::where('status', DPRStatus::Approved)->where('requester_id', Auth::user()?->employee?->id)->count())
            //     ->badgeColor('success'),

            // 'rejected' => Tab::make('Ditolak')
            //     ->modifyQueryUsing(fn (Builder $query) => $query->where('status', DPRStatus::Rejected)->where('requester_id', Auth::user()?->employee?->id))
            //     ->badge(fn () => DailyPaymentRequest::where('status', DPRStatus::Rejected)->where('requester_id', Auth::user()?->employee?->id)->count())
            //     ->badgeColor('danger'),
        ];
    }
}

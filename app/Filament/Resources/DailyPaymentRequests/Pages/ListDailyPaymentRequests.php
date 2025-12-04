<?php

namespace App\Filament\Resources\DailyPaymentRequests\Pages;

use App\Enums\DPRStatus;
use App\Filament\Resources\DailyPaymentRequests\DailyPaymentRequestResource;
use App\Models\DailyPaymentRequest;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListDailyPaymentRequests extends ListRecords
{
    protected static string $resource = DailyPaymentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->authorize('create')
                ->authorizationTooltip(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Request')->modifyQueryUsing(function (Builder $query) {
                if (Auth::user()->employee->jobTitle->jobLevel->level === 5 || Auth::user()->employee->jobTitle->department->code === 'FIN') {
                    $query->whereNot('status', DPRStatus::Draft);
                } else {
                    $query->where('status', DPRStatus::Pending)->where('requester_id', Auth::user()->employee->id);
                }
            }),

            'require_approval' => Tab::make('Permintaan Approval')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', DPRStatus::Pending)->whereNot('requester_id', Auth::user()?->employee?->id)
                    ->whereHas('approvalHistories', function ($q) {
                        // Only show requests where this employee is the CURRENT pending approver
                        $q->where('approver_id', Auth::user()?->employee?->id)
                            ->where('action', 'pending')
                            ->whereRaw('sequence = (
                      SELECT MIN(sequence)
                      FROM approval_histories ah2
                      WHERE ah2.daily_payment_request_id = approval_histories.daily_payment_request_id
                      AND ah2.action = "pending"
                  )');
                    }))
                ->badge(fn () => DailyPaymentRequest::where('status', DPRStatus::Pending)->whereNot('requester_id', Auth::user()?->employee?->id)->whereHas('approvalHistories', function ($q) {
                    // Only show requests where this employee is the CURRENT pending approver
                    $q->where('approver_id', Auth::user()?->employee?->id)
                        ->where('action', 'pending')
                        ->whereRaw('sequence = (
                      SELECT MIN(sequence)
                      FROM approval_histories ah2
                      WHERE ah2.daily_payment_request_id = approval_histories.daily_payment_request_id
                      AND ah2.action = "pending"
                  )');
                })->count())
                ->badgeColor('info'),

            'my_requests' => Tab::make('Request Saya')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('requester_id', Auth::user()?->employee?->id)),

            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', DPRStatus::Draft)->where('requester_id', Auth::user()?->employee?->id))
                ->badge(fn () => DailyPaymentRequest::where('status', DPRStatus::Draft)->where('requester_id', Auth::user()?->employee?->id)->count()),

            'pending' => Tab::make('Menunggu Approval')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', DPRStatus::Pending)->where('requester_id', Auth::user()?->employee?->id))
                ->badge(fn () => DailyPaymentRequest::where('status', DPRStatus::Pending)->where('requester_id', Auth::user()?->employee?->id)->count())
                ->badgeColor('warning'),

            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', DPRStatus::Approved)->where('requester_id', Auth::user()?->employee?->id))
                ->badge(fn () => DailyPaymentRequest::where('status', DPRStatus::Approved)->where('requester_id', Auth::user()?->employee?->id)->count())
                ->badgeColor('success'),

            'rejected' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', DPRStatus::Rejected)->where('requester_id', Auth::user()?->employee?->id))
                ->badge(fn () => DailyPaymentRequest::where('status', DPRStatus::Rejected)->where('requester_id', Auth::user()?->employee?->id)->count())
                ->badgeColor('danger'),
        ];
    }
}

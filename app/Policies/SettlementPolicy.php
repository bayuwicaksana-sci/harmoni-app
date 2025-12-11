<?php

namespace App\Policies;

use App\Enums\RequestItemStatus;
use App\Enums\SettlementStatus;
use App\Models\RequestItem;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;

class SettlementPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Settlement $settlement): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        return RequestItem::where('status', RequestItemStatus::WaitingSettlement)->whereHas('dailyPaymentRequest', function (Builder $query) use ($user) {
            $query->where('requester_id', $user->employee->id);
        })->get()->first() ? Response::allow() : Response::deny('Kamu tidak memiliki Item yang menunggu settlement! Mantap!');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Settlement $settlement): bool
    {
        return ($settlement->status === SettlementStatus::Draft || $settlement->status === SettlementStatus::WaitingRefund)
            && $settlement->submitter_id === $user->employee->id
            && ! $settlement->hasPendingDPR();     // Can edit if no pending DPR
        // && ! $settlement->hasApprovedDPR();    // Can't edit if has approved DPR
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Settlement $settlement): bool
    {
        return ($settlement->status === SettlementStatus::Draft || $settlement->status === SettlementStatus::WaitingRefund)
            && $settlement->submitter_id === $user->employee->id
            && ! $settlement->hasPendingDPR()
            && ! $settlement->hasApprovedDPR();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Settlement $settlement): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Settlement $settlement): bool
    {
        return true;
    }
}

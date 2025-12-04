<?php

namespace App\Policies;

use App\Models\DailyPaymentRequest;
use App\Models\RequestItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;

class DailyPaymentRequestPolicy
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
    public function view(User $user, DailyPaymentRequest $dailyPaymentRequest): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        return RequestItem::where('due_date', '<', now()->toDateString())->whereHas('dailyPaymentRequest', function (Builder $query) use ($user) {
            $query->where('requester_id', $user->employee->id);
        })->get()->first() ? Response::deny('Kamu memiliki Item yang belum di settle nih. Settle dulu ya!') : Response::allow();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DailyPaymentRequest $dailyPaymentRequest): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DailyPaymentRequest $dailyPaymentRequest): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DailyPaymentRequest $dailyPaymentRequest): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DailyPaymentRequest $dailyPaymentRequest): bool
    {
        return true;
    }
}

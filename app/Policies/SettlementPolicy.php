<?php

namespace App\Policies;

use App\Enums\SettlementStatus;
use App\Models\Settlement;
use App\Models\User;

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
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Settlement $settlement): bool
    {
        return ($settlement->status === SettlementStatus::Draft || $settlement->status === SettlementStatus::WaitingRefund) && $settlement->submitter_id === $user->employee->id && ! ($settlement->generatedPaymentRequest !== null);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Settlement $settlement): bool
    {
        return ($settlement->status === SettlementStatus::Draft || $settlement->status === SettlementStatus::WaitingRefund) && $settlement->submitter_id === $user->employee->id && ! ($settlement->generatedPaymentRequest !== null);
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

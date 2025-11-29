<?php

namespace App\Services;

use App\Mail\SettlementConfirmationRequestMail;
use App\Mail\SettlementRevisionRequestedMail;
use App\Models\Employee;
use App\Models\Settlement;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SettlementNotificationService
{
    public function notifyFinanceOperatorForConfirmation(Settlement $settlement): void
    {
        try {
            $financeOperator = Employee::whereHas('jobTitle', function ($query) {
                $query->where('code', 'FO');
            })->first();

            if (! $financeOperator) {
                Log::warning('No Finance Operator found', [
                    'settlement_id' => $settlement->id,
                ]);

                return;
            }

            Mail::to($financeOperator->user->email)
                ->send(new SettlementConfirmationRequestMail($settlement, $financeOperator));

            Log::info('FO notified for settlement confirmation', [
                'settlement_id' => $settlement->id,
                'fo_email' => $financeOperator->user->email,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to notify FO', [
                'settlement_id' => $settlement->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function notifySubmitterOfRevision(Settlement $settlement): void
    {
        try {
            Mail::to($settlement->submitter->user->email)
                ->send(new SettlementRevisionRequestedMail($settlement));

            Log::info('Submitter notified of revision request', [
                'settlement_id' => $settlement->id,
                'submitter_email' => $settlement->submitter->user->email,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to notify submitter', [
                'settlement_id' => $settlement->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

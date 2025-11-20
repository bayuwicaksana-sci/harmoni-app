<?php

namespace App\Filament\Resources\DailyPaymentRequests\Pages;

use App\Enums\ApprovalAction;
use App\Enums\DPRStatus;
use App\Enums\RequestItemStatus;
use Exception;
use App\Filament\Resources\DailyPaymentRequests\DailyPaymentRequestResource;
use App\Services\ApprovalService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ViewDailyPaymentRequest extends ViewRecord
{
    protected static string $resource = DailyPaymentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn() => $this->record->canBeEdited()),
            Action::make('submit')
                ->label('Submit for Approval')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        $validation = $this->record->validateForSubmission();

                        if (!$validation['valid']) {
                            // Format errors for better readability
                            $errorList = collect($validation['errors'])
                                ->map(fn($error) => "• {$error}")
                                ->implode("\n");

                            Notification::make()
                                ->title('Request Gagal Diajukan')
                                ->body("Perbaiki data berikut sebelum kembali mengajukan:\n\n" . $errorList)
                                ->danger()
                                ->persistent()
                                ->send();

                            // Redirect to edit page to fix errors
                            return redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                        }

                        // If valid, submit
                        $this->record->status = DPRStatus::Pending;
                        $this->record->request_date = now();
                        $this->record->save();

                        $this->record->requestItems()->update(['status' => RequestItemStatus::WaitingPayment]);

                        $approvalService = app(ApprovalService::class);
                        $approvalService->submitRequest($this->record);

                        Notification::make()
                            ->title('Request Submitted')
                            ->success()
                            ->body('Your payment request has been submitted for approval.')
                            ->send();

                        return redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (Exception $e) {
                        Notification::make()
                            ->title('Submission Failed')
                            ->danger()
                            ->body($e->getMessage())
                            ->send();
                    }
                })
                ->visible(fn() => $this->record->status === DPRStatus::Draft && $this->record->requester->id === Auth::user()->employee->id),
            Action::make('approve')
                ->label(fn() => Auth::user()->employee->jobTitle->code === 'FO' ? 'Submit ke Head of Finance' : 'Approve')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->schema([
                    Textarea::make('notes')
                        ->label('Approval Notes')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $approvalService = app(ApprovalService::class);
                    $employee = Auth::user()->employee;

                    $approval = $this->record->approvalHistories()
                        ->where('approver_id', $employee->id)
                        ->where('action', ApprovalAction::Pending)
                        ->first();

                    if ($approval) {
                        $approvalService->approve($approval, $data['notes'] ?? null);

                        Notification::make()
                            ->title('Request Approved')
                            ->success()
                            ->send();

                        return redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    }
                })
                ->visible(function () {
                    $employee = Auth::user()?->employee;
                    if (!$employee) return false;

                    $latestApprovalSeq = $this->record->approvalHistories()
                        ->where('action', ApprovalAction::Pending)
                        ->min('sequence');

                    $approverSeq = $this->record->approvalHistories()
                        ->where('approver_id', $employee->id)?->first()?->sequence ?? null;

                    return $this->record->approvalHistories()
                        ->where('approver_id', $employee->id)
                        ->where('action', ApprovalAction::Pending)
                        ->exists() && $latestApprovalSeq === $approverSeq;
                }),
            Action::make('reject')
                ->label('Reject')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('notes')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $approvalService = app(ApprovalService::class);
                    $employee = Auth::user()->employee;

                    $approval = $this->record->approvalHistories()
                        ->where('approver_id', $employee->id)
                        ->where('action', ApprovalAction::Pending)
                        ->first();

                    if ($approval) {
                        $approvalService->reject($approval, $data['notes']);

                        Notification::make()
                            ->title('Request Rejected')
                            ->danger()
                            ->send();

                        return redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
                    }
                })
                ->visible(function () {
                    $employee = Auth::user()?->employee;
                    if (!$employee) return false;

                    $latestApprovalSeq = $this->record->approvalHistories()
                        ->where('action', ApprovalAction::Pending)
                        ->min('sequence');

                    $approverSeq = $this->record->approvalHistories()
                        ->where('approver_id', $employee->id)?->first()?->sequence ?? null;

                    return $this->record->approvalHistories()
                        ->where('approver_id', $employee->id)
                        ->where('action', ApprovalAction::Pending)
                        ->exists() && $employee->jobTitle->code !== 'FO' && $latestApprovalSeq === $approverSeq;
                }),
            DeleteAction::make()
                ->visible(fn() => $this->record->isDraft() && $this->record->requester->id === Auth::user()->employee->id),
        ];
    }
}

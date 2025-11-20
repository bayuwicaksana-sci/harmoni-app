<?php

namespace App\Filament\Resources\DailyPaymentRequests\Pages;

use App\Enums\DPRStatus;
use App\Enums\RequestItemStatus;
use App\Filament\Resources\DailyPaymentRequests\DailyPaymentRequestResource;
use App\Models\DailyPaymentRequest;
use App\Services\ApprovalService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditDailyPaymentRequest extends EditRecord
{
    protected static string $resource = DailyPaymentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()->visible(fn() => $this->record->isDraft()),
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
                ->visible(fn() => $this->record->status === DPRStatus::Draft),
        ];
    }

    protected function afterSave(): void
    {
        // Recalculate totals after editing
        $this->record->calculateTotals();
    }
}

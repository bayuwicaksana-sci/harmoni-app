<?php

namespace App\Filament\Resources\Settlements\Pages;

use App\Enums\SettlementStatus;
use App\Filament\Resources\Settlements\SettlementResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EditSettlement extends EditRecord
{
    protected static string $resource = SettlementResource::class;

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): ?string
    {
        return SettlementResource::getUrl('view', ['record' => $this->record]);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->formId('form'),
            Action::make('saveAndSubmit')
                ->label('Save & Submit')
                ->icon('heroicon-o-paper-airplane')
                ->iconPosition(IconPosition::Before)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Submit Revised Settlement')
                ->modalDescription('Apakah Anda yakin ingin mengirimkan kembali settlement ini?')
                ->modalSubmitActionLabel('Ya, Submit')
                ->action(function () {
                    try {
                        $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

                        // $this->record->save();

                        $this->record->refresh();

                        $this->record->resubmit();

                        Notification::make()
                            ->title('Settlement berhasil disubmit')
                            ->body('Settlement telah diproses')
                            ->success()
                            ->send();

                        // Redirect to view
                        $this->redirect(SettlementResource::getUrl('view', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        // Log the error for debugging
                        Log::error('Settlement saveAndSubmit error', [
                            'settlement_id' => $this->record->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);

                        // Show validation error notification
                        Notification::make()
                            ->title('Validasi Gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();

                        // Don't halt - let user fix the form
                    }
                })
                ->visible(fn () => ($this->record->status === SettlementStatus::Draft || $this->record->status === SettlementStatus::WaitingRefund)
                    && $this->record->submitter_id === Auth::user()->employee?->id
                ),
            $this->getCancelFormAction()
                ->formId('form'),
            DeleteAction::make(),
        ];
    }

    // protected function afterSave(): void
    // {
    //     $settlement = $this->record;

    //     dd($settlement->settlementItems()->get());
    // }
}

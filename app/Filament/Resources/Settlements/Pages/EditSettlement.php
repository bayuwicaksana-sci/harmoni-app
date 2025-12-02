<?php

namespace App\Filament\Resources\Settlements\Pages;

use App\Enums\SettlementStatus;
use App\Filament\Resources\Settlements\Schemas\SettlementForm;
use App\Filament\Resources\Settlements\SettlementResource;
use App\Models\RequestItem;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EditSettlement extends EditRecord
{
    protected static string $resource = SettlementResource::class;

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->formId('form'),
            $this->getCancelFormAction()
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
                    // $parsedRecords = SettlementForm::parseCurrencyToFloat($this->data['settlementReceipts'] ?? []);
                    // $formData = ['settlementReceipts' => $parsedRecords];
                    // dd($this->data['settlementReceipts'], $parsedRecords, $formData);
                    // try {
                    //     DB::transaction(function () {
                    //         $settlement = $this->record;

                    //         // 1. Manually process form data (same as SettlementForm callback)
                    //         $formDataService = app(\App\Services\SettlementFormDataService::class);

                    //         // Parse currency and extract structured data from form
                    //         $parsedRecords = SettlementForm::parseCurrencyToFloat($this->data['settlementReceipts'] ?? []);
                    //         $formData = ['settlementReceipts' => $parsedRecords];
                    //         $structuredData = $formDataService->extractFormData($formData);

                    //         // 2. Process items with edit mode flag (clean existing data)
                    //         $itemProcessor = app(\App\Services\SettlementItemProcessingService::class);
                    //         $results = $itemProcessor->processReceipts(
                    //             $structuredData['receipts'],
                    //             $settlement,
                    //             true // edit mode - deletes existing offset/reimburse items
                    //         );

                    //         // 3. Clean up deleted items
                    //         $processedIds = collect($results)->pluck('item.id')->filter()->toArray();
                    //         $existingIds = $settlement->settlementItems()->pluck('id')->toArray();
                    //         $deletedIds = array_diff($existingIds, $processedIds);

                    //         if (! empty($deletedIds)) {
                    //             RequestItem::whereIn('id', $deletedIds)->delete();
                    //         }

                    //         // 4. Categorize and process settlement for DPR calculation
                    //         $categorized = $itemProcessor->categorizeItems(collect($results));
                    //         $offsetService = app(\App\Services\SettlementOffsetCalculationService::class);
                    //         $overspentResults = $categorized['overspent'] ? collect($categorized['overspent'])->flatten(1)->toArray() : [];

                    //         $processResult = $offsetService->processSettlement($categorized, $overspentResults, $settlement);

                    //         // 5. Update refund amount if in Draft status
                    //         if ($settlement->status === SettlementStatus::Draft) {
                    //             $refundAmount = $processResult['total_refund_amount'] ?? 0;
                    //             $settlement->update(['refund_amount' => max(0, $refundAmount)]);
                    //         }

                    //         // 6. Save settlement basic changes (settlementReceipts already saved via relationship)
                    //         $settlement->save();

                    //         // 7. Refresh and call resubmit (now with processed data)
                    //         $settlement->refresh();
                    //         $settlement->resubmit();
                    //     });

                    //     Notification::make()
                    //         ->title('Settlement berhasil disubmit')
                    //         ->body('Settlement telah diproses')
                    //         ->success()
                    //         ->send();

                    //     // Redirect to view
                    //     $this->redirect(SettlementResource::getUrl('view', ['record' => $this->record]));

                    // } catch (\Exception $e) {
                    //     // Log the error for debugging
                    //     Log::error('Settlement saveAndSubmit error', [
                    //         'settlement_id' => $this->record->id,
                    //         'error' => $e->getMessage(),
                    //         'trace' => $e->getTraceAsString(),
                    //     ]);

                    //     // Show validation error notification
                    //     Notification::make()
                    //         ->title('Validasi Gagal')
                    //         ->body($e->getMessage())
                    //         ->danger()
                    //         ->persistent()
                    //         ->send();

                    //     // Don't halt - let user fix the form
                    // }

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

            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn () => ($this->record->status === SettlementStatus::Draft || $this->record->status === SettlementStatus::WaitingRefund)
                    && $this->record->submitter_id === Auth::user()->employee?->id
                    && ! ($this->record->generatedPaymentRequest !== null)
                ),
        ];
    }

    // protected function afterSave(): void
    // {
    //     $settlement = $this->record;

    //     dd($settlement->settlementItems()->get());
    // }
}

<?php

namespace App\Filament\Resources\Settlements\Pages;

use App\Enums\RequestItemStatus;
use App\Enums\SettlementStatus;
use App\Filament\Resources\Settlements\Schemas\SettlementForm;
use App\Filament\Resources\Settlements\SettlementResource;
use App\Models\RequestItem;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EditSettlement extends EditRecord
{
    protected static string $resource = SettlementResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Eager load relationships to avoid N+1 queries
        $this->record->load(['settlementReceipts.requestItems']);

        // Block editing if settlement has DPR
        if ($this->record->generated_payment_request_id !== null) {
            Notification::make()
                ->title('Editing Blocked')
                ->body('Settlement dengan DPR tidak dapat direvisi. DPR approval adalah komitmen finansial.')
                ->danger()
                ->send();

            $this->redirect(SettlementResource::getUrl('view', ['record' => $this->record]));
        }
    }

    public function form(Schema $schema): Schema
    {
        // If revision mode, show revision notes section first
        if ($this->record->revision_notes !== null) {
            return $schema->components([
                Section::make('Catatan Revisi dari Finance Operator')
                    ->description('Harap perbaiki settlement sesuai catatan di bawah')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->iconColor('warning')
                    ->columnSpanFull()
                    ->schema([
                        Text::make($this->record->revision_notes)
                            ->color('warning')
                            ->size(TextSize::Medium)
                            ->weight(FontWeight::Medium),
                    ]),

                // Use SAME form as CreateSettlement
                ...SettlementForm::configure($schema)->getComponents(),
            ]);
        }

        // Normal edit mode (no revision)
        return SettlementForm::configure($schema);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Calculate financial summary from settlement's existing RequestItems
        $settlement = $this->record;

        // Load all settlement items (RequestItems linked to this settlement)
        $settlement->load('settlementItems');

        $approvedAmount = 0;
        $cancelledAmount = 0;
        $spentAmount = 0;

        foreach ($settlement->settlementItems as $item) {
            // Calculate approved amount (original request amount) - only for planned items
            if (! $item->is_unplanned) {
                $requestTotal = $item->quantity * $item->amount_per_item;
                $approvedAmount += $requestTotal;

                // If cancelled, add to cancelled amount
                if ($item->status === RequestItemStatus::Cancelled) {
                    $cancelledAmount += $requestTotal;
                } else {
                    // Add actual spent amount
                    $spentAmount += $item->act_quantity * $item->act_amount_per_item;
                }
            } else {
                // For unplanned items, only add to spent amount (no approved amount)
                $spentAmount += $item->act_quantity * $item->act_amount_per_item;
            }
        }

        $variance = $approvedAmount - $spentAmount;

        $formatMoney = fn ($num) => number_format($num, 2, ',', '.');

        $data['approved_request_amount'] = $formatMoney($approvedAmount);
        $data['cancelled_amount'] = $formatMoney($cancelledAmount);
        $data['spent_amount'] = $formatMoney($spentAmount);
        $data['variance'] = $formatMoney($variance);

        return $data;
    }

    // protected function afterSave(): void
    // {
    //     try {
    //         DB::transaction(function () {
    //             $settlement = $this->record;

    //             // Filament already saved settlementReceipts via relationship
    //             // We only need to process the manually-submitted items (request_items, new_request_items)

    //             $formData = $this->data;

    //             // Service 1: Extract form data
    //             $formDataService = app(\App\Services\SettlementFormDataService::class);
    //             $structuredData = $formDataService->extractFormData($formData);

    //             // Service 2: Process items (with edit mode flag)
    //             $itemProcessor = app(\App\Services\SettlementItemProcessingService::class);
    //             $results = $itemProcessor->processReceipts(
    //                 $structuredData['receipts'],
    //                 $settlement,
    //                 true // edit mode - deletes existing offset/reimburse items
    //             );

    //             // Track deletions: compare processed IDs to existing IDs
    //             $processedIds = collect($results)->pluck('item.id')->filter()->toArray();
    //             $existingIds = $settlement->settlementItems()->pluck('id')->toArray();
    //             $deletedIds = array_diff($existingIds, $processedIds);

    //             if (! empty($deletedIds)) {
    //                 RequestItem::whereIn('id', $deletedIds)->delete();
    //             }

    //             // Service 3: Categorize items
    //             $categorized = $itemProcessor->categorizeItems(collect($results));

    //             // Service 4: Recalculate offsets
    //             $offsetService = app(\App\Services\SettlementOffsetCalculationService::class);
    //             $offsetResult = $offsetService->calculateOffsets($categorized, $settlement);

    //             // Service 5: Create reimbursement items
    //             $reimbursementItems = $offsetService->createReimbursementItems(
    //                 $categorized['overspent'] ? collect($categorized['overspent'])->flatten(1)->toArray() : [],
    //                 $settlement
    //             );

    //             // Update refund amount if in Draft status
    //             if ($settlement->status === SettlementStatus::Draft) {
    //                 $refundAmount = $offsetResult['total_refund_amount'] ?? 0;
    //                 $settlement->update(['refund_amount' => max(0, $refundAmount)]);
    //             }

    //             // Note: resubmit() will handle DPR creation when "Save & Submit" is clicked
    //         });
    //     } catch (\Exception $e) {
    //         // Show validation error notification
    //         Notification::make()
    //             ->title('Validasi Gagal')
    //             ->body($e->getMessage())
    //             ->danger()
    //             ->persistent()
    //             ->send();

    //         // Halt the save process
    //         $this->halt();
    //     }
    // }

    protected function getHeaderActions(): array
    {
        return [
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
                    // 1. Save changes first
                    // $this->save();

                    // 2. Refresh settlement
                    // $this->record->refresh();

                    // 3. Call resubmit (handles DPR detection & smart routing)
                    $this->record->resubmit();

                    Notification::make()
                        ->title('Settlement berhasil disubmit')
                        ->body('Settlement telah diproses')
                        ->success()
                        ->send();

                    // Redirect to view
                    $this->redirect(SettlementResource::getUrl('view', ['record' => $this->record]));
                })
                ->visible(fn () => $this->record->status === SettlementStatus::Draft
                    && $this->record->submitter_id === Auth::user()->employee?->id
                ),

            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn () => $this->record->status === SettlementStatus::Draft),
        ];
    }
}

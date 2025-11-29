<?php

namespace App\Filament\Resources\Settlements\Pages;

use App\Enums\SettlementStatus;
use App\Filament\Resources\Settlements\SettlementResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\Facades\Auth;

class ViewSettlement extends ViewRecord
{
    protected static string $resource = SettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadRefundReceipt')
                ->label('Upload Bukti Transfer')
                ->icon('heroicon-o-arrow-up-tray')
                ->iconPosition(IconPosition::Before)
                ->color('info')
                ->schema([
                    SpatieMediaLibraryFileUpload::make('refund_receipt')
                        ->multiple(false)
                        ->label('Bukti Transfer Pengembalian Dana')
                        ->required()
                        ->dehydrated(true)
                        ->storeFiles(false)
                        ->maxSize(5120)
                        ->acceptedFileTypes(['image/*', 'application/pdf'])
                        ->helperText('Upload bukti transfer pengembalian dana (max 5MB)'),
                ])
                ->action(function (array $data) {
                    $settlement = $this->record;

                    // Upload receipt
                    $settlement->addMedia($data['refund_receipt'])
                        ->toMediaCollection('refund_receipts', 'local');

                    $settlement->update(['status' => SettlementStatus::WaitingConfirmation]);

                    app(\App\Services\SettlementNotificationService::class)
                        ->notifyFinanceOperatorForConfirmation($settlement);

                    Notification::make()
                        ->title('Bukti transfer berhasil diupload')
                        ->body('Menunggu konfirmasi dari Finance Operator')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->status === SettlementStatus::WaitingRefund
                    && $this->record->submitter_id === Auth::user()->employee?->id
                ),

            Action::make('confirmRefund')
                ->label('Konfirmasi Pengembalian Dana')
                ->icon('heroicon-o-check-circle')
                ->iconPosition(IconPosition::Before)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Pengembalian Dana')
                ->modalDescription('Apakah Anda yakin dana sudah dikembalikan oleh employee?')
                ->modalSubmitActionLabel('Ya, Konfirmasi')
                ->action(function () {
                    $settlement = $this->record;
                    $settlement->confirmRefund(Auth::user()->employee);

                    Notification::make()
                        ->title('Pengembalian dana dikonfirmasi')
                        ->body('Settlement telah selesai')
                        ->success()
                        ->send();

                    $this->redirect(SettlementResource::getUrl('view', ['record' => $settlement]));
                })
                ->visible(fn () => $this->record->status === SettlementStatus::WaitingConfirmation
                    && $this->record->getMedia('refund_receipts')->isNotEmpty()
                    && Auth::user()->employee?->jobTitle?->code === 'FO'
                ),

            Action::make('confirmSettlement')
                ->label('Konfirmasi Settlement')
                ->icon('heroicon-o-check-circle')
                ->iconPosition(IconPosition::Before)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Settlement')
                ->modalDescription('Apakah Anda yakin semua data settlement sudah benar?')
                ->modalSubmitActionLabel('Ya, Konfirmasi')
                ->action(function () {
                    $settlement = $this->record;
                    $settlement->confirmSettlement(Auth::user()->employee);

                    Notification::make()
                        ->title('Settlement dikonfirmasi')
                        ->body('Settlement telah selesai')
                        ->success()
                        ->send();

                    $this->redirect(SettlementResource::getUrl('view', ['record' => $settlement]));
                })
                ->visible(fn () => $this->record->status === SettlementStatus::WaitingConfirmation
                    && $this->record->getMedia('refund_receipts')->isEmpty()
                    && Auth::user()->employee?->jobTitle?->code === 'FO'
                ),

            Action::make('requestRevision')
                ->label('Minta Revisi')
                ->icon('heroicon-o-arrow-uturn-left')
                ->iconPosition(IconPosition::Before)
                ->color('warning')
                ->schema([
                    \Filament\Forms\Components\Textarea::make('revision_reason')
                        ->label('Alasan Revisi')
                        ->required()
                        ->rows(3)
                        ->placeholder('Jelaskan apa yang perlu diperbaiki...'),
                ])
                ->action(function (array $data) {
                    $settlement = $this->record;
                    $settlement->requestRevision($data['revision_reason']);

                    app(\App\Services\SettlementNotificationService::class)
                        ->notifySubmitterOfRevision($settlement);

                    Notification::make()
                        ->title('Revisi diminta')
                        ->body('Submitter akan diberitahu untuk melakukan perbaikan')
                        ->success()
                        ->send();

                    $this->redirect(SettlementResource::getUrl('view', ['record' => $settlement]));
                })
                ->visible(fn () => $this->record->status === SettlementStatus::WaitingConfirmation
                    && Auth::user()->employee?->jobTitle?->code === 'FO'
                    && $this->record->generated_payment_request_id === null
                ),

            EditAction::make()
                ->label(fn () => $this->record->revision_notes
                    ? 'Edit & Resubmit'
                    : 'Edit'
                )
                ->color(fn () => $this->record->revision_notes
                    ? 'warning'
                    : 'gray'
                )
                ->visible(fn () => $this->record->status === SettlementStatus::Draft
                    && $this->record->submitter_id === Auth::user()->employee?->id
                ),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            \App\Filament\Resources\Settlements\RelationManagers\SettlementItemsRelationManager::class,
        ];
    }
}

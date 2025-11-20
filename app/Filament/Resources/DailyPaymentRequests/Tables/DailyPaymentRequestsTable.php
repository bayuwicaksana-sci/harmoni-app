<?php

namespace App\Filament\Resources\DailyPaymentRequests\Tables;

use App\Enums\DPRStatus;
use App\Enums\RequestItemStatus;
use App\Filament\Resources\DailyPaymentRequests\DailyPaymentRequestResource;
use Exception;
use App\Models\DailyPaymentRequest;
use App\Services\ApprovalService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DailyPaymentRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('request_number')
                    ->label('Request ID')
                    ->placeholder('Belum Disubmit')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('request_date')
                    ->label('Tanggal Request')
                    ->placeholder('Belum Disubmit')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('requester.user.name')
                    ->label('Requester')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('requester.jobTitle.department.name')
                    ->label('Department')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->getStateUsing(fn($record) => $record->total_request_amount)
                    ->label('Nominal Request')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('request_items_count')
                    ->label('Jumlah Item')
                    ->counts('requestItems')
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending Approval',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'paid' => 'Paid',
                        'settled' => 'Settled',
                    ]),

                // SelectFilter::make('requester')
                //     ->relationship('requester', 'user.name')
                //     ->searchable()
                //     ->preload(),

                Filter::make('request_date')
                    ->schema([
                        DatePicker::make('from')
                            ->native(false),
                        DatePicker::make('until')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('request_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('request_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->hidden(fn($record) => !$record->canBeEdited()),
                Action::make('submit')
                    ->label('Submit')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Submit Payment Request')
                    ->modalDescription('Are you sure you want to submit this request for approval? You will not be able to edit it after submission.')
                    ->action(function (DailyPaymentRequest $record) {
                        try {
                            $validation = $record->validateForSubmission();

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
                                return redirect(DailyPaymentRequestResource::getUrl('edit', ['record' => $record]));
                            }

                            // If valid, submit
                            $record->status = DPRStatus::Pending;
                            $record->request_date = now();
                            $record->save();

                            $record->requestItems()->update(['status' => RequestItemStatus::WaitingPayment]);

                            $approvalService = app(ApprovalService::class);
                            $approvalService->submitRequest($record);

                            Notification::make()
                                ->title('Request Submitted')
                                ->success()
                                ->body('Your payment request has been submitted for approval.')
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Submission Failed')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    })
                    ->visible(fn($record) => $record->canBeSubmitted()),
                DeleteAction::make()
                    ->visible(fn($record) => $record->isDraft() && $record->requester->id === Auth::user()?->employee->id)
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make()->visible(fn($record) => $record->isDraft()),
                // ]),
            ])
            ->defaultSort('request_date', 'desc');
    }
}

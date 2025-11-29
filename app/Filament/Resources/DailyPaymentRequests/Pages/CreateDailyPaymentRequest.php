<?php

namespace App\Filament\Resources\DailyPaymentRequests\Pages;

use App\Enums\DPRStatus;
use App\Enums\RequestItemStatus;
use App\Enums\RequestPaymentType;
use App\Exports\DPRTemplateExport;
use App\Filament\Resources\DailyPaymentRequests\DailyPaymentRequestResource;
use App\Filament\Resources\DailyPaymentRequests\Schemas\DailyPaymentRequestCreateForm2;
use App\Imports\DPRTemplateImport;
use App\Models\Coa;
use App\Models\ProgramActivity;
use App\Models\ProgramActivityItem;
use App\Models\RequestItem;
use App\Services\ApprovalService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class CreateDailyPaymentRequest extends CreateRecord
{
    use HasWizard;

    protected static string $resource = DailyPaymentRequestResource::class;

    protected ?array $requestItems;

    protected function getSteps(): array
    {
        return [
            Step::make('Formulir Payment Request')
                ->schema(DailyPaymentRequestCreateForm2::formFields())
                ->afterValidation(function (Get $get, Set $set) {
                    $items = [];
                    if ($get('requestItems') !== null) {
                        $items = array_filter($get('requestItems'), function ($item) {
                            return ! empty($item['coa_id']) && ! empty($item['item']) && ! empty($item['qty']) && ! empty($item['unit_qty']) && ! empty($item['base_price']);
                        });
                    }
                    // dd($items);
                    if (empty($items)) {
                        Notification::make()
                            ->title('Formulir masih kosong')
                            ->body('Pastikan setidaknya ada 1 item request')
                            ->danger()
                            ->persistent()
                            ->send();
                        throw new Halt;
                    }
                }),
            Step::make('Review')
                ->schema([
                    TextEntry::make('reviewTotalRequestAmount')
                        ->label('Total Nominal Request')
                        ->state(fn (Get $get) => (float) str_replace(['.', ','], ['', '.'], $get('total_request_amount')))
                        ->money(currency: 'IDR', locale: 'id')
                        ->size(TextSize::Large)
                        ->weight(FontWeight::ExtraBold),
                    Tabs::make('Tabs')
                        ->tabs([
                            Tab::make('Rincian Request')
                                ->schema([
                                    RepeatableEntry::make('reviewItems')
                                        ->hiddenLabel()
                                        ->state(function (Get $get) {
                                            $filteredRequestItems = collect(array_filter($get('requestItems'), function ($item) {
                                                return ! empty($item['coa_id']) && ! empty($item['item']) && ! empty($item['qty']) && ! empty($item['unit_qty']) && ! empty($item['base_price']);
                                            }))->groupBy('coa_id')
                                                ->map(function ($items, $coa_id) {
                                                    return [
                                                        'coa_name' => Coa::find($coa_id)->name,
                                                        'coa_request_amount' => $items->sum(function ($item) {
                                                            // Remove thousand separators and convert to float
                                                            return (float) str_replace(['.', ','], ['', '.'], $item['total_price']);
                                                        }),
                                                        'requestedItems' => $items->map(function ($item) {
                                                            return collect($item)->except('coa_id')->toArray();
                                                        })->values()->toArray(),
                                                    ];
                                                })
                                                ->values()
                                                ->toArray();

                                            return $filteredRequestItems;
                                        })
                                        ->schema([
                                            Flex::make([
                                                TextEntry::make('coa_name')
                                                    ->hiddenLabel(),
                                                TextEntry::make('coa_request_amount')
                                                    ->hiddenLabel()
                                                    ->money(currency: 'IDR', locale: 'id')
                                                    ->size(TextSize::Large)
                                                    ->weight(FontWeight::ExtraBold)
                                                    ->grow(false),
                                            ]),
                                            RepeatableEntry::make('requestedItems')
                                                ->hiddenLabel()
                                                ->contained(false)
                                                ->table([
                                                    TableColumn::make('Aktivitas')->width('250px'),
                                                    TableColumn::make('Deskripsi Item')->width('250px'),
                                                    TableColumn::make('Qty')->width('100px'),
                                                    TableColumn::make('Unit Qty')->width('150px'),
                                                    TableColumn::make('Harga/item')->width('200px'),
                                                    TableColumn::make('Total Harga')->width('250px'),
                                                    TableColumn::make('Nama Bank Tujuan')->width('200px'),
                                                    TableColumn::make('Nomor Rekening Tujuan')->width('200px'),
                                                    TableColumn::make('Nama Pemilik Rekening')->width('200px'),
                                                ])
                                                ->schema([
                                                    TextEntry::make('program_activity_id')
                                                        ->label('Aktivitas')
                                                        ->formatStateUsing(fn ($state) => ProgramActivity::find($state)->name)
                                                        ->placeholder('N/A'),
                                                    TextEntry::make('item')
                                                        ->label('Deskripsi Item'),
                                                    TextEntry::make('qty')
                                                        ->label('Qty'),
                                                    TextEntry::make('unit_qty')
                                                        ->label('Unit Qty'),
                                                    TextEntry::make('base_price')
                                                        ->formatStateUsing(fn ($state) => 'Rp '.$state)
                                                        ->label('Harga/item'),
                                                    TextEntry::make('total_price')
                                                        ->formatStateUsing(fn ($state) => 'Rp '.$state)
                                                        ->label('Total Harga'),
                                                    TextEntry::make('bank_name')
                                                        ->label('Nama Bank Tujuan'),
                                                    TextEntry::make('bank_account')
                                                        ->label('Nomor Rekening Tujuan'),
                                                    TextEntry::make('account_owner')
                                                        ->label('Nama Pemilik Rekening'),
                                                ])
                                                ->extraAttributes([
                                                    'class' => 'overflow-x-auto p-0.5 pb-1.5 *:first:table-fixed *:first:[&_thead]:[&_th]:first:w-[46px] *:first:table-fixed *:first:[&_thead]:[&_th]:last:w-[46px]',
                                                ]),
                                        ]),
                                ]),
                            Tab::make('Informasi Pemindahan Dana')
                                ->schema([
                                    RepeatableEntry::make('reviewTransferInstructions')
                                        ->hiddenLabel()
                                        ->state(function (Get $get) {
                                            $filteredRequestItems = collect(array_filter($get('requestItems'), function ($item) {
                                                return ! empty($item['coa_id']) && ! empty($item['item']) && ! empty($item['qty']) && ! empty($item['unit_qty']) && ! empty($item['base_price']);
                                            }))->groupBy('bank_account')
                                                ->map(function ($items, $bank_account) {
                                                    $firstItem = $items->first();

                                                    return [
                                                        'bank_account' => $bank_account,
                                                        'bank_name' => $firstItem['bank_name'],
                                                        'account_owner' => $firstItem['account_owner'],
                                                        'transfer_amount' => $items->sum(function ($item) {
                                                            return (float) str_replace(['.', ','], ['', '.'], $item['total_price']);
                                                        }),
                                                    ];
                                                })
                                                ->values()
                                                ->toArray();

                                            return $filteredRequestItems;
                                        })
                                        ->table([
                                            TableColumn::make('Nama Bank Tujuan'),
                                            TableColumn::make('Nomor Rekening Tujuan'),
                                            TableColumn::make('Nama Pemilik Rekening'),
                                            TableColumn::make('Nominal Transfer'),
                                        ])
                                        ->schema([
                                            TextEntry::make('bank_name')
                                                ->label('Nama Bank Tujuan'),
                                            TextEntry::make('bank_account')
                                                ->label('Nomor Rekening Tujuan'),
                                            TextEntry::make('account_owner')
                                                ->label('Nama Pemilik Rekening'),
                                            TextEntry::make('transfer_amount')
                                                ->money(currency: 'IDR', locale: 'id')
                                                ->label('Nominal Transfer'),
                                        ]),
                                ]),
                        ]),
                ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save_draft')
                ->label('Simpan sebagai Draft')
                ->action('saveAsDraft'),
            Action::make('export')
                ->label('Export Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function ($livewire) {
                    // dd($livewire->data);
                    $data = $livewire->data;
                    if (isset($data['requestItems'])) {
                        $data['requestItems'] = array_filter($data['requestItems'], function ($item) {
                            return ! empty($item['item']) || ! empty($item['qty']) || ! empty($item['unit_qty']) || ! empty($item['base_price']);
                        });
                    }
                    // dd($data);
                    $fileName = 'template_payment_request_'.now()->format('YmdHis').'.xlsx';

                    return Excel::download(
                        new DPRTemplateExport($data),
                        $fileName
                    );
                }),
            Action::make('import')
                ->label('Import Request Item')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->schema([
                    FileUpload::make('file')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                        ->multiple(false)
                        ->required(),
                ])
                ->action(function (array $data, $livewire) {
                    // dd($data, $livewire);
                    $file = storage_path('app/private/'.$data['file']);
                    $import = new DPRTemplateImport;
                    $importedPaymentRequestData = Excel::toArray($import, $file);

                    dd($importedPaymentRequestData);

                    $livewire->data = $importedPaymentRequestData;

                    Notification::make()
                        ->success()
                        ->title('Imported')
                        ->send();
                }),
        ];
    }

    public function saveAsDraft(): void
    {

        $data = $this->form->getRawState();

        if (isset($data['requestItems'])) {
            $data['requestItems'] = array_filter($data['requestItems'], function ($item) {
                return ! empty($item['coa_id']) || ! empty($item['program_activity_id']) || ! empty($item['item']) || ! empty($item['qty']) || ! empty($item['unit_qty']) || ! empty($item['base_price']);
            });
        }

        if (empty($data['requestItems'])) {
            Notification::make()
                ->title('Gagal menyimpan draft')
                ->body('Pastikan setidaknya ada 1 item request')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $dataDpr = [
            'requester_id' => Auth::user()->employee?->id,
            'status' => DPRStatus::Draft,
        ];

        $record = static::getModel()::create($dataDpr);

        foreach ($data['requestItems'] as $index => $item) {
            $itemData = [
                'daily_payment_request_id' => $record->id,
                'coa_id' => $item['coa_id'] ?? null,
                'program_activity_id' => $item['program_activity_id'] ?? null,
                'program_activity_item_id' => $item['program_activity_id']
                    ? ProgramActivityItem::whereProgramActivityId($item['program_activity_id'])
                        ->whereDescription($item['item'])
                        ->value('id')
                    : null,
                'payment_type' => RequestPaymentType::from($item['payment_type']),
                'description' => $item['item'] ?? null,
                'notes' => $item['notes'] ?? null,
                'quantity' => $item['payment_type'] === RequestPaymentType::Advance->value ? ((float) $item['qty']) ?? null : 0,
                'act_quantity' => $item['payment_type'] === RequestPaymentType::Reimburse->value ? ((float) $item['qty']) ?? null : 0,
                'unit_quantity' => $item['unit_qty'] ?? null,
                'amount_per_item' => $item['payment_type'] === RequestPaymentType::Advance->value ? ((float) str_replace(['.', ','], ['', '.'], $item['base_price'])) ?? null : 0,
                'act_amount_per_item' => $item['payment_type'] === RequestPaymentType::Reimburse->value ? ((float) str_replace(['.', ','], ['', '.'], $item['base_price'])) ?? null : 0,
                'self_account' => $item['self_account'],
                'bank_name' => $item['bank_name'] ?? null,
                'bank_account' => ((string) $item['bank_account']) ?? null,
                'account_owner' => $item['account_owner'] ?? null,
            ];

            $createdRequestItem = RequestItem::create($itemData);

            if (count($item['attachments']) > 0) {
                foreach ($item['attachments'] as $index => $attachment) {
                    $createdRequestItem->addMedia($attachment)->toMediaCollection('request_item_attachments', 'local');
                }
            }

            if (count($item['item_image']) > 0) {
                foreach ($item['item_image'] as $index => $attachment) {
                    $createdRequestItem->addMedia($attachment)->toMediaCollection('request_item_image', 'local');
                }
            }
        }

        $this->redirect(static::getResource()::getUrl('edit', ['record' => $record]));

        Notification::make()
            ->title('Draft saved successfully')
            ->success()
            ->send();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        // dd($data);
        if (isset($data['requestItems'])) {
            $data['requestItems'] = array_filter($data['requestItems'], function ($item) {
                return ! empty($item['coa_id']) && ! empty($item['item']) && ! empty($item['qty']) && ! empty($item['unit_qty']) && ! empty($item['base_price']);
            });
        }

        if (empty($data['requestItems'])) {
            Notification::make()
                ->title('Gagal : Formulir Kosong!')
                ->body('Pastikan setidaknya ada 1 item request')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        $data['request_date'] = now();
        $data['status'] = DPRStatus::Pending;
        $data['requester_id'] = Auth::user()->employee->id;

        foreach ($data['requestItems'] as $index => $item) {
            $itemData = [
                'coa_id' => $item['coa_id'] ?? null,
                'program_activity_id' => $item['program_activity_id'] ?? null,
                'program_activity_item_id' => $item['program_activity_id']
                    ? ProgramActivityItem::whereProgramActivityId($item['program_activity_id'])
                        ->whereDescription($item['item'])
                        ->value('id')
                    : null,
                'payment_type' => RequestPaymentType::from($item['payment_type']),
                'description' => $item['item'] ?? null,
                'notes' => $item['notes'] ?? null,
                'quantity' => $item['payment_type'] === RequestPaymentType::Advance->value ? $item['qty'] ?? null : 0,
                'act_quantity' => $item['payment_type'] === RequestPaymentType::Reimburse->value ? $item['qty'] ?? null : 0,
                'unit_quantity' => $item['unit_qty'] ?? null,
                'amount_per_item' => $item['payment_type'] === RequestPaymentType::Advance->value ? $item['base_price'] ?? null : 0,
                'act_amount_per_item' => $item['payment_type'] === RequestPaymentType::Reimburse->value ? $item['base_price'] ?? null : 0,
                'attachments' => $item['attachments'],
                'self_account' => $item['self_account'],
                'bank_name' => $item['bank_name'] ?? null,
                'bank_account' => (string) $item['bank_account'] ?? null,
                'account_owner' => $item['account_owner'] ?? null,
                'status' => RequestItemStatus::WaitingPayment,
            ];

            $this->requestItems[] = $itemData;
        }

        unset($data['requestItems']);

        // dd($data, $this->requestItems);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function afterCreate(): void
    {
        foreach ($this->requestItems as $index => $item) {
            $itemAttachments = $item['attachments'] ?? [];
            $itemImage = $item['item_image'] ?? [];
            unset($item['attachments'],$item['item_image']);

            $item['daily_payment_request_id'] = $this->record->id;

            $createdRequestItem = RequestItem::create($item);

            if (count($itemAttachments) > 0) {
                foreach ($itemAttachments as $index => $attachment) {
                    $createdRequestItem->addMedia($attachment)->toMediaCollection('request_item_attachments', 'local');
                }
            }
            if (count($itemImage) > 0) {
                foreach ($itemImage as $index => $image) {
                    $createdRequestItem->addMedia($image)->toMediaCollection('request_item_image', 'local');
                }
            }
        }

        $approvalService = app(ApprovalService::class);
        $approvalService->submitRequest($this->record);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Payment request created. Add items and submit for approval.';
    }
}

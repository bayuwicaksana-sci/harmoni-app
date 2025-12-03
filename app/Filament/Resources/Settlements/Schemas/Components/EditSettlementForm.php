<?php

use App\Enums\RequestItemStatus;
use App\Enums\RequestPaymentType;
use App\Models\Coa;
use App\Models\ProgramActivity;
use App\Models\ProgramActivityItem;
use App\Models\RequestItem;
use App\Models\SettlementReceipt;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Alignment;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditSettlementForm
{
    public static function make(): Repeater
    {
        return Repeater::make('settlementReceipts')
            ->relationship()
            ->label('Daftar Nota')
            ->addActionLabel('Tambahkan Nota Baru')
            ->collapsible()
            ->itemLabel('Nota ke - ')
            ->itemNumbers()
            ->columns(12)
            ->columnSpanFull()
            ->saveRelationshipsUsing(function (Repeater $component, Model $record, $state) {

                $relationship = $component->getRelationship();
                // Get the IDs from the current state
                $currentIds = collect($state)
                    ->pluck('id')
                    ->filter()
                    ->map(fn ($value) => $value === 'new' ? $value : (int) $value)
                    ->toArray();

                // Get existing IDs
                $existingIds = $relationship->pluck($relationship->getRelated()->getKeyName())->toArray();

                // Determine which records to detach (instead of delete)
                $idsToHandle = array_diff($existingIds, $currentIds);

                // dd($component, $record, $state, $currentIds, $existingIds, $idsToHandle);

                foreach ($idsToHandle as $index => $id) {
                    if (in_array($id, $existingIds)) {
                        $retrievedSettlementReceipt = SettlementReceipt::find($id);

                        $requestItems = $retrievedSettlementReceipt->requestItems;

                        foreach ($requestItems as $index => $item) {
                            if (! $item->is_unplanned) {
                                $item->clearMediaCollection('request_item_image');
                                $item->update([
                                    'act_quantity' => null,
                                    'act_amount_per_item' => null,
                                    'status' => RequestItemStatus::WaitingSettlement,
                                    'settlement_receipt_id' => null,
                                    'settlement_id' => null,
                                ]);
                            } else {
                                $item->forceDelete();
                            }

                        }

                        // $retrievedSettlementReceipt->clearMediaCollection('settlement_receipt_attachments');
                        $retrievedSettlementReceipt->delete();
                    }
                }
                // Handle creates and updates
                foreach ($state as $receiptIndex => $receiptData) {
                    if (isset($receiptData['id'])) {
                        $originalReceipt = SettlementReceipt::find($receiptData['id']);
                        if ($originalReceipt->realization_date !== $receiptData['realization_date']) {
                            $originalReceipt->update(['realization_date' => $receiptData['realization_date']]);
                            $originalReceipt->refresh();
                        }

                        foreach ($receiptData['attachment'] as $attIndex => $file) {
                            if ($file instanceof TemporaryUploadedFile) {
                                $originalReceipt->clearMediaCollection('settlement_receipt_attachments');
                                $originalReceipt->copyMedia($file)->toMediaCollection('settlement_receipt_attachments', 'local');

                                @unlink($file->getPathname());
                            }
                        }

                        Log::info("\n\n\n\nSettlement Receipt Id = ".(string) $receiptData['id']);

                        $currentItemIds = collect($receiptData['requestItems'])
                            ->pluck('id')
                            ->filter()
                            ->map(fn ($value) => $value === 'new' ? $value : (int) $value)
                            ->toArray();

                        // Get existing IDs
                        $existingItemIds = $originalReceipt->requestItems->pluck('id')->toArray();

                        // Determine which records to detach (instead of delete)
                        $itemIdsToHandle = array_diff($existingItemIds, $currentItemIds);

                        Log::info("\n\nCurrent Item IDs");
                        Log::info($currentItemIds);
                        Log::info("\n\nExisting Item IDs");
                        Log::info($existingItemIds);

                        foreach ($itemIdsToHandle as $index => $itemId) {
                            if (in_array($itemId, $existingItemIds)) {
                                Log::info("\n\nItem ID to Handle = ".(string) $itemId);
                                $retrievedRequestItem = RequestItem::find($itemId);
                                if (! $retrievedRequestItem->is_unplanned) {
                                    $retrievedRequestItem->act_quantity = null;
                                    $retrievedRequestItem->act_amount_per_item = null;
                                    $retrievedRequestItem->status = RequestItemStatus::WaitingSettlement;
                                    $retrievedRequestItem->settlement_id = null;
                                    $retrievedRequestItem->settlement_receipt_id = null;
                                    $retrievedRequestItem->save();

                                    $retrievedRequestItem->clearMediaCollection('request_item_image');
                                } else {
                                    $retrievedRequestItem->forceDelete();
                                }
                            }
                        }

                        foreach ($receiptData['requestItems'] as $itemDataIndex => $itemData) {
                            if (isset($itemData['id']) && $itemData['id'] !== 'new') {
                                $originalItem = RequestItem::find((int) $itemData['id']);

                                // $existingMedia = $originalItem->getMedia('request_item_image')->pluck('uuid', 'uuid')->toArray();

                                // dd($existingMedia, $itemData['item_image'], array_diff($existingMedia, $itemData['item_image']));

                                if (! $originalItem->settlement_id) {
                                    // dd($itemData['item_image']);
                                    foreach ($itemData['item_image'] ?? [] as $index => $file) {
                                        $originalItem->copyMedia($file)->toMediaCollection('request_item_image', 'local');

                                        @unlink($file->getPathname());
                                    }
                                } else {
                                    foreach ($itemData['item_image'] ?? [] as $index => $file) {
                                        if ($file instanceof TemporaryUploadedFile) {
                                            $originalItem->copyMedia($file)->toMediaCollection('request_item_image', 'local');

                                            @unlink($file->getPathname());
                                        }
                                    }
                                }

                                $originalItem->settlement_id = $record->id;
                                $originalItem->settlement_receipt_id = $originalReceipt->id;
                                $originalItem->act_quantity = (float) str_replace(['.', ','], ['', '.'], $itemData['act_quantity']);
                                $originalItem->act_amount_per_item = (float) str_replace(['.', ','], ['', '.'], $itemData['act_amount_per_item']);
                                $originalItem->status = (float) str_replace(['.', ','], ['', '.'], $itemData['act_quantity']) <= 0.00 || (float) str_replace(['.', ','], ['', '.'], $itemData['act_amount_per_item']) <= 0.00 ? RequestItemStatus::Cancelled : $originalItem->status;

                                $originalItem->save();

                                Log::info("\n\nUpdated Item of Existing Receipt ID = ".(string) $originalReceipt->id.' => '.(string) $originalItem->id);
                            } else {

                                $itemData['program_activity_id'] = $itemData['program_activity_id'] ?? null;
                                $itemData['program_activity_item_id'] = isset($itemData['program_activity_id']) || $itemData['program_activity_id'] !== '' || $itemData['program_activity_id'] !== null ? ProgramActivityItem::whereProgramActivityId($itemData['program_activity_id'])
                                    ->whereDescription($itemData['description'])
                                    ->value('id')
                                    : null;
                                $itemData['quantity'] = (float) str_replace(['.', ','], ['', '.'], $itemData['quantity']);
                                $itemData['amount_per_item'] = (float) str_replace(['.', ','], ['', '.'], $itemData['amount_per_item']);
                                $itemData['act_quantity'] = (float) str_replace(['.', ','], ['', '.'], $itemData['act_quantity']);
                                $itemData['act_amount_per_item'] = (float) str_replace(['.', ','], ['', '.'], $itemData['act_amount_per_item']);
                                $itemData['self_account'] = true;
                                $itemData['bank_name'] = Auth::user()->employee->bank_name;
                                $itemData['bank_account'] = Auth::user()->employee->bank_account_number;
                                $itemData['account_owner'] = Auth::user()->employee->bank_cust_name;
                                $itemData['is_unplanned'] = true;
                                $itemData['payment_type'] = RequestPaymentType::Reimburse;
                                $itemData['status'] = RequestItemStatus::Draft;
                                $itemData['settlement_id'] = $record->id;
                                $itemData['settlement_receipt_id'] = $originalReceipt->id;

                                $itemImage = $itemData['item_image'];

                                unset($itemData['item_image']);

                                $createdItem = RequestItem::create($itemData);

                                foreach ($itemImage ?? [] as $index => $file) {
                                    $createdItem->copyMedia($file)->toMediaCollection('request_item_image', 'local');
                                }

                                Log::info("\n\nCreated New Item of Existing Receipt Id = ".(string) $originalReceipt->id);
                                Log::info($itemData);

                            }
                        }
                    } else {
                        $createdReceipt = SettlementReceipt::create([
                            'realization_date' => $receiptData['realization_date'],
                            'settlement_id' => $record->id,
                        ]);

                        $createdReceipt = $createdReceipt->fresh();

                        Log::info("\n\nCreated New Receipt Id = ".(string) $createdReceipt->id);

                        foreach ($receiptData['attachment'] as $index => $file) {
                            $createdReceipt->copyMedia($file)->toMediaCollection('settlement_receipt_attachments');
                            @unlink($file->getPathname());
                        }

                        foreach ($receiptData['requestItems'] as $itemDataIndex => $itemData2) {
                            if (isset($itemData2['id']) && $itemData2['id'] !== 'new') {
                                $originalItem2 = RequestItem::find((int) $itemData2['id']);

                                // $existingMedia = $originalItem2->getMedia('request_item_image')->pluck('uuid', 'uuid')->toArray();

                                // dd($existingMedia, $itemData2['item_image'], array_diff($existingMedia, $itemData2['item_image']));

                                if (! $originalItem2->settlement_id) {
                                    // dd($itemData2['item_image']);
                                    foreach ($itemData2['item_image'] ?? [] as $index => $file) {
                                        $originalItem2->copyMedia($file)->toMediaCollection('request_item_image', 'local');

                                        // @unlink($file->getPathname());
                                    }
                                }

                                $originalItem2->settlement_id = $record->id;
                                $originalItem2->settlement_receipt_id = $createdReceipt->id;
                                $originalItem2->act_quantity = (float) str_replace(['.', ','], ['', '.'], $itemData2['act_quantity']);
                                $originalItem2->act_amount_per_item = (float) str_replace(['.', ','], ['', '.'], $itemData2['act_amount_per_item']);
                                $originalItem2->status = (float) str_replace(['.', ','], ['', '.'], $itemData2['act_quantity']) <= 0.00 || (float) str_replace(['.', ','], ['', '.'], $itemData2['act_amount_per_item']) <= 0.00 ? RequestItemStatus::Cancelled : $originalItem2->status;

                                $originalItem2->save();

                                Log::info("\n\nUpdated Item of New Receipt ID = ".(string) $createdReceipt->id.' => '.(string) $originalItem2->id);

                            } else {
                                $itemData2['program_activity_id'] = (int) $itemData2['program_activity_id'] ?? null;
                                $itemData2['program_activity_item_id'] = isset($itemData2['program_activity_id']) || $itemData2['program_activity_id'] !== '' || $itemData2['program_activity_id'] !== null ? ProgramActivityItem::whereProgramActivityId((int) $itemData2['program_activity_id'])
                                    ->whereDescription($itemData2['description'])
                                    ->value('id')
                                    : null;
                                $itemData2['quantity'] = (float) str_replace(['.', ','], ['', '.'], $itemData2['quantity']);
                                $itemData2['amount_per_item'] = (float) str_replace(['.', ','], ['', '.'], $itemData2['amount_per_item']);
                                $itemData2['act_quantity'] = (float) str_replace(['.', ','], ['', '.'], $itemData2['act_quantity']);
                                $itemData2['act_amount_per_item'] = (float) str_replace(['.', ','], ['', '.'], $itemData2['act_amount_per_item']);
                                $itemData2['self_account'] = true;
                                $itemData2['bank_name'] = Auth::user()->employee->bank_name;
                                $itemData2['bank_account'] = Auth::user()->employee->bank_account_number;
                                $itemData2['account_owner'] = Auth::user()->employee->bank_cust_name;
                                $itemData2['is_unplanned'] = true;
                                $itemData2['payment_type'] = RequestPaymentType::Reimburse;
                                $itemData2['status'] = RequestItemStatus::Draft;
                                $itemData2['settlement_id'] = $record->id;
                                $itemData2['settlement_receipt_id'] = $createdReceipt->id;

                                $itemImage = $itemData2['item_image'];

                                unset($itemData2['item_image']);

                                $createdItem = RequestItem::create($itemData2);

                                foreach ($itemImage ?? [] as $index => $file) {
                                    $createdItem->copyMedia($file)->toMediaCollection('request_item_image', 'local');
                                }

                                Log::info("\n\nCreated New Item of New Receipt ID = ".(string) $createdReceipt->id);
                                Log::info($itemData2);

                            }
                        }
                    }

                }
            })
            ->schema([
                SpatieMediaLibraryFileUpload::make('attachment')
                    ->required()
                    ->validationMessages([
                        'required' => 'Eits, jangan lupa upload nota ya!',
                    ])
                    ->collection('settlement_receipt_attachments')
                    ->dehydrated(true)
                    ->storeFiles(false)
                    ->label('Upload Nota')
                    ->openable(true)
                    ->multiple(false)
                    ->columnSpan(6),
                DatePicker::make('realization_date')
                    ->required()
                    ->native(false)
                    ->label('Tanggal Realisasi')
                    ->belowLabel('Sesuai Nota')
                    ->displayFormat('j M Y')
                    ->columnSpan(3),
                Repeater::make('requestItems')
                    ->relationship()
                    ->label('Pilih Item Request')
                    ->addActionLabel('Tambah Item Request')
                    ->compact()
                    ->addActionAlignment(Alignment::Start)
                    ->columnSpanFull()
                    ->saveRelationshipsUsing(function () {})
                    // ->saveRelationshipsUsing(function (Repeater $component, Model $record, $state, $rawState) {

                    //     $relationship = $component->getRelationship();
                    //     // Get the IDs from the current state
                    //     $currentIds = collect($state)
                    //         ->pluck('id')
                    //         ->filter()
                    //         ->map(fn ($value) => $value === 'new' ? $value : (int) $value)
                    //         ->toArray();

                    //     // Get existing IDs
                    //     $existingIds = $relationship->pluck($relationship->getRelated()->getKeyName())->toArray();

                    //     // Determine which records to detach (instead of delete)
                    //     $idsToHandle = array_diff($existingIds, $currentIds);

                    //     // dd($currentIds, $existingIds, $idsToHandle);
                    //     foreach ($idsToHandle as $index => $id) {
                    //         if (in_array($id, $existingIds)) {
                    //             $retrievedRequestItem = RequestItem::find($id);
                    //             if (! $retrievedRequestItem->is_unplanned) {
                    //                 $retrievedRequestItem->act_quantity = null;
                    //                 $retrievedRequestItem->act_amount_per_item = null;
                    //                 $retrievedRequestItem->status = RequestItemStatus::WaitingSettlement;
                    //                 $retrievedRequestItem->settlement_id = null;
                    //                 $retrievedRequestItem->settlement_receipt_id = null;
                    //                 $retrievedRequestItem->save();

                    //                 $retrievedRequestItem->clearMediaCollection('request_item_image');
                    //             } else {
                    //                 $retrievedRequestItem->forceDelete();
                    //             }
                    //         }
                    //     }
                    //     // Handle creates and updates
                    //     foreach ($state as $itemData) {
                    //         if (isset($itemData['id']) && $itemData['id'] !== 'new') {
                    //             $originalItem = RequestItem::find((int) $itemData['id']);

                    //             // $existingMedia = $originalItem->getMedia('request_item_image')->pluck('uuid', 'uuid')->toArray();

                    //             // dd($existingMedia, $itemData['item_image'], array_diff($existingMedia, $itemData['item_image']));

                    //             if (! $originalItem->settlement_id) {
                    //                 // dd($itemData['item_image']);
                    //                 foreach ($itemData['item_image'] ?? [] as $index => $file) {
                    //                     $originalItem->copyMedia($file)->toMediaCollection('request_item_image', 'local');

                    //                     // @unlink($file->getPathname());
                    //                 }
                    //             }

                    //             $originalItem->settlement_id = $record->settlement_id;
                    //             $originalItem->settlement_receipt_id = $record->id;
                    //             $originalItem->act_quantity = (float) str_replace(['.', ','], ['', '.'], $itemData['act_quantity']);
                    //             $originalItem->act_amount_per_item = (float) str_replace(['.', ','], ['', '.'], $itemData['act_amount_per_item']);
                    //             $originalItem->status = (float) str_replace(['.', ','], ['', '.'], $itemData['act_quantity']) <= 0.00 || (float) str_replace(['.', ','], ['', '.'], $itemData['act_amount_per_item']) <= 0.00 ? RequestItemStatus::Cancelled : $originalItem->status;

                    //             $originalItem->save();

                    //         } else {
                    //             $itemData['program_activity_id'] = $itemData['program_activity_id'] ?? null;
                    //             $itemData['program_activity_item_id'] = isset($itemData['program_activity_id']) || $itemData['program_activity_id'] !== '' || $itemData['program_activity_id'] !== null ? ProgramActivityItem::whereProgramActivityId((int) $itemData['program_activity_id'])
                    //                 ->whereDescription($itemData['description'])
                    //                 ->value('id')
                    //                 : null;
                    //             $itemData['quantity'] = (float) str_replace(['.', ','], ['', '.'], $itemData['quantity']);
                    //             $itemData['amount_per_item'] = (float) str_replace(['.', ','], ['', '.'], $itemData['amount_per_item']);
                    //             $itemData['act_quantity'] = (float) str_replace(['.', ','], ['', '.'], $itemData['act_quantity']);
                    //             $itemData['act_amount_per_item'] = (float) str_replace(['.', ','], ['', '.'], $itemData['act_amount_per_item']);
                    //             $itemData['self_account'] = true;
                    //             $itemData['bank_name'] = Auth::user()->employee->bank_name;
                    //             $itemData['bank_account'] = Auth::user()->employee->bank_account_number;
                    //             $itemData['account_owner'] = Auth::user()->employee->bank_cust_name;
                    //             $itemData['is_unplanned'] = true;
                    //             $itemData['payment_type'] = RequestPaymentType::Reimburse;
                    //             $itemData['status'] = RequestItemStatus::Draft;
                    //             $itemData['settlement_id'] = $record->settlement_id;

                    //             $itemImage = $itemData['item_image'];

                    //             unset($itemData['item_image']);

                    //             $createdItem = $relationship->create($itemData);

                    //             foreach ($itemImage ?? [] as $index => $file) {
                    //                 $createdItem->copyMedia($file)->toMediaCollection('request_item_image', 'local');
                    //             }

                    //         }
                    //     }
                    // })
                    ->extraAttributes([
                        'class' => 'overflow-x-auto p-0.5 pb-1.5 *:first:table-fixed *:first:[&_thead]:[&_th]:first:w-[46px] *:first:table-fixed *:first:[&_thead]:[&_th]:last:w-[46px]',
                    ])
                    ->table([
                        TableColumn::make('Request Item')->width('300px'),
                        // TableColumn::make('Request ID')->width('250px'),
                        TableColumn::make('Terealisasi ?')->width('150px'),
                        TableColumn::make('COA')->width('300px'),
                        TableColumn::make('Aktivitas')->width('300px'),
                        TableColumn::make('Item')->width('300px'),
                        TableColumn::make('Qty (Request)')->width('150px'),
                        TableColumn::make('Qty (Aktual)')->width('150px'),
                        TableColumn::make('Unit Qty')->width('175px'),
                        TableColumn::make('Harga/item (Request)')->width('250px'),
                        TableColumn::make('Harga/item (Aktual)')->width('250px'),
                        TableColumn::make('Total Request')->width('250px'),
                        TableColumn::make('Total Aktual')->width('250px'),
                        TableColumn::make('Variasi')->width('250px'),
                        TableColumn::make('Foto Item/Produk')->width('350px'),
                    ])
                    ->schema([
                        Select::make('id')
                            ->label('Pilih Item Request')
                            ->required()
                            ->validationMessages([
                                'required_with' => 'Item wajib dipilih',
                            ])
                            ->native(true)
                            ->live()
                            ->disabled(fn ($state, Get $get) => $state !== 'new' && $state !== null && $get('settlement_receipt_id') !== null)
                            ->dehydrated(true)
                    // ->partiallyRenderComponentsAfterStateUpdated(['request_quantity', 'request_unit_quantity', 'request_amount_per_item'])
                            ->options(function ($record) {
                                $options = RequestItem::query()
                                    ->whereHas('dailyPaymentRequest', fn (Builder $query) => $query->where('requester_id', Auth::user()->employee->id))
                                    ->where('request_items.status', '=', RequestItemStatus::WaitingSettlement->value)
                                    ->orWhere('request_items.status', '=', RequestItemStatus::WaitingSettlementReview->value)
                                    ->orWhere('request_items.status', '=', RequestItemStatus::Cancelled->value)
                                    ->orWhere('request_items.status', '=', RequestItemStatus::WaitingRefund->value)
                                    ->join('daily_payment_requests', 'request_items.daily_payment_request_id', '=', 'daily_payment_requests.id')
                                    ->get(['request_items.id', 'request_items.description', 'daily_payment_requests.request_number as daily_payment_request_number'])
                                    ->groupBy('daily_payment_request_number')
                                    ->map(fn ($items) => $items->pluck('description', 'id'))
                                    ->toArray();

                                $draftItems = [];
                                if ($record) {
                                    $draftItems = RequestItem::query()->where('settlement_receipt_id', $record->settlement_receipt_id)->where('daily_payment_request_id', '=', null)->get()->pluck('description', 'id')->toArray();

                                }

                                $options = [
                                    'new' => 'Item Baru',
                                    'DRAFT' => $draftItems,
                                    ...$options,

                                ];

                                return $options;
                            })
                            ->disableOptionWhen(function ($value, $state, Get $get) {
                                $parentData = $get('../../../../') ?? [];
                                $allReceipts = $parentData['settlementReceipts'] ?? [];

                                $selectedIds = collect($allReceipts)
                                    ->pluck('requestItems.*.id')
                                    ->flatten()
                                    ->filter(fn ($value) => $value !== 'new')
                                    ->all();

                                return ($value === 'new' || $state === 'new') ? false : in_array($value, $selectedIds) && $value !== $state;
                            })
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $parseMoney = fn ($val) => (float) str_replace(['.', ','], ['', '.'], $val ?? '0');
                                $formatMoney = fn ($num) => number_format($num, 2, ',', '.');

                                $currentRequestTotal = 0;

                                if ($state && $state !== 'new') {
                                    $requestItem = RequestItem::with(['dailyPaymentRequest:id,request_number', 'coa:id,name', 'programActivity:id,name'])
                                        ->where('id', $state)
                                        ->first(['settlement_receipt_id', 'quantity', 'unit_quantity', 'amount_per_item', 'daily_payment_request_id', 'coa_id', 'program_activity_id', 'description']);

                                    $set('coa_id', $requestItem->coa_id);
                                    $set('program_activity_id', $requestItem->program_activity_id);
                                    $set('description', $requestItem->description);
                                    $set('quantity', (int) $requestItem->quantity);
                                    $set('unit_quantity', $requestItem->unit_quantity);
                                    $set('amount_per_item', $formatMoney($requestItem->amount_per_item));
                                    $set('settlement_receipt_id', $requestItem->settlement_receipt_id);

                                    $currentRequestTotal = $requestItem->quantity * $requestItem->amount_per_item;
                                    $set('request_total_price', $formatMoney($currentRequestTotal));
                                } elseif ($state === 'new') {
                                    $set('coa_id', null);
                                    $set('program_activity_id', null);
                                    $set('description', null);
                                    $set('is_realized', true);
                                    $set('quantity', 0);
                                    $set('act_quantity', 0);
                                    $set('unit_quantity', null);
                                    $set('amount_per_item', '0,00');
                                    $set('act_amount_per_item', '0,00');
                                    $set('request_total_price', '0,00');
                                    $set('settlement_receipt_id', null);
                                } else {
                                    $set('coa_id', null);
                                    $set('program_activity_id', null);
                                    $set('description', null);
                                    $set('quantity', null);
                                    $set('unit_quantity', null);
                                    $set('amount_per_item', null);
                                    $set('request_total_price', null);
                                    $set('settlement_receipt_id', null);
                                }

                                // Recalculate financial summary
                                // $rootData = $get('../../../../') ?? [];
                                // $receipts = $rootData['settlementReceipts'] ?? [];

                                // $approvedAmount = 0;
                                // $cancelledAmount = 0;
                                // $spentAmount = 0;

                                // foreach ($receipts as $receipt) {
                                //     foreach ($receipt['requestItems'] ?? [] as $item) {
                                //         // Check if this is current item
                                //         $isCurrentItem = ($item['id'] ?? null) === $state;

                                //         $requestTotal = $isCurrentItem ? $currentRequestTotal : $parseMoney($item['request_total_price'] ?? '0');
                                //         $approvedAmount += $requestTotal;

                                //         $isRealized = $item['is_realized'] ?? true;
                                //         if (! $isRealized) {
                                //             $cancelledAmount += $requestTotal;
                                //         } else {
                                //             $spentAmount += $parseMoney($item['actual_total_price'] ?? '0');
                                //         }
                                //     }

                                //     // // new_request_items only affects spent_amount
                                //     // foreach ($receipt['new_request_items'] ?? [] as $item) {
                                //     //     $totalPrice = $parseMoney($item['total_price'] ?? '0');
                                //     //     $spentAmount += $totalPrice;
                                //     // }
                                // }

                                // $variance = $approvedAmount - $spentAmount;

                                // $set('../../../../approved_request_amount', $formatMoney($approvedAmount));
                                // $set('../../../../cancelled_amount', $formatMoney($cancelledAmount));
                                // $set('../../../../spent_amount', $formatMoney($spentAmount));
                                // $set('../../../../variance', $formatMoney($variance));
                            }),
                        Checkbox::make('is_realized')
                            ->label('Terealisasi?')
                            ->default(true)
                            ->dehydrated(false)
                            ->formatStateUsing(fn (Get $get) => $get('id') !== 'new' || $get('id') !== null ? true : $get('act_quantity') > 0)
                            ->disabled(fn (Get $get) => $get('id') === 'new')
                            ->inline(false)
                            ->extraAttributes(
                                [
                                    'class' => 'mx-auto',
                                ]
                            )
                            ->live()
                            ->afterStateUpdatedJs(
                                <<<'JS'
                                            const formatMoney = (num) => {
                                                if (num === 0) return '0,00';
                                                const isNegative = num < 0;
                                                const absNum = Math.abs(num);
                                                const formatted = absNum.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                                return isNegative ? '-' + formatted : formatted;
                                            };
                                            const parseMoney = (val) => parseFloat((val ?? '0').toString().replace(/\./g, '').replace(',', '.')) || 0;
                                            
                                            // Set values first
                                            if (!$state) {
                                                $set('act_quantity', 0);
                                                $set('act_amount_per_item', '0');
                                                $set('actual_total_price', '0');
                                                const reqTotal = parseMoney($get('request_total_price'));
                                                $set('variance', formatMoney(0 - reqTotal));
                                            } else {
                                                const reqTotal = parseMoney($get('request_total_price'));
                                                const actTotal = parseMoney($get('actual_total_price')) ?? 0;
                                                $set('variance', formatMoney(actTotal - reqTotal));
                                            }
                                            
                                            // Store current item's known values for calculation
                                            // const currentRequestTotal = parseMoney($get('request_total_price'));
                                            // const currentActualTotal = $state ? parseMoney($get('actual_total_price')) : 0;
                                            // const currentIsRealized = $state;
                                            // const currentItemId = $get('request_item_id');
                                            
                                            // // Recalculate financial summary
                                            // const receipts = $get('../../../../settlementReceipts') ?? {};
                                            
                                            // let approvedAmount = 0;
                                            // let cancelledAmount = 0;
                                            // let spentAmount = 0;
                                            
                                            // Object.values(receipts).forEach(receipt => {
                                            //     const requestItems = receipt?.request_items ?? {};
                                            //     Object.values(requestItems).forEach(item => {
                                            //         const itemRequestItemId = item?.request_item_id;
                                            //         const requestTotal = parseMoney(item?.request_total_price);
                                                    
                                            //         // Check if this is the current item being edited
                                            //         const isCurrentItem = itemRequestItemId && itemRequestItemId === currentItemId;
                                                    
                                            //         approvedAmount += requestTotal;
                                                    
                                            //         // Use known values for current item, otherwise use stored values
                                            //         const isRealized = isCurrentItem 
                                            //             ? currentIsRealized 
                                            //             : (item?.is_realized !== false && item?.is_realized !== 0 && item?.is_realized !== '0' && item?.is_realized !== null);
                                            //         const actualTotal = isCurrentItem ? currentActualTotal : parseMoney(item?.actual_total_price);
                                                    
                                            //         if (!isRealized) {
                                            //             cancelledAmount += requestTotal;
                                            //         } else {
                                            //             spentAmount += actualTotal;
                                            //         }
                                            //     });
                                                
                                            //     // new_request_items only affects spent_amount
                                            //     const newRequestItems = receipt?.new_request_items ?? {};
                                            //     Object.values(newRequestItems).forEach(item => {
                                            //         const totalPrice = parseMoney(item?.total_price);
                                            //         spentAmount += totalPrice;
                                            //     });
                                            // });
                                            
                                            // const variance =  approvedAmount - spentAmount;
                                            
                                            // $set('../../../../approved_request_amount', formatMoney(approvedAmount));
                                            // $set('../../../../cancelled_amount', formatMoney(cancelledAmount));
                                            // $set('../../../../spent_amount', formatMoney(spentAmount));
                                            // $set('../../../../variance', formatMoney(variance));
                                        JS
                            ),
                        Select::make('coa_id')
                            ->label('COA')
                            ->disabled(fn (Get $get) => $get('id') !== 'new')
                            ->dehydrated(fn (Get $get) => $get('id') === 'new')
                            ->options(Coa::query()->pluck('name', 'id'))
                            ->native(true)
                            ->live(),
                        // ->afterStateUpdatedJs(
                        //     <<<'JS'
                        //         $set('program_activity_id', null);
                        //     JS
                        // ),
                        Select::make('program_activity_id')
                            ->label('Aktivitas')
                            ->options(fn (Get $get) => $get('coa_id') ? ProgramActivity::query()->whereCoaId($get('coa_id'))->pluck('name', 'id') : ProgramActivity::query()->pluck('name', 'id'))
                            ->live()
                            ->native(true)
                            ->preload()
                            ->disabled(fn (Get $get) => $get('coa_id') === null || $get('id') !== 'new')
                            ->dehydrated(fn (Get $get) => $get('id') === 'new'),
                        TextInput::make('description')
                            ->required()
                            ->disabled(fn (Get $get) => $get('id') !== 'new')
                            ->dehydrated(fn (Get $get) => $get('id') === 'new')
                            ->validationMessages([
                                'required' => 'Deskripsi item wajib diisi',
                            ])
                            ->datalist(fn (Get $get) => ProgramActivityItem::query()->whereProgramActivityId($get('program_activity_id'))->pluck('description')->toArray())
                            ->live(debounce: 500),
                        TextInput::make('quantity')
                            ->readOnly()
                            ->dehydrated(fn (Get $get) => $get('id') === 'new')
                            ->numeric(),
                        // ->afterStateUpdatedJs(
                        //     <<<'JS'
                        //         const basePrice = ($get('amount_per_item') ?? '0').toString().replace(/\./g, '').replace(',', '.');
                        //         const basePriceNum = parseFloat(basePrice) || 0;
                        //         const qtyNum = parseFloat($state) || 0;
                        //         const total = qtyNum * basePriceNum;

                        //         if (total === 0) {
                        //             $set('request_total_price', '');
                        //         } else {
                        //             const formatted = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                        //             $set('request_total_price', formatted);
                        //         }
                        //     JS
                        // ),
                        TextInput::make('act_quantity')
                            ->label('Harga/item (Aktual)')
                            ->requiredWith('id,act_amount_per_item')
                            ->validationMessages([
                                'required_with' => 'Jumlah Aktual wajib diisi',
                            ])
                            ->numeric()
                            ->readOnly(fn (Get $get) => ! $get('is_realized'))
                            ->afterStateUpdatedJs(<<<'JS'
                                    const formatMoney = (num) => {
                                        if (num === 0) return '0,00';
                                        const isNegative = num < 0;
                                        const absNum = Math.abs(num);
                                        const formatted = absNum.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                        return isNegative ? '-' + formatted : formatted;
                                    };
                                    const parseMoney = (val) => parseFloat((val ?? '0').toString().replace(/\./g, '').replace(',', '.')) || 0;
                                    
                                    // Calculate actual_total_price
                                    const basePriceNum = parseMoney($get('act_amount_per_item'));
                                    const qtyNum = parseFloat($state) || 0;
                                    const total = qtyNum * basePriceNum;

                                    $set('actual_total_price', formatMoney(total));
                                    
                                    // Calculate item variance
                                    // const requestTotal = parseMoney($get('request_total_price'));
                                    // $set('variance', formatMoney(requestTotal - total));
                                    
                                    // // Store current item's known values
                                    // const currentActualTotal = total;
                                    // const currentItemId = $get('request_item_id');
                                    
                                    // // Recalculate financial summary
                                    // const receipts = $get('../../../../settlementReceipts') ?? {};
                                    
                                    // let approvedAmount = 0;
                                    // let cancelledAmount = 0;
                                    // let spentAmount = 0;
                                    
                                    // Object.values(receipts).forEach(receipt => {
                                    //     const requestItems = receipt?.request_items ?? {};
                                    //     Object.values(requestItems).forEach(item => {
                                    //         const itemRequestItemId = item?.request_item_id;
                                    //         const reqTotal = parseMoney(item?.request_total_price);
                                            
                                    //         const isCurrentItem = itemRequestItemId && itemRequestItemId === currentItemId;
                                            
                                    //         approvedAmount += reqTotal;
                                            
                                    //         const isRealized = item?.is_realized !== false && item?.is_realized !== 0 && item?.is_realized !== '0' && item?.is_realized !== null;
                                    //         const actualTotal = isCurrentItem ? currentActualTotal : parseMoney(item?.actual_total_price);
                                            
                                    //         if (!isRealized) {
                                    //             cancelledAmount += reqTotal;
                                    //         } else {
                                    //             spentAmount += actualTotal;
                                    //         }
                                    //     });
                                        
                                    //     const newRequestItems = receipt?.new_request_items ?? {};
                                    //     Object.values(newRequestItems).forEach(item => {
                                    //         const totalPrice = parseMoney(item?.total_price);
                                    //         spentAmount += totalPrice;
                                    //     });
                                    // });
                                    
                                    // const summaryVariance =  approvedAmount - spentAmount;
                                    
                                    // $set('../../../../approved_request_amount', formatMoney(approvedAmount));
                                    // $set('../../../../cancelled_amount', formatMoney(cancelledAmount));
                                    // $set('../../../../spent_amount', formatMoney(spentAmount));
                                    // $set('../../../../variance', formatMoney(summaryVariance));
                                JS),
                        TextInput::make('unit_quantity')
                            ->readOnly(fn (Get $get) => $get('id') !== 'new')
                            ->dehydrated(fn (Get $get) => $get('id') === 'new'),
                        TextInput::make('amount_per_item')
                            ->prefix('Rp')
                            ->readOnly()
                            ->dehydrated(fn (Get $get) => $get('id') === 'new')
                            ->formatStateUsing(fn ($state) => $state ? (float) $state : null)
                            ->dehydrateStateUsing(fn ($rawState) => (float) str_replace(['.', ','], ['', '.'], $rawState))
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)')),
                        // ->afterStateUpdatedJs(
                        //     <<<'JS'
                        //         const cleanPrice = ($state ?? '0').toString().replace(/\./g, '').replace(',', '.');
                        //         const priceNum = parseFloat(cleanPrice) || 0;
                        //         const qtyNum = parseFloat($get('quantity')) || 0;
                        //         const total = qtyNum * priceNum;

                        //         if (total === 0) {
                        //             $set('request_total_price', '');
                        //         } else {
                        //             const formatted = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                        //             $set('request_total_price', formatted);
                        //         }
                        //     JS
                        // ),
                        TextInput::make('act_amount_per_item')
                            ->requiredWith('id,act_quantity')
                            ->readOnly(fn (Get $get) => ! $get('is_realized'))
                            ->validationMessages([
                                'required_with' => 'Harga per item wajib diisi',
                            ])
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                            ->dehydrateStateUsing(fn ($rawState) => (float) str_replace(['.', ','], ['', '.'], $rawState))
                            ->stripCharacters(['.', ','])
                            ->afterStateUpdatedJs(
                                <<<'JS'
                                            const formatMoney = (num) => {
                                                if (num === 0) return '0,00';
                                                const isNegative = num < 0;
                                                const absNum = Math.abs(num);
                                                const formatted = absNum.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                                return isNegative ? '-' + formatted : formatted;
                                            };
                                            const parseMoney = (val) => parseFloat((val ?? '0').toString().replace(/\./g, '').replace(',', '.')) || 0;
                                            
                                            // Calculate actual_total_price
                                            const priceNum = parseMoney($state);
                                            const qtyNum = parseFloat($get('act_quantity')) || 0;
                                            const total = qtyNum * priceNum;

                                            $set('actual_total_price', formatMoney(total));
                                            
                                            // Calculate item variance
                                            const requestTotal = parseMoney($get('request_total_price') ?? 0);
                                            $set('variance', formatMoney(requestTotal - total));
                                            
                                            // Store current item's known values
                                            // const currentActualTotal = total;
                                            // const currentItemId = $get('request_item_id');
                                            
                                            // // Recalculate financial summary
                                            // const receipts = $get('../../../../settlementReceipts') ?? {};
                                            
                                            // let approvedAmount = 0;
                                            // let cancelledAmount = 0;
                                            // let spentAmount = 0;
                                            
                                            // Object.values(receipts).forEach(receipt => {
                                            //     const requestItems = receipt?.request_items ?? {};
                                            //     Object.values(requestItems).forEach(item => {
                                            //         const itemRequestItemId = item?.request_item_id;
                                            //         const reqTotal = parseMoney(item?.request_total_price);
                                                    
                                            //         const isCurrentItem = itemRequestItemId && itemRequestItemId === currentItemId;
                                                    
                                            //         approvedAmount += reqTotal;
                                                    
                                            //         const isRealized = item?.is_realized !== false && item?.is_realized !== 0 && item?.is_realized !== '0' && item?.is_realized !== null;
                                            //         const actualTotal = isCurrentItem ? currentActualTotal : parseMoney(item?.actual_total_price);
                                                    
                                            //         if (!isRealized) {
                                            //             cancelledAmount += reqTotal;
                                            //         } else {
                                            //             spentAmount += actualTotal;
                                            //         }
                                            //     });
                                                
                                            //     const newRequestItems = receipt?.new_request_items ?? {};
                                            //     Object.values(newRequestItems).forEach(item => {
                                            //         const totalPrice = parseMoney(item?.total_price);
                                            //         spentAmount += totalPrice;
                                            //     });
                                            // });
                                            
                                            // const summaryVariance =  approvedAmount - spentAmount;
                                            
                                            // $set('../../../../approved_request_amount', formatMoney(approvedAmount));
                                            // $set('../../../../cancelled_amount', formatMoney(cancelledAmount));
                                            // $set('../../../../spent_amount', formatMoney(spentAmount));
                                            // $set('../../../../variance', formatMoney(summaryVariance));
                                        JS
                            ),
                        TextInput::make('request_total_price')
                            ->label('Total Request')
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                            ->readOnly()
                            ->dehydrated(false)
                            ->formatStateUsing(fn (Get $get) => number_format($get('amount_per_item') * $get('quantity'), 2, ',', '.')),
                        TextInput::make('actual_total_price')
                            ->label('Total Aktual')
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                            ->readOnly()
                            ->dehydrated(false)
                            ->afterStateUpdatedJs(<<<'JS'
                                            const cleanActualTotalPrice = ($state ?? 0).toString().replace(/\./g, '').replace(',', '.');
                                            const cleanRequestTotalPrice = ($get('request_total_price') ?? '0').toString().replace(/\./g, '').replace(',', '.');

                                            $set('variance', (parseFloat(cleanRequestTotalPrice) - parseFloat(cleanActualTotalPrice)).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1'))
                                        JS),
                        TextInput::make('variance')
                            ->label('Selisih')
                            ->prefix('Rp')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                            ->readOnly()
                            ->dehydrated(false),
                        SpatieMediaLibraryFileUpload::make('item_image')
                            ->collection('request_item_image')
                            ->label('Foto Item/Produk')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'application/pdf'])
                            ->multiple()
                            ->appendFiles()
                            ->maxSize(4096)
                            ->columnSpanFull()
                            // ->dehydrated(function (Get $get) {
                            //     return $get('id') === 'new' || ($get('id') !== null && RequestItem::find((int) $get('id'))->settlement_receipt_id === null);
                            // })
                            ->dehydrated(true)
                            ->storeFiles(false)
                            ->previewable(false)
                            ->openable(true)
                            ->required()
                            ->validationMessages([
                                'required' => 'Foto Item/Produk diperlukan untuk Settlement',
                            ]),
                    ]),

            ]);
    }
}

<?php

namespace App\Filament\Resources\DailyPaymentRequests\RelationManagers;

use App\Enums\RequestItemStatus;
use App\Enums\RequestPaymentType;
use App\Enums\TaxMethod;
use App\Models\Coa;
use App\Models\ProgramActivity;
use App\Models\ProgramActivityItem;
use App\Models\RequestItem;
use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RequestItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'requestItems';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->schema([
                        Select::make('coa_id')
                            ->label('COA')
                            ->options(Coa::query()->pluck('name', 'id'))
                            ->native(false)
                            ->live()
                            ->afterStateUpdatedJs(
                                <<<'JS'
                                $set('program_activity_id', null);
                                JS
                            )
                            ->columnSpan(4),
                        Select::make('program_activity_id')
                            ->label('Aktivitas')
                            ->options(fn (Get $get) => ProgramActivity::query()->whereCoaId($get('coa_id'))->pluck('name', 'id'))
                            ->live()
                            ->native(false)
                            ->preload()
                            ->searchable()
                            ->disabled(fn (Get $get) => $get('coa_id') === null)
                            ->columnSpan(4),
                        TextInput::make('description')
                            ->label('Deskripsi Item')
                            ->trim()
                            ->datalist(fn (Get $get) => ProgramActivityItem::query()->whereProgramActivityId($get('program_activity_id'))->limit(10)->pluck('description', 'id')->toArray())
                            ->columnSpan(4)
                            ->live(debounce: 500),
                        TextInput::make('quantity')
                            ->label('Qty')
                            ->columnSpan(1)
                            ->hidden(fn (Get $get) => $get('payment_type') === RequestPaymentType::Reimburse->value || $get('payment_type') === RequestPaymentType::Offset->value)
                            ->dehydrated(true)
                            ->numeric()
                            ->minValue(1)
                            ->placeholder(function (Get $get) {
                                $activityItem = $get('program_activity_id') ? ProgramActivityItem::whereProgramActivityId($get('program_activity_id'))->whereDescription($get('description'))->get(['volume'])->first() : null;

                                return $activityItem ? $activityItem->volume : null;
                            })
                            ->afterStateUpdatedJs(<<<'JS'
                                const basePrice = ($get('amount_per_item') ?? '0').toString().replace(/\./g, '').replace(',', '.');
                                const basePriceNum = parseFloat(basePrice) || 0;
                                const qtyNum = parseFloat($state) || 0;
                                const total = qtyNum * basePriceNum;

                                if (total === 0) {
                                    $set('total_price', '');
                                } else {
                                    const formatted = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                    $set('total_price', formatted);
                                }
                            JS),
                        TextInput::make('act_quantity')
                            ->label('Qty')
                            ->columnSpan(1)
                            ->hidden(fn (Get $get) => $get('payment_type') === RequestPaymentType::Advance->value)
                            ->dehydrated(true)
                            ->numeric()
                            ->minValue(1)
                            ->placeholder(function (Get $get) {
                                $activityItem = $get('program_activity_id') ? ProgramActivityItem::whereProgramActivityId($get('program_activity_id'))->whereDescription($get('description'))->get(['volume'])->first() : null;

                                return $activityItem ? $activityItem->volume : null;
                            })
                            ->afterStateUpdatedJs(<<<'JS'
                                const basePrice = ($get('act_amount_per_item') ?? '0').toString().replace(/\./g, '').replace(',', '.');
                                const basePriceNum = parseFloat(basePrice) || 0;
                                const qtyNum = parseFloat($state) || 0;
                                const total = qtyNum * basePriceNum;

                                if (total === 0) {
                                    $set('total_price', '');
                                } else {
                                    const formatted = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                    $set('total_price', formatted);
                                }
                            JS),
                        TextInput::make('unit_quantity')
                            ->label('Unit Qty')
                            ->columnSpan(2)
                            ->trim()
                            ->placeholder(function (Get $get) {
                                $activityItem = $get('program_activity_id') ? ProgramActivityItem::whereProgramActivityId($get('program_activity_id'))->whereDescription($get('description'))?->get(['unit'])->first() : null;

                                return $activityItem ? $activityItem->unit : null;
                            }),
                        TextInput::make('amount_per_item')
                            ->label('Harga/item')
                            ->columnSpan(3)
                            ->numeric()
                            ->prefix('Rp')
                            ->hidden(fn (Get $get) => $get('payment_type') === RequestPaymentType::Reimburse->value || $get('payment_type') === RequestPaymentType::Offset->value)
                            ->dehydrated(true)
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                            ->stripCharacters(['.', ','])
                            ->dehydrateStateUsing(fn ($rawState) => str_replace(['.', ','], ['', '.'], $rawState))
                            ->placeholder(function (Get $get) {
                                $activityItem = $get('program_activity_id') ? ProgramActivityItem::whereProgramActivityId($get('program_activity_id'))->whereDescription($get('description'))->get(['total_item_planned_budget', 'volume'])->first() : null;

                                $plannedBudgetPerItem = $activityItem ? ((float) $activityItem->total_item_planned_budget / $activityItem->volume) : 0;

                                return $plannedBudgetPerItem ? number_format($plannedBudgetPerItem, 2, ',', '.') : null;
                            })
                            ->afterStateUpdatedJs(<<<'JS'
                                const cleanPrice = ($state ?? '0').toString().replace(/\./g, '').replace(',', '.');
                                const priceNum = parseFloat(cleanPrice) || 0;
                                const qtyNum = parseFloat($get('quantity')) || 0;
                                const total = qtyNum * priceNum;

                                if (total === 0) {
                                    $set('total_price', '');
                                } else {
                                    const formatted = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                    $set('total_price', formatted);
                                }
                            JS)
                            ->minValue(1),
                        TextInput::make('act_amount_per_item')
                            ->label('Harga/item')
                            ->columnSpan(3)
                            ->numeric()
                            ->prefix('Rp')
                            ->hidden(fn (Get $get) => $get('payment_type') === RequestPaymentType::Advance->value)
                            ->dehydrated(true)
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                            ->stripCharacters(['.', ','])
                            ->dehydrateStateUsing(fn ($rawState) => str_replace(['.', ','], ['', '.'], $rawState))
                            ->placeholder(function (Get $get) {
                                $activityItem = $get('program_activity_id') ? ProgramActivityItem::whereProgramActivityId($get('program_activity_id'))->whereDescription($get('description'))->get(['total_item_planned_budget', 'volume'])->first() : null;

                                $plannedBudgetPerItem = $activityItem ? ((float) $activityItem->total_item_planned_budget / $activityItem->volume) : 0;

                                return $plannedBudgetPerItem ? number_format($plannedBudgetPerItem, 2, ',', '.') : null;
                            })
                            ->afterStateUpdatedJs(<<<'JS'
                                const cleanPrice = ($state ?? '0').toString().replace(/\./g, '').replace(',', '.');
                                const priceNum = parseFloat(cleanPrice) || 0;
                                const qtyNum = parseFloat($get('act_quantity')) || 0;
                                const total = qtyNum * priceNum;

                                if (total === 0) {
                                    $set('total_price', '');
                                } else {
                                    const formatted = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                    $set('total_price', formatted);
                                }
                            JS)
                            ->minValue(1),
                        TextInput::make('total_price')
                            ->label('Total Harga Item')
                            ->columnSpan(3)
                            ->prefix('Rp')
                            ->placeholder(function (Get $get) {
                                $activityItem = $get('program_activity_id') ? ProgramActivityItem::whereProgramActivityId($get('program_activity_id'))->whereDescription($get('description'))->get(['total_item_planned_budget', 'volume'])->first() : null;

                                $plannedBudget = $activityItem ? (float) $activityItem->total_item_planned_budget : 0;

                                return $plannedBudget ? number_format($plannedBudget, 2, ',', '.') : null;
                            })
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                            ->readOnly()
                            ->dehydrated(false),
                        Select::make('payment_type')
                            ->live()
                            ->partiallyRenderComponentsAfterStateUpdated(['quantity', 'act_quantity', 'amount_per_item', 'act_amount_per_item', 'attachments'])
                            ->label('Tipe Request')
                            ->columnSpan(3)
                            ->label('Tipe Pengajuan')
                            ->options([
                                RequestPaymentType::Advance->value => RequestPaymentType::Advance->getLabel(),
                                RequestPaymentType::Reimburse->value => RequestPaymentType::Reimburse->getLabel(),
                            ])
                            ->afterStateUpdatedJs(<<<'JS'
                                if ($state === 'advance') {
                                    $set('act_quantity', 0);
                                    $set('act_amount_per_item', 0);
                                } else {
                                    $set('quantity', 0);
                                    $set('amount_per_item', 0);
                                }
                            JS)
                            ->default(RequestPaymentType::Advance->value)
                            ->selectablePlaceholder(false)
                            ->native(false)
                            ->required(),
                        SpatieMediaLibraryFileUpload::make('attachments')
                            ->label('Lampiran')
                            ->collection('request_item_attachments')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg'])
                            ->multiple()
                            ->appendFiles()
                            ->maxSize(4096)
                            ->columnSpanFull()
                            ->previewable(true)
                            ->openable(true)
                            ->required(fn (Get $get) => $get('payment_type') === RequestPaymentType::Reimburse->value)
                            ->validationMessages([
                                'required' => 'Lampiran diperlukan untuk Reimbursement',
                            ]),
                        SpatieMediaLibraryFileUpload::make('item_image')
                            ->label('Foto Item/Produk')
                            ->collection('request_item_image')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'application/pdf'])
                            ->multiple()
                            ->appendFiles()
                            ->maxSize(4096)
                            ->columnSpanFull()
                            ->previewable(true)
                            ->openable(true)
                            ->required(fn (Get $get) => $get('payment_type') === RequestPaymentType::Reimburse->value)
                            ->validationMessages([
                                'required' => 'Foto Item/Produk diperlukan untuk Reimbursement',
                            ]),
                        Toggle::make('self_account')
                            ->columnSpan(3)
                            ->label('Kirim ke Rekening Sendiri?')
                            ->inline(false)
                            ->default(true)
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state) {
                                    $set('bank_name', Auth::user()->employee?->bank_name);
                                    $set('bank_account', Auth::user()->employee?->bank_account_number);
                                    $set('account_owner', Auth::user()->employee?->bank_cust_name);
                                } else {
                                    $set('bank_name', null);
                                    $set('bank_account', null);
                                    $set('account_owner', null);
                                }
                            }),
                        TextInput::make('bank_name')
                            ->columnSpan(3)
                            ->label('Nama Bank')
                            ->default(Auth::user()->employee?->bank_name)
                            ->readOnly(fn (Get $get) => $get('self_account')),
                        TextInput::make('bank_account')
                            ->columnSpan(3)
                            ->label('Nomor Rekening')
                            ->default(Auth::user()->employee?->bank_account_number)
                            ->readOnly(fn (Get $get) => $get('self_account')),
                        TextInput::make('account_owner')
                            ->columnSpan(3)
                            ->label('Nama Pemilik Rekening')
                            ->default(Auth::user()->employee?->bank_cust_name)
                            ->readOnly(fn (Get $get) => $get('self_account')),
                        Textarea::make('notes')
                            ->columnSpan(6)
                            ->label('Keterangan'),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('coa.name')
                    ->label('COA')
                    ->searchable(),
                TextColumn::make('programActivity.name')
                    ->label('Aktivitas')
                    ->placeholder('N/A'),
                TextColumn::make('description')
                    ->label('Deskripsi'),
                TextColumn::make('payment_type')
                    ->label('Tipe Request')
                    ->badge()
                    ->searchable(),
                TextColumn::make('quantity')
                    ->getStateUsing(fn ($record) => $record->payment_type === RequestPaymentType::Advance ? $record->quantity : $record->act_quantity)
                    ->numeric()
                    ->sortable(),
                TextColumn::make('amount_per_item')
                    ->label('Harga per Item')
                    ->getStateUsing(fn ($record) => $record->payment_type === RequestPaymentType::Advance ? $record->amount_per_item : $record->act_amount_per_item)
                    ->numeric()
                    ->money(currency: 'IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->getStateUsing(fn ($record) => $record->payment_type === RequestPaymentType::Advance ? $record->total_amount : $record->total_act_amount)
                    ->label('Total Harga Item')
                    ->numeric()
                    ->money(currency: 'IDR', locale: 'id'),
                TextColumn::make('net_amount')
                    ->label('Nilai Setelah Pajak')
                    ->money(currency: 'IDR', locale: 'id'),
                SpatieMediaLibraryImageColumn::make('attachments')
                    ->label('Lampiran')
                    ->collection('request_item_attachments')
                    ->placeholder('N/A'),
                SpatieMediaLibraryImageColumn::make('item_image')
                    ->label('Foto Item/Produk')
                    ->collection('request_item_image')
                    ->placeholder('N/A'),
                TextColumn::make('due_date')
                    ->label('Tenggat Waktu Realisasi')
                    ->date('j M Y', 'Asia/Jakarta')
                    ->placeholder('N/A'),
                ToggleColumn::make('is_taxed')
                    ->disabled(fn ($record) => $record->status === RequestItemStatus::Closed || $record->status === RequestItemStatus::WaitingSettlement || $record->status === RequestItemStatus::Cancelled || $record->status === RequestItemStatus::Rejected)
                    ->label('Dikenai Pajak?')
                    ->hidden(fn () => Auth::user()->employee->jobTitle->department->code !== 'FIN'),
                SelectColumn::make('tax_id')
                    ->disabled(fn ($record) => ! $record->is_taxed || $record->status === RequestItemStatus::Closed || $record->status === RequestItemStatus::WaitingSettlement || $record->status === RequestItemStatus::Cancelled || $record->status === RequestItemStatus::Rejected)
                    ->hidden(fn () => Auth::user()->employee->jobTitle->department->code !== 'FIN')
                    ->optionsRelationship('tax', 'name')
                    ->label('Tipe Pajak'),
                SelectColumn::make('tax_method')
                    ->disabled(fn ($record) => ! $record->is_taxed || $record->status === RequestItemStatus::Closed || $record->status === RequestItemStatus::WaitingSettlement || $record->status === RequestItemStatus::Cancelled || $record->status === RequestItemStatus::Rejected)
                    ->hidden(fn () => Auth::user()->employee->jobTitle->department->code !== 'FIN')
                    ->options(TaxMethod::class)
                    ->label('Penanggung Pajak'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->hidden(fn (RelationManager $livewire) => ! $livewire->getOwnerRecord()->isDraft())
                    ->modalWidth(Width::SevenExtraLarge),
                // AssociateAction::make(),
            ])
            ->recordActions([
                Action::make('updateDueDate')
                    ->icon(Heroicon::OutlinedCalendar)
                    ->label('Ubah Tenggat Waktu')
                    ->modalWidth(Width::ExtraSmall)
                    ->fillForm(fn (RequestItem $record): array => [
                        'due_date' => $record->due_date,
                        'minDate' => $record->dailyPaymentRequest->updated_at->addDays(3)->toDateString(),
                        'maxDate' => $record->dailyPaymentRequest->updated_at->addDays(14)->toDateString(),
                    ])
                    ->schema([
                        DatePicker::make('due_date')
                            ->label('Atur Tenggat Waktu Baru')
                            ->displayFormat('j M Y')
                            ->timezone('Asia/Jakarta')
                            ->locale('id')
                            ->native(false)
                            ->minDate(fn (Get $get) => $get('minDate'))
                            ->maxDate(fn (Get $get) => $get('maxDate'))
                            ->required(),
                    ])
                    ->action(function (array $data, RequestItem $record): void {
                        $record->due_date = $data['due_date'];
                        $record->save();
                    })
                    ->successNotificationTitle('Tenggat Waktu Diubah')
                    ->hidden(fn ($record) => ($record->payment_type === RequestPaymentType::Reimburse || Auth::user()->employee->jobTitle->code !== 'FO') && ! ($record->status === RequestItemStatus::WaitingSettlement || $record->status === RequestItemStatus::WaitingSettlementReview)),
                ViewAction::make()
                    ->modalWidth(Width::SevenExtraLarge),
                EditAction::make()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->hidden(fn (RelationManager $livewire) => ! $livewire->getOwnerRecord()->canBeEdited()),
                DeleteAction::make()->hidden(fn (RelationManager $livewire) => ! $livewire->getOwnerRecord()->canBeEdited()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ])->hidden(fn (RelationManager $livewire) => ! $livewire->getOwnerRecord()->canBeEdited()),
            ]);
        // ->groups([
        //     Group::make('bank_account')
        //         ->collapsible()
        //         ->label('Nomor Rekening')
        //         ->getTitleFromRecordUsing(fn(RequestItem $record) => $record->bank_account . ' - ' . $record->bank_name . ' - ' . $record->account_owner)
        //         ->titlePrefixedWithLabel(false)
        //         ->getDescriptionFromRecordUsing(fn(RequestItem $record) => 'Rp ' . (string) number_format(RequestItem::whereDailyPaymentRequestId($record->daily_payment_request_id)->whereBankAccount($record->bank_account)->get()->sum('total_amount'), 0, ',', '.')),
        //     Group::make('coa.name')
        //         ->collapsible()
        //         ->label('Chart of Account')
        //         ->titlePrefixedWithLabel(false)
        //         ->getDescriptionFromRecordUsing(fn(RequestItem $record) => 'Rp ' . (string) number_format(RequestItem::whereDailyPaymentRequestId($record->daily_payment_request_id)->whereCoaId($record->coa_id)->get()->sum('total_amount'), 0, ',', '.'))
        // ])
        // ->defaultGroup('coa.name')
        // ->defaultGroup(Group::make('coa.name')
        //     ->collapsible()
        //     ->label('Chart of Account')
        //     ->titlePrefixedWithLabel(false)
        //     ->getDescriptionFromRecordUsing(fn(RequestItem $record) => 'Rp ' . (string) number_format(RequestItem::whereDailyPaymentRequestId($record->daily_payment_request_id)->whereCoaId($record->coa_id)->get()->sum('total_amount'), 0, ',', '.')))
        // ->collapsedGroupsByDefault();
    }
}

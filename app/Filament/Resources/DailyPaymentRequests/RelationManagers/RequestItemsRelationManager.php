<?php

namespace App\Filament\Resources\DailyPaymentRequests\RelationManagers;

use App\Enums\COAType;
use App\Enums\RequestItemStatus;
use App\Enums\RequestPaymentType;
use App\Enums\TaxMethod;
use App\Models\Coa;
use App\Models\ProgramActivity;
use App\Models\ProgramActivityItem;
use App\Models\RequestItem;
use App\Models\RequestItemType;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group as ComponentsGroup;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Operation;
use Filament\Support\Enums\Width;
use Filament\Support\RawJs;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
                            ->options(fn(Get $get) => ProgramActivity::query()->whereCoaId($get('coa_id'))->pluck('name', 'id'))
                            ->live()
                            ->native(false)
                            ->preload()
                            ->searchable()
                            ->disabled(fn(Get $get) => $get('coa_id') === null)
                            ->columnSpan(4),
                        TextInput::make('description')
                            ->label('Deskripsi Item')
                            ->trim()
                            ->datalist(fn(Get $get) => ProgramActivityItem::query()->whereProgramActivityId($get('program_activity_id'))->limit(10)->pluck('description', 'id')->toArray())
                            ->columnSpan(4)
                            ->live(debounce: 500),
                        TextInput::make('quantity')
                            ->label('Qty')
                            ->columnSpan(1)
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
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                            ->stripCharacters(['.'])
                            ->placeholder(function (Get $get) {
                                $activityItem = $get('program_activity_id') ? ProgramActivityItem::whereProgramActivityId($get('program_activity_id'))->whereDescription($get('description'))->get(['total_item_planned_budget', 'volume'])->first() : null;

                                $plannedBudgetPerItem = $activityItem ? ((float)$activityItem->total_item_planned_budget / $activityItem->volume) : 0;

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
                        TextInput::make('total_price')
                            ->label('Total Harga Item')
                            ->columnSpan(3)
                            ->prefix('Rp')
                            ->placeholder(function (Get $get) {
                                $activityItem = $get('program_activity_id') ? ProgramActivityItem::whereProgramActivityId($get('program_activity_id'))->whereDescription($get('description'))->get(['total_item_planned_budget', 'volume'])->first() : null;

                                $plannedBudget = $activityItem ? (float)$activityItem->total_item_planned_budget : 0;

                                return $plannedBudget ? number_format($plannedBudget, 2, ',', '.') : null;
                            })
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                            ->readOnly()
                            ->dehydrated(false),
                        Select::make('payment_type')
                            ->live()
                            ->partiallyRenderComponentsAfterStateUpdated(['attachments'])
                            ->label('Tipe Request')
                            ->columnSpan(3)
                            ->label('Tipe Pengajuan')
                            ->options(RequestPaymentType::class)
                            ->default(RequestPaymentType::Advance)
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
                            ->required(fn(Get $get) => $get('payment_type') === RequestPaymentType::Reimburse)
                            ->validationMessages([
                                'required' => 'Lampiran diperlukan untuk Reimbursement',
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
                            ->readOnly(fn(Get $get) => $get('self_account')),
                        TextInput::make('bank_account')
                            ->columnSpan(3)
                            ->label('Nomor Rekening')
                            ->default(Auth::user()->employee?->bank_account_number)
                            ->readOnly(fn(Get $get) => $get('self_account')),
                        TextInput::make('account_owner')
                            ->columnSpan(3)
                            ->label('Nama Pemilik Rekening')
                            ->default(Auth::user()->employee?->bank_cust_name)
                            ->readOnly(fn(Get $get) => $get('self_account')),
                    ])
                    ->columnSpanFull()
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
                    ->numeric()
                    ->sortable(),
                TextColumn::make('amount_per_item')
                    ->label('Harga per Item')
                    ->numeric()
                    ->money(currency: "IDR", locale: "id")
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->getStateUsing(fn($record) => $record->total_amount)
                    ->label('Total Harga Item')
                    ->numeric()
                    ->money(currency: "IDR", locale: "id"),
                SpatieMediaLibraryImageColumn::make('attachments')
                    ->collection('request_item_attachments'),
                ToggleColumn::make('is_taxed')
                    ->disabled(fn($record) => $record->status === RequestItemStatus::Paid || $record->status === RequestItemStatus::WaitingSettlement || $record->status === RequestItemStatus::Settled)
                    ->label('Dikenai Pajak?')
                    ->hidden(fn() => Auth::user()->employee->jobTitle->department->code !== 'FIN'),
                SelectColumn::make('tax_id')
                    ->disabled(fn($record) => !$record->is_taxed || $record->status === RequestItemStatus::Paid || $record->status === RequestItemStatus::WaitingSettlement || $record->status === RequestItemStatus::Settled)
                    ->hidden(fn() => Auth::user()->employee->jobTitle->department->code !== 'FIN')
                    ->optionsRelationship('tax', 'name')
                    ->label('Tipe Pajak'),
                SelectColumn::make('tax_method')
                    ->disabled(fn($record) => !$record->is_taxed || $record->status === RequestItemStatus::Paid || $record->status === RequestItemStatus::WaitingSettlement || $record->status === RequestItemStatus::Settled)
                    ->hidden(fn() => Auth::user()->employee->jobTitle->department->code !== 'FIN')
                    ->options(TaxMethod::class)
                    ->label('Penanggung Pajak'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->hidden(fn(RelationManager $livewire) => !$livewire->getOwnerRecord()->isDraft())
                    ->modalWidth(Width::SevenExtraLarge),
                // AssociateAction::make(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalWidth(Width::SevenExtraLarge),
                EditAction::make()
                    ->modalWidth(Width::SevenExtraLarge)
                    ->hidden(fn(RelationManager $livewire) => !$livewire->getOwnerRecord()->canBeEdited()),
                // ->mountUsing(function ($form, $record) {
                //     $data = $record->toArray();
                //     $data['total_price'] = (float) ($record->quantity ?? 0) * (float) ($record->amount_per_item ?? 0);
                //     $form->fill($data);
                // })
                // ->after(function (Model $record) {
                //     $record->createSnapshots();
                //     $record->dailyPaymentRequest->calculateTotals();
                // // })
                // ->mutateRecordDataUsing(function (array $data, $record) {
                //     $data['total_price'] = (float) ($record->quantity ?? 0) * (float) ($record->amount_per_item ?? 0);
                //     // dd($data);
                //     return $data;
                // })
                // ->successRedirectUrl(fn(Model $record): string => route('filament.admin.resources.daily-payment-requests.edit', [
                //     'record' => $record->dailyPaymentRequest->id,
                // ])),
                // DissociateAction::make(),
                DeleteAction::make()->hidden(fn(RelationManager $livewire) => !$livewire->getOwnerRecord()->canBeEdited()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ])->hidden(fn(RelationManager $livewire) => !$livewire->getOwnerRecord()->canBeEdited()),
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

<?php

namespace App\Filament\Resources\DailyPaymentRequests\Schemas;

use App\Enums\RequestPaymentType;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class DailyPaymentRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Fieldset::make('Summary Request')
                    ->schema([
                        TextEntry::make('request_number')
                            ->label('Request ID')
                            ->placeholder('Belum Submit')
                            ->badge()
                            ->color('primary')
                            ->size('lg'),

                        TextEntry::make('request_date')
                            ->label('Tanggal Request')
                            ->placeholder('N/A')
                            ->date('d F Y'),

                        TextEntry::make('requester.user.name')
                            ->label('Requester'),

                        TextEntry::make('requester.jobTitle.title')
                            ->label('Job Title'),

                        TextEntry::make('requester.jobTitle.department.name')
                            ->label('Department')
                            ->badge(),

                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('total_request_amount')
                            ->getStateUsing(fn ($record) => $record->total_request_amount)
                            ->label('Total Nominal Request')
                            ->belowContent('(Tidak termasuk pajak)')
                            ->money('IDR'),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
                Tabs::make('Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Riwayat Approval')
                            ->visible(fn ($record) => $record->approvalHistories()->exists())
                            ->schema([
                                RepeatableEntry::make('approvalHistories')
                                    ->columnSpanFull()
                                    ->hiddenLabel()
                                    ->contained(false)
                                    ->table([
                                        TableColumn::make('Urutan')->width('75px'),
                                        TableColumn::make('Approver'),
                                        TableColumn::make('Jabatan'),
                                        TableColumn::make('Status'),
                                        TableColumn::make('Tanggal Disetujui'),
                                        TableColumn::make('Notes')->width('250px'),
                                    ])
                                    ->schema([
                                        TextEntry::make('sequence')
                                            ->badge()
                                            ->label('Urutan'),

                                        TextEntry::make('approver.user.name')
                                            ->label('Approver'),

                                        TextEntry::make('approver.jobTitle.title')
                                            ->label('Job Title'),

                                        TextEntry::make('action')
                                            ->badge(),

                                        TextEntry::make('approved_at')
                                            ->label('Action Date')
                                            ->dateTime('d M Y H:i')
                                            ->placeholder('Pending'),

                                        TextEntry::make('notes')
                                            ->placeholder('No notes')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(3)
                                    ->grid(1)
                                    ->visible(fn ($record) => $record->approvalHistories()->exists()),
                            ]),
                        Tab::make('Summary COA')
                            ->schema([
                                RepeatableEntry::make('groupedByCoa')
                                    ->columnSpanFull()
                                    ->state(fn ($record) => $record->getGroupedByCoa())
                                    ->hiddenLabel()
                                    // ->contained(false)
                                    // ->table([
                                    //     TableColumn::make('Nama Bank'),
                                    //     TableColumn::make('Nomor Rekening'),
                                    //     TableColumn::make('Nama Pemilik Rekening'),
                                    //     TableColumn::make('Nominal Transfer (Termasuk pajak)'),
                                    // ])
                                    ->schema([
                                        Flex::make([
                                            TextEntry::make('coa_name')
                                                ->hiddenLabel(),

                                            TextEntry::make('total_amount')
                                                ->hiddenLabel()
                                                ->money(currency: 'IDR', locale: 'id')
                                                ->weight(FontWeight::Bold)
                                                ->size(TextSize::Large)
                                                ->grow(false),
                                        ]),
                                        RepeatableEntry::make('items')
                                            ->hiddenLabel()
                                            ->contained(false)
                                            ->table([
                                                TableColumn::make('Aktivitas')->width('350px'),
                                                TableColumn::make('Deskripsi')->width('350px'),
                                                TableColumn::make('Qty')->width('70px'),
                                                TableColumn::make('Unit Qty')->width('150px'),
                                                TableColumn::make('Base Price')->width('200px'),
                                                TableColumn::make('Total Price')->width('200px'),
                                                TableColumn::make('Keterangan')->width('300px'),
                                            ])
                                            ->extraAttributes([
                                                'class' => 'overflow-x-auto p-0.5 pb-1.5 *:first:table-fixed *:first:[&_thead]:[&_th]:first:w-[46px] *:first:table-fixed *:first:[&_thead]:[&_th]:last:w-[46px]',
                                            ])
                                            ->schema([
                                                TextEntry::make('programActivity.name')->placeholder('N/A'),
                                                TextEntry::make('description'),
                                                TextEntry::make('quantity')
                                                    ->getStateUsing(fn ($record) => $record->payment_type === RequestPaymentType::Advance ? $record->quantity : $record->act_quantity)
                                                    ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),
                                                TextEntry::make('unit_quantity'),
                                                TextEntry::make('amount_per_item')
                                                    ->getStateUsing(fn ($record) => $record->payment_type === RequestPaymentType::Advance ? $record->amount_per_item : $record->act_amount_per_item)
                                                    ->money(currency: 'IDR', locale: 'id'),
                                                TextEntry::make('total_amount')
                                                    ->getStateUsing(fn ($record) => $record->payment_type === RequestPaymentType::Advance ? $record->total_amount : $record->total_act_amount)
                                                    ->money(currency: 'IDR', locale: 'id'),
                                                TextEntry::make('notes')
                                                    ->label('Keterangan')
                                                    ->placeholder('N/A'),
                                            ]),

                                    ]),
                            ]),
                        Tab::make('Informasi Pemindahan Dana')
                            ->schema([
                                RepeatableEntry::make('transferInstructions')
                                    ->columnSpanFull()
                                    ->state(fn ($record) => $record->getGroupedByBankAccount())
                                    ->hiddenLabel()
                                    // ->grid(3)
                                    ->contained(false)
                                    ->table([
                                        TableColumn::make('Nama Bank'),
                                        TableColumn::make('Nomor Rekening'),
                                        TableColumn::make('Nama Pemilik Rekening'),
                                        TableColumn::make('Nominal Transfer (Termasuk pajak)'),
                                    ])
                                    ->schema([
                                        TextEntry::make('bank_name')
                                            ->label('Nama Bank'),

                                        TextEntry::make('bank_account')
                                            ->label('Nomor Rekening'),

                                        TextEntry::make('account_owner')
                                            ->label('Nama Pemilik Rekening'),

                                        TextEntry::make('total_amount')
                                            ->label('Nominal Transfer (Termasuk pajak)')
                                            ->money(currency: 'IDR', locale: 'id'),
                                    ]),
                            ]),
                    ]),
            ])
            ->columns(4);
    }
}

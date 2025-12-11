<?php

namespace App\Filament\Resources\Settlements\Schemas;

use App\Enums\SettlementStatus;
use App\Filament\Resources\DailyPaymentRequests\DailyPaymentRequestResource;
use App\Models\Settlement;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Njxqlus\Filament\Components\Infolists\LightboxSpatieMediaLibraryImageEntry;

class SettlementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Revision Alert (if exists) - PROMINENT!
                Section::make('Revision Diminta')
                    ->description('Finance Operator telah meminta revisi untuk settlement ini. Silakan periksa catatan revisi di bawah.')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->iconColor('warning')
                    ->collapsed(false)
                    ->schema([
                        TextEntry::make('revision_notes')
                            ->label('Catatan Revisi')
                            ->columnSpanFull()
                            ->markdown()
                            ->color('warning')
                            ->weight(FontWeight::Medium)
                            ->size(TextSize::Medium),
                    ])
                    ->columnSpanFull()
                    ->visible(fn ($record) => ! empty($record->revision_notes)),

                // Header Section - Settlement Information
                Fieldset::make('Informasi Settlement')
                    ->schema([

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('settlement_number')
                                    ->label('Nomor Settlement')
                                    ->placeholder('Belum Submit')
                                    ->badge()
                                    ->color('primary')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->icon('heroicon-o-document-text')
                                    ->iconPosition(IconPosition::Before),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('submit_date')
                                    ->label('Tanggal Submit')
                                    ->placeholder('Belum Submit')
                                    ->dateTime('d F Y H:i')
                                    ->timezone('Asia/Jakarta')
                                    ->icon('heroicon-o-calendar')
                                    ->iconPosition(IconPosition::Before),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('submitter.user.name')
                                    ->label('Submitter')
                                    ->icon('heroicon-o-user')
                                    ->iconPosition(IconPosition::Before),

                                TextEntry::make('submitter.jobTitle.title')
                                    ->label('Job Title / Departemen')
                                    ->getStateUsing(fn ($record) => $record->submitter?->jobTitle?->title.' / '.$record->submitter?->jobTitle?->department?->name)
                                    ->badge()
                                    ->color('info')
                                    ->columnSpan(2),
                            ]),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),

                // Financial Summary - HIGH LEVEL OVERVIEW
                Fieldset::make('Ringkasan Keuangan')
                    ->schema([
                        Grid::make(5)
                            ->columnSpanFull()
                            ->schema([
                                TextEntry::make('approved_budget')
                                    ->label('Total Anggaran Disetujui')
                                    ->money('IDR', locale: 'id')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('info')
                                    ->icon('heroicon-o-banknotes')
                                    ->iconPosition(IconPosition::Before),

                                TextEntry::make('spent_budget')
                                    ->label('Total Dibelanjakan')
                                    ->money('IDR', locale: 'id')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('primary')
                                    ->icon('heroicon-o-shopping-cart')
                                    ->iconPosition(IconPosition::Before),

                                TextEntry::make('cancelled_budget')
                                    ->label('Item Dibatalkan')
                                    ->money('IDR', locale: 'id')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('gray')
                                    ->icon('heroicon-o-x-circle')
                                    ->iconPosition(IconPosition::Before),

                                TextEntry::make('new_request_budget')
                                    ->label('Item Baru (Unplanned)')
                                    ->money('IDR', locale: 'id')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('warning')
                                    ->icon('heroicon-o-plus-circle')
                                    ->iconPosition(IconPosition::Before),

                                TextEntry::make('budget_variance')
                                    ->label('Selisih (Variance)')
                                    ->money('IDR', locale: 'id')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                                    ->icon(fn ($state) => $state > 0 ? 'heroicon-o-arrow-trending-down' : ($state < 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-minus'))
                                    ->iconPosition(IconPosition::Before),
                            ]),
                    ])
                    ->columnSpanFull(),

                // Net Settlement Result - FINAL OUTCOME
                Section::make('Hasil Akhir Settlement')
                    ->description(function ($record) {
                        if ($record->net_settlement > 0) {
                            return 'Employee perlu mengembalikan dana ke Finance';
                        } elseif ($record->net_settlement < 0) {
                            return 'Finance perlu melakukan reimbursement ke Employee';
                        }

                        return 'Tidak ada pengembalian dana atau reimbursement (Break-Even)';
                    })
                    ->icon(function ($record) {
                        if ($record->net_settlement > 0) {
                            return 'heroicon-o-arrow-left-circle';
                        } elseif ($record->net_settlement < 0) {
                            return 'heroicon-o-arrow-right-circle';
                        }

                        return 'heroicon-o-check-circle';
                    })
                    ->iconColor(function ($record) {
                        if ($record->net_settlement > 0) {
                            return 'warning';
                        } elseif ($record->net_settlement < 0) {
                            return 'success';
                        }

                        return 'gray';
                    })
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('total_amount_to_return')
                                    ->label('Dikembalikan ke Finance')
                                    ->money('IDR', locale: 'id')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('warning')
                                    ->icon('heroicon-o-arrow-left-circle')
                                    ->iconPosition(IconPosition::Before),

                                TextEntry::make('total_amount_to_reimburse')
                                    ->label('Reimbursement ke Employee')
                                    ->money('IDR', locale: 'id')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('success')
                                    ->icon('heroicon-o-arrow-right-circle')
                                    ->iconPosition(IconPosition::Before),

                                // TextEntry::make('net_settlement')
                                //     ->label('Net Settlement')
                                //     ->helperText(function ($state) {
                                //         if ($state > 0) {
                                //             return 'Positif = Employee Owes Company';
                                //         } elseif ($state < 0) {
                                //             return 'Negatif = Company Owes Employee';
                                //         }

                                //         return 'Zero = Break Even';
                                //     })
                                //     ->money('IDR', locale: 'id')
                                //     ->size(TextSize::Large)
                                //     ->weight(FontWeight::ExtraBold)
                                //     ->color(fn ($state) => $state > 0 ? 'warning' : ($state < 0 ? 'success' : 'gray'))
                                //     ->icon(fn ($state) => $state > 0 ? 'heroicon-o-arrow-left-circle' : ($state < 0 ? 'heroicon-o-arrow-right-circle' : 'heroicon-o-check-circle'))
                                //     ->iconPosition(IconPosition::Before),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(false),

                // MAIN CONTENT TABS
                Tabs::make('Settlement Details')
                    ->columnSpanFull()
                    ->tabs([
                        // TAB 1: COA RECONCILIATION - MOST IMPORTANT FOR FINANCE!
                        Tab::make('Rekonsiliasi Per COA')
                            ->icon('heroicon-o-calculator')
                            ->badge(function ($record) {
                                $reconciliation = $record->getReconciliation();

                                return count($reconciliation['reconciliations'] ?? []);
                            })
                            ->schema([
                                RepeatableEntry::make('coa_reconciliations')
                                    ->label('Rekonsiliasi Berdasarkan COA')
                                    ->columnSpanFull()
                                    ->hiddenLabel()
                                    ->state(function ($record) {
                                        $reconciliation = $record->getReconciliation();
                                        // dd($reconciliation);

                                        return $reconciliation['reconciliations'] ?? [];
                                    })
                                    ->schema([
                                        Section::make(fn (Get $get) => $get('coa_name'))
                                            ->icon(Heroicon::OutlinedTag)
                                            ->compact()
                                            ->collapsible()
                                            ->collapsed()
                                            ->schema([
                                                // COA Header with Name and Code
                                                TextEntry::make('coa_name')
                                                    ->label('COA')
                                                    ->hidden()
                                                    // ->state(fn (array $state): string => ($state['coa_name'] ?? 'N/A').' ('.($state['coa_code'] ?? 'N/A').')')
                                                    ->size(TextSize::Large)
                                                    ->weight(FontWeight::Bold)
                                                    ->color('primary')
                                                    ->icon('heroicon-o-tag')
                                                    ->iconPosition(IconPosition::Before),

                                                // Financial Summary for this COA
                                                KeyValueEntry::make('summary')
                                                    ->extraAttributes([
                                                        'class' => '**:font-sans!',
                                                    ])
                                                    ->label('Ringkasan Keuangan COA')
                                                    ->keyLabel('Kategori')
                                                    ->valueLabel('Nominal')
                                                    ->columnSpanFull(),

                                                // Detailed Items Breakdown - TABLE FORMAT
                                                RepeatableEntry::make('items')
                                                    ->label('Detail Item')
                                                    ->hiddenLabel()
                                                    ->contained(false)
                                                    ->extraAttributes([
                                                        'class' => 'overflow-x-auto p-0.5 pb-1.5 *:first:table-fixed *:first:[&_thead]:[&_th]:first:w-[46px] *:first:table-fixed *:first:[&_thead]:[&_th]:last:w-[46px]',
                                                    ])
                                                    ->table([
                                                        TableColumn::make('Type')->width('100px'),
                                                        TableColumn::make('Deskripsi')->width('300px'),
                                                        TableColumn::make('Qty Request')->width('110px'),
                                                        TableColumn::make('Qty Aktual')->width('110px'),
                                                        TableColumn::make('Unit')->width('125px'),
                                                        TableColumn::make('Harga Request')->width('250px'),
                                                        TableColumn::make('Harga Aktual')->width('250px'),
                                                        TableColumn::make('Total Request')->width('250px'),
                                                        TableColumn::make('Total Aktual')->width('250px'),
                                                        TableColumn::make('Selisih')->width('250px'),
                                                        TableColumn::make('Status')->width('200px'),
                                                    ])
                                                    ->schema([
                                                        TextEntry::make('type')
                                                            ->label('Type')
                                                            ->badge()
                                                            ->color(fn ($state) => match ($state) {
                                                                'realized' => 'success',
                                                                'cancelled' => 'gray',
                                                                'new_unplanned' => 'warning',
                                                                'offset' => 'info',
                                                                default => 'primary',
                                                            })
                                                            ->formatStateUsing(fn ($state) => match ($state) {
                                                                'realized' => 'Terealisasi',
                                                                'cancelled' => 'Dibatalkan',
                                                                'new_unplanned' => 'Item Baru',
                                                                'offset' => 'Offset',
                                                                default => ucfirst($state ?? 'unknown'),
                                                            }),

                                                        TextEntry::make('description')
                                                            ->label('Deskripsi')
                                                            ->wrap()
                                                            ->lineClamp(3),

                                                        TextEntry::make('request_quantity')
                                                            ->label('Qty Request')
                                                            ->placeholder('N/A')
                                                            ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),

                                                        TextEntry::make('actual_quantity')
                                                            ->label('Qty Aktual')
                                                            ->placeholder('N/A')
                                                            ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),

                                                        TextEntry::make('unit')
                                                            ->label('Unit')
                                                            ->placeholder('N/A'),

                                                        TextEntry::make('request_price')
                                                            ->label('Harga Request')
                                                            ->placeholder('N/A')
                                                            ->money('IDR', locale: 'id'),

                                                        TextEntry::make('actual_price')
                                                            ->label('Harga Aktual')
                                                            ->placeholder('N/A')
                                                            ->money('IDR', locale: 'id'),

                                                        TextEntry::make('request_total')
                                                            ->label('Total Request')
                                                            ->placeholder('N/A')
                                                            ->money('IDR', locale: 'id')
                                                            ->weight(FontWeight::Bold),

                                                        TextEntry::make('actual_total')
                                                            ->label('Total Aktual')
                                                            ->placeholder('N/A')
                                                            ->money('IDR', locale: 'id')
                                                            ->weight(FontWeight::Bold)
                                                            ->color('primary'),

                                                        TextEntry::make('variance')
                                                            ->label('Selisih')
                                                            ->placeholder('N/A')
                                                            ->money('IDR', locale: 'id')
                                                            ->weight(FontWeight::Bold)
                                                            ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray')),

                                                        TextEntry::make('status')
                                                            ->label('Status')
                                                            ->placeholder('N/A')
                                                            ->badge(),
                                                    ]),
                                            ]),
                                    ])
                                    ->contained(false),
                            ]),

                        // // TAB 2: SETTLEMENT RECEIPTS
                        Tab::make('Bukti Kwitansi')
                            ->icon('heroicon-o-document-text')
                            ->badge(fn ($record) => $record->settlementReceipts->count())
                            ->schema([
                                RepeatableEntry::make('settlementReceipts')
                                    ->label('Daftar Kwitansi')
                                    ->columnSpanFull()
                                    ->hiddenLabel()
                                    ->schema([
                                        // Receipt Header
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('realization_date')
                                                    ->label('Tanggal Realisasi')
                                                    ->date('d F Y')
                                                    ->icon('heroicon-o-calendar')
                                                    ->iconPosition(IconPosition::Before)
                                                    ->size(TextSize::Large)
                                                    ->weight(FontWeight::Bold),

                                                LightboxSpatieMediaLibraryImageEntry::make('attachment')
                                                    ->collection('settlement_receipt_attachments')
                                                    ->label('Lampiran Kwitansi')
                                                    ->visible(fn ($record) => $record->getMedia('settlement_receipt_attachments')->isNotEmpty())
                                                    ->slideZoomable(true)
                                                    ->slideDraggable(true)
                                                    ->slideWidth('auto')
                                                    ->slideHeight('auto'),
                                            ]),

                                        // Items in this Receipt - TABLE FORMAT
                                        RepeatableEntry::make('requestItems')
                                            ->label('Item dalam Kwitansi')
                                            ->hiddenLabel()
                                            ->contained(false)
                                            ->extraAttributes([
                                                'class' => 'overflow-x-auto p-0.5 pb-1.5 *:first:table-fixed *:first:[&_thead]:[&_th]:first:w-[46px] *:first:table-fixed *:first:[&_thead]:[&_th]:last:w-[46px]',
                                            ])
                                            ->table([
                                                TableColumn::make('COA')->width('300px'),
                                                TableColumn::make('Aktivitas')->width('300px'),
                                                TableColumn::make('Deskripsi')->width('300px'),
                                                TableColumn::make('Qty Request')->width('150px'),
                                                TableColumn::make('Qty Aktual')->width('150px'),
                                                TableColumn::make('Unit')->width('150px'),
                                                TableColumn::make('Harga Aktual')->width('250px'),
                                                TableColumn::make('Total')->width('250px'),
                                                TableColumn::make('Status')->width('150px'),
                                            ])
                                            ->schema([
                                                TextEntry::make('coa.name')
                                                    ->label('COA')
                                                    ->badge()
                                                    ->color('primary'),

                                                TextEntry::make('programActivity.name')
                                                    ->label('Aktivitas')
                                                    ->placeholder('N/A'),

                                                TextEntry::make('description')
                                                    ->label('Deskripsi')
                                                    ->wrap()
                                                    ->lineClamp(2),

                                                TextEntry::make('quantity')
                                                    ->label('Qty Request')
                                                    ->placeholder('N/A')
                                                    ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),

                                                TextEntry::make('act_quantity')
                                                    ->label('Qty Aktual')
                                                    ->numeric(decimalPlaces: 0, decimalSeparator: ',', thousandsSeparator: '.'),

                                                TextEntry::make('unit_quantity')
                                                    ->label('Unit'),

                                                TextEntry::make('act_amount_per_item')
                                                    ->label('Harga Aktual')
                                                    ->money('IDR', locale: 'id'),

                                                TextEntry::make('total_act_amount')
                                                    ->label('Total')
                                                    ->money('IDR', locale: 'id')
                                                    ->weight(FontWeight::Bold),

                                                TextEntry::make('status')
                                                    ->label('Status')
                                                    ->badge(),
                                            ]),
                                    ]),
                            ]),

                        // // TAB 3: GENERATED DPR HISTORY (if exists)
                        Tab::make('Riwayat DPR')
                            ->icon('heroicon-o-document-duplicate')
                            ->visible(fn (Settlement $record) => $record->generatedPaymentRequests()->exists())
                            ->schema([
                                RepeatableEntry::make('generatedPaymentRequests')
                                    ->label('Riwayat DPR')
                                    ->columnSpanFull()
                                    ->contained(false)
                                    ->schema([
                                        Section::make()
                                            ->schema([
                                                Grid::make(4)
                                                    ->schema([
                                                        TextEntry::make('request_number')
                                                            ->label('Nomor DPR')
                                                            ->badge()
                                                            ->color('primary')
                                                            ->size(TextSize::Large)
                                                            ->weight(FontWeight::Bold)
                                                            ->url(fn ($record) => DailyPaymentRequestResource::getUrl('view', ['record' => $record->id]))
                                                            ->openUrlInNewTab(),

                                                        TextEntry::make('status')
                                                            ->label('Status DPR')
                                                            ->badge()
                                                            ->size(TextSize::Large)
                                                            ->color(fn ($state) => match ($state?->value) {
                                                                'approved' => 'success',
                                                                'rejected' => 'danger',
                                                                'pending' => 'warning',
                                                                default => 'gray',
                                                            }),

                                                        TextEntry::make('created_at')
                                                            ->label('Tanggal Dibuat')
                                                            ->dateTime('d M Y H:i', 'Asia/Jakarta'),

                                                        TextEntry::make('total_request_amount')
                                                            ->label('Total Nominal')
                                                            ->money('IDR', locale: 'id')
                                                            ->size(TextSize::Large)
                                                            ->weight(FontWeight::Bold),
                                                    ]),

                                                // Show rejection notes if rejected
                                                TextEntry::make('approvalHistories')
                                                    ->label('Alasan Ditolak')
                                                    ->visible(fn ($record) => $record->status?->value === 'rejected')
                                                    ->formatStateUsing(function ($state) {
                                                        return $state->notes ? $state->approver->user->name.' : '.$state->notes : null;
                                                        // $rejection = $record->approvalHistories()
                                                        //     ->where('action', \App\Enums\ApprovalAction::Rejected)
                                                        //     ->first();

                                                        // return $rejection?->notes ?? 'Tidak ada alasan';
                                                    })
                                                    ->color('danger')
                                                    ->badge()
                                                    ->columnSpanFull(),
                                            ])
                                            ->collapsible(),
                                    ]),
                            ]),

                        // // TAB 4: REFUND INFORMATION
                        Tab::make('Informasi Pengembalian Dana')
                            ->icon('heroicon-o-arrow-left-circle')
                            ->visible(fn ($record) => $record->refund_amount > 0 || $record->status === SettlementStatus::WaitingRefund || ($record->status === SettlementStatus::WaitingConfirmation && $record->getMedia('refund_receipts')->isNotEmpty()))
                            ->schema([
                                Section::make('Detail Pengembalian Dana')
                                    ->description('Informasi terkait pengembalian dana dari Employee ke Finance')
                                    ->icon('heroicon-o-arrow-left-circle')
                                    ->iconColor('warning')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('refund_amount')
                                                    ->label('Jumlah yang Harus Dikembalikan')
                                                    ->money('IDR', locale: 'id')
                                                    ->size(TextSize::Large)
                                                    ->weight(FontWeight::ExtraBold)
                                                    ->color('warning')
                                                    ->icon('heroicon-o-banknotes')
                                                    ->iconPosition(IconPosition::Before),

                                                TextEntry::make('refund_status')
                                                    ->label('Status Pengembalian')
                                                    ->getStateUsing(function ($record) {
                                                        if ($record->refund_confirmed_at) {
                                                            return 'Sudah Dikonfirmasi';
                                                        } elseif ($record->getMedia('refund_receipts')->isNotEmpty()) {
                                                            return 'Menunggu Konfirmasi FO';
                                                        } elseif ($record->status === SettlementStatus::WaitingRefund) {
                                                            return 'Menunggu Bukti Transfer';
                                                        } elseif ($record->status === SettlementStatus::WaitingDPRApproval) {
                                                            return 'Menunggu Persetujuan Selesai';
                                                        }

                                                        return 'Tidak Perlu Pengembalian';
                                                    })
                                                    ->badge()
                                                    ->color(function ($record) {
                                                        if ($record->refund_confirmed_at) {
                                                            return 'success';
                                                        } elseif ($record->getMedia('refund_receipts')->isNotEmpty()) {
                                                            return 'info';
                                                        } elseif ($record->status === SettlementStatus::WaitingRefund) {
                                                            return 'warning';
                                                        }

                                                        return 'gray';
                                                    })
                                                    ->size(TextSize::Large),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('refundConfirmer.user.name')
                                                    ->label('Dikonfirmasi Oleh')
                                                    ->placeholder('Belum dikonfirmasi')
                                                    ->icon('heroicon-o-user')
                                                    ->iconPosition(IconPosition::Before),

                                                TextEntry::make('refund_confirmed_at')
                                                    ->label('Tanggal Konfirmasi')
                                                    ->placeholder('Belum dikonfirmasi')
                                                    ->dateTime('d F Y H:i')
                                                    ->timezone('Asia/Jakarta')
                                                    ->icon('heroicon-o-calendar')
                                                    ->iconPosition(IconPosition::Before),
                                            ]),
                                        LightboxSpatieMediaLibraryImageEntry::make('refund_receipts')
                                            ->collection('refund_receipts')
                                            ->label('Bukti Transfer Pengembalian Dana')
                                            ->visible(fn ($record) => $record->getMedia('refund_receipts')->isNotEmpty())
                                            ->slideZoomable(true)
                                            ->slideDraggable(true)
                                            ->slideWidth('auto')
                                            ->slideHeight('auto')
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        // TAB 5: AUDIT & TRACKING
                        Tab::make('Tracking & Audit')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Fieldset::make('Informasi Sistem')
                                    ->schema([
                                        Grid::make(2)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextEntry::make('created_at')
                                                    ->label('Dibuat')
                                                    ->dateTime('d F Y H:i')
                                                    ->timezone('Asia/Jakarta')
                                                    ->icon('heroicon-o-plus-circle')
                                                    ->iconPosition(IconPosition::Before),

                                                TextEntry::make('updated_at')
                                                    ->label('Terakhir Diupdate')
                                                    ->dateTime('d F Y H:i')
                                                    ->timezone('Asia/Jakarta')
                                                    ->icon('heroicon-o-pencil-square')
                                                    ->iconPosition(IconPosition::Before),
                                            ]),
                                    ])
                                    ->columnSpanFull(),

                                Fieldset::make('Konfirmasi Settlement')
                                    ->schema([
                                        Grid::make(2)
                                            ->columnSpanFull()
                                            ->schema([
                                                TextEntry::make('confirmer.user.name')
                                                    ->label('Dikonfirmasi Oleh (FO)')
                                                    ->placeholder('Belum dikonfirmasi')
                                                    ->icon('heroicon-o-user')
                                                    ->iconPosition(IconPosition::Before),

                                                TextEntry::make('confirmed_at')
                                                    ->label('Tanggal Konfirmasi')
                                                    ->placeholder('Belum dikonfirmasi')
                                                    ->dateTime('d F Y H:i')
                                                    ->timezone('Asia/Jakarta')
                                                    ->icon('heroicon-o-calendar')
                                                    ->iconPosition(IconPosition::Before),
                                            ]),
                                    ])
                                    ->columnSpanFull()
                                    ->visible(fn ($record) => $record->confirmed_at !== null),

                                Fieldset::make('Status History')
                                    ->schema([
                                        TextEntry::make('previous_status')
                                            ->label('Status Sebelumnya')
                                            ->placeholder('Tidak ada')
                                            ->badge()
                                            ->formatStateUsing(function ($state) {
                                                return match ($state) {
                                                    'draft' => 'Draft',
                                                    'pending' => 'Menunggu Proses',
                                                    'waiting_dpr_approval' => 'Menunggu Approval DPR',
                                                    'approved' => 'Disetujui',
                                                    'waiting_refund' => 'Menunggu Bukti Pengembalian Dana',
                                                    'waiting_confirmation' => 'Menunggu Konfirmasi Finance Operator',
                                                    'closed' => 'Selesai',
                                                    'rejected' => 'Ditolak',
                                                    default => $state ?? 'N/A',
                                                };
                                            }),
                                    ])
                                    ->columnSpanFull()
                                    ->visible(fn ($record) => ! empty($record->previous_status)),
                            ]),
                    ]),
            ]);
    }
}

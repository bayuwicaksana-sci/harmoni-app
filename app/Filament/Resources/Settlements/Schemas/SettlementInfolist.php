<?php

namespace App\Filament\Resources\Settlements\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class SettlementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Settlement')
                    ->columnSpanFull()
                    ->columns(4)
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->schema([
                        TextEntry::make('settlement_number')
                            ->label('Settlement ID')
                            ->placeholder('N/A'),
                        TextEntry::make('submitter.user.name')
                            ->label('Submitter')
                            ->placeholder('N/A'),
                        TextEntry::make('submit_date')
                            ->label('Tanggal Pengajuan')
                            ->dateTime(timezone: 'Asia/Jakarta')
                            ->placeholder('-'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('generatedPaymentRequest.request_number')
                            ->label('DPR Terkait')
                            ->placeholder('Tidak ada DPR')
                            ->url(fn ($record) => $record->generated_payment_request_id
                                ? route('filament.admin.resources.daily-payment-requests.view', ['record' => $record->generated_payment_request_id])
                                : null
                            )
                            ->color('primary')
                            ->icon('heroicon-o-document-text')
                            ->visible(fn ($record) => $record->generated_payment_request_id !== null),
                    ]),
                Section::make('Ringkasan Financial')
                    ->description('Ringkasan anggaran dan realisasi pengeluaran')
                    ->columnSpanFull()
                    ->columns(2)
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('approved_budget')
                                    ->label('Anggaran Disetujui')
                                    ->helperText('Total anggaran yang disetujui (excluding cancelled items)')
                                    ->placeholder('Rp 0,00')
                                    ->money(currency: 'IDR', locale: 'id')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('primary'),
                                TextEntry::make('spent_budget')
                                    ->label('Total Dibelanjakan')
                                    ->helperText('Realisasi aktual + item baru')
                                    ->placeholder('Rp 0,00')
                                    ->money(currency: 'IDR', locale: 'id')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color('info'),
                                TextEntry::make('budget_variance')
                                    ->label('Selisih (Variance)')
                                    ->helperText('Disetujui - Dibelanjakan')
                                    ->placeholder('Rp 0,00')
                                    ->money(currency: 'IDR', locale: 'id')
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color(function ($state) {
                                        if ($state < 0) {
                                            return 'danger';
                                        }
                                        if ($state > 0) {
                                            return 'success';
                                        }

                                        return 'gray';
                                    }),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('cancelled_budget')
                                    ->label('Item Dibatalkan')
                                    ->helperText('Total anggaran item yang tidak terealisasi')
                                    ->placeholder('Rp 0,00')
                                    ->money(currency: 'IDR', locale: 'id')
                                    ->size(TextSize::Medium)
                                    ->weight(FontWeight::SemiBold)
                                    ->color('warning'),
                                TextEntry::make('new_request_budget')
                                    ->label('Item Baru (Unplanned)')
                                    ->helperText('Total pengeluaran tidak direncanakan')
                                    ->placeholder('Rp 0,00')
                                    ->money(currency: 'IDR', locale: 'id')
                                    ->size(TextSize::Medium)
                                    ->weight(FontWeight::SemiBold)
                                    ->color('info'),
                            ]),
                    ]),
                Section::make('Ringkasan Tindakan')
                    ->description('Aksi yang perlu dilakukan berdasarkan settlement ini. PENTING: Transaksi harus dilakukan per COA (tidak bisa di-offset antar COA berbeda).')
                    ->columnSpanFull()
                    ->columns(2)
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->schema([
                        TextEntry::make('total_amount_to_return')
                            ->label('Total Dikembalikan ke Finance')
                            ->helperText('Employee → Company (lihat detail per COA di bawah)')
                            ->placeholder('Rp 0,00')
                            ->money(currency: 'IDR', locale: 'id')
                            ->color('warning')
                            ->size(TextSize::Large)
                            ->weight(FontWeight::Bold),
                        TextEntry::make('total_amount_to_reimburse')
                            ->label('Total Reimbursement ke Employee')
                            ->helperText('Company → Employee (lihat detail per COA di bawah)')
                            ->placeholder('Rp 0,00')
                            ->money(currency: 'IDR', locale: 'id')
                            ->color('success')
                            ->size(TextSize::Large)
                            ->weight(FontWeight::Bold),
                    ]),
                Section::make('Bukti Pengembalian Dana')
                    ->description('Bukti transfer pengembalian dana dari employee ke perusahaan')
                    ->columnSpanFull()
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->schema([
                        SpatieMediaLibraryImageEntry::make('refund_receipts')
                            ->label('')
                            ->collection('refund_receipts')
                            // ->disk('public')
                            ->imageHeight(400),
                        // ->square()
                        // ->visibility('public'),
                        TextEntry::make('refundConfirmer.user.name')
                            ->label('Dikonfirmasi Oleh')
                            ->placeholder('Belum dikonfirmasi')
                            ->icon('heroicon-o-user'),
                        TextEntry::make('refund_confirmed_at')
                            ->label('Tanggal Konfirmasi')
                            ->dateTime(timezone: 'Asia/Jakarta')
                            ->placeholder('-')
                            ->icon('heroicon-o-calendar'),
                    ])
                    ->visible(fn ($record) => $record->getMedia('refund_receipts')->isNotEmpty()),
                Section::make('Konfirmasi Settlement')
                    ->description('Informasi konfirmasi dari Finance Operator')
                    ->columnSpanFull()
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->schema([
                        TextEntry::make('confirmer.user.name')
                            ->label('Dikonfirmasi Oleh')
                            ->icon('heroicon-o-user'),
                        TextEntry::make('confirmed_at')
                            ->label('Tanggal Konfirmasi')
                            ->dateTime(timezone: 'Asia/Jakarta')
                            ->icon('heroicon-o-calendar'),
                    ])
                    ->visible(fn ($record) => $record->confirmed_by !== null),
                Section::make('Catatan Revisi')
                    ->description('Alasan revisi dari Finance Operator')
                    ->columnSpanFull()
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->schema([
                        TextEntry::make('revision_notes')
                            ->label('')
                            ->color('warning'),
                    ])
                    ->visible(fn ($record) => $record->revision_notes !== null),
                Section::make('Rekonsiliasi Per COA')
                    ->description('Detail pengeluaran dikelompokkan berdasarkan Chart of Account. Setiap COA ditampilkan secara terpisah.')
                    ->columnSpanFull()
                    ->collapsed()
                    ->icon(Heroicon::OutlinedDocumentChartBar)
                    ->schema([
                        RepeatableEntry::make('reconciliation.reconciliations')
                            ->label('')
                            ->schema([
                                KeyValueEntry::make('summary')
                                    ->label('')
                                    ->columnSpanFull()
                                    ->keyLabel('Kategori')
                                    ->valueLabel('Nilai'),
                            ]),
                    ]),
            ]);
    }
}

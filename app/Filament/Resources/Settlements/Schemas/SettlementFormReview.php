<?php

namespace App\Filament\Resources\Settlements\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;

class SettlementFormReview
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(self::formFields());
    }

    public static function formFields(): array
    {
        return [
            // Overall Financial Summary
            Fieldset::make('Ringkasan Financial Keseluruhan')
                ->columnSpanFull()
                ->columns(4)
                ->schema([
                    TextEntry::make('review_approved_amount')
                        ->label('Total Pengeluaran yang Disetujui')
                        ->state(fn (Get $get) => 'Rp '.($get('approved_request_amount') ?? '0,00')),
                    TextEntry::make('review_cancelled_amount')
                        ->label('Total Nominal yang Dibatalkan')
                        ->state(fn (Get $get) => 'Rp '.($get('cancelled_amount') ?? '0,00')),
                    TextEntry::make('review_spent_amount')
                        ->label('Total Nominal yang Dibelanjakan')
                        ->state(fn (Get $get) => 'Rp '.($get('spent_amount') ?? '0,00')),
                    TextEntry::make('review_variance')
                        ->label('Selisih Nominal Keseluruhan')
                        ->state(fn (Get $get) => 'Rp '.($get('variance') ?? '0,00'))
                        ->extraAttributes(fn (Get $get) => [
                            'class' => static::getVarianceColor($get('variance')),
                        ]),
                ]),

            // // Receipts Breakdown
            // Section::make('Detail Nota & Item')
            //     ->schema([
            //         RepeatableEntry::make('receipts_review')
            //             ->label('')
            //             ->state(fn (Get $get) => $get('settlementReceipts') ?? [])
            //             ->schema([
            //                 Grid::make(1)
            //                     ->schema([
            //                         TextEntry::make('realization_date')
            //                             ->date('j M Y')
            //                             ->label('Tanggal Realisasi'),

            //                         // Request Items Table
            //                         Section::make('Item Request yang Direalisasi')
            //                             ->schema([
            //                                 RepeatableEntry::make('request_items')
            //                                     ->label('')
            //                                     ->schema([
            //                                         Grid::make(9)
            //                                             ->schema([
            //                                                 TextEntry::make('request_item_id')
            //                                                     ->label('Item'),

            //                                                 TextEntry::make('is_realized')
            //                                                     ->label('Status')
            //                                                     ->formatStateUsing(function ($state) {
            //                                                         if ($state) {
            //                                                             return new HtmlString('<span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md bg-success-100 text-success-700 dark:bg-success-500/10 dark:text-success-400">✓ Terealisasi</span>');
            //                                                         }

            //                                                         return new HtmlString('<span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md bg-danger-100 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400">✗ Dibatalkan</span>');
            //                                                     }),

            //                                                 TextEntry::make('request_quantity')
            //                                                     ->label('Qty (Request)')
            //                                                     ->placeholder('N/A'),

            //                                                 TextEntry::make('actual_quantity')
            //                                                     ->label('Qty (Aktual)'),

            //                                                 TextEntry::make('request_amount_per_item')
            //                                                     ->label('Harga/item (Request)')
            //                                                     ->money(currency: 'IDR', locale: 'id'),

            //                                                 TextEntry::make('actual_amount_per_item')
            //                                                     ->label('Harga/item (Aktual)')
            //                                                     ->money(currency: 'IDR', locale: 'id'),

            //                                                 TextEntry::make('request_total_price')
            //                                                     ->label('Total Request')
            //                                                     ->money(currency: 'IDR', locale: 'id'),

            //                                                 TextEntry::make('actual_total_price')
            //                                                     ->label('Total Aktual')
            //                                                     ->money(currency: 'IDR', locale: 'id'),

            //                                                 TextEntry::make('variance')
            //                                                     ->label('Selisih')
            //                                                     ->formatStateUsing(function ($state) {
            //                                                         $variance = $state ?? '0,00';
            //                                                         $color = static::getVarianceColor($variance);

            //                                                         return new HtmlString("<span class='font-semibold {$color}'>Rp {$variance}</span>");
            //                                                     }),
            //                                             ]),
            //                                     ])
            //                                     ->visible(fn (Get $get) => ! empty($get('request_items'))),
            //                             ])
            //                             ->collapsed()
            //                             ->visible(fn (Get $get) => ! empty($get('request_items'))),

            //                         // New Request Items Table
            //                         Section::make('Item Request Baru yang Ditambahkan')
            //                             ->schema([
            //                                 RepeatableEntry::make('new_request_items')
            //                                     ->label('')
            //                                     ->schema([
            //                                         Grid::make(7)
            //                                             ->schema([
            //                                                 TextEntry::make('coa_id')
            //                                                     ->label('COA')
            //                                                     ->formatStateUsing(fn ($state) => Coa::find($state)->value('name')),

            //                                                 TextEntry::make('program_activity_id')
            //                                                     ->label('Aktivitas')
            //                                                     ->formatStateUsing(function ($state) {
            //                                                         ProgramActivity::find($state)->value('name');
            //                                                     }),

            //                                                 TextEntry::make('item')
            //                                                     ->label('Item'),

            //                                                 TextEntry::make('qty')
            //                                                     ->label('Qty'),

            //                                                 TextEntry::make('unit_qty')
            //                                                     ->label('Unit'),

            //                                                 TextEntry::make('base_price')
            //                                                     ->label('Harga/item'),

            //                                                 TextEntry::make('total_price')
            //                                                     ->label('Total'),
            //                                             ]),
            //                                     ])
            //                                     ->visible(fn (Get $get) => ! empty($get('new_request_items'))),
            //                             ])
            //                             ->collapsed()
            //                             ->visible(fn (Get $get) => ! empty($get('new_request_items'))),
            //                     ]),
            //             ]),
            //         // ->itemLabel(fn ($state, $index): string => 'Nota ke-'.($index + 1))

            //     ])
            //     ->collapsible()
            //     ->collapsed(),

            // // COA-wise Budget Reconciliation
            // Section::make('Rekonsiliasi Anggaran per COA')
            //     ->description('Menampilkan detail penggunaan dana yang dibatalkan untuk item baru dalam COA yang sama')
            //     ->schema([
            //         RepeatableEntry::make('coa_reconciliation')
            //             ->label('')
            //             ->state(fn (Get $get) => static::calculateCoaReconciliation($get)['coa_breakdown'])
            //             ->visible(fn (Get $get) => ! empty(static::calculateCoaReconciliation($get)['coa_breakdown']))
            //             ->schema([
            //                 Grid::make(1)
            //                     ->schema([
            //                         TextEntry::make('name')
            //                             ->label('')
            //                             ->formatStateUsing(function ($record, $state) {
            //                                 $hasCancelled = ($record['cancelled_budget'] ?? 0) > 0;

            //                                 $badge = $hasCancelled
            //                                     ? '<span class="ml-2 inline-flex items-center px-2 py-1 text-xs font-medium rounded-md bg-warning-100 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">Ada Dana Dibatalkan</span>'
            //                                     : '';

            //                                 return new HtmlString("<div class='text-lg font-semibold'>{$state}{$badge}</div>");
            //                             }),

            //                         Grid::make(4)
            //                             ->schema([
            //                                 TextEntry::make('cancelled_budget')
            //                                     ->label('Dana Dibatalkan')
            //                                     ->formatStateUsing(function ($state) {
            //                                         $amount = static::formatMoney($state ?? 0);

            //                                         return new HtmlString("<span class='text-lg font-bold text-danger-600 dark:text-danger-400'>Rp {$amount}</span>");
            //                                     }),

            //                                 TextEntry::make('new_items_spending')
            //                                     ->label('Item Baru (COA Sama)')
            //                                     ->formatStateUsing(function ($state) {
            //                                         $amount = static::formatMoney($state ?? 0);

            //                                         return new HtmlString("<span class='text-lg font-bold text-info-600 dark:text-info-400'>Rp {$amount}</span>");
            //                                     }),

            //                                 TextEntry::make('net_offset')
            //                                     ->label('Dana yang Digunakan')
            //                                     ->formatStateUsing(function ($state) {
            //                                         $amount = static::formatMoney($state ?? 0);

            //                                         return new HtmlString("<span class='text-lg font-bold text-success-600 dark:text-success-400'>Rp {$amount}</span>");
            //                                     }),

            //                                 TextEntry::make('amount_to_return')
            //                                     ->label('Harus Dikembalikan')
            //                                     ->formatStateUsing(function ($state) {
            //                                         $amount = static::formatMoney($state ?? 0);
            //                                         $color = ($state ?? 0) > 0
            //                                             ? 'text-primary-600 dark:text-primary-400'
            //                                             : 'text-gray-600 dark:text-gray-400';

            //                                         return new HtmlString("<span class='text-lg font-bold {$color}'>Rp {$amount}</span>");
            //                                     }),
            //                             ]),

            //                         TextEntry::make('explanation')
            //                             ->label('')
            //                             ->state(function ($record) {
            //                                 $cancelled = $record['cancelled_budget'] ?? 0;
            //                                 $offset = $record['net_offset'] ?? 0;
            //                                 $toReturn = $record['amount_to_return'] ?? 0;

            //                                 if ($cancelled <= 0) {
            //                                     return '';
            //                                 }

            //                                 $cancelledFormatted = static::formatMoney($cancelled);
            //                                 $offsetFormatted = static::formatMoney($offset);
            //                                 $toReturnFormatted = static::formatMoney($toReturn);

            //                                 if ($offset > 0) {
            //                                     $text = "<strong>Penjelasan:</strong> Dari Rp {$cancelledFormatted} yang dibatalkan, Rp {$offsetFormatted} digunakan untuk item baru dalam COA yang sama. Sisanya <strong>Rp {$toReturnFormatted}</strong> harus dikembalikan ke Finance.";
            //                                 } else {
            //                                     $text = "<strong>Penjelasan:</strong> Tidak ada item baru dalam COA ini, sehingga seluruh dana dibatalkan <strong>Rp {$toReturnFormatted}</strong> harus dikembalikan ke Finance.";
            //                                 }

            //                                 return new HtmlString("<div class='text-sm p-3 bg-info-50 dark:bg-info-500/10 border-l-4 border-info-400 rounded'>{$text}</div>");
            //                             })
            //                             ->visible(fn ($record) => ($record['cancelled_budget'] ?? 0) > 0),
            //                     ]),
            //             ]),
            //     ])
            //     ->collapsible(),

            // // Final Return Summary
            // Section::make('Ringkasan Pengembalian Dana ke Finance')
            //     ->schema([
            //         Grid::make(3)
            //             ->schema([
            //                 TextEntry::make('total_cancelled_summary')
            //                     ->label('Total Dana Dibatalkan (Semua COA)')
            //                     ->state(function (Get $get) {
            //                         $amount = static::formatMoney(static::calculateCoaReconciliation($get)['total_cancelled']);

            //                         return new HtmlString("<span class='text-2xl font-bold text-danger-600 dark:text-danger-400'>Rp {$amount}</span>");
            //                     }),

            //                 TextEntry::make('total_reused_summary')
            //                     ->label('Dana Digunakan untuk Item Baru')
            //                     ->state(function (Get $get) {
            //                         $amount = static::formatMoney(static::calculateCoaReconciliation($get)['total_reused']);

            //                         return new HtmlString("<span class='text-2xl font-bold text-success-600 dark:text-success-400'>- Rp {$amount}</span>");
            //                     }),

            //                 TextEntry::make('net_return_summary')
            //                     ->label('Net Pengembalian ke Finance')
            //                     ->state(function (Get $get) {
            //                         $amount = static::formatMoney(static::calculateCoaReconciliation($get)['net_return']);

            //                         return new HtmlString("<span class='text-3xl font-bold text-primary-600 dark:text-primary-400'>Rp {$amount}</span>");
            //                     }),
            //             ]),
            //     ])
            //     ->collapsible(),

            // You can now use this in RepeatableEntry
            RepeatableEntry::make('reconciliations')
                ->label('Rekonsiliasi per COA')
                ->state(fn (Get $get) => static::calculateReviewData($get)['reconciliations'])
                ->columns(12)
                ->schema([
                    TextEntry::make('coa_name')
                        ->columnSpanFull()
                        ->hiddenLabel()
                        ->size(TextSize::Large)
                        ->weight(FontWeight::Black),

                    KeyValueEntry::make('calculations')
                        ->label('Ringkasan Rekonsiliasi')
                        ->columnSpan(4)
                        ->keyLabel('Ringkasan')
                        ->valueLabel('Nilai'),
                    // TextEntry::make('approved_budget')
                    //     ->label('Anggaran Disetujui')
                    //     ->money('IDR', locale: 'id')
                    //     ->formatStateUsing(fn ($state) => 'Rp '.number_format($state, 2, ',', '.')),

                    // TextEntry::make('spent_budget')
                    //     ->label('Total Pengeluaran')
                    //     ->money('IDR', locale: 'id')
                    //     ->formatStateUsing(fn ($state) => 'Rp '.number_format($state, 2, ',', '.')),

                    // TextEntry::make('cancelled_budget')
                    //     ->label('Anggaran Dibatalkan')
                    //     ->money('IDR', locale: 'id')
                    //     ->formatStateUsing(fn ($state) => 'Rp '.number_format($state, 2, ',', '.')),

                    // TextEntry::make('total_new_request_items')
                    //     ->label('Total Item Baru')
                    //     ->money('IDR', locale: 'id')
                    //     ->formatStateUsing(fn ($state) => 'Rp '.number_format($state, 2, ',', '.')),

                    // TextEntry::make('amount_to_return')
                    //     ->label('Dikembalikan ke Finance')
                    //     ->money('IDR', locale: 'id')
                    //     ->formatStateUsing(fn ($state) => 'Rp '.number_format($state, 2, ',', '.'))
                    //     ->color('success'),

                    // TextEntry::make('amount_to_reimburse')
                    //     ->label('Perlu Reimbursement')
                    //     ->money('IDR', locale: 'id')
                    //     ->formatStateUsing(fn ($state) => 'Rp '.number_format($state, 2, ',', '.'))
                    //     ->color('danger'),

                    // Nested items
                    RepeatableEntry::make('items')
                        ->label('Detail Item')
                        ->columnSpan(8)
                        ->table([
                            TableColumn::make('Status')->width('100px'),
                            TableColumn::make('Deskripsi Item')->width('250px'),
                            TableColumn::make('Qty (Request)')->width('150px'),
                            TableColumn::make('Qty (Aktual)')->width('150px'),
                            TableColumn::make('Unit Qty')->width('200px'),
                            TableColumn::make('Harga/item (Request)')->width('200px'),
                            TableColumn::make('Harga/item (Aktual)')->width('200px'),
                            TableColumn::make('Total Request')->width('200px'),
                            TableColumn::make('Total Aktual')->width('200px'),
                            TableColumn::make('Selisih')->width('200px'),
                        ])
                        ->extraAttributes([
                            'class' => 'overflow-x-auto p-0.5 pb-1.5 *:first:table-fixed *:first:[&_thead]:[&_th]:first:w-[46px] *:first:table-fixed *:first:[&_thead]:[&_th]:last:w-[46px]',
                        ])
                        ->schema([
                            TextEntry::make('status')
                                ->formatStateUsing(function ($state) {
                                    if ($state === 'realized') {
                                        return 'Terealisasi';
                                    } elseif ($state === 'cancelled') {
                                        return 'Dibatalkan';
                                    } else {
                                        return 'Item Baru';
                                    }
                                })
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'realized' => 'success',
                                    'cancelled' => 'danger',
                                    'new' => 'info',
                                }),
                            TextEntry::make('description'),
                            TextEntry::make('request_quantity')
                                ->placeholder('N/A'),
                            TextEntry::make('actual_quantity')
                                ->placeholder('N/A'),
                            TextEntry::make('unit_quantity'),
                            TextEntry::make('request_amount_per_item')
                                ->placeholder('N/A')
                                ->formatStateUsing(fn ($state) => 'Rp '.$state),
                            TextEntry::make('actual_amount_per_item')
                                ->formatStateUsing(fn ($state) => 'Rp '.$state),
                            TextEntry::make('request_total_price')
                                ->placeholder('N/A')
                                ->formatStateUsing(fn ($state) => 'Rp '.$state),
                            TextEntry::make('actual_total_price')
                                ->formatStateUsing(fn ($state) => 'Rp '.$state),
                            TextEntry::make('variance')
                                ->formatStateUsing(fn ($state) => 'Rp '.$state)
                                ->placeholder('N/A'),
                        ]),
                ]),

            // Summary section
            Grid::make(3)
                ->schema([
                    TextEntry::make('total_return')
                        ->label('Total Dikembalikan')
                        ->state(fn (Get $get) => 'Rp '.number_format(static::calculateReviewData($get)['total_amount_to_return'], 2, ',', '.')),

                    TextEntry::make('total_reimburse')
                        ->label('Total Reimbursement')
                        ->state(fn (Get $get) => 'Rp '.number_format(static::calculateReviewData($get)['total_amount_to_reimburse'], 2, ',', '.')),
                ]),
        ];
    }
}

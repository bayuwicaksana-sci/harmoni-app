<?php

namespace App\Filament\Resources\Settlements\Pages;

use App\Enums\DPRStatus;
use App\Enums\RequestItemStatus;
use App\Enums\SettlementStatus;
use App\Filament\Resources\Settlements\SettlementResource;
use App\Models\Coa;
use App\Models\DailyPaymentRequest;
use App\Models\RequestItem;
use App\Models\Settlement;
use App\Models\SettlementReceipt;
use App\Services\ApprovalService;
use App\Services\SettlementFormDataService;
use CreateSettlementForm;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

use function Symfony\Component\Clock\now;

class CreateSettlement extends CreateRecord
{
    use HasWizard;

    protected static string $resource = SettlementResource::class;

    protected static bool $canCreateAnother = false;

    protected ?array $settlementReceiptData;

    public function hasSkippableSteps(): bool
    {
        return true;
    }

    protected function getSteps(): array
    {
        return [
            Step::make('Formulir Settlement')
                ->schema([
                    Fieldset::make('Ringkasan Financial')
                        ->columnSpanFull()
                        ->columns(4)
                        ->schema([
                            TextInput::make('approved_request_amount')
                                ->label('Pengeluaran yang Disetujui')
                                ->prefix('Rp')
                                ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                ->readOnly()
                                ->dehydrated(false),
                            TextInput::make('cancelled_amount')
                                ->label('Nominal yang Dibatalkan')
                                ->prefix('Rp')
                                ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                ->readOnly()
                                ->dehydrated(false),
                            TextInput::make('spent_amount')
                                ->label('Nominal yang Dibelanjakan')
                                ->prefix('Rp')
                                ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                ->readOnly()
                                ->dehydrated(false),
                            TextInput::make('variance')
                                ->label('Selisih Nominal')
                                ->prefix('Rp')
                                ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                ->readOnly()
                                ->dehydrated(false),
                        ]),
                    CreateSettlementForm::make(),
                ]),
            // Step::make('Review')
            //     ->schema([
            //         \Filament\Forms\Components\Hidden::make('review_trigger')
            //             ->default(fn () => time())
            //             ->live(onBlur: false)
            //             ->afterStateUpdatedJs(
            //                 <<<'JS'
            //                     const formatMoney = (num) => {
            //                         if (num === 0) return '0,00';
            //                         const isNegative = num < 0;
            //                         const absNum = Math.abs(num);
            //                         const formatted = absNum.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
            //                         return isNegative ? '-' + formatted : formatted;
            //                     };
            //                     const parseMoney = (val) => parseFloat((val ?? '0').toString().replace(/\./g, '').replace(',', '.')) || 0;

            //                     // Get all settlement receipts data
            //                     const receipts = $get('../../settlementReceipts') ?? {};

            //                     let approvedAmount = 0;
            //                     let cancelledAmount = 0;
            //                     let spentAmount = 0;

            //                     // Calculate financial summary
            //                     Object.values(receipts).forEach(receipt => {
            //                         const requestItems = receipt?.requestItems ?? {};
            //                         Object.values(requestItems).forEach(item => {
            //                             const itemId = item?.id;
            //                             const isNewItem = itemId === 'new';

            //                             const requestTotal = parseMoney(item?.request_total_price);
            //                             const actualTotal = parseMoney(item?.actual_total_price);
            //                             const isRealized = item?.is_realized !== false && item?.is_realized !== 0 && item?.is_realized !== '0' && item?.is_realized !== null;

            //                             if (!isNewItem) {
            //                                 // Existing item
            //                                 approvedAmount += requestTotal;

            //                                 if (!isRealized) {
            //                                     cancelledAmount += requestTotal;
            //                                 } else {
            //                                     spentAmount += actualTotal;
            //                                 }
            //                             } else {
            //                                 // New item
            //                                 if (isRealized) {
            //                                     spentAmount += actualTotal;
            //                                 }
            //                             }
            //                         });
            //                     });

            //                     const variance = approvedAmount - spentAmount;

            //                     // Set all review fields
            //                     $set('review_approved_request_amount', formatMoney(approvedAmount));
            //                     $set('review_cancelled_amount', formatMoney(cancelledAmount));
            //                     $set('review_spent_amount', formatMoney(spentAmount));
            //                     $set('review_variance', formatMoney(variance));
            //                 JS
            //             ),

            //         // Financial Summary Section
            //         \Filament\Schemas\Components\Section::make('Ringkasan Financial')
            //             ->description('Ringkasan keseluruhan anggaran dan realisasi')
            //             ->columnSpanFull()
            //             ->columns(4)
            //             ->schema([
            //                 TextEntry::make('review_approved_request_amount')
            //                     ->label('Pengeluaran yang Disetujui')
            //                     ->money(currency: 'IDR', locale: 'id')
            //                     ->state(fn (Get $get) => (float) str_replace(['.', ','], ['', '.'], $get('approved_request_amount'))),
            //                 TextEntry::make('review_cancelled_amount')
            //                     ->label('Nominal yang Dibatalkan')
            //                     ->money(currency: 'IDR', locale: 'id')
            //                     ->state(fn (Get $get) => (float) str_replace(['.', ','], ['', '.'], $get('cancelled_amount'))),
            //                 TextEntry::make('review_spent_amount')
            //                     ->label('Nominal yang Dibelanjakan')
            //                     ->money(currency: 'IDR', locale: 'id')
            //                     ->state(fn (Get $get) => (float) str_replace(['.', ','], ['', '.'], $get('spent_amount'))),
            //                 TextEntry::make('review_variance')
            //                     ->label('Selisih Nominal')
            //                     ->money(currency: 'IDR', locale: 'id')
            //                     ->state(fn (Get $get) => (float) str_replace(['.', ','], ['', '.'], $get('variance'))),
            //             ]),

            //         // Submitter Next Action Section
            //         Section::make('Tindakan yang Harus Dilakukan')
            //             ->description('Instruksi untuk submitter berdasarkan hasil rekonsiliasi')
            //             ->columnSpanFull()
            //             ->schema(function (Get $get): array {
            //                 $reviewData = self::calculateReviewData($get);

            //                 if (empty($reviewData['reconciliations'])) {
            //                     return [
            //                         TextEntry::make('no_data')
            //                             ->label('')
            //                             ->state('Tidak ada data untuk ditampilkan'),
            //                     ];
            //                 }

            //                 $components = [];
            //                 $hasRefund = false;
            //                 $hasReimbursement = false;

            //                 // Check each COA
            //                 foreach ($reviewData['reconciliations'] as $coaGroup) {
            //                     if ($coaGroup['amount_to_return'] > 0) {
            //                         $hasRefund = true;
            //                     }
            //                     if ($coaGroup['amount_to_reimburse'] > 0) {
            //                         $hasReimbursement = true;
            //                     }
            //                 }

            //                 if ($hasRefund && $hasReimbursement) {
            //                     $components[] = TextEntry::make('action_status')
            //                         ->label('')
            //                         ->state('REFUND DAN REIMBURSEMENT DIPERLUKAN')
            //                         ->badge()
            //                         ->color('warning')
            //                         ->columnSpanFull();

            //                     $components[] = TextEntry::make('action_description')
            //                         ->label('')
            //                         ->state('Karena terdapat sisa anggaran di satu COA dan pengeluaran baru di COA lain, maka TIDAK DAPAT dilakukan rekonsiliasi lintas-COA. Anda harus:')
            //                         ->columnSpanFull();

            //                     $components[] = TextEntry::make('action_refund_amount')
            //                         ->label('Total yang Harus Di-REFUND ke Finance')
            //                         ->state(fn () => $reviewData['total_amount_to_return'])
            //                         ->money('IDR', locale: 'id')
            //                         ->hint('Kembalikan seluruh sisa anggaran')
            //                         ->hintColor('danger');

            //                     $components[] = TextEntry::make('action_reimbursement_amount')
            //                         ->label('Total yang Perlu Di-REIMBURSE')
            //                         ->state(fn () => $reviewData['total_amount_to_reimburse'])
            //                         ->money('IDR', locale: 'id')
            //                         ->hint('Tunggu reimbursement dari Finance')
            //                         ->hintColor('warning');
            //                 } elseif ($hasRefund) {
            //                     $components[] = TextEntry::make('action_status')
            //                         ->label('')
            //                         ->state('REFUND DIPERLUKAN')
            //                         ->badge()
            //                         ->color('danger')
            //                         ->columnSpanFull();

            //                     $components[] = TextEntry::make('action_description')
            //                         ->label('')
            //                         ->state('Terdapat sisa anggaran yang harus dikembalikan ke Finance.')
            //                         ->columnSpanFull();

            //                     $components[] = TextEntry::make('action_refund_amount')
            //                         ->label('Total yang Harus Di-REFUND')
            //                         ->state(fn () => $reviewData['total_amount_to_return'])
            //                         ->money('IDR', locale: 'id')
            //                         ->hint('Kembalikan ke Finance')
            //                         ->hintColor('danger')
            //                         ->columnSpanFull();
            //                 } elseif ($hasReimbursement) {
            //                     $components[] = TextEntry::make('action_status')
            //                         ->label('')
            //                         ->state('REIMBURSEMENT DIPERLUKAN')
            //                         ->badge()
            //                         ->color('warning')
            //                         ->columnSpanFull();

            //                     $components[] = TextEntry::make('action_description')
            //                         ->label('')
            //                         ->state('Terdapat kelebihan pengeluaran yang memerlukan reimbursement dari Finance.')
            //                         ->columnSpanFull();

            //                     $components[] = TextEntry::make('action_reimbursement_amount')
            //                         ->label('Total yang Perlu Di-REIMBURSE')
            //                         ->state(fn () => $reviewData['total_amount_to_reimburse'])
            //                         ->money('IDR', locale: 'id')
            //                         ->hint('Tunggu reimbursement dari Finance')
            //                         ->hintColor('warning')
            //                         ->columnSpanFull();
            //                 } else {
            //                     $components[] = TextEntry::make('action_status')
            //                         ->label('')
            //                         ->state('SETTLEMENT SEIMBANG')
            //                         ->badge()
            //                         ->color('success')
            //                         ->columnSpanFull();

            //                     $components[] = TextEntry::make('action_description')
            //                         ->label('')
            //                         ->state('Tidak ada refund atau reimbursement yang diperlukan. Settlement sudah seimbang.')
            //                         ->columnSpanFull();
            //                 }

            //                 return $components;
            //             }),

            //         // COA Reconciliation Section
            //         Section::make('Rekonsiliasi per COA')
            //             ->description('Detail rekonsiliasi anggaran yang dibatalkan dan item baru per COA')
            //             ->columnSpanFull()
            //             ->collapsed()
            //             ->schema(function (Get $get): array {
            //                 $reconciliation = self::calculateCoaReconciliation($get);

            //                 if (empty($reconciliation['coa_breakdown'])) {
            //                     return [
            //                         Text::make('Tidak ada data rekonsiliasi'),
            //                     ];
            //                 }

            //                 $components = [];

            //                 foreach ($reconciliation['coa_breakdown'] as $coa) {
            //                     $components[] = Fieldset::make($coa['code'].' - '.$coa['name'])
            //                         ->columnSpanFull()
            //                         ->columns(4)
            //                         ->schema([
            //                             TextInput::make('coa_'.$coa['id'].'_cancelled')
            //                                 ->label('Anggaran Dibatalkan')
            //                                 ->prefix('Rp')
            //                                 ->default(self::formatMoney($coa['cancelled_budget']))
            //                                 ->readOnly()
            //                                 ->dehydrated(false),
            //                             TextInput::make('coa_'.$coa['id'].'_new_spending')
            //                                 ->label('Pengeluaran Item Baru')
            //                                 ->prefix('Rp')
            //                                 ->default(self::formatMoney($coa['new_items_spending']))
            //                                 ->readOnly()
            //                                 ->dehydrated(false),
            //                             TextInput::make('coa_'.$coa['id'].'_offset')
            //                                 ->label('Offset Bersih')
            //                                 ->prefix('Rp')
            //                                 ->default(self::formatMoney($coa['net_offset']))
            //                                 ->readOnly()
            //                                 ->dehydrated(false),
            //                             TextInput::make('coa_'.$coa['id'].'_return')
            //                                 ->label('Nominal Dikembalikan')
            //                                 ->prefix('Rp')
            //                                 ->default(self::formatMoney($coa['amount_to_return']))
            //                                 ->readOnly()
            //                                 ->dehydrated(false)
            //                                 ->extraInputAttributes(['class' => 'font-bold']),
            //                         ]);
            //                 }

            //                 // Add summary
            //                 $components[] = Fieldset::make('Total Keseluruhan')
            //                     ->columnSpanFull()
            //                     ->columns(3)
            //                     ->schema([
            //                         TextInput::make('total_cancelled_summary')
            //                             ->label('Total Dibatalkan')
            //                             ->prefix('Rp')
            //                             ->default(self::formatMoney($reconciliation['total_cancelled']))
            //                             ->readOnly()
            //                             ->dehydrated(false)
            //                             ->extraInputAttributes(['class' => 'font-bold']),
            //                         TextInput::make('total_reused_summary')
            //                             ->label('Total Digunakan Kembali')
            //                             ->prefix('Rp')
            //                             ->default(self::formatMoney($reconciliation['total_reused']))
            //                             ->readOnly()
            //                             ->dehydrated(false)
            //                             ->extraInputAttributes(['class' => 'font-bold']),
            //                         TextInput::make('net_return_summary')
            //                             ->label('Net Dikembalikan')
            //                             ->prefix('Rp')
            //                             ->default(self::formatMoney($reconciliation['net_return']))
            //                             ->readOnly()
            //                             ->dehydrated(false)
            //                             ->extraInputAttributes(['class' => 'font-bold text-success-600']),
            //                     ]);

            //                 return $components;
            //             }),

            //         // Detailed Reconciliation by COA Section
            //         \Filament\Schemas\Components\Section::make('Detail Rekonsiliasi per COA')
            //             ->description('Rincian lengkap item per COA dengan perhitungan reimbursement')
            //             ->columnSpanFull()
            //             ->collapsed()
            //             ->schema(function (Get $get): array {
            //                 $reviewData = self::calculateReviewData($get);

            //                 if (empty($reviewData['reconciliations'])) {
            //                     return [
            //                         \Filament\Schemas\Components\Text::make('Tidak ada data rekonsiliasi detail'),
            //                     ];
            //                 }

            //                 $components = [];

            //                 foreach ($reviewData['reconciliations'] as $coaIndex => $coaGroup) {
            //                     $coaComponents = [];

            //                     // Calculations section
            //                     $coaComponents[] = \Filament\Schemas\Components\Grid::make(5)
            //                         ->schema([
            //                             TextInput::make('detail_coa_'.$coaIndex.'_approved')
            //                                 ->label('Anggaran Disetujui')
            //                                 ->prefix('Rp')
            //                                 ->default(self::formatMoney($coaGroup['approved_budget']))
            //                                 ->readOnly()
            //                                 ->dehydrated(false),
            //                             TextInput::make('detail_coa_'.$coaIndex.'_cancelled')
            //                                 ->label('Anggaran Dibatalkan')
            //                                 ->prefix('Rp')
            //                                 ->default(self::formatMoney($coaGroup['cancelled_budget']))
            //                                 ->readOnly()
            //                                 ->dehydrated(false),
            //                             TextInput::make('detail_coa_'.$coaIndex.'_realized')
            //                                 ->label('Total Realisasi')
            //                                 ->prefix('Rp')
            //                                 ->default(self::formatMoney($coaGroup['total_realized_items']))
            //                                 ->readOnly()
            //                                 ->dehydrated(false),
            //                             TextInput::make('detail_coa_'.$coaIndex.'_new_items')
            //                                 ->label('Total Item Baru')
            //                                 ->prefix('Rp')
            //                                 ->default(self::formatMoney($coaGroup['total_new_items']))
            //                                 ->readOnly()
            //                                 ->dehydrated(false),
            //                             TextInput::make('detail_coa_'.$coaIndex.'_spent')
            //                                 ->label('Total Pengeluaran')
            //                                 ->prefix('Rp')
            //                                 ->default(self::formatMoney($coaGroup['spent_budget']))
            //                                 ->readOnly()
            //                                 ->dehydrated(false)
            //                                 ->extraInputAttributes(['class' => 'font-bold']),
            //                         ]);

            //                     // Items list
            //                     if (! empty($coaGroup['items'])) {
            //                         foreach ($coaGroup['items'] as $itemIndex => $item) {
            //                             $statusColor = match ($item['status']) {
            //                                 'realized' => 'success',
            //                                 'cancelled' => 'danger',
            //                                 'new' => 'info',
            //                                 default => 'gray',
            //                             };

            //                             $statusLabel = match ($item['status']) {
            //                                 'realized' => 'Direalisasi',
            //                                 'cancelled' => 'Dibatalkan',
            //                                 'new' => 'Item Baru',
            //                                 default => 'Unknown',
            //                             };

            //                             if ($item['type'] === 'existing_item') {
            //                                 $coaComponents[] = Fieldset::make($item['description'])
            //                                     ->columnSpanFull()
            //                                     ->columns(5)
            //                                     ->extraAttributes(['class' => 'border-l-4 border-'.$statusColor.'-500'])
            //                                     ->schema([
            //                                         \Filament\Schemas\Components\Text::make($statusLabel)
            //                                             ->badge()
            //                                             ->color($statusColor)
            //                                             ->columnSpanFull(),
            //                                         TextInput::make('item_'.$coaIndex.'_'.$itemIndex.'_req_qty')
            //                                             ->label('Qty Diajukan')
            //                                             ->default($item['request_quantity'].' '.$item['unit_quantity'])
            //                                             ->readOnly()
            //                                             ->dehydrated(false),
            //                                         TextInput::make('item_'.$coaIndex.'_'.$itemIndex.'_act_qty')
            //                                             ->label('Qty Aktual')
            //                                             ->default($item['actual_quantity'].' '.$item['unit_quantity'])
            //                                             ->readOnly()
            //                                             ->dehydrated(false),
            //                                         TextInput::make('item_'.$coaIndex.'_'.$itemIndex.'_req_price')
            //                                             ->label('Total Diajukan')
            //                                             ->prefix('Rp')
            //                                             ->default($item['request_total_price'])
            //                                             ->readOnly()
            //                                             ->dehydrated(false),
            //                                         TextInput::make('item_'.$coaIndex.'_'.$itemIndex.'_act_price')
            //                                             ->label('Total Aktual')
            //                                             ->prefix('Rp')
            //                                             ->default($item['actual_total_price'])
            //                                             ->readOnly()
            //                                             ->dehydrated(false),
            //                                         TextInput::make('item_'.$coaIndex.'_'.$itemIndex.'_variance')
            //                                             ->label('Selisih')
            //                                             ->prefix('Rp')
            //                                             ->default($item['variance'])
            //                                             ->readOnly()
            //                                             ->dehydrated(false),
            //                                     ]);
            //                             } else {
            //                                 $coaComponents[] = Fieldset::make($item['description'])
            //                                     ->columnSpanFull()
            //                                     ->columns(3)
            //                                     ->extraAttributes(['class' => 'border-l-4 border-'.$statusColor.'-500'])
            //                                     ->schema([
            //                                         \Filament\Schemas\Components\Text::make($statusLabel)
            //                                             ->badge()
            //                                             ->color($statusColor)
            //                                             ->columnSpanFull(),
            //                                         TextInput::make('item_'.$coaIndex.'_'.$itemIndex.'_qty')
            //                                             ->label('Qty')
            //                                             ->default($item['actual_quantity'].' '.$item['unit_quantity'])
            //                                             ->readOnly()
            //                                             ->dehydrated(false),
            //                                         TextInput::make('item_'.$coaIndex.'_'.$itemIndex.'_unit_price')
            //                                             ->label('Harga Satuan')
            //                                             ->prefix('Rp')
            //                                             ->default($item['actual_amount_per_item'])
            //                                             ->readOnly()
            //                                             ->dehydrated(false),
            //                                         TextInput::make('item_'.$coaIndex.'_'.$itemIndex.'_total')
            //                                             ->label('Total')
            //                                             ->prefix('Rp')
            //                                             ->default($item['actual_total_price'])
            //                                             ->readOnly()
            //                                             ->dehydrated(false),
            //                                     ]);
            //                             }
            //                         }
            //                     }

            //                     // COA summary
            //                     $coaComponents[] = \Filament\Schemas\Components\Grid::make(2)
            //                         ->schema([
            //                             TextInput::make('detail_coa_'.$coaIndex.'_return')
            //                                 ->label('Dikembalikan ke Finance')
            //                                 ->prefix('Rp')
            //                                 ->default(self::formatMoney($coaGroup['amount_to_return']))
            //                                 ->readOnly()
            //                                 ->dehydrated(false)
            //                                 ->extraInputAttributes(['class' => 'font-bold text-success-600']),
            //                             TextInput::make('detail_coa_'.$coaIndex.'_reimburse')
            //                                 ->label('Perlu Reimbursement')
            //                                 ->prefix('Rp')
            //                                 ->default(self::formatMoney($coaGroup['amount_to_reimburse']))
            //                                 ->readOnly()
            //                                 ->dehydrated(false)
            //                                 ->extraInputAttributes(['class' => 'font-bold text-warning-600']),
            //                         ]);

            //                     $components[] = \Filament\Schemas\Components\Section::make($coaGroup['coa_code'].' - '.$coaGroup['coa_name'])
            //                         ->columnSpanFull()
            //                         ->collapsible()
            //                         ->schema($coaComponents);
            //                 }

            //                 // Add overall summary
            //                 $components[] = Fieldset::make('Total Keseluruhan')
            //                     ->columnSpanFull()
            //                     ->columns(2)
            //                     ->schema([
            //                         TextInput::make('grand_total_return')
            //                             ->label('Total Dikembalikan ke Finance')
            //                             ->prefix('Rp')
            //                             ->default(self::formatMoney($reviewData['total_amount_to_return']))
            //                             ->readOnly()
            //                             ->dehydrated(false)
            //                             ->extraInputAttributes(['class' => 'font-bold text-success-600']),
            //                         TextInput::make('grand_total_reimburse')
            //                             ->label('Total Perlu Reimbursement')
            //                             ->prefix('Rp')
            //                             ->default(self::formatMoney($reviewData['total_amount_to_reimburse']))
            //                             ->readOnly()
            //                             ->dehydrated(false)
            //                             ->extraInputAttributes(['class' => 'font-bold text-warning-600']),
            //                     ]);

            //                 return $components;
            //             }),

            //         // Settlement Receipts Summary Section
            //         \Filament\Schemas\Components\Section::make('Ringkasan Kuitansi Settlement')
            //             ->description('Daftar lengkap semua kuitansi dan item yang di-settle')
            //             ->columnSpanFull()
            //             ->collapsed()
            //             ->schema(function (Get $get): array {
            //                 $receipts = $get('settlementReceipts') ?? [];

            //                 if (empty($receipts)) {
            //                     return [
            //                         \Filament\Schemas\Components\Text::make('Tidak ada kuitansi settlement'),
            //                     ];
            //                 }

            //                 $components = [];

            //                 foreach ($receipts as $receiptIndex => $receipt) {
            //                     $receiptComponents = [];
            //                     $receiptNumber = (int) $receiptIndex + 1;

            //                     // Receipt date
            //                     $receiptComponents[] = TextInput::make('receipt_'.$receiptIndex.'_date')
            //                         ->label('Tanggal Realisasi')
            //                         ->default($receipt['realization_date'] ?? '-')
            //                         ->readOnly()
            //                         ->dehydrated(false)
            //                         ->columnSpanFull();

            //                     // Items
            //                     $items = $receipt['requestItems'] ?? [];
            //                     if (! empty($items)) {
            //                         foreach ($items as $itemIndex => $item) {
            //                             $isNewItem = ($item['id'] ?? null) === 'new';
            //                             $isRealized = ($item['is_realized'] ?? true);

            //                             $statusColor = match (true) {
            //                                 $isNewItem && $isRealized => 'info',
            //                                 ! $isNewItem && $isRealized => 'success',
            //                                 ! $isRealized => 'danger',
            //                                 default => 'gray',
            //                             };

            //                             $statusLabel = match (true) {
            //                                 $isNewItem && $isRealized => 'Item Baru',
            //                                 ! $isNewItem && $isRealized => 'Direalisasi',
            //                                 ! $isRealized => 'Dibatalkan',
            //                                 default => 'Unknown',
            //                             };

            //                             if (! $isNewItem) {
            //                                 $receiptComponents[] = Fieldset::make($item['description'] ?? 'N/A')
            //                                     ->columnSpanFull()
            //                                     ->columns(4)
            //                                     ->extraAttributes(['class' => 'border-l-4 border-'.$statusColor.'-500'])
            //                                     ->schema([
            //                                         \Filament\Schemas\Components\Text::make($statusLabel)
            //                                             ->badge()
            //                                             ->color($statusColor)
            //                                             ->columnSpanFull(),
            //                                         TextInput::make('receipt_'.$receiptIndex.'_item_'.$itemIndex.'_req_qty')
            //                                             ->label('Qty Diajukan')
            //                                             ->default(($item['quantity'] ?? '0').' '.($item['unit_quantity'] ?? ''))
            //                                             ->readOnly()
            //                                             ->dehydrated(false),
            //                                         TextInput::make('receipt_'.$receiptIndex.'_item_'.$itemIndex.'_act_qty')
            //                                             ->label('Qty Aktual')
            //                                             ->default(($item['act_quantity'] ?? '0').' '.($item['unit_quantity'] ?? ''))
            //                                             ->readOnly()
            //                                             ->dehydrated(false),
            //                                         TextInput::make('receipt_'.$receiptIndex.'_item_'.$itemIndex.'_price')
            //                                             ->label('Harga')
            //                                             ->default('Rp '.($item['amount_per_item'] ?? '0').' → Rp '.($item['act_amount_per_item'] ?? '0'))
            //                                             ->readOnly()
            //                                             ->dehydrated(false),
            //                                         TextInput::make('receipt_'.$receiptIndex.'_item_'.$itemIndex.'_total')
            //                                             ->label('Total')
            //                                             ->prefix('Rp')
            //                                             ->default($item['actual_total_price'] ?? '0')
            //                                             ->readOnly()
            //                                             ->dehydrated(false),
            //                                     ]);
            //                             } else {
            //                                 $receiptComponents[] = Fieldset::make($item['description'] ?? 'N/A')
            //                                     ->columnSpanFull()
            //                                     ->columns(3)
            //                                     ->extraAttributes(['class' => 'border-l-4 border-'.$statusColor.'-500'])
            //                                     ->schema([
            //                                         \Filament\Schemas\Components\Text::make($statusLabel)
            //                                             ->badge()
            //                                             ->color($statusColor)
            //                                             ->columnSpanFull(),
            //                                         TextInput::make('receipt_'.$receiptIndex.'_item_'.$itemIndex.'_qty')
            //                                             ->label('Qty')
            //                                             ->default(($item['act_quantity'] ?? '0').' '.($item['unit_quantity'] ?? ''))
            //                                             ->readOnly()
            //                                             ->dehydrated(false),
            //                                         TextInput::make('receipt_'.$receiptIndex.'_item_'.$itemIndex.'_unit_price')
            //                                             ->label('Harga Satuan')
            //                                             ->prefix('Rp')
            //                                             ->default($item['act_amount_per_item'] ?? '0')
            //                                             ->readOnly()
            //                                             ->dehydrated(false),
            //                                         TextInput::make('receipt_'.$receiptIndex.'_item_'.$itemIndex.'_total')
            //                                             ->label('Total')
            //                                             ->prefix('Rp')
            //                                             ->default($item['actual_total_price'] ?? '0')
            //                                             ->readOnly()
            //                                             ->dehydrated(false),
            //                                     ]);
            //                             }
            //                         }
            //                     } else {
            //                         $receiptComponents[] = \Filament\Schemas\Components\Text::make('Tidak ada item')->columnSpanFull();
            //                     }

            //                     $components[] = \Filament\Schemas\Components\Section::make('Kuitansi #'.$receiptNumber)
            //                         ->columnSpanFull()
            //                         ->collapsible()
            //                         ->schema($receiptComponents);
            //                 }

            //                 return $components;
            //             }),
            //     ]),
            Step::make('Review')
                ->schema([
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
                ]),
        ];
    }

    public static function recalculateFinancialSummary(Get $get, Set $set, string $pathPrefix = ''): void
    {
        $parseMoney = fn ($val) => (float) str_replace(['.', ','], ['', '.'], $val ?? '0');
        $formatMoney = fn ($num) => number_format($num, 2, ',', '.');

        $receiptsPath = $pathPrefix ? $pathPrefix.'settlementReceipts' : 'settlementReceipts';
        $receipts = $get($receiptsPath) ?? [];

        $approvedAmount = 0;
        $cancelledAmount = 0;
        $spentAmount = 0;

        foreach ($receipts as $receipt) {
            foreach ($receipt['requestItems'] ?? [] as $item) {
                $requestTotal = $parseMoney($item['request_total_price'] ?? '0');
                $approvedAmount += $requestTotal;

                $isRealized = $item['is_realized'] ?? true;
                if (! $isRealized) {
                    $cancelledAmount += $requestTotal;
                } else {
                    $spentAmount += $parseMoney($item['actual_total_price'] ?? '0');
                }
            }

            // // new_request_items only affects spent_amount, not approved_amount
            // foreach ($receipt['new_request_items'] ?? [] as $item) {
            //     $totalPrice = $parseMoney($item['total_price'] ?? '0');
            //     $spentAmount += $totalPrice;
            // }
        }

        $variance = $approvedAmount - $spentAmount;

        $set($pathPrefix.'approved_request_amount', $formatMoney($approvedAmount));
        $set($pathPrefix.'cancelled_amount', $formatMoney($cancelledAmount));
        $set($pathPrefix.'spent_amount', $formatMoney($spentAmount));
        $set($pathPrefix.'variance', $formatMoney($variance));
    }

    public static function getVarianceColor(?string $variance): string
    {
        if (! $variance) {
            return 'text-gray-600 dark:text-gray-400';
        }

        $amount = static::parseMoney($variance);

        if ($amount > 0) {
            return 'text-success-600 dark:text-success-400';
        } elseif ($amount < 0) {
            return 'text-danger-600 dark:text-danger-400';
        }

        return 'text-gray-600 dark:text-gray-400';
    }

    public static function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', '.');
    }

    public static function keyValueMoney(float $amount): HtmlString
    {
        return new HtmlString('<div class="flex justify-between items-center w-full"><div>Rp</div> <div>'.self::formatMoney($amount).'</div></div>');
    }

    public static function parseMoney(?string $value): float
    {
        return (float) str_replace(['.', ','], ['', '.'], $value ?? '0');
    }

    public static function calculateCoaReconciliation(Get $get): array
    {
        static $cachedResult = null;
        static $cachedHash = null;

        // Create a hash of the receipts data to detect changes
        $receipts = $get('settlementReceipts') ?? [];
        $currentHash = md5(json_encode($receipts));

        // Return cached result if data hasn't changed
        if ($cachedResult !== null && $cachedHash === $currentHash) {
            return $cachedResult;
        }

        $coaData = [];
        $parseMoney = fn ($val) => static::parseMoney($val);

        // Collect all RequestItem IDs and COA IDs for batch loading
        $requestItemIds = [];
        $coaIds = [];

        foreach ($receipts as $receipt) {
            foreach ($receipt['requestItems'] ?? [] as $item) {
                $itemId = $item['id'] ?? null;

                // Collect IDs for batch loading
                if ($itemId && $itemId !== 'new') {
                    $requestItemIds[] = $itemId;
                }

                // Collect COA IDs
                if (! empty($item['coa_id'])) {
                    $coaIds[] = $item['coa_id'];
                }
            }
        }

        // Batch load RequestItems with COA relationship (avoid N+1)
        $requestItems = RequestItem::query()
            ->with('coa:id,name,code')
            ->whereIn('id', array_unique($requestItemIds))
            ->get()
            ->keyBy('id');

        // Batch load COAs (avoid N+1)
        $coas = Coa::query()
            ->whereIn('id', array_unique($coaIds))
            ->get(['id', 'name', 'code'])
            ->keyBy('id');

        // Process all items
        foreach ($receipts as $receipt) {
            foreach ($receipt['requestItems'] ?? [] as $item) {
                $itemId = $item['id'] ?? null;
                $coaId = $item['coa_id'] ?? null;

                if (! $coaId) {
                    continue;
                }

                // Determine if this is an existing or new item
                $isNewItem = $itemId === 'new';
                $isRealized = $item['is_realized'] ?? true;

                // Get COA info
                $coaInfo = null;
                if ($isNewItem) {
                    $coaInfo = $coas->get($coaId);
                } else {
                    $requestItem = $requestItems->get($itemId);
                    $coaInfo = $requestItem?->coa;
                }

                if (! $coaInfo) {
                    continue;
                }

                // Initialize COA data if not exists
                if (! isset($coaData[$coaId])) {
                    $coaData[$coaId] = [
                        'id' => $coaId,
                        'name' => $coaInfo->name,
                        'code' => $coaInfo->code ?? '',
                        'cancelled_budget' => 0,
                        'new_items_spending' => 0,
                    ];
                }

                // Calculate cancelled budget (only from existing items that are not realized)
                if (! $isNewItem && ! $isRealized) {
                    $cancelledAmount = $parseMoney($item['request_total_price'] ?? '0');
                    $coaData[$coaId]['cancelled_budget'] += $cancelledAmount;
                }

                // Calculate new items spending (only from new items that are realized)
                if ($isNewItem && $isRealized) {
                    $newItemTotal = $parseMoney($item['actual_total_price'] ?? '0');
                    $coaData[$coaId]['new_items_spending'] += $newItemTotal;
                }
            }
        }

        // Calculate net offset and amount to return for each COA
        $totalCancelled = 0;
        $totalReused = 0;
        $netReturn = 0;

        foreach ($coaData as $coaId => &$coa) {
            $coa['net_offset'] = min($coa['cancelled_budget'], $coa['new_items_spending']);
            $coa['amount_to_return'] = $coa['cancelled_budget'] - $coa['net_offset'];

            $totalCancelled += $coa['cancelled_budget'];
            $totalReused += $coa['net_offset'];
            $netReturn += $coa['amount_to_return'];
        }
        unset($coa);

        // Sort by COA code
        uasort($coaData, fn ($a, $b) => strcmp($a['code'], $b['code']));

        $cachedResult = [
            'coa_breakdown' => array_values($coaData),
            'total_cancelled' => $totalCancelled,
            'total_reused' => $totalReused,
            'net_return' => $netReturn,
        ];

        $cachedHash = $currentHash;

        return $cachedResult;
    }

    public static function calculateReviewData(Get $get): array
    {
        $receipts = $get('settlementReceipts') ?? [];

        if (empty($receipts)) {
            return [
                'reconciliations' => [],
                'total_amount_to_return' => 0,
                'total_amount_to_reimburse' => 0,
            ];
        }

        $parseMoney = fn ($val) => (float) str_replace(['.', ','], ['', '.'], $val ?? '0');

        // Step 1: Collect all RequestItem IDs for batch loading
        $requestItemIds = [];
        $coaIds = [];

        foreach ($receipts as $receipt) {
            foreach ($receipt['requestItems'] ?? [] as $item) {
                $itemId = $item['id'] ?? null;

                if ($itemId && $itemId !== 'new') {
                    $requestItemIds[] = $itemId;
                }

                if (! empty($item['coa_id'])) {
                    $coaIds[] = $item['coa_id'];
                }
            }
        }

        // Step 2: Batch load RequestItems with COA (avoid N+1 query problem)
        $requestItemsMap = [];
        if (! empty($requestItemIds)) {
            $requestItemsMap = \App\Models\RequestItem::query()
                ->with('coa:id,name,code')
                ->whereIn('id', array_unique($requestItemIds))
                ->get(['id', 'coa_id', 'description', 'quantity', 'unit_quantity', 'amount_per_item'])
                ->keyBy('id');
        }

        // Batch load COAs
        $coasMap = \App\Models\Coa::query()
            ->whereIn('id', array_unique($coaIds))
            ->get(['id', 'name', 'code'])
            ->keyBy('id');

        // Step 3: Group data by COA and calculate metrics
        $coaGroups = [];

        foreach ($receipts as $receiptIndex => $receipt) {
            foreach ($receipt['requestItems'] ?? [] as $itemIndex => $item) {
                $itemId = $item['id'] ?? null;
                $coaId = $item['coa_id'] ?? null;

                if (! $itemId || ! $coaId) {
                    continue;
                }

                $isNewItem = $itemId === 'new';

                // Get COA info
                $coaInfo = null;
                $description = $item['description'] ?? '';

                if ($isNewItem) {
                    $coaInfo = $coasMap->get($coaId);
                } else {
                    $requestItem = $requestItemsMap->get($itemId);
                    if (! $requestItem) {
                        continue;
                    }
                    $coaInfo = $requestItem->coa;
                    $description = $requestItem->description ?? $description;
                }

                if (! $coaInfo) {
                    continue;
                }

                // Initialize COA group if not exists
                if (! isset($coaGroups[$coaId])) {
                    $coaGroups[$coaId] = [
                        'coa_id' => $coaId,
                        'coa_name' => $coaInfo->name ?? '',
                        'coa_code' => $coaInfo->code ?? '',
                        'approved_budget' => 0,
                        'spent_budget' => 0,
                        'cancelled_budget' => 0,
                        'total_new_items' => 0,
                        'total_realized_items' => 0,
                        'calculations' => [
                            'Anggaran Disetujui' => 0,
                            'Anggaran Dibatalkan' => 0,
                            'Total Realisasi' => 0,
                            'Total Item Baru' => 0,
                            'Total Pengeluaran' => 0,
                        ],
                        'items' => [],
                    ];
                }

                $isRealized = $item['is_realized'] ?? true;
                $requestTotal = $parseMoney($item['request_total_price'] ?? '0');
                $actualTotal = $parseMoney($item['actual_total_price'] ?? '0');

                // Process based on item type
                if ($isNewItem) {
                    // New item
                    if ($isRealized) {
                        $coaGroups[$coaId]['spent_budget'] += $actualTotal;
                        $coaGroups[$coaId]['total_new_items'] += $actualTotal;
                        $coaGroups[$coaId]['calculations']['Total Item Baru'] = self::keyValueMoney($coaGroups[$coaId]['total_new_items']);
                        $coaGroups[$coaId]['calculations']['Total Pengeluaran'] = self::keyValueMoney($coaGroups[$coaId]['spent_budget']);
                    }

                    // Add item details
                    $coaGroups[$coaId]['items'][] = [
                        'type' => 'new_item',
                        'receipt_index' => $receiptIndex,
                        'item_index' => $itemIndex,
                        'status' => $isRealized ? 'new' : 'cancelled',
                        'description' => $description,
                        'is_realized' => $isRealized,
                        'actual_quantity' => $item['act_quantity'] ?? 0,
                        'unit_quantity' => $item['unit_quantity'] ?? '',
                        'actual_amount_per_item' => $item['act_amount_per_item'] ?? '0,00',
                        'actual_total_price' => $item['actual_total_price'] ?? '0,00',
                    ];
                } else {
                    // Existing item
                    $coaGroups[$coaId]['approved_budget'] += $requestTotal;
                    $coaGroups[$coaId]['calculations']['Anggaran Disetujui'] = self::keyValueMoney($coaGroups[$coaId]['approved_budget']);

                    if ($isRealized) {
                        $coaGroups[$coaId]['spent_budget'] += $actualTotal;
                        $coaGroups[$coaId]['total_realized_items'] += $actualTotal;
                        $coaGroups[$coaId]['calculations']['Total Realisasi'] = self::keyValueMoney($coaGroups[$coaId]['total_realized_items']);
                        $coaGroups[$coaId]['calculations']['Total Pengeluaran'] = self::keyValueMoney($coaGroups[$coaId]['spent_budget']);
                    } else {
                        $coaGroups[$coaId]['cancelled_budget'] += $requestTotal;
                        $coaGroups[$coaId]['calculations']['Anggaran Dibatalkan'] = self::keyValueMoney($coaGroups[$coaId]['cancelled_budget']);
                    }

                    // Add item details
                    $coaGroups[$coaId]['items'][] = [
                        'type' => 'existing_item',
                        'receipt_index' => $receiptIndex,
                        'item_index' => $itemIndex,
                        'request_item_id' => $itemId,
                        'status' => $isRealized ? 'realized' : 'cancelled',
                        'description' => $description,
                        'is_realized' => $isRealized,
                        'request_quantity' => $item['quantity'] ?? 0,
                        'actual_quantity' => $item['act_quantity'] ?? 0,
                        'unit_quantity' => $item['unit_quantity'] ?? '',
                        'request_amount_per_item' => $item['amount_per_item'] ?? '0,00',
                        'actual_amount_per_item' => $item['act_amount_per_item'] ?? '0,00',
                        'request_total_price' => $item['request_total_price'] ?? '0,00',
                        'actual_total_price' => $item['actual_total_price'] ?? '0,00',
                        'variance' => $item['variance'] ?? '0,00',
                    ];
                }
            }
        }

        // Step 4: Calculate amount_to_return and amount_to_reimburse for each COA
        $totalAmountToReturn = 0;
        $totalAmountToReimburse = 0;

        foreach ($coaGroups as &$group) {
            // Amount to return: if spent less than approved
            $group['amount_to_return'] = max(0, $group['approved_budget'] - $group['spent_budget']);
            $group['calculations']['Dikembalikan ke Finance'] = self::keyValueMoney($group['amount_to_return']);

            // Amount to reimburse: if spent more than approved (overspending)
            $overspending = $group['spent_budget'] - $group['approved_budget'];
            $group['amount_to_reimburse'] = max(0, $overspending);
            $group['calculations']['Perlu Reimbursement'] = self::keyValueMoney($group['amount_to_reimburse']);

            $totalAmountToReturn += $group['amount_to_return'];
            $totalAmountToReimburse += $group['amount_to_reimburse'];
        }
        unset($group);

        // Step 5: Sort by COA code for better readability
        uasort($coaGroups, fn ($a, $b) => strcmp($a['coa_code'], $b['coa_code']));

        return [
            'reconciliations' => array_values($coaGroups),
            'total_amount_to_return' => $totalAmountToReturn,
            'total_amount_to_reimburse' => $totalAmountToReimburse,
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // dd($data);

        $approvedAmount = 0;
        $cancelledAmount = 0;
        $spentAmount = 0;
        $newRequestItemTotal = 0;

        foreach ($data['settlementReceipts'] as $receiptIndex => $receipt) {
            foreach ($receipt['requestItems'] as $itemIndex => $item) {
                if (empty($item['id'])) {
                    continue;
                }

                if ($item['id'] !== 'new') {
                    $requestItem = RequestItem::find($item['id']);

                    if (! $requestItem) {
                        // Log error or throw exception
                        Log::warning("RequestItem not found: {$item['id']}");

                        continue;
                    }

                    // Validate that RequestItem is in correct status
                    if ($requestItem->status !== RequestItemStatus::WaitingSettlement) {
                        throw new \Exception("RequestItem {$requestItem->id} is not in WaitingSettlement status");
                    }

                    // Calculate request_total_price from database values
                    $requestTotalPrice = $requestItem->quantity * $requestItem->amount_per_item;

                    // Add to $data for storage
                    $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['quantity'] = (int) $requestItem->quantity;
                    $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['unit_quantity'] = $requestItem->unit_quantity;
                    $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['amount_per_item'] = (float) $requestItem->amount_per_item;
                    $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['request_total_price'] = $requestTotalPrice;

                    // Add to approved amount
                    $approvedAmount += $requestTotalPrice;

                    // Check if realized
                    $isRealized = $item['is_realized'] ?? true;

                    if (! $isRealized) {
                        // If not realized, add to cancelled amount
                        $cancelledAmount += $requestTotalPrice;

                        // Set actual values to 0
                        $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['act_quantity'] = 0;
                        $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['act_amount_per_item'] = 0;
                        $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['actual_total_price'] = 0;
                        $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['variance'] = $requestTotalPrice;
                    } else {
                        foreach ($item['item_image'] ?? [] as $index => $file) {
                            $requestItem->addMedia($file)->toMediaCollection('request_item_image', 'local');
                        }
                        // Calculate actual_total_price from user input
                        $actualQuantity = $item['act_quantity'] ?? 0;
                        $actualAmountPerItem = $item['act_amount_per_item'] ?? 0;
                        $actualTotalPrice = $actualQuantity * $actualAmountPerItem;

                        // Validate that actual values are provided
                        if ($actualQuantity <= 0 || $actualAmountPerItem <= 0) {
                            throw new \Exception('Actual quantity and amount per item must be greater than 0 for realized items');
                        }

                        // Add to $data for storage
                        $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['actual_total_price'] = $actualTotalPrice;

                        // Add to spent amount
                        $spentAmount += $actualTotalPrice;

                        // Calculate variance
                        $variance = $requestTotalPrice - $actualTotalPrice;
                        $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['variance'] = $variance;
                    }
                } else {
                    // Calculate total_price
                    $qty = $item['act_quantity'];
                    $basePrice = $item['act_amount_per_item'];
                    $totalPrice = $qty * $basePrice;

                    // Add to $data for storage
                    $data['settlementReceipts'][$receiptIndex]['requestItems'][$itemIndex]['total_price'] = $totalPrice;

                    // Add to new_request_item_total
                    $newRequestItemTotal += $totalPrice;

                    // new_request_items only affect spent_amount
                    $spentAmount += $totalPrice;
                }
            }
        }

        // Calculate final variance
        $variance = $approvedAmount - $spentAmount;

        // Add financial summary to data
        $data['approved_request_amount'] = $approvedAmount;
        $data['cancelled_amount'] = $cancelledAmount;
        $data['spent_amount'] = $spentAmount;
        $data['new_request_item_total'] = $newRequestItemTotal;
        $data['variance'] = $variance;

        $this->settlementReceiptData = $data;

        unset($data);

        // dd($this->settlementReceiptData);

        $settlementData = [
            'submitter_id' => Auth::user()->employee->id,
            'submit_date' => now(),
            'status' => SettlementStatus::Draft,
        ];

        return $settlementData;
    }

    protected function afterCreate(): void
    {
        $settlement = $this->record;

        try {
            DB::transaction(function () use ($settlement) {
                foreach ($this->settlementReceiptData['settlementReceipts'] ?? [] as $index => $receipt) {
                    $createdReceipt = SettlementReceipt::create([
                        'settlement_id' => $settlement->id,
                        'realization_date' => $receipt['realization_date'] ?? null,
                    ]);

                    // Handle attachment media
                    if (isset($receipt['attachment'])) {
                        $createdReceipt->addMedia($receipt['attachment'])
                            ->toMediaCollection('settlement_receipt_attachments', 'local');
                    }

                    // Add the created SettlementReceipt ID to form data for processing
                    $this->settlementReceiptData['settlementReceipts'][$index]['id'] = $createdReceipt->id;
                }

                // Service 1: Extract form data (already done in mutateFormDataBeforeCreate, stored in $this->settlementData)
                $formDataService = app(SettlementFormDataService::class);
                $structuredData = $formDataService->extractFormData($this->settlementReceiptData);

                // Service 2: Process items from receipts
                $itemProcessor = app(\App\Services\SettlementItemProcessingService::class);
                $results = $itemProcessor->processReceipts(
                    $structuredData['receipts'],
                    $settlement,
                    false // create mode
                );

                // Service 3: Categorize items
                $categorized = $itemProcessor->categorizeItems(collect($results));

                // Service 4: Process settlement with same-COA reconciliation first
                $offsetService = app(\App\Services\SettlementOffsetCalculationService::class);
                $overspentResults = $categorized['overspent'] ? collect($categorized['overspent'])->flatten(1)->toArray() : [];

                // Use the new processSettlement method that handles same-COA internal reconciliation
                $processResult = $offsetService->processSettlement($categorized, $overspentResults, $settlement);

                // Collect all DPR items (offsets + reimbursements + new items)
                $dprItems = array_merge(
                    $processResult['offset_items'],
                    $processResult['reimbursement_items']
                );

                // Add new items (flatten COA groups)
                foreach ($categorized['new'] ?? [] as $coaId => $newItems) {
                    foreach ($newItems as $newResult) {
                        $dprItems[] = $newResult['item'];
                    }
                }

                // Check if DPR is needed using the new service method
                $dprService = app(\App\Services\SettlementDPRService::class);
                $requiresDPR = $dprService->requiresDPRFromResults([
                    'categorized' => $categorized,
                    'reimbursement_items' => $processResult['reimbursement_items'],
                    'offset_items' => $processResult['offset_items'],
                ]);

                // Create DPR if there are items requiring approval
                if ($requiresDPR && ! empty($dprItems)) {
                    $this->createDPRForSettlement($settlement, $dprItems);
                } else {
                    // No DPR needed - set final status based on processed refund amount
                    $this->finalizeSettlementWithoutDPR($settlement, $processResult);
                }
            });
        } catch (\Exception $e) {
            // Delete the settlement if validation fails
            throw new \Exception($e);
            $settlement->delete();

            // Show validation error notification
            Notification::make()
                ->title('Validasi Gagal')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            // Redirect back to create page
            $this->halt();
        }
    }

    /**
     * Create DPR for settlement items
     */
    protected function createDPRForSettlement(Settlement $settlement, array $items): void
    {
        $dpr = DailyPaymentRequest::create([
            'request_date' => $settlement->submit_date,
            'status' => DPRStatus::Pending,
            'requester_id' => Auth::user()->employee->id,
        ]);

        // Link all items to DPR
        foreach ($items as $item) {
            $item->daily_payment_request_id = $dpr->id;
            $item->save();
        }

        // Submit through approval workflow
        app(ApprovalService::class)->submitRequest($dpr);

        // Link DPR to Settlement and set status
        $settlement->update([
            'generated_payment_request_id' => $dpr->id,
            'status' => SettlementStatus::WaitingDPRApproval,
        ]);
    }

    /**
     * Finalize settlement status when no DPR is needed
     */
    protected function finalizeSettlementWithoutDPR(
        Settlement $settlement,
        ?array $offsetResult = null
    ): void {
        // Use offset-calculated refund if available, fallback to net_settlement
        $refundAmount = $offsetResult['total_refund_amount'] ?? $settlement->net_settlement;

        if ($refundAmount > 0) {
            // Employee owes money - need refund confirmation
            $settlement->update([
                'status' => SettlementStatus::WaitingRefund,
                'refund_amount' => $refundAmount,
            ]);
        } else {
            // No refund needed - wait for FO confirmation
            $settlement->update([
                'status' => SettlementStatus::WaitingConfirmation,
            ]);

            app(\App\Services\SettlementNotificationService::class)
                ->notifyFinanceOperatorForConfirmation($settlement);
        }
    }
}

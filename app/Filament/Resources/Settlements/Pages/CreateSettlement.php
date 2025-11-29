<?php

namespace App\Filament\Resources\Settlements\Pages;

use App\Enums\DPRStatus;
use App\Enums\RequestItemStatus;
use App\Enums\SettlementStatus;
use App\Filament\Resources\Settlements\SettlementResource;
use App\Models\Coa;
use App\Models\DailyPaymentRequest;
use App\Models\ProgramActivity;
use App\Models\ProgramActivityItem;
use App\Models\RequestItem;
use App\Models\Settlement;
use App\Models\SettlementReceipt;
use App\Services\ApprovalService;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Enums\Alignment;
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

    public function hasSkippableSteps(): bool
    {
        return true;
    }

    protected static string $resource = SettlementResource::class;

    protected static bool $canCreateAnother = false;

    protected ?array $settlementData;

    // protected function getRedirectUrl(): string
    // {
    //     return SettlementResource::getUrl('index');
    // }

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
                    Repeater::make('settlementReceipts')
                        ->label('Daftar Nota')
                        ->addActionLabel('Tambahkan Nota Baru')
                        ->collapsible()
                        ->itemLabel('Nota ke - ')
                        ->itemNumbers()
                        ->columns(12)
                        ->schema([
                            SpatieMediaLibraryFileUpload::make('attachment')
                                ->required()
                                ->label('Upload Nota')
                                ->multiple(false)
                                ->dehydrated(true)
                                ->storeFiles(false)
                                ->columnSpan(6),
                            DatePicker::make('realization_date')
                                ->required()
                                ->native(false)
                                ->label('Tanggal Realisasi')
                                ->belowLabel('Sesuai Nota')
                                ->displayFormat('j M Y')
                                ->columnSpan(6),
                            Repeater::make('request_items')
                                ->label('Pilih Item Request')
                                ->addActionLabel('Tambah Item Request')
                                ->compact()
                                ->addActionAlignment(Alignment::Start)
                                ->columnSpanFull()
                                ->addAction(
                                    fn (Action $action) => $action->after(function (Get $get, Set $set) {
                                        static::recalculateFinancialSummary($get, $set, '../../');
                                    })
                                )
                                ->deleteAction(
                                    fn (Action $action) => $action->after(function (Get $get, Set $set) {
                                        static::recalculateFinancialSummary($get, $set, '../../');
                                    })
                                )
                                ->extraAttributes([
                                    'class' => 'overflow-x-auto p-0.5 pb-1.5 *:first:table-fixed *:first:[&_thead]:[&_th]:first:w-[46px] *:first:table-fixed *:first:[&_thead]:[&_th]:last:w-[46px]',
                                ])
                                ->table([
                                    TableColumn::make('Request Item')->width('300px'),
                                    // TableColumn::make('Request ID')->width('250px'),
                                    TableColumn::make('Terealisasi ?')->width('150px'),
                                    TableColumn::make('Foto Item/Produk')->width('350px'),
                                    TableColumn::make('Qty (Request)')->width('150px'),
                                    TableColumn::make('Qty (Aktual)')->width('150px'),
                                    TableColumn::make('Unit Qty')->width('175px'),
                                    TableColumn::make('Harga/item (Request)')->width('250px'),
                                    TableColumn::make('Harga/item (Aktual)')->width('250px'),
                                    TableColumn::make('Total Request')->width('250px'),
                                    TableColumn::make('Total Aktual')->width('250px'),
                                    TableColumn::make('Variasi')->width('250px'),
                                ])
                                ->schema([
                                    Select::make('request_item_id')
                                        ->label('Pilih Item Request')
                                        ->requiredWith('actual_quantity,actual_amount_per_item')
                                        ->validationMessages([
                                            'required_with' => 'Item wajib dipilih',
                                        ])
                                        ->native(true)
                                        ->live()
                                        ->partiallyRenderComponentsAfterStateUpdated(['request_quantity', 'request_unit_quantity', 'request_amount_per_item'])
                                        ->options(function () {
                                            return RequestItem::query()
                                                ->where('request_items.status', '=', RequestItemStatus::WaitingSettlement->value)
                                                ->join('daily_payment_requests', 'request_items.daily_payment_request_id', '=', 'daily_payment_requests.id')
                                                ->get(['request_items.id', 'request_items.description', 'daily_payment_requests.request_number as daily_payment_request_number'])
                                                ->groupBy('daily_payment_request_number')
                                                ->map(fn ($items) => $items->pluck('description', 'id'))
                                                ->toArray();
                                        })
                                        ->disableOptionWhen(function ($value, $state, Get $get) {
                                            $parentData = $get('../../../../') ?? [];
                                            $allReceipts = $parentData['settlementReceipts'] ?? [];

                                            $selectedIds = collect($allReceipts)
                                                ->pluck('request_items.*.request_item_id')
                                                ->flatten()
                                                ->filter()
                                                ->all();

                                            return in_array($value, $selectedIds) && $value !== $state;
                                        })
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            $parseMoney = fn ($val) => (float) str_replace(['.', ','], ['', '.'], $val ?? '0');
                                            $formatMoney = fn ($num) => number_format($num, 2, ',', '.');

                                            $currentRequestTotal = 0;

                                            if ($state) {
                                                $requestItem = RequestItem::with('dailyPaymentRequest:id,request_number')
                                                    ->where('id', $state)
                                                    ->first(['quantity', 'unit_quantity', 'amount_per_item', 'daily_payment_request_id']);

                                                $set('request_quantity', (int) $requestItem->quantity);
                                                $set('request_unit_quantity', $requestItem->unit_quantity);
                                                $set('request_amount_per_item', $formatMoney($requestItem->amount_per_item));

                                                $currentRequestTotal = $requestItem->quantity * $requestItem->amount_per_item;
                                                $set('request_total_price', $formatMoney($currentRequestTotal));
                                            } else {
                                                $set('request_quantity', null);
                                                $set('request_unit_quantity', null);
                                                $set('request_amount_per_item', null);
                                                $set('request_total_price', null);
                                            }

                                            // Recalculate financial summary
                                            $rootData = $get('../../../../') ?? [];
                                            $receipts = $rootData['settlementReceipts'] ?? [];

                                            $approvedAmount = 0;
                                            $cancelledAmount = 0;
                                            $spentAmount = 0;

                                            foreach ($receipts as $receipt) {
                                                foreach ($receipt['request_items'] ?? [] as $item) {
                                                    // Check if this is current item
                                                    $isCurrentItem = ($item['request_item_id'] ?? null) === $state;

                                                    $requestTotal = $isCurrentItem ? $currentRequestTotal : $parseMoney($item['request_total_price'] ?? '0');
                                                    $approvedAmount += $requestTotal;

                                                    $isRealized = $item['is_realized'] ?? true;
                                                    if (! $isRealized) {
                                                        $cancelledAmount += $requestTotal;
                                                    } else {
                                                        $spentAmount += $parseMoney($item['actual_total_price'] ?? '0');
                                                    }
                                                }

                                                // new_request_items only affects spent_amount
                                                foreach ($receipt['new_request_items'] ?? [] as $item) {
                                                    $totalPrice = $parseMoney($item['total_price'] ?? '0');
                                                    $spentAmount += $totalPrice;
                                                }
                                            }

                                            $variance = $approvedAmount - $spentAmount;

                                            $set('../../../../approved_request_amount', $formatMoney($approvedAmount));
                                            $set('../../../../cancelled_amount', $formatMoney($cancelledAmount));
                                            $set('../../../../spent_amount', $formatMoney($spentAmount));
                                            $set('../../../../variance', $formatMoney($variance));
                                        }),
                                    Checkbox::make('is_realized')
                                        ->label('Terealisasi?')
                                        ->default(true)
                                        ->inline(false)
                                        ->extraAttributes(
                                            [
                                                'class' => 'mx-auto',
                                            ])
                                        ->live()
                                        ->afterStateUpdatedJs(<<<'JS'
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
                                                $set('actual_quantity', 0);
                                                $set('actual_amount_per_item', '0');
                                                $set('actual_total_price', '0');
                                                const reqTotal = parseMoney($get('request_total_price'));
                                                $set('variance', formatMoney(0 - reqTotal));
                                            }
                                            
                                            // Store current item's known values for calculation
                                            const currentRequestTotal = parseMoney($get('request_total_price'));
                                            const currentActualTotal = $state ? parseMoney($get('actual_total_price')) : 0;
                                            const currentIsRealized = $state;
                                            const currentItemId = $get('request_item_id');
                                            
                                            // Recalculate financial summary
                                            const receipts = $get('../../../../settlementReceipts') ?? {};
                                            
                                            let approvedAmount = 0;
                                            let cancelledAmount = 0;
                                            let spentAmount = 0;
                                            
                                            Object.values(receipts).forEach(receipt => {
                                                const requestItems = receipt?.request_items ?? {};
                                                Object.values(requestItems).forEach(item => {
                                                    const itemRequestItemId = item?.request_item_id;
                                                    const requestTotal = parseMoney(item?.request_total_price);
                                                    
                                                    // Check if this is the current item being edited
                                                    const isCurrentItem = itemRequestItemId && itemRequestItemId === currentItemId;
                                                    
                                                    approvedAmount += requestTotal;
                                                    
                                                    // Use known values for current item, otherwise use stored values
                                                    const isRealized = isCurrentItem 
                                                        ? currentIsRealized 
                                                        : (item?.is_realized !== false && item?.is_realized !== 0 && item?.is_realized !== '0' && item?.is_realized !== null);
                                                    const actualTotal = isCurrentItem ? currentActualTotal : parseMoney(item?.actual_total_price);
                                                    
                                                    if (!isRealized) {
                                                        cancelledAmount += requestTotal;
                                                    } else {
                                                        spentAmount += actualTotal;
                                                    }
                                                });
                                                
                                                // new_request_items only affects spent_amount
                                                const newRequestItems = receipt?.new_request_items ?? {};
                                                Object.values(newRequestItems).forEach(item => {
                                                    const totalPrice = parseMoney(item?.total_price);
                                                    spentAmount += totalPrice;
                                                });
                                            });
                                            
                                            const variance =  approvedAmount - spentAmount;
                                            
                                            $set('../../../../approved_request_amount', formatMoney(approvedAmount));
                                            $set('../../../../cancelled_amount', formatMoney(cancelledAmount));
                                            $set('../../../../spent_amount', formatMoney(spentAmount));
                                            $set('../../../../variance', formatMoney(variance));
                                        JS),
                                    SpatieMediaLibraryFileUpload::make('item_image')
                                        ->label('Foto Item/Produk')
                                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'application/pdf'])
                                        ->multiple()
                                        ->appendFiles()
                                        ->maxSize(4096)
                                        ->storeFiles(false)
                                        ->columnSpanFull()
                                        ->previewable(false)
                                        ->dehydrated(true)
                                        ->required()
                                        ->validationMessages([
                                            'required' => 'Foto Item/Produk diperlukan untuk Settlement',
                                        ]),
                                    TextInput::make('request_quantity')
                                        ->readOnly()
                                        ->dehydrated(false)
                                        ->numeric(),
                                    TextInput::make('actual_quantity')
                                        ->label('Harga/item (Aktual)')
                                        ->requiredWith('request_item_id,actual_amount_per_item')
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
                                        const basePriceNum = parseMoney($get('actual_amount_per_item'));
                                        const qtyNum = parseFloat($state) || 0;
                                        const total = qtyNum * basePriceNum;

                                        $set('actual_total_price', formatMoney(total));
                                        
                                        // Calculate item variance
                                        const requestTotal = parseMoney($get('request_total_price'));
                                        $set('variance', formatMoney(requestTotal - total));
                                        
                                        // Store current item's known values
                                        const currentActualTotal = total;
                                        const currentItemId = $get('request_item_id');
                                        
                                        // Recalculate financial summary
                                        const receipts = $get('../../../../settlementReceipts') ?? {};
                                        
                                        let approvedAmount = 0;
                                        let cancelledAmount = 0;
                                        let spentAmount = 0;
                                        
                                        Object.values(receipts).forEach(receipt => {
                                            const requestItems = receipt?.request_items ?? {};
                                            Object.values(requestItems).forEach(item => {
                                                const itemRequestItemId = item?.request_item_id;
                                                const reqTotal = parseMoney(item?.request_total_price);
                                                
                                                const isCurrentItem = itemRequestItemId && itemRequestItemId === currentItemId;
                                                
                                                approvedAmount += reqTotal;
                                                
                                                const isRealized = item?.is_realized !== false && item?.is_realized !== 0 && item?.is_realized !== '0' && item?.is_realized !== null;
                                                const actualTotal = isCurrentItem ? currentActualTotal : parseMoney(item?.actual_total_price);
                                                
                                                if (!isRealized) {
                                                    cancelledAmount += reqTotal;
                                                } else {
                                                    spentAmount += actualTotal;
                                                }
                                            });
                                            
                                            const newRequestItems = receipt?.new_request_items ?? {};
                                            Object.values(newRequestItems).forEach(item => {
                                                const totalPrice = parseMoney(item?.total_price);
                                                spentAmount += totalPrice;
                                            });
                                        });
                                        
                                        const summaryVariance =  approvedAmount - spentAmount;
                                        
                                        $set('../../../../approved_request_amount', formatMoney(approvedAmount));
                                        $set('../../../../cancelled_amount', formatMoney(cancelledAmount));
                                        $set('../../../../spent_amount', formatMoney(spentAmount));
                                        $set('../../../../variance', formatMoney(summaryVariance));
                                    JS),
                                    TextInput::make('request_unit_quantity')
                                        ->readOnly()
                                        ->dehydrated(false),
                                    TextInput::make('request_amount_per_item')
                                        ->prefix('Rp')
                                        ->readOnly()
                                        ->dehydrated(false)
                                        ->afterStateUpdatedJs(<<<'JS'
                                            const cleanPrice = ($state ?? '0').toString().replace(/\./g, '').replace(',', '.');
                                            const priceNum = parseFloat(cleanPrice) || 0;
                                            const qtyNum = parseFloat($get('request_quantity')) || 0;
                                            const total = qtyNum * priceNum;

                                            if (total === 0) {
                                                $set('request_total_price', '');
                                            } else {
                                                const formatted = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                                $set('request_total_price', formatted);
                                            }
                                        JS),
                                    TextInput::make('actual_amount_per_item')
                                        ->requiredWith('request_item_id,actual_quantity')
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
                                            const priceNum = parseMoney($state);
                                            const qtyNum = parseFloat($get('actual_quantity')) || 0;
                                            const total = qtyNum * priceNum;

                                            $set('actual_total_price', formatMoney(total));
                                            
                                            // Calculate item variance
                                            const requestTotal = parseMoney($get('request_total_price'));
                                            $set('variance', formatMoney(requestTotal - total));
                                            
                                            // Store current item's known values
                                            const currentActualTotal = total;
                                            const currentItemId = $get('request_item_id');
                                            
                                            // Recalculate financial summary
                                            const receipts = $get('../../../../settlementReceipts') ?? {};
                                            
                                            let approvedAmount = 0;
                                            let cancelledAmount = 0;
                                            let spentAmount = 0;
                                            
                                            Object.values(receipts).forEach(receipt => {
                                                const requestItems = receipt?.request_items ?? {};
                                                Object.values(requestItems).forEach(item => {
                                                    const itemRequestItemId = item?.request_item_id;
                                                    const reqTotal = parseMoney(item?.request_total_price);
                                                    
                                                    const isCurrentItem = itemRequestItemId && itemRequestItemId === currentItemId;
                                                    
                                                    approvedAmount += reqTotal;
                                                    
                                                    const isRealized = item?.is_realized !== false && item?.is_realized !== 0 && item?.is_realized !== '0' && item?.is_realized !== null;
                                                    const actualTotal = isCurrentItem ? currentActualTotal : parseMoney(item?.actual_total_price);
                                                    
                                                    if (!isRealized) {
                                                        cancelledAmount += reqTotal;
                                                    } else {
                                                        spentAmount += actualTotal;
                                                    }
                                                });
                                                
                                                const newRequestItems = receipt?.new_request_items ?? {};
                                                Object.values(newRequestItems).forEach(item => {
                                                    const totalPrice = parseMoney(item?.total_price);
                                                    spentAmount += totalPrice;
                                                });
                                            });
                                            
                                            const summaryVariance =  approvedAmount - spentAmount;
                                            
                                            $set('../../../../approved_request_amount', formatMoney(approvedAmount));
                                            $set('../../../../cancelled_amount', formatMoney(cancelledAmount));
                                            $set('../../../../spent_amount', formatMoney(spentAmount));
                                            $set('../../../../variance', formatMoney(summaryVariance));
                                        JS),
                                    TextInput::make('request_total_price')
                                        ->label('Total Request')
                                        ->prefix('Rp')
                                        ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                        ->readOnly()
                                        ->dehydrated(false),
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
                                ]),
                            Repeater::make('new_request_items')
                                ->label('Daftarkan Item Request Baru (Jika ada)')
                                ->addActionLabel('Tambah Item Baru')
                                ->belowLabel('Menambahkan Item Baru akan membuat Payment Request baru')
                                ->compact()
                                ->addActionAlignment(Alignment::Start)
                                ->columnSpanFull()
                                ->addAction(
                                    fn (Action $action) => $action->after(function (Get $get, Set $set) {
                                        static::recalculateFinancialSummary($get, $set, '../../');
                                    })
                                )
                                ->deleteAction(
                                    fn (Action $action) => $action->after(function (Get $get, Set $set) {
                                        static::recalculateFinancialSummary($get, $set, '../../');
                                    })
                                )
                                ->extraAttributes([
                                    'class' => 'overflow-x-auto p-0.5 pb-1.5 *:first:table-fixed *:first:[&_thead]:[&_th]:first:w-[46px] *:first:table-fixed *:first:[&_thead]:[&_th]:last:w-[46px]',
                                ])
                                ->table([
                                    TableColumn::make('COA')->width('280px'),
                                    TableColumn::make('Aktivitas')->width('280px'),
                                    TableColumn::make('Item')->width('300px'),
                                    TableColumn::make('Qty')->width('100px'),
                                    TableColumn::make('Unit Qty')->width('150px'),
                                    TableColumn::make('Base Price')->width('250px'),
                                    TableColumn::make('Total Price')->width('250px'),
                                    TableColumn::make('Foto Item/Produk')->width('350px'),
                                ])
                                ->schema([
                                    Select::make('coa_id')
                                        ->options(Coa::query()->pluck('name', 'id'))
                                        ->native(true)
                                        ->live()
                                        ->requiredWith('item,qty,unit_qty,base_price,item_image')
                                        ->validationMessages([
                                            'required_with' => 'COA wajib diisi',
                                        ])
                                        ->afterStateUpdatedJs(
                                            <<<'JS'
                                            $set('program_activity_id', null);
                                        JS
                                        ),
                                    Select::make('program_activity_id')
                                        ->options(fn (Get $get) => ProgramActivity::query()->whereCoaId($get('coa_id'))->pluck('name', 'id'))
                                        ->live()
                                        ->native(true)
                                        ->preload()
                                        ->disabled(fn (Get $get) => $get('coa_id') === null),
                                    TextInput::make('item')
                                        ->requiredWith('coa_id,qty,unit_qty,base_price,item_image')
                                        ->validationMessages([
                                            'required_with' => 'Deskripsi item wajib diisi',
                                        ])
                                        ->datalist(fn (Get $get) => ProgramActivityItem::query()->whereProgramActivityId($get('program_activity_id'))->pluck('description')->toArray())
                                        ->live(debounce: 500),
                                    TextInput::make('qty')
                                        ->requiredWith('coa_id,item,unit_qty,base_price,item_image')
                                        ->validationMessages([
                                            'required_with' => 'Jumlah item wajib diisi',
                                        ])
                                        ->numeric()
                                        ->minValue(1)
                                        // ->live()
                                        // ->partiallyRenderComponentsAfterStateUpdated(['coa_id'])
                                        ->placeholder(function (Get $get) {
                                            $activityItem = $get('program_activity_id') ? ProgramActivityItem::whereProgramActivityId($get('program_activity_id'))->whereDescription($get('item'))->get(['volume'])->first() : null;

                                            return $activityItem ? $activityItem->volume : null;
                                        })
                                        ->afterStateUpdatedJs(<<<'JS'
                                        const formatMoney = (num) => {
                                            if (num === 0) return '0,00';
                                            const isNegative = num < 0;
                                            const absNum = Math.abs(num);
                                            const formatted = absNum.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                            return isNegative ? '-' + formatted : formatted;
                                        };
                                        const parseMoney = (val) => parseFloat((val ?? '0').toString().replace(/\./g, '').replace(',', '.')) || 0;
                                        
                                        // Calculate total_price
                                        const basePriceNum = parseMoney($get('base_price'));
                                        const qtyNum = parseFloat($state) || 0;
                                        const total = qtyNum * basePriceNum;

                                        $set('total_price', formatMoney(total));
                                        
                                        // Store current item's known values
                                        const currentTotal = total;
                                        const currentCoa = $get('coa_id');
                                        const currentItemName = $get('item');
                                        
                                        // Recalculate financial summary
                                        const receipts = $get('../../../../settlementReceipts') ?? {};
                                        
                                        let approvedAmount = 0;
                                        let cancelledAmount = 0;
                                        let spentAmount = 0;
                                        
                                        Object.values(receipts).forEach(receipt => {
                                            const requestItems = receipt?.request_items ?? {};
                                            Object.values(requestItems).forEach(item => {
                                                const reqTotal = parseMoney(item?.request_total_price);
                                                approvedAmount += reqTotal;
                                                
                                                const isRealized = item?.is_realized !== false && item?.is_realized !== 0 && item?.is_realized !== '0' && item?.is_realized !== null;
                                                
                                                if (!isRealized) {
                                                    cancelledAmount += reqTotal;
                                                } else {
                                                    spentAmount += parseMoney(item?.actual_total_price);
                                                }
                                            });
                                            
                                            const newRequestItems = receipt?.new_request_items ?? {};
                                            Object.values(newRequestItems).forEach(item => {
                                                const isCurrentItem = item?.coa_id === currentCoa && item?.item === currentItemName;
                                                const totalPrice = isCurrentItem ? currentTotal : parseMoney(item?.total_price);
                                                spentAmount += totalPrice;
                                            });
                                        });
                                        
                                        const variance =  approvedAmount - spentAmount;
                                        
                                        $set('../../../../approved_request_amount', formatMoney(approvedAmount));
                                        $set('../../../../cancelled_amount', formatMoney(cancelledAmount));
                                        $set('../../../../spent_amount', formatMoney(spentAmount));
                                        $set('../../../../variance', formatMoney(variance));
                                    JS),
                                    TextInput::make('unit_qty')
                                        ->requiredWith('coa_id,item,qty,base_price')
                                        ->validationMessages([
                                            'required_with' => 'Satuan item wajib diisi',
                                        ])
                                        ->trim()
                                        ->placeholder(function (Get $get) {
                                            $activityItem = $get('program_activity_id') ? ProgramActivityItem::whereProgramActivityId($get('program_activity_id'))->whereDescription($get('item'))?->get(['unit'])->first() : null;

                                            return $activityItem ? $activityItem->unit : null;
                                        }),
                                    TextInput::make('base_price')
                                        ->requiredWith('coa_id,item,qty,unit_qty,item_image')
                                        ->validationMessages([
                                            'required_with' => 'Harga per item wajib diisi',
                                        ])
                                        ->numeric()
                                        ->prefix('Rp')
                                        ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                        ->dehydrateStateUsing(fn ($rawState) => (float) str_replace(['.', ','], ['', '.'], $rawState))
                                        ->stripCharacters(['.', ','])
                                        // ->live()
                                        // ->partiallyRenderComponentsAfterStateUpdated(['coa_id'])
                                        ->placeholder(function (Get $get) {
                                            $activityItem = $get('program_activity_id') ? ProgramActivityItem::whereProgramActivityId($get('program_activity_id'))->whereDescription($get('item'))->get(['total_item_planned_budget', 'volume', 'frequency'])->first() : null;

                                            $plannedBudgetPerItem = $activityItem ? ((float) $activityItem->total_item_planned_budget / $activityItem->frequency / $activityItem->volume) : 0;

                                            return $plannedBudgetPerItem ? number_format($plannedBudgetPerItem, 2, ',', '.') : null;
                                        })
                                        ->afterStateUpdatedJs(<<<'JS'
                                        const formatMoney = (num) => {
                                            if (num === 0) return '0,00';
                                            const isNegative = num < 0;
                                            const absNum = Math.abs(num);
                                            const formatted = absNum.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.').replace(/\.(\d{2})$/, ',$1');
                                            return isNegative ? '-' + formatted : formatted;
                                        };
                                        const parseMoney = (val) => parseFloat((val ?? '0').toString().replace(/\./g, '').replace(',', '.')) || 0;
                                        
                                        // Calculate total_price
                                        const priceNum = parseMoney($state);
                                        const qtyNum = parseFloat($get('qty')) || 0;
                                        const total = qtyNum * priceNum;

                                        $set('total_price', formatMoney(total));
                                        
                                        // Store current item's known values
                                        const currentTotal = total;
                                        const currentCoa = $get('coa_id');
                                        const currentItemName = $get('item');
                                        
                                        // Recalculate financial summary
                                        const receipts = $get('../../../../settlementReceipts') ?? {};
                                        
                                        let approvedAmount = 0;
                                        let cancelledAmount = 0;
                                        let spentAmount = 0;
                                        
                                        Object.values(receipts).forEach(receipt => {
                                            const requestItems = receipt?.request_items ?? {};
                                            Object.values(requestItems).forEach(item => {
                                                const reqTotal = parseMoney(item?.request_total_price);
                                                approvedAmount += reqTotal;
                                                
                                                const isRealized = item?.is_realized !== false && item?.is_realized !== 0 && item?.is_realized !== '0' && item?.is_realized !== null;
                                                
                                                if (!isRealized) {
                                                    cancelledAmount += reqTotal;
                                                } else {
                                                    spentAmount += parseMoney(item?.actual_total_price);
                                                }
                                            });
                                            
                                            const newRequestItems = receipt?.new_request_items ?? {};
                                            Object.values(newRequestItems).forEach(item => {
                                                const isCurrentItem = item?.coa_id === currentCoa && item?.item === currentItemName;
                                                const totalPrice = isCurrentItem ? currentTotal : parseMoney(item?.total_price);
                                                spentAmount += totalPrice;
                                            });
                                        });
                                        
                                        const variance =  approvedAmount - spentAmount;
                                        
                                        $set('../../../../approved_request_amount', formatMoney(approvedAmount));
                                        $set('../../../../cancelled_amount', formatMoney(cancelledAmount));
                                        $set('../../../../spent_amount', formatMoney(spentAmount));
                                        $set('../../../../variance', formatMoney(variance));
                                    JS)
                                        ->minValue(1),
                                    TextInput::make('total_price')
                                        ->prefix('Rp')
                                        ->placeholder(function (Get $get) {
                                            $activityItem = $get('program_activity_id') ? ProgramActivityItem::whereProgramActivityId($get('program_activity_id'))->whereDescription($get('item'))->get(['total_item_planned_budget', 'volume', 'frequency'])->first() : null;

                                            $plannedBudget = $activityItem ? ((float) $activityItem->total_item_planned_budget / $activityItem->frequency / $activityItem->volume) * $activityItem->volume : 0;

                                            return $plannedBudget ? number_format($plannedBudget, 2, ',', '.') : null;
                                        })
                                        ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                        ->readOnly()
                                        ->dehydrated(false),
                                    SpatieMediaLibraryFileUpload::make('item_image')
                                        ->label('Foto Item/Produk')
                                        ->requiredWith('coa_id,item,qty,unit_qty,base_price')
                                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'application/pdf'])
                                        ->multiple()
                                        ->appendFiles()
                                        ->maxSize(4096)
                                        ->storeFiles(false)
                                        ->columnSpanFull()
                                        ->previewable(false)
                                        ->dehydrated(true)
                                        ->validationMessages([
                                            'required_with' => 'Foto Item/Produk diperlukan untuk Settlement',
                                        ]),
                                ]),
                        ])
                        ->columnSpanFull()
                        ->addAction(
                            fn (Action $action) => $action->after(function (Get $get, Set $set) {
                                static::recalculateFinancialSummary($get, $set, '');
                            })
                        )
                        ->deleteAction(
                            fn (Action $action) => $action->after(function (Get $get, Set $set) {
                                static::recalculateFinancialSummary($get, $set, '');
                            })
                        ),
                ]),
            Step::make('Review')
                ->schema([
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
                ]),
        ];
    }

    protected static function recalculateFinancialSummary(Get $get, Set $set, string $pathPrefix = ''): void
    {
        $parseMoney = fn ($val) => (float) str_replace(['.', ','], ['', '.'], $val ?? '0');
        $formatMoney = fn ($num) => number_format($num, 2, ',', '.');

        $receiptsPath = $pathPrefix ? $pathPrefix.'settlementReceipts' : 'settlementReceipts';
        $receipts = $get($receiptsPath) ?? [];

        $approvedAmount = 0;
        $cancelledAmount = 0;
        $spentAmount = 0;

        foreach ($receipts as $receipt) {
            foreach ($receipt['request_items'] ?? [] as $item) {
                $requestTotal = $parseMoney($item['request_total_price'] ?? '0');
                $approvedAmount += $requestTotal;

                $isRealized = $item['is_realized'] ?? true;
                if (! $isRealized) {
                    $cancelledAmount += $requestTotal;
                } else {
                    $spentAmount += $parseMoney($item['actual_total_price'] ?? '0');
                }
            }

            // new_request_items only affects spent_amount, not approved_amount
            foreach ($receipt['new_request_items'] ?? [] as $item) {
                $totalPrice = $parseMoney($item['total_price'] ?? '0');
                $spentAmount += $totalPrice;
            }
        }

        $variance = $approvedAmount - $spentAmount;

        $set($pathPrefix.'approved_request_amount', $formatMoney($approvedAmount));
        $set($pathPrefix.'cancelled_amount', $formatMoney($cancelledAmount));
        $set($pathPrefix.'spent_amount', $formatMoney($spentAmount));
        $set($pathPrefix.'variance', $formatMoney($variance));
    }

    protected static function getVarianceColor(?string $variance): string
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

    protected static function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', '.');
    }

    protected static function keyValueMoney(float $amount): HtmlString
    {
        return new HtmlString('<div class="flex justify-between items-center w-full"><div>Rp</div> <div>'.self::formatMoney($amount).'</div></div>');
    }

    protected static function parseMoney(?string $value): float
    {
        return (float) str_replace(['.', ','], ['', '.'], $value ?? '0');
    }

    protected static function calculateCoaReconciliation(Get $get): array
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
            foreach ($receipt['request_items'] ?? [] as $item) {
                if (! empty($item['request_item_id'])) {
                    $requestItemIds[] = $item['request_item_id'];
                }
            }

            foreach ($receipt['new_request_items'] ?? [] as $newItem) {
                if (! empty($newItem['coa_id'])) {
                    $coaIds[] = $newItem['coa_id'];
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

        // Step 1: Calculate cancelled budget per COA from request_items
        foreach ($receipts as $receipt) {
            foreach ($receipt['request_items'] ?? [] as $item) {
                $isRealized = $item['is_realized'] ?? true;

                if (! $isRealized && ! empty($item['request_item_id'])) {
                    $requestItem = $requestItems->get($item['request_item_id']);

                    if ($requestItem && $requestItem->coa) {
                        $coaId = $requestItem->coa->id;

                        if (! isset($coaData[$coaId])) {
                            $coaData[$coaId] = [
                                'id' => $coaId,
                                'name' => $requestItem->coa->name,
                                'code' => $requestItem->coa->code ?? '',
                                'cancelled_budget' => 0,
                                'new_items_spending' => 0,
                            ];
                        }

                        $cancelledAmount = $parseMoney($item['request_total_price'] ?? '0');
                        $coaData[$coaId]['cancelled_budget'] += $cancelledAmount;
                    }
                }
            }
        }

        // Step 2: Calculate new items spending per COA
        foreach ($receipts as $receipt) {
            foreach ($receipt['new_request_items'] ?? [] as $newItem) {
                $coaId = $newItem['coa_id'] ?? null;

                if ($coaId) {
                    if (! isset($coaData[$coaId])) {
                        $coa = $coas->get($coaId);
                        if ($coa) {
                            $coaData[$coaId] = [
                                'id' => $coaId,
                                'name' => $coa->name,
                                'code' => $coa->code ?? '',
                                'cancelled_budget' => 0,
                                'new_items_spending' => 0,
                            ];
                        }
                    }

                    if (isset($coaData[$coaId])) {
                        $newItemTotal = $parseMoney($newItem['total_price'] ?? '0');
                        $coaData[$coaId]['new_items_spending'] += $newItemTotal;
                    }
                }
            }
        }

        // Step 3: Calculate net offset and amount to return for each COA
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

        // dd($cachedResult);

        return $cachedResult;
    }

    protected static function calculateReviewData(Get $get): array
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

        // Step 1: Collect all request_item_ids for batch loading
        $requestItemIds = [];
        foreach ($receipts as $receipt) {
            foreach ($receipt['request_items'] ?? [] as $item) {
                if (! empty($item['request_item_id'])) {
                    $requestItemIds[] = $item['request_item_id'];
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
                ->keyBy('id')
                ->toArray();
        }

        // Step 3: Group data by COA and calculate metrics
        $coaGroups = [];

        foreach ($receipts as $receiptIndex => $receipt) {
            // Process request_items
            foreach ($receipt['request_items'] ?? [] as $itemIndex => $item) {
                $requestItemId = $item['request_item_id'] ?? null;
                if (! $requestItemId || ! isset($requestItemsMap[$requestItemId])) {
                    continue;
                }

                $requestItem = $requestItemsMap[$requestItemId];
                $coaId = $requestItem['coa_id'] ?? null;

                if (! $coaId) {
                    continue;
                }

                // Initialize COA group if not exists
                if (! isset($coaGroups[$coaId])) {
                    $coaGroups[$coaId] = [
                        'coa_id' => $coaId,
                        'coa_name' => $requestItem['coa']['name'] ?? '',
                        'coa_code' => $requestItem['coa']['code'] ?? '',
                        'approved_budget' => 0,
                        'spent_budget' => 0,
                        'cancelled_budget' => 0,
                        'total_new_request_items' => 0,
                        'total_actual_spent_request_items' => 0,
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

                // Calculate metrics
                $coaGroups[$coaId]['approved_budget'] += $requestTotal;
                $coaGroups[$coaId]['calculations']['Anggaran Disetujui'] = self::keyValueMoney($coaGroups[$coaId]['approved_budget']);

                if ($isRealized) {
                    $coaGroups[$coaId]['spent_budget'] += $actualTotal;
                    $coaGroups[$coaId]['calculations']['Total Pengeluaran'] = self::keyValueMoney($coaGroups[$coaId]['spent_budget']);
                    $coaGroups[$coaId]['total_actual_spent_request_items'] += $actualTotal;
                    $coaGroups[$coaId]['calculations']['Total Realisasi'] = self::keyValueMoney($coaGroups[$coaId]['total_actual_spent_request_items']);
                } else {
                    $coaGroups[$coaId]['cancelled_budget'] += $requestTotal;
                    $coaGroups[$coaId]['calculations']['Anggaran Dibatalkan'] = self::keyValueMoney($coaGroups[$coaId]['cancelled_budget']);
                }

                // Add item details
                $coaGroups[$coaId]['items'][] = [
                    'type' => 'request_item',
                    'receipt_index' => $receiptIndex,
                    'item_index' => $itemIndex,
                    'request_item_id' => $requestItemId,
                    'status' => $isRealized ? 'realized' : 'cancelled',
                    'description' => $requestItem['description'] ?? '',
                    'is_realized' => $isRealized,
                    'request_quantity' => $item['request_quantity'] ?? 0,
                    'actual_quantity' => $item['actual_quantity'] ?? 0,
                    'unit_quantity' => $item['request_unit_quantity'] ?? '',
                    'request_amount_per_item' => $item['request_amount_per_item'] ?? '0,00',
                    'actual_amount_per_item' => $item['actual_amount_per_item'] ?? '0,00',
                    'request_total_price' => $item['request_total_price'] ?? '0,00',
                    'actual_total_price' => $item['actual_total_price'] ?? '0,00',
                    'variance' => $item['variance'] ?? '0,00',
                ];
            }

            // Process new_request_items
            foreach ($receipt['new_request_items'] ?? [] as $itemIndex => $newItem) {
                $coaId = $newItem['coa_id'] ?? null;
                if (! $coaId) {
                    continue;
                }

                // Initialize COA group if not exists
                if (! isset($coaGroups[$coaId])) {
                    $coa = \App\Models\Coa::find($coaId, ['id', 'name', 'code']);
                    if (! $coa) {
                        continue;
                    }

                    $coaGroups[$coaId] = [
                        'coa_id' => $coaId,
                        'coa_name' => $coa->name ?? '',
                        'coa_code' => $coa->code ?? '',
                        'approved_budget' => 0,
                        'spent_budget' => 0,
                        'cancelled_budget' => 0,
                        'total_actual_spent_request_items' => 0,
                        'total_new_request_items' => 0,
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

                $totalPrice = $parseMoney($newItem['total_price'] ?? '0');

                // Calculate metrics
                $coaGroups[$coaId]['spent_budget'] += $totalPrice;
                $coaGroups[$coaId]['calculations']['Total Pengeluaran'] = self::keyValueMoney($coaGroups[$coaId]['spent_budget']);
                $coaGroups[$coaId]['total_new_request_items'] += $totalPrice;
                $coaGroups[$coaId]['calculations']['Total Item Baru'] = self::keyValueMoney($coaGroups[$coaId]['total_new_request_items']);

                // Add item details
                $coaGroups[$coaId]['items'][] = [
                    'type' => 'new_request_item',
                    'receipt_index' => $receiptIndex,
                    'item_index' => $itemIndex,
                    'coa_id' => $coaId,
                    'program_activity_id' => $newItem['program_activity_id'] ?? null,
                    'status' => 'new',
                    'description' => $newItem['item'] ?? '',
                    'actual_quantity' => $newItem['qty'] ?? 0,
                    'unit_quantity' => $newItem['unit_qty'] ?? '',
                    'actual_amount_per_item' => $newItem['base_price'] ?? '0,00',
                    'actual_total_price' => $newItem['total_price'] ?? '0,00',
                ];
            }
        }

        // Step 4: Calculate amount_to_return and amount_to_reimburse for each COA
        $totalAmountToReturn = 0;
        $totalAmountToReimburse = 0;

        foreach ($coaGroups as &$group) {
            // Amount to return: cancelled budget that wasn't used by new items
            $availableForReuse = $group['cancelled_budget'];
            $usedByNewItems = $group['total_new_request_items'];
            $group['amount_to_return'] = max(0, $group['approved_budget'] - $group['spent_budget']);
            $group['calculations']['Dikembalikan ke Finance'] = self::keyValueMoney(max(0, $group['approved_budget'] - $group['spent_budget']));
            // $group['amount_to_return'] = max(0, $availableForReuse - $usedByNewItems);
            // $group['calculations']['Dikembalikan ke Finance'] = max(0, $availableForReuse - $usedByNewItems);

            // Amount to reimburse: if spent more than approved (overspending)
            // This can happen when new items exceed cancelled budget
            $overspending = $group['spent_budget'] - $group['approved_budget'];
            $group['amount_to_reimburse'] = max(0, $overspending);
            $group['calculations']['Perlu Reimbursement'] = self::keyValueMoney(max(0, $overspending));

            $totalAmountToReturn += $group['amount_to_return'];
            $totalAmountToReimburse += $group['amount_to_reimburse'];
        }
        unset($group);

        // Step 5: Sort by COA code for better readability
        uasort($coaGroups, fn ($a, $b) => strcmp($a['coa_code'], $b['coa_code']));

        // dd([
        //     'reconciliations' => array_values($coaGroups),
        //     'total_amount_to_return' => $totalAmountToReturn,
        //     'total_amount_to_reimburse' => $totalAmountToReimburse,
        // ]);

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

        // Process each receipt
        foreach ($data['settlementReceipts'] ?? [] as $receiptIndex => $receipt) {
            // Process request_items
            foreach ($receipt['request_items'] ?? [] as $itemIndex => $item) {
                if (empty($item['request_item_id'])) {
                    continue;
                }

                // Fetch the RequestItem from database to get accurate data
                $requestItem = RequestItem::find($item['request_item_id']);

                if (! $requestItem) {
                    // Log error or throw exception
                    Log::warning("RequestItem not found: {$item['request_item_id']}");

                    continue;
                }

                // Validate that RequestItem is in correct status
                if ($requestItem->status !== RequestItemStatus::WaitingSettlement) {
                    throw new \Exception("RequestItem {$requestItem->id} is not in WaitingSettlement status");
                }

                // Calculate request_total_price from database values
                $requestTotalPrice = $requestItem->quantity * $requestItem->amount_per_item;

                // Add to $data for storage
                $data['settlementReceipts'][$receiptIndex]['request_items'][$itemIndex]['request_quantity'] = $requestItem->quantity;
                $data['settlementReceipts'][$receiptIndex]['request_items'][$itemIndex]['request_unit_quantity'] = $requestItem->unit_quantity;
                $data['settlementReceipts'][$receiptIndex]['request_items'][$itemIndex]['request_amount_per_item'] = $requestItem->amount_per_item;
                $data['settlementReceipts'][$receiptIndex]['request_items'][$itemIndex]['request_total_price'] = $requestTotalPrice;

                // Add to approved amount
                $approvedAmount += $requestTotalPrice;

                // Check if realized
                $isRealized = $item['is_realized'] ?? true;

                if (! $isRealized) {
                    // If not realized, add to cancelled amount
                    $cancelledAmount += $requestTotalPrice;

                    // Set actual values to 0
                    $data['settlementReceipts'][$receiptIndex]['request_items'][$itemIndex]['actual_quantity'] = 0;
                    $data['settlementReceipts'][$receiptIndex]['request_items'][$itemIndex]['actual_amount_per_item'] = 0;
                    $data['settlementReceipts'][$receiptIndex]['request_items'][$itemIndex]['actual_total_price'] = 0;
                    $data['settlementReceipts'][$receiptIndex]['request_items'][$itemIndex]['variance'] = $requestTotalPrice;
                } else {
                    foreach ($item['item_image'] ?? [] as $index => $file) {
                        $requestItem->addMedia($file)->toMediaCollection('request_item_image', 'local');
                    }
                    // Calculate actual_total_price from user input
                    $actualQuantity = $item['actual_quantity'] ?? 0;
                    $actualAmountPerItem = $item['actual_amount_per_item'] ?? 0;
                    $actualTotalPrice = $actualQuantity * $actualAmountPerItem;

                    // Validate that actual values are provided
                    if ($actualQuantity <= 0 || $actualAmountPerItem <= 0) {
                        throw new \Exception('Actual quantity and amount per item must be greater than 0 for realized items');
                    }

                    // Add to $data for storage
                    $data['settlementReceipts'][$receiptIndex]['request_items'][$itemIndex]['actual_total_price'] = $actualTotalPrice;

                    // Add to spent amount
                    $spentAmount += $actualTotalPrice;

                    // Calculate variance
                    $variance = $requestTotalPrice - $actualTotalPrice;
                    $data['settlementReceipts'][$receiptIndex]['request_items'][$itemIndex]['variance'] = $variance;
                }
            }

            // Process new_request_items
            foreach ($receipt['new_request_items'] ?? [] as $itemIndex => $item) {
                // Validate required fields
                // if (empty($item['coa_id']) || empty($item['item']) || empty($item['qty']) || empty($item['unit_qty']) || empty($item['base_price'])) {
                //     throw new \Exception('All fields are required for new request items');
                // }

                // Calculate total_price
                $qty = $item['qty'];
                $basePrice = $item['base_price'];
                $totalPrice = $qty * $basePrice;

                // Add to $data for storage
                $data['settlementReceipts'][$receiptIndex]['new_request_items'][$itemIndex]['total_price'] = $totalPrice;

                // Add to new_request_item_total
                $newRequestItemTotal += $totalPrice;

                // new_request_items only affect spent_amount
                $spentAmount += $totalPrice;
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

        $this->settlementData = $data;

        // dd($this->settlementData);

        unset($data);

        $settlement = [
            'submitter_id' => Auth::user()->employee->id,
            'submit_date' => now(),
            'status' => SettlementStatus::Draft,  // Initial status, will be updated after DPR creation
        ];

        return $settlement;
    }

    protected function afterCreate(): void
    {
        $settlement = $this->record;

        try {
            DB::transaction(function () use ($settlement) {
                // Create SettlementReceipts from form data
                foreach ($this->settlementData['settlementReceipts'] ?? [] as $index => $receiptData) {
                    $settlementReceipt = SettlementReceipt::create([
                        'settlement_id' => $settlement->id,
                        'realization_date' => $receiptData['realization_date'] ?? null,
                    ]);

                    // Handle attachment media
                    if (isset($receiptData['attachment'])) {
                        $settlementReceipt->addMedia($receiptData['attachment'])
                            ->toMediaCollection('settlement_receipt_attachments', 'local');
                    }

                    // Add the created SettlementReceipt ID to form data for processing
                    $this->settlementData['settlementReceipts'][$index]['id'] = $settlementReceipt->id;
                }

                // Service 1: Extract form data (already done in mutateFormDataBeforeCreate, stored in $this->settlementData)
                $formDataService = app(\App\Services\SettlementFormDataService::class);
                $structuredData = $formDataService->extractFormData($this->settlementData);

                // Service 2: Process items from receipts
                $itemProcessor = app(\App\Services\SettlementItemProcessingService::class);
                $results = $itemProcessor->processReceipts(
                    $structuredData['receipts'],
                    $settlement,
                    false // create mode
                );

                // Service 3: Categorize items
                $categorized = $itemProcessor->categorizeItems(collect($results));

                // Service 4: Calculate offsets
                $offsetService = app(\App\Services\SettlementOffsetCalculationService::class);
                $offsetResult = $offsetService->calculateOffsets($categorized, $settlement);

                // Service 5: Create reimbursement items for overspent items
                $reimbursementItems = $offsetService->createReimbursementItems(
                    $categorized['overspent'] ? collect($categorized['overspent'])->flatten(1)->toArray() : [],
                    $settlement
                );

                // Collect all DPR items (offsets + reimbursements + new items)
                $dprItems = array_merge(
                    $offsetResult['offset_items'],
                    $reimbursementItems
                );

                // Add new items (flatten COA groups)
                foreach ($categorized['new'] ?? [] as $coaId => $newItems) {
                    foreach ($newItems as $newResult) {
                        $dprItems[] = $newResult['item'];
                    }
                }

                // Create DPR if there are items requiring approval
                if (! empty($dprItems)) {
                    $this->createDPRForSettlement($settlement, $dprItems);
                } else {
                    // No DPR needed - set final status based on offset-calculated refund
                    $this->finalizeSettlementWithoutDPR($settlement, $offsetResult);
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

// array:6 [▼
//   "receipts" => array:1 [▼
//     0 => array:4 [▼
//       "attachment" => Livewire\Features\SupportFileUploads\TemporaryUploadedFile {#1726 ▶}
//       "realization_date" => "2025-11-02"
//       "request_items" => array:1 [▼
//         0 => array:10 [▼
//           "request_item_id" => 43
//           "is_realized" => true
//           "actual_quantity" => 1.0
//           "actual_amount_per_item" => 600000.0
//           "request_quantity" => "1.00"
//           "request_unit_quantity" => "Sample"
//           "request_amount_per_item" => "500000.00"
//           "request_total_price" => 500000.0
//           "actual_total_price" => 600000.0
//           "variance" => -100000.0
//         ]
//       ]
//       "new_request_items" => array:1 [▼
//         0 => array:7 [▼
//           "coa_id" => 1
//           "program_activity_id" => 1
//           "item" => "Vitamin B1"
//           "qty" => 1.0
//           "unit_qty" => "Paket"
//           "base_price" => 195000.0
//           "total_price" => 195000.0
//         ]
//       ]
//     ]
//   ]
//   "approved_request_amount" => 500000.0
//   "cancelled_amount" => 0
//   "spent_amount" => 795000.0
//   "new_request_item_total" => 195000.0
//   "variance" => -295000.0
// ]

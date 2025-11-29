<div class="space-y-6">
    @foreach ($receipts as $index => $receipt)
        <div class="rounded-lg border bg-gray-50 p-4 dark:bg-gray-800">
            <h3 class="mb-3 text-lg font-semibold text-gray-900 dark:text-gray-100">
                Nota ke-{{ (int) $index + 1 }}
            </h3>

            <div class="mb-3 text-sm text-gray-600 dark:text-gray-400">
                <strong>Tanggal Realisasi:</strong>
                {{ $receipt['realization_date'] ? \Carbon\Carbon::parse($receipt['realization_date'])->format('d M Y') : '-' }}
            </div>

            {{-- Request Items --}}
            @if (!empty($receipt['request_items']))
                <div class="mb-4">
                    <h4 class="mb-2 font-medium text-gray-900 dark:text-gray-100">Item Request yang Direalisasi:</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <th class="px-3 py-2 text-left">Item</th>
                                    <th class="px-3 py-2 text-left">Status</th>
                                    <th class="px-3 py-2 text-right">Qty (Request)</th>
                                    <th class="px-3 py-2 text-right">Qty (Aktual)</th>
                                    <th class="px-3 py-2 text-right">Harga/item (Request)</th>
                                    <th class="px-3 py-2 text-right">Harga/item (Aktual)</th>
                                    <th class="px-3 py-2 text-right">Total Request</th>
                                    <th class="px-3 py-2 text-right">Total Aktual</th>
                                    <th class="px-3 py-2 text-right">Variasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @foreach ($receipt['request_items'] as $item)
                                    @php
                                        $requestItem = $item['request_item_id']
                                            ? \App\Models\RequestItem::find($item['request_item_id'])
                                            : null;
                                        $isRealized = $item['is_realized'] ?? true;
                                        $parseMoney = fn($val) => (float) str_replace(
                                            ['.', ','],
                                            ['', '.'],
                                            $val ?? '0',
                                        );
                                        $variance = $parseMoney($item['variance'] ?? '0');
                                        $varianceClass =
                                            $variance > 0
                                                ? 'text-success-600'
                                                : ($variance < 0
                                                    ? 'text-danger-600'
                                                    : 'text-gray-600');
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2">
                                            {{ $requestItem?->description ?? '-' }}
                                        </td>
                                        <td class="px-3 py-2">
                                            @if ($isRealized)
                                                <span
                                                    class="bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300 inline-flex items-center rounded-full px-2 py-1 text-xs font-medium">
                                                    ✓ Terealisasi
                                                </span>
                                            @else
                                                <span
                                                    class="bg-danger-100 text-danger-700 dark:bg-danger-900 dark:text-danger-300 inline-flex items-center rounded-full px-2 py-1 text-xs font-medium">
                                                    ✗ Dibatalkan
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-right">{{ $item['request_quantity'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-right">
                                            {{ $isRealized ? $item['actual_quantity'] ?? '-' : '-' }}</td>
                                        <td class="px-3 py-2 text-right">Rp
                                            {{ $item['request_amount_per_item'] ?? '0,00' }}</td>
                                        <td class="px-3 py-2 text-right">
                                            {{ $isRealized ? 'Rp ' . ($item['actual_amount_per_item'] ?? '0,00') : '-' }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-medium">Rp
                                            {{ $item['request_total_price'] ?? '0,00' }}</td>
                                        <td class="px-3 py-2 text-right font-medium">
                                            {{ $isRealized ? 'Rp ' . ($item['actual_total_price'] ?? '0,00') : '-' }}
                                        </td>
                                        <td class="{{ $varianceClass }} px-3 py-2 text-right font-semibold">
                                            Rp {{ $item['variance'] ?? '0,00' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- New Request Items --}}
            @if (!empty($receipt['new_request_items']))
                <div>
                    <h4 class="mb-2 font-medium text-gray-900 dark:text-gray-100">Item Request Baru yang Ditambahkan:
                    </h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-100 dark:bg-gray-700">
                                <tr>
                                    <th class="px-3 py-2 text-left">COA</th>
                                    <th class="px-3 py-2 text-left">Aktivitas</th>
                                    <th class="px-3 py-2 text-left">Item</th>
                                    <th class="px-3 py-2 text-right">Qty</th>
                                    <th class="px-3 py-2 text-left">Unit</th>
                                    <th class="px-3 py-2 text-right">Harga/item</th>
                                    <th class="px-3 py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @foreach ($receipt['new_request_items'] as $newItem)
                                    @php
                                        $coa = $newItem['coa_id'] ? \App\Models\Coa::find($newItem['coa_id']) : null;
                                        $activity = $newItem['program_activity_id']
                                            ? \App\Models\ProgramActivity::find($newItem['program_activity_id'])
                                            : null;
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2">{{ $coa?->name ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $activity?->name ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $newItem['item'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-right">{{ $newItem['qty'] ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $newItem['unit_qty'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-right">Rp {{ $newItem['base_price'] ?? '0,00' }}</td>
                                        <td class="px-3 py-2 text-right font-medium">Rp
                                            {{ $newItem['total_price'] ?? '0,00' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endforeach
</div>

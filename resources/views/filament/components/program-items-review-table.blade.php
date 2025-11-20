@props(['items'])

<div class="filament-tables-container overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="overflow-x-auto">
        <table class="w-full table-auto divide-y divide-gray-200 dark:divide-white/5">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white" style="width: 500px;">
                        <span class="group flex items-center gap-x-1 whitespace-nowrap">
                            <span>Deskripsi</span>
                        </span>
                    </th>
                    <th class="px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white" style="width: 250px;">
                        <span class="group flex items-center gap-x-1 whitespace-nowrap">
                            <span>Aktivitas</span>
                        </span>
                    </th>
                    <th class="px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white" style="width: 150px;">
                        <span class="group flex items-center gap-x-1 whitespace-nowrap">
                            <span>Qty</span>
                        </span>
                    </th>
                    <th class="px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">
                        <span class="group flex items-center gap-x-1 whitespace-nowrap">
                            <span>Unit Qty</span>
                        </span>
                    </th>
                    <th class="px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">
                        <span class="group flex items-center gap-x-1 whitespace-nowrap">
                            <span>Frekuensi</span>
                        </span>
                    </th>
                    <th class="px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white" style="width: 250px;">
                        <span class="group flex items-center gap-x-1 whitespace-nowrap">
                            <span>Nilai Kontrak Item</span>
                        </span>
                    </th>
                    <th class="px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white" style="width: 250px;">
                        <span class="group flex items-center gap-x-1 whitespace-nowrap">
                            <span>Nilai Planned Item</span>
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                @forelse($items as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                        <td class="px-3 py-4 text-sm text-gray-950 dark:text-white">
                            {{ $item['description'] ?? '' }}
                        </td>
                        <td class="px-3 py-4 text-sm text-gray-950 dark:text-white">
                            {{ $item['program_activity_name'] ?? '' }}
                        </td>
                        <td class="px-3 py-4 text-sm text-gray-950 dark:text-white">
                            {{ $item['quantity'] ?? '' }}
                        </td>
                        <td class="px-3 py-4 text-sm text-gray-950 dark:text-white">
                            {{ $item['unit'] ?? '' }}
                        </td>
                        <td class="px-3 py-4 text-sm text-gray-950 dark:text-white">
                            {{ $item['frequency'] ?? '' }}
                        </td>
                        <td class="px-3 py-4 text-sm text-gray-950 dark:text-white">
                            {{ $item['total_item_budget_formatted'] ?? '' }}
                        </td>
                        <td class="px-3 py-4 text-sm text-gray-950 dark:text-white">
                            {{ $item['total_item_planned_budget_formatted'] ?? '' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            Tidak ada data item
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

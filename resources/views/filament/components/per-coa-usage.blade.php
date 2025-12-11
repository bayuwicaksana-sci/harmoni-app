<div class="flex w-full flex-col gap-3">
    <div class="flex flex-wrap justify-between gap-5 text-sm">
        <div class="flex flex-col">
            <span class="text-sm text-gray-500">Total Pengeluaran Aktual</span>
            <span class="text-xl font-semibold">Rp {{ number_format($totalSpent, 2, ',', '.') }}</span>
        </div>
        <div class="flex flex-col">
            <span class="text-sm text-gray-500">Total Planned Budget</span>
            <span class="text-xl font-semibold">Rp {{ number_format($totalCoaPlannedBudget, 2, ',', '.') }}</span>
        </div>
    </div>
    <div class="h-3 w-full overflow-hidden rounded-full bg-gray-200">
        <div class="{{ $colorClass }} h-3 rounded-full transition-all duration-300"
            style="width: {{ $progressPercentage }}%"></div>
    </div>
    <div class="flex items-center justify-between">
        <div class="text-xs text-gray-500">{{ $isNegative ? 'Over Budget' : 'Under Budget' }}</div>
        <div class="{{ $textColorClass }} text-sm font-semibold">{{ ($isNegative ? '-' : '+') . round($percentage, 2) }}
            %
        </div>
    </div>
    <div class="text-right">
        <div class="{{ $textColorClass }} font-semibold">Rp {{ number_format(abs($variance), 2, ',', '.') }}</div>
    </div>
</div>

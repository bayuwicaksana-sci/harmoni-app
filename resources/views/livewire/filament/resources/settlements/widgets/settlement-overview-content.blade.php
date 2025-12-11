<div class="flex w-full flex-col gap-3">
    <div class="flex flex-wrap justify-between gap-2 text-sm">
        <div class="flex flex-col">
            <span class="text-xs text-gray-500">Total Requested</span>
            <span class="font-semibold">Rp {{ number_format($totalRequested, 2, ',', '.') }}</span>
        </div>
        <div class="flex flex-col">
            <span class="text-xs text-gray-500">Total Settled</span>
            <span class="font-semibold">Rp {{ number_format($totalSettled, 2, ',', '.') }}</span>
        </div>
    </div>
    <div class="h-3 w-full overflow-hidden rounded-full bg-gray-200">
        <div class="{{ $colorClass }} h-3 rounded-full transition-all duration-300"
            style="width: {{ $progressPercentage }}%"></div>
    </div>
    <div class="flex items-center justify-between">
        <div class="text-xs text-gray-500">{{ $isNegative ? 'Over Budget' : 'Under Budget' }}</div>
        <div class="{{ $textColorClass }} text-base font-semibold">
            {{ ($isNegative ? '-' : '+') . round($percentage, 2) }}
            %
        </div>
    </div>
    <div class="text-right">
        <div class="{{ $textColorClass }} text-xl font-semibold">Rp {{ number_format(abs($variance), 2, ',', '.') }}
        </div>
    </div>
</div>

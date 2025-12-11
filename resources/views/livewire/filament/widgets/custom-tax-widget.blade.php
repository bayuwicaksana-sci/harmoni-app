<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Nilai Pajak
        </x-slot>
        <x-slot name="afterHeader">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="filter">
                    <option value="all">Semua</option>
                    <option value="this_month">Bulan ini</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </x-slot>
        <div class="flex flex-col gap-5">
            <div class="flex-col gap-1">
                <div class="text-base">PPh 21</div>
                <div class="text-3xl font-semibold">Rp {{ number_format($pph21, 2, ',', '.') }}</div>
            </div>
            <div class="flex-col gap-1">
                <div class="text-base">PPh 23</div>
                <div class="text-3xl font-semibold">Rp {{ number_format($pph23, 2, ',', '.') }}</div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

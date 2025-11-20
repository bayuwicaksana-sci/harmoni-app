<?php

namespace App\Filament\Resources\Clients\Resources\PartnershipContracts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PartnershipContractsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->withCount('programs'))
            ->columns([
                TextColumn::make('contract_number')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->label('Nomor Kontrak')
                    ->weight('bold'),

                TextColumn::make('client.name')
                    ->searchable()
                    ->label('Nama Klien')
                    ->sortable(),

                TextColumn::make('contract_year')
                    ->badge()
                    ->color('info')
                    ->label('Tahun Kontrak')
                    ->sortable(),

                TextColumn::make('start_date')
                    ->date('d M Y')
                    ->label('Dari')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->date('d M Y')
                    ->label('Hingga')
                    ->sortable(),

                TextColumn::make('contract_value')
                    ->getStateUsing(fn($record) => $record->contract_value)
                    ->money('IDR')
                    ->label('Nilai Kontrak')
                    ->sortable(
                        query: fn($query, $direction) =>
                        $query->withSum('programActivityItems', 'total_item_budget')
                            ->orderBy('program_activity_items_sum_total_item_budget', $direction)
                    ),

                TextColumn::make('planned_value')
                    ->getStateUsing(fn($record) => $record->planned_value)
                    ->money('IDR')
                    ->label('Nilai Planned')
                    ->sortable(
                        query: fn($query, $direction) =>
                        $query->withSum('programActivityItems', 'total_item_planned_budget')
                            ->orderBy('program_activity_items_sum_total_item_planned_budget', $direction)
                    ),

                TextColumn::make('programs_count')
                    // ->counts('programs')
                    ->label('Jumlah Program')
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                SelectFilter::make('client')
                    ->label('Klien')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('contract_year')
                    ->label('Tahun Kontrak')
                    ->options(function () {
                        $years = range(2020, 2030);
                        return array_combine($years, $years);
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('contract_year', 'desc');
    }
}

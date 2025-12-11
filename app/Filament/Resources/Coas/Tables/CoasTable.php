<?php

namespace App\Filament\Resources\Coas\Tables;

use App\Enums\COAType;
use App\Filament\Resources\Coas\RelationManagers\RequestItemsRelationManager;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Guava\FilamentModalRelationManagers\Actions\RelationManagerAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class CoasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->header(new HtmlString('Hai'))
            ->modifyQueryUsing(function (Builder $query) {
                if (Auth::user()->employee->jobTitle->code === 'CEO' || Auth::user()->employee->jobTitle->department->code === 'FIN') {
                    return $query;
                } else {
                    return $query->active();
                }
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Nama COA')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('type')
                    ->label('Tipe COA')
                    ->badge(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedXCircle)
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('actual_spent')
                    ->label('Penggunaan Aktual')
                    ->money(currency: 'IDR', locale: 'id'),

                TextColumn::make('planned_budget')
                    ->label('Nilai Planned')
                    ->money(currency: 'IDR', locale: 'id'),

                // TextColumn::make('total_allocated')
                //     ->label('Total Dialokasikan')
                //     ->money('IDR')
                //     ->getStateUsing(fn($record) => $record->getTotalSpent())
                //     ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe COA')
                    ->options(COAType::class),

                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->boolean()
                    ->trueLabel('Hanya')
                    ->falseLabel('Inactive Only')
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make()
                    ->slideOver(),
                EditAction::make()
                    ->slideOver(),
                RelationManagerAction::make('request-item-relation')
                    ->label('Transaksi')
                    ->modalHeading(fn ($record) => 'Riwayat Transaksi : '.$record->name)
                    ->record(fn ($record) => $record)
                    ->relationManager(RequestItemsRelationManager::class)
                    ->modalWidth(Width::SevenExtraLarge),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

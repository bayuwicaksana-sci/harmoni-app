<?php

namespace App\Filament\Resources\Coas\Tables;

use App\Enums\COAType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CoasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('primary')
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('Nama COA')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('coa_program_category')
                    ->label('Tipe COA')
                    ->badge()
                    ->color(fn ($state) => $state === 'Non-Program' ? 'info' : 'success'),

                TextColumn::make('program.name')
                    ->label('Program')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—')
                    ->wrap(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedXCircle)
                    ->trueColor('success')
                    ->falseColor('danger'),

                // TextColumn::make('total_allocated')
                //     ->label('Total Dialokasikan')
                //     ->money('IDR')
                //     ->getStateUsing(fn($record) => $record->getTotalSpent())
                //     ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

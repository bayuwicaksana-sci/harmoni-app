<?php

namespace App\Filament\Resources\Settlements\Schemas;

use App\Filament\Resources\Settlements\Schemas\Components\EditSettlementForm;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class SettlementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(self::formFields());
    }

    public static function formFields(): array
    {
        return [
            // Fieldset::make('Ringkasan Financial')
            //     ->columnSpanFull()
            //     ->columns(4)
            //     ->schema([
            //         TextInput::make('approved_request_amount')
            //             ->label('Pengeluaran yang Disetujui')
            //             ->prefix('Rp')
            //             ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
            //             ->readOnly()
            //             ->dehydrated(false),
            //         TextInput::make('cancelled_amount')
            //             ->label('Nominal yang Dibatalkan')
            //             ->prefix('Rp')
            //             ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
            //             ->readOnly()
            //             ->dehydrated(false),
            //         TextInput::make('spent_amount')
            //             ->label('Nominal yang Dibelanjakan')
            //             ->prefix('Rp')
            //             ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
            //             ->readOnly()
            //             ->dehydrated(false),
            //         TextInput::make('variance')
            //             ->label('Selisih Nominal')
            //             ->prefix('Rp')
            //             ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
            //             ->readOnly()
            //             ->dehydrated(false),
            //     ]),
            EditSettlementForm::make(),
        ];
    }

    public static function parseIdrToFloat(string $currency): float
    {
        // Remove thousand separator (.) and replace decimal separator (,) with (.)
        $parsed = str_replace('.', '', $currency);
        $parsed = str_replace(',', '.', $parsed);

        return (float) $parsed;
    }

    public static function parseCurrencyToFloat(array $records): array
    {
        // dd($records);
        $currencyFields = [
            'amount_per_item',
            'total_price',
            'act_amount_per_item',
            'act_total_price',
            'variance',
        ];

        foreach ($records as $recordKey => $record) {
            // Parse request_items
            if (isset($record['requestItems']) && is_array($record['requestItems'])) {
                foreach ($record['requestItems'] as $itemKey => $item) {
                    foreach ($currencyFields as $field) {
                        if (isset($item[$field]) && is_string($item[$field])) {
                            $records[$recordKey]['requestItems'][$itemKey][$field] = self::parseIdrToFloat($item[$field]);
                        }
                    }
                }
            }

            // Parse new_request_items (if any)
            if (isset($record['requestItems']) && is_array($record['requestItems'])) {
                foreach ($record['requestItems'] as $itemKey => $item) {
                    foreach ($currencyFields as $field) {
                        if (isset($item[$field]) && is_string($item[$field])) {
                            $records[$recordKey]['requestItems'][$itemKey][$field] = self::parseIdrToFloat($item[$field]);
                        }
                    }
                }
            }
        }

        return $records;
    }
}

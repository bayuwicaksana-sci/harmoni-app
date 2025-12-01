<?php

namespace App\Filament\Resources\Settlements\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SettlementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('settlement_number')
                    ->placeholder('-'),
                TextEntry::make('submitter.id')
                    ->label('Submitter')
                    ->placeholder('-'),
                TextEntry::make('refund_amount')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('submit_date')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('previous_status')
                    ->placeholder('-'),
                TextEntry::make('generatedPaymentRequest.id')
                    ->label('Generated payment request')
                    ->placeholder('-'),
                TextEntry::make('refund_confirmed_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('refund_confirmed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('confirmed_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('confirmed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('revision_notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}

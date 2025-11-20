<?php

namespace App\Filament\Resources\DailyPaymentRequests;

use App\Enums\DPRStatus;
use App\Filament\Resources\DailyPaymentRequests\RelationManagers\RequestItemsRelationManager;
use App\Filament\Resources\DailyPaymentRequests\Pages\CreateDailyPaymentRequest;
use App\Filament\Resources\DailyPaymentRequests\Pages\EditDailyPaymentRequest;
use App\Filament\Resources\DailyPaymentRequests\Pages\ListDailyPaymentRequests;
use App\Filament\Resources\DailyPaymentRequests\Pages\ViewDailyPaymentRequest;
use App\Filament\Resources\DailyPaymentRequests\Schemas\DailyPaymentRequestForm;
use App\Filament\Resources\DailyPaymentRequests\Schemas\DailyPaymentRequestInfolist;
use App\Filament\Resources\DailyPaymentRequests\Tables\DailyPaymentRequestsTable;
use App\Models\DailyPaymentRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class DailyPaymentRequestResource extends Resource
{
    protected static ?string $model = DailyPaymentRequest::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;
    protected static string | \UnitEnum | null $navigationGroup = "Transactions";
    protected static ?string $navigationLabel = "Payment Requests";

    protected static ?string $recordTitleAttribute = 'request_number';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->where('status', DPRStatus::Pending)->whereNot('requester_id', Auth::user()?->employee?->id)->whereHas('approvalHistories', function ($q) {
            // Only show requests where this employee is the CURRENT pending approver
            $q->where('approver_id', Auth::user()?->employee?->id)
                ->where('action', 'pending')
                ->whereRaw('sequence = (
                      SELECT MIN(sequence)
                      FROM approval_histories ah2
                      WHERE ah2.daily_payment_request_id = approval_histories.daily_payment_request_id
                      AND ah2.action = "pending"
                  )');
        })->count();
        return $count > 0 ? $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Jumlah Permintaan Approval';
    }

    public static function form(Schema $schema): Schema
    {
        return DailyPaymentRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DailyPaymentRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DailyPaymentRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RequestItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDailyPaymentRequests::route('/'),
            'create' => CreateDailyPaymentRequest::route('/create'),
            'view' => ViewDailyPaymentRequest::route('/{record}'),
            'edit' => EditDailyPaymentRequest::route('/{record}/edit'),
        ];
    }
}

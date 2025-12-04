<?php

namespace App\Filament\Resources\Coas\Pages;

use App\Filament\Resources\Coas\CoaResource;
use App\Filament\Resources\Coas\Widgets\CoaUsageOverview;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCoa extends ViewRecord
{
    protected static string $resource = CoaResource::class;

    protected ?bool $hasErrorNotifications = true;

    protected function setUpErrorNotifications(): void
    {
        $this->registerErrorNotification(
            title: 'COA tidak ditemukan',
            body: 'COA yang anda cari tidak ditemukan',
            statusCode: 404,
        );
        $this->registerErrorNotification(
            title: 'Akses Ditolak',
            body: 'COA ini Confidential',
            statusCode: 403,
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CoaUsageOverview::class,
        ];
    }
}

<?php

namespace App\Filament\Resources\JobTitles\Pages;

use App\Filament\Resources\JobTitles\JobTitleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJobTitle extends CreateRecord
{
    protected static string $resource = JobTitleResource::class;
}

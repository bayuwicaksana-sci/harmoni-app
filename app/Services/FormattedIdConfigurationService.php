<?php

namespace App\Services;

class FormattedIdConfigurationService
{
    protected array $configurations = [];

    public function __construct()
    {
        $this->configurations = config('formatted_ids', []);
    }

    public function getConfiguration(string $modelClass): ?array
    {
        return $this->configurations[$modelClass] ?? null;
    }

    public function setConfiguration(string $modelClass, array $config): void
    {
        $this->configurations[$modelClass] = $config;
    }
}

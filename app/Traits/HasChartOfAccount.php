<?php

namespace App\Traits;

use App\Models\ChartOfAccount;

trait HasChartOfAccount
{
    public static function bootHasChartOfAccount()
    {
        static::deleting(function ($model) {
            if ($model->isCoaEnabled()) {
                $model->chartOfAccount()->delete();
            }
        });
    }

    public function chartOfAccount()
    {
        return $this->morphOne(ChartOfAccount::class, 'accountable');
    }

    public function isCoaEnabled(): bool
    {
        return $this->chartOfAccount()->exists();
    }

    public function enableCoa(array $coaData = []): ChartOfAccount
    {
        if ($this->isCoaEnabled()) {
            return $this->chartOfAccount;
        }

        $defaultData = [
            'code' => $this->generateCoaCode(),
            'name' => $this->getCoaName(),
            'type' => $this->getDefaultCoaType(),
            'balance' => 0,
        ];

        return $this->chartOfAccount()->create(array_merge($defaultData, $coaData));
    }

    public function disableCoa(): bool
    {
        if (!$this->isCoaEnabled()) {
            return false;
        }

        return $this->chartOfAccount()->delete();
    }

    protected function generateCoaCode(): string
    {
        $prefix = strtoupper(substr(class_basename($this), 0, 3));
        return $prefix . '-' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    protected function getCoaName(): string
    {
        return $this->name ?? class_basename($this) . ' #' . $this->id;
    }

    protected function getDefaultCoaType(): string
    {
        return 'expense';
    }
}

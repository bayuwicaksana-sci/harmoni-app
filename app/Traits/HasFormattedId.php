<?php

namespace App\Traits;

use App\Services\FormattedIdConfigurationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait HasFormattedId
{
    protected static function bootHasFormattedId(): void
    {
        static::created(function (Model $model) {
            $model->generateFormattedId();
        });
    }

    public function generateFormattedId(): void
    {
        $config = $this->getFormattedIdConfiguration();

        if (!$config) {
            return;
        }

        $fieldName = $config['field'];

        if ($this->getAttribute($fieldName) !== null) {
            return; // Already has a formatted ID
        }

        DB::transaction(function () use ($fieldName, $config) {
            // Lock the record to prevent concurrent updates
            $locked = static::lockForUpdate()->find($this->id);

            if ($locked->getAttribute($fieldName) !== null) {
                return; // Another process already generated it
            }

            $formattedId = $this->buildFormattedId($this->id, $config);

            // Update directly without triggering model events
            DB::table($this->getTable())
                ->where('id', $this->id)
                ->update([$fieldName => $formattedId]);

            $this->{$fieldName} = $formattedId;
        });
    }

    protected function buildFormattedId(int $id, array $config): string
    {
        // Handle custom formatter if provided
        if (isset($config['formatter']) && is_callable($config['formatter'])) {
            return call_user_func($config['formatter'], $id, $this);
        }

        // Default formatting logic
        $components = [];

        if (!empty($config['prefix'])) {
            $components[] = $config['prefix'];
        }

        if ($config['include_year'] ?? false) {
            $components[] = now()->year;
        }

        if ($config['include_month'] ?? false) {
            $components[] = now()->format('m');
        }

        $components[] = str_pad(
            $id,
            $config['padding'] ?? 6,
            $config['pad_string'] ?? '0',
            STR_PAD_LEFT
        );

        return implode($config['separator'] ?? '-', $components);
    }

    public function hasFormattedId(): bool
    {
        $config = $this->getFormattedIdConfiguration();

        if (!$config) {
            return true; // No formatted ID configured, so it's "complete"
        }

        return $this->{$config['field']} !== null;
    }

    public function regenerateFormattedId(): void
    {
        $config = $this->getFormattedIdConfiguration();

        if (!$config) {
            return;
        }

        $this->{$config['field']} = null;
        $this->generateFormattedId();
    }

    /**
     * Get the formatted ID configuration for this model.
     * Override this method in your model to configure the formatted ID.
     * Return null if the model doesn't use formatted IDs.
     */
    protected function getFormattedIdConfiguration(): ?array
    {
        // First check if model has overridden configuration
        if (property_exists($this, 'formattedIdConfig')) {
            return $this->formattedIdConfig;
        }

        // Otherwise, get from configuration service
        $service = app(FormattedIdConfigurationService::class);
        return $service->getConfiguration(get_class($this));
    }

    public function __get($key)
    {
        $config = $this->getFormattedIdConfiguration();

        if ($config && $key === $config['field']) {
            $value = parent::__get($key);

            // Auto-generate if missing when accessed
            if ($value === null && $this->exists && ($config['auto_generate_on_access'] ?? true)) {
                $this->generateFormattedId();
                $this->refresh();
                return $this->{$key};
            }

            return $value;
        }

        return parent::__get($key);
    }
}

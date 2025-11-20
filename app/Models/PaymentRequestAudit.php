<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequestAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'daily_payment_request_id',
        'action',
        'old_values',
        'new_values',
        'user_id',
        'ip_address',
        'notes',
        'created_at'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = now();
            $model->ip_address = request()->ip();
        });
    }

    public function dailyPaymentRequest(): BelongsTo
    {
        return $this->belongsTo(DailyPaymentRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

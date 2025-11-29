<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SettlementReceipt extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = [];

    protected $casts = [
        'realization_date' => 'datetime',
    ];

    // Relationships

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function requestItems(): HasMany
    {
        return $this->hasMany(RequestItem::class, 'settlement_receipt_id');
    }

    // Media Collections

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('settlement_receipt_attachments')
            ->useDisk('local')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf']);
        // ->registerMediaConversions(function (Media $media) {
        //     $this
        //         ->addMediaConversion('thumb')
        //         ->width(300)
        //         ->height(300)
        //         ->sharpen(10);

        //     $this
        //         ->addMediaConversion('medium')
        //         ->width(800)
        //         ->height(600);
        // });
    }
}

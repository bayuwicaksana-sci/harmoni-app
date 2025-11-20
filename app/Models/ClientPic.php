<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPic extends Model
{
    protected $fillable = [
        'client_id',
        'name',
        'email',
        'position',
        'phone'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}

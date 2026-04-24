<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = ['epc_id', 'type'];

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'epc_id', 'epc_id');
    }
}

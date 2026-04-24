<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tag extends Model
{
    protected $primaryKey = 'epc_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['epc_id', 'item_id', 'status'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'epc_id', 'epc_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScannerState extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['id', 'active_mode', 'target_item_id'];

    const UPDATED_AT = 'updated_at';

    public function targetItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'target_item_id');
    }

    /**
     * Get the singleton state (always Row ID=1)
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'active_mode' => 'idle',
            'target_item_id' => null,
        ]);
    }
}

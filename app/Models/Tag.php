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

    /**
     * Cari tag berdasarkan EPC dengan fallback substring matching.
     *
     * Reader RFID bisa melaporkan EPC yang sama tapi dengan offset berbeda
     * dalam byte frame (misal: dengan/tanpa PC word, trailing zeros, dll).
     * Prioritas: exact → stored-dalam-scanned → scanned-dalam-stored.
     */
    public static function findByEpc(string $epc): ?self
    {
        // 1. Exact match — jalur paling cepat
        $tag = static::with('item')->where('epc_id', $epc)->first();
        if ($tag) return $tag;

        // 2. Substring match — guard minimal 8 char agar tidak false-positive
        if (strlen($epc) < 8) return null;

        return static::with('item')
            ->where(function ($q) use ($epc) {
                // EPC tersimpan muncul di dalam EPC yang di-scan (reader menambah prefix/suffix)
                $q->whereRaw('LOCATE(epc_id, ?) > 0', [$epc])
                // EPC yang di-scan muncul di dalam EPC tersimpan (stored lebih panjang)
                  ->orWhereRaw('LOCATE(?, epc_id) > 0', [$epc]);
            })
            ->whereRaw('LENGTH(epc_id) >= 8')
            ->first();
    }
}

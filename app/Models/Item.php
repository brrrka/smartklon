<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $fillable = ['kode_barang', 'nama_barang', 'deskripsi', 'satuan'];

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function inStockTags(): HasMany
    {
        return $this->hasMany(Tag::class)->where('status', 'in_stock')->orderBy('created_at', 'asc');
    }

    public function outOfStockTags(): HasMany
    {
        return $this->hasMany(Tag::class)->where('status', 'out_of_stock');
    }

    public function getInStockCountAttribute(): int
    {
        return $this->tags()->where('status', 'in_stock')->count();
    }

    public function getOutOfStockCountAttribute(): int
    {
        return $this->tags()->where('status', 'out_of_stock')->count();
    }

    public function getTotalTagsAttribute(): int
    {
        return $this->tags()->count();
    }
}

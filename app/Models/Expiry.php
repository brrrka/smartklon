<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Expiry extends Model
{
    protected $guarded = ['id'];

    // Pastikan property dinamis ini selalu dibawa saat di-return ke JSON (seperti saat liveData dipanggil)
    protected $appends = ['expiry_state', 'days_left'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    // Menghitung sisa hari
    public function getDaysLeftAttribute()
    {
        return Carbon::today()->diffInDays(Carbon::parse($this->expiry_date), false);
    }

    // Menentukan State
    public function getExpiryStateAttribute()
    {
        $daysLeft = $this->days_left;

        if ($daysLeft < 0) return 'Expired';
        if ($daysLeft == 0) return 'Expired Today';
        if ($daysLeft <= 7) return 'Warning';

        return 'Safe';
    }
}

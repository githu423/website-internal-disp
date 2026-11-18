<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Notifikasi pengguna (lambang "belum dibaca" = dibaca_at masih kosong).
 */
class Notifikasi extends Model
{
    protected $table = 'notifikasi';
    protected $fillable = ['user_id', 'judul', 'isi', 'tipe', 'url', 'dibaca_at'];
    protected $casts = ['dibaca_at' => 'datetime'];

    // ===== RELASI =====
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ===== SCOPE: YANG BELUM DIBACA =====
    public function scopeBelum($query)
    {
        return $query->whereNull('dibaca_at');
    }
}

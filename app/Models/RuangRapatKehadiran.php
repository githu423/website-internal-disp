<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Daftar kehadiran tamu pada satu ruang rapat.
 */
class RuangRapatKehadiran extends Model
{
    protected $table = 'ruang_rapat_kehadiran';

    public $timestamps = false;

    protected $fillable = ['id_ruang_rapat', 'nama', 'instansi', 'kontak'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    // ===== RELASI =====
    public function ruangRapat()
    {
        return $this->belongsTo(RuangRapat::class, 'id_ruang_rapat');
    }
}

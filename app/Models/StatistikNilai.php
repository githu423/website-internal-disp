<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Nilai satu indikator statistik untuk satu tahun.
 */
class StatistikNilai extends Model
{
    public $timestamps = false;

    protected $table = 'statistik_nilai';

    protected $fillable = ['id_statistik', 'tahun', 'nilai'];

    // ===== TIPE DATA =====
    protected $casts = ['nilai' => 'float', 'tahun' => 'integer'];
}

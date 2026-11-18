<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu indikator statistik pariwisata (mis. "Jumlah Kunjungan Wisatawan"),
 * dengan nilai per tahun di statistik_nilai. Kunci upsert import = nama indikator.
 */
class Statistik extends Model
{
    protected $table = 'statistik';

    protected $fillable = ['no_urut', 'indikator', 'satuan', 'keterangan'];

    // ===== RELASI =====
    public function nilais()
    {
        return $this->hasMany(StatistikNilai::class, 'id_statistik')->orderBy('tahun');
    }
}

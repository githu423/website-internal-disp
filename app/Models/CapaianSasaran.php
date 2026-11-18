<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Sasaran strategis pada matriks Capaian Kinerja (satu baris "No." pada berkas sumber).
 */
class CapaianSasaran extends Model
{
    public $timestamps = false;

    protected $table = 'capaian_sasaran';
    protected $fillable = ['nomor', 'judul', 'cakupan', 'urut', 'id_bidang'];

    // ===== RELASI =====
    public function bidang()
    {
        return $this->belongsTo(Bidang::class, 'id_bidang');
    }

    public function indikator()
    {
        return $this->hasMany(CapaianIndikator::class, 'sasaran_id')->orderBy('urut');
    }
}

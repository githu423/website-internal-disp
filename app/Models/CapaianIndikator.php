<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Indikator kinerja di bawah sebuah sasaran (kolom "Indikator Kinerja" pada berkas sumber).
 */
class CapaianIndikator extends Model
{
    public $timestamps = false;

    protected $table = 'capaian_indikator';
    protected $fillable = ['sasaran_id', 'nama', 'satuan', 'urut'];

    // ===== RELASI =====
    public function sasaran()
    {
        return $this->belongsTo(CapaianSasaran::class, 'sasaran_id');
    }

    public function tahunan()
    {
        return $this->hasMany(CapaianTahunan::class, 'indikator_id')->orderBy('tahun');
    }

    // ===== PENGOLAHAN ANGKA DARI TEKS =====

    /** Angka persen dari teks bebas: "84.36%", "105", ">100", "n/a" → float|null */
    public static function persenNum(?string $p): ?float
    {
        if ($p === null || trim($p) === '' || strcasecmp(trim($p), 'n/a') === 0) {
            return null;
        }
        $angka = preg_replace('/[^\d.\-]/', '', str_replace(',', '', $p));

        return $angka === '' ? null : (float) $angka;
    }

    /** Angka murni dari teks berkas: "1,498,925" → 1498925; "BB"/"n/a" → null */
    public static function angkaNum(?string $v): ?float
    {
        if ($v === null || trim($v) === '' || strcasecmp(trim($v), 'n/a') === 0) {
            return null;
        }
        $angka = preg_replace('/[^\d.\-]/', '', str_replace(',', '', $v));

        return is_numeric($angka) ? (float) $angka : null;
    }
}

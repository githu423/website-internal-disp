<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Kinerja fisik: program / kegiatan / sub-kegiatan dinas per tahun.
 *
 * Contoh level: program, kegiatan, sub_kegiatan.
 */
class CapaianProgram extends Model
{
    protected $table = 'capaian_program';
    protected $fillable = ['tahun', 'level', 'nama', 'sumber_dana', 'pagu_anggaran', 'indikator',
        'target', 'satuan', 'capaian', 'persentase', 'catatan', 'id_bidang', 'urutan'];

    // ===== RELASI =====
    public function bidang()
    {
        return $this->belongsTo(Bidang::class, 'id_bidang');
    }

    // ===== SCOPE: FILTER TAHUN (OPSIONAL) =====
    public function scopeTahun($query, ?int $tahun)
    {
        return $query->when($tahun, fn ($queryInner) => $queryInner->where('tahun', $tahun));
    }

    // ===== DAFTAR TAHUN YANG ADA DATANYA =====
    public static function tahunTersedia(): array
    {
        return static::query()->distinct()->orderByDesc('tahun')->pluck('tahun')->map(fn ($tahun) => (int) $tahun)->all();
    }

    // ===== LABEL STATUS BERDASARKAN PERSENTASE =====
    public function getLabelStatusAttribute(): string
    {
        $persen = (float) $this->persentase;

        return match (true) {
            $persen >= 100 => 'Tuntas',
            $persen >= 60 => 'Berjalan',
            default => 'Perlu perhatian',
        };
    }
}

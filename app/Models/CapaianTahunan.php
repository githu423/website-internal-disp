<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Nilai satu indikator untuk satu tahun: target, realisasi, persen (apa adanya dari berkas).
 */
class CapaianTahunan extends Model
{
    public $timestamps = false;

    protected $table = 'capaian_tahunan';
    protected $fillable = ['indikator_id', 'tahun', 'target', 'realisasi', 'persen'];

    // ===== RELASI =====
    public function indikator()
    {
        return $this->belongsTo(CapaianIndikator::class, 'indikator_id');
    }

    // ===== RINGKASAN TINGKAT CAPAIAN PER TAHUN =====
    /**
     * v23: tingkat capaian keseluruhan per tahun = % indikator bernilai
     * yang mencapai target (>=100%). Dipakai halaman statistik capaian
     * DAN kartu dashboard, biar angkanya selalu sama.
     *
     * @return array{0:int[],1:array<int,float|null>}
     */
    public static function tingkatPerTahun(): array
    {
        $daftarTahun = self::distinct()->orderBy('tahun')->pluck('tahun')->map(fn ($tahun) => (int) $tahun)->all();
        $pct = [];

        foreach ($daftarTahun as $tahun) {
            $dinilai = self::where('tahun', $tahun)->get()
                ->filter(fn ($nilai) => CapaianIndikator::persenNum($nilai->persen) !== null);
            $pct[] = $dinilai->count()
                ? round($dinilai->filter(fn ($nilai) => CapaianIndikator::persenNum($nilai->persen) >= 100)->count() / $dinilai->count() * 100, 1)
                : null;
        }

        return [$daftarTahun, $pct];
    }

    // ===== APAKAH NILAI INI MENCAPAI TARGET =====
    public function getTercapaiAttribute(): ?bool
    {
        $persenNum = CapaianIndikator::persenNum($this->persen);

        return $persenNum === null ? null : $persenNum >= 100;
    }
}

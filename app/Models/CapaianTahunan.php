<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Nilai satu indikator untuk satu tahun: target, realisasi, persen (apa adanya dari berkas). */
class CapaianTahunan extends Model
{
    public $timestamps = false;

    protected $table = 'capaian_tahunan';
    protected $fillable = ['indikator_id', 'tahun', 'target', 'realisasi', 'persen'];

    public function indikator()
    {
        return $this->belongsTo(CapaianIndikator::class, 'indikator_id');
    }

    /** v23: tingkat capaian keseluruhan per tahun = % indikator bernilai
     *  yang mencapai target (>=100%). Dipakai halaman statistik capaian
     *  DAN kartu dashboard, biar angkanya selalu sama. */
    public static function tingkatPerTahun(): array
    {
        $years = self::distinct()->orderBy('tahun')->pluck('tahun')->map(fn ($t) => (int) $t)->all();
        $pct = [];
        foreach ($years as $y) {
            $din = self::where('tahun', $y)->get()
                ->filter(fn ($n) => CapaianIndikator::persenNum($n->persen) !== null);
            $pct[] = $din->count()
                ? round($din->filter(fn ($n) => CapaianIndikator::persenNum($n->persen) >= 100)->count() / $din->count() * 100, 1)
                : null;
        }
        return [$years, $pct];
    }

    public function getTercapaiAttribute(): ?bool
    {
        $p = CapaianIndikator::persenNum($this->persen);
        return $p === null ? null : $p >= 100;
    }
}

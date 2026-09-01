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

    public function getTercapaiAttribute(): ?bool
    {
        $p = CapaianIndikator::persenNum($this->persen);
        return $p === null ? null : $p >= 100;
    }
}

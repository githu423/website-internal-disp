<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pad extends Model
{
    protected $table = 'pad';
    protected $fillable = ['tahun', 'kategori', 'no_rekening', 'objek', 'target_tahunan', 'target_perubahan',
        'realisasi_bulan_lalu', 'realisasi_bulan_ini', 'kumulatif', 'persentase', 'sisa_target'];

    public static function tahunTersedia(): array
    {
        return static::query()->distinct()->orderByDesc('tahun')->pluck('tahun')->map(fn ($t) => (int) $t)->all();
    }

    public function getTargetEfektifAttribute(): float
    {
        return (float) ($this->target_perubahan > 0 ? $this->target_perubahan : $this->target_tahunan);
    }
}

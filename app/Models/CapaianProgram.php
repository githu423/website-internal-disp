<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapaianProgram extends Model
{
    protected $table = 'capaian_program';
    protected $fillable = ['tahun', 'level', 'nama', 'sumber_dana', 'pagu_anggaran', 'indikator',
        'target', 'satuan', 'capaian', 'persentase', 'catatan', 'id_bidang', 'urutan'];

    public function bidang() { return $this->belongsTo(Bidang::class, 'id_bidang'); }

    public function scopeTahun($q, ?int $t)
    {
        return $q->when($t, fn ($qq) => $qq->where('tahun', $t));
    }

    public static function tahunTersedia(): array
    {
        return static::query()->distinct()->orderByDesc('tahun')->pluck('tahun')->map(fn ($t) => (int) $t)->all();
    }

    public function getLabelStatusAttribute(): string
    {
        $p = (float) $this->persentase;
        return match (true) {
            $p >= 100 => 'Tuntas',
            $p >= 60 => 'Berjalan',
            default => 'Perlu perhatian',
        };
    }
}

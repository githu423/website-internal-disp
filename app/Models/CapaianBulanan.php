<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapaianBulanan extends Model
{
    protected $table = 'capaian_bulanan';
    protected $fillable = ['tahun', 'bulan', 'target', 'capaian'];

    public static function tahunTersedia(): array
    {
        return static::query()->distinct()->orderByDesc('tahun')->pluck('tahun')->map(fn ($t) => (int) $t)->all();
    }
}

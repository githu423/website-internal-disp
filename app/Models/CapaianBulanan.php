<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Data capaian bulanan: target & capaian untuk setiap bulan pada satu tahun.
 */
class CapaianBulanan extends Model
{
    protected $table = 'capaian_bulanan';
    protected $fillable = ['tahun', 'bulan', 'target', 'capaian'];

    // ===== DAFTAR TAHUN YANG ADA DATANYA =====
    public static function tahunTersedia(): array
    {
        return static::query()->distinct()->orderByDesc('tahun')->pluck('tahun')->map(fn ($tahun) => (int) $tahun)->all();
    }
}

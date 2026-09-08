<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prestasi extends Model
{
    protected $table = 'prestasi';
    protected $fillable = ['nama_penghargaan', 'nama_penerima', 'pemberi_penghargaan', 'tahun', 'tingkat', 'is_featured'];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    public static function tahunTersedia(): array
    {
        return static::query()->distinct()->orderByDesc('tahun')->pluck('tahun')->map(fn ($t) => (int) $t)->all();
    }

    /**
     * Ambil data prestasi untuk ditampilkan di slider dashboard:
     * 1. Prioritaskan prestasi yang dipilih/ditandai manual oleh admin (is_featured = true).
     * 2. Sisa slot kuota diisi secara otomatis dengan rotasi harian yang berganti setiap hari.
     */
    public static function untukDashboard(int $limit = 10)
    {
        // 1. Prestasi pilihan manual admin
        $featured = static::where('is_featured', true)
            ->orderByDesc('tahun')
            ->orderByDesc('id')
            ->take($limit)
            ->get();

        $sisa = $limit - $featured->count();
        if ($sisa <= 0) {
            return $featured;
        }

        // 2. Sisa kuota diisi rotasi harian dari prestasi lainnya
        $nonFeatured = static::where('is_featured', false)->get();
        if ($nonFeatured->isEmpty()) {
            return $featured;
        }

        // Seed deterministik berbasis tanggal hari ini (YYYYMMDD) agar selama seharian urutannya stabil,
        // lalu otomatis berganti kombinasi saat memasuki hari berikutnya (jam 00:00).
        mt_srand((int) date('Ymd'));
        $pool = $nonFeatured->all();
        shuffle($pool);
        mt_srand(); // reset seed

        $rotasi = collect($pool)->take($sisa);

        return $featured->concat($rotasi);
    }
}

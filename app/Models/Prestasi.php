<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Prestasi / penghargaan.
 *
 * is_featured = dipilih manual admin untuk tampil di dashboard;
 * tanpa tanda, dirotasi otomatis setiap hari.
 */
class Prestasi extends Model
{
    protected $table = 'prestasi';
    protected $fillable = ['nama_penghargaan', 'nama_penerima', 'pemberi_penghargaan', 'tahun', 'tingkat', 'is_featured'];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    // ===== DAFTAR TAHUN =====
    public static function tahunTersedia(): array
    {
        return static::query()->distinct()->orderByDesc('tahun')->pluck('tahun')->map(fn ($tahun) => (int) $tahun)->all();
    }

    // ===== PRESTASI UNTUK SLIDER DASHBOARD =====
    /**
     * Ambil data prestasi untuk ditampilkan di slider dashboard:
     * 1. Prioritaskan prestasi yang dipilih/ditandai manual oleh admin (is_featured = true).
     * 2. Sisa slot kuota diisi secara otomatis dengan rotasi harian yang berganti setiap hari.
     */
    public static function untukDashboard(int $limit = 10)
    {
        // ===== 1. PRESTASI PILIHAN MANUAL ADMIN =====
        $pilihanAdmin = static::where('is_featured', true)
            ->orderByDesc('tahun')
            ->orderByDesc('id')
            ->take($limit)
            ->get();

        $sisaKuota = $limit - $pilihanAdmin->count();
        if ($sisaKuota <= 0) {
            return $pilihanAdmin;
        }

        // ===== 2. ROTASI HARIAN UNTUK SISA KUOTA =====
        $bukanPilihan = static::where('is_featured', false)->get();
        if ($bukanPilihan->isEmpty()) {
            return $pilihanAdmin;
        }

        // Seed deterministik berbasis tanggal hari ini (YYYYMMDD) agar selama seharian urutannya stabil,
        // lalu otomatis berganti kombinasi saat memasuki hari berikutnya (jam 00:00).
        mt_srand((int) date('Ymd'));
        $kumpulan = $bukanPilihan->all();
        shuffle($kumpulan);
        mt_srand(); // reset seed

        $rotasiHarian = collect($kumpulan)->take($sisaKuota);

        return $pilihanAdmin->concat($rotasiHarian);
    }
}

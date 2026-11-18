<?php

namespace App\Services;

use App\Models\KategoriDokumen;
use Illuminate\Support\Str;

/**
 * Layanan kategori dokumen bertingkat (induk → anak).
 *
 * Kategori DIPAKAI BERSAMA untuk semua dokumen; status akses (publik / privat
 * bidang / rahasia) tetap melekat pada tiap dokumen, bukan pada kategori.
 */
class KategoriDokumenService
{
    /**
     * Mencari kategori berdasarkan nama (case-insensitive). Bila `indukId`
     * diberikan, nama dicari di bawah induk tersebut; selain itu dicari
     * sebagai akar (induk_id NULL).
     */
    public static function cari(string $nama, ?int $indukId = null): ?KategoriDokumen
    {
        // ===== CARI NAMA YANG PERSIS =====
        $nama = (string) Str::of($nama)->trim();

        return KategoriDokumen::query()
            ->where('nama', $nama)
            ->where(fn ($query) => $indukId === null ? $query->whereNull('induk_id') : $query->where('induk_id', $indukId))
            ->first();
    }

    /**
     * Baik membuat kategori baru maupun memakai yang sudah ada. Tidak boleh ada
     * nama duplikat di dalam induk yang sama; bila nama sudah ada, pakai yang ada.
     */
    public static function dapatkanAtauBuat(string $nama, ?int $indukId = null): KategoriDokumen
    {
        // ===== PAKAI YANG SUDAH ADA, ATAU BUAT BARU =====
        $nama = (string) Str::of($nama)->trim()->limit(60, '');

        $ada = static::cari($nama, $indukId);
        if ($ada) {
            return $ada;
        }

        return KategoriDokumen::create([
            'nama' => $nama,
            'induk_id' => $indukId,
        ]);
    }

    /**
     * Membuat/memakai pohon kategori dari teks berjenjang, misal
     * "Laporan / Tahun 2026 / Triwulan I". Segmen yang sama dipakai kembali
     * (tidak membuat duplikat) dan mengembalikan kategori daun terakhir.
     */
    public static function dapatkanAtauBuatBerjenjang(string $teks): KategoriDokumen
    {
        // ===== URAI TEKS MENJADI SEGMEN =====
        $segmen = collect(explode('/', (string) Str::of($teks)->replace('\\', '/')))
            ->map(fn ($bagian) => trim((string) $bagian))
            ->filter()
            ->map(fn ($bagian) => Str::of($bagian)->trim()->limit(60, '')->__toString())
            ->values();

        // ===== BANGUN / PAKAI KATEGORI PER SEGMEN =====
        $indukId = null;
        $nodeAktif = null;
        foreach ($segmen as $segmenSaatIni) {
            $ada = static::cari($segmenSaatIni, $indukId);
            $nodeAktif = $ada ?? KategoriDokumen::create(['nama' => $segmenSaatIni, 'induk_id' => $indukId]);
            $indukId = $nodeAktif->id;
        }

        if ($nodeAktif === null) {
            throw new \InvalidArgumentException('Teks kategori kosong.');
        }

        return $nodeAktif;
    }
}

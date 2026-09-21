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
        $nama = (string) Str::of($nama)->trim();

        return KategoriDokumen::query()
            ->where('nama', $nama)
            ->where(fn ($q) => $indukId === null ? $q->whereNull('induk_id') : $q->where('induk_id', $indukId))
            ->first();
    }

    /**
     * Baik membuat kategori baru maupun memakai yang sudah ada. Tidak boleh ada
     * nama duplikat di dalam induk yang sama; bila nama sudah ada, pakai yang ada.
     */
    public static function dapatkanAtauBuat(string $nama, ?int $indukId = null): KategoriDokumen
    {
        $nama = (string) Str::of($nama)->trim()->limit(60, '');

        $existing = static::cari($nama, $indukId);
        if ($existing) {
            return $existing;
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
        $segmen = collect(explode('/', (string) Str::of($teks)->replace('\\', '/')))
            ->map(fn ($s) => trim((string) $s))
            ->filter()
            ->map(fn ($s) => Str::of($s)->trim()->limit(60, '')->__toString())
            ->values();

        $indukId = null;
        $node = null;
        foreach ($segmen as $seg) {
            $existing = static::cari($seg, $indukId);
            $node = $existing ?? KategoriDokumen::create(['nama' => $seg, 'induk_id' => $indukId]);
            $indukId = $node->id;
        }

        if ($node === null) {
            throw new \InvalidArgumentException('Teks kategori kosong.');
        }

        return $node;
    }
}

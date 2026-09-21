<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Peralihan data lama: kategori teks `Arsip Bidang` yang selama ini ditetapkan
 * otomatis oleh unggahan halaman bidang dipindahkan ke pohon kategori sehingga
 * dokumen lama tetap dapat difilter bersama dokumen baru. Nama kategori lain
 * (jika ada) dikosongkan pada `dokumen` dan dibuatkan sebagai akar kategori
 * agar tidak hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kategori_dokumen')) {
            return;
        }

        $namaLama = DB::table('dokumen')
            ->whereNotNull('kategori')
            ->where('kategori', '<>', '')
            ->distinct()
            ->pluck('kategori')
            ->map(fn ($k) => trim((string) $k))
            ->unique()
            ->filter();

        foreach ($namaLama as $nama) {
            $k = DB::table('kategori_dokumen')->whereNull('induk_id')->where('nama', $nama)->first();
            $id = $k->id ?? null;
            if ($id === null) {
                $id = DB::table('kategori_dokumen')->insertGetId([
                    'nama' => $nama,
                    'induk_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('dokumen')->where('kategori', $nama)->update([
                'id_kategori' => $id,
                'kategori' => null,
            ]);
        }
    }

    public function down(): void
    {
        // Tidak mengembalikan teks lama; data lama tetap terjaga lewat relasi id_kategori.
    }
};

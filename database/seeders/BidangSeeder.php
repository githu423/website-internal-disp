<?php

namespace Database\Seeders;

use App\Models\Bidang;
use Illuminate\Database\Seeder;

class BidangSeeder extends Seeder
{
    public function run(): void
    {
        // Struktur organisasi resmi Dispar (konfirmasi Dinas, 10 Sep 2026) —
        // nama & kepala bidang resmi, kode tetap abadi agar relasi data lama aman.
        foreach (Bidang::daftarResmi() as $b) {
            Bidang::updateOrCreate(['kode' => $b['kode']], [
                'nama' => $b['nama'],
                'ketua' => $b['ketua'],
                'deskripsi' => $b['deskripsi'],
            ]);
        }
    }
}
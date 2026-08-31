<?php

namespace Database\Seeders;

use App\Models\Pegawai;
use App\Services\ImportService;
use Illuminate\Database\Seeder;

class PegawaiSeeder extends Seeder
{
    public function run(): void
    {
        if (Pegawai::count() > 0) return;
        $path = ImportService::cariFileSumber('pegawai', 'PEGAWAI DISPAR.csv');
        if (! $path) {
            $this->command?->warn('Sumber pegawai tidak ditemukan (cek PEGAWAI_IMPORT_PATH di .env), lewati seeding pegawai.');
            return;
        }
        $hasil = ImportService::applyPegawai(ImportService::rowsFrom($path));
        $this->command?->info("Pegawai: {$hasil['dibuat']} data + akun akses dibuat.");
    }
}

<?php

namespace Database\Seeders;

use App\Models\CapaianProgram;
use App\Services\ImportService;
use Illuminate\Database\Seeder;

class KinerjaSeeder extends Seeder
{
    public function run(): void
    {
        if (CapaianProgram::count() > 0) return;
        $path = ImportService::cariFileSumber('kinerja', 'Kinerja Fisik Sedinas.csv');
        if (! $path) {
            $this->command?->warn('Sumber kinerja tidak ditemukan, lewati seeding capaian.');
            return;
        }
        $hasil = ImportService::applyKinerja(ImportService::rowsFrom($path));
        $this->command?->info('Kinerja fisik: ' . $hasil['imported'] . ' baris (tahun ' . implode(', ', $hasil['years']) . ').');
    }
}

<?php

namespace Database\Seeders;

use App\Services\ImportService;
use Illuminate\Database\Seeder;

/** Memuat matriks Capaian Kinerja dari berkas sumber resmi (CAPAIAN KINERJA.csv). */
class CapaianKinerjaSeeder extends Seeder
{
    public function run(): void
    {
        $nama = 'CAPAIAN KINERJA.csv';
        $path = null;
        foreach (['resources/source/' . $nama, 'database/source/' . $nama, 'storage/app/private/' . $nama] as $f) {
            if (is_file(base_path($f))) { $path = base_path($f); break; }
        }
        if (!$path) {
            $this->command?->warn('Berkas CAPAIAN KINERJA.csv tidak ditemukan — lewati seeder capaian kinerja.');
            return;
        }

        $data = ImportService::parseCapaian(ImportService::rowsFrom($path));
        if (!$data) {
            $this->command?->warn('Format CAPAIAN KINERJA.csv tidak dikenali — lewati.');
            return;
        }
        $res = ImportService::applyCapaian($data, 'ganti');
        $this->command?->info(
            "Capaian kinerja: {$res['sasaran_baru']} sasaran, {$res['indikator_baru']} indikator, {$res['nilai_baru']} nilai tahunan."
        );
    }
}

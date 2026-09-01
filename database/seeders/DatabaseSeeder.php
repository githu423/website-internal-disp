<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->salinSumberData();

        $this->call([
            BidangSeeder::class,
            AdminSeeder::class,
            PegawaiSeeder::class,
            KinerjaSeeder::class,
            CapaianKinerjaSeeder::class,
            PadSeeder::class,
            DemoDataSeeder::class,
        ]);
    }

    /** simpan salinan file sumber asli ke database/source agar seed berulang-ulang bisa */
    private function salinSumberData(): void
    {
        foreach ([database_path('source'), storage_path('app/private')] as $dir) {
            File::ensureDirectoryExists($dir);
        }
        foreach (['PEGAWAI DISPAR.csv' => 'PEGAWAI DISPAR.csv', 'Kinerja Fisik Sedinas.csv' => 'Kinerja Fisik Sedinas.csv', 'PAD.csv' => 'PAD.csv', 'CAPAIAN KINERJA.csv' => 'CAPAIAN KINERJA.csv'] as $sumber => $nama) {
            $tujuanPrivat = storage_path('app/private/'.$nama);
            $tujuanCadangan = database_path('source/'.$nama);
            if (is_file($tujuanPrivat) || is_file($tujuanCadangan)) continue;
            foreach ([base_path('resources/source/'.$nama), '/home/user/uploads/'.$nama, getcwd().'/../uploads/'.$nama, getcwd().'/uploads/'.$nama] as $f) {
                if (is_file($f)) {
                    File::copy($f, $tujuanCadangan);
                    File::copy($f, $tujuanPrivat);
                    break;
                }
            }
        }
    }
}

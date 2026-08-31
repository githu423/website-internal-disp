<?php

namespace Database\Seeders;

use App\Models\Pad;
use App\Services\ImportService;
use Illuminate\Database\Seeder;

class PadSeeder extends Seeder
{
    public function run(): void
    {
        if (Pad::count() > 0) return;
        $path = ImportService::cariFileSumber('pad', 'PAD.csv');
        if (! $path) {
            $this->command?->warn('Sumber PAD tidak ditemukan, lewati seeding PAD.');
            return;
        }
        $hasil = ImportService::applyPad(ImportService::rowsFrom($path));
        $this->command?->info('PAD: ' . $hasil['imported'] . ' objek pendapatan (tahun ' . implode(', ', $hasil['years']) . ').');
    }
}

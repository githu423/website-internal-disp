<?php

namespace App\Console\Commands;

use App\Models\Bidang;
use App\Services\ImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Selaraskan peran & bidang akun non-admin dari tabel pegawai.
 *
 * Jalankan: php artisan dispar:sinkron-akun
 */
class SinkronAkunPegawai extends Command
{
    protected $signature = 'dispar:sinkron-akun';

    protected $description = 'Sinkronkan peran (bidang/pegawai) & keanggotaan bidang seluruh akun non-admin mengikuti jabatan & unit kerja di data pegawai';

    public function handle(): int
    {
        // ===== 1. PASTIKAN DATA UPTD ADA =====
        $baru = Bidang::pastikanUptd();
        if ($baru) {
            $this->info("{$baru} bidang UPTD baru ditambahkan (Ciamis & Kawali).");
        }

        // ===== 2. KELOMPOKKAN PEGAWAI KE BIDANG =====
        $m = ImportService::sinkronBidangPegawai();
        if ($m) {
            $this->info("{$m} pegawai dikelompokkan ke bidangnya (deteksi dari jabatan/unit kerja).");
        }

        // ===== 3. SESUAIKAN PERAN & BIDANG AKUN =====
        $n = ImportService::sinkronAkunPegawai();
        $this->info($n ? "{$n} akun disesuaikan (peran & bidang)." : 'Tidak ada yang perlu diubah — semua akun sudah sesuai data pegawai.');

        // ===== 4. PERINGATAN BILA KOLOM BELUM ADA =====
        if (! Schema::hasColumn('pegawai', 'unit_kerja')) {
            $this->warn('Kolom unit_kerja belum ada — jalankan `php artisan migrate` agar bidang juga dideteksi dari UNIT KERJA.');
        }

        return self::SUCCESS;
    }
}

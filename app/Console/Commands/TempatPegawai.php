<?php

namespace App\Console\Commands;

use App\Models\Bidang;
use App\Services\ImportService;
use Illuminate\Console\Command;

/**
 * Tempatkan ulang SELURUH pegawai ke bidangnya sesuai URUTAN baris file Excel
 * sumber (logika batas kepala, konfirmasi Dinas 10 Sep 2026) + sinkronkan akun
 * login terkait. Berguna setelah kode diperbarui: data lama di database baru
 * mengikuti penempatan baru saat file dibaca ulang lewat perintah ini.
 *
 * Jalankan: php artisan dispar:tempat-pegawai
 */
class TempatPegawai extends Command
{
    protected $signature = 'dispar:tempat-pegawai';

    protected $description = 'Tempatkan ulang semua pegawai ke bidang sesuai urutan file Excel sumber (batas kepala) dan sinkronkan akun loginnya';

    public function handle(): int
    {
        // ===== 1. CARI FILE SUMBER =====
        $path = ImportService::cariFileSumber('pegawai', 'PEGAWAI DISPAR.csv');
        if (! $path) {
            $this->error('File sumber daftar pegawai tidak ditemukan.');
            $this->line('Letakkan file di storage/app/private/PEGAWAI_DISPAR.csv (atau sesuaikan PEGAWAI_IMPORT_PATH di .env),');
            $this->line('ATAU unggah lewat menu Kelola Pegawai → Import di browser.');

            return self::FAILURE;
        }

        // ===== 2. BACA & TERAPKAN =====
        $this->line("Membaca: {$path}");
        $hasil = ImportService::applyPegawai(ImportService::rowsFrom($path));

        // ===== 3. LAPORKAN SUSUNAN HASIL =====
        $this->newLine();
        $this->line('Susunan anggota per bidang setelah penempatan:');
        foreach (Bidang::withCount('pegawais')->orderBy('kode')->get() as $bidang) {
            $this->line("  {$bidang->nama}: {$bidang->pegawais_count} pegawai");
        }
        $this->newLine();
        $this->info(sprintf(
            'Selesai: %d pegawai baru, %d diperbarui, %d akun baru, %d akun disinkron (peran/bidang).',
            $hasil['dibuat'], $hasil['diperbarui'], $hasil['akun_baru'], $hasil['akun_disinkron'] ?? 0
        ));

        return self::SUCCESS;
    }
}

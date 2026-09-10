<?php

namespace App\Console\Commands;

use App\Models\Bidang;
use Illuminate\Console\Command;

/**
 * Selaraskan NAMA & KEPALA BIDANG dengan struktur organisasi resmi Dispar
 * (konfirmasi Dinas, 10 Sep 2026). Kode bidang TIDAK berubah sehingga seluruh
 * relasi data lama (pegawai, agenda, dokumen, akun) tetap utuh.
 * Jalankan: php artisan dispar:sinkron-bidang
 */
class SinkronBidangResmi extends Command
{
    protected $signature = 'dispar:sinkron-bidang';

    protected $description = 'Samakan nama & kepala bidang dengan struktur organisasi resmi Dispar (kode tidak berubah, data relasi aman)';

    public function handle(): int
    {
        $ubah = 0;
        foreach (Bidang::daftarResmi() as $resmi) {
            $row = Bidang::where('kode', $resmi['kode'])->first();
            if (! $row) {
                Bidang::create($resmi);
                $ubah++;
                $this->line("  + {$resmi['nama']} (baru)");
                continue;
            }
            $d = [];
            $namaLama = (string) $row->nama;
            if (trim($namaLama) !== $resmi['nama']) $d['nama'] = $resmi['nama'];
            // kepala bidang lama yang sudah diisi manual TIDAK ditimpa — hanya diisi bila kosong
            if (! trim((string) $row->ketua) && $resmi['ketua']) $d['ketua'] = $resmi['ketua'];
            if (trim((string) $row->deskripsi) !== $resmi['deskripsi']) $d['deskripsi'] = $resmi['deskripsi'];
            if ($d) {
                $row->update($d);
                $ubah++;
                $this->line('  ~ ' . $resmi['nama'] . ($namaLama !== $resmi['nama'] ? ' (dari "' . $namaLama . '")' : ''));
            }
        }
        $this->info($ubah ? "{$ubah} bidang disesuaikan dengan struktur resmi." : 'Semua bidang sudah sesuai struktur resmi — tidak ada perubahan.');
        return self::SUCCESS;
    }
}
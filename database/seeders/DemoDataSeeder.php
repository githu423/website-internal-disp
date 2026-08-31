<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\Dokumen;
use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ----- akun contoh per peran -----
        $dummy = [
            ['DUMMY-ADMIN', 'Admin Contoh', 'admin', null],
            ['DUMMY-SEKRE', 'Sekretaris Contoh', 'bidang', 1],
            ['DUMMY-BIDANG', 'Kabid Contoh', 'bidang', 3],
            ['DUMMY-PEGAWAI', 'Staf Contoh', 'pegawai', 3],
        ];
        $users = [];
        foreach ($dummy as [$nip, $nama, $role, $bidang]) {
            $users[$nip] = User::firstOrCreate(['nip' => $nip], [
                'name' => $nama, 'password' => 'password123', 'role' => $role, 'id_bidang' => $bidang,
            ]);
        }

        // ----- agenda contoh (relatif terhadap hari ini, biar fitur pengingat terasa) -----
        if (Agenda::count() === 0) {
            $items = [
                ['Rapat Koordinasi Persiapan Event Wisata Tahunan', now()->addDays(2)->toDateString(), '09:00', 'Aula Dinas', 'rapat', null, 'penting'],
                ['Pendampingan Desa Wisata Karangsari', now()->addDays(5)->toDateString(), '13:00', 'Desa Karangsari, Panjalu', 'kunjungan', 2, null],
                ['Monitoring Daya Tarik Wisata Situ Lengkong', now()->addDays(9)->toDateString(), '08:30', 'Panjalu', 'kegiatan', 2, null],
                ['Workshop Pemasaran Digital Pariwisata', now()->addDays(16)->toDateString(), '08:00', 'Aula Dinas', 'kegiatan', 3, null],
                ['Evaluasi Kinerja Triwulan', now()->subDays(3)->toDateString(), '10:00', 'Ruang Rapat Sekretaris', 'rapat', 1, 'terlewat'],
            ];
            foreach ($items as $it) {
                Agenda::create([
                    'judul' => $it[0], 'tanggal' => $it[1], 'jam_mulai' => $it[2], 'lokasi' => $it[3],
                    'kategori' => $it[4], 'id_bidang' => $it[5], 'status' => $it[6] === 'terlewat' ? 'belum' : 'belum',
                    'created_by' => $users['ADMIN0000'] ?? $users['DUMMY-ADMIN']->id,
                ]);
            }
        }

        // ----- satu dokumen contoh (PDF sederhana) -----
        if (Dokumen::count() === 0) {
            $pdfPath = 'dokumen/contoh-profil-dinas.pdf';
            if (! Storage::disk('public')->exists($pdfPath)) {
                Storage::disk('public')->put($pdfPath, $this->pdfSederhana('Profil Singkat Dinas Pariwisata Kabupaten Ciamis', 'Dokumen contoh hasil instalasi SIPARDIS.'));
            }
            Dokumen::create([
                'judul' => 'Profil Singkat Dinas Pariwisata (contoh)',
                'deskripsi' => 'Dokumen contoh untuk menguji modul dokumen & visibilitas.',
                'kategori' => 'Profil', 'visibility' => 'publik',
                'file_path' => $pdfPath, 'file_name' => 'profil-dinas.pdf',
                'file_size' => strlen($this->pdfSederhana('a', 'b')),
                'mime' => 'application/pdf',
                'uploaded_by' => $users['DUMMY-ADMIN']->id,
            ]);
        }

        // ----- notifikasi sambutan -----
        if (Notifikasi::count() === 0) {
            foreach (User::pluck('id') as $id) {
                Notifikasi::create([
                    'user_id' => $id,
                    'judul' => 'Selamat datang di SIPARDIS',
                    'isi' => 'Data PAD, kinerja fisik, dan 64 pegawai Dinas Pariwisata telah dimuat dari file resmi. Buka menu Kinerja untuk mengelolanya.',
                    'tipe' => 'info', 'url' => '/dashboard',
                ]);
            }
        }
    }

    private function pdfSederhana(string $judul, string $isi): string
    {
        $stream = "BT /F1 16 Tf 60 720 Td ($judul) Tj ET BT /F1 11 Tf 60 690 Td ($isi) Tj ET";
        $objs = [
            "<< /Type /Catalog /Pages 2 0 R >>",
            "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>",
            "<< /Length ".strlen($stream)." >>\nstream\n$stream\nendstream",
            "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
        ];
        $pdf = "%PDF-1.4\n"; $ofs = [];
        foreach ($objs as $i => $o) { $ofs[] = strlen($pdf); $pdf .= ($i + 1)." 0 obj\n$o\nendobj\n"; }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objs) + 1)."\n0000000000 65535 f \n";
        foreach ($ofs as $o) $pdf .= sprintf("%010d 00000 n \n", $o);
        $pdf .= "trailer\n<< /Size ".(count($objs) + 1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
        return $pdf;
    }
}

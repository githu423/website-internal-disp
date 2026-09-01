<?php

namespace Tests\Feature;

use App\Services\ImportService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportFileTest extends TestCase
{
    /** susun baris csv dari pasangan index => nilai agar kolom presisi */
    private function baris(array $cells, int $n = 31): string
    {
        $r = array_fill(0, $n, '');
        foreach ($cells as $i => $v) $r[$i] = $v;
        return implode(',', array_map(fn ($c) => str_contains($c, ',') ? '"' . $c . '"' : $c, $r));
    }

    private function csvKinerja(): string
    {
        $L = [];
        $L[] = $this->baris([0 => 'TAHUN 2025'], 22);
        $L[] = $this->baris([0 => 'NO', 1 => 'PROGRAM/KEGIATAN/SUB KEGIATAN', 4 => 'SUMBER DANA', 5 => 'PAGU ANGGARAN', 6 => 'Target', 7 => ' Satuan ', 20 => 'total capaian', 21 => '%'], 22);
        $L[] = $this->baris([1 => 'PROGRAM PENUNJANG URUSAN PEMERINTAH', 5 => '1,000,000,000', 21 => '100.00'], 22);
        $L[] = $this->baris([2 => 'Administrasi Umum Perangkat Daerah', 5 => '500,000,000'], 22);
        $L[] = $this->baris([3 => 'Penyediaan Bahan Logistik Kantor', 4 => 'DAU', 5 => '500,000,000', 6 => '24', 7 => 'Paket', 19 => '12', 20 => '24', 21 => '100.00'], 22);
        $L[] = $this->baris([0 => 'JUMLAH'], 22);
        return implode("\n", $L);
    }

    public function test_parse_kinerja_mengisi_capaian_dan_persen(): void
    {
        $this->buatKerangka();
        $rows = array_map(fn ($l) => str_getcsv($l), explode("\n", $this->csvKinerja()));
        $p = ImportService::parseKinerja($rows);
        $this->assertCount(3, $p['rows']);
        $sub = collect($p['rows'])->firstWhere('level', 'sub_kegiatan');
        $this->assertSame(2025, $sub['tahun']);
        $this->assertSame(24.0, $sub['target']);
        $this->assertSame('Paket', $sub['satuan']);
        $this->assertSame(24.0, $sub['capaian']);
        $this->assertSame(100.0, $sub['persentase']);
        $this->assertSame(1, $sub['id_bidang']); // PENUNJANG -> Sekretariat
    }

    public function test_import_kinerja_lewat_upload_admin(): void
    {
        $this->buatKerangka();
        $this->actingAs($this->userAdmin());
        $file = UploadedFile::fake()->createWithContent('kinerja.csv', $this->csvKinerja());
        $this->post('/admin/kinerja/import', ['file' => $file])->assertRedirect();
        $this->assertDatabaseHas('capaian_program', ['tahun' => 2025, 'nama' => 'Penyediaan Bahan Logistik Kantor', 'persentase' => 100]);
        $this->assertDatabaseHas('notifikasi', ['judul' => 'Data kinerja diperbarui']);
    }

    private function csvPad(): string
    {
        $L = [];
        $L[] = $this->baris([0 => 'TAHUN 2024'], 20);
        $L[] = $this->baris([0 => 'No.', 1 => 'No. Rekening', 2 => 'Uraian', 3 => 'Target T.A. 2024', 17 => 'Jumlah Realisasi', 18 => 'Sisa Target', 19 => 'Persentase (%)'], 20);
        $L[] = $this->baris([0 => 'A.', 1 => 'Retribusi Rekreasi dan Olah Raga'], 20);
        $L[] = $this->baris([0 => '1', 1 => '4.1.02.02.17.0001', 2 => 'OW. Situ Lengkong', 3 => '999,600,000', 5 => '40,443,000', 17 => '744,384,000', 18 => '-255,216,000', 19 => '74.47'], 20);
        $L[] = $this->baris([0 => 'Jumlah', 3 => '1,174,823,700'], 20);
        return implode("\n", $L);
    }

    public function test_parse_pad_dengan_angka_ribuan_dan_sisa_negatif(): void
    {
        $rows = array_map(fn ($l) => str_getcsv($l), explode("\n", $this->csvPad()));
        $p = ImportService::parsePad($rows);
        $this->assertCount(1, $p);
        $this->assertSame(999600000.0, $p[0]['target_tahunan']);
        $this->assertSame(744384000.0, $p[0]['kumulatif']);
        $this->assertSame(-255216000.0, $p[0]['sisa_target']);
        $this->assertSame(74.47, $p[0]['persentase']);
        $this->assertSame('Retribusi Rekreasi dan Olah Raga', $p[0]['kategori']);
        $this->assertSame(2024, $p[0]['tahun']);
    }

    public function test_import_pad_mengganti_tahun_yang_sama(): void
    {
        $this->buatKerangka();
        $this->actingAs($this->userAdmin());
        \App\Models\Pad::create(['tahun' => 2024, 'objek' => 'Lama', 'target_tahunan' => 100]);
        $L = [
            $this->baris([0 => 'TAHUN 2024'], 20),
            $this->baris([0 => 'No.'], 20),
            $this->baris([0 => 'A.', 1 => 'Fasum'], 20),
            $this->baris([0 => '1', 1 => '4.1', 2 => 'OW. Baru', 3 => '5,000,000', 17 => '4,000,000', 18 => '-1,000,000', 19 => '80'], 20),
        ];
        $file = UploadedFile::fake()->createWithContent('pad.csv', implode("\n", $L));
        $this->post('/admin/pad/import', ['file' => $file])->assertRedirect()->assertSessionHas('sukses');
        $this->assertDatabaseMissing('pad', ['objek' => 'Lama']);
        $this->assertDatabaseHas('pad', ['objek' => 'OW. Baru', 'persentase' => 80]);
    }

    public function test_file_undikenali_memberi_pesan_gagal(): void
    {
        $this->buatKerangka();
        $this->actingAs($this->userAdmin());
        $file = UploadedFile::fake()->createWithContent('acak.csv', "halo,dunia\n1,2\n");
        $this->post('/admin/pad/import', ['file' => $file])->assertSessionHas('gagal');
    }
}

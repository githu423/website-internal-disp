<?php

namespace Tests\Feature;

use App\Services\ImportService;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Tests\TestCase;

/**
 * Regresi 7 Sep 2026: import Excel (.xlsx) lewat form upload gagal dengan
 * "Tidak ada baris pegawai yang dikenali" padahal CSV bisa — karena file
 * sementara upload PHP bernama phpXXXX.tmp tanpa ekstensi, sehingga dibaca
 * sebagai CSV. Fix: deteksi jenis berkas dari isi file (magic bytes).
 */
class ImportExcelTest extends TestCase
{
    /** Susun .xlsx bergaya "template Dinas" milik user: header 2 baris + merge-ish. */
    private function xlsxPegawai(): string
    {
        $ss = new Spreadsheet();
        $sh = $ss->getActiveSheet();
        $sh->fromArray([
            ['No.', 'NAMA LENGKAP', 'N I P', 'TEMPAT LAHIR', 'TANGGAL LAHIR', 'PANGKAT/GOL. RUANG', 'TMT CPNS', 'PANGKAT/GOL. RUANG', 'ASN PNS/PPPK', 'TMT SEKARANG', 'JABATAN SEKARANG', 'TMT Jabatan', 'PENDIDIKAN UMUM DAN DIKLAT JABATAN TERAKHIR', '', '', '', 'JENIS KELAMIN', 'UNIT KERJA'],
            ['', '', '', '', '', '', '', '', '', '', '', '', 'PENDIDIKAN UMUM', 'DIKLAT STRUKTURAL', 'DIKLAT FUNGSIONAL', 'DIKLAT TEKNIS', '', ''],
        ], null, 'A1');
        // baris 3: tanggal serial Excel (kolom TMT CPNS) + teks dd/mm/yyyy (tanggal lahir)
        $sh->fromArray([
            ['1', 'UJI XLSU SATU', '19750211 1996011001', 'CIAMIS', '11/02/1975', 'PENGATUR MUDA (II/a)', Date::PHPToExcel('1996-10-01'), 'PENATA (III/c)', 'PNS', '01/10/2023', 'KEPALA SUB BIDANG DESTINASI', '01/10/2023', 'S-2 Manajemen', 'Diklat PIM Tk. III 2021', '', '', 'Laki-laki', 'Destinasi'],
            ['2', 'UJI XLSU DUA', '19780723 2003121004', 'KAB. CIAMIS', '23/07/1978', 'PENGATUR (II/c)', Date::PHPToExcel('2003-12-01'), 'PEMBINA (IV/a)', 'PNS', Date::PHPToExcel('2025-08-23'), 'STAF SEKRETARIAT', '', 'S-2', 'Prajabatan 2004', '', '', 'Perempuan', 'Sekretariat'],
        ], null, 'A3');
        $tmp = tempnam(sys_get_temp_dir(), 'xlsxfixture') . '.xlsx';
        (new XlsxWriter($ss))->save($tmp);
        $ss->disconnectWorksheets();
        return $tmp;
    }

    public function test_rows_from_mengenali_xlsx_tanpa_ekstensi_benar(): void
    {
        $this->buatKerangka();
        $xlsx = $this->xlsxPegawai();
        // persis kondisi upload PHP: file temp bernama "phpXXXX" TANPA ekstensi, tanpa hints nama
        $tanpaExt = dirname($xlsx) . '/php' . uniqid();
        rename($xlsx, $tanpaExt);
        try {
            $rows = ImportService::rowsFrom($tanpaExt);
            $p = ImportService::parsePegawai($rows);
            $this->assertCount(2, $p, 'baris xlsx harus terdeteksi walau berkas tanpa ekstensi .xlsx');

            $satu = $p[0];
            $this->assertSame('197502111996011001', $satu['nip']);
            $this->assertSame('UJI XLSU SATU', $satu['nama']);
            $this->assertSame('1975-02-11', $satu['tanggal_lahir'], 'tanggal teks dd/mm/yyyy');
            $this->assertSame('1996-10-01', $satu['tmt_cpns'], 'tanggal serial Excel harus dikonversi');
            $this->assertSame('2023-10-01', $satu['tmt_sekarang']);
            $this->assertSame('PENATA (III/c)', $satu['pangkat_golongan'], 'pakai pangkat TERAKHIR (kolom H), bukan awal');
            $this->assertSame('PNS', $satu['jenis_asn']);
            $this->assertSame('PENGATUR MUDA (II/a)', $satu['pangkat_awal'], 'kolom PANGKAT pertama = pangkat AWAL');
            $this->assertSame('Diklat PIM Tk. III 2021', $satu['diklat_struktural']);
            $this->assertNull($satu['diklat_fungsional']);
            $this->assertSame('L', $satu['jenis_kelamin']);
            $this->assertSame('Destinasi', $satu['unit_kerja'], 'UNIT KERJA di kolom R (17) terdeteksi dari header');
            $this->assertSame('S-2', $satu['pendidikan']);

            $dua = $p[1];
            $this->assertSame('P', $dua['jenis_kelamin']);
            $this->assertSame('2025-08-23', $dua['tmt_sekarang']);
            $this->assertSame('Sekretariat', $dua['unit_kerja']);
        } finally {
            @unlink($tanpaExt);
        }
    }

    public function test_upload_xlsx_lewat_form_admin_memperbarui_data(): void
    {
        $this->buatKerangka();
        // pegawai lama dengan NIP sama -> harus DIPERBARUI, bukan dobel
        \App\Models\Pegawai::create(['nip' => '197502111996011001', 'nama' => 'UJI LAMA SATU', 'status' => 'aktif']);
        $xlsx = $this->xlsxPegawai();
        $this->actingAs($this->userAdmin());
        $file = UploadedFile::fake()->createWithContent('daftar pegawai.xlsx', file_get_contents($xlsx));
        @unlink($xlsx);
        $this->post('/admin/pegawai/import', ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('sukses', fn ($v) => str_contains($v, '1 pegawai baru') && str_contains($v, '1 diperbarui'));

        $baru = \App\Models\Pegawai::where('nip', '197502111996011001')->firstOrFail();
        $this->assertSame('UJI XLSU SATU', $baru->nama);
        $this->assertSame('1996-10-01', $baru->tmt_cpns?->format('Y-m-d'), 'serial Excel tersimpan sebagai tanggal asli');
        $this->assertSame('Destinasi', $baru->unit_kerja);
        $this->assertDatabaseHas('pegawai', ['nip' => '197807232003121004', 'nama' => 'UJI XLSU DUA', 'jenis_kelamin' => 'P']);
        $this->assertSame(1, \App\Models\Pegawai::where('nip', '197502111996011001')->count(), 'NIP sama tidak boleh dobel');
    }

    public function test_csv_tetap_berfungsi(): void
    {
        $this->buatKerangka();
        $csv = "No.,NAMA LENGKAP,N I P,TEMPAT LAHIR,TANGGAL LAHIR,PANGKAT/GOL. RUANG,TMT CPNS,PANGKAT/GOL. RUANG,ASN PNS/PPPK,TMT SEKARANG,JABATAN SEKARANG,TMT Jabatan,PENDIDIKAN UMUM,DIKLAT STRUKTURAL,DIKLAT FUNGSIONAL,DIKLAT TEKNIS,JENIS KELAMIN,UNIT KERJA\n"
            . "1,UJI CSV SATU,198001012000011001,BANJAR,01/01/1980,PENGATUR (II/c),01/01/2000,PENGATUR (II/c),PNS,,STAF BIDANG,,,,,Laki-laki,Pemasaran\n";
        $this->actingAs($this->userAdmin());
        $file = UploadedFile::fake()->createWithContent('daftar.csv', $csv);
        $this->post('/admin/pegawai/import', ['file' => $file])
            ->assertRedirect()->assertSessionHas('sukses');
        $row = \App\Models\Pegawai::where('nip', '198001012000011001')->firstOrFail();
        $this->assertSame('UJI CSV SATU', $row->nama);
        $this->assertSame('2000-01-01', $row->tmt_cpns->format('Y-m-d'));
    }

    public function test_kinerja_xlsx_tanpa_ekstensi_pun_terbaca(): void
    {
        // jalur umum rowsFrom dipakai juga import Kinerja/PAD — pastikan tidak regresi
        $this->buatKerangka();
        $xlsx = $this->xlsxPegawai();
        $tanpaExt = dirname($xlsx) . '/php' . uniqid();
        rename($xlsx, $tanpaExt);
        try {
            $rows = ImportService::rowsFrom($tanpaExt, 'bebas-nama.csv'); // nama menyesatkan: isi tetap menang
            $this->assertNotEmpty($rows);
            $this->assertSame('UJI XLSU SATU', trim($rows[2][1]));
        } finally {
            @unlink($tanpaExt);
        }
    }
}

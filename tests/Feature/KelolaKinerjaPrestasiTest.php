<?php

namespace Tests\Feature;

use App\Models\CapaianProgram;
use App\Models\Prestasi;
use App\Services\ImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Tests\TestCase;

/**
 * Regresi 9 Sep 2026 — laporan admin:
 *  1. Update data kinerja dari modal edit selalu gagal tanpa pesan (validasi menuntut
 *     kolom "tahun" & "level" yang tidak pernah dikirim form lama).
 *  2. Import file Excel kinerja "tidak dikenali" walau kolomnya sama — penyebab:
 *     baris "TAHUN <tahun>" di Excel rata tengah/merged (tidak di kolom A) dan/atau
 *     ditulis "TAHUN ANGGARAN <tahun>", plus sheet pertama bisa berupa sampul kosong.
 *  3. Prestasi tidak bisa ditambah satu per satu (hanya import), dan tampilan import
 *     tidak seragam dengan halaman kelola lain.
 */
class KelolaKinerjaPrestasiTest extends TestCase
{
    private function kegiatanContoh(): CapaianProgram
    {
        return CapaianProgram::create([
            'tahun' => 2026, 'level' => 'sub_kegiatan', 'nama' => 'Promosi Destinasi Ciamis',
            'sumber_dana' => 'DAU', 'pagu_anggaran' => 5000000, 'indikator' => 'Jumlah kegiatan',
            'target' => 12, 'satuan' => 'Kegiatan', 'capaian' => 9, 'persentase' => 75.00,
            'catatan' => null, 'id_bidang' => 2, 'urutan' => 1,
        ]);
    }

    /** 1a. Form edit lama (tanpa tahun & level) harusnya tetap bisa menyimpan. */
    public function test_update_kinerja_tanpa_kolom_tahun_level_tetap_berhasil(): void
    {
        $this->buatKerangka();
        $admin = $this->userAdmin();
        $k = $this->kegiatanContoh();

        // persis payload form edit lama: TANPA tahun, level, sumber_dana, indikator, id_bidang
        $this->actingAs($admin)->patch(route('admin.kinerja.update', $k), [
            'nama' => 'Promosi Destinasi Ciamis 2026',
            'target' => 12, 'satuan' => 'Kegiatan', 'capaian' => 11,
            'persentase' => 91.67, 'pagu_anggaran' => 6000000, 'catatan' => 'Dipercepat',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $k->refresh();
        $this->assertSame('Promosi Destinasi Ciamis 2026', $k->nama, 'nama harus ikut terubah');
        $this->assertSame(2026, (int) $k->tahun, 'tahun lama dipertahankan saat form tidak mengirimkannya');
        $this->assertSame('sub_kegiatan', $k->level, 'level lama dipertahankan');
        $this->assertSame('DAU', $k->sumber_dana);
        $this->assertEquals(11, (float) $k->capaian);
        $this->assertEquals(91.67, (float) $k->persentase);
    }

    /** 1b. Form edit lengkap (modal baru) bisa mengubah level, tahun, dan bidang. */
    public function test_update_kinerja_form_lengkap(): void
    {
        $this->buatKerangka();
        $admin = $this->userAdmin();
        $k = $this->kegiatanContoh();

        $this->actingAs($admin)->patch(route('admin.kinerja.update', $k), [
            'nama' => 'Promosi Wisata Panjalu', 'tahun' => 2025, 'level' => 'kegiatan',
            'sumber_dana' => 'DBH', 'pagu_anggaran' => 1000, 'indikator' => 'Indikator baru',
            'target' => 5, 'satuan' => 'Lokasi', 'capaian' => 5, 'persentase' => 100,
            'catatan' => 'Selesai', 'id_bidang' => 3,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $k->refresh();
        $this->assertSame('kegiatan', $k->level);
        $this->assertSame(2025, (int) $k->tahun);
        $this->assertSame(3, (int) $k->id_bidang);
    }

    /**
     * 2. Susun .xlsx bergaya file Excel dinas: sheet pertama sampul kosong, baris
     * "TAHUN ANGGARAN 2026" rata tengah (tidak di kolom A), lalu data kinerja normal.
     */
    private function xlsxKinerja(): string
    {
        $ss = new Spreadsheet();
        $ss->createSheet(); // sheet 0 tetap kosong sebagai "sampul"

        $sh = $ss->getSheet(1);
        $sh->setTitle('KINERJA');
        // baris 1: judul tahun rata tengah (merged/center) — TIDAK di kolom A
        $sh->fromArray([['', '', 'TAHUN ANGGARAN 2026']], null, 'A1');
        // baris 2: header ala file dinas
        $sh->fromArray([['NO', 'PROGRAM/KEGIATAN/SUB KEGIATAN', '', '', 'SUMBER DANA', 'PAGU ANGGARAN', 'Target', 'Satuan', 'Capaian', '', '', '', '', '', '', '', '', '', '', '', 'total capaian', '%']], null, 'A2');
        // baris 3: bulan
        $sh->fromArray([['', '', '', '', '', '', '', '', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agst', 'Sept', 'Okt', 'Nop', 'Des']], null, 'A3');
        // baris data
        $sh->fromArray([
            ['', 'PROGRAM PROMOSI PARIWISATA', '', '', '', 9000000, '', '', '', '', '', '', '', '', '', '', '', '', '', '', 90, 90],
            ['', '', 'Pemasaran Pariwisata', '', '', 8000000, '', '', '', '', '', '', '', '', '', '', '', '', '', '', 80, 80],
            ['', '', '', 'Penyediaan Data dan Informasi Pariwisata', 'DAU', 1000000, 10, 'Dok.', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 9, 90],
        ], null, 'A4');

        $tmp = tempnam(sys_get_temp_dir(), 'xlsxkinerja') . '.xlsx';
        (new XlsxWriter($ss))->save($tmp);
        $ss->disconnectWorksheets();

        return $tmp;
    }

    public function test_parse_kinerja_xlsx_tahun_rata_tengah_dan_sheet_sampul(): void
    {
        $this->buatKerangka();
        $rows = ImportService::rowsFrom($this->xlsxKinerja(), 'Kinerja Fisik Sedinas.xlsx');
        $p = ImportService::parseKinerja($rows);

        $this->assertSame([2026], $p['years'], 'baris "TAHUN ANGGARAN 2026" (rata tengah) harus terbaca');
        $this->assertCount(3, $p['rows'], 'program + kegiatan + sub kegiatan harus terdeteksi');
        $this->assertSame('PROGRAM PROMOSI PARIWISATA', $p['rows'][0]['nama']);
        $this->assertSame('sub_kegiatan', $p['rows'][2]['level']);
        $this->assertEquals(9, (float) $p['rows'][2]['capaian']);
        $this->assertEquals(90, (float) $p['rows'][2]['persentase']);
    }

    public function test_import_kinerja_xlsx_lewat_form_admin_berhasil(): void
    {
        $this->buatKerangka();
        $admin = $this->userAdmin();

        $this->actingAs($admin)
            ->post(route('admin.kinerja.import'), [
                'file' => new UploadedFile($this->xlsxKinerja(), 'Kinerja Fisik Sedinas.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
                'ganti_tahun' => '1',
            ])
            ->assertRedirect()->assertSessionHasNoErrors()
            ->assertSessionHas('sukses');

        $this->assertSame(3, CapaianProgram::where('tahun', 2026)->count(), '3 baris kinerja harus tersimpan');
    }

    /** 3a. Tambah satu prestasi manual. */
    public function test_tambah_prestasi_manual(): void
    {
        $this->buatKerangka();
        $admin = $this->userAdmin();

        $this->actingAs($admin)
            ->post(route('admin.prestasi.store'), [
                'nama_penghargaan' => 'Juara 1 Festival Pantai',
                'nama_penerima' => 'Bidang Destinasi',
                'pemberi_penghargaan' => 'Gubernur Jawa Barat',
                'tahun' => 2026,
                'tingkat' => 'Provinsi',
            ])
            ->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('sukses');

        $this->assertSame(1, Prestasi::count());
        $p = Prestasi::first();
        $this->assertSame('Juara 1 Festival Pantai', $p->nama_penghargaan);
        $this->assertSame(2026, (int) $p->tahun);
    }

    /** 3b. Validasi tambah prestasi menolak isian kosong (dan pesannya sampai ke layar). */
    public function test_tambah_prestasi_kosong_ditolak(): void
    {
        $this->buatKerangka();
        $admin = $this->userAdmin();

        $this->actingAs($admin)
            ->post(route('admin.prestasi.store'), ['nama_penghargaan' => '', 'tahun' => ''])
            ->assertRedirect()
            ->assertSessionHasErrors(['nama_penghargaan', 'tahun']);
        $this->assertSame(0, Prestasi::count());
    }

    /** 3c. Edit prestasi manual. */
    public function test_edit_prestasi_manual(): void
    {
        $this->buatKerangka();
        $admin = $this->userAdmin();
        $p = Prestasi::create(['nama_penghargaan' => 'Juara 2 Lomba Dokumentasi', 'tahun' => 2025]);

        $this->actingAs($admin)
            ->patch(route('admin.prestasi.update', $p), [
                'nama_penghargaan' => 'Juara 1 Lomba Dokumentasi', 'nama_penerima' => 'Tim Media',
                'pemberi_penghargaan' => 'Kemenparekraf', 'tahun' => 2025, 'tingkat' => 'Nasional',
            ])
            ->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('sukses');

        $p->refresh();
        $this->assertSame('Juara 1 Lomba Dokumentasi', $p->nama_penghargaan);
        $this->assertSame('Nasional', $p->tingkat);
    }

    /** Halaman kelola kinerja & prestasi tetap render dengan markup baru. */
    public function test_halaman_kelola_render(): void
    {
        $this->buatKerangka();
        $admin = $this->userAdmin();

        $this->actingAs($admin)->get(route('admin.kinerja.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.prestasi.index'))->assertOk();
    }

    protected function tearDown(): void
    {
        File::delete(glob(sys_get_temp_dir() . '/xlsxkinerja*') ?? []);
        parent::tearDown();
    }
}

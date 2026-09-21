<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\KategoriDokumen;
use App\Services\KategoriDokumenService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Kategori dokumen bertingkat yang dipakai bersama semua dokumen.
 * Status akses (publik / privat bidang / rahasia) tetap pada tiap dokumen.
 */
class KategoriDokumenTest extends TestCase
{
    public function test_kesepakatan_kategori_bersama(): void
    {
        $this->buatKerangka();
        $k = KategoriDokumenService::dapatkanAtauBuat('Laporan');
        $this->assertSame('Laporan', $k->nama);

        // nama sama → tidak membuat duplikat
        $k2 = KategoriDokumenService::dapatkanAtauBuat('Laporan');
        $this->assertSame($k->id, $k2->id);
        $this->assertSame(1, KategoriDokumen::where('nama', 'Laporan')->count());
    }

    public function test_berjenjang_induk_anak_tanpa_duplikat(): void
    {
        $this->buatKerangka();
        $daun = KategoriDokumenService::dapatkanAtauBuatBerjenjang('Laporan / Tahun 2026 / Triwulan I');

        $this->assertSame('Triwulan I', $daun->nama);
        $this->assertNotNull($daun->induk);
        $this->assertSame('Tahun 2026', $daun->induk->nama);
        $this->assertNotNull($daun->induk->induk);
        $this->assertSame('Laporan', $daun->induk->induk->nama);

        // membangun ulang jejak yang sama tidak membuat duplikat
        $daun2 = KategoriDokumenService::dapatkanAtauBuatBerjenjang('Laporan / Tahun 2026 / Triwulan I');
        $this->assertSame($daun->id, $daun2->id);
        $this->assertSame(1, KategoriDokumen::where('nama', 'Triwulan I')->count());
        $this->assertSame(1, KategoriDokumen::where('nama', 'Tahun 2026')->count());
    }

    public function test_dokumen_publik_dan_rahasia_berbagi_kategori(): void
    {
        $this->buatKerangka();
        $k = KategoriDokumenService::dapatkanAtauBuat('Nota Dinas');
        $pemilikA = $this->userPegawai(2, 'KD-A');
        $pemilikB = $this->userPegawai(3, 'KD-B');

        $d1 = Dokumen::create([
            'judul' => 'Nota Publik', 'visibility' => 'publik', 'id_kategori' => $k->id,
            'file_path' => 'dokumen/x', 'file_name' => 'x.txt', 'file_size' => 1,
            'mime' => 'text/plain', 'uploaded_by' => $pemilikA->id,
        ]);
        $d2 = Dokumen::create([
            'judul' => 'Nota Rahasia', 'visibility' => 'rahasia', 'id_kategori' => $k->id,
            'file_path' => 'dokumen/y', 'file_name' => 'y.txt', 'file_size' => 1,
            'mime' => 'text/plain', 'uploaded_by' => $pemilikB->id,
        ]);

        $this->assertSame($k->id, (int) $d1->id_kategori);
        $this->assertSame($k->id, (int) $d2->id_kategori);

        // status akses tetap per dokumen, bukan per kategori
        $admin = $this->userAdmin();
        $this->actingAs($admin)->get('/dokumen')->assertOk()
            ->assertSee('Nota Publik')
            ->assertSee('Nota Rahasia');
    }

    public function test_ungah_universal_menyimpan_kategori_dan_memakai_kembali(): void
    {
        $this->buatKerangka();
        $peg = $this->userPegawai(2, 'KD-U1');
        $peg->update(['role' => 'bidang']);

        $file = UploadedFile::fake()->createWithContent('laporan.txt', 'isi laporan');
        $this->actingAs($peg)->post('/dokumen', [
            'judul' => 'Laporan Uji', 'visibility' => 'publik',
            'kategori' => 'Laporan / Tahun 2026 / Triwulan II',
            'file' => $file,
        ])->assertRedirect();

        $d = Dokumen::where('judul', 'Laporan Uji')->firstOrFail();
        $this->assertNotNull($d->id_kategori);
        $this->assertSame('Triwulan II', $d->kategoriRel->nama);
        $this->assertSame('Tahun 2026', $d->kategoriRel->induk->nama);
        $this->assertSame('Laporan', $d->kategoriRel->induk->induk->nama);

        // unggahan berikutnya dengan kategori sama memakai kategori yang sama
        $file2 = UploadedFile::fake()->createWithContent('laporan2.txt', 'isi laporan 2');
        $this->actingAs($peg)->post('/dokumen', [
            'judul' => 'Laporan Uji 2', 'visibility' => 'publik',
            'kategori' => 'Laporan / Tahun 2026 / Triwulan II',
            'file' => $file2,
        ])->assertRedirect();

        $d2 = Dokumen::where('judul', 'Laporan Uji 2')->firstOrFail();
        $this->assertSame((int) $d->id_kategori, (int) $d2->id_kategori);
    }

    public function test_ungkan_bidang_default_arsip_bidang(): void
    {
        $this->buatKerangka();
        $staf = $this->userPegawai(2, 'KD-BD1');
        $file = UploadedFile::fake()->createWithContent('notula.txt', 'isi notula');
        $this->actingAs($staf)->post(route('bidang.dokumen', 2), ['judul' => 'Notula Kategori', 'file' => $file])
            ->assertRedirect()->assertSessionHas('sukses');

        $d = Dokumen::where('judul', 'Notula Kategori')->firstOrFail();
        $this->assertSame('bidang', $d->visibility);
        $this->assertNotNull($d->id_kategori);
        $this->assertSame('Arsip Bidang', $d->kategoriRel->nama);
    }

    public function test_label_kategori_berjenjang(): void
    {
        $this->buatKerangka();
        $daun = KategoriDokumenService::dapatkanAtauBuatBerjenjang('SK / 2026 / Penetapan');
        $d = Dokumen::create([
            'judul' => 'Doc SK', 'visibility' => 'publik', 'id_kategori' => $daun->id,
            'file_path' => 'dokumen/sk', 'file_name' => 'sk.txt', 'file_size' => 1,
            'mime' => 'text/plain', 'uploaded_by' => $this->userAdmin()->id,
        ]);
        $this->assertSame('SK / 2026 / Penetapan', $d->kategori_label);
    }

    public function test_filter_kategori_mencakup_subkategori(): void
    {
        $this->buatKerangka();
        $admin = $this->userAdmin();
        $induk = KategoriDokumenService::dapatkanAtauBuatBerjenjang('Laporan / Tahun 2026 / Triwulan I');
        $indukRoot = $induk->induk->induk; // 'Laporan'

        Dokumen::create([
            'judul' => 'Lap TW I', 'visibility' => 'publik', 'id_kategori' => $induk->id,
            'file_path' => 'dokumen/a', 'file_name' => 'a.txt', 'file_size' => 1,
            'mime' => 'text/plain', 'uploaded_by' => $admin->id,
        ]);

        // filter pada akar 'Laporan' ikut menampilkan dokumen subkategori
        $this->actingAs($admin)->get('/dokumen?kategori=' . $indukRoot->id)->assertOk()->assertSee('Lap TW I');
        // filter pada daun 'Triwulan I' juga menemukannya
        $this->actingAs($admin)->get('/dokumen?kategori=' . $induk->id)->assertOk()->assertSee('Lap TW I');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Bidang;
use App\Models\CapaianIndikator;
use App\Models\CapaianSasaran;
use App\Models\Dokumen;
use App\Models\Pegawai;
use App\Services\ImportService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** Round 11 (notulen Ricky 2 Sep): agenda utk semua, role UPTD, badge bidang di capaian, preview office. */
class UpdateRickyTest extends TestCase
{
    public function test_agenda_dinas_hanya_admin_yang_bisa_update(): void
    {
        $this->buatKerangka();
        // pegawai & bidang TIDAK boleh menambah/mengubah agenda dinas lagi
        foreach (['pegawai', 'bidang'] as $r) {
            $u = $this->userPegawai(1);
            $u->update(['role' => $r]);
            $this->actingAs($u)->post(route('agenda.store'), ['judul' => 'X', 'tanggal' => '2026-09-10'])->assertForbidden();
        }
        $admin = $this->userAdmin();
        $a = Agenda::create(['judul' => 'Rapat Dinas', 'tanggal' => '2026-09-10', 'status' => 'belum', 'created_by' => $admin->id]);
        $staf = $this->userPegawai(2);
        $this->actingAs($staf)->patch(route('agenda.status', $a))->assertForbidden();
        // admin boleh semuanya
        $this->actingAs($admin)->post(route('agenda.store'), ['judul' => 'Agenda Admin', 'tanggal' => '2026-09-11'])->assertRedirect();
        $this->actingAs($admin)->patch(route('agenda.status', $a))->assertRedirect();
        $this->assertDatabaseHas('agenda', ['judul' => 'Agenda Admin']);
        $this->assertSame('selesai', $a->fresh()->status);
    }

    public function test_anggota_bidang_bisa_tambah_agenda_bidang_lewat_halaman_bidang(): void
    {
        $this->buatKerangka();
        $anggota = $this->userPegawai(2);
        $this->actingAs($anggota)->post(route('bidang.agenda', 2), [
            'judul' => 'Kunjungan Desa Wisata Uji', 'tanggal' => '2026-09-15', 'jam_mulai' => '09:00', 'lokasi' => 'Panjalu',
        ])->assertRedirect()->assertSessionHas('sukses');
        $this->assertDatabaseHas('agenda', ['judul' => 'Kunjungan Desa Wisata Uji', 'id_bidang' => 2]);

        // agenda tampil di halaman bidangnya, lengkap dengan jam & lokasi
        $this->actingAs($anggota)->get('/bidang/2')->assertOk()->assertSee('Kunjungan Desa Wisata Uji')->assertSee('Panjalu');

        // orang bidang lain tidak boleh menambah ke bidang 2
        $asing = $this->userPegawai(3);
        $this->actingAs($asing)->post(route('bidang.agenda', 2), ['judul' => 'Nope', 'tanggal' => '2026-09-15'])->assertForbidden();

        // dan menu/form agenda tampil untuk SEMUA user di sidebar Utama (cek route dulu — semua user bisa lihat halaman)
        $this->actingAs($asing)->get('/agenda')->assertOk();
    }

    public function test_uptd_jadi_bidang_dan_tertebak_dari_jabatan(): void
    {
        $ini = Bidang::pastikanUptd();
        $this->assertSame(2, $ini, 'helper menambah dua UPTD saat belum ada');
        $this->assertSame(0, Bidang::pastikanUptd(), 'idempoten — pemanggilan kedua tidak menambah apa pun');
        $this->assertDatabaseHas('bidang', ['nama' => 'UPTD CIAMIS']);
        $this->assertDatabaseHas('bidang', ['nama' => 'UPTD KAWALI']);

        $kawali = Bidang::where('nama', 'UPTD KAWALI')->first();
        $tebak = Bidang::tebakDariTeks('Kepala UPTD Pengelolaan Objek Wisata Wilayah Kawali Pada UPTD Pariwisata');
        $this->assertSame((int) $kawali->id, (int) $tebak, 'jabatan Kepala UPTD Kawali harus terikat ke bidang UPTD KAWALI');

        // teks dengan "UPTD" tapi tanpa nama wilayah → tidak memaksa kecocokan
        $this->assertNotSame((int) $kawali->id, (int) Bidang::tebakDariTeks('Staf Tata Usaha UPTD'), 'tanpa wilayah tidak boleh tertebak ke UPTD tertentu');
    }

    public function test_sasaran_kinerja_punya_bidang_dan_badge_tampil_publik(): void
    {
        $this->buatKerangka();
        $admin = $this->userAdmin();
        $bId = Bidang::where('nama', 'BIDANG PEMASARAN')->value('id');

        // admin tambah sasaran dengan bidang eksplisit
        $this->actingAs($admin)->post(route('admin.capaian.sasaran.store'), [
            'nomor' => 1, 'judul' => 'Meningkatnya Kunjungan', 'id_bidang' => $bId,
        ])->assertRedirect();
        // sasaran tanpa bidang → otomatis ditebak dari judul; kalau tak kena = Lintas Dinas
        $this->actingAs($admin)->post(route('admin.capaian.sasaran.store'), [
            'nomor' => 2, 'judul' => 'Sasaran Umum Dinas',
        ])->assertRedirect();

        CapaianIndikator::create(['sasaran_id' => CapaianSasaran::where('nomor', 1)->value('id'), 'nama' => 'Indikator A', 'satuan' => 'orang', 'urut' => 1]);

        // tampilan Matriks & Statistik dua-duanya menampilkan badge
        $this->actingAs($admin)->get('/kinerja/capaian')->assertOk()->assertSee('BIDANG PEMASARAN')->assertSee('LINTAS DINAS');
        $this->actingAs($admin)->get('/kinerja/capaian?tampilan=statistik')->assertOk()
            ->assertSee('BIDANG PEMASARAN')->assertSee('data-chart', false);

        // form admin punya dropdown bidang
        $this->actingAs($admin)->get('/admin/capaian-kinerja')->assertOk()->assertSee('Lintas Dinas / tidak spesifik');
    }

    public function test_pegawai_uptd_terkelompok_otomatis_di_bidangnya(): void
    {
        $this->buatKerangka();
        Bidang::pastikanUptd();
        $kawali = Bidang::where('nama', 'UPTD KAWALI')->firstOrFail();
        Pegawai::create([
            'nip' => '198101012006041002', 'nama' => 'PEGAWAI UPTD KAWALI',
            'jabatan' => 'Kepala UPTD Pengelolaan Objek Wisata Wilayah Kawali', 'status' => 'aktif',
        ]);

        // fallback tampilan: langsung tampil walau id_bidang belum diisi — bahkan saat rekan sebidangnya sudah tertaut
        Pegawai::create([
            'nip' => '198101012006041003', 'nama' => 'PEGAWAI KAWALI TERTAUT',
            'jabatan' => 'Staf UPTD Pengelolaan Objek Wisata Wilayah Kawali', 'status' => 'aktif', 'id_bidang' => $kawali->id,
        ]);
        $this->actingAs($this->userAdmin())->get('/bidang/'.$kawali->id)
            ->assertOk()->assertSee('PEGAWAI UPTD KAWALI')->assertSee('PEGAWAI KAWALI TERTAUT');

        // sinkron mengisi database secara permanen lalu idempoten
        $n = ImportService::sinkronBidangPegawai();
        $this->assertGreaterThan(0, $n);
        $this->assertSame((int) $kawali->id, (int) Pegawai::where('nip', '198101012006041002')->value('id_bidang'));
        $this->assertSame(0, ImportService::sinkronBidangPegawai(), 'jalankan kedua kali tidak mengubah apa pun');
    }

    public function test_js_nama_file_tidak_memutus_input_dari_form(): void
    {
        // regresi v8: input file pernah "dipindah" ke wadah lepas sehingga tombol unggah hilang total
        $js = file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString("insertAdjacentElement('afterend', lbl)", $js);
        $this->assertStringNotContainsString('wrap.appendChild(f)', $js, 'input file tidak boleh dipindah keluar DOM');
        $this->assertStringNotContainsString('::file-selector-button', $js, 'gaya tombol serahkan ke class file: Tailwind');
        $this->assertStringContainsString('input.fname-own{color:transparent}', $js);
        $this->assertStringContainsString("import './office-preview'", $js);
        $this->assertStringContainsString("import('docx-preview')", file_get_contents(resource_path('js/office-preview.js')));
    }

    public function test_tata_letak_mobile_dan_pencarian_dokumen_di_halaman_bidang(): void
    {
        $this->buatKerangka();
        $u = $this->userAdmin();
        $file = UploadedFile::fake()->createWithContent('renja.docx', 'isi');
        $this->actingAs($u)->post(route('bidang.dokumen', 2), ['judul' => 'Rencana Kerja Bidang', 'file' => $file])->assertRedirect();

        $html = $this->actingAs($u)->get('/bidang/2')->assertOk()->getContent();
        // mobile-first: aturan order dipasang inline (mandiri, tanpa build Tailwind) — agenda/dokumen di atas pegawai
        $this->assertStringContainsString('.bidang-grid > .bidang-aksi { order: 1; }', $html);
        $this->assertStringContainsString('.bidang-grid > .bidang-pegawai { order: 2; }', $html);
        $this->assertStringContainsString('@media (max-width: 1023px)', $html);
        // kelas penanda terpasang pada kartu yang benar
        $this->assertMatchesRegularExpression('/<div class="card overflow-hidden lg:col-span-2 bidang-pegawai">/', $html);
        $this->assertMatchesRegularExpression('/<div class="space-y-4 bidang-aksi">.*Agenda Bidang/s', $html);
        // kolom pencarian + atribut baris dokumen
        $this->assertStringContainsString('data-doc-q', $html);
        $this->assertStringContainsString('data-doc-text="rencana kerja bidang ', $html);
        // skrip pencarian inline ikut ter-render
        $this->assertStringContainsString('addEventListener(\'input\',upd)', $html);
    }

    public function test_pratinjau_halaman_dokumen_office_tersedia(): void
    {
        $this->buatKerangka();
        $u = $this->userAdmin();
        $file = UploadedFile::fake()->createWithContent('laporan.xlsx', 'isi');
        $path = $file->storeAs('dokumen', 'uji-xlsx.xlsx', 'public');
        $d = Dokumen::create([
            'judul' => 'Laporan XLSX', 'visibility' => 'publik', 'file_path' => $path,
            'file_name' => 'laporan.xlsx', 'file_size' => 10, 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'uploaded_by' => $u->id,
        ]);
        // halaman buka = 200 (host #office-host dirender utk xlsx; render tabel terjadi di browser)
        $this->actingAs($u)->get(route('dokumen.show', $d))->assertOk()->assertSee('office-host', false)->assertSee('data-kind="xlsx"', false);

        // Word: host dirender dengan data-kind docx (docx-preview bekerja di browser)
        $docx = UploadedFile::fake()->createWithContent('proposal.docx', 'isi');
        $path2 = $docx->storeAs('dokumen', 'uji-docx.docx', 'public');
        $d2 = Dokumen::create([
            'judul' => 'Proposal Docx', 'visibility' => 'publik', 'file_path' => $path2,
            'file_name' => 'proposal.docx', 'file_size' => 10, 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'uploaded_by' => $u->id,
        ]);
        $this->actingAs($u)->get(route('dokumen.show', $d2))->assertOk()->assertSee('data-kind="docx"', false);
    }
}

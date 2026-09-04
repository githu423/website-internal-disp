<?php

namespace Tests\Feature;

use App\Models\CapaianBulanan;
use App\Models\Dokumen;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** Round 9: statistik bisa dikelola & filter tahun nyata; halaman bidang untuk semua + arsip privat. */
class BidangStatistikTest extends TestCase
{
    public function test_grafik_bulanan_ikut_tahun_terpilih(): void
    {
        $this->buatKerangka();
        $admin = $this->userAdmin();
        CapaianBulanan::create(['tahun' => 2025, 'bulan' => 1, 'target' => 20, 'capaian' => 10]);
        CapaianBulanan::create(['tahun' => 2026, 'bulan' => 1, 'target' => 100, 'capaian' => 90]);

        $chartDi = function (string $html): array {
            preg_match("/data-chart='([^']+)'/", $html, $m);
            return json_decode($m[1] ?? 'null', true) ?? [];
        };
        $c25 = $chartDi($this->actingAs($admin)->get('/statistik?tahun=2025')->assertOk()->getContent());
        $c26 = $chartDi($this->actingAs($admin)->get('/statistik?tahun=2026')->assertOk()->getContent());
        $this->assertSame(10.0, (float) ($c25['capaian'][0] ?? -1), 'Januari 2025 harus 10');
        $this->assertSame(90.0, (float) ($c26['capaian'][0] ?? -1), 'Januari 2026 harus 90 — bukan data tahun lain');
    }

    public function test_kelola_statistik_manual_dan_impor(): void
    {
        $this->buatKerangka();
        $admin = $this->userAdmin();

        $nilai = [];
        for ($b = 1; $b <= 12; $b++) $nilai[$b] = ['target' => '', 'capaian' => ''];
        $nilai[3] = ['target' => '5', 'capaian' => '6'];
        $this->actingAs($admin)->post(route('admin.statistik.store'), ['tahun' => 2027, 'nilai' => $nilai])->assertRedirect();
        $this->assertDatabaseHas('capaian_bulanan', ['tahun' => 2027, 'bulan' => 3, 'target' => 5, 'capaian' => 6]);

        $csv = "1,100,80\n2,\"120,5\",90\nJul,7,7\n";
        $this->actingAs($admin)->post(route('admin.statistik.import'), [
            'tahun' => 2027,
            'berkas' => UploadedFile::fake()->createWithContent('stat.csv', $csv),
        ])->assertRedirect()->assertSessionHas('sukses');

        $this->assertDatabaseHas('capaian_bulanan', ['tahun' => 2027, 'bulan' => 1, 'target' => 100, 'capaian' => 80]);
        $this->assertDatabaseHas('capaian_bulanan', ['tahun' => 2027, 'bulan' => 2, 'target' => 120.5, 'capaian' => 90]);
        // impor tidak menghapus bulan yang tidak disebut (bulan 3 tetap)
        $this->assertDatabaseHas('capaian_bulanan', ['tahun' => 2027, 'bulan' => 3, 'target' => 5]);
        // nama bulan alias ("Jul") dikenali sebagai bulan 7
        $this->assertDatabaseHas('capaian_bulanan', ['tahun' => 2027, 'bulan' => 7, 'target' => 7, 'capaian' => 7]);
    }

    public function test_staf_bidang_unggah_arsip_privat_bidang_lain_terkunci(): void
    {
        $this->buatKerangka();
        $staf = $this->userPegawai(2); // role pegawai, bidang 2
        $this->actingAs($staf)->get('/bidang/2')->assertOk()->assertSee('Kelola halaman');

        $file = UploadedFile::fake()->createWithContent('notulen.txt', 'isi notulen rapat');
        $this->actingAs($staf)->post(route('bidang.dokumen', 2), ['judul' => 'Notulen Rapat Bid', 'file' => $file])
            ->assertRedirect()->assertSessionHas('sukses');
        $d = Dokumen::where('judul', 'Notulen Rapat Bid')->firstOrFail();
        $this->assertSame('bidang', $d->visibility);

        $asing = $this->userPegawai(3);
        $this->actingAs($asing)->get('/dokumen')->assertOk()->assertDontSee('Notulen Rapat Bid');
        $this->actingAs($asing)->get(route('dokumen.unduh', $d))->assertForbidden();
        $this->actingAs($asing)->get('/bidang/2')->assertOk()->assertDontSee('Notulen Rapat Bid');
        $this->actingAs($asing)->delete(route('dokumen.destroy', $d))->assertForbidden();

        // sebidang: terlihat, boleh unduh & hapus
        $this->actingAs($staf)->get('/bidang/2')->assertOk()->assertSee('Notulen Rapat Bid');
        $this->actingAs($staf)->get(route('dokumen.unduh', $d))->assertOk();
        $this->actingAs($staf)->delete(route('dokumen.destroy', $d))->assertRedirect();
    }

    public function test_semua_user_bisa_mencari_semua_data_pegawai(): void
    {
        $this->buatKerangka();
        \App\Models\Pegawai::create(['nip' => '198001012005011001', 'nama' => 'CARI SAYA', 'id_bidang' => 3, 'status' => 'aktif']);
        $u = $this->userPegawai(1);
        $this->actingAs($u)->get('/pegawai?q=CARI SAYA')->assertOk()->assertSee('CARI SAYA');
    }
}

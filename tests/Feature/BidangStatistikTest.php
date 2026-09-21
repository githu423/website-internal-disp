<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\Statistik;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** Round 9: statistik bisa dikelola & filter tahun nyata; halaman bidang untuk semua + arsip privat. */
class BidangStatistikTest extends TestCase
{
    // --- v17: modul Statistik diganti ke format "indikator x tahun" (file Data Statistik) ---

    public function test_grafik_publik_memakai_tahun_dari_file_statistik(): void
    {
        $this->buatKerangka();
        $admin = $this->userAdmin();
        $csv = "DATA STATISTIK PARIWISATA,,\n\nNo,Indikator,Satuan,Tahun,,,,,Keterangan\n,,,2017,2018,2019,2020,2021,,,,\n1,Jumlah Kunjungan,Orang,10,20,30,40,50,,,,\n";
        $this->actingAs($admin)->post(route('admin.statistik.import'), [
            'file' => UploadedFile::fake()->createWithContent('s.csv', $csv),
        ])->assertRedirect()->assertSessionHas('sukses');

        preg_match("/data-chart='([^']+)'/", $this->actingAs($admin)->get('/statistik')->assertOk()->getContent(), $m);
        $chart = json_decode($m[1] ?? 'null', true) ?? [];
        $this->assertSame(['2017', '2018', '2019', '2020', '2021'], $chart['labels'], 'label grafik = tahun dari file, bukan bulan');
        $this->assertSame([10.0, 20.0, 30.0, 40.0, 50.0], array_map('floatval', $chart['datasets'][0]['data']));
    }

    public function test_kelola_statistik_manual_dan_impor(): void
    {
        $this->buatKerangka();
        $admin = $this->userAdmin();

        // grid manual: ribuan gaya Indonesia & persen dikenali
        $this->actingAs($admin)->post(route('admin.statistik.store'), [
            'tahunList' => [2024, 2025],
            'rows' => [['no_urut' => '1', 'indikator' => 'UJI STATISTIK BARU', 'satuan' => 'Persen',
                        'keterangan' => 'catatan', 'nilai' => [2024 => '1.250.000', 2025 => '12.5%']]],
        ])->assertRedirect()->assertSessionHas('sukses');
        $st = Statistik::where('indikator', 'UJI STATISTIK BARU')->firstOrFail();
        $this->assertSame(1250000.0, (float) $st->nilais->firstWhere('tahun', 2024)->nilai);
        $this->assertSame(12.5, (float) $st->nilais->firstWhere('tahun', 2025)->nilai);
        $this->assertSame('Persen', $st->satuan);

        // import indikator sama -> membarui (upsert by nama), tahun yang tidak disebut file dibiarkan
        $csv = "No,Indikator,Satuan,Tahun,,Keterangan\n,,,2024,2025,\n1,UJI STATISTIK BARU,Orang,999,,\n";
        $this->actingAs($admin)->post(route('admin.statistik.import'), [
            'file' => UploadedFile::fake()->createWithContent('s.csv', $csv),
        ])->assertRedirect()->assertSessionHas('sukses');
        $st->refresh();
        $this->assertCount(2, $st->nilais);
        $this->assertSame(999.0, (float) $st->nilais->firstWhere('tahun', 2024)->nilai);
        $this->assertSame('Orang', $st->satuan);
        $this->assertSame(12.5, (float) $st->nilais->firstWhere('tahun', 2025)->nilai, '2025 tidak disebut file -> tetap 12.5');
    }

    public function test_staf_bidang_unggah_arsip_privat_bidang_lain_terkunci(): void
    {
        $this->buatKerangka();
        $staf = $this->userPegawai(2); // role pegawai, bidang 2
        $this->actingAs($staf)->get('/bidang/2')->assertOk()->assertSee('Unggah ke Bidang Ini');

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

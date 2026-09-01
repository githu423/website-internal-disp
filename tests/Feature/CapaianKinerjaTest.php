<?php

namespace Tests\Feature;

use App\Models\CapaianIndikator;
use App\Models\CapaianSasaran;
use App\Models\CapaianTahunan;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CapaianKinerjaTest extends TestCase
{
    private function csv(): string
    {
        return <<<CSV
CAPAIAN KINERJA,
No.,Sasaran,Indikator Kinerja,Satuan,Tahun,
,,,,2024,,,2025,
,,,,Target,Realisasi,%,Target,Realisasi,%
1,"Uji Satu
Sasaran Tahun : 2025",Indikator A,Unit,"1,000",900,90.00%,"2,000","2,100",105.00%
,,Indikator B,Buah,n/a,n/a,n/a,5,4,80.00%
2,Uji Dua,Indikator C,Nilai,,,,,,,
CSV;
    }

    public function test_impor_matriks_dan_tampil_publik(): void
    {
        $this->buatKerangka();
        $admin = $this->userAdmin();
        $file = UploadedFile::fake()->createWithContent('capaian.csv', $this->csv());

        $this->actingAs($admin)->post(route('admin.capaian.import'), ['berkas' => $file])->assertRedirect();

        $this->assertSame(2, CapaianSasaran::count());
        $this->assertSame(3, CapaianIndikator::count());
        $this->assertSame(3, CapaianTahunan::count()); // sel n/a tidak disimpan
        $this->assertDatabaseHas('capaian_sasaran', ['nomor' => 1, 'cakupan' => '2025']);
        $this->assertDatabaseHas('capaian_tahunan', [
            'tahun' => 2025, 'target' => '2,000', 'realisasi' => '2,100', 'persen' => '105.00%',
        ]);

        $res = $this->actingAs($admin)->get(route('kinerja.capaian'));
        $res->assertOk()
            ->assertSee('Indikator A', false)
            ->assertSee('Uji Satu', false)
            ->assertSee('2,100', false)
            ->assertSee('105.00%', false)
            ->assertSee('80.00%', false);
    }

    public function test_input_manual_sasaran_indikator_dan_nilai(): void
    {
        $this->buatKerangka();
        $admin = $this->userAdmin();

        $this->actingAs($admin)->post(route('admin.capaian.sasaran.store'), [
            'judul' => 'Sasaran Manual', 'cakupan' => '2024-2026',
        ])->assertRedirect();
        $sasaran = CapaianSasaran::firstOrFail();

        $this->actingAs($admin)->post(route('admin.capaian.indikator.store'), [
            'sasaran_id' => $sasaran->id, 'nama' => 'Indikator Manual', 'satuan' => 'Persen',
        ])->assertRedirect();
        $ind = CapaianIndikator::firstOrFail();

        $this->actingAs($admin)->post(route('admin.capaian.nilai', $ind), [
            'nilai' => [2025 => ['target' => '10', 'realisasi' => '11', 'persen' => '110%']],
            't_tahun' => 2026, 't_target' => '20', 't_realisasi' => '', 't_persen' => '',
        ])->assertRedirect();
        $this->assertDatabaseHas('capaian_tahunan', ['indikator_id' => $ind->id, 'tahun' => 2025, 'persen' => '110%']);
        $this->assertDatabaseHas('capaian_tahunan', ['indikator_id' => $ind->id, 'tahun' => 2026, 'target' => '20', 'realisasi' => null]);

        // menghapus baris tahun dengan mengosongkan seluruh selnya
        $this->actingAs($admin)->post(route('admin.capaian.nilai', $ind), [
            'nilai' => [2025 => ['target' => '', 'realisasi' => '', 'persen' => '']],
        ])->assertRedirect();
        $this->assertDatabaseMissing('capaian_tahunan', ['indikator_id' => $ind->id, 'tahun' => 2025]);
    }

    public function test_hanya_admin_yang_bisa_kelola(): void
    {
        $this->buatKerangka();
        $peg = $this->userPegawai();
        $this->actingAs($peg)->get(route('admin.capaian.index'))->assertForbidden();
        $this->actingAs($peg)->post(route('admin.capaian.sasaran.store'), ['judul' => 'X'])->assertForbidden();
    }

    public function test_impor_berulang_tidak_menduplikat(): void
    {
        $this->buatKerangka();
        $admin = $this->userAdmin();
        $kirim = fn (string $isi) => $this->actingAs($admin)->post(route('admin.capaian.import'), [
            'berkas' => UploadedFile::fake()->createWithContent('c.csv', $isi), 'mode' => 'update',
        ]);

        $kirim($this->csv())->assertRedirect();
        $awal = [CapaianSasaran::count(), CapaianIndikator::count(), CapaianTahunan::count()];
        $this->assertSame([2, 3, 3], $awal);

        $kirim($this->csv())->assertRedirect(); // file yang sama, diunggah lagi
        $this->assertSame($awal, [CapaianSasaran::count(), CapaianIndikator::count(), CapaianTahunan::count()]);

        $kirim(str_replace('"2,100"', '"3,500"', $this->csv()))->assertRedirect(); // hanya satu nilai berubah
        $this->assertSame($awal, [CapaianSasaran::count(), CapaianIndikator::count(), CapaianTahunan::count()]);
        $this->assertDatabaseHas('capaian_tahunan', ['tahun' => 2025, 'realisasi' => '3,500']);
    }

    public function test_tampilan_statistik_dan_matriks(): void
    {
        $this->buatKerangka();
        $admin = $this->userAdmin();
        $this->actingAs($admin)->post(route('admin.capaian.import'), [
            'berkas' => UploadedFile::fake()->createWithContent('c.csv', $this->csv()),
        ])->assertRedirect();

        $this->actingAs($admin)->get(route('kinerja.capaian', ['tampilan' => 'statistik']))->assertOk()
            ->assertSee('data-chart', false)
            ->assertSee('Capaian %', false)
            ->assertSee('Tingkat capaian keseluruhan', false);
        $this->actingAs($admin)->get(route('kinerja.capaian'))->assertOk()
            ->assertSee('Indikator A', false)
            ->assertSee('Matriks', false);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Statistik;
use App\Models\StatistikNilai;
use App\Services\ImportService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * v17 (7 Sep 2026): modul Statistik diganti total ke format file
 * "DATA STATISTIK PARIWISATA KABUPATEN CIAMIS" (indikator × tahun).
 */
class StatistikBaruTest extends TestCase
{
    private function csv(array $ubah = []): string
    {
        $L = [
            'DATA STATISTIK PARIWISATA,,,,,,,,,,,,',
            'KABUPATEN CIAMIS,,,,,,,,,,,,',
            ',,,,,,,,,,,,',
            'No,Indikator,Satuan,Tahun,,,,,,,,,Keterangan',
            ',,,2017,2018,2019,2020,2021,2022,2023,2024,2025,',
            '1,Indeks Kepuasan Masyarakat Dinas Pariwisata,Nilai,0,0,0,0,0,82.00,84.97,86.54,87.20,',
            '2,Persentase PAD yang dikelola Dinas Pariwisata,Persen,22.07%,29.35%,69.02%,92.34%,106.42%,89.97%,117.32%,78.02%,100.49%,',
            '3,Jumlah PAD Yang Direncanakan,Rupiah,"1,173,275,000","1,150,733,500","1,144,718,000","487,470,000","481,758,000","900,984,000","643,503,500","1,214,879,100","980,836,000",',
            '5,Lama Kunjungan Wisata,Hari,n/a,n/a,n/a,0.43,0.46,0.43,0.44,1,1,',
            '11,Jumlah Obyek Wisata Dan Potensi OW,Lokasi,132,132,132,242,250,252,254,254,277,',
        ];
        foreach ($ubah as $dari => $ke) { $L = array_map(fn ($l) => str_replace($dari, $ke, $l), $L); }
        return implode("\r\n", $L);
    }

    private function rows(string $csv): array
    {
        return array_map(fn ($l) => str_getcsv($l), explode("\r\n", $csv));
    }

    public function test_parse_matriks_indikator_x_tahun(): void
    {
        $p = ImportService::parseStatistik($this->rows($this->csv()));
        $this->assertCount(5, $p, 'baris judul & header tidak boleh ikut jadi data');

        $ikm = collect($p)->firstWhere('indikator', 'like', 'Indeks%') ?? $p[0];
        $this->assertSame([2017, 2018, 2019, 2020, 2021, 2022, 2023, 2024, 2025], array_keys($p[0]['nilai']));
        $this->assertSame(0.0, $p[0]['nilai'][2017], 'nol asli TETAP 0, bukan n/a');
        $this->assertSame(82.0, $p[0]['nilai'][2022]);

        $this->assertSame(22.07, $p[1]['nilai'][2017], '"22.07%" -> 22.07');
        $this->assertSame(1173275000.0, $p[2]['nilai'][2017], 'ribuan "1,173,275,000" utuh');
        $this->assertArrayNotHasKey(2017, $p[3]['nilai'], 'n/a = TIDAK ADA nilai (bukan 0)');
        $this->assertSame(0.43, $p[3]['nilai'][2020]);
        $this->assertNull($p[0]['keterangan']);
        $this->assertSame(277.0, $p[4]['nilai'][2025]);
    }

    public function test_import_lewat_form_dan_upsert_by_nama(): void
    {
        $this->buatKerangka();
        $u = $this->userAdmin();
        $this->actingAs($u)->post('/admin/statistik/import', [
            'file' => UploadedFile::fake()->createWithContent('Data Statistik.csv', $this->csv()),
        ])->assertRedirect()->assertSessionHas('sukses');

        $this->assertSame(5, Statistik::count());
        $pad = Statistik::where('indikator', 'like', 'Persentase PAD%')->firstOrFail();
        $this->assertSame(22.07, (float) StatistikNilai::where('id_statistik', $pad->id)->where('tahun', 2017)->value('nilai'));
        $this->assertSame('Persen', $pad->satuan);

        // import ulang dengan satu angka diubah -> diperbarui, tidak dobel
        $this->actingAs($u)->post('/admin/statistik/import', [
            'file' => UploadedFile::fake()->createWithContent('Data Statistik.csv', $this->csv(['254,254,277,' => '254,254,300,'])),
        ])->assertSessionHas('sukses');
        $this->assertSame(5, Statistik::count(), 'nama indikator sama harus membarui, bukan menambah');
        $ow = Statistik::where('indikator', 'like', 'Jumlah Obyek Wisata%')->firstOrFail();
        $this->assertSame(300.0, (float) StatistikNilai::where('id_statistik', $ow->id)->where('tahun', 2025)->value('nilai'));
    }

    public function test_grid_manual_update_tambah_hapus(): void
    {
        $this->buatKerangka();
        $u = $this->userAdmin();
        $this->actingAs($u)->post('/admin/statistik/import', [
            'file' => UploadedFile::fake()->createWithContent('s.csv', $this->csv()),
        ]);
        $lama = Statistik::where('indikator', 'like', 'Indeks Kepuasan%')->firstOrFail();
        $hapus = Statistik::where('indikator', 'like', 'Lama Kunjungan%')->firstOrFail();

        $this->actingAs($u)->post('/admin/statistik', [
            'tahunList' => [2017, 2018, 2019, 2020, 2021, 2022, 2023, 2024, 2025],
            'rows' => [
                ['id' => $lama->id, 'no_urut' => '1', 'indikator' => $lama->indikator, 'satuan' => 'Nilai',
                 'keterangan' => 'survei indeks tunggal', 'nilai' => [2025 => '88.10', 2024 => '']],
                ['no_urut' => '', 'indikator' => 'UJI MANUAL BARU', 'satuan' => 'Orang',
                 'keterangan' => '', 'nilai' => [2024 => '42', 2025 => '1,000']],
                ['id' => $hapus->id, 'indikator' => $hapus->indikator, 'hapus' => '1'],
            ],
        ])->assertSessionHas('sukses');

        $lama->refresh();
        $this->assertSame('survei indeks tunggal', $lama->keterangan);
        $this->assertSame(88.1, (float) StatistikNilai::where('id_statistik', $lama->id)->where('tahun', 2025)->value('nilai'));
        $this->assertNull(StatistikNilai::where('id_statistik', $lama->id)->where('tahun', 2024)->first(), 'sel dikosongkan = hapus nilai');

        $baru = Statistik::where('indikator', 'UJI MANUAL BARU')->firstOrFail();
        $this->assertSame(1000.0, (float) StatistikNilai::where('id_statistik', $baru->id)->where('tahun', 2025)->value('nilai'));
        $this->assertSame(5, Statistik::count(), '5 import - 1 dihapus + 1 baru = 5'); $this->assertDatabaseMissing('statistik', ['indikator' => $hapus->indikator]);
    }

    public function test_halaman_publik_tabel_dan_grafik(): void
    {
        $this->buatKerangka();
        $this->actingAs($this->userAdmin())->post('/admin/statistik/import', [
            'file' => UploadedFile::fake()->createWithContent('s.csv', $this->csv()),
        ]);
        $ow = Statistik::where('indikator', 'like', 'Jumlah Obyek Wisata%')->firstOrFail();

        $pu = $this->userPegawai(2, 'ST-U1');
        $this->actingAs($pu)->get('/statistik')->assertOk()
            ->assertSee('Statistik Pariwisata Daerah')
            ->assertSee('Indeks Kepuasan Masyarakat Dinas Pariwisata')
            ->assertSee('data-chart', false);

        $this->actingAs($pu)->get('/statistik?ind=' . $ow->id)->assertOk()
            ->assertSee('Tren —', false)->assertSee('Jumlah Obyek Wisata Dan Potensi OW');

        $json = $this->actingAs($pu)->getJson('/statistik/data')->assertOk()->json();
        $this->assertCount(5, $json['rows']);
        $this->assertSame(2017, $json['tahun'][0]);
    }

    public function test_angka_sel_heuristik(): void
    {
        $this->assertSame(1173275000.0, ImportService::angkaSel('1,173,275,000'));
        $this->assertSame(1173275000.0, ImportService::angkaSel('1.173.275.000'));
        $this->assertSame(22.07, ImportService::angkaSel('22.07%'));
        $this->assertSame(1.5, ImportService::angkaSel('1,5'));
        $this->assertSame(82.0, ImportService::angkaSel('82.00'));
        $this->assertSame(-5.25, ImportService::angkaSel('-5.25'));
        $this->assertNull(ImportService::angkaSel('n/a'));
        $this->assertNull(ImportService::angkaSel('—'));
        $this->assertNull(ImportService::angkaSel(''));
        $this->assertSame(0.0, ImportService::angkaSel('0'));
    }
}

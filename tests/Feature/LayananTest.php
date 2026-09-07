<?php

namespace Tests\Feature;

use App\Services\MasaKerjaService;
use Tests\TestCase;
use Carbon\Carbon;

class LayananTest extends TestCase
{
    public function test_masa_kerja_terhitung_tepat(): void
    {
        // 10 Feb 2020 -> 10 Agu 2026 = 6 th 6 bln 0 hari (tanggal uji difiksasi agar tidak rapuh)
        $this->travelTo(Carbon::create(2026, 8, 10));
        $r = MasaKerjaService::hitung(Carbon::create(2020, 2, 10));
        $this->assertSame(6, $r['tahun']);
        $this->assertSame(6, $r['bulan']);
        $this->assertSame(0, $r['hari']);
        $this->assertSame('6 tahun 6 bulan 0 hari', $r['label']);
        $this->assertSame('—', MasaKerjaService::hitung(null)['label']);
    }

    public function test_masa_kerja_menyertakan_hari_realtime(): void
    {
        // permintaan Ricky 7 Sep 2026: satuan hari ikut muncul & berubah tiap hari
        $this->travelTo(Carbon::create(2026, 9, 7));
        $r = MasaKerjaService::hitung(Carbon::create(2020, 2, 10));
        $this->assertSame([6, 6, 28], [$r['tahun'], $r['bulan'], $r['hari']]);
        $this->assertSame('6 tahun 6 bulan 28 hari', $r['label']);

        $this->assertSame('1 tahun 1 bulan 4 hari', MasaKerjaService::hitung('2025-08-03')['label']);
        $this->assertSame('5 bulan 0 hari', MasaKerjaService::hitung('2026-04-07')['label']);
        $this->assertSame('10 hari', MasaKerjaService::hitung('2026-08-28')['label']);
        $this->assertSame('0 hari', MasaKerjaService::hitung('2026-09-07')['label']);
        // tanggal di masa depan -> strip, bukan minus
        $this->assertSame('—', MasaKerjaService::hitung('2027-01-01')['label']);
    }

    public function test_dokumen_rahasia_hanya_untuk_pemilik_dan_admin(): void
    {
        $this->buatKerangka();
        $pemilik = $this->userPegawai(1);
        $lain = $this->userPegawai(3);
        $dok = \App\Models\Dokumen::create([
            'judul' => 'Rahasia', 'visibility' => 'rahasia', 'file_path' => 'x.txt', 'file_name' => 'x.txt',
            'file_size' => 1, 'uploaded_by' => $pemilik->id,
        ]);
        $this->assertTrue($dok->bolehDilihat($pemilik));
        $this->assertFalse($dok->bolehDilihat($lain));
        $this->assertTrue($dok->bolehDilihat($this->userAdmin()));
    }
}

<?php

namespace Tests\Feature;

use App\Services\MasaKerjaService;
use Tests\TestCase;
use Carbon\Carbon;

class LayananTest extends TestCase
{
    public function test_masa_kerja_terhitung_tepat(): void
    {
        $r = MasaKerjaService::hitung(Carbon::create(2020, 2, 10));
        $this->assertSame(6, $r['tahun']);
        $this->assertSame(6, $r['bulan']);
        $this->assertSame('—', MasaKerjaService::hitung(null)['label']);
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

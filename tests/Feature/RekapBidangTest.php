<?php

namespace Tests\Feature;

use App\Models\Bidang;
use App\Models\Pegawai;
use Tests\TestCase;

/** Round 19 (7 Sep 2026): strip Rekap jumlah di bawah daftar pegawai halaman detail Bidang. */
class RekapBidangTest extends TestCase
{
    public function test_rekap_bidang_konsisten_dengan_daftar_anggota(): void
    {
        $this->buatKerangka();
        $b = Bidang::orderBy('id')->first();
        Pegawai::create(['nip' => 'RB1', 'nama' => 'UJI RB SATU', 'status' => 'aktif', 'id_bidang' => $b->id, 'jenis_kelamin' => 'L', 'jenis_asn' => 'PNS']);
        Pegawai::create(['nip' => 'RB2', 'nama' => 'UJI RB DUA', 'status' => 'aktif', 'id_bidang' => $b->id, 'jenis_kelamin' => 'P', 'jenis_asn' => 'PPPK']);
        Pegawai::create(['nip' => 'RB3', 'nama' => 'UJI RB TIGA', 'status' => 'aktif', 'id_bidang' => $b->id]);

        $resp = $this->actingAs($this->userAdmin())->get('/bidang/' . $b->id)->assertOk()->assertSee('Rekap:');
        $st = $resp->viewData('statistik');
        $anggota = $resp->viewData('pegawais');

        $this->assertSame($anggota->count(), $st['semua'], 'rekap harus sama persis dengan jumlah baris daftar');
        $this->assertSame($st['pns'] + $st['nonpns'], $st['semua'], 'PNS + non-PNS harus menutup total');
        $this->assertGreaterThanOrEqual(1, $st['l']);
        $this->assertGreaterThanOrEqual(1, $st['p']);
        $this->assertGreaterThanOrEqual(1, $st['pns']);
    }

    public function test_rekap_juga_terlihat_untuk_user_bidang_lain(): void
    {
        $this->buatKerangka();
        $b = Bidang::orderBy('id')->first();
        $luar = $this->userPegawai(2, 'RB-LUAR');
        $this->assertNotSame($b->id, $luar->id_bidang);
        $this->actingAs($luar)->get('/bidang/' . $b->id)->assertOk()->assertSee('Rekap:');
    }
}

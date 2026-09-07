<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Services\MasaKerjaService;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Tes round 18 (7 Sep 2026): halaman /profil untuk SEMUA user tanpa terkecuali
 * + masa kerja real-time bersatuan hari.
 */
class ProfilTest extends TestCase
{
    public function test_semua_peran_bisa_buka_halaman_profil(): void
    {
        $this->buatKerangka();
        foreach ([$this->userAdmin(), $this->userPegawai(2, 'PF-A'), $this->userPegawai(3, 'PF-B')] as $u) {
            $this->actingAs($u)->get('/profil')->assertOk()
                ->assertSee('Profil Saya')->assertSee('Isi data kepegawaianmu');
        }
    }

    public function test_simpan_profil_mengisi_baris_pegawai_tertaut(): void
    {
        $this->buatKerangka();
        $u = $this->userPegawai(2, '199001012015011001');
        Pegawai::create(['nip' => '199001012015011001', 'nama' => 'UJI PROFIL', 'status' => 'aktif']);

        $this->actingAs($u)->patch('/profil', [
            'name' => 'UJI PROFIL BARU',
            'tempat_lahir' => 'Ciamis', 'tanggal_lahir' => '1990-01-01', 'jenis_kelamin' => 'L',
            'pangkat_golongan' => 'PENATA (III/c)', 'pendidikan' => 'S1 Hukum', 'unit_kerja' => 'Bidang Pemasaran',
            'tmt_cpns' => '2015-02-01', 'tmt_sekarang' => '2023-04-01', 'tmt_jabatan' => '2024-01-01',
        ])->assertRedirect()->assertSessionHas('sukses');

        $row = Pegawai::where('nip', '199001012015011001')->firstOrFail();
        $this->assertSame('UJI PROFIL BARU', $row->nama);
        $this->assertSame('PENATA (III/c)', $row->pangkat_golongan);
        $this->assertSame('S1 Hukum', $row->pendidikan);
        $this->assertSame('2015-02-01', $row->tmt_cpns->format('Y-m-d'));
        $this->assertSame('2023-04-01', $row->tmt_sekarang->format('Y-m-d'));
        $this->assertSame('UJI PROFIL BARU', $u->fresh()->name);

        // masa kerja peserta ikut bersatuan hari setelah TMT diisi
        $this->travelTo(Carbon::create(2026, 9, 7));
        $this->assertSame('11 tahun 7 bulan 6 hari', MasaKerjaService::hitung('2015-02-01')['label']);
    }

    public function test_user_tanpa_baris_pegawai_membuat_entri_sendiri(): void
    {
        $this->buatKerangka();
        $u = $this->userPegawai(3, '199202022016022002');
        $this->assertNull($u->pegawai);

        $this->actingAs($u)->patch('/profil', [
            'name' => $u->name, 'tempat_lahir' => 'Banjar', 'jenis_kelamin' => 'P', 'pendidikan' => 'S2 Manajemen',
        ])->assertRedirect()->assertSessionHas('sukses');

        $this->assertDatabaseHas('pegawai', ['nip' => '199202022016022002', 'pendidikan' => 'S2 Manajemen']);
    }

    public function test_masa_kerja_di_daftar_pegawai_menyertakan_hari(): void
    {
        $this->buatKerangka();
        $this->travelTo(Carbon::create(2026, 9, 7));
        Pegawai::create([
            'nip' => '198001012000011001', 'nama' => 'UJI HARI', 'status' => 'aktif',
            'tmt_cpns' => '2025-08-03', // tepat 1 th 1 bln 4 hr menuju 7 Sep 2026
        ]);
        $this->actingAs($this->userAdmin())->get('/pegawai')->assertOk()->assertSee('1 tahun 1 bulan 4 hari');
    }

    public function test_rekap_jumlah_pegawai_tampil_di_bawah_tabel(): void
    {
        $this->buatKerangka();
        Pegawai::create(['nip' => 'RK1', 'nama' => 'UJI REKAP L PNS', 'status' => 'aktif', 'jenis_kelamin' => 'L', 'jenis_asn' => 'PNS']);
        Pegawai::create(['nip' => 'RK2', 'nama' => 'UJI REKAP P PPPK', 'status' => 'aktif', 'jenis_kelamin' => 'P', 'jenis_asn' => 'PPPK']);
        Pegawai::create(['nip' => 'RK3', 'nama' => 'UJI REKAP KOSONG', 'status' => 'aktif']); // tak berJK/ASN -> non-PNS juga

        $resp = $this->actingAs($this->userAdmin())->get('/pegawai')->assertOk()->assertSee('Rekap:');
        $st = $resp->viewData('statistik');
        $this->assertSame(Pegawai::count(), $st['semua']);
        $this->assertSame(Pegawai::where('jenis_kelamin', 'L')->count(), $st['l']);
        $this->assertSame(Pegawai::where('jenis_kelamin', 'P')->count(), $st['p']);
        $this->assertSame(Pegawai::where('jenis_asn', 'PNS')->count(), $st['pns']);
        $this->assertSame($st['semua'] - $st['pns'], $st['nonpns'], 'non-PNS harus sisa dari total agar angkanya konsisten');
        $this->assertGreaterThanOrEqual(1, $st['pns']);
    }

    public function test_tautan_profil_muncul_di_header_dan_sidebar(): void
    {
        $this->buatKerangka();
        $this->actingAs($this->userPegawai(2, 'PF-C'))
            ->get('/pegawai')->assertOk()->assertSee('Profil saya', false);
    }
}

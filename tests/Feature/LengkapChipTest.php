<?php

namespace Tests\Feature;

use App\Models\Bidang;
use App\Models\Pegawai;
use Tests\TestCase;

/**
 * Round 20 (7 Sep 2026): chip merah "belum lengkap data" (klik = saring)
 * di Data Pegawai user & admin, plus rekap kecil ♂/♀/PNS per kartu Bidang.
 * Kriteria lengkap = checklist halaman /profil.
 */
class LengkapChipTest extends TestCase
{
    private function pegawaiLengkap(string $nip, string $nama, ?int $bidangId = null): void
    {
        Pegawai::create([
            'nip' => $nip, 'nama' => $nama, 'status' => 'aktif', 'id_bidang' => $bidangId,
            'tempat_lahir' => 'Banjar', 'tanggal_lahir' => '1985-05-05', 'jenis_kelamin' => 'L',
            'pangkat_golongan' => 'PENATA (III/c)', 'pendidikan' => 'S1', 'unit_kerja' => 'Sekretariat',
            'pangkat_awal' => 'PENGATUR (II/c)', 'diklat_struktural' => 'Latsar 2011', 'diklat_fungsional' => 'Diklat Pokja', 'diklat_teknis' => 'Bimtek Sipanli',
            'jenis_asn' => 'PNS', 'jabatan' => 'STAF BIASA',
            'tmt_cpns' => '2010-01-01', 'tmt_sekarang' => '2020-01-01', 'tmt_jabatan' => '2022-01-01',
        ]);
    }

    public function test_scope_belum_lengkap_mencerminkan_checklist_profil(): void
    {
        $this->buatKerangka();
        $this->pegawaiLengkap('LC1', 'UJI LENGKAP SATU');
        Pegawai::create(['nip' => 'LC2', 'nama' => 'UJI BOLONG SATU', 'status' => 'aktif']);
        // pangkat terisi tapi tmt_jabatan kosong -> tetap "belum lengkap"
        Pegawai::create(['nip' => 'LC3', 'nama' => 'UJI BOLONG DUA', 'status' => 'aktif', 'pangkat_golongan' => 'PENATA (III/c)']);

        $belum = Pegawai::belumLengkap()->pluck('nip')->all();
        $this->assertNotContains('LC1', $belum);
        $this->assertContains('LC2', $belum);
        $this->assertContains('LC3', $belum);
    }

    public function test_penanda_kelengkapan_khusus_admin_v24(): void
    {
        $this->buatKerangka();
        $this->pegawaiLengkap('LC1', 'UJI LENGKAP SATU');
        Pegawai::create(['nip' => 'LC2', 'nama' => 'UJI BOLONG SATU', 'status' => 'aktif']);

        // user biasa: TIDAK ada chip, tidak ada 'sudah lengkap', filter ?belum diabaikan
        $u = $this->userPegawai(2, 'LC-U1');
        $this->actingAs($u)->get('/pegawai')->assertOk()
            ->assertDontSee('saring')->assertDontSee('sudah lengkap')
            ->assertDontSee('belum lengkap: ', false)
            ->assertSee('UJI LENGKAP SATU');
        $this->actingAs($u)->get('/pegawai?belum=1')->assertOk()
            ->assertSee('UJI LENGKAP SATU')->assertSee('UJI BOLONG SATU');
        // admin di halaman yg sama: chip + saring + catatan lengkap
        $a = $this->userAdmin();
        $this->actingAs($a)->get('/pegawai')->assertOk()
            ->assertSee('belum lengkap datanya')->assertSee('saring')->assertSee('sudah lengkap');
        $this->actingAs($a)->get('/pegawai?belum=1')->assertOk()
            ->assertSee('UJI BOLONG SATU')->assertDontSee('UJI LENGKAP SATU')
            ->assertSee('belum lengkap: ', false);
    }

    public function test_chip_merah_dan_saring_di_halaman_admin(): void
    {
        $this->buatKerangka();
        $this->pegawaiLengkap('LC1', 'UJI LENGKAP SATU');
        Pegawai::create(['nip' => 'LC2', 'nama' => 'UJI BOLONG SATU', 'status' => 'aktif']);

        $u = $this->userAdmin();
        $this->actingAs($u)->get('/admin/pegawai')->assertOk()->assertSee('⚠', false);
        $this->actingAs($u)->get('/admin/pegawai?belum=1')->assertOk()
            ->assertSee('UJI BOLONG SATU')->assertDontSee('UJI LENGKAP SATU');
    }

    public function test_rekap_kecil_di_kartu_daftar_bidang(): void
    {
        $this->buatKerangka();
        $b = Bidang::create(['kode' => 'X9', 'nama' => 'UPTD UJI CHIP', 'deskripsi' => '—']);
        $this->pegawaiLengkap('LC1', 'UJI LENGKAP SATU', $b->id); // L, PNS
        Pegawai::create([ // P, PPPK, bolong pangkat dll
            'nip' => 'LC2', 'nama' => 'UJI BOLONG SATU', 'status' => 'aktif',
            'id_bidang' => $b->id, 'jenis_kelamin' => 'P', 'jenis_asn' => 'PPPK',
        ]);

        $resp = $this->actingAs($this->userAdmin())->get('/bidang')->assertOk();
        $kartu = collect($resp->viewData('bidangList'))->firstWhere('nama', 'UPTD UJI CHIP');
        $this->assertNotNull($kartu->rek, 'rekap agregat harus menempel ke kartu bidangnya');
        $this->assertSame(1, (int) $kartu->rek->l);
        $this->assertSame(1, (int) $kartu->rek->p);
        $this->assertSame(1, (int) $kartu->rek->pns);
        $this->assertSame(1, (int) $kartu->rek->blm);
        $resp->assertSee('♂ 1 · ♀ 1 · PNS 1 · non-PNS 1', false)->assertSee('belum lengkap', false);
    }

    public function test_kartu_bidang_tanpa_anggota_tanpa_rekap_kecil(): void
    {
        $this->buatKerangka();
        $b = Bidang::create(['kode' => 'X8', 'nama' => 'BIDANG KOSONG UJI', 'deskripsi' => '—']);
        $this->actingAs($this->userAdmin())->get('/bidang')->assertOk()
            ->assertSee('BIDANG KOSONG UJI'); // tetap tampil, hanya tanpa baris rekap (rek null)
    }

    public function test_kriteria_lengkap_v21_semua_kolom_file(): void
    {
        $this->buatKerangka();
        $this->pegawaiLengkap('LC9', 'UJI V21 LENGKAP');
        $l = Pegawai::where('nip', 'LC9')->first();
        $this->assertNotContains('LC9', Pegawai::belumLengkap()->pluck('nip')->all(), '13 kolom file terisi = lengkap');
        $this->assertSame([], $l->kolomKurang());
        // tempat/tanggal lahir KOSONG pun tidak memengaruhi status (bukan kolom wajib)
        $l->update(['tempat_lahir' => null, 'tanggal_lahir' => null]);
        $this->assertNotContains('LC9', Pegawai::belumLengkap()->pluck('nip')->all());
        // satu kolom diklat dikosongkan -> langsung tercatat belum lengkap + disebut di badge
        $l->update(['diklat_teknis' => '']);
        $this->assertContains('LC9', Pegawai::belumLengkap()->pluck('nip')->all());
        $this->assertSame(['Diklat teknis'], $l->fresh()->kolomKurang());
    }

    public function test_mode_belum_lengkap_di_admin_tanpa_pin_di_mode_normal(): void
    {
        $this->buatKerangka();
        $this->pegawaiLengkap('LC1', 'UJI LENGKAP SATU');
        $bolong = Pegawai::create(['nip' => 'LC2', 'nama' => 'UJI BOLONG SATU', 'status' => 'aktif']);
        $u = $this->userAdmin();
        // mode normal: TANPA badge merah/hijau sama sekali (permintaan 7 Sep)
        $this->actingAs($u)->get('/admin/pegawai')->assertOk()
            ->assertDontSee('belum lengkap: ', false)
            ->assertDontSee('✓ lengkap', false)
            ->assertSee('1 belum', false)->assertSee('1 lengkap', false);
        // mode belum (chip): TABEL berubah + catatan lengkap apa yang bolong
        $this->actingAs($u)->get('/admin/pegawai?belum=1')->assertOk()
            ->assertSee('UJI BOLONG SATU')->assertDontSee('UJI LENGKAP SATU')
            ->assertSee('belum lengkap: Pangkat/golongan awal, TMT CPNS, Pangkat/golongan terakhir', false);
        // kata kunci di kotak cari = saringan yang sama — khusus admin (v24)
        $this->actingAs($u)->get('/pegawai?q=belum+lengkap')->assertOk()
            ->assertSee('UJI BOLONG SATU')->assertDontSee('UJI LENGKAP SATU')
            ->assertSee('belum lengkap: ', false);
        // cari nama biasa tetap normal utk semua user
        $pu = $this->userPegawai(2, 'LC-U2');
        $this->actingAs($pu)->get('/pegawai?q=UJI+LENGKAP')->assertOk()->assertSee('UJI LENGKAP SATU');
    }

}

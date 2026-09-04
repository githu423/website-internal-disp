<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\User;
use App\Services\ImportService;
use Tests\TestCase;

/** Round 10: menu bidang utk semua user, lencana = nama bidang, penanda "bidang siapa". */
class HalamanBidangTest extends TestCase
{
    public function test_semua_user_melihat_menu_halaman_bidang(): void
    {
        $this->buatKerangka();
        $this->actingAs($this->userPegawai(1))->get('/dashboard')->assertOk()->assertSee('Bidang Dinas');
        $this->actingAs($this->userAdmin())->get('/dashboard')->assertOk()->assertSee('Bidang Dinas');
    }

    public function test_lencana_atas_menampilkan_nama_bidang_bukan_teks_bidang(): void
    {
        $this->buatKerangka();
        $u = $this->userPegawai(3);
        $u->update(['role' => 'bidang']);
        // kerangka membuat bidang 'BIDANG PEMASARAN'; lencana membuang kata "BIDANG" di depan agar ringkas
        $this->actingAs($u)->get('/dashboard')->assertOk()->assertSee('PEMASARAN', false);
    }

    public function test_lencana_memperingatkan_role_bidang_tanpa_bidang_tertentu(): void
    {
        $this->buatKerangka();
        $u = $this->userPegawai();
        $u->update(['role' => 'bidang', 'id_bidang' => null]);
        $this->actingAs($u)->get('/dashboard')->assertOk()->assertSee('Bidang: belum tertentu');
    }

    public function test_sinkron_mengikat_pimpinan_ke_bidang_tertentu(): void
    {
        $this->buatKerangka();
        Pegawai::create(['nip' => '197501011998031001', 'nama' => 'UJI KABID', 'jabatan' => 'Kepala Bidang Pemasaran', 'status' => 'aktif']);
        $u = User::create(['name' => 'Uji Kabid', 'nip' => '197501011998031001', 'password' => 'x12345678', 'role' => 'pegawai', 'id_bidang' => null]);
        ImportService::sinkronAkunPegawai();
        $u->refresh();
        $this->assertSame('bidang', $u->role);
        $this->assertSame(3, (int) $u->id_bidang, 'peran bidang harus terikat ke bidang tertentu hasil tebakan jabatan');
    }

    public function test_penanda_bidang_sendiri_di_daftar_dan_di_halaman(): void
    {
        $this->buatKerangka();
        $saya = $this->userPegawai(2); // anggota bidang 2 (PENGEMBANGAN DESTINASI)
        $this->actingAs($saya)->get('/bidang')->assertOk()->assertSee('BIDANG ANDA');

        $this->actingAs($saya)->get('/bidang/2')->assertOk()->assertSee('ANDA DI BIDANG INI')->assertDontSee('melihat bidang lain');
        $this->actingAs($saya)->get('/bidang/3')->assertOk()->assertSee('melihat bidang lain')->assertDontSee('ANDA DI BIDANG INI');

        // admin melihat label mode admin di halaman bidang mana pun
        $this->actingAs($this->userAdmin())->get('/bidang/1')->assertOk()->assertSee('MODE ADMIN');
    }
}

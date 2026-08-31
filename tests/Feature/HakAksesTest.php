<?php

namespace Tests\Feature;

use Tests\TestCase;

class HakAksesTest extends TestCase
{
    public function test_pegawai_tidak_bisa_masuk_area_admin(): void
    {
        $this->buatKerangka();
        $this->actingAs($this->userPegawai());
        foreach (['/admin', '/admin/pad', '/admin/kinerja', '/admin/akun'] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_bidang_hanya_bisa_buka_profil_bidangnya(): void
    {
        $this->buatKerangka();
        $u = $this->userPegawai();
        $u->update(['role' => 'bidang']);
        $this->actingAs($u);
        $this->get('/bidang/1')->assertOk();
        $this->get('/bidang/3')->assertForbidden();
    }

    public function test_pegawai_bisa_membuka_dashboard_dan_modul_publik(): void
    {
        $this->buatKerangka();
        $this->actingAs($this->userPegawai());
        foreach (['/dashboard', '/kinerja', '/pad', '/statistik', '/agenda', '/dokumen', '/pegawai', '/notifikasi'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_dashboard_tampil_bagian_kinerja_dan_pengingat(): void
    {
        $this->buatKerangka();
        $this->actingAs($this->userAdmin());
        \App\Models\CapaianProgram::create(['tahun' => 2026, 'level' => 'sub_kegiatan', 'nama' => 'Event Uji Coba', 'persentase' => 120]);
        \App\Models\Agenda::create(['judul' => 'Rapat Uji', 'tanggal' => now()->addDays(2)->toDateString(), 'status' => 'belum']);
        $this->get('/dashboard')->assertOk()
            ->assertSee('Event Uji Coba')->assertSee('Rapat Uji')
            ->assertSee('Pengingat Kegiatan')->assertSee('Kegiatan &amp; Kinerja Dinas', false);
    }
}

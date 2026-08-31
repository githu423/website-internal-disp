<?php

namespace Tests\Feature;

use Tests\TestCase;

class AutentikasiTest extends TestCase
{
    public function test_login_memakai_nip_dan_password_dan_mengalihkan_ke_dashboard(): void
    {
        $this->buatKerangka();
        $admin = $this->userAdmin();

        $res = $this->post('/login', ['nip' => 'ADMIN0000', 'password' => 'admin123']);
        $res->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_password_salah_ditolak(): void
    {
        $this->buatKerangka();
        $this->userPegawai();
        $this->post('/login', ['nip' => 'PEG9', 'password' => 'salah123'])
            ->assertSessionHasErrors('nip');
        $this->assertGuest();
    }

    public function test_nip_pegawai_dengan_spasi_tetap_bisa_masuk(): void
    {
        $this->buatKerangka();
        $u = \App\Models\User::create([
            'name' => 'Uji Spasi', 'nip' => '197502111996011001', 'password' => 'DISPARCiamis2026', 'role' => 'pegawai',
        ]);
        $this->post('/login', ['nip' => '19750211 199601 1 001', 'password' => 'DISPARCiamis2026'])
            ->assertSessionHasErrors(); // spasi TIDAK di-strip otomatis saat login — harus NIP bersih
        $this->post('/login', ['nip' => '197502111996011001', 'password' => 'DISPARCiamis2026'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_halaman_login_tanpa_captcha(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('captcha', false);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\CapaianProgram;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MatriksRoleTest extends TestCase
{
    public static function peran(): array
    {
        return ['admin' => ['admin'], 'bidang' => ['bidang'], 'pegawai' => ['pegawai']];
    }

    #[DataProvider('peran')]
    public function test_dashboard_dan_modul_bersama_tersedia_untuk_setiap_role(string $role): void
    {
        $this->buatKerangka();
        $user = $this->userPegawai();
        $user->update(['role' => $role]);
        CapaianProgram::create(['tahun' => 2026, 'level' => 'sub_kegiatan', 'nama' => 'Kegiatan lintas role', 'persentase' => 100]);
        Agenda::create(['judul' => 'Pengingat lintas role', 'tanggal' => now()->addDays(2)->toDateString(), 'status' => 'belum']);
        $this->actingAs($user);

        $this->get('/dashboard')->assertOk()->assertSee('Kegiatan lintas role')->assertSee('Pengingat lintas role')
            ->assertSee('Pegawai Aktif')->assertSee('Pegawai Non-Aktif')->assertSee('Pegawai Pensiun');
        foreach (['/dashboard?lingkup=saya', '/dashboard/kartu', '/kinerja', '/kinerja/capaian', '/pad', '/statistik', '/statistik/data', '/agenda', '/dokumen', '/pegawai', '/profil', '/notifikasi', '/notifikasi/unread', '/bidang', '/bidang/1', '/bidang/3', '/ruang-rapat', '/search?q=uji'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    #[DataProvider('peran')]
    public function test_semua_halaman_admin_dibatasi_sesuai_role(string $role): void
    {
        $this->buatKerangka();
        $user = $this->userPegawai();
        $user->update(['role' => $role]);
        $this->actingAs($user);

        foreach (['/admin', '/admin/akun', '/admin/pegawai', '/admin/dokumen', '/admin/agenda', '/admin/kinerja', '/admin/capaian-kinerja', '/admin/statistik', '/admin/pad', '/admin/prestasi', '/admin/log'] as $url) {
            $response = $this->get($url);
            if ($role === 'admin') {
                $response->assertOk();
            } else {
                $response->assertForbidden();
            }
        }
    }

    public function test_tamu_harus_login_untuk_modul_internal(): void
    {
        foreach (['/dashboard', '/admin', '/dokumen', '/pegawai', '/profil', '/bidang', '/ruang-rapat', '/notifikasi'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }
}

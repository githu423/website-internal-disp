<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PencarianAksesTest extends TestCase
{
    #[TestWith(['pegawai', 1])]
    #[TestWith(['bidang', 1])]
    #[TestWith(['pegawai', null])]
    public function test_pencarian_tidak_membocorkan_dokumen_privat_atau_rahasia(string $role, ?int $bidang): void
    {
        $this->buatKerangka();
        $pemilik = $this->userPegawai(3);
        $user = $this->userPegawai($bidang);
        $user->update(['role' => $role]);
        $this->buatDokumen('publik', 3, $pemilik->id, 'Publik lintas dinas');
        $this->buatDokumen('bidang', 3, $pemilik->id, 'Privat bidang lain');
        $this->buatDokumen('rahasia', 1, $pemilik->id, 'Rahasia milik orang lain');
        $this->buatDokumen('rahasia', 3, $user->id, 'Rahasia milik sendiri');

        $this->actingAs($user)->get('/search?q=Arsip')->assertOk()
            ->assertSee('Publik lintas dinas')->assertSee('Rahasia milik sendiri')
            ->assertDontSee('Privat bidang lain')->assertDontSee('Rahasia milik orang lain');
    }

    public function test_admin_dapat_menemukan_dokumen_rahasia_dan_rekan_dapat_menemukan_arsip_sebidang(): void
    {
        $this->buatKerangka();
        $pemilik = $this->userPegawai(1);
        $rekan = $this->userPegawai(1);
        $this->buatDokumen('bidang', 1, $pemilik->id, 'Privat sebidang');
        $this->buatDokumen('rahasia', 1, $pemilik->id, 'Rahasia pemilik');

        $this->actingAs($rekan)->get('/search?q=Arsip')->assertOk()
            ->assertSee('Privat sebidang')->assertDontSee('Rahasia pemilik');
        $this->actingAs($this->userAdmin())->get('/search?q=Arsip')->assertOk()
            ->assertSee('Privat sebidang')->assertSee('Rahasia pemilik');
    }

    private function buatDokumen(string $visibility, int $bidang, int $pemilik, string $judul): void
    {
        Dokumen::create([
            'judul' => 'Arsip '.$judul, 'visibility' => $visibility,
            'id_bidang' => $bidang, 'uploaded_by' => $pemilik,
            'file_path' => 'pengujian.txt', 'file_name' => 'pengujian.txt', 'file_size' => 1,
        ]);
    }
}

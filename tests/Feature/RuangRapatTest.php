<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\RuangRapat;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RuangRapatTest extends TestCase
{
    private function userBidang(): User
    {
        $this->buatKerangka();
        $user = $this->userPegawai(1, 'BIDANG-RUANG-RAPAT');
        $user->update(['role' => 'bidang']);

        return $user;
    }

    private function buatDokumen(User $pengunggah, string $visibility = 'publik'): Dokumen
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->createWithContent('agenda.txt', 'Isi dokumen rapat untuk pengujian.');
        $path = $file->storeAs('dokumen', 'ruang-rapat-'.uniqid().'.txt', 'public');

        return Dokumen::create([
            'judul' => $visibility === 'publik' ? 'Materi Rapat Publik' : 'Arsip Rahasia',
            'visibility' => $visibility,
            'id_bidang' => $pengunggah->id_bidang,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime' => 'text/plain',
            'uploaded_by' => $pengunggah->id,
        ]);
    }

    private function buatRuangRapat(User $pembuat, Dokumen $dokumen, ?string $pin = null, ?string $berlakuSampai = null): RuangRapat
    {
        $ruangRapat = RuangRapat::create([
            'judul' => 'Rapat Koordinasi Uji',
            'id_bidang' => $pembuat->id_bidang,
            'izinkan_unduh_default' => true,
            'berlaku_sampai' => $berlakuSampai ?: now()->addDays(10)->toDateString(),
            'status' => 'aktif',
            'dibuat_oleh' => $pembuat->id,
        ]);

        if ($pin !== null) {
            $ruangRapat->pin_hash = Hash::make($pin);
            $ruangRapat->save();
        }

        $ruangRapat->dokumen()->attach($dokumen->id, [
            'kelompok' => 'materi',
            'izinkan_unduh' => null,
        ]);

        return $ruangRapat;
    }

    public function test_dokumen_rahasia_tidak_dapat_ditambahkan_ke_ruang_rapat(): void
    {
        $user = $this->userBidang();
        $dokumenRahasia = $this->buatDokumen($user, 'rahasia');

        $this->actingAs($user)
            ->from(route('ruang-rapat.create'))
            ->post(route('ruang-rapat.store'), [
                'judul' => 'Rapat Terbatas',
                'berlaku_sampai' => now()->addDays(5)->toDateString(),
                'izinkan_unduh_default' => '1',
                'dokumen' => [$dokumenRahasia->id],
                'kelompok' => [$dokumenRahasia->id => 'agenda'],
            ])
            ->assertRedirect(route('ruang-rapat.create'))
            ->assertSessionHasErrors('dokumen');

        $this->assertDatabaseCount('ruang_rapat', 0);
        $this->assertDatabaseCount('ruang_rapat_dokumen', 0);
    }

    public function test_tamu_tanpa_pin_yang_benar_tidak_dapat_melihat_dokumen(): void
    {
        $user = $this->userBidang();
        $dokumen = $this->buatDokumen($user);
        $ruangRapat = $this->buatRuangRapat($user, $dokumen, '4826');

        $this->assertNotSame('4826', $ruangRapat->fresh()->pin_hash);
        $this->assertTrue($ruangRapat->fresh()->cekPin('4826'));

        $parameter = ['token' => $ruangRapat->token, 'dokumen' => $dokumen->id];
        $this->get(route('rapat.lihat', $parameter))
            ->assertRedirect(route('rapat.buka', ['token' => $ruangRapat->token]));
        $this->get(route('rapat.buka', ['token' => $ruangRapat->token]))
            ->assertOk()
            ->assertSee('Masukkan PIN')
            ->assertDontSee($dokumen->judul);

        $this->post(route('rapat.pin', ['token' => $ruangRapat->token]), ['pin' => '0000'])
            ->assertRedirect(route('rapat.buka', ['token' => $ruangRapat->token]));
        $this->get(route('rapat.buka', ['token' => $ruangRapat->token]))
            ->assertOk()
            ->assertSee('PIN tidak sesuai')
            ->assertDontSee($dokumen->judul);
        $this->get(route('rapat.lihat', $parameter))
            ->assertRedirect(route('rapat.buka', ['token' => $ruangRapat->token]));

        $this->post(route('rapat.pin', ['token' => $ruangRapat->token]), ['pin' => '4826'])
            ->assertRedirect(route('rapat.buka', ['token' => $ruangRapat->token]));
        $this->get(route('rapat.buka', ['token' => $ruangRapat->token]))
            ->assertOk()
            ->assertSee($dokumen->judul);
        $this->get(route('rapat.lihat', $parameter))->assertOk();

        $this->assertSame(1, $ruangRapat->fresh()->dibuka_count);
    }

    public function test_ruang_rapat_yang_lewat_berlaku_sampai_menampilkan_halaman_tidak_aktif(): void
    {
        $user = $this->userBidang();
        $dokumen = $this->buatDokumen($user);
        $ruangRapat = $this->buatRuangRapat(
            $user,
            $dokumen,
            berlakuSampai: now()->subDay()->toDateString()
        );

        $this->get(route('rapat.buka', ['token' => $ruangRapat->token]))
            ->assertStatus(410)
            ->assertSee('Tautan tidak aktif')
            ->assertSee($ruangRapat->judul);
    }
}

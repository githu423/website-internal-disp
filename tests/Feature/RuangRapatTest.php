<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\RuangRapat;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
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

    #[TestWith(['docx', 'docx', 'application/pdf'])]
    #[TestWith(['xlsx', 'xlsx', 'text/plain'])]
    #[TestWith(['xls', 'xlsx', 'application/octet-stream'])]
    public function test_lihat_office_menampilkan_viewer_bukan_navigasi_file(string $ext, string $kind, string $mime): void
    {
        $user = $this->userBidang();
        $dokumen = $this->buatDokumen($user);
        $dokumen->update(['file_name' => 'materi.'.$ext, 'mime' => $mime]);
        $ruang = $this->buatRuangRapat($user, $dokumen);
        $ruang->update(['izinkan_unduh_default' => false]);
        $params = ['token' => $ruang->token, 'dokumen' => $dokumen->id];

        $this->get(route('rapat.lihat', $params))->assertOk()
            ->assertHeader('content-type', 'text/html; charset=utf-8')
            ->assertSee('data-kind="'.$kind.'"', false)
            ->assertSee('data-src="'.route('rapat.raw', $params).'"', false)
            ->assertDontSee('<iframe', false)->assertDontSee('Unduh Dokumen');
        $this->get(route('rapat.raw', $params))->assertOk()
            ->assertHeader('content-disposition', 'inline; filename="materi.'.$ext.'"');
        $this->get(route('rapat.unduh', $params))->assertForbidden();
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

    public function test_pegawai_biasa_dapat_melihat_ruang_rapat_tetapi_tidak_bisa_membuat_atau_mengubah(): void
    {
        $pembuat = $this->userBidang();
        $dokumen = $this->buatDokumen($pembuat);
        $ruangRapat = $this->buatRuangRapat($pembuat, $dokumen);

        $pegawai = $this->userPegawai();

        // Pegawai biasa dapat membuka halaman daftar dan detail
        $this->actingAs($pegawai)
            ->get(route('ruang-rapat.index'))
            ->assertOk()
            ->assertSee($ruangRapat->judul)
            ->assertDontSee('Buat Ruang Rapat');

        $this->actingAs($pegawai)
            ->get(route('ruang-rapat.show', $ruangRapat))
            ->assertOk()
            ->assertSee($ruangRapat->judul)
            ->assertSee('Informasi Rapat')
            ->assertDontSee('Simpan pengaturan')
            ->assertDontSee('Unggah notulen')
            ->assertDontSee('Matikan tautan tamu');

        // Pegawai biasa dilarang mengakses form buat dan aksi mutasi
        $this->actingAs($pegawai)
            ->get(route('ruang-rapat.create'))
            ->assertForbidden();

        $this->actingAs($pegawai)
            ->post(route('ruang-rapat.store'), [
                'judul' => 'Rapat Pegawai Ilegal',
                'berlaku_sampai' => now()->addDays(5)->toDateString(),
                'izinkan_unduh_default' => '1',
                'dokumen' => [$dokumen->id],
                'kelompok' => [$dokumen->id => 'agenda'],
            ])
            ->assertForbidden();

        $this->actingAs($pegawai)
            ->patch(route('ruang-rapat.update', $ruangRapat), [
                'judul' => 'Rapat Diubah Pegawai',
                'berlaku_sampai' => now()->addDays(5)->toDateString(),
                'izinkan_unduh_default' => '1',
            ])
            ->assertForbidden();
    }

    public function test_pegawai_bidang_memiliki_akses_kelola_seperti_admin(): void
    {
        $userBidang = $this->userBidang();
        $dokumen = $this->buatDokumen($userBidang);

        // Pegawai bidang dapat mengakses halaman buat dan memilih bidang penyelenggara
        $this->actingAs($userBidang)
            ->get(route('ruang-rapat.create'))
            ->assertOk()
            ->assertSee('Bidang Penyelenggara');

        // Pegawai bidang dapat membuat ruang rapat untuk bidang lain (id_bidang 2)
        $this->actingAs($userBidang)
            ->post(route('ruang-rapat.store'), [
                'judul' => 'Rapat Kolaborasi Bidang',
                'id_bidang' => 2,
                'berlaku_sampai' => now()->addDays(7)->toDateString(),
                'izinkan_unduh_default' => '1',
                'dokumen' => [$dokumen->id],
                'kelompok' => [$dokumen->id => 'agenda'],
            ])
            ->assertRedirect();

        $ruangRapatBaru = RuangRapat::where('judul', 'Rapat Kolaborasi Bidang')->first();
        $this->assertNotNull($ruangRapatBaru);
        $this->assertSame(2, (int) $ruangRapatBaru->id_bidang);

        // Pegawai bidang dapat memperbarui ruang rapat
        $this->actingAs($userBidang)
            ->patch(route('ruang-rapat.update', $ruangRapatBaru), [
                'judul' => 'Rapat Kolaborasi Bidang - Diperbarui',
                'id_bidang' => 3,
                'berlaku_sampai' => now()->addDays(8)->toDateString(),
                'izinkan_unduh_default' => '1',
            ])
            ->assertRedirect();

        $this->assertSame(3, (int) $ruangRapatBaru->fresh()->id_bidang);
        $this->assertSame('Rapat Kolaborasi Bidang - Diperbarui', $ruangRapatBaru->fresh()->judul);
    }

    public function test_lihat_dokumen_rapat_menampilkan_pratinjau_bukan_mengunduh(): void
    {
        $user = $this->userBidang();
        $dokumen = $this->buatDokumen($user);
        $ruangRapat = $this->buatRuangRapat($user, $dokumen);

        // Akses route lihat: harus menampilkan halaman view pratinjau, bukan attachment unduhan
        $parameter = ['token' => $ruangRapat->token, 'dokumen' => $dokumen->id];
        $response = $this->get(route('rapat.lihat', $parameter));

        $response->assertOk()
            ->assertViewIs('tamu-rapat.lihat')
            ->assertSee('Pratinjau')
            ->assertSee($dokumen->judul);

        // Akses route raw: menyajikan file secara inline
        $rawResponse = $this->get(route('rapat.raw', $parameter));
        $rawResponse->assertOk();
        $this->assertStringContainsString('inline', (string) $rawResponse->headers->get('Content-Disposition'));

        // Akses route unduh: menyajikan file dengan download
        $unduhResponse = $this->get(route('rapat.unduh', $parameter));
        $unduhResponse->assertOk();
        $this->assertStringContainsString('attachment', (string) $unduhResponse->headers->get('Content-Disposition'));
    }
}

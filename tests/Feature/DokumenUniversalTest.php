<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DokumenUniversalTest extends TestCase
{
    private function dok(string $vis, int $uploaderBidang, bool $pdf = false): array
    {
        $user = $this->userPegawai($uploaderBidang);
        $file = $pdf
            ? UploadedFile::fake()->createWithContent('x.pdf', '%PDF-1.4 uji')
            : UploadedFile::fake()->createWithContent('catatan.txt', 'isi');
        $path = $file->storeAs('dokumen', 'uji-' . uniqid(), 'public');
        $d = Dokumen::create([
            'judul' => "Dokumen $vis", 'visibility' => $vis, 'id_bidang' => $uploaderBidang,
            'file_path' => $path, 'file_name' => $file->getClientOriginalName(),
            'file_size' => 10, 'mime' => $pdf ? 'application/pdf' : 'text/plain',
            'uploaded_by' => $user->id,
        ]);
        return [$user, $d];
    }

    public function test_dokumen_bidang_privat_hanya_untuk_sebidang(): void
    {
        $this->buatKerangka();
        [$pemilik, $d] = $this->dok('bidang', 2);
        $orangLain = $this->userPegawai(3);
        $this->actingAs($orangLain)->get('/dokumen')->assertOk()->assertDontSee('Dokumen bidang');
        $this->actingAs($orangLain)->get(route('dokumen.unduh', $d))->assertForbidden();
        $kawan = $this->userPegawai(2, 'KAWAN-DUA');
        $this->actingAs($kawan)->get('/dokumen')->assertOk()->assertSee('Dokumen bidang');
        $this->actingAs($kawan)->get(route('dokumen.unduh', $d))->assertOk();
        // sesama anggota bidang boleh menghapus arsip privat
        $this->actingAs($kawan)->delete(route('dokumen.destroy', $d))->assertRedirect();
        $this->assertDatabaseMissing('dokumen', ['id' => $d->id]);
    }

    public function test_rahasia_tersembunyi_dari_semua_kecuali_pemilik_dan_admin(): void
    {
        $this->buatKerangka();
        [$pemilik, $d] = $this->dok('rahasia', 2);
        $orangLain = $this->userPegawai(3);
        $this->actingAs($orangLain)->get('/dokumen')->assertOk()->assertDontSee('Dokumen rahasia');
        $this->actingAs($orangLain)->get(route('dokumen.show', $d))->assertForbidden();
        $this->actingAs($pemilik)->get('/dokumen')->assertOk()->assertSee('Dokumen rahasia');
        $this->actingAs($this->userAdmin())->get('/dokumen')->assertOk()->assertSee('Dokumen rahasia');
    }

    public function test_hanya_pengunggah_yang_boleh_ubah_akses(): void
    {
        $this->buatKerangka();
        [$pemilik, $d] = $this->dok('rahasia', 2);
        $orangLain = $this->userPegawai(3);
        $this->actingAs($orangLain)->patch(route('dokumen.akses', $d), ['visibility' => 'publik'])->assertForbidden();
        $this->actingAs($pemilik)->patch(route('dokumen.akses', $d), ['visibility' => 'publik'])->assertRedirect();
        $this->assertSame('publik', $d->fresh()->visibility);
    }

    public function test_pratinjau_halaman_dan_file_inline(): void
    {
        $this->buatKerangka();
        [$user, $d] = $this->dok('publik', 2, pdf: true);
        $this->actingAs($user)->get(route('dokumen.show', $d))->assertOk()->assertSee('Unduh');
        $this->actingAs($user)->get(route('dokumen.lihat', $d))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename=x.pdf');
        $this->assertSame(1, $d->fresh()->dibaca_count);
    }

    public function test_filter_bidang_di_halaman_universal(): void
    {
        $this->buatKerangka();
        $this->dok('publik', 2);
        $this->dok('publik', 3);
        $u = $this->userAdmin();
        $this->actingAs($u)->get('/dokumen?bidang=2')->assertOk()->assertSee('Dokumen publik');
        $this->actingAs($u)->get('/dokumen?q=tidak-ada-xyz')->assertOk()->assertSee('Tidak ada dokumen yang cocok');
    }
}

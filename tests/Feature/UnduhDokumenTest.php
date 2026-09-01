<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UnduhDokumenTest extends TestCase
{
    private function dok(?string $vis = 'publik'): array
    {
        $user = $this->userPegawai();
        $file = UploadedFile::fake()->createWithContent('catatan.txt', 'isi dokumen uji');
        $path = $file->storeAs('dokumen', 'uji-' . uniqid(), 'public');
        $d = Dokumen::create([
            'judul' => 'Dok Uji', 'visibility' => $vis ?: 'publik',
            'file_path' => $path, 'file_name' => 'catatan.txt',
            'file_size' => 15, 'mime' => 'text/plain', 'uploaded_by' => $user->id,
        ]);
        return [$user, $d];
    }

    public function test_download_menghasilkan_file_dan_menaikkan_pembaca(): void
    {
        $this->buatKerangka();
        [$user, $d] = $this->dok();
        $this->actingAs($user);
        $res = $this->get(route('dokumen.unduh', $d));
        $res->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=catatan.txt')
            ->assertStreamedContent('isi dokumen uji');
        $this->assertSame(1, $d->fresh()->dibaca_count);
    }

    public function test_rahasia_orang_lain_ditolak(): void
    {
        $this->buatKerangka();
        [$pemilik, $d] = $this->dok('rahasia');
        $orangLain = $this->userPegawai(2);
        $this->actingAs($orangLain)->get(route('dokumen.unduh', $d))->assertForbidden();
        $this->actingAs($pemilik)->get(route('dokumen.unduh', $d))->assertOk();
    }

    public function test_upload_lalu_unduh_penuh_flow(): void
    {
        $this->buatKerangka();
        $user = $this->userPegawai();
        $user->update(['role' => 'bidang']); // unggah kini khusus bidang/admin
        $this->actingAs($user)->post('/dokumen', [
            'judul' => 'Laporan Uji', 'visibility' => 'publik',
            'file' => UploadedFile::fake()->createWithContent('laporan.txt', 'halo'),
        ])->assertRedirect();
        $d = Dokumen::latest('id')->first();
        $this->assertNotNull($d);
        $this->actingAs($user)->get(route('dokumen.unduh', $d))
            ->assertOk()->assertStreamedContent('halo');
        Storage::disk('public')->delete($d->file_path);
    }
}

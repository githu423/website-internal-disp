<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\RuangRapat;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BatasUnggahDokumenTest extends TestCase
{
    public static function jalurDanUkuran(): array
    {
        $cases = [];
        foreach (['dokumen', 'bidang', 'notulen'] as $jalur) {
            foreach ([10241, 51200, 51201] as $ukuran) {
                $cases[$jalur.'-'.$ukuran.'KB'] = [$jalur, $ukuran];
            }
        }

        return $cases;
    }

    #[DataProvider('jalurDanUkuran')]
    public function test_batas_50_mb_di_semua_jalur_unggah(string $jalur, int $ukuran): void
    {
        Storage::fake('public');
        $this->buatKerangka();
        $user = $this->userAdmin();
        $ruang = RuangRapat::create([
            'judul' => 'Rapat Uji Ukuran', 'id_bidang' => 1,
            'berlaku_sampai' => now()->addDays(5)->toDateString(),
            'status' => 'aktif', 'dibuat_oleh' => $user->id,
        ]);
        $url = match ($jalur) {
            'dokumen' => route('dokumen.store'),
            'bidang' => route('bidang.dokumen', 1),
            'notulen' => route('ruang-rapat.notulen.upload', $ruang),
        };
        $file = UploadedFile::fake()->create('materi.pdf', $ukuran, 'application/pdf');

        $response = $this->actingAs($user)->from('/dashboard')->post($url, [
            'judul' => 'Dokumen Uji Batas', 'visibility' => 'publik', 'file' => $file,
        ]);

        $response->assertRedirect();
        if ($ukuran > 51200) {
            $response->assertSessionHasErrors(['file' => 'Ukuran dokumen maksimal 50 MB.']);
            $this->assertDatabaseCount('dokumen', 0);
            $this->assertSame([], Storage::disk('public')->allFiles());
        } else {
            $response->assertSessionHasNoErrors();
            $this->assertDatabaseHas('dokumen', ['judul' => 'Dokumen Uji Batas', 'file_size' => $ukuran * 1024]);
            $dokumen = Dokumen::firstOrFail();
            Storage::disk('public')->assertExists($dokumen->file_path);
            if ($jalur === 'notulen') {
                $this->assertDatabaseHas('ruang_rapat_dokumen', [
                    'id_ruang_rapat' => $ruang->id, 'id_dokumen' => $dokumen->id, 'kelompok' => 'notulen',
                ]);
            }
        }
    }
}

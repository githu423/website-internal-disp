<?php

namespace App\Http\Controllers;

use App\Models\Dokumen;
use App\Models\LogAktivitas;
use App\Models\RuangRapat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman rapat untuk tamu eksternal (tanpa login).
 *
 * Tamu membuka tautan khusus berbentuk token, misalnya /rapat/{token}.
 * - Tautan yang sudah kedaluwarsa menampilkan halaman "tautan tidak aktif".
 * - Tautan ber-PIN hanya bisa diakses setelah PIN diverifikasi (disimpan di sesi).
 * - Hanya dokumen ber-visibility "publik" yang boleh ditampilkan ke tamu.
 */
class TamuRapatController extends Controller
{
    /**
     * Halaman utama tautan rapat publik.
     */
    public function buka(Request $request, string $token): View|Response
    {
        // ===== 1. CARI RUANG RAPAT =====
        $ruangRapat = $this->cariRuangRapat($token)->load('bidang');

        // ===== 2. CEK KEDALUWARSA =====
        if ($ruangRapat->sudahKedaluwarsa()) {
            return $this->tampilkanTautanTidakAktif($ruangRapat);
        }

        // ===== 3. CEK PIN =====
        if ($ruangRapat->pin_hash && ! $this->pinTerverifikasi($request, $ruangRapat)) {
            return view('tamu-rapat.pin', compact('ruangRapat'));
        }

        // ===== 4. TAMPILKAN DAFTAR DOKUMEN PUBLIK =====
        $ruangRapat->load(['dokumen' => fn ($query) => $query->where('dokumen.visibility', 'publik')->orderBy('judul')]);

        return view('tamu-rapat.buka', compact('ruangRapat'));
    }

    /**
     * Memverifikasi PIN yang dikirim tamu, lalu menyimpan status di sesi.
     */
    public function verifikasiPin(Request $request, string $token): RedirectResponse
    {
        // ===== 1. CARI RUANG RAPAT =====
        $ruangRapat = $this->cariRuangRapat($token);

        // ===== 2. KEMBALI JIKA KEDALUWARSA / TANPA PIN =====
        if ($ruangRapat->sudahKedaluwarsa() || ! $ruangRapat->pin_hash) {
            return redirect()->route('rapat.buka', ['token' => $token]);
        }

        // ===== 3. VALIDASI PIN =====
        $request->query->remove('pin');
        $pinInput = $request->input('pin');
        $request->merge(['pin' => null]);
        $data = Validator::make(['pin' => $pinInput], [
            'pin' => ['required', 'string', 'max:64'],
        ])->validate();

        // ===== 4. COCOKKAN PIN =====
        if (! $ruangRapat->cekPin($data['pin'])) {
            return redirect()->route('rapat.buka', ['token' => $token])
                ->withErrors(['pin' => 'PIN tidak sesuai. Silakan periksa kembali.']);
        }

        // ===== 5. SIMPAN STATUS VERIFIKASI DI SESI =====
        $request->session()->put(
            $this->sessionKey($token),
            hash('sha256', $ruangRapat->pin_hash)
        );

        return redirect()->route('rapat.buka', ['token' => $token]);
    }

    /**
     * Halaman pratinjau dokumen rapat untuk tamu (Word/Excel dirender di browser).
     */
    public function lihatDokumen(Request $request, string $token, string $dokumen): View|Response
    {
        // ===== 1. CARI RUANG RAPAT & CEK AKSES =====
        $ruangRapat = $this->cariRuangRapat($token);
        if ($ruangRapat->sudahKedaluwarsa()) {
            return $this->tampilkanTautanTidakAktif($ruangRapat);
        }
        if (! $this->pinTerverifikasi($request, $ruangRapat)) {
            return redirect()->route('rapat.buka', ['token' => $token]);
        }

        // ===== 2. AMBIL DOKUMEN PUBLIK & BERKAS =====
        $dokumenModel = $this->dokumenPublikDiRuang($ruangRapat, $dokumen);
        $path = Storage::disk('public')->path($dokumenModel->file_path);
        abort_unless(is_file($path), 404, 'Berkas tidak ditemukan.');

        // ===== 3. CATAT AKTIVITAS =====
        $ruangRapat->increment('dibuka_count');
        $dokumenModel->increment('dibaca_count');
        LogAktivitas::catat('lihat dokumen ruang rapat', Str::limit($ruangRapat->judul.' · '.$dokumenModel->judul, 255, ''));

        // ===== 4. SIAPKAN PRATINJAU =====
        $mime = (string) ($dokumenModel->mime ?: mime_content_type($path) ?: '');
        $isiTeks = null;
        if ((str_starts_with($mime, 'text/') || in_array($mime, ['application/json', 'application/xml'], true)) && filesize($path) < 512000) {
            $isiTeks = file_get_contents($path);
        }

        // ===== 5. Tentukan hak unduh =====
        $bolehUnduh = $dokumenModel->pivot->izinkan_unduh === null
            ? $ruangRapat->izinkan_unduh_default
            : (bool) $dokumenModel->pivot->izinkan_unduh;

        // ===== 6. TAMPILKAN PRATINJAU =====
        return view('tamu-rapat.lihat', [
            'ruangRapat' => $ruangRapat,
            'dokumen' => $dokumenModel,
            'mime' => $mime,
            'isiTeks' => $isiTeks,
            'bolehUnduh' => $bolehUnduh,
        ]);
    }

    /**
     * Mengirim berkas secara inline (tanpa memaksa unduh) untuk <iframe>/<img>.
     */
    public function rawDokumen(Request $request, string $token, string $dokumen): Response
    {
        // ===== 1. CARI RUANG RAPAT & CEK AKSES =====
        $ruangRapat = $this->cariRuangRapat($token);
        if ($ruangRapat->sudahKedaluwarsa()) {
            return $this->tampilkanTautanTidakAktif($ruangRapat);
        }
        if (! $this->pinTerverifikasi($request, $ruangRapat)) {
            return redirect()->route('rapat.buka', ['token' => $token]);
        }

        // ===== 2. AMBIL DOKUMEN PUBLIK & BERKAS =====
        $dokumenModel = $this->dokumenPublikDiRuang($ruangRapat, $dokumen);
        $path = Storage::disk('public')->path($dokumenModel->file_path);
        abort_unless(is_file($path), 404, 'Berkas tidak ditemukan.');

        // ===== 3. KIRIM BERKAS SECARA INLINE =====
        $mime = $dokumenModel->mime ?: (mime_content_type($path) ?: 'application/octet-stream');
        $namaBerkas = preg_replace('/[^A-Za-z0-9._-]/', '_', $dokumenModel->file_name);

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$namaBerkas.'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Mengunduh dokumen rapat (hanya jika unduhan diizinkan).
     */
    public function unduhDokumen(Request $request, string $token, string $dokumen): Response
    {
        // ===== 1. CARI RUANG RAPAT & CEK AKSES =====
        $ruangRapat = $this->cariRuangRapat($token);
        if ($ruangRapat->sudahKedaluwarsa()) {
            return $this->tampilkanTautanTidakAktif($ruangRapat);
        }
        if (! $this->pinTerverifikasi($request, $ruangRapat)) {
            return redirect()->route('rapat.buka', ['token' => $token]);
        }

        // ===== 2. AMBIL DOKUMEN & CEK IJIN UNDUH =====
        $dokumenModel = $this->dokumenPublikDiRuang($ruangRapat, $dokumen);
        $izinkanUnduh = $dokumenModel->pivot->izinkan_unduh === null
            ? $ruangRapat->izinkan_unduh_default
            : (bool) $dokumenModel->pivot->izinkan_unduh;
        abort_unless($izinkanUnduh, 403, 'Unduhan tidak diizinkan untuk dokumen ini.');

        // ===== 3. KIRIM BERKAS =====
        $path = Storage::disk('public')->path($dokumenModel->file_path);
        abort_unless(is_file($path), 404, 'Berkas tidak ditemukan.');

        $ruangRapat->increment('dibuka_count');
        $dokumenModel->increment('dibaca_count');
        LogAktivitas::catat('unduh dokumen ruang rapat', Str::limit($ruangRapat->judul.' · '.$dokumenModel->judul, 255, ''));

        return response()->download($path, $dokumenModel->file_name, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Mencari ruang rapat berdasarkan token tautan; gagal jika tidak ada.
     */
    private function cariRuangRapat(string $token): RuangRapat
    {
        return RuangRapat::query()->where('token', $token)->firstOrFail();
    }

    /**
     * Apakah tamu boleh masuk: sudah login, tautan tanpa PIN, atau PIN sudah diverifikasi di sesi.
     */
    private function pinTerverifikasi(Request $request, RuangRapat $ruangRapat): bool
    {
        if (auth()->check()) {
            return true;
        }

        if (! $ruangRapat->pin_hash) {
            return true;
        }

        $tersimpan = $request->session()->get($this->sessionKey($ruangRapat->token));
        $fingerprint = hash('sha256', $ruangRapat->pin_hash);

        return is_string($tersimpan) && hash_equals($fingerprint, $tersimpan);
    }

    /**
     * Mengambil dokumen publik yang tertaut ke ruang rapat ini.
     *
     * Jaga tautan yang sudah dibuat agar tidak membuka dokumen yang kemudian
     * diubah menjadi privat/rahasia; dokumen tersebut sejak awal tetap ditolak
     * oleh validasi controller staf.
     */
    private function dokumenPublikDiRuang(RuangRapat $ruangRapat, string $id): Dokumen
    {
        $dokumen = $ruangRapat->dokumen()
            ->where('dokumen.id', (int) $id)
            ->firstOrFail();

        abort_unless($dokumen->visibility === 'publik', 404);

        return $dokumen;
    }

    /**
     * Respons "tautan tidak aktif" (HTTP 410 Gone).
     */
    private function tampilkanTautanTidakAktif(RuangRapat $ruangRapat): Response
    {
        return response()->view('tamu-rapat.tidak-aktif', compact('ruangRapat'), 410);
    }

    /**
     * Kunci sesi untuk menyimpan status verifikasi PIN per tautan.
     */
    private function sessionKey(string $token): string
    {
        return 'ruang_rapat.pin.'.hash('sha256', $token);
    }
}

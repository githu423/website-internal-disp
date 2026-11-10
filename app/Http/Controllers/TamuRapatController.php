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

class TamuRapatController extends Controller
{
    public function buka(Request $request, string $token): View|Response
    {
        $ruangRapat = $this->cariRuangRapat($token)->load('bidang');

        if ($ruangRapat->sudahKedaluwarsa()) {
            return $this->tampilkanTautanTidakAktif($ruangRapat);
        }

        if ($ruangRapat->pin_hash && ! $this->pinTerverifikasi($request, $ruangRapat)) {
            return view('tamu-rapat.pin', compact('ruangRapat'));
        }

        $ruangRapat->load(['dokumen' => fn ($query) => $query->where('dokumen.visibility', 'publik')->orderBy('judul')]);

        return view('tamu-rapat.buka', compact('ruangRapat'));
    }

    public function verifikasiPin(Request $request, string $token): RedirectResponse
    {
        $ruangRapat = $this->cariRuangRapat($token);

        if ($ruangRapat->sudahKedaluwarsa() || ! $ruangRapat->pin_hash) {
            return redirect()->route('rapat.buka', ['token' => $token]);
        }

        $request->query->remove('pin');
        $pinInput = $request->input('pin');
        $request->merge(['pin' => null]);
        $data = Validator::make(['pin' => $pinInput], [
            'pin' => ['required', 'string', 'max:64'],
        ])->validate();

        if (! $ruangRapat->cekPin($data['pin'])) {
            return redirect()->route('rapat.buka', ['token' => $token])
                ->withErrors(['pin' => 'PIN tidak sesuai. Silakan periksa kembali.']);
        }

        $request->session()->put(
            $this->sessionKey($token),
            hash('sha256', $ruangRapat->pin_hash)
        );

        return redirect()->route('rapat.buka', ['token' => $token]);
    }

    public function lihatDokumen(Request $request, string $token, string $dokumen): View|Response
    {
        $ruangRapat = $this->cariRuangRapat($token);

        if ($ruangRapat->sudahKedaluwarsa()) {
            return $this->tampilkanTautanTidakAktif($ruangRapat);
        }

        if (! $this->pinTerverifikasi($request, $ruangRapat)) {
            return redirect()->route('rapat.buka', ['token' => $token]);
        }

        $dokumenModel = $this->dokumenPublikDiRuang($ruangRapat, $dokumen);
        $path = Storage::disk('public')->path($dokumenModel->file_path);
        abort_unless(is_file($path), 404, 'Berkas tidak ditemukan.');

        $ruangRapat->increment('dibuka_count');
        $dokumenModel->increment('dibaca_count');
        LogAktivitas::catat('lihat dokumen ruang rapat', Str::limit($ruangRapat->judul.' · '.$dokumenModel->judul, 255, ''));

        $mime = (string) ($dokumenModel->mime ?: mime_content_type($path) ?: '');
        $isiTeks = null;
        if ((str_starts_with($mime, 'text/') || in_array($mime, ['application/json', 'application/xml'], true)) && filesize($path) < 512000) {
            $isiTeks = file_get_contents($path);
        }

        $bolehUnduh = $dokumenModel->pivot->izinkan_unduh === null
            ? $ruangRapat->izinkan_unduh_default
            : (bool) $dokumenModel->pivot->izinkan_unduh;

        return view('tamu-rapat.lihat', [
            'ruangRapat' => $ruangRapat,
            'dokumen' => $dokumenModel,
            'mime' => $mime,
            'isiTeks' => $isiTeks,
            'bolehUnduh' => $bolehUnduh,
        ]);
    }

    public function rawDokumen(Request $request, string $token, string $dokumen): Response
    {
        $ruangRapat = $this->cariRuangRapat($token);

        if ($ruangRapat->sudahKedaluwarsa()) {
            return $this->tampilkanTautanTidakAktif($ruangRapat);
        }

        if (! $this->pinTerverifikasi($request, $ruangRapat)) {
            return redirect()->route('rapat.buka', ['token' => $token]);
        }

        $dokumenModel = $this->dokumenPublikDiRuang($ruangRapat, $dokumen);
        $path = Storage::disk('public')->path($dokumenModel->file_path);
        abort_unless(is_file($path), 404, 'Berkas tidak ditemukan.');

        $mime = $dokumenModel->mime ?: (mime_content_type($path) ?: 'application/octet-stream');
        $namaBerkas = preg_replace('/[^A-Za-z0-9._-]/', '_', $dokumenModel->file_name);

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$namaBerkas.'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function unduhDokumen(Request $request, string $token, string $dokumen): Response
    {
        $ruangRapat = $this->cariRuangRapat($token);

        if ($ruangRapat->sudahKedaluwarsa()) {
            return $this->tampilkanTautanTidakAktif($ruangRapat);
        }

        if (! $this->pinTerverifikasi($request, $ruangRapat)) {
            return redirect()->route('rapat.buka', ['token' => $token]);
        }

        $dokumenModel = $this->dokumenPublikDiRuang($ruangRapat, $dokumen);
        $izinkanUnduh = $dokumenModel->pivot->izinkan_unduh === null
            ? $ruangRapat->izinkan_unduh_default
            : (bool) $dokumenModel->pivot->izinkan_unduh;
        abort_unless($izinkanUnduh, 403, 'Unduhan tidak diizinkan untuk dokumen ini.');

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

    private function cariRuangRapat(string $token): RuangRapat
    {
        return RuangRapat::query()->where('token', $token)->firstOrFail();
    }

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

    private function dokumenPublikDiRuang(RuangRapat $ruangRapat, string $id): Dokumen
    {
        $dokumen = $ruangRapat->dokumen()
            ->where('dokumen.id', (int) $id)
            ->firstOrFail();

        // Jaga tautan yang sudah dibuat agar tidak membuka dokumen yang kemudian
        // diubah menjadi privat/rahasia; dokumen tersebut sejak awal tetap ditolak
        // oleh validasi controller staf.
        abort_unless($dokumen->visibility === 'publik', 404);

        return $dokumen;
    }

    private function tampilkanTautanTidakAktif(RuangRapat $ruangRapat): Response
    {
        return response()->view('tamu-rapat.tidak-aktif', compact('ruangRapat'), 410);
    }

    private function sessionKey(string $token): string
    {
        return 'ruang_rapat.pin.'.hash('sha256', $token);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Bidang;
use App\Models\Dokumen;
use App\Models\KategoriDokumen;
use App\Models\LogAktivitas;
use App\Services\KategoriDokumenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengelolaan dokumen / arsip.
 *
 * Aturan hak akses dokumen:
 * - "publik"  : dapat dilihat semua pengguna yang masuk aplikasi.
 * - "bidang"  : hanya anggota bidang terkait (dan admin).
 * - "rahasia" : hanya pengunggah (dan admin).
 *
 * Batas ukuran berkas: 50 MB per dokumen.
 */
class DokumenController extends Controller
{
    /**
     * Halaman dokumen: menampilkan dokumen yang boleh dilihat pengguna,
     * dengan filter pencarian, kategori, bidang, dan "milik saya".
     */
    public function index(Request $request): View
    {
        $pengguna = $request->user();

        // ===== 1. QUERY DASAR + HAK AKSES =====
        // Halaman universal: semua dokumen lintas bidang "tinggal cari".
        $query = Dokumen::query()->with(['bidang', 'uploader', 'kategoriRel.induk.induk']);
        $query->where(function ($queryAkses) use ($pengguna) {
            $queryAkses->where('visibility', 'publik')->orWhere('uploaded_by', $pengguna->id);
            if ($pengguna->id_bidang) {
                $queryAkses->orWhere(function ($queryBidang) use ($pengguna) {
                    $queryBidang->where('visibility', 'bidang')->where(function ($queryPemilik) use ($pengguna) {
                        $queryPemilik->where('id_bidang', $pengguna->id_bidang)->orWhereNull('id_bidang');
                    });
                });
            }
            if ($pengguna->isAdmin()) {
                $queryAkses->orWhereRaw('1=1');
            }
        });

        // ===== 2. FILTER PENCARIAN =====
        if ($kataKunci = trim((string) $request->query('q'))) {
            $query->where(function ($queryCari) use ($kataKunci) {
                $queryCari->where('judul', 'like', "%$kataKunci%")
                    ->orWhere('deskripsi', 'like', "%$kataKunci%")
                    ->orWhere('kategori', 'like', "%$kataKunci%")
                    ->orWhere('file_name', 'like', "%$kataKunci%")
                    ->orWhereHas('kategoriRel', fn ($queryKategori) => $queryKategori->where('nama', 'like', "%$kataKunci%"));
            });
        }

        // ===== 3. FILTER KATEGORI =====
        if ($kategoriId = $request->query('kategori')) {
            // Filter mencakup subkategori: dokumen yang kategorinya persis sama
            // ATAU berada di bawah kategori tersebut (anak/cucu) ikut tampil.
            $kategoriAktif = KategoriDokumen::find((int) $kategoriId);
            if ($kategoriAktif) {
                $query->where(function ($queryKategori) use ($kategoriAktif) {
                    $queryKategori->where('id_kategori', $kategoriAktif->id);
                    $keturunan = $kategoriAktif->anak->pluck('id');
                    foreach ($kategoriAktif->anak as $anak) {
                        $keturunan = $keturunan->merge($anak->anak->pluck('id'));
                    }
                    if ($keturunan->isNotEmpty()) {
                        $queryKategori->orWhereIn('id_kategori', $keturunan->all());
                    }
                });
            } elseif (! ctype_digit((string) $kategoriId)) {
                // Fallback bookmark/link lama yang masih memakai nama kategori (teks),
                // bukan id: cocokkan nama kategori maupun kolom teks bawaan dokumen.
                $query->where(function ($queryKategori) use ($kategoriId) {
                    $queryKategori->where('kategori', $kategoriId)
                        ->orWhereHas('kategoriRel', fn ($queryNama) => $queryNama->where('nama', $kategoriId));
                });
            } else {
                $query->where('id_kategori', (int) $kategoriId);
            }
        }

        // ===== 4. FILTER BIDANG & "MILIK SAYA" =====
        if ($bidangId = $request->query('bidang')) {
            $query->where('id_bidang', (int) $bidangId);
        }
        if ($request->query('milik_saya')) {
            $query->where('uploaded_by', $pengguna->id);
        }

        // ===== 5. HASIL & DATA PENDUKUNG =====
        $dokumen = $query->latest()->paginate(12)->withQueryString();
        $kategori = KategoriDokumen::with('anak.anak')->orderBy('nama')->get();
        $bidangs = Bidang::query()->withCount(['dokumen' => fn ($queryHitung) => $queryHitung->where('visibility', '!=', 'rahasia')])->orderBy('id')->get();
        $total = (clone $query)->count();

        return view('dokumen.index', compact('dokumen', 'kategori', 'bidangs', 'total'));
    }

    /**
     * Menyimpan dokumen baru (unggah berkas).
     * Hanya pengelola bidang / admin yang boleh mengunggah.
     */
    public function store(Request $request): RedirectResponse
    {
        // ===== PEMERIKSAAN AKSES =====
        abort_unless($request->user()->isBisaUnggah(), 403, 'Pengunggahan dokumen dilakukan oleh pengelola bidang / admin.');

        // ===== VALIDASI INPUT (termasuk batas 50 MB) =====
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:180'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'kategori' => ['nullable', 'string', 'max:180'],
            'id_kategori' => ['nullable', 'integer', 'exists:kategori_dokumen,id'],
            'visibility' => ['required', 'in:publik,bidang,rahasia'],
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg,txt',
                'max:51200', // 50 MB (satuan KB)
                function ($attribute, $value, $fail) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    $allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'png', 'jpg', 'jpeg', 'txt'];
                    if (! in_array($ext, $allowedExt, true)) {
                        $fail('Ekstensi file tidak diizinkan. Hanya format PDF, Word, Excel, PowerPoint, Gambar, atau Teks (.txt) yang diperbolehkan.');
                    }
                },
            ],
        ], ['file.max' => 'Ukuran dokumen maksimal 50 MB.']);

        // ===== PENYIMPANAN BERKAS =====
        $file = $request->file('file');
        $path = $file->storeAs('dokumen', time().'_'.preg_replace('/[^A-Za-z0-9._-]/', '_', $file->hashName()), 'public');

        // ===== PENENTUAN KATEGORI =====
        // Kategori bertingkat diutamakan; input teks berjenjang didukung sebagai fallback.
        $idKategori = isset($data['id_kategori']) && $data['id_kategori'] !== '' ? (int) $data['id_kategori'] : null;
        $kategoriTeks = isset($data['kategori']) ? trim((string) $data['kategori']) : '';
        if ($idKategori === null && $kategoriTeks !== '') {
            $idKategori = KategoriDokumenService::dapatkanAtauBuatBerjenjang($kategoriTeks)->id;
        }

        // ===== PENYIMPANAN DATA DOKUMEN =====
        $dokumenBaru = Dokumen::create([
            'judul' => $data['judul'], 'deskripsi' => $data['deskripsi'] ?? null,
            'kategori' => $idKategori ? null : ($kategoriTeks !== '' ? $kategoriTeks : null),
            'id_kategori' => $idKategori, 'visibility' => $data['visibility'],
            'id_bidang' => $request->user()->id_bidang,
            'file_path' => $path, 'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(), 'mime' => $file->getClientMimeType(),
            'uploaded_by' => $request->user()->id,
        ]);

        // ===== CATAT LOG & RESPONS =====
        LogAktivitas::catat('upload dokumen', $dokumenBaru->judul);

        return back()->with('sukses', 'Dokumen berhasil diunggah.');
    }

    /**
     * Mengunduh dokumen (dipakai tombol "Unduh").
     */
    public function show(Request $request, Dokumen $dokumen): Response
    {
        // ===== PEMERIKSAAN AKSES & KEBERADAAN BERKAS =====
        abort_unless($dokumen->bolehDilihat($request->user()), 403);
        $dokumen->increment('dibaca_count');
        $abs = Storage::disk('public')->path($dokumen->file_path);
        abort_unless(is_file($abs), 404, 'Berkas tidak ditemukan.');

        // ===== RESPONS: UNGGAH BERKAS =====
        return response()->download($abs, $dokumen->file_name);
    }

    /**
     * Halaman pratinjau: pdf/gambar/teks tampil inline, format office menawarkan unduh.
     */
    public function buka(Request $request, Dokumen $dokumen): View
    {
        // ===== PEMERIKSAAN AKSES & KEBERADAAN BERKAS =====
        abort_unless($dokumen->bolehDilihat($request->user()), 403);
        $abs = Storage::disk('public')->path($dokumen->file_path);
        abort_unless(is_file($abs), 404, 'Berkas tidak ditemukan.');
        $dokumen->increment('dibaca_count');

        // ===== PERSIAPAN DATA PRATINJAU =====
        $mime = (string) ($dokumen->mime ?: mime_content_type($abs) ?: '');
        $isiTeks = null;
        if ((str_starts_with($mime, 'text/') || in_array($mime, ['application/json', 'application/xml'])) && filesize($abs) < 512000) {
            $isiTeks = file_get_contents($abs);
        }

        // ===== RESPONS: TAMPILKAN HALAMAN PRATINJAU =====
        return view('dokumen.lihat', ['d' => $dokumen, 'u' => $request->user(), 'isiTeks' => $isiTeks]);
    }

    /**
     * Pratinjau inline di browser (pdf/gambar/teks), tanpa memaksa download — dipakai src iframe/gambar.
     */
    public function lihat(Request $request, Dokumen $dokumen): Response
    {
        // ===== PEMERIKSAAN AKSES & KEBERADAAN BERKAS =====
        abort_unless($dokumen->bolehDilihat($request->user()), 403);
        $abs = Storage::disk('public')->path($dokumen->file_path);
        abort_unless(is_file($abs), 404, 'Berkas tidak ditemukan.');
        $mime = $dokumen->mime ?: (mime_content_type($abs) ?: 'application/octet-stream');

        // ===== RESPONS: TAMPILKAN BERKAS SECARA INLINE =====
        return response()->file($abs, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename='.preg_replace('/[^A-Za-z0-9._-]/', '_', $dokumen->file_name),
        ]);
    }

    /**
     * Pemilik upload saja yang boleh mengubah akses (publik / bidang / rahasia).
     */
    public function ubahAkses(Request $request, Dokumen $dokumen): RedirectResponse
    {
        // ===== PEMERIKSAAN AKSES =====
        $pengguna = $request->user();
        abort_unless($pengguna->id === $dokumen->uploaded_by, 403, 'Hanya pengunggah yang dapat mengubah akses dokumen ini.');

        // ===== VALIDASI & SIMPAN =====
        $data = $request->validate(['visibility' => ['required', 'in:publik,bidang,rahasia']]);
        $dokumen->update($data);

        // ===== CATAT LOG & RESPONS =====
        LogAktivitas::catat('ubah akses dokumen', $dokumen->judul.' -> '.$data['visibility']);

        return back()->with('sukses', 'Akses dokumen diubah menjadi '.$data['visibility'].'.');
    }

    /**
     * Menghapus dokumen (pengunggah, sesama anggota bidang, atau admin).
     */
    public function destroy(Request $request, Dokumen $dokumen): RedirectResponse
    {
        // ===== PEMERIKSAAN AKSES =====
        $pengguna = $request->user();
        $anggotaBidang = $dokumen->visibility === 'bidang' && $dokumen->id_bidang && $pengguna->id_bidang === $dokumen->id_bidang;
        abort_unless($pengguna->isAdmin() || $dokumen->uploaded_by === $pengguna->id || $anggotaBidang, 403, 'Hanya pengunggah, sesama anggota bidang, atau admin.');

        // ===== HAPUS BERKAS & DATA =====
        Storage::disk('public')->delete($dokumen->file_path);
        $dokumen->delete();

        // ===== CATAT LOG & RESPONS =====
        LogAktivitas::catat('hapus dokumen', $dokumen->judul);

        return back()->with('sukses', 'Dokumen dihapus.');
    }
}

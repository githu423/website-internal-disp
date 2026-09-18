<?php

namespace App\Http\Controllers;

use App\Models\Dokumen;
use App\Models\LogAktivitas;
use App\Services\NotifikasiService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DokumenController extends Controller
{
    public function index(Request $request): View
    {
        $u = $request->user();
        // halaman universal: semua dokumen lintas bidang "tinggal cari";
        // 'rahasia' hanya utk pengunggah (+admin); 'bidang' = privat hanya utk sebidang (+admin)
        $q = Dokumen::query()->with(['bidang', 'uploader']);
        $q->where(function ($w) use ($u) {
            $w->where('visibility', 'publik')->orWhere('uploaded_by', $u->id);
            if ($u->id_bidang) {
                $w->orWhere(function ($x) use ($u) {
                    $x->where('visibility', 'bidang')->where(function ($y) use ($u) {
                        $y->where('id_bidang', $u->id_bidang)->orWhereNull('id_bidang');
                    });
                });
            }
            if ($u->isAdmin()) $w->orWhereRaw('1=1');
        });

        if ($sq = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('judul', 'like', "%$sq%")->orWhere('deskripsi', 'like', "%$sq%")->orWhere('kategori', 'like', "%$sq%")->orWhere('file_name', 'like', "%$sq%"));
        }
        if ($k = $request->query('kategori')) $q->where('kategori', $k);
        if ($b = $request->query('bidang')) $q->where('id_bidang', (int) $b);
        if ($request->query('milik_saya')) $q->where('uploaded_by', $u->id);

        $dokumen = $q->latest()->paginate(12)->withQueryString();
        $kategori = Dokumen::whereNotNull('kategori')->distinct()->orderBy('kategori')->pluck('kategori');
        $bidangs = \App\Models\Bidang::query()->withCount(['dokumen' => fn ($qq) => $qq->where('visibility', '!=', 'rahasia')])->orderBy('id')->get();
        $total = (clone $q)->count();

        return view('dokumen.index', compact('dokumen', 'kategori', 'bidangs', 'total'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isBisaUnggah(), 403, 'Pengunggahan dokumen dilakukan oleh pengelola bidang / admin.');
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:180'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'kategori' => ['nullable', 'string', 'max:60'],
            'visibility' => ['required', 'in:publik,bidang,rahasia'],
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg,txt',
                'max:10240',
                function ($attribute, $value, $fail) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    $allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'png', 'jpg', 'jpeg', 'txt'];
                    if (!in_array($ext, $allowedExt, true)) {
                        $fail('Ekstensi file tidak diizinkan. Hanya format PDF, Word, Excel, PowerPoint, Gambar, atau Teks (.txt) yang diperbolehkan.');
                    }
                },
            ],
        ]);
        $file = $request->file('file');
        $path = $file->storeAs('dokumen', time().'_'.preg_replace('/[^A-Za-z0-9._-]/', '_', $file->hashName()), 'public');

        $d = Dokumen::create([
            'judul' => $data['judul'], 'deskripsi' => $data['deskripsi'] ?? null,
            'kategori' => $data['kategori'] ?? null, 'visibility' => $data['visibility'],
            'id_bidang' => $request->user()->id_bidang,
            'file_path' => $path, 'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(), 'mime' => $file->getClientMimeType(),
            'uploaded_by' => $request->user()->id,
        ]);
        LogAktivitas::catat('upload dokumen', $d->judul);
        return back()->with('sukses', 'Dokumen berhasil diunggah.');
    }

    public function show(Request $request, Dokumen $dokumen): Response
    {
        abort_unless($dokumen->bolehDilihat($request->user()), 403);
        $dokumen->increment('dibaca_count');
        $abs = Storage::disk('public')->path($dokumen->file_path);
        abort_unless(is_file($abs), 404, 'Berkas tidak ditemukan.');
        return response()->download($abs, $dokumen->file_name);
    }

    /** halaman pratinjau: pdf/gambar/teks tampil inline, format office menawarkan unduh */
    public function buka(Request $request, Dokumen $dokumen): \Illuminate\View\View
    {
        $d = $dokumen;
        abort_unless($d->bolehDilihat($request->user()), 403);
        $abs = Storage::disk('public')->path($d->file_path);
        abort_unless(is_file($abs), 404, 'Berkas tidak ditemukan.');
        $d->increment('dibaca_count');
        $mime = (string) ($d->mime ?: mime_content_type($abs) ?: '');
        $isiTeks = null;
        if ((str_starts_with($mime, 'text/') || in_array($mime, ['application/json', 'application/xml'])) && filesize($abs) < 512000) {
            $isiTeks = file_get_contents($abs);
        }
        return view('dokumen.lihat', ['d' => $d, 'u' => $request->user(), 'isiTeks' => $isiTeks]);
    }

    /** pratinjau inline di browser (pdf/gambar/teks), tanpa memaksa download — dipakai src iframe/gambar */
    public function lihat(Request $request, Dokumen $dokumen): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless($dokumen->bolehDilihat($request->user()), 403);
        $abs = Storage::disk('public')->path($dokumen->file_path);
        abort_unless(is_file($abs), 404, 'Berkas tidak ditemukan.');
        $mime = $dokumen->mime ?: (mime_content_type($abs) ?: 'application/octet-stream');
        return response()->file($abs, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename=' . preg_replace('/[^A-Za-z0-9._-]/', "_", $dokumen->file_name),
        ]);
    }

    /** pemilik upload saja yang boleh mengubah akses (publik / bidang / rahasia) */
    public function ubahAkses(Request $request, Dokumen $dokumen): RedirectResponse
    {
        $u = $request->user();
        abort_unless($u->id === $dokumen->uploaded_by, 403, 'Hanya pengunggah yang dapat mengubah akses dokumen ini.');
        $d = $request->validate(['visibility' => ['required', 'in:publik,bidang,rahasia']]);
        $dokumen->update($d);
        LogAktivitas::catat('ubah akses dokumen', $dokumen->judul . ' -> ' . $d['visibility']);
        return back()->with('sukses', 'Akses dokumen diubah menjadi ' . $d['visibility'] . '.');
    }

    public function destroy(Request $request, Dokumen $dokumen): RedirectResponse
    {
        $u = $request->user();
        $anggotaBidang = $dokumen->visibility === 'bidang' && $dokumen->id_bidang && $u->id_bidang === $dokumen->id_bidang;
        abort_unless($u->isAdmin() || $dokumen->uploaded_by === $u->id || $anggotaBidang, 403, 'Hanya pengunggah, sesama anggota bidang, atau admin.');
        Storage::disk('public')->delete($dokumen->file_path);
        $dokumen->delete();
        LogAktivitas::catat('hapus dokumen', $dokumen->judul);
        return back()->with('sukses', 'Dokumen dihapus.');
    }
}

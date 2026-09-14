<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Dokumen;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Global search: pegawai, agenda, dokumen.
     * Toleransi typo: kombinasi LIKE + SOUNDS LIKE (MySQL SOUNDEX).
     * Hasil: max 5 per kategori agar halaman tidak membanjir.
     */
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return view('search.index', [
                'q'       => '',
                'pegawai' => collect(),
                'agenda'  => collect(),
                'dokumen' => collect(),
                'total'   => 0,
            ]);
        }

        // ─── PEGAWAI ──────────────────────────────────────────────────────
        // Utama: scopeCari (LIKE) — sudah sangat komprehensif.
        // Tambahan: SOUNDS LIKE pada kolom nama untuk toleransi typo fonetik.
        $pegawai = Pegawai::with('bidang')
            ->where(function ($w) use ($q) {
                $w->cari($q)                                          // LIKE komprehensif
                  ->orWhereRaw('nama SOUNDS LIKE ?', [$q])           // fonetik
                  ->orWhereRaw('jabatan SOUNDS LIKE ?', [$q]);       // fonetik jabatan
            })
            ->orderBy('nama')
            ->limit(5)
            ->get();

        // ─── AGENDA ───────────────────────────────────────────────────────
        $agenda = Agenda::with('bidang')
            ->where(function ($w) use ($q) {
                $w->where('judul', 'like', "%$q%")
                  ->orWhere('lokasi', 'like', "%$q%")
                  ->orWhere('deskripsi', 'like', "%$q%")
                  ->orWhereRaw('judul SOUNDS LIKE ?', [$q])
                  ->orWhereHas('bidang', fn ($b) => $b->where('nama', 'like', "%$q%"));
            })
            ->orderBy('tanggal', 'desc')
            ->limit(5)
            ->get();

        // ─── DOKUMEN ──────────────────────────────────────────────────────
        $dokumen = Dokumen::where(function ($w) use ($q) {
                $w->where('judul', 'like', "%$q%")
                  ->orWhere('deskripsi', 'like', "%$q%")
                  ->orWhere('kategori', 'like', "%$q%")
                  ->orWhere('file_name', 'like', "%$q%")
                  ->orWhereRaw('judul SOUNDS LIKE ?', [$q]);
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $total = $pegawai->count() + $agenda->count() + $dokumen->count();

        return view('search.index', compact('q', 'pegawai', 'agenda', 'dokumen', 'total'));
    }
}

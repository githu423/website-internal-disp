<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Dokumen;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                'q' => '',
                'pegawai' => collect(),
                'agenda' => collect(),
                'dokumen' => collect(),
                'total' => 0,
            ]);
        }

        $fonetik = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);

        // ─── PEGAWAI ──────────────────────────────────────────────────────
        // Utama: scopeCari (LIKE) — sudah sangat komprehensif.
        // Tambahan: SOUNDS LIKE pada kolom nama untuk toleransi typo fonetik.
        $pegawai = Pegawai::with('bidang')
            ->where(function ($w) use ($q, $fonetik) {
                $w->cari($q);
                if ($fonetik) {
                    $w->orWhereRaw('nama SOUNDS LIKE ?', [$q])
                        ->orWhereRaw('jabatan SOUNDS LIKE ?', [$q]);
                }
            })
            ->orderBy('nama')
            ->limit(5)
            ->get();

        // ─── AGENDA ───────────────────────────────────────────────────────
        $agenda = Agenda::with('bidang')
            ->where(function ($w) use ($q, $fonetik) {
                $w->where('judul', 'like', "%$q%")
                    ->orWhere('lokasi', 'like', "%$q%")
                    ->orWhere('deskripsi', 'like', "%$q%")
                    ->orWhereHas('bidang', fn ($b) => $b->where('nama', 'like', "%$q%"));
                if ($fonetik) {
                    $w->orWhereRaw('judul SOUNDS LIKE ?', [$q]);
                }
            })
            ->orderBy('tanggal', 'desc')
            ->limit(5)
            ->get();

        // ─── DOKUMEN ──────────────────────────────────────────────────────
        $user = $request->user();
        $dokumen = Dokumen::with(['kategoriRel.induk.induk'])
            ->when(! $user->isAdmin(), function ($query) use ($user) {
                $query->where(function ($akses) use ($user) {
                    $akses->where('visibility', 'publik')
                        ->orWhere('uploaded_by', $user->id)
                        ->orWhere(function ($bidang) use ($user) {
                            $bidang->where('visibility', 'bidang')
                                ->where(function ($pemilik) use ($user) {
                                    $pemilik->whereNull('id_bidang');
                                    if ($user->id_bidang) {
                                        $pemilik->orWhere('id_bidang', $user->id_bidang);
                                    }
                                });
                        });
                });
            })
            ->where(function ($w) use ($q, $fonetik) {
                $w->where('judul', 'like', "%$q%")
                    ->orWhere('deskripsi', 'like', "%$q%")
                    ->orWhere('kategori', 'like', "%$q%")
                    ->orWhere('file_name', 'like', "%$q%")
                    ->orWhereHas('kategoriRel', fn ($kk) => $kk->where('nama', 'like', "%$q%"));
                if ($fonetik) {
                    $w->orWhereRaw('judul SOUNDS LIKE ?', [$q]);
                }
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $total = $pegawai->count() + $agenda->count() + $dokumen->count();

        return view('search.index', compact('q', 'pegawai', 'agenda', 'dokumen', 'total'));
    }
}

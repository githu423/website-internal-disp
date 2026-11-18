<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Dokumen;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Pencarian global: pegawai, agenda, dan dokumen.
 *
 * - Toleransi salah ketik: kombinasi LIKE + SOUNDS LIKE (khusus MySQL/MariaDB).
 * - Hasil dibatasi 5 per kategori agar halaman tidak membanjir.
 * - Dokumen selalu difilter sesuai hak akses pengguna.
 */
class SearchController extends Controller
{
    /**
     * Menampilkan hasil pencarian untuk satu kata kunci.
     */
    public function index(Request $request): View
    {
        $kataKunci = trim((string) $request->query('q', ''));

        // ===== KATA KUNCI KOSONG: LANGSUNG KEMBALIKAN HASIL KOSONG =====
        if ($kataKunci === '') {
            return view('search.index', [
                'q' => '',
                'pegawai' => collect(),
                'agenda' => collect(),
                'dokumen' => collect(),
                'total' => 0,
            ]);
        }

        // SOUNDS LIKE hanya tersedia di MySQL/MariaDB.
        $pencarianFonetik = in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);

        // ===== HASIL: PEGAWAI =====
        // Utama: scopeCari (LIKE) — sudah sangat komprehensif.
        // Tambahan: SOUNDS LIKE pada kolom nama untuk toleransi typo fonetik.
        $pegawai = Pegawai::with('bidang')
            ->where(function ($query) use ($kataKunci, $pencarianFonetik) {
                $query->cari($kataKunci);
                if ($pencarianFonetik) {
                    $query->orWhereRaw('nama SOUNDS LIKE ?', [$kataKunci])
                        ->orWhereRaw('jabatan SOUNDS LIKE ?', [$kataKunci]);
                }
            })
            ->orderBy('nama')
            ->limit(5)
            ->get();

        // ===== HASIL: AGENDA =====
        $agenda = Agenda::with('bidang')
            ->where(function ($query) use ($kataKunci, $pencarianFonetik) {
                $query->where('judul', 'like', "%$kataKunci%")
                    ->orWhere('lokasi', 'like', "%$kataKunci%")
                    ->orWhere('deskripsi', 'like', "%$kataKunci%")
                    ->orWhereHas('bidang', fn ($queryBidang) => $queryBidang->where('nama', 'like', "%$kataKunci%"));
                if ($pencarianFonetik) {
                    $query->orWhereRaw('judul SOUNDS LIKE ?', [$kataKunci]);
                }
            })
            ->orderBy('tanggal', 'desc')
            ->limit(5)
            ->get();

        // ===== HASIL: DOKUMEN (DIFILTER HAK AKSES) =====
        $pengguna = $request->user();
        $dokumen = Dokumen::with(['kategoriRel.induk.induk'])
            ->when(! $pengguna->isAdmin(), function ($query) use ($pengguna) {
                $query->where(function ($queryAkses) use ($pengguna) {
                    $queryAkses->where('visibility', 'publik')
                        ->orWhere('uploaded_by', $pengguna->id)
                        ->orWhere(function ($queryBidang) use ($pengguna) {
                            $queryBidang->where('visibility', 'bidang')
                                ->where(function ($queryPemilik) use ($pengguna) {
                                    $queryPemilik->whereNull('id_bidang');
                                    if ($pengguna->id_bidang) {
                                        $queryPemilik->orWhere('id_bidang', $pengguna->id_bidang);
                                    }
                                });
                        });
                });
            })
            ->where(function ($query) use ($kataKunci, $pencarianFonetik) {
                $query->where('judul', 'like', "%$kataKunci%")
                    ->orWhere('deskripsi', 'like', "%$kataKunci%")
                    ->orWhere('kategori', 'like', "%$kataKunci%")
                    ->orWhere('file_name', 'like', "%$kataKunci%")
                    ->orWhereHas('kategoriRel', fn ($queryKategori) => $queryKategori->where('nama', 'like', "%$kataKunci%"));
                if ($pencarianFonetik) {
                    $query->orWhereRaw('judul SOUNDS LIKE ?', [$kataKunci]);
                }
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // ===== TOTAL & RESPONS =====
        $total = $pegawai->count() + $agenda->count() + $dokumen->count();

        // 'q' dipertahankan karena menjadi nama variabel di tampilan (search/index).
        return view('search.index', ['q' => $kataKunci, 'pegawai' => $pegawai, 'agenda' => $agenda, 'dokumen' => $dokumen, 'total' => $total]);
    }
}

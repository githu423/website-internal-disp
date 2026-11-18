<?php

namespace App\Http\Controllers;

use App\Models\Pad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Halaman PAD (Pelaksanaan Anggaran Dinas).
 *
 * Menampilkan target & realisasi per kategori untuk satu tahun.
 */
class PadController extends Controller
{
    /**
     * Daftar PAD per tahun + ringkasan target/realisasi.
     */
    public function index(Request $request): View
    {
        // ===== 1. TAHUN YANG DITAMPILKAN =====
        $tahunList = Pad::tahunTersedia();
        $tahun = (int) ($request->query('tahun') ?: (in_array(now()->year, $tahunList) ? now()->year : ($tahunList[0] ?? now()->year)));

        // ===== 2. DAFTAR PAD & RINGKASAN =====
        $rows = Pad::where('tahun', $tahun)->orderBy('kategori')->orderBy('id')->get();
        $sum = Pad::where('tahun', $tahun)->select(
            DB::raw('COALESCE(SUM(kumulatif),0) realisasi'),
            DB::raw('COALESCE(SUM(CASE WHEN target_perubahan>0 THEN target_perubahan ELSE target_tahunan END),0) target')
        )->first();

        // ===== 3. REKAP PER KATEGORI =====
        $perKategori = $rows->groupBy('kategori')->map(fn ($kelompok) => [
            'target' => $kelompok->sum('target_perubahan') ?: $kelompok->sum(fn ($item) => (float) $item->target_tahunan),
            'realisasi' => $kelompok->sum(fn ($item) => (float) $item->kumulatif),
        ]);

        return view('pad.index', compact('rows', 'tahun', 'tahunList', 'sum', 'perKategori'));
    }
}

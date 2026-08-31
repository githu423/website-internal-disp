<?php

namespace App\Http\Controllers;

use App\Models\Pad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PadController extends Controller
{
    public function index(Request $request): View
    {
$tahunList = Pad::tahunTersedia();
        $tahun = (int) ($request->query('tahun') ?: (in_array(now()->year, $tahunList) ? now()->year : ($tahunList[0] ?? now()->year)));

        $rows = Pad::where('tahun', $tahun)->orderBy('kategori')->orderBy('id')->get();
        $sum = Pad::where('tahun', $tahun)->select(
            DB::raw('COALESCE(SUM(kumulatif),0) realisasi'),
            DB::raw('COALESCE(SUM(CASE WHEN target_perubahan>0 THEN target_perubahan ELSE target_tahunan END),0) target')
        )->first();

        $perKategori = $rows->groupBy('kategori')->map(fn ($g) => [
            'target' => $g->sum('target_perubahan') ?: $g->sum(fn ($r) => (float) $r->target_tahunan),
            'realisasi' => $g->sum(fn ($r) => (float) $r->kumulatif),
        ]);

        return view('pad.index', compact('rows', 'tahun', 'tahunList', 'sum', 'perKategori'));
    }
}

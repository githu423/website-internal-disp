<?php

namespace App\Http\Controllers;

use App\Models\CapaianBulanan;
use App\Models\CapaianProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatistikController extends Controller
{
    public function index(Request $request): View
    {
        $tahun = (int) ($request->query('tahun') ?: (CapaianProgram::tahunTersedia()[0] ?? now()->year));
        $baris = CapaianProgram::where('tahun', $tahun)->orderBy('urutan')->get();
        $bulanTahun = CapaianBulanan::tahunTersedia()[0] ?? $tahun;
        $bulanan = CapaianBulanan::where('tahun', $bulanTahun)->orderBy('bulan')->get();

        $namaBln = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $statChart = [
            'labels' => array_map(fn ($m) => $namaBln[$m], range(1, 12)),
            'target' => array_map(fn ($m) => (float) ($bulanan->firstWhere('bulan', $m)?->target ?? 0), range(1, 12)),
            'capaian' => array_map(fn ($m) => (float) ($bulanan->firstWhere('bulan', $m)?->capaian ?? 0), range(1, 12)),
        ];

        return view('statistik.index', [
            'statChart' => $statChart,
            'tahun' => $tahun,
            'tahunList' => CapaianProgram::tahunTersedia(),
            'programs' => $baris->where('level', 'program'),
            'kegiatan' => $baris->where('level', 'kegiatan')->count(),
            'subs' => $baris->where('level', 'sub_kegiatan'),
            'bulanan' => $bulanan,
            'bulanTahun' => $bulanTahun,
        ]);
    }

    /** daftar kegiatan & capaian (bisa difilter bidang) — info + pengingat kegiatan dinas */
    public function kinerja(Request $request): View
    {
        $u = $request->user();
        $tahunList = CapaianProgram::tahunTersedia();
        $tahun = (int) ($request->query('tahun') ?: ($tahunList[0] ?? now()->year));
        $q = CapaianProgram::where('tahun', $tahun)->where('level', 'sub_kegiatan')->with('bidang')->orderBy('urutan');
        if (! $u->isAdmin() && $u->id_bidang) $q->where('id_bidang', $u->id_bidang);
        if ($f = $request->query('status')) {
            if ($f === 'tuntas') $q->where('persentase', '>=', 100);
            if ($f === 'berjalan') $q->whereBetween('persentase', [60, 99.999]);
            if ($f === 'lambat') $q->where('persentase', '<', 60);
        }
        if ($s = trim((string) $request->query('q'))) $q->where('nama', 'like', "%$s%");
        $items = $q->paginate(20)->withQueryString();
        $ringkas = CapaianProgram::where('tahun', $tahun)->where('level', 'sub_kegiatan')
            ->selectRaw("COUNT(*) total, SUM(CASE WHEN persentase>=100 THEN 1 ELSE 0 END) tuntas, SUM(CASE WHEN persentase>=60 AND persentase<100 THEN 1 ELSE 0 END) berjalan, SUM(CASE WHEN persentase<60 THEN 1 ELSE 0 END) lambat")->first();

        return view('kinerja.index', compact('items', 'tahun', 'tahunList', 'ringkas'));
    }

    public function data(Request $request): JsonResponse
    {
        $tahun = (int) $request->query('tahun', now()->year);
        $rows = CapaianBulanan::where('tahun', $tahun)->orderBy('bulan')->get();
        $nama = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return response()->json([
            'labels' => array_map(fn ($m) => $nama[$m], range(1, 12)),
            'target' => array_map(fn ($m) => (float) ($rows->firstWhere('bulan', $m)?->target ?? 0), range(1, 12)),
            'capaian' => array_map(fn ($m) => (float) ($rows->firstWhere('bulan', $m)?->capaian ?? 0), range(1, 12)),
        ]);
    }
}

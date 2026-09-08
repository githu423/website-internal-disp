<?php

namespace App\Http\Controllers;

use App\Models\Statistik;
use App\Models\StatistikNilai;
use App\Models\CapaianIndikator;
use App\Models\CapaianProgram;
use App\Models\CapaianSasaran;
use App\Models\CapaianTahunan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatistikController extends Controller
{
    /**
     * Halaman Statistik (v17): tabel indikator x tahun + grafik tren per indikator,
     * bersumber dari modul Admin > Statistik (import file "Data Statistik Pariwisata").
     */
    public function index(Request $request): View
    {
        $q = Statistik::with('nilais')->orderByRaw('COALESCE(no_urut, 99999)')->orderBy('indikator');
        if ($s = trim((string) $request->query('q'))) $q->where('indikator', 'like', '%' . $s . '%');
        $rows = $q->get();
        $tahunList = StatistikNilai::distinct()->orderBy('tahun')->pluck('tahun')->map(fn ($t) => (int) $t)->all();

        $selected = $rows->firstWhere('id', (int) $request->query('ind')) ?: $rows->first();
        $chart = null;
        if ($selected && $tahunList) {
            $byT = $selected->nilais->keyBy('tahun');
            $chart = [
                'labels' => array_map('strval', $tahunList),
                'datasets' => [[
                    'label' => $selected->indikator . ($selected->satuan ? ' (' . $selected->satuan . ')' : ''),
                    'data' => array_map(fn ($t) => $byT->get($t)?->nilai !== null ? (float) $byT->get($t)->nilai : null, $tahunList),
                    'type' => 'line', 'color' => '#0d9488', 'fill' => true,
                ]],
            ];
        }

        return view('statistik.index', [
            'rows' => $rows,
            'tahunList' => $tahunList,
            'selected' => $selected,
            'chart' => $chart,
            'totalSemua' => Statistik::count(),
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
        $ringkasQuery = CapaianProgram::where('tahun', $tahun)->where('level', 'sub_kegiatan');
        if (! $u->isAdmin() && $u->id_bidang) $ringkasQuery->where('id_bidang', $u->id_bidang);
        $ringkas = $ringkasQuery->selectRaw("COUNT(*) total, SUM(CASE WHEN persentase>=100 THEN 1 ELSE 0 END) tuntas, SUM(CASE WHEN persentase>=60 AND persentase<100 THEN 1 ELSE 0 END) berjalan, SUM(CASE WHEN persentase<60 THEN 1 ELSE 0 END) lambat")->first();

        return view('kinerja.index', compact('items', 'tahun', 'tahunList', 'ringkas'));
    }

    public function capaian(Request $request): View
    {
        $sasarans = CapaianSasaran::with(['indikator.tahunan'])->orderBy('urut')->get();
        $years = CapaianTahunan::distinct()->orderBy('tahun')->pluck('tahun')->map(fn ($t) => (int) $t)->all();
        if (!$years) $years = [(int) now()->year];
        $last = end($years);
        $dinilaiLalu = CapaianTahunan::where('tahun', $last)->get();
        $tercapai = $dinilaiLalu->filter(fn ($n) => ($x = CapaianIndikator::persenNum($n->persen)) !== null && $x >= 100)->count();
        $dinilai = $dinilaiLalu->filter(fn ($n) => CapaianIndikator::persenNum($n->persen) !== null)->count();

        $tampilan = $request->query('tampilan') === 'statistik' ? 'statistik' : 'matriks';
        $charts = [];
        $ringkasChart = null;
        if ($tampilan === 'statistik') {
            foreach ($sasarans as $s) {
                foreach ($s->indikator as $ind) {
                    $by = $ind->tahunan->keyBy('tahun');
                    $t = $r = $p = $chips = [];
                    foreach ($years as $y) {
                        $n = $by->get($y);
                        $t[] = CapaianIndikator::angkaNum($n?->target);
                        $r[] = CapaianIndikator::angkaNum($n?->realisasi);
                        $p[] = CapaianIndikator::persenNum($n?->persen);
                        $num = CapaianIndikator::persenNum($n?->persen);
                        $chips[] = ['tahun' => $y, 'teks' => $n?->realisasi ?? '—', 'ok' => $num !== null ? $num >= 100 : null];
                    }
                    $adaAngka = array_filter($t) !== [] || array_filter($r) !== [];
                    $charts[] = [
                        'nama' => $ind->nama, 'satuan' => $ind->satuan, 'sasaran' => $s->nomor . '. ' . $s->judul, 'bidang' => $s->bidang?->nama,
                        'chart' => $adaAngka ? ['labels' => $years, 'datasets' => [
                            ['label' => 'Target', 'data' => $t, 'type' => 'bar', 'color' => '#94a3b8'],
                            ['label' => 'Realisasi', 'data' => $r, 'type' => 'bar', 'color' => '#14b8a6'],
                            ['label' => 'Capaian %', 'data' => $p, 'type' => 'line', 'color' => '#f97316', 'axis' => 'y1'],
                        ]] : null,
                        'chips' => $chips,
                    ];
                }
            }
            [$yearsRingkas, $pct] = CapaianTahunan::tingkatPerTahun();
            $ringkasChart = ['labels' => $yearsRingkas, 'datasets' => [
                ['label' => '% indikator tercapai (≥100%)', 'data' => $pct, 'type' => 'line', 'color' => '#0f172a', 'fill' => true],
            ]];
        }

        return view('kinerja.capaian', compact('sasarans', 'years', 'last', 'tercapai', 'dinilai', 'tampilan', 'charts', 'ringkasChart'));
    }

    /** JSON seluruh indikator + nilai per tahun (dipakai utk grafik/ekspor pihak ketiga). */
    public function data(Request $request): JsonResponse
    {
        $rows = Statistik::with('nilais')->orderByRaw('COALESCE(no_urut, 99999)')->get();
        return response()->json([
            'tahun' => StatistikNilai::distinct()->orderBy('tahun')->pluck('tahun'),
            'rows' => $rows->map(fn ($r) => [
                'id' => $r->id, 'indikator' => $r->indikator, 'satuan' => $r->satuan,
                'keterangan' => $r->keterangan, 'nilai' => $r->nilais->pluck('nilai', 'tahun'),
            ]),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\CapaianIndikator;
use App\Models\CapaianProgram;
use App\Models\CapaianSasaran;
use App\Models\CapaianTahunan;
use App\Models\Statistik;
use App\Models\StatistikNilai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman Statistik & kinerja.
 *
 * - Statistik: tabel indikator x tahun + grafik tren per indikator,
 *   bersumber dari modul Admin > Statistik (import "Data Statistik Pariwisata").
 * - Kinerja: daftar kegiatan & capaian, bisa difilter per bidang.
 * - Capaian: matriks sasaran/indikator atau tampilan statistik per tahun.
 */
class StatistikController extends Controller
{
    /**
     * Halaman Statistik (v17): tabel indikator x tahun + grafik tren per indikator.
     */
    public function index(Request $request): View
    {
        // ===== 1. DAFTAR INDIKATOR =====
        $query = Statistik::with('nilais')->orderByRaw('COALESCE(no_urut, 99999)')->orderBy('indikator');
        if ($kataKunci = trim((string) $request->query('q'))) {
            $query->where('indikator', 'like', '%'.$kataKunci.'%');
        }
        $baris = $query->get();
        $tahunList = StatistikNilai::distinct()->orderBy('tahun')->pluck('tahun')->map(fn ($tahun) => (int) $tahun)->all();

        // ===== 2. GRAFIK TREN INDIKATOR TERPILIH =====
        $terpilih = $baris->firstWhere('id', (int) $request->query('ind')) ?: $baris->first();
        $chart = null;
        if ($terpilih && $tahunList) {
            $nilaiPerTahun = $terpilih->nilais->keyBy('tahun');
            $chart = [
                'labels' => array_map('strval', $tahunList),
                'datasets' => [[
                    'label' => $terpilih->indikator.($terpilih->satuan ? ' ('.$terpilih->satuan.')' : ''),
                    'data' => array_map(fn ($tahun) => $nilaiPerTahun->get($tahun)?->nilai !== null ? (float) $nilaiPerTahun->get($tahun)->nilai : null, $tahunList),
                    'type' => 'line', 'color' => '#0d9488', 'fill' => true,
                ]],
            ];
        }

        // ===== 3. RESPONS =====
        return view('statistik.index', [
            'rows' => $baris,
            'tahunList' => $tahunList,
            'selected' => $terpilih,
            'chart' => $chart,
            'totalSemua' => Statistik::count(),
        ]);
    }

    /**
     * Daftar kegiatan & capaian (bisa difilter bidang) — info + pengingat kegiatan dinas.
     */
    public function kinerja(Request $request): View
    {
        $pengguna = $request->user();

        // ===== 1. LINGKUP DATA =====
        // Konfirmasi Dinas 10 Sep 2026: bawaan = seluruh dinas (sama utk semua user);
        // pemilik bidang boleh memilih lingkup bidangnya sendiri lewat ?lingkup=saya.
        $punyaBidang = ! $pengguna->isAdmin() && $pengguna->id_bidang;
        $idBidangLingkup = $punyaBidang && $request->query('lingkup') === 'saya' ? $pengguna->id_bidang : null;
        $lingkupSaya = (bool) $idBidangLingkup;

        // ===== 2. DAFTAR KEGIATAN =====
        $tahunList = CapaianProgram::tahunTersedia();
        $tahun = (int) ($request->query('tahun') ?: ($tahunList[0] ?? now()->year));
        $query = CapaianProgram::where('tahun', $tahun)->where('level', 'sub_kegiatan')->with('bidang')->orderBy('urutan');
        if ($idBidangLingkup) {
            $query->where('id_bidang', $idBidangLingkup);
        }
        if ($filterStatus = $request->query('status')) {
            if ($filterStatus === 'tuntas') {
                $query->where('persentase', '>=', 100);
            }
            if ($filterStatus === 'berjalan') {
                $query->whereBetween('persentase', [60, 99.999]);
            }
            if ($filterStatus === 'lambat') {
                $query->where('persentase', '<', 60);
            }
        }
        if ($kataKunci = trim((string) $request->query('q'))) {
            $query->where('nama', 'like', "%$kataKunci%");
        }
        $items = $query->paginate(20)->withQueryString();

        // ===== 3. RINGKASAN STATUS =====
        $ringkasQuery = CapaianProgram::where('tahun', $tahun)->where('level', 'sub_kegiatan');
        if ($idBidangLingkup) {
            $ringkasQuery->where('id_bidang', $idBidangLingkup);
        }
        $ringkas = $ringkasQuery->selectRaw("COUNT(*) total, SUM(CASE WHEN persentase>=100 THEN 1 ELSE 0 END) tuntas, SUM(CASE WHEN persentase>=60 AND persentase<100 THEN 1 ELSE 0 END) berjalan, SUM(CASE WHEN persentase<60 THEN 1 ELSE 0 END) lambat")->first();

        return view('kinerja.index', compact('items', 'tahun', 'tahunList', 'ringkas', 'punyaBidang', 'lingkupSaya'));
    }

    /**
     * Halaman capaian: matriks sasaran/indikator, atau tampilan statistik per tahun.
     */
    public function capaian(Request $request): View
    {
        // ===== 1. DATA SASARAN & TAHUN =====
        $sasarans = CapaianSasaran::with(['indikator.tahunan'])->orderBy('urut')->get();
        $years = CapaianTahunan::distinct()->orderBy('tahun')->pluck('tahun')->map(fn ($tahun) => (int) $tahun)->all();
        if (! $years) {
            $years = [(int) now()->year];
        }
        $last = end($years);

        // ===== 2. RINGKASAN TAHUN TERAKHIR =====
        $dinilaiLalu = CapaianTahunan::where('tahun', $last)->get();
        $tercapai = $dinilaiLalu->filter(fn ($nilai) => ($persenNum = CapaianIndikator::persenNum($nilai->persen)) !== null && $persenNum >= 100)->count();
        $dinilai = $dinilaiLalu->filter(fn ($nilai) => CapaianIndikator::persenNum($nilai->persen) !== null)->count();

        // ===== 3. SUSUN GRAFIK PER INDIKATOR (JIKA MODE "STATISTIK") =====
        $tampilan = $request->query('tampilan') === 'statistik' ? 'statistik' : 'matriks';
        $charts = [];
        $ringkasChart = null;
        if ($tampilan === 'statistik') {
            foreach ($sasarans as $sasaran) {
                foreach ($sasaran->indikator as $indikator) {
                    $nilaiPerTahun = $indikator->tahunan->keyBy('tahun');
                    $target = $realisasi = $persen = $chips = [];
                    foreach ($years as $tahun) {
                        $nilai = $nilaiPerTahun->get($tahun);
                        $target[] = CapaianIndikator::angkaNum($nilai?->target);
                        $realisasi[] = CapaianIndikator::angkaNum($nilai?->realisasi);
                        $persen[] = CapaianIndikator::persenNum($nilai?->persen);
                        $persenNum = CapaianIndikator::persenNum($nilai?->persen);
                        $chips[] = ['tahun' => $tahun, 'teks' => $nilai?->realisasi ?? '—', 'ok' => $persenNum !== null ? $persenNum >= 100 : null];
                    }
                    $adaAngka = array_filter($target) !== [] || array_filter($realisasi) !== [];
                    $charts[] = [
                        'nama' => $indikator->nama, 'satuan' => $indikator->satuan, 'sasaran' => $sasaran->nomor . '. ' . $sasaran->judul, 'bidang' => $sasaran->bidang?->nama,
                        'chart' => $adaAngka ? ['labels' => $years, 'datasets' => [
                            ['label' => 'Target', 'data' => $target, 'type' => 'bar', 'color' => '#94a3b8'],
                            ['label' => 'Realisasi', 'data' => $realisasi, 'type' => 'bar', 'color' => '#14b8a6'],
                            ['label' => 'Capaian %', 'data' => $persen, 'type' => 'line', 'color' => '#f97316', 'axis' => 'y1'],
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

    /**
     * JSON seluruh indikator + nilai per tahun (dipakai utk grafik/ekspor pihak ketiga).
     */
    public function data(Request $request): JsonResponse
    {
        $baris = Statistik::with('nilais')->orderByRaw('COALESCE(no_urut, 99999)')->get();

        return response()->json([
            'tahun' => StatistikNilai::distinct()->orderBy('tahun')->pluck('tahun'),
            'rows' => $baris->map(fn ($barisItem) => [
                'id' => $barisItem->id, 'indikator' => $barisItem->indikator, 'satuan' => $barisItem->satuan,
                'keterangan' => $barisItem->keterangan, 'nilai' => $barisItem->nilais->pluck('nilai', 'tahun'),
            ]),
        ]);
    }
}

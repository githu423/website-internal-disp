<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CapaianBulanan;
use App\Models\LogAktivitas;
use App\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kelola Statistik Pariwisata — tren bulanan (target & capaian) per tahun,
 * yang tampil pada grafik halaman /statistik. Manual per sel atau impor berkas.
 */
class StatistikController extends Controller
{
    public function index(Request $request): View
    {
        $tahunList = CapaianBulanan::tahunTersedia();
        $tahun = (int) ($request->query('tahun') ?: ($tahunList[0] ?? now()->year));
        $rows = CapaianBulanan::where('tahun', $tahun)->get()->keyBy('bulan');

        return view('admin.statistik.index', [
            'rows' => $rows,
            'tahun' => $tahun,
            'tahunList' => $tahunList,
            'tahunOptions' => range((int) date('Y') + 3, 2015),
            'bulanLabels' => [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $request->validate([
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'nilai' => ['required', 'array', 'size:12'],
            'nilai.*.target' => ['nullable', 'numeric', 'min:0'],
            'nilai.*.capaian' => ['nullable', 'numeric', 'min:0'],
        ]);
        for ($b = 1; $b <= 12; $b++) {
            $t = $d['nilai'][$b]['target'] ?? '';
            $c = $d['nilai'][$b]['capaian'] ?? '';
            $row = CapaianBulanan::firstOrNew(['tahun' => (int) $d['tahun'], 'bulan' => $b]);
            if ($t === '' && $c === '') {
                if ($row->exists) $row->delete();
                continue;
            }
            $row->target = (float) ($t ?: 0);
            $row->capaian = (float) ($c ?: 0);
            $row->save();
        }
        LogAktivitas::catat('ubah statistik bulanan', 'TA ' . $d['tahun']);

        return back()->with('sukses', "Statistik bulanan TA {$d['tahun']} disimpan.");
    }

    /** Berkas CSV/XLSX: baris [bulan, target, capaian] (bulan: angka 1-12 atau nama) — atau [target, capaian] urut Jan..Des. */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'berkas' => ['required', 'file', 'mimes:csv,txt,xlsx,xls,ods'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);
        $tahun = (int) $request->input('tahun');
        $namaBln = ['jan' => 1, 'januari' => 1, 'feb' => 2, 'februari' => 2, 'mar' => 3, 'maret' => 3,
            'apr' => 4, 'april' => 4, 'mei' => 5, 'jun' => 6, 'juni' => 6, 'jul' => 7, 'juli' => 7,
            'agu' => 8, 'agustus' => 8, 'sep' => 9, 'sept' => 9, 'september' => 9, 'okt' => 10, 'oktober' => 10,
            'nop' => 11, 'nov' => 11, 'november' => 11, 'des' => 12, 'desember' => 12];
        $num = function ($v): float {
            $v = trim((string) $v);
            if ($v === '' || strcasecmp($v, 'n/a') === 0) return 0.0;
            if (preg_match('/^-?\d{1,3},\d{1,2}$/', $v)) { $v = str_replace(',', '.', $v); } // koma desimal
            else { $v = str_replace(',', '', $v); }                                             // pemisah ribuan
            $x = preg_replace('/[^\d.\-]/', '', $v);
            return is_numeric($x) ? (float) $x : 0.0;
        };

        $n = 0; $urutan = 1;
        foreach (ImportService::rowsFrom($request->file('berkas')->getRealPath()) as $r) {
            $r = array_values(array_filter(array_map(fn ($c) => trim((string) $c), $r), fn ($c) => $c !== ''));
            if (count($r) < 2) continue;
            $bulan = null; $t = null; $c = null;
            $f0 = mb_strtolower($r[0]);
            if (is_numeric($f0) && (int) $f0 >= 1 && (int) $f0 <= 12 && count($r) >= 3) { [$bulan, $t, $c] = [(int) $f0, $r[1], $r[2]]; }
            elseif (isset($namaBln[$f0]) && count($r) >= 3) { [$bulan, $t, $c] = [$namaBln[$f0], $r[1], $r[2]]; }
            elseif (count($r) >= 2 && $urutan <= 12 && $f0 !== 'bulan') { [$bulan, $t, $c] = [$urutan++, $r[0], $r[1]]; }
            if ($bulan === null) continue;
            CapaianBulanan::updateOrCreate(['tahun' => $tahun, 'bulan' => (int) $bulan], ['target' => $num($t), 'capaian' => $num($c)]);
            $n++;
        }
        if (!$n) {
            return back()->with('gagal', 'Tidak ada baris yang dikenali. Format: "bulan,target,capaian" per baris, atau dua kolom target,capaian urut Januari–Desember.');
        }
        LogAktivitas::catat('impor statistik bulanan', "{$n} bulan TA {$tahun}");

        return back()->with('sukses', "Impor statistik TA {$tahun}: {$n} bulan tersimpan.");
    }
}

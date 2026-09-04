<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CapaianBulanan;
use App\Models\LogAktivitas;
use App\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Kelola statistik pariwisata: angka target vs capaian per bulan untuk satu tahun.
 * Menggerakkan grafik di halaman /statistik publik. Bisa diisi manual atau impor CSV/XLSX.
 */
class StatistikController extends Controller
{
    /** @var array<int,string> label bulan utk tabel */
    private const LABEL = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

    /** Pemisah ribuan & koma desimal Indonesia dikenali ("120,5" -> 120.5 ; "1,500" -> 1500). */
    private static function angkaTeks($v): float
    {
        $v = trim((string) $v);
        if ($v === '' || strcasecmp($v, 'n/a') === 0) return 0.0;
        if (preg_match('/^-?\d{1,3},\d{1,2}$/', $v)) $v = str_replace(',', '.', $v);
        else $v = str_replace(',', '', $v);
        $v = preg_replace('/[^\d.\-]/', '', $v);
        return is_numeric($v) ? (float) $v : 0.0;
    }

    public function index(Request $request): View
    {
        $tahun = (int) $request->query('tahun') ?: (int) now()->year;
        $list = CapaianBulanan::tahunTersedia();
        if (! in_array($tahun, $list, true)) $list[] = $tahun;
        rsort($list);
        $tahunList = array_values(array_unique($list));

        return view('admin.statistik.index', [
            'tahun' => $tahun,
            'tahunList' => $tahunList,
            'tahunOptions' => range((int) now()->year + 1, (int) now()->year - 10),
            'rows' => CapaianBulanan::where('tahun', $tahun)->get()->keyBy('bulan'),
            'bulanLabels' => self::LABEL,
        ]);
    }

    /** Simpan manual 12 bulan sekaligus (upsert; baris kosong-hapus). */
    public function store(Request $request): RedirectResponse
    {
        $d = $request->validate([
            'tahun' => ['required', 'integer', 'min:1990', 'max:2100'],
            'nilai' => ['required', 'array'],
        ]);
        $tahun = (int) $d['tahun'];
        $nilai = $d['nilai'];
        $tersimpan = 0; $terhapus = 0;

        DB::transaction(function () use ($tahun, $nilai, &$tersimpan, &$terhapus) {
            for ($b = 1; $b <= 12; $b++) {
                $t = trim((string) ($nilai[$b]['target'] ?? ''));
                $c = trim((string) ($nilai[$b]['capaian'] ?? ''));
                if ($t === '' && $c === '') {
                    CapaianBulanan::where('tahun', $tahun)->where('bulan', $b)->delete();
                    $terhapus++;
                    continue;
                }
                CapaianBulanan::updateOrCreate(
                    ['tahun' => $tahun, 'bulan' => $b],
                    ['target' => self::angkaTeks($t), 'capaian' => self::angkaTeks($c)]
                );
                $tersimpan++;
            }
        });

        LogAktivitas::catat('ubah statistik pariwisata', "TA $tahun: $tersimpan bulan disimpan, $terhapus baris kosong dihapus");

        return back()->with('sukses', "Statistik TA $tahun tersimpan ($tersimpan bulan diperbarui).");
    }

    /** Impor CSV/XLSX per tahun: [bulan,target,capaian] atau dua kolom target,capaian urut Jan→Des. */
    public function import(Request $request): RedirectResponse
    {
        $d = $request->validate([
            'tahun' => ['required', 'integer', 'min:1990', 'max:2100'],
            'berkas' => ['required', 'file', 'max:20480'],
        ]);
        $tahun = (int) $d['tahun'];
        $rows = ImportService::rowsFrom($request->file('berkas')->getRealPath());

        $alias = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'mei' => 5, 'jun' => 6,
            'jul' => 7, 'agu' => 8, 'ags' => 8, 'agt' => 8, 'aug' => 8, 'sep' => 9,
            'okt' => 10, 'nov' => 11, 'des' => 12, 'dec' => 12];
        $parsed = []; $urut = 0;
        foreach ($rows as $r) {
            $c0 = trim((string) ($r[0] ?? ''));
            if ($c0 === '' || mb_strtolower($c0) === 'bulan') continue; // header, bila ada
            if (count($r) >= 3) {
                $bulan = is_numeric($c0) ? (int) $c0 : ($alias[mb_substr(mb_strtolower($c0), 0, 3)] ?? null);
                [$t, $c] = [$r[1], $r[2]];
            } elseif (count($r) === 2) {
                $bulan = ++$urut; // dua kolom: urut Januari → Desember
                [$t, $c] = [$r[0], $r[1]];
            } else {
                continue;
            }
            if ($bulan < 1 || $bulan > 12) continue;
            $parsed[$bulan] = ['target' => self::angkaTeks($t), 'capaian' => self::angkaTeks($c)];
        }

        if (! $parsed) {
            return back()->with('gagal', 'Tidak ada baris data yang dikenali. Format: bulan,target,capaian (bulan 1–12 atau Jan..Des) atau dua kolom target,capaian urut Januari→Desember.');
        }

        foreach ($parsed as $bulan => $v) {
            CapaianBulanan::updateOrCreate(['tahun' => $tahun, 'bulan' => $bulan], $v);
        }
        LogAktivitas::catat('impor statistik pariwisata', "TA $tahun: " . count($parsed) . ' bulan');

        return back()->with('sukses', 'Impor selesai — ' . count($parsed) . " bulan TA $tahun diperbarui. Bulan yang tidak disebut file tetap utuh.");
    }
}

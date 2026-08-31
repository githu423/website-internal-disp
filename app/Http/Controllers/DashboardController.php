<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\CapaianBulanan;
use App\Models\CapaianProgram;
use App\Models\Dokumen;
use App\Models\Pad;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $u = $request->user();
        $bid = $u->isAdmin() ? null : $u->id_bidang;
        $scope = fn ($q) => $bid ? $q->where('id_bidang', $bid) : $q;

        $kinerjaTahun = (int) ($request->query('tahun') ?: (CapaianProgram::tahunTersedia()[0] ?? now()->year));
        $padList = Pad::tahunTersedia();
        $padTahun = (int) (in_array(now()->year, $padList) ? now()->year : ($padList[0] ?? now()->year));

        // kartu statistik
        $jumlahPegawai = $scope(Pegawai::query())->where('status', 'aktif')->count();
        $jumlahDokumen = $scope(Dokumen::query())->count();
        $agendaMendatang = $scope(Agenda::query())->terdekat()->limit(5)->get();
        $agendaTerlewat = $scope(Agenda::query())->terlewat()->count();

        $padSum = Pad::where('tahun', $padTahun)
            ->select(DB::raw('COALESCE(SUM(kumulatif),0) realisasi'), DB::raw('COALESCE(SUM(CASE WHEN target_perubahan>0 THEN target_perubahan ELSE target_tahunan END),0) target'))
            ->first();
        $padPersen = $padSum && $padSum->target > 0 ? round($padSum->realisasi / $padSum->target * 100, 1) : null;

        // grafik bulanan: tahun dengan data, fallback ke data terdekat
        $chartTahun = CapaianBulanan::tahunTersedia()[0] ?? $kinerjaTahun;
        $bulanan = CapaianBulanan::where('tahun', $chartTahun)->orderBy('bulan')->get();
        $bulanNama = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $chart = [
            'labels' => array_map(fn ($m) => $bulanNama[$m], range(1, 12)),
            'target' => array_map(fn ($m) => $bulanan->firstWhere('bulan', $m)?->target ?? 0, range(1, 12)),
            'capaian' => array_map(fn ($m) => $bulanan->firstWhere('bulan', $m)?->capaian ?? 0, range(1, 12)),
            'tahun' => $chartTahun,
        ];

        // kinerja & kegiatan tahun berjalan
        $kinerjaQuery = $scope(CapaianProgram::query()->where('tahun', $kinerjaTahun)->where('level', 'sub_kegiatan'))
            ->orderBy('urutan');
        $kinerja = (clone $kinerjaQuery)->limit(8)->get();
        $kinerjaTotal = (clone $kinerjaQuery)->count();
        $kinerjaSelesai = CapaianProgram::where('tahun', $kinerjaTahun)->where('level', 'sub_kegiatan')
            ->where('persentase', '>=', 100)->count();

        return view('dashboard.index', compact(
            'jumlahPegawai', 'jumlahDokumen', 'agendaMendatang', 'agendaTerlewat',
            'padTahun', 'padSum', 'padPersen', 'chart', 'kinerja', 'kinerjaTotal',
            'kinerjaSelesai', 'kinerjaTahun'
        ));
    }
}

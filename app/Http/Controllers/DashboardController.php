<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\CapaianBulanan;
use App\Models\CapaianProgram;
use App\Models\Dokumen;
use App\Models\Pad;
use App\Models\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $u = $request->user();
        // Konfirmasi Dinas 10 Sep 2026: halaman utama SAMA untuk semua user (lingkup seluruh
        // dinas). User yang punya bidang diberi PILIHAN melihat data bidangnya sendiri
        // lewat ?lingkup=saya — bukan lagi dipaksa otomatis.
        $punyaBidang = ! $u->isAdmin() && $u->id_bidang;
        $bid = $punyaBidang && $request->query('lingkup') === 'saya' ? $u->id_bidang : null;
        $lingkupSaya = (bool) $bid;
        $scope = fn ($q) => $bid ? $q->where('id_bidang', $bid) : $q;

        $kinerjaTahun = (int) ($request->query('tahun') ?: (CapaianProgram::tahunTersedia()[0] ?? now()->year));
        $padList = Pad::tahunTersedia();
        $padTahun = (int) (in_array(now()->year, $padList) ? now()->year : ($padList[0] ?? now()->year));

        // kartu statistik (konfirmasi Dinas 10 Sep: kartu non-aktif & pensiun + angka live via polling)
        $jumlahPegawai = Pegawai::where('status', 'aktif')->count();
        $jumlahPegawaiNonaktif = Pegawai::where('status', 'nonaktif')->count();
        $jumlahPegawaiPensiun = Pegawai::where('status', 'pensiun')->count();
        $prestasiTerbaru = \App\Models\Prestasi::untukDashboard(10);
        $jumlahDokumen = $scope(Dokumen::query())->count();

        // agenda: konfirmasi Dinas 10 Sep — SEMUA agenda (dinas + bidang mana pun) tampil untuk
        // semua user; saat lingkup "bidang saya" → agenda dinas umum + bidang sendiri saja.
        $agendaQ = Agenda::query();
        if ($bid) $agendaQ->where(fn ($w) => $w->whereNull('id_bidang')->orWhere('id_bidang', $bid));
        $agendaMendatang = (clone $agendaQ)->with('bidang')->terdekat()->limit(5)->get();
        $agendaTerlewat = (clone $agendaQ)->terlewat()->count();

        $padSum = Pad::where('tahun', $padTahun)
            ->select(DB::raw('COALESCE(SUM(kumulatif),0) realisasi'), DB::raw('COALESCE(SUM(CASE WHEN target_perubahan>0 THEN target_perubahan ELSE target_tahunan END),0) target'))
            ->first();
        $padPersen = $padSum && $padSum->target > 0 ? round($padSum->realisasi / $padSum->target * 100, 1) : null;

        // v23: kartu utama = "Tingkat capaian keseluruhan per tahun" dari modul
        // capaian kinerja (sama persis dgn halaman /kinerja/capaian?tampilan=statistik).
        [$rTahun, $rPct] = \App\Models\CapaianTahunan::tingkatPerTahun();
        $ringkas = $rTahun ? [
            'labels' => $rTahun,
            'datasets' => [['label' => '% indikator tercapai (≥100%)', 'data' => $rPct, 'type' => 'line', 'color' => '#0f172a', 'fill' => true]],
        ] : null;

        // grafik bulanan cadangan: tahun dengan data, fallback ke data terdekat
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
        $kinerjaSelesai = $scope(CapaianProgram::query()->where('tahun', $kinerjaTahun)->where('level', 'sub_kegiatan'))
            ->where('persentase', '>=', 100)->count();

        return view('dashboard.index', compact(
            'jumlahPegawai', 'jumlahPegawaiNonaktif', 'jumlahPegawaiPensiun', 'jumlahDokumen',
            'agendaMendatang', 'agendaTerlewat', 'punyaBidang', 'lingkupSaya',
            'padTahun', 'padSum', 'padPersen', 'prestasiTerbaru', 'chart', 'ringkas', 'kinerja', 'kinerjaTotal',
            'kinerjaSelesai', 'kinerjaTahun'
        ));
    }

    /** JSON angka kartu pegawai — dipanggil tiap 10 detik oleh dashboard agar tetap mutakhir (realtime). */
    public function kartu(Request $request): JsonResponse
    {
        return response()->json([
            'pegawai_aktif' => Pegawai::where('status', 'aktif')->count(),
            'pegawai_nonaktif' => Pegawai::where('status', 'nonaktif')->count(),
            'pegawai_pensiun' => Pegawai::where('status', 'pensiun')->count(),
        ]);
    }
}
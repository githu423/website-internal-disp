<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Data Pegawai.
 *
 * Penanda dan saringan "belum lengkap" hanya untuk admin.
 * Kartu non-aktif / pensiun dari dashboard mengarah ke halaman ini
 * dengan filter status yang sudah diset.
 */
class PegawaiController extends Controller
{
    /**
     * Daftar pegawai + strip rekap jumlah.
     */
    public function index(Request $request): View
    {
        // ===== 1. HAK AKSES fitur "kelengkapan" =====
        $bolehKelengkapan = (bool) optional($request->user())->isAdmin();

        // ===== 2. SARIKAN KATA KUNCI =====
        // v22: "belum lengkap" adalah FILTER, bukan teks cari biasa — dikenali juga dari kotak cari.
        $kataKunci = trim((string) $request->query('q', ''));
        $kunciNormal = strtolower(preg_replace('/\s+/', '', $kataKunci));
        $modeBelum = $bolehKelengkapan && ($kunciNormal === 'belumlengkap' || (bool) $request->query('belum'));

        // ===== 3. FILTER STATUS (dari kartu dashboard) =====
        // Konfirmasi Dinas 10 Sep: kartu dashboard pegawai non-aktif / pensiun masuk ke sini
        // langsung terfilter sesuai kartunya (?status=nonaktif / ?status=pensiun).
        $statusFilter = (string) $request->query('status', '');
        if (! in_array($statusFilter, ['aktif', 'nonaktif', 'pensiun'], true)) {
            $statusFilter = '';
        }

        // ===== 4. DAFTAR PEGAWAI =====
        $query = Pegawai::query()->with('bidang');
        if ($kunciNormal !== 'belumlengkap') {
            $query->cari($kataKunci ?: null);
        }
        if ($modeBelum) {
            $query->belumLengkap(); // chip merah / kata kunci: hanya yang datanya bolong
        }
        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        // ===== 5. REKAP JUMLAH (SELURUH DATA, TIDAK TERFILTER CARI) =====
        // Permintaan Ricky, 7 Sep 2026: angka ini selalu kondisi seluruh data.
        $totalSemua = Pegawai::count();
        $totalBelum = Pegawai::belumLengkap()->count();
        $statistik = [
            'semua'  => $totalSemua,
            'l'      => Pegawai::where('jenis_kelamin', 'L')->count(),
            'p'      => Pegawai::where('jenis_kelamin', 'P')->count(),
            'pns'    => Pegawai::where('jenis_asn', 'PNS')->count(),
            'nonpns' => Pegawai::where(fn ($query) => $query->where('jenis_asn', '!=', 'PNS')->orWhereNull('jenis_asn'))->count(),
            'belum'  => $totalBelum,
            'lengkap' => $totalSemua - $totalBelum,
            'aktif'  => Pegawai::where('status', 'aktif')->count(),
            'nonaktif' => Pegawai::where('status', 'nonaktif')->count(),
            'pensiun' => Pegawai::where('status', 'pensiun')->count(),
        ];

        // ===== RESPONS =====
        return view('pegawai.index', [
            'pegawai' => $query->orderBy('nama')->paginate(15)->withQueryString(),
            'statistik' => $statistik,
            'modeBelum' => $modeBelum,
            'bolehKelengkapan' => $bolehKelengkapan,
            'statusFilter' => $statusFilter,
        ]);
    }
}

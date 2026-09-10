<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PegawaiController extends Controller
{
        public function index(Request $request): View
    {
        // v24: penanda & saringan "belum lengkap" KHUSUS admin (permintaan 7 Sep).
        $bolehKelengkapan = (bool) optional($request->user())->isAdmin();
        // v22: "belum lengkap" adalah FILTER, bukan teks cari biasa — dikenali juga dari kotak cari.
        $cari = trim((string) $request->query('q', ''));
        $kunci = strtolower(preg_replace('/\s+/', '', $cari));
        $modeBelum = $bolehKelengkapan && ($kunci === 'belumlengkap' || (bool) $request->query('belum'));
        // Konfirmasi Dinas 10 Sep: kartu dashboard pegawai non-aktif / pensiun masuk ke sini
        // langsung terfilter sesuai kartunya (?status=nonaktif / ?status=pensiun).
        $status = (string) $request->query('status', '');
        if (! in_array($status, ['aktif', 'nonaktif', 'pensiun'], true)) $status = '';
        $q = Pegawai::query()->with('bidang');
        if ($kunci !== 'belumlengkap') $q->cari($cari ?: null);
        if ($modeBelum) $q->belumLengkap(); // chip merah / kata kunci: hanya yang datanya bolong
        if ($status !== '') $q->where('status', $status);

        // Rekap jumlah untuk strip di bawah tabel (permintaan Ricky, 7 Sep 2026).
        // Sengaja TIDAK ikut terfilter kotak cari: angka ini selalu kondisi seluruh data.
        $semua = Pegawai::count();
        $belum = Pegawai::belumLengkap()->count();
        $statistik = [
            'semua'  => $semua,
            'l'      => Pegawai::where('jenis_kelamin', 'L')->count(),
            'p'      => Pegawai::where('jenis_kelamin', 'P')->count(),
            'pns'    => Pegawai::where('jenis_asn', 'PNS')->count(),
            'nonpns' => Pegawai::where(fn ($w) => $w->where('jenis_asn', '!=', 'PNS')->orWhereNull('jenis_asn'))->count(),
            'belum'  => $belum,
            'lengkap' => $semua - $belum,
            'aktif'  => Pegawai::where('status', 'aktif')->count(),
            'nonaktif' => Pegawai::where('status', 'nonaktif')->count(),
            'pensiun' => Pegawai::where('status', 'pensiun')->count(),
        ];

        return view('pegawai.index', [
            'pegawai' => $q->orderBy('nama')->paginate(15)->withQueryString(),
            'statistik' => $statistik,
            'modeBelum' => $modeBelum,
            'bolehKelengkapan' => $bolehKelengkapan,
            'statusFilter' => $status,
        ]);
    }
}

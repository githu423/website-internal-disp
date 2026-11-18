<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\LogAktivitas;
use App\Services\NotifikasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Agenda dinas dan bidang.
 *
 * Semua agenda (dinas + milik bidang mana pun) tampil untuk semua pengguna,
 * dikelompokkan per bidang di tampilan.
 */
class AgendaController extends Controller
{
    /**
     * Daftar agenda per bulan, beserta agenda terdekat.
     */
    public function index(Request $request): View
    {
        $pengguna = $request->user();

        // ===== 1. TENTUKAN BULAN YANG DITAMPILKAN =====
        $bulan = $request->query('bulan') ?: now()->format('Y-m');
        [$tahun, $bulanAngka] = array_pad(explode('-', $bulan), 2, null);
        $tahun = (int) $tahun;
        $bulanAngka = (int) ($bulanAngka ?: now()->month);

        // ===== 2. AMBIL AGENDA BULAN INI =====
        // Konfirmasi Dinas 10 Sep 2026: SEMUA agenda (dinas + milik bidang mana pun) tampil
        // untuk semua user — dikelompokkan per bidang di tampilan. Tidak ada lagi yang tersembunyi.
        $query = Agenda::query()->with('bidang')->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulanAngka);

        $items = $query->orderBy('tanggal')->orderBy('jam_mulai')->get();
        $terdekat = Agenda::query()->with('bidang')->terdekat()->limit(5)->get();

        // ===== 3. TAUTAN BULAN SEBELUMNYA / BERIKUTNYA =====
        return view('agenda.index', [
            'items' => $items, 'bulanIni' => sprintf('%04d-%02d', $tahun, $bulanAngka),
            'terdekat' => $terdekat,
            'bulanLalu' => sprintf('%04d-%02d', now()->setDate($tahun, $bulanAngka, 1)->subMonth()->year, now()->setDate($tahun, $bulanAngka, 1)->subMonth()->month),
            'bulanDepan' => sprintf('%04d-%02d', now()->setDate($tahun, $bulanAngka, 1)->addMonth()->year, now()->setDate($tahun, $bulanAngka, 1)->addMonth()->month),
        ]);
    }

    /**
     * Menambah agenda dinas (khusus admin).
     */
    public function store(Request $request): RedirectResponse
    {
        // ===== PEMERIKSAAN AKSES =====
        $pengguna = $request->user();
        abort_unless($pengguna->isAdmin(), 403, 'Agenda dinas diatur oleh admin. Anggota bidang dapat menambah agenda lewat halaman bidangnya masing-masing.');

        // ===== VALIDASI & SIMPAN =====
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:180'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'tanggal' => ['required', 'date'],
            'jam_mulai' => ['nullable', 'date_format:H:i'],
            'jam_selesai' => ['nullable', 'date_format:H:i'],
            'lokasi' => ['nullable', 'string', 'max:120'],
            'kategori' => ['nullable', 'in:rapat,kegiatan,kunjungan,lainnya'],
            'id_bidang' => ['nullable', 'integer', 'exists:bidang,id'],
        ]);
        $data['created_by'] = $pengguna->id;
        $agendaBaru = Agenda::create($data);

        // ===== KIRIM NOTIFIKASI (OPSIONAL) =====
        if ($request->boolean('beri_tahu')) {
            NotifikasiService::broadcast('Agenda baru: '.$agendaBaru->judul,
                $agendaBaru->tanggal->translatedFormat('d F Y').($agendaBaru->jam_mulai ? ' · '.$agendaBaru->jam_mulai.' WIB' : '').($agendaBaru->lokasi ? " · {$agendaBaru->lokasi}" : ''),
                'agenda', route('agenda.index'));
        }

        // ===== CATAT LOG & RESPONS =====
        LogAktivitas::catat('tambah agenda', $agendaBaru->judul);

        return back()->with('sukses', 'Agenda ditambahkan.');
    }

    /**
     * Menandai agenda selesai / belum (admin atau pembuat).
     */
    public function selesai(Request $request, Agenda $agenda): RedirectResponse
    {
        // ===== PEMERIKSAAN AKSES =====
        $pengguna = $request->user();
        abort_unless($pengguna->isAdmin() || $pengguna->id === $agenda->created_by, 403, 'Status agenda hanya dapat diubah admin atau pembuatnya.');

        // ===== TOGGLE STATUS =====
        $agenda->update(['status' => $agenda->status === 'selesai' ? 'belum' : 'selesai']);

        return back()->with('sukses', 'Status agenda diperbarui.');
    }

    /**
     * Menghapus agenda (admin atau pembuat).
     */
    public function destroy(Request $request, Agenda $agenda): RedirectResponse
    {
        // ===== PEMERIKSAAN AKSES =====
        $pengguna = $request->user();
        abort_unless($pengguna->isAdmin() || $pengguna->id === $agenda->created_by, 403);

        // ===== HAPUS =====
        $agenda->delete();

        return back()->with('sukses', 'Agenda dihapus.');
    }
}

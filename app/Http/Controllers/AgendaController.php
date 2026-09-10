<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\LogAktivitas;
use App\Services\NotifikasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgendaController extends Controller
{
        public function index(Request $request): View
    {
        $u = $request->user();
        $bulan = $request->query('bulan') ?: now()->format('Y-m');
        [$y, $m] = array_pad(explode('-', $bulan), 2, null);
        $y = (int) $y; $m = (int) ($m ?: now()->month);

        // Konfirmasi Dinas 10 Sep 2026: SEMUA agenda (dinas + milik bidang mana pun) tampil
        // untuk semua user — dikelompokkan per bidang di tampilan. Tidak ada lagi yang tersembunyi.
        $q = Agenda::query()->with('bidang')->whereYear('tanggal', $y)->whereMonth('tanggal', $m);

        $items = $q->orderBy('tanggal')->orderBy('jam_mulai')->get();
        $terdekat = Agenda::query()->with('bidang')->terdekat()->limit(5)->get();

        return view('agenda.index', [
            'items' => $items, 'bulanIni' => sprintf('%04d-%02d', $y, $m),
            'terdekat' => $terdekat,
            'bulanLalu' => sprintf('%04d-%02d', now()->setDate($y, $m, 1)->subMonth()->year, now()->setDate($y, $m, 1)->subMonth()->month),
            'bulanDepan' => sprintf('%04d-%02d', now()->setDate($y, $m, 1)->addMonth()->year, now()->setDate($y, $m, 1)->addMonth()->month),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $u = $request->user();
        abort_unless($u->isAdmin(), 403, 'Agenda dinas diatur oleh admin. Anggota bidang dapat menambah agenda lewat halaman bidangnya masing-masing.');
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
        $data['created_by'] = $u->id;
        $a = Agenda::create($data);

        if ($request->boolean('beri_tahu')) {
            NotifikasiService::broadcast('Agenda baru: '.$a->judul,
                $a->tanggal->translatedFormat('d F Y').($a->jam_mulai ? ' · '.$a->jam_mulai.' WIB' : '').($a->lokasi ? " · {$a->lokasi}" : ''),
                'agenda', route('agenda.index'));
        }
        LogAktivitas::catat('tambah agenda', $a->judul);
        return back()->with('sukses', 'Agenda ditambahkan.');
    }

    public function selesai(Request $request, Agenda $agenda): RedirectResponse
    {
        $u = $request->user();
        abort_unless($u->isAdmin() || $u->id === $agenda->created_by, 403, 'Status agenda hanya dapat diubah admin atau pembuatnya.');
        $agenda->update(['status' => $agenda->status === 'selesai' ? 'belum' : 'selesai']);
        return back()->with('sukses', 'Status agenda diperbarui.');
    }

    public function destroy(Request $request, Agenda $agenda): RedirectResponse
    {
        $u = $request->user();
        abort_unless($u->isAdmin() || $u->id === $agenda->created_by, 403);
        $agenda->delete();
        return back()->with('sukses', 'Agenda dihapus.');
    }
}

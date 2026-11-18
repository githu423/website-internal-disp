<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Bidang;
use App\Models\LogAktivitas;
use App\Services\NotifikasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengelolaan agenda oleh admin.
 */
class AgendaController extends Controller
{
    /**
     * Daftar agenda dengan filter status.
     */
    public function index(Request $request): View
    {
        $query = Agenda::query()->with('bidang');
        if ($filterStatus = $request->query('status')) {
            $query->where('status', $filterStatus);
        }

        return view('admin.agenda.index', [
            'items' => $query->orderByDesc('tanggal')->paginate(12)->withQueryString(),
            'bidangs' => Bidang::orderBy('id')->get(),
        ]);
    }

    /**
     * Menambah agenda dinas.
     */
    public function store(Request $request): RedirectResponse
    {
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
        $data['created_by'] = $request->user()->id;
        $agendaBaru = Agenda::create($data);

        // ===== KIRIM NOTIFIKASI (OPSIONAL) =====
        if ($request->boolean('beri_tahu')) {
            NotifikasiService::broadcast('Agenda baru: '.$agendaBaru->judul,
                $agendaBaru->tanggal->translatedFormat('d F Y').($agendaBaru->jam_mulai ? " · {$data['jam_mulai']} WIB" : ''), 'agenda', route('agenda.index'));
        }

        // ===== CATAT LOG & RESPONS =====
        LogAktivitas::catat('tambah agenda', $agendaBaru->judul);

        return back()->with('sukses', 'Agenda ditambahkan.');
    }

    /**
     * Mengubah status agenda.
     */
    public function update(Request $request, Agenda $agenda): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:belum,selesai,dibatalkan']]);
        $agenda->update($data);

        return back()->with('sukses', 'Status diperbarui.');
    }

    /**
     * Menghapus agenda.
     */
    public function destroy(Agenda $agenda): RedirectResponse
    {
        $agenda->delete();

        return back()->with('sukses', 'Agenda dihapus.');
    }
}

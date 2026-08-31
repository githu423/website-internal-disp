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

class AgendaController extends Controller
{
    public function index(Request $request): View
    {
        $q = Agenda::query()->with('bidang');
        if ($f = $request->query('status')) $q->where('status', $f);
        return view('admin.agenda.index', [
            'items' => $q->orderByDesc('tanggal')->paginate(12)->withQueryString(),
            'bidangs' => Bidang::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $request->validate([
            'judul' => ['required', 'string', 'max:180'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'tanggal' => ['required', 'date'],
            'jam_mulai' => ['nullable', 'date_format:H:i'],
            'jam_selesai' => ['nullable', 'date_format:H:i'],
            'lokasi' => ['nullable', 'string', 'max:120'],
            'kategori' => ['nullable', 'in:rapat,kegiatan,kunjungan,lainnya'],
            'id_bidang' => ['nullable', 'integer', 'exists:bidang,id'],
        ]);
        $d['created_by'] = $request->user()->id;
        $a = Agenda::create($d);
        if ($request->boolean('beri_tahu')) {
            NotifikasiService::broadcast('Agenda baru: '.$a->judul,
                $a->tanggal->translatedFormat('d F Y').($a->jam_mulai ? " · {$d['jam_mulai']} WIB" : ''), 'agenda', route('agenda.index'));
        }
        LogAktivitas::catat('tambah agenda', $a->judul);
        return back()->with('sukses', 'Agenda ditambahkan.');
    }

    public function update(Request $request, Agenda $agenda): RedirectResponse
    {
        $d = $request->validate(['status' => ['required', 'in:belum,selesai,dibatalkan']]);
        $agenda->update($d);
        return back()->with('sukses', 'Status diperbarui.');
    }

    public function destroy(Agenda $agenda): RedirectResponse
    {
        $agenda->delete();
        return back()->with('sukses', 'Agenda dihapus.');
    }
}

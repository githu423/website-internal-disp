<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Bidang;
use App\Models\Dokumen;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BidangController extends Controller
{
    public function index(Request $request): View
    {
        $u = $request->user();
        abort_unless($u->isAdmin() || $u->isBidang(), 403);
        $bidang = $u->isAdmin()
            ? Bidang::withCount(['pegawais', 'dokumen', 'agenda'])->get()
            : collect([$u->bidang->loadCount(['pegawais', 'dokumen', 'agenda'])]);
        return view('bidang.index', ['bidangList' => $bidang->filter()]);
    }

    public function show(Request $request, Bidang $bidang): View
    {
        abort_unless($request->user()->isAdmin() || $request->user()->id_bidang === $bidang->id, 403, 'Hanya bidang terkait yang dapat membuka halaman ini.');
        return view('bidang.show', [
            'b' => $bidang,
            'pegawais' => Pegawai::where('id_bidang', $bidang->id)->orderBy('nama')->get(),
            'dokumens' => Dokumen::where('id_bidang', $bidang->id)->latest()->limit(8)->get(),
            'agenda' => Agenda::where('id_bidang', $bidang->id)->orderByDesc('tanggal')->limit(6)->get(),
        ]);
    }
}

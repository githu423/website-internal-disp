<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PegawaiController extends Controller
{
    public function index(Request $request): View
    {
        $u = $request->user();
        $q = Pegawai::query()->with('bidang')->cari($request->query('q'));
        if (! $u->isAdmin()) $q->where('id_bidang', $u->id_bidang ?: -1);
        return view('pegawai.index', ['pegawai' => $q->orderBy('nama')->paginate(15)->withQueryString()]);
    }
}

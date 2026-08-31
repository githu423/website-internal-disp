<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $q = LogAktivitas::query();
        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('nama', 'like', "%$s%")->orWhere('aksi', 'like', "%$s%")->orWhere('keterangan', 'like', "%$s%"));
        }
        return view('admin.log.index', ['items' => $q->latest()->paginate(25)->withQueryString()]);
    }
}

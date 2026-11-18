<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Daftar log aktivitas (khusus admin).
 */
class LogController extends Controller
{
    /**
     * Menampilkan log aktivitas dengan filter pencarian.
     */
    public function index(Request $request): View
    {
        // ===== FILTER PENCARIAN =====
        $query = LogAktivitas::query();

        if ($kataKunci = trim((string) $request->query('q'))) {
            $query->where(fn ($queryCari) => $queryCari->where('nama', 'like', "%$kataKunci%")->orWhere('aksi', 'like', "%$kataKunci%")->orWhere('keterangan', 'like', "%$kataKunci%"));
        }

        // ===== RESPONS =====
        return view('admin.log.index', ['items' => $query->latest()->paginate(25)->withQueryString()]);
    }
}

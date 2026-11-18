<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\CapaianProgram;
use App\Models\Dokumen;
use App\Models\LogAktivitas;
use App\Models\Pad;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\View\View;

/**
 * Dashboard khusus admin: ringkasan jumlah data + log aktivitas terbaru.
 */
class DashboardController extends Controller
{
    /**
     * Menampilkan kartu statistik dan 12 log aktivitas terakhir.
     */
    public function index(): View
    {
        // ===== KUMPULKAN STATISTIK & LOG TERBARU =====
        return view('admin.dashboard.index', [
            'stats' => [
                'users' => User::count(),
                'pegawai' => Pegawai::count(),
                'dokumen' => Dokumen::count(),
                'agenda' => Agenda::where('status', 'belum')->count(),
                'kinerja' => CapaianProgram::where('level', 'sub_kegiatan')->count(),
                'pad' => Pad::count(),
            ],
            'logs' => LogAktivitas::latest()->limit(12)->get(),
        ]);
    }
}

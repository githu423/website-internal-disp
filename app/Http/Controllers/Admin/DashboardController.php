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

class DashboardController extends Controller
{
    public function index(): View
    {
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

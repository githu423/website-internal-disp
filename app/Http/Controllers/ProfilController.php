<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\Pegawai;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman profil pribadi — untuk SEMUA user tanpa terkecuali (permintaan Ricky, 7 Sep 2026).
 * Data yang diisi di sini menulis ke baris pegawai tertaut-NIP, sehingga kolom "—" di
 * daftar Data Pegawai terisi dan masa kerja ikut terkoreksi real-time.
 */
class ProfilController extends Controller
{
    public function index(Request $request): View
    {
        $u = $request->user();
        return view('profil.index', ['u' => $u, 'p' => $u->pegawai]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $u = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'jabatan' => ['nullable', 'string', 'max:150'],
            'pangkat_golongan' => ['nullable', 'string', 'max:80'],
            'jenis_asn' => ['nullable', 'in:PNS,PPPK'],
            'pendidikan' => ['nullable', 'string', 'max:60'],
            'unit_kerja' => ['nullable', 'string', 'max:150'],
            'tmt_cpns' => ['nullable', 'date'],
            'tmt_sekarang' => ['nullable', 'date'],
            'tmt_jabatan' => ['nullable', 'date'],
        ]);

        if ($u->name !== $data['name']) { $u->name = $data['name']; $u->save(); }
        $isi = collect($data)->except('name')->filter(fn ($v) => $v !== null && $v !== '')->all();

        $p = $u->pegawai;
        if (! $p) {
            if (! $u->nip) {
                return back()->with('gagal', 'Akun Anda belum punya NIP — minta admin mengisinya lewat Admin ▸ Akun & Akses.');
            }
            $p = Pegawai::create(['nip' => $u->nip, 'nama' => $u->name, 'status' => 'aktif', 'id_bidang' => $u->id_bidang] + $isi);
        } else {
            if ($p->nama !== $u->name) { $p->nama = $u->name; }
            $p->fill($isi)->save();
        }

        LogAktivitas::catat('perbarui profil', $u->nip ?: $u->name);
        return back()->with('sukses', 'Profil tersimpan — data kepegawaian Anda ikut terisi dan masa kerja dihitung ulang otomatis.');
    }
}

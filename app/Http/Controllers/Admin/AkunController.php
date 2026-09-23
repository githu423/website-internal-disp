<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bidang;
use App\Models\Pegawai;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Services\ImportService;
use Illuminate\View\View;

class AkunController extends Controller
{
    public function index(Request $request): View
    {
        $q = User::query()->with(['bidang', 'pegawai']);
        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('nip', 'like', "%$s%"));
        }
        if ($r = $request->query('role')) $q->where('role', $r);
        $st = (string) $request->query('status', '');
        if (in_array($st, ['aktif', 'nonaktif', 'pensiun'], true)) $q->where('status', $st);
        return view('admin.akun.index', [
            'users' => $q->orderBy('name')->paginate(15)->withQueryString(),
            'bidangs' => Bidang::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'nip' => ['required', 'string', 'max:40', 'unique:users,nip'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', 'in:admin,bidang,pegawai'],
            'id_bidang' => ['nullable', 'integer', 'exists:bidang,id'],
            'jabatan' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:aktif,nonaktif,pensiun'],
        ]);
        // peran & bidang yang dibiarkan kosong = otomatis mengikuti data pegawai (jabatan/unit kerja)
        $peg = null;
        if (! empty($d['nip'])) {
            $peg = Pegawai::where('nip', $d['nip'])->first();
            if ($peg && (empty($d['role']) || empty($d['id_bidang']) || empty($d['jabatan']))) {
                $d['id_bidang'] = ($d['id_bidang'] ?? null) ?: ($peg->id_bidang ?? Bidang::tebakDariTeks(trim(($peg->jabatan ?? '') . ' ' . ($peg->unit_kerja ?? ''))));
                $d['role'] = ($d['role'] ?? null) ?: ImportService::peranDariJabatan($peg->jabatan);
                $d['jabatan'] = ($d['jabatan'] ?? null) ?: $peg->jabatan;
            }
            // warisi status pegawai bila tidak diisi eksplisit
            if (empty($d['status']) && $peg) $d['status'] = $peg->status ?? 'aktif';
        }
        $d['role'] = $d['role'] ?: 'pegawai';
        $d['status'] = $d['status'] ?? 'aktif';
        $user = User::create($d);
        // sinkron ke tabel pegawai agar filter status di Data Pegawai langsung tampil
        $pegSync = Pegawai::where('nip', $user->nip)->first();
        if ($pegSync) {
            if ($pegSync->status !== $user->status) $pegSync->update(['status' => $user->status]);
        } elseif (in_array($user->status, ['nonaktif', 'pensiun'], true)) {
            // akun nonaktif/pensiun tanpa data pegawai → buat baris minimal agar muncul di daftar pegawai
            Pegawai::create(['nip' => $user->nip, 'nama' => $user->name, 'status' => $user->status, 'id_bidang' => $user->id_bidang, 'jabatan' => $user->jabatan]);
        }
        LogAktivitas::catat('buat akun', $d['nip']);
        return back()->with('sukses', 'Akun dibuat.');
    }

    public function update(Request $request, User $akun): RedirectResponse
    {
        $d = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', 'in:admin,bidang,pegawai'],
            'id_bidang' => ['nullable', 'integer', 'exists:bidang,id'],
            'status' => ['required', 'in:aktif,nonaktif,pensiun'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);
        if (! empty($d['password'])) {
            $akun->fill(['password' => $d['password']]);
        }
        unset($d['password']);
        $akun->update($d);
        // sinkron status ke data pegawai dengan NIP sama (agar rekap & daftar pegawai konsisten)
        $peg = Pegawai::where('nip', $akun->nip)->first();
        if ($peg) {
            if ($peg->status !== $d['status']) $peg->update(['status' => $d['status']]);
        } elseif (in_array($d['status'], ['nonaktif', 'pensiun'], true)) {
            Pegawai::create(['nip' => $akun->nip, 'nama' => $akun->name, 'status' => $d['status'], 'id_bidang' => $akun->id_bidang, 'jabatan' => $akun->jabatan]);
        }
        LogAktivitas::catat('ubah akun', $akun->nip);
        return back()->with('sukses', 'Akun diperbarui.');
    }

    public function destroy(Request $request, User $akun): RedirectResponse
    {
        abort_if($akun->id === $request->user()->id, 422, 'Tidak dapat menghapus akun sendiri.');
        $akun->delete();
        LogAktivitas::catat('hapus akun', $akun->nip);
        return back()->with('sukses', 'Akun dihapus.');
    }

    /** Satu klik: cocokkan peran, bidang, dan status seluruh akun dengan data pegawai terbaru. */
    public function sinkronPeran(): RedirectResponse
    {
        $ubah = ImportService::sinkronAkunPegawai();
        LogAktivitas::catat('sinkron peran akun', "{$ubah} akun disinkronkan");
        return back()->with('sukses', $ubah
            ? "{$ubah} akun disesuaikan otomatis (peran, bidang & status) mengikuti data pegawai."
            : 'Semua akun sudah sesuai data pegawai — tidak ada yang diubah.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bidang;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AkunController extends Controller
{
    public function index(Request $request): View
    {
        $q = User::query()->with('bidang');
        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('nip', 'like', "%$s%"));
        }
        if ($r = $request->query('role')) $q->where('role', $r);
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
            'role' => ['required', 'in:admin,bidang,pegawai'],
            'id_bidang' => ['nullable', 'integer', 'exists:bidang,id'],
            'jabatan' => ['nullable', 'string', 'max:120'],
        ]);
        User::create($d);
        LogAktivitas::catat('buat akun', $d['nip']);
        return back()->with('sukses', 'Akun dibuat.');
    }

    public function update(Request $request, User $akun): RedirectResponse
    {
        $d = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', 'in:admin,bidang,pegawai'],
            'id_bidang' => ['nullable', 'integer', 'exists:bidang,id'],
            'status' => ['required', 'in:aktif,nonaktif'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);
        if (! empty($d['password'])) {
            $akun->fill(['password' => $d['password']]);
        }
        unset($d['password']);
        $akun->update($d);
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
}

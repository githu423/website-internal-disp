<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bidang;
use App\Models\LogAktivitas;
use App\Models\Pegawai;
use App\Services\ImportService;
use App\Services\NotifikasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PegawaiController extends Controller
{
    public function index(Request $request): View
    {
        $q = Pegawai::query()->with('bidang')->cari($request->query('q'));
        if ($b = $request->query('bidang')) $q->where('id_bidang', $b);
        return view('admin.pegawai.index', [
            'pegawai' => $q->orderBy('nama')->paginate(15)->withQueryString(),
            'bidangs' => Bidang::orderBy('id')->get(),
            'total' => Pegawai::count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $this->validateData($request);
        Pegawai::create($d);
        LogAktivitas::catat('tambah pegawai', $d['nama']);
        return back()->with('sukses', 'Pegawai ditambahkan.');
    }

    public function update(Request $request, Pegawai $pegawai): RedirectResponse
    {
        $pegawai->update($this->validateData($request, $pegawai->id));
        return back()->with('sukses', 'Data pegawai diperbarui.');
    }

    public function destroy(Pegawai $pegawai): RedirectResponse
    {
        $pegawai->delete();
        LogAktivitas::catat('hapus pegawai', $pegawai->nama);
        return back()->with('sukses', 'Data pegawai dihapus.');
    }

    /** Import manual dari file CSV/Excel daftar pegawai */
    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls,ods', 'max:10240']]);
        $path = $request->file('file')->getRealPath();
        try {
            $hasil = ImportService::applyPegawai(ImportService::rowsFrom($path));
        } catch (\Throwable $e) {
            return back()->with('gagal', 'File gagal dibaca: '.$e->getMessage());
        }
        if (($hasil['dibuat'] + $hasil['diperbarui']) === 0) {
            return back()->with('gagal', 'Tidak ada baris pegawai yang dikenali. Pastikan format file sama dengan template Dinas (kolom No., NAMA LENGKAP, N I P, ...).');
        }
        NotifikasiService::broadcast('Data pegawai diperbarui',
            "Import: {$hasil['dibuat']} baru, {$hasil['diperbarui']} diperbarui, {$hasil['akun_baru']} akun akses dibuat" . (($hasil['akun_disinkron'] ?? 0) ? ", {$hasil['akun_disinkron']} akun disinkron peran/bidangnya." : '.'),
            'import', route('pegawai.index'));
        LogAktivitas::catat('import pegawai', json_encode($hasil));
        return back()->with('sukses', "Import selesai: {$hasil['dibuat']} pegawai baru, {$hasil['diperbarui']} diperbarui, {$hasil['akun_baru']} akun akses dibuat"
            . (($hasil['akun_disinkron'] ?? 0) ? ", {$hasil['akun_disinkron']} akun disinkron peran & bidangnya dari jabatan." : '.'));
    }

    private function validateData(Request $request, ?int $exceptId = null): array
    {
        $d = $request->validate([
            'nip' => ['required', 'string', 'max:40', 'unique:pegawai,nip'.($exceptId ? ",$exceptId" : '')],
            'nama' => ['required', 'string', 'max:150'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'jabatan' => ['nullable', 'string', 'max:150'],
            'pangkat_golongan' => ['nullable', 'string', 'max:100'],
            'jenis_asn' => ['nullable', 'in:PNS,PPPK'],
            'tmt_cpns' => ['nullable', 'date'],
            'tmt_sekarang' => ['nullable', 'date'],
            'tmt_jabatan' => ['nullable', 'date'],
            'pendidikan' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:aktif,nonaktif,pensiun'],
            'id_bidang' => ['nullable', 'integer', 'exists:bidang,id'],
        ]);
        return $d;
    }
}

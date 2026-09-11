<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bidang;
use App\Models\LogAktivitas;
use App\Models\Pegawai;
use App\Models\User;
use App\Services\ImportService;
use App\Services\NotifikasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PegawaiController extends Controller
{
    public function index(Request $request): View
    {
        // v22: kata kunci "belum lengkap" di kotak cari = saringan kelengkapan, bukan teks nama.
        $cari = trim((string) $request->query('q', ''));
        $kunci = strtolower(preg_replace('/\s+/', '', $cari));
        $modeBelum = $kunci === 'belumlengkap' || (bool) $request->query('belum');
        $q = Pegawai::query()->with('bidang');
        if ($kunci !== 'belumlengkap') $q->cari($cari ?: null);
        if ($b = $request->query('bidang')) $q->where('id_bidang', $b);
        if ($modeBelum) $q->belumLengkap(); // chip merah / kata kunci: hanya yang datanya bolong
        $belumLengkap = Pegawai::belumLengkap()->count();
        $total = Pegawai::count();
        return view('admin.pegawai.index', [
            'pegawai' => $q->orderBy('nama')->paginate(15)->withQueryString(),
            'bidangs' => Bidang::orderBy('id')->get(),
            'total' => $total,
            'belumLengkap' => $belumLengkap,
            'lengkap' => $total - $belumLengkap,
            'modeBelum' => $modeBelum,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $this->validateData($request);

        // Konfirmasi Dinas 10 Sep: tambah manual pun "terkonek" — bidang dibiarkan kosong
        // → ditebak dari jabatan/unit kerja; dan akun login dibuat otomatis seperti import.
        if (empty($d['id_bidang'])) {
            $d['id_bidang'] = Bidang::tebakDariTeks(trim(($d['jabatan'] ?? '') . ' ' . ($d['unit_kerja'] ?? '')));
        }
        Pegawai::create($d);

        $akunBaru = false;
        if (! User::where('nip', $d['nip'])->exists()) {
            User::create([
                'name' => $d['nama'],
                'nip' => $d['nip'],
                'password' => config('dispar.password_default_pegawai'),
                'role' => ImportService::peranDariJabatan($d['jabatan'] ?? null),
                'id_bidang' => $d['id_bidang'],
                'jabatan' => $d['jabatan'] ?? null,
            ]);
            $akunBaru = true;
        }
        LogAktivitas::catat('tambah pegawai', $d['nama']);
        return back()->with('sukses', 'Pegawai ditambahkan.' . ($akunBaru ? ' Akun login dibuat otomatis (password default dinas).' : ''));
    }

    public function update(Request $request, Pegawai $pegawai): RedirectResponse
    {
        $d = $this->validateData($request, $pegawai->id);
        $pegawai->update($d);

        // Konfirmasi Dinas 10 Sep: admin cukup ubah SATU kolom (mis. hanya bidang) lalu simpan.
        // Perubahan bidang/jabatan pada data pegawai ikut diterapkan ke AKUN LOGIN terkait
        // (non-admin) — agar lingkup "Bidang Saya", agenda bidang, dan peran pengelola
        // langsung mengikuti data terbaru tanpa perlu menunggu import ulang Excel.
        $akunUbah = [];
        $user = User::where('nip', $pegawai->nip)->first();
        if ($user && $user->role !== 'admin') {
            if (! empty($d['id_bidang']) && (int) $user->id_bidang !== (int) $d['id_bidang']) $akunUbah['id_bidang'] = $d['id_bidang'];
            if (! empty($d['jabatan']) && $user->jabatan !== $d['jabatan']) $akunUbah['jabatan'] = $d['jabatan'];
            $peran = ImportService::peranDariJabatan($pegawai->jabatan);
            if ($user->role !== $peran) $akunUbah['role'] = $peran;
            if ($akunUbah) $user->update($akunUbah);
        }
        LogAktivitas::catat('ubah pegawai', $pegawai->nama . ($akunUbah ? ' (' . implode(', ', array_keys($akunUbah)) . ')' : ''));
        return back()->with('sukses', 'Data pegawai diperbarui' . ($akunUbah ? ' — akun login ikut disesuaikan.' : '.'));
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
        $f = $request->file('file');
        $path = $f->getRealPath();
        $namaAsli = $f->getClientOriginalName();
        try {
            $hasil = ImportService::applyPegawai(ImportService::rowsFrom($path, $namaAsli));
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
        // Mode edit ($exceptId terisi, 10 Sep 2026): semua aturan diberi awalan 'sometimes' —
        // admin boleh mengirim SEBAGIAN kolom saja (mis. hanya bidang/status) dan tetap
        // bisa simpan; kolom yang tidak dikirim tidak ikut diubah. Mode tambah tetap ketat.
        $r = fn (array $rules) => $exceptId ? array_merge(['sometimes'], $rules) : $rules;
        return $request->validate([
            'nip' => $r(['required', 'string', 'max:40', 'unique:pegawai,nip'.($exceptId ? ','.$exceptId : '')]),
            'nama' => $r(['required', 'string', 'max:150']),
            'tempat_lahir' => $r(['nullable', 'string', 'max:100']),
            'tanggal_lahir' => $r(['nullable', 'date']),
            'jenis_kelamin' => $r(['nullable', 'in:L,P']),
            'jabatan' => $r(['nullable', 'string', 'max:150']),
            'pangkat_golongan' => $r(['nullable', 'string', 'max:100']),
            'jenis_asn' => $r(['nullable', 'in:PNS,PPPK']),
            'tmt_cpns' => $r(['nullable', 'date']),
            'tmt_sekarang' => $r(['nullable', 'date']),
            'tmt_jabatan' => $r(['nullable', 'date']),
            'pendidikan' => $r(['nullable', 'string', 'max:100']),
            'unit_kerja' => $r(['nullable', 'string', 'max:120']),
            'pangkat_awal' => $r(['nullable', 'string', 'max:100']),
            'diklat_struktural' => $r(['nullable', 'string', 'max:150']),
            'diklat_fungsional' => $r(['nullable', 'string', 'max:150']),
            'diklat_teknis' => $r(['nullable', 'string', 'max:150']),
            'status' => $r(['required', 'in:aktif,nonaktif,pensiun']),
            'id_bidang' => $r(['nullable', 'integer', 'exists:bidang,id']),
        ]);
    }
}
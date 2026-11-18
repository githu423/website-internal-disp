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

/**
 * Pengelolaan data pegawai oleh admin.
 *
 * - Tambah/ubah/hapus manual.
 * - Import dari CSV/Excel.
 * - Perubahan bidang/jabatan/status ikut disinkronkan ke akun login terkait.
 */
class PegawaiController extends Controller
{
    /**
     * Daftar pegawai + rekap status & kelengkapan.
     */
    public function index(Request $request): View
    {
        // ===== 1. SARIKAN KATA KUNCI =====
        // v22: kata kunci "belum lengkap" di kotak cari = saringan kelengkapan, bukan teks nama.
        $kataKunci = trim((string) $request->query('q', ''));
        $kunciNormal = strtolower(preg_replace('/\s+/', '', $kataKunci));
        $modeBelum = $kunciNormal === 'belumlengkap' || (bool) $request->query('belum');

        // ===== 2. FILTER STATUS & BIDANG =====
        $statusFilter = (string) $request->query('status', '');
        if (! in_array($statusFilter, ['aktif', 'nonaktif', 'pensiun'], true)) {
            $statusFilter = '';
        }
        $query = Pegawai::query()->with('bidang');
        if ($kunciNormal !== 'belumlengkap') {
            $query->cari($kataKunci ?: null);
        }
        if ($idBidang = $request->query('bidang')) {
            $query->where('id_bidang', $idBidang);
        }
        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }
        if ($modeBelum) {
            $query->belumLengkap(); // chip merah / kata kunci: hanya yang datanya bolong
        }

        // ===== 3. REKAP JUMLAH =====
        $totalBelumLengkap = Pegawai::belumLengkap()->count();
        $total = Pegawai::count();
        $statistik = [
            'semua' => $total,
            'aktif' => Pegawai::where('status', 'aktif')->count(),
            'nonaktif' => Pegawai::where('status', 'nonaktif')->count(),
            'pensiun' => Pegawai::where('status', 'pensiun')->count(),
        ];

        // ===== 4. RESPONS =====
        return view('admin.pegawai.index', [
            'pegawai' => $query->orderBy('nama')->paginate(15)->withQueryString(),
            'bidangs' => Bidang::orderBy('id')->get(),
            'total' => $total,
            'belumLengkap' => $totalBelumLengkap,
            'lengkap' => $total - $totalBelumLengkap,
            'modeBelum' => $modeBelum,
            'statusFilter' => $statusFilter,
            'statistik' => $statistik,
        ]);
    }

    /**
     * Menambah pegawai manual (akun login dibuat otomatis bila belum ada).
     */
    public function store(Request $request): RedirectResponse
    {
        // ===== 1. VALIDASI =====
        $data = $this->validateData($request);

        // ===== 2. TEBAK BIDANG JIKA KOSONG =====
        // Konfirmasi Dinas 10 Sep: tambah manual pun "terkonek" — bidang dibiarkan kosong
        // → ditebak dari jabatan/unit kerja; dan akun login dibuat otomatis seperti import.
        if (empty($data['id_bidang'])) {
            $data['id_bidang'] = Bidang::tebakDariTeks(trim(($data['jabatan'] ?? '').' '.($data['unit_kerja'] ?? '')));
        }
        $pegawai = Pegawai::create($data);

        // ===== 3. BUAT / SINKRONKAN AKUN LOGIN =====
        $akunBaru = false;
        if (! User::where('nip', $data['nip'])->exists()) {
            User::create([
                'name' => $data['nama'],
                'nip' => $data['nip'],
                'password' => config('dispar.password_default_pegawai'),
                'role' => ImportService::peranDariJabatan($data['jabatan'] ?? null),
                'id_bidang' => $data['id_bidang'],
                'jabatan' => $data['jabatan'] ?? null,
                'status' => $data['status'] ?? 'aktif',
            ]);
            $akunBaru = true;
        } else {
            // Sinkron status bila akun sudah ada (nonaktif/pensiun harus cermin).
            $akun = User::where('nip', $data['nip'])->first();
            if ($akun && $akun->status !== ($data['status'] ?? 'aktif')) {
                $akun->update(['status' => $data['status'] ?? 'aktif']);
            }
        }

        // ===== 4. CATAT LOG & RESPONS =====
        LogAktivitas::catat('tambah pegawai', $data['nama']);

        return back()->with('sukses', 'Pegawai ditambahkan.'.($akunBaru ? ' Akun login dibuat otomatis (password default dinas).' : ''));
    }

    /**
     * Memperbarui data pegawai + menyinkronkan akun login terkait.
     */
    public function update(Request $request, Pegawai $pegawai): RedirectResponse
    {
        // ===== 1. VALIDASI =====
        $data = $this->validateData($request, $pegawai->id);
        $pegawai->update($data);

        // ===== 2. SINKRONKAN AKUN LOGIN =====
        // Konfirmasi Dinas 10 Sep: admin cukup ubah SATU kolom (mis. hanya bidang) lalu simpan.
        // Perubahan bidang/jabatan/status pada data pegawai ikut diterapkan ke AKUN LOGIN terkait
        // — agar lingkup "Bidang Saya", agenda bidang, peran pengelola, dan blokir login
        // langsung mengikuti data terbaru tanpa perlu menunggu import ulang Excel.
        $perubahanAkun = [];
        $akun = User::where('nip', $pegawai->nip)->first();
        if ($akun) {
            // Sinkron status selalu (berlaku juga untuk admin bila pegawai pensiun/nonaktif).
            if (isset($data['status']) && $akun->status !== $data['status']) {
                $perubahanAkun['status'] = $data['status'];
            }
            if ($akun->role !== 'admin') {
                if (! empty($data['id_bidang']) && (int) $akun->id_bidang !== (int) $data['id_bidang']) {
                    $perubahanAkun['id_bidang'] = $data['id_bidang'];
                }
                if (! empty($data['jabatan']) && $akun->jabatan !== $data['jabatan']) {
                    $perubahanAkun['jabatan'] = $data['jabatan'];
                }
                $peran = ImportService::peranDariJabatan($pegawai->jabatan);
                if ($akun->role !== $peran) {
                    $perubahanAkun['role'] = $peran;
                }
            }
            if ($perubahanAkun) {
                $akun->update($perubahanAkun);
            }
        }

        // ===== 3. CATAT LOG & RESPONS =====
        LogAktivitas::catat('ubah pegawai', $pegawai->nama.($perubahanAkun ? ' ('.implode(', ', array_keys($perubahanAkun)).')' : ''));

        return back()->with('sukses', 'Data pegawai diperbarui'.($perubahanAkun ? ' — akun login ikut disesuaikan.' : '.'));
    }

    /**
     * Menghapus data pegawai.
     */
    public function destroy(Pegawai $pegawai): RedirectResponse
    {
        $pegawai->delete();
        LogAktivitas::catat('hapus pegawai', $pegawai->nama);

        return back()->with('sukses', 'Data pegawai dihapus.');
    }

    /**
     * Import manual dari file CSV/Excel daftar pegawai.
     */
    public function import(Request $request): RedirectResponse
    {
        // ===== 1. VALIDASI BERKAS =====
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls,ods', 'max:10240']]);
        $file = $request->file('file');
        $pathSementara = $file->getRealPath();
        $namaAsli = $file->getClientOriginalName();

        // ===== 2. BACA & TERAPKAN =====
        try {
            $hasil = ImportService::applyPegawai(ImportService::rowsFrom($pathSementara, $namaAsli));
        } catch (\Throwable $kesalahan) {
            return back()->with('gagal', 'File gagal dibaca: '.$kesalahan->getMessage());
        }
        if (($hasil['dibuat'] + $hasil['diperbarui']) === 0) {
            return back()->with('gagal', 'Tidak ada baris pegawai yang dikenali. Pastikan format file sama dengan template Dinas (kolom No., NAMA LENGKAP, N I P, ...).');
        }

        // ===== 3. NOTIFIKASI & LOG =====
        NotifikasiService::broadcast('Data pegawai diperbarui',
            "Import: {$hasil['dibuat']} baru, {$hasil['diperbarui']} diperbarui, {$hasil['akun_baru']} akun akses dibuat" . (($hasil['akun_disinkron'] ?? 0) ? ", {$hasil['akun_disinkron']} akun disinkron peran/bidangnya." : '.'),
            'import', route('pegawai.index'));
        LogAktivitas::catat('import pegawai', json_encode($hasil));

        return back()->with('sukses', "Import selesai: {$hasil['dibuat']} pegawai baru, {$hasil['diperbarui']} diperbarui, {$hasil['akun_baru']} akun akses dibuat"
            .(($hasil['akun_disinkron'] ?? 0) ? ", {$hasil['akun_disinkron']} akun disinkron peran & bidangnya dari jabatan." : '.'));
    }

    /**
     * Aturan validasi data pegawai.
     *
     * Mode edit ($exceptId terisi, 10 Sep 2026): semua aturan diberi awalan 'sometimes' —
     * admin boleh mengirim SEBAGIAN kolom saja (mis. hanya bidang/status) dan tetap
     * bisa simpan; kolom yang tidak dikirim tidak ikut diubah. Mode tambah tetap ketat.
     */
    private function validateData(Request $request, ?int $exceptId = null): array
    {
        $aturanKadang = fn (array $rules) => $exceptId ? array_merge(['sometimes'], $rules) : $rules;

        return $request->validate([
            'nip' => $aturanKadang(['required', 'string', 'max:40', 'unique:pegawai,nip'.($exceptId ? ','.$exceptId : '')]),
            'nama' => $aturanKadang(['required', 'string', 'max:150']),
            'tempat_lahir' => $aturanKadang(['nullable', 'string', 'max:100']),
            'tanggal_lahir' => $aturanKadang(['nullable', 'date']),
            'jenis_kelamin' => $aturanKadang(['nullable', 'in:L,P']),
            'jabatan' => $aturanKadang(['nullable', 'string', 'max:150']),
            'pangkat_golongan' => $aturanKadang(['nullable', 'string', 'max:100']),
            'jenis_asn' => $aturanKadang(['nullable', 'in:PNS,PPPK']),
            'tmt_cpns' => $aturanKadang(['nullable', 'date']),
            'tmt_sekarang' => $aturanKadang(['nullable', 'date']),
            'tmt_jabatan' => $aturanKadang(['nullable', 'date']),
            'pendidikan' => $aturanKadang(['nullable', 'string', 'max:100']),
            'unit_kerja' => $aturanKadang(['nullable', 'string', 'max:120']),
            'pangkat_awal' => $aturanKadang(['nullable', 'string', 'max:100']),
            'diklat_struktural' => $aturanKadang(['nullable', 'string', 'max:150']),
            'diklat_fungsional' => $aturanKadang(['nullable', 'string', 'max:150']),
            'diklat_teknis' => $aturanKadang(['nullable', 'string', 'max:150']),
            'status' => $aturanKadang(['required', 'in:aktif,nonaktif,pensiun']),
            'id_bidang' => $aturanKadang(['nullable', 'integer', 'exists:bidang,id']),
        ]);
    }
}

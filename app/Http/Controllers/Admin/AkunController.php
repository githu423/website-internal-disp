<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bidang;
use App\Models\LogAktivitas;
use App\Models\Pegawai;
use App\Models\User;
use App\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengelolaan akun & akses oleh admin.
 *
 * Peran: admin, bidang, pegawai.
 * Peran/bidang yang dibiarkan kosong otomatis mengikuti data pegawai
 * (jabatan / unit kerja).
 */
class AkunController extends Controller
{
    /**
     * Daftar akun dengan filter pencarian, peran, dan status.
     */
    public function index(Request $request): View
    {
        $query = User::query()->with(['bidang', 'pegawai']);
        if ($kataKunci = trim((string) $request->query('q'))) {
            $query->where(fn ($queryCari) => $queryCari->where('name', 'like', "%$kataKunci%")->orWhere('nip', 'like', "%$kataKunci%"));
        }
        if ($filterRole = $request->query('role')) {
            $query->where('role', $filterRole);
        }
        $filterStatus = (string) $request->query('status', '');
        if (in_array($filterStatus, ['aktif', 'nonaktif', 'pensiun'], true)) {
            $query->where('status', $filterStatus);
        }

        return view('admin.akun.index', [
            'users' => $query->orderBy('name')->paginate(15)->withQueryString(),
            'bidangs' => Bidang::orderBy('id')->get(),
        ]);
    }

    /**
     * Membuat akun baru.
     */
    public function store(Request $request): RedirectResponse
    {
        // ===== 1. VALIDASI INPUT =====
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'nip' => ['required', 'string', 'max:40', 'unique:users,nip'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', 'in:admin,bidang,pegawai'],
            'id_bidang' => ['nullable', 'integer', 'exists:bidang,id'],
            'jabatan' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:aktif,nonaktif,pensiun'],
        ]);

        // ===== 2. LENGKAPI PERAN/BIDANG DARI DATA PEGAWAI =====
        $pegawai = null;
        if (! empty($data['nip'])) {
            $pegawai = Pegawai::where('nip', $data['nip'])->first();
            if ($pegawai && (empty($data['role']) || empty($data['id_bidang']) || empty($data['jabatan']))) {
                $data['id_bidang'] = ($data['id_bidang'] ?? null) ?: ($pegawai->id_bidang ?? Bidang::tebakDariTeks(trim(($pegawai->jabatan ?? '').' '.($pegawai->unit_kerja ?? ''))));
                $data['role'] = ($data['role'] ?? null) ?: ImportService::peranDariJabatan($pegawai->jabatan);
                $data['jabatan'] = ($data['jabatan'] ?? null) ?: $pegawai->jabatan;
            }
            // Warisi status pegawai bila tidak diisi eksplisit.
            if (empty($data['status']) && $pegawai) {
                $data['status'] = $pegawai->status ?? 'aktif';
            }
        }
        $data['role'] = $data['role'] ?: 'pegawai';
        $data['status'] = $data['status'] ?? 'aktif';

        // ===== 3. SIMPAN AKUN =====
        $akunBaru = User::create($data);

        // ===== 4. SINKRONKAN KE DATA PEGAWAI =====
        // Sinkron ke tabel pegawai agar filter status di Data Pegawai langsung tampil.
        $pegawaiTertaut = Pegawai::where('nip', $akunBaru->nip)->first();
        if ($pegawaiTertaut) {
            if ($pegawaiTertaut->status !== $akunBaru->status) {
                $pegawaiTertaut->update(['status' => $akunBaru->status]);
            }
        } elseif (in_array($akunBaru->status, ['nonaktif', 'pensiun'], true)) {
            // Akun nonaktif/pensiun tanpa data pegawai → buat baris minimal agar muncul di daftar pegawai.
            Pegawai::create(['nip' => $akunBaru->nip, 'nama' => $akunBaru->name, 'status' => $akunBaru->status, 'id_bidang' => $akunBaru->id_bidang, 'jabatan' => $akunBaru->jabatan]);
        }

        // ===== 5. CATAT LOG & RESPONS =====
        LogAktivitas::catat('buat akun', $data['nip']);

        return back()->with('sukses', 'Akun dibuat.');
    }

    /**
     * Memperbarui akun (termasuk ganti password).
     */
    public function update(Request $request, User $akun): RedirectResponse
    {
        // ===== 1. VALIDASI INPUT =====
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', 'in:admin,bidang,pegawai'],
            'id_bidang' => ['nullable', 'integer', 'exists:bidang,id'],
            'status' => ['required', 'in:aktif,nonaktif,pensiun'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        // ===== 2. SIMPAN (PASSWORD DIPROSES TERPISAH) =====
        if (! empty($data['password'])) {
            $akun->fill(['password' => $data['password']]);
        }
        unset($data['password']);
        $akun->update($data);

        // ===== 3. SINKRONKAN STATUS KE DATA PEGAWAI =====
        // Agar rekap & daftar pegawai konsisten.
        $pegawai = Pegawai::where('nip', $akun->nip)->first();
        if ($pegawai) {
            if ($pegawai->status !== $data['status']) {
                $pegawai->update(['status' => $data['status']]);
            }
        } elseif (in_array($data['status'], ['nonaktif', 'pensiun'], true)) {
            Pegawai::create(['nip' => $akun->nip, 'nama' => $akun->name, 'status' => $data['status'], 'id_bidang' => $akun->id_bidang, 'jabatan' => $akun->jabatan]);
        }

        // ===== 4. CATAT LOG & RESPONS =====
        LogAktivitas::catat('ubah akun', $akun->nip);

        return back()->with('sukses', 'Akun diperbarui.');
    }

    /**
     * Menghapus akun (tidak boleh akun sendiri).
     */
    public function destroy(Request $request, User $akun): RedirectResponse
    {
        abort_if($akun->id === $request->user()->id, 422, 'Tidak dapat menghapus akun sendiri.');
        $akun->delete();
        LogAktivitas::catat('hapus akun', $akun->nip);

        return back()->with('sukses', 'Akun dihapus.');
    }

    /**
     * Satu klik: cocokkan peran, bidang, dan status seluruh akun dengan data pegawai terbaru.
     */
    public function sinkronPeran(): RedirectResponse
    {
        $jumlahDiubah = ImportService::sinkronAkunPegawai();
        LogAktivitas::catat('sinkron peran akun', "{$jumlahDiubah} akun disinkronkan");

        return back()->with('sukses', $jumlahDiubah
            ? "{$jumlahDiubah} akun disesuaikan otomatis (peran, bidang & status) mengikuti data pegawai."
            : 'Semua akun sudah sesuai data pegawai — tidak ada yang diubah.');
    }
}

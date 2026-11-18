<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\Pegawai;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Halaman profil pribadi — untuk SEMUA user tanpa terkecuali (permintaan Ricky, 7 Sep 2026).
 *
 * Data yang diisi di sini menulis ke baris pegawai tertaut-NIP, sehingga kolom "—" di
 * daftar Data Pegawai terisi dan masa kerja ikut terkoreksi real-time.
 */
class ProfilController extends Controller
{
    /**
     * Menampilkan profil akun dan data kepegawaian tertaut.
     */
    public function index(Request $request): View
    {
        $pengguna = $request->user();

        // 'u' dan 'p' dipertahankan karena menjadi nama variabel di tampilan (profil/index).
        return view('profil.index', ['u' => $pengguna, 'p' => $pengguna->pegawai]);
    }

    /**
     * Menyimpan data profil + data kepegawaian.
     */
    public function simpan(Request $request): RedirectResponse
    {
        $pengguna = $request->user();

        // ===== 1. VALIDASI INPUT =====
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

        // ===== 2. SINKRONKAN NAMA AKUN =====
        if ($pengguna->name !== $data['name']) {
            $pengguna->name = $data['name'];
            $pengguna->save();
        }

        // Hanya kolom yang benar-benar diisi yang ditulis ke data kepegawaian.
        $dataPegawai = collect($data)->except('name')->filter(fn ($nilai) => $nilai !== null && $nilai !== '')->all();

        // ===== 3. TULIS KE DATA KEPEGAWAIAN =====
        $pegawai = $pengguna->pegawai;
        if (! $pegawai) {
            // Belum ada baris pegawai: buat baru jika akun punya NIP.
            if (! $pengguna->nip) {
                return back()->with('gagal', 'Akun Anda belum punya NIP — minta admin mengisinya lewat Admin ▸ Akun & Akses.');
            }
            $pegawai = Pegawai::create(['nip' => $pengguna->nip, 'nama' => $pengguna->name, 'status' => 'aktif', 'id_bidang' => $pengguna->id_bidang] + $dataPegawai);
        } else {
            if ($pegawai->nama !== $pengguna->name) {
                $pegawai->nama = $pengguna->name;
            }
            $pegawai->fill($dataPegawai)->save();
        }

        // ===== 4. CATAT LOG & RESPONS =====
        LogAktivitas::catat('perbarui profil', $pengguna->nip ?: $pengguna->name);

        return back()->with('sukses', 'Profil tersimpan — data kepegawaian Anda ikut terisi dan masa kerja dihitung ulang otomatis.');
    }

    /**
     * Mengganti password sendiri.
     */
    public function gantiPassword(Request $request): RedirectResponse
    {
        $pengguna = $request->user();

        // ===== 1. VALIDASI INPUT =====
        $request->validate([
            'password_lama'  => ['required', 'string'],
            'password_baru'  => ['required', 'string', 'min:8', 'confirmed', Password::defaults()],
        ], [
            'password_lama.required' => 'Password lama wajib diisi.',
            'password_baru.required' => 'Password baru wajib diisi.',
            'password_baru.min'      => 'Password baru minimal 8 karakter.',
            'password_baru.confirmed'=> 'Konfirmasi password baru tidak cocok.',
        ]);

        // ===== 2. COCOKKAN PASSWORD LAMA =====
        if (! Hash::check($request->password_lama, $pengguna->password)) {
            return back()
                ->withErrors(['password_lama' => 'Password lama yang Anda masukkan salah.'])
                ->withInput();
        }

        // ===== 3. SIMPAN PASSWORD BARU =====
        $pengguna->update(['password' => $request->password_baru]);

        // ===== 4. CATAT LOG & RESPONS =====
        LogAktivitas::catat('ganti password', $pengguna->nip ?: $pengguna->name);

        return back()->with('sukses_pw', 'Password berhasil diperbarui. Gunakan password baru Anda pada login berikutnya.');
    }
}

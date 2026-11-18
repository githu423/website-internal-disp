<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\Pad;
use App\Services\ImportService;
use App\Services\NotifikasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengelolaan data PAD oleh admin (manual + import Excel/CSV).
 */
class PadController extends Controller
{
    /**
     * Daftar PAD per tahun.
     */
    public function index(Request $request): View
    {
        $tahunList = Pad::tahunTersedia();
        $tahun = (int) ($request->query('tahun') ?: (in_array(now()->year, $tahunList) ? now()->year : ($tahunList[0] ?? now()->year)));

        return view('admin.pad.index', [
            'rows' => Pad::where('tahun', $tahun)->orderBy('kategori')->orderBy('id')->get(),
            'tahunList' => $tahunList,
            'tahun' => $tahun,
        ]);
    }

    /**
     * Menambah satu baris PAD manual.
     */
    public function store(Request $request): RedirectResponse
    {
        Pad::create($this->validateData($request));
        LogAktivitas::catat('tambah PAD manual');

        return back()->with('sukses', 'Data PAD ditambahkan.');
    }

    /**
     * Memperbarui satu baris PAD.
     */
    public function update(Request $request, Pad $pad): RedirectResponse
    {
        $pad->update($this->validateData($request));

        return back()->with('sukses', 'Data PAD diperbarui.');
    }

    /**
     * Menghapus satu baris PAD.
     */
    public function destroy(Pad $pad): RedirectResponse
    {
        $pad->delete();

        return back()->with('sukses', 'Baris dihapus.');
    }

    /**
     * Import PAD dari Excel/CSV (format: TAHUN, No./Uraian/Target/Realisasi bulanan).
     */
    public function import(Request $request): RedirectResponse
    {
        // ===== 1. VALIDASI BERKAS =====
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls,ods', 'max:20480']]);
        $file = $request->file('file');
        $pathSementara = $file->getRealPath();
        $namaAsli = $file->getClientOriginalName();

        // ===== 2. BACA & TERAPKAN =====
        try {
            $hasil = ImportService::applyPad(ImportService::rowsFrom($pathSementara, $namaAsli));
        } catch (\Throwable $kesalahan) {
            return back()->with('gagal', 'File gagal dibaca: '.$kesalahan->getMessage());
        }
        if ($hasil['imported'] === 0) {
            return back()->with('gagal', 'Tidak ada data PAD yang dikenali. Gunakan format file PAD (blok TAHUN + kolom No., No. Rekening, Uraian, Target, Realisasi Jan-Des, Jumlah, Sisa, %).');
        }

        // ===== 3. NOTIFIKASI & LOG =====
        $daftarTahun = implode(', ', $hasil['years']);
        $pesan = "Import PAD: {$hasil['dibuat']} objek baru, {$hasil['diperbarui']} diperbarui (tahun $daftarTahun) — koreksi manual & data bulan lalu tetap aman.";
        NotifikasiService::broadcast('Data PAD diperbarui', $pesan, 'import', route('pad.index'));
        LogAktivitas::catat('import PAD', "rows={$hasil['imported']} baru={$hasil['dibuat']} ubah={$hasil['diperbarui']} years=$daftarTahun");

        return back()->with('sukses', $pesan);
    }

    /**
     * Aturan validasi satu baris PAD.
     */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'kategori' => ['nullable', 'string', 'max:120'],
            'no_rekening' => ['nullable', 'string', 'max:64'],
            'objek' => ['required', 'string', 'max:200'],
            'target_tahunan' => ['nullable', 'numeric', 'min:0'],
            'target_perubahan' => ['nullable', 'numeric', 'min:0'],
            'realisasi_bulan_lalu' => ['nullable', 'numeric', 'min:0'],
            'realisasi_bulan_ini' => ['nullable', 'numeric', 'min:0'],
            'kumulatif' => ['nullable', 'numeric', 'min:0'],
            'persentase' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'sisa_target' => ['nullable', 'numeric'],
        ]);
    }
}

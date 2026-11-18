<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\Prestasi;
use App\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengelolaan data prestasi/penghargaan oleh admin.
 *
 * Prestasi bertanda "featured" diprioritaskan tampil di dashboard;
 * tanpa tanda, diputar otomatis setiap hari.
 */
class PrestasiController extends Controller
{
    /**
     * Daftar prestasi.
     */
    public function index(Request $request): View
    {
        return view('admin.prestasi.index', [
            'items' => Prestasi::orderByDesc('is_featured')->orderByDesc('tahun')->orderBy('id')->paginate(25),
            'featuredCount' => Prestasi::where('is_featured', true)->count(),
            'totalCount' => Prestasi::count(),
        ]);
    }

    /**
     * Tambah satu prestasi secara manual (tanpa import file).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_penghargaan' => ['required', 'string', 'max:500'],
            'nama_penerima' => ['nullable', 'string', 'max:255'],
            'pemberi_penghargaan' => ['nullable', 'string', 'max:255'],
            'tahun' => ['required', 'integer', 'min:1900', 'max:2100'],
            'tingkat' => ['nullable', 'string', 'max:50'],
        ]);
        $prestasi = Prestasi::create($data);
        LogAktivitas::catat('tambah prestasi', $prestasi->nama_penghargaan.' ('.$prestasi->tahun.')');

        return back()->with('sukses', 'Prestasi "'.$prestasi->nama_penghargaan.'" ditambahkan.');
    }

    /**
     * Ubah satu prestasi secara manual.
     */
    public function update(Request $request, Prestasi $prestasi): RedirectResponse
    {
        $data = $request->validate([
            'nama_penghargaan' => ['required', 'string', 'max:500'],
            'nama_penerima' => ['nullable', 'string', 'max:255'],
            'pemberi_penghargaan' => ['nullable', 'string', 'max:255'],
            'tahun' => ['required', 'integer', 'min:1900', 'max:2100'],
            'tingkat' => ['nullable', 'string', 'max:50'],
        ]);
        $prestasi->update($data);
        LogAktivitas::catat('ubah prestasi', $data['nama_penghargaan'].' ('.$data['tahun'].')');

        return back()->with('sukses', 'Prestasi "'.$data['nama_penghargaan'].'" diperbarui.');
    }

    /**
     * Menandai / menghapus tanda "tampil di dashboard".
     */
    public function toggleFeatured(Prestasi $prestasi): RedirectResponse
    {
        $prestasi->is_featured = ! $prestasi->is_featured;
        $prestasi->save();

        $pesan = $prestasi->is_featured
            ? "Prestasi \"{$prestasi->nama_penghargaan}\" ditandai untuk ditampilkan di dashboard."
            : "Tanda tampil di dashboard untuk \"{$prestasi->nama_penghargaan}\" telah dinonaktifkan.";

        LogAktivitas::catat('atur tampilan prestasi', $pesan);

        return back()->with('sukses', $pesan);
    }

    /**
     * Menghapus semua tanda manual (kembali ke rotasi harian otomatis).
     */
    public function resetFeatured(): RedirectResponse
    {
        Prestasi::where('is_featured', true)->update(['is_featured' => false]);
        LogAktivitas::catat('reset tampilan prestasi', 'Semua pilihan manual prestasi dikembalikan ke rotasi harian otomatis.');

        return back()->with('sukses', 'Semua pilihan manual direset. Prestasi di dashboard kini sepenuhnya dirotasi otomatis setiap hari.');
    }

    /**
     * Import prestasi dari Excel/CSV.
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
            $rows = ImportService::rowsFrom($pathSementara, $namaAsli);
            $hasil = ImportService::applyPrestasi($rows);
        } catch (\Throwable $kesalahan) {
            return back()->with('gagal', 'File gagal dibaca: '.$kesalahan->getMessage());
        }
        if ($hasil['imported'] === 0) {
            return back()->with('gagal', 'Tidak ada data prestasi yang dikenali. Gunakan format file dengan blok "TAHUN 20XX" dan kolom No, Nama Penghargaan, Nama Penerima, Pemberi Penghargaan, Tahun Penghargaan.');
        }

        // ===== 3. LOG & RESPONS =====
        $daftarTahun = implode(', ', $hasil['years']);
        $pesan = "Import Prestasi: {$hasil['imported']} baris (tahun $daftarTahun).";
        LogAktivitas::catat('import prestasi', $pesan);

        return back()->with('sukses', $pesan);
    }

    /**
     * Menghapus data prestasi.
     */
    public function destroy(Prestasi $prestasi): RedirectResponse
    {
        $prestasi->delete();
        LogAktivitas::catat('hapus prestasi', $prestasi->nama_penghargaan);

        return back()->with('sukses', 'Data prestasi dihapus.');
    }
}

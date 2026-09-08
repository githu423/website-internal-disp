<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\Prestasi;
use App\Services\ImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrestasiController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.prestasi.index', [
            'items' => Prestasi::orderByDesc('is_featured')->orderByDesc('tahun')->orderBy('id')->paginate(25),
            'featuredCount' => Prestasi::where('is_featured', true)->count(),
            'totalCount' => Prestasi::count(),
        ]);
    }

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

    public function resetFeatured(): RedirectResponse
    {
        Prestasi::where('is_featured', true)->update(['is_featured' => false]);
        LogAktivitas::catat('reset tampilan prestasi', 'Semua pilihan manual prestasi dikembalikan ke rotasi harian otomatis.');

        return back()->with('sukses', 'Semua pilihan manual direset. Prestasi di dashboard kini sepenuhnya dirotasi otomatis setiap hari.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls,ods', 'max:10240']]);
        $f = $request->file('file');
        $path = $f->getRealPath();
        $namaAsli = $f->getClientOriginalName();
        try {
            $rows = ImportService::rowsFrom($path, $namaAsli);
            $hasil = ImportService::applyPrestasi($rows);
        } catch (\Throwable $e) {
            return back()->with('gagal', 'File gagal dibaca: ' . $e->getMessage());
        }
        if ($hasil['imported'] === 0) {
            return back()->with('gagal', 'Tidak ada data prestasi yang dikenali. Gunakan format file dengan blok "TAHUN 20XX" dan kolom No, Nama Penghargaan, Nama Penerima, Pemberi Penghargaan, Tahun Penghargaan.');
        }
        $yrs = implode(', ', $hasil['years']);
        $pesan = "Import Prestasi: {$hasil['imported']} baris (tahun $yrs).";
        LogAktivitas::catat('import prestasi', $pesan);

        return back()->with('sukses', $pesan);
    }

    public function destroy(Prestasi $prestasi): RedirectResponse
    {
        $prestasi->delete();
        LogAktivitas::catat('hapus prestasi', $prestasi->nama_penghargaan);

        return back()->with('sukses', 'Data prestasi dihapus.');
    }
}

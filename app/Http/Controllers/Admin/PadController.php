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

class PadController extends Controller
{
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

    public function store(Request $request): RedirectResponse
    {
        Pad::create($this->validateData($request));
        LogAktivitas::catat('tambah PAD manual');
        return back()->with('sukses', 'Data PAD ditambahkan.');
    }

    public function update(Request $request, Pad $pad): RedirectResponse
    {
        $pad->update($this->validateData($request));
        return back()->with('sukses', 'Data PAD diperbarui.');
    }

    public function destroy(Pad $pad): RedirectResponse
    {
        $pad->delete();
        return back()->with('sukses', 'Baris dihapus.');
    }

    /** Import PAD dari Excel/CSV (format: TAHUN, No./Uraian/Target/Realisasi bulanan) */
    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls,ods', 'max:20480']]);
        $path = $request->file('file')->getRealPath();
        try {
            $hasil = ImportService::applyPad(ImportService::rowsFrom($path));
        } catch (\Throwable $e) {
            return back()->with('gagal', 'File gagal dibaca: '.$e->getMessage());
        }
        if ($hasil['imported'] === 0) {
            return back()->with('gagal', 'Tidak ada data PAD yang dikenali. Gunakan format file PAD (blok TAHUN + kolom No., No. Rekening, Uraian, Target, Realisasi Jan-Des, Jumlah, Sisa, %).');
        }
        $yrs = implode(', ', $hasil['years']);
        NotifikasiService::broadcast('Data PAD diperbarui', "Import file PAD: {$hasil['imported']} objek pendapatan (tahun $yrs).", 'import', route('pad.index'));
        LogAktivitas::catat('import PAD', "rows={$hasil['imported']} years=$yrs");
        return back()->with('sukses', "Import PAD berhasil: {$hasil['imported']} baris untuk tahun $yrs.");
    }

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

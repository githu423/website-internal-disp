<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bidang;
use App\Models\CapaianProgram;
use App\Models\LogAktivitas;
use App\Services\ImportService;
use App\Services\NotifikasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Kinerja Fisik Sedinas — program/kegiatan/kegiatan rutin dinas.
 * Dipakai sebagai info + pengingat kegiatan di Dashboard Utama.
 */
class KinerjaController extends Controller
{
    public function index(Request $request): View
    {
        $tahunList = CapaianProgram::tahunTersedia();
        $tahun = (int) ($request->query('tahun') ?: ($tahunList[0] ?? now()->year));
        $q = CapaianProgram::where('tahun', $tahun)->with('bidang')->orderBy('urutan');
        if ($l = $request->query('level')) $q->where('level', $l);
        if ($s = trim((string) $request->query('q'))) $q->where('nama', 'like', "%$s%");

        return view('admin.kinerja.index', [
            'items' => $q->paginate(25)->withQueryString(),
            'tahunList' => $tahunList,
            'tahun' => $tahun,
            'bidangs' => Bidang::orderBy('id')->get(),
            'summary' => CapaianProgram::where('tahun', $tahun)->where('level', 'sub_kegiatan')->select(
                DB::raw('COUNT(*) total'),
                DB::raw("SUM(CASE WHEN persentase >= 100 THEN 1 ELSE 0 END) tuntas"),
                DB::raw("SUM(CASE WHEN persentase < 60 THEN 1 ELSE 0 END) lambat"),
            )->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $d = $this->validateData($request);
        $d['level'] = $d['level'] ?: 'sub_kegiatan';
        $d['urutan'] = (int) CapaianProgram::where('tahun', $d['tahun'])->max('urutan') + 1;
        CapaianProgram::create($d);
        LogAktivitas::catat('tambah kinerja', $d['nama']);
        return back()->with('sukses', 'Kegiatan ditambahkan.');
    }

    public function update(Request $request, CapaianProgram $kinerja): RedirectResponse
    {
        // 9 Sep 2026: dulu form edit modal hanya mengirim nama/target/satuan/capaian/persen/pagu/catatan,
        // padahal validasi menuntut "tahun" & "level" — update SELALU gagal validasi tanpa pesan (bug).
        // Sekarang: kolom yang tidak ikut dikirim otomatis diambil dari data lama.
        $request->mergeIfMissing([
            'tahun' => $kinerja->tahun,
            'level' => $kinerja->level,
            'sumber_dana' => $kinerja->sumber_dana,
            'indikator' => $kinerja->indikator,
            'satuan' => $kinerja->satuan,
            'id_bidang' => $kinerja->id_bidang,
        ]);
        $d = $this->validateData($request, $kinerja->id);
        $kinerja->update($d);
        LogAktivitas::catat('ubah kinerja', $d['nama']);
        return back()->with('sukses', 'Data kinerja «'.$d['nama'].'» diperbarui.');
    }

    public function destroy(CapaianProgram $kinerja): RedirectResponse
    {
        $kinerja->delete();
        LogAktivitas::catat('hapus kinerja', $kinerja->nama);
        return back()->with('sukses', 'Data dihapus.');
    }

    /** Import massal dari file Excel/CSV (Kinerja Fisik / DATA PARIWISATA format) */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls,ods', 'max:20480'],
            'ganti_tahun' => ['nullable', 'boolean'],
        ]);
        $f = $request->file('file');
        $path = $f->getRealPath();
        $namaAsli = $f->getClientOriginalName();
        try {
            $rows = ImportService::rowsFrom($path, $namaAsli);
            $hasil = ImportService::applyKinerja($rows, $request->boolean('ganti_tahun'));
        } catch (\Throwable $e) {
            return back()->with('gagal', 'File gagal dibaca: '.$e->getMessage());
        }
        if ($hasil['imported'] === 0) {
            return back()->with('gagal', 'Tidak ada data kinerja yang dikenali (file terbaca '.count($rows).' baris). '
                .'Pastikan file memuat baris "TAHUN <tahun>" (boleh "TAHUN ANGGARAN <tahun>" / rata tengah seperti di Excel), '
                .'lalu baris data dengan kolom PROGRAM / KEGIATAN / SUB KEGIATAN, Sumber Dana, Pagu, Target, Satuan, dan Total Capaian.');
        }
        $yrs = implode(', ', $hasil['years']);
        $ganti = $request->boolean('ganti_tahun');
        $pesan = "Import kinerja: {$hasil['dibuat']} baru, {$hasil['diperbarui']} diperbarui (tahun $yrs)"
            . ($ganti ? ' — data tahun tersebut diganti total.' : ' — data manual & tahun lain tetap aman.');
        NotifikasiService::broadcast('Data kinerja diperbarui', $pesan, 'import', route('admin.kinerja.index'));
        LogAktivitas::catat('import kinerja', "rows={$hasil['imported']} baru={$hasil['dibuat']} ubah={$hasil['diperbarui']} years=$yrs mode={$hasil['mode']}");
        return back()->with('sukses', $pesan);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'level' => ['required', 'in:program,kegiatan,sub_kegiatan'],
            'nama' => ['required', 'string', 'max:255'],
            'sumber_dana' => ['nullable', 'string', 'max:30'],
            'pagu_anggaran' => ['nullable', 'numeric', 'min:0'],
            'indikator' => ['nullable', 'string', 'max:255'],
            'target' => ['nullable', 'numeric', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:40'],
            'capaian' => ['nullable', 'numeric', 'min:0'],
            'persentase' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'id_bidang' => ['nullable', 'integer', 'exists:bidang,id'],
        ]);
    }
}

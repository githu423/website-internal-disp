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
 *
 * Dipakai sebagai info + pengingat kegiatan di Dashboard Utama.
 */
class KinerjaController extends Controller
{
    /**
     * Daftar kegiatan per tahun + ringkasan tuntas/lambat.
     */
    public function index(Request $request): View
    {
        // ===== 1. FILTER =====
        $tahunList = CapaianProgram::tahunTersedia();
        $tahun = (int) ($request->query('tahun') ?: ($tahunList[0] ?? now()->year));
        $query = CapaianProgram::where('tahun', $tahun)->with('bidang')->orderBy('urutan');
        if ($filterLevel = $request->query('level')) {
            $query->where('level', $filterLevel);
        }
        if ($kataKunci = trim((string) $request->query('q'))) {
            $query->where('nama', 'like', "%$kataKunci%");
        }

        // ===== 2. RESPONS =====
        return view('admin.kinerja.index', [
            'items' => $query->paginate(25)->withQueryString(),
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

    /**
     * Menambah kegiatan.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['level'] = $data['level'] ?: 'sub_kegiatan';
        $data['urutan'] = (int) CapaianProgram::where('tahun', $data['tahun'])->max('urutan') + 1;
        CapaianProgram::create($data);
        LogAktivitas::catat('tambah kinerja', $data['nama']);

        return back()->with('sukses', 'Kegiatan ditambahkan.');
    }

    /**
     * Memperbarui kegiatan.
     *
     * 9 Sep 2026: dulu form edit modal hanya mengirim nama/target/satuan/capaian/persen/pagu/catatan,
     * padahal validasi menuntut "tahun" & "level" — update SELALU gagal validasi tanpa pesan (bug).
     * Sekarang: kolom yang tidak ikut dikirim otomatis diambil dari data lama.
     */
    public function update(Request $request, CapaianProgram $kinerja): RedirectResponse
    {
        $request->mergeIfMissing([
            'tahun' => $kinerja->tahun,
            'level' => $kinerja->level,
            'sumber_dana' => $kinerja->sumber_dana,
            'indikator' => $kinerja->indikator,
            'satuan' => $kinerja->satuan,
            'id_bidang' => $kinerja->id_bidang,
        ]);
        $data = $this->validateData($request, $kinerja->id);
        $kinerja->update($data);
        LogAktivitas::catat('ubah kinerja', $data['nama']);

        return back()->with('sukses', 'Data kinerja «'.$data['nama'].'» diperbarui.');
    }

    /**
     * Menghapus kegiatan.
     */
    public function destroy(CapaianProgram $kinerja): RedirectResponse
    {
        $kinerja->delete();
        LogAktivitas::catat('hapus kinerja', $kinerja->nama);

        return back()->with('sukses', 'Data dihapus.');
    }

    /**
     * Import massal dari file Excel/CSV (Kinerja Fisik / DATA PARIWISATA format).
     */
    public function import(Request $request): RedirectResponse
    {
        // ===== 1. VALIDASI BERKAS =====
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls,ods', 'max:20480'],
            'ganti_tahun' => ['nullable', 'boolean'],
        ]);
        $file = $request->file('file');
        $pathSementara = $file->getRealPath();
        $namaAsli = $file->getClientOriginalName();

        // ===== 2. BACA & TERAPKAN =====
        try {
            $rows = ImportService::rowsFrom($pathSementara, $namaAsli);
            $hasil = ImportService::applyKinerja($rows, $request->boolean('ganti_tahun'));
        } catch (\Throwable $kesalahan) {
            return back()->with('gagal', 'File gagal dibaca: '.$kesalahan->getMessage());
        }
        if ($hasil['imported'] === 0) {
            return back()->with('gagal', 'Tidak ada data kinerja yang dikenali (file terbaca '.count($rows).' baris). '
                .'Pastikan file memuat baris "TAHUN <tahun>" (boleh "TAHUN ANGGARAN <tahun>" / rata tengah seperti di Excel), '
                .'lalu baris data dengan kolom PROGRAM / KEGIATAN / SUB KEGIATAN, Sumber Dana, Pagu, Target, Satuan, dan Total Capaian.');
        }

        // ===== 3. NOTIFIKASI & LOG =====
        $daftarTahun = implode(', ', $hasil['years']);
        $gantiTahun = $request->boolean('ganti_tahun');
        $pesan = "Import kinerja: {$hasil['dibuat']} baru, {$hasil['diperbarui']} diperbarui (tahun $daftarTahun)"
            .($gantiTahun ? ' — data tahun tersebut diganti total.' : ' — data manual & tahun lain tetap aman.');
        NotifikasiService::broadcast('Data kinerja diperbarui', $pesan, 'import', route('admin.kinerja.index'));
        LogAktivitas::catat('import kinerja', "rows={$hasil['imported']} baru={$hasil['dibuat']} ubah={$hasil['diperbarui']} years=$daftarTahun mode={$hasil['mode']}");

        return back()->with('sukses', $pesan);
    }

    /**
     * Aturan validasi data kegiatan.
     *
     * Catatan: pemanggil update() mengirim argumen kedua ($exceptId) yang saat ini
     * belum dipakai; argumen itu diabaikan agar tetap kompatibel.
     */
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

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\Statistik;
use App\Models\StatistikNilai;
use App\Services\ImportService;
use App\Services\NotifikasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kelola Statistik Pariwisata — format file "DATA STATISTIK PARIWISATA
 * KABUPATEN CIAMIS" (indikator × tahun, permintaan Ricky 7 Sep 2026).
 *
 * Format lama "target vs capaian per bulan" DICABUT dari modul ini karena
 * dobel dengan halaman Kegiatan & Kinerja; tabel capaian_bulanan tetap utuh
 * untuk modul Capaian Kinerja.
 */
class StatistikController extends Controller
{
    /**
     * Daftar indikator + nilai per tahun dalam bentuk grid.
     */
    public function index(Request $request): View
    {
        $baris = Statistik::with('nilais')->orderByRaw('COALESCE(no_urut, 99999)')->orderBy('indikator')->get();
        $tahunList = StatistikNilai::distinct()->orderBy('tahun')->pluck('tahun')->map(fn ($tahun) => (int) $tahun)->all();
        if (! $tahunList) {
            $tahunList = [(int) now()->year];
        }

        return view('admin.statistik.index', [
            'rows' => $baris->map(fn ($barisItem) => [
                'id' => $barisItem->id, 'no' => $barisItem->no_urut, 'indikator' => $barisItem->indikator,
                'satuan' => $barisItem->satuan, 'ket' => $barisItem->keterangan,
                'nilai' => $barisItem->nilais->pluck('nilai', 'tahun')->map(fn ($nilai) => (string) $nilai)->all(),
            ])->values(),
            'tahunList' => $tahunList,
            'total' => Statistik::count(),
        ]);
    }

    /**
     * Simpan seluruh grid sekaligus; baris ber-id dgn indikator dikosongkan/cek hapus = dihapus.
     */
    public function store(Request $request): RedirectResponse
    {
        // ===== 1. VALIDASI INPUT GRID =====
        $data = $request->validate([
            'tahunList' => ['required', 'array'],
            'rows' => ['nullable', 'array'],
            'rows.*.id' => ['nullable', 'integer', 'exists:statistik,id'],
            'rows.*.no_urut' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'rows.*.indikator' => ['nullable', 'string', 'max:180'],
            'rows.*.satuan' => ['nullable', 'string', 'max:40'],
            'rows.*.keterangan' => ['nullable', 'string', 'max:255'],
            'rows.*.nilai' => ['nullable', 'array'],
            'rows.*.hapus' => ['nullable', 'boolean'],
        ]);

        $daftarTahun = array_values(array_unique(array_map('intval', (array) $data['tahunList'])));
        $totalSimpan = 0;
        $totalHapus = 0;

        // ===== 2. PROSES TIAP BARIS =====
        foreach ((array) ($data['rows'] ?? []) as $barisInput) {
            $idBaris = $barisInput['id'] ?? null;
            if ($idBaris && ! empty($barisInput['hapus'])) {
                Statistik::destroy((int) $idBaris);
                $totalHapus++;

                continue;
            }

            $namaIndikator = trim((string) ($barisInput['indikator'] ?? ''));
            if ($namaIndikator === '') {
                continue; // baris baru kosong -> abaikan
            }

            $statistik = $idBaris ? Statistik::find((int) $idBaris) : null;
            if (! $statistik) {
                // Cari berdasarkan nama agar tidak dobel dgn hasil import.
                $statistik = Statistik::whereRaw('upper(indikator) = ?', [mb_strtoupper(preg_replace('/\s+/', ' ', $namaIndikator))])->first()
                    ?? new Statistik();
            }

            $statistik->indikator = preg_replace('/\s+/', ' ', $namaIndikator);
            $statistik->no_urut = ($noUrut = trim((string) ($barisInput['no_urut'] ?? ''))) !== '' ? (int) $noUrut : null;
            $statistik->satuan = trim((string) ($barisInput['satuan'] ?? '')) ?: null;
            $statistik->keterangan = trim((string) ($barisInput['keterangan'] ?? '')) ?: null;
            $statistik->save();

            // ===== 3. SIMPAN NILAI PER TAHUN =====
            foreach ($daftarTahun as $tahun) {
                $teksNilai = trim((string) ($barisInput['nilai'][$tahun] ?? ''));
                if ($teksNilai === '') {
                    StatistikNilai::where('id_statistik', $statistik->id)->where('tahun', $tahun)->delete();

                    continue;
                }
                $nilai = ImportService::angkaSel($teksNilai);
                if ($nilai === null) {
                    continue; // tidak bisa dibaca -> jangan menimpa data lama
                }
                StatistikNilai::updateOrCreate(['id_statistik' => $statistik->id, 'tahun' => $tahun], ['nilai' => $nilai]);
            }

            $totalSimpan++;
        }

        // ===== 4. CATAT LOG & RESPONS =====
        LogAktivitas::catat('ubah statistik pariwisata', "grid manual: $totalSimpan baris disimpan, $totalHapus dihapus");

        return back()->with('sukses', 'Statistik tersimpan'.($totalHapus ? " — $totalSimpan baris diperbarui, $totalHapus dihapus." : '.'));
    }

    /**
     * Impor file Excel/CSV format Data Statistik (judul & baris tahun deteksi otomatis).
     */
    public function import(Request $request): RedirectResponse
    {
        // ===== 1. VALIDASI BERKAS =====
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls,ods', 'max:20480']]);
        $file = $request->file('file');

        // ===== 2. BACA & PARSE =====
        try {
            $rows = ImportService::rowsFrom($file->getRealPath(), $file->getClientOriginalName());
        } catch (\Throwable $kesalahan) {
            return back()->with('gagal', 'File gagal dibaca: '.$kesalahan->getMessage());
        }
        $parsed = ImportService::parseStatistik($rows);
        if (! $parsed) {
            return back()->with('gagal', 'Baris indikator tidak dikenali. Gunakan format file "Data Statistik Pariwisata": baris judul bebas, lalu header "No. | Indikator | Satuan | Tahun | Keterangan", baris daftar tahun (mis. 2017..2025), kemudian datanya.');
        }

        // ===== 3. TERAPKAN & KABARI =====
        $hasil = ImportService::applyStatistik($parsed);
        NotifikasiService::broadcast('Statistik pariwisata diperbarui',
            "Import statistik: {$hasil['dibuat']} indikator baru, {$hasil['diperbarui']} diperbarui, {$hasil['sel']} sel nilai terisi.",
            'import', route('statistik.index'));
        LogAktivitas::catat('impor statistik pariwisata', json_encode($hasil));

        return back()->with('sukses', "Import statistik selesai: {$hasil['dibuat']} indikator baru, {$hasil['diperbarui']} diperbarui, {$hasil['sel']} sel nilai terisi.");
    }

    /**
     * Menghapus satu indikator.
     */
    public function destroy(Statistik $statistik): RedirectResponse
    {
        $statistik->delete();
        LogAktivitas::catat('hapus indikator statistik', $statistik->indikator);

        return back()->with('sukses', 'Indikator "'.$statistik->indikator.'" dihapus.');
    }
}

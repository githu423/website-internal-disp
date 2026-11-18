<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bidang;
use App\Models\CapaianIndikator;
use App\Models\CapaianSasaran;
use App\Models\CapaianTahunan;
use App\Models\LogAktivitas;
use App\Services\ImportService;
use App\Services\NotifikasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Kelola matriks Capaian Kinerja (sasaran strategis + indikator + target/realisasi per tahun).
 *
 * Sumber berkas: CAPAIAN KINERJA.csv — impor bersifat menggantikan seluruh data.
 */
class CapaianKinerjaController extends Controller
{
    /**
     * Matriks sasaran + indikator + nilai per tahun.
     */
    public function index(Request $request): View
    {
        $sasarans = CapaianSasaran::with(['indikator.tahunan'])->orderBy('urut')->get();
        $years = CapaianTahunan::distinct()->orderBy('tahun')->pluck('tahun')->map(fn ($tahun) => (int) $tahun)->all();
        if (! $years) {
            $years = [(int) now()->year - 1, (int) now()->year];
        }

        return view('admin.capaian-kinerja.index', [
            'sasarans' => $sasarans,
            'years' => $years,
            'tambahYears' => range((int) date('Y') + 3, 2015),
            'bidangs' => Bidang::orderBy('kode')->get(),
        ]);
    }

    // ===== SASARAN =====

    /**
     * Menambah sasaran strategis.
     */
    public function storeSasaran(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nomor' => ['nullable', 'integer', 'min:1', 'max:999'],
            'judul' => ['required', 'string', 'max:500'],
            'cakupan' => ['nullable', 'string', 'max:160'],
            'id_bidang' => ['nullable', 'integer', 'exists:bidang,id'],
        ]);
        $data['nomor'] = $data['nomor'] ?? ((int) CapaianSasaran::max('nomor') + 1);
        if (! isset($data['id_bidang'])) {
            $data['id_bidang'] = Bidang::tebakDariTeks($data['judul']);
        }
        $data['urut'] = (int) CapaianSasaran::max('urut') + 1;
        CapaianSasaran::create($data);
        LogAktivitas::catat('tambah sasaran kinerja', $data['judul']);

        return back()->with('sukses', 'Sasaran ditambahkan.');
    }

    /**
     * Memperbarui sasaran strategis.
     */
    public function updateSasaran(Request $request, CapaianSasaran $sasaran): RedirectResponse
    {
        $data = $request->validate([
            'nomor' => ['nullable', 'integer', 'min:1', 'max:999'],
            'judul' => ['required', 'string', 'max:500'],
            'cakupan' => ['nullable', 'string', 'max:160'],
            'id_bidang' => ['nullable', 'integer', 'exists:bidang,id'],
        ]);
        $sasaran->update($data);

        return back()->with('sukses', 'Sasaran diperbarui.');
    }

    /**
     * Menghapus sasaran beserta indikatornya.
     */
    public function destroySasaran(CapaianSasaran $sasaran): RedirectResponse
    {
        $sasaran->delete();
        LogAktivitas::catat('hapus sasaran kinerja', $sasaran->judul);

        return back()->with('sukses', 'Sasaran beserta indikatornya dihapus.');
    }

    // ===== INDIKATOR =====

    /**
     * Menambah indikator pada sebuah sasaran.
     */
    public function storeIndikator(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sasaran_id' => ['required', 'integer', 'exists:capaian_sasaran,id'],
            'nama' => ['required', 'string', 'max:500'],
            'satuan' => ['nullable', 'string', 'max:80'],
        ]);
        $data['urut'] = (int) CapaianIndikator::where('sasaran_id', $data['sasaran_id'])->max('urut') + 1;
        CapaianIndikator::create($data);

        return back()->with('sukses', 'Indikator ditambahkan.');
    }

    /**
     * Memperbarui indikator.
     */
    public function updateIndikator(Request $request, CapaianIndikator $indikator): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:500'],
            'satuan' => ['nullable', 'string', 'max:80'],
        ]);
        $indikator->update($data);

        return back()->with('sukses', 'Indikator diperbarui.');
    }

    /**
     * Menghapus indikator.
     */
    public function destroyIndikator(CapaianIndikator $indikator): RedirectResponse
    {
        $indikator->delete();

        return back()->with('sukses', 'Indikator dihapus.');
    }

    /**
     * Simpan/ubah nilai per tahun satu indikator; sel yang dikosongkan menghapus baris tahun tsb.
     */
    public function simpanNilai(Request $request, CapaianIndikator $indikator): RedirectResponse
    {
        $data = $request->validate([
            'nilai' => ['nullable', 'array'],
            'nilai.*.target' => ['nullable', 'string', 'max:120'],
            'nilai.*.realisasi' => ['nullable', 'string', 'max:120'],
            'nilai.*.persen' => ['nullable', 'string', 'max:24'],
            't_tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            't_target' => ['nullable', 'string', 'max:120'],
            't_realisasi' => ['nullable', 'string', 'max:120'],
            't_persen' => ['nullable', 'string', 'max:24'],
            'nama' => ['nullable', 'string', 'max:500'],
            'satuan' => ['nullable', 'string', 'max:80'],
        ]);
        if ($data['nama'] ?? null) {
            $indikator->update(['nama' => $data['nama'], 'satuan' => $data['satuan'] ?? null]);
        }

        DB::transaction(function () use ($data, $indikator) {
            foreach ($data['nilai'] ?? [] as $tahun => $nilai) {
                $this->simpanBaris($indikator, (int) $tahun, $nilai['target'] ?? null, $nilai['realisasi'] ?? null, $nilai['persen'] ?? null);
            }
            if (! empty($data['t_tahun'])) {
                $this->simpanBaris($indikator, (int) $data['t_tahun'], $data['t_target'] ?? null, $data['t_realisasi'] ?? null, $data['t_persen'] ?? null);
            }
        });

        return back()->with('sukses', "Nilai indikator \"{$indikator->nama}\" disimpan.");
    }

    /**
     * Menyimpan satu baris nilai tahunan; jika semua kolom kosong, baris dihapus.
     */
    private function simpanBaris(CapaianIndikator $indikator, int $tahun, ?string $target, ?string $realisasi, ?string $persen): void
    {
        // Nilai kosong / "N/A" dianggap tidak diisi.
        $bersihkan = fn (?string $nilai) => ($nilai = trim((string) $nilai)) === '' || strcasecmp($nilai, 'n/a') === 0 ? null : $nilai;
        [$target, $realisasi, $persen] = array_map($bersihkan, [$target, $realisasi, $persen]);

        $baris = CapaianTahunan::firstOrNew(['indikator_id' => $indikator->id, 'tahun' => $tahun]);
        if ($target === null && $realisasi === null && $persen === null) {
            if ($baris->exists) {
                $baris->delete();
            }

            return;
        }

        $baris->target = $target;
        $baris->realisasi = $realisasi;
        $baris->persen = $persen;
        $baris->save();
    }

    // ===== IMPOR BERKAS =====

    /**
     * Impor berkas capaian kinerja (mode: ganti seluruh data atau gabungkan).
     */
    public function import(Request $request): RedirectResponse
    {
        // ===== 1. VALIDASI BERKAS =====
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls,ods,xlsb', 'max:20480'],
            'mode' => ['nullable', 'in:ganti,update'],
        ]);
        $mode = $request->input('mode', 'update');
        $file = $request->file('file');

        // ===== 2. BACA & PARSE =====
        try {
            $rows = ImportService::rowsFrom($file->getRealPath(), $file->getClientOriginalName());
        } catch (\Throwable $kesalahan) {
            return back()->with('gagal', 'File gagal dibaca: '.$kesalahan->getMessage());
        }
        $data = ImportService::parseCapaian($rows);
        if (! $data) {
            return back()->with('gagal', 'Format tidak dikenali. Butuh baris header "No. | Sasaran | Indikator Kinerja | Satuan" dan baris tahun dengan kolom Target/Realisasi/% per tahun.');
        }

        // ===== 3. TERAPKAN & KABARI =====
        $res = ImportService::applyCapaian($data, $mode);
        $pesan = $mode === 'ganti'
            ? "Data diganti seluruhnya: {$res['sasaran_baru']} sasaran, {$res['indikator_baru']} indikator, {$res['nilai_baru']} nilai tahunan."
            : "Gabungkan selesai: {$res['nilai_baru']} nilai baru, {$res['nilai_ubah']} diperbarui, {$res['nilai_sama']} sudah sama (tidak diduplikat)"
              .($res['indikator_baru'] || $res['sasaran_baru'] ? "; {$res['sasaran_baru']} sasaran & {$res['indikator_baru']} indikator baru dibuat" : '').'.';
        NotifikasiService::broadcast('Data capaian kinerja diperbarui', $pesan, 'import', route('admin.capaian.index'));
        LogAktivitas::catat('impor capaian kinerja ('.$mode.')', $pesan);

        return back()->with('sukses', $pesan);
    }
}

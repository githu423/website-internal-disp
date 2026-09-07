<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Bidang;
use App\Models\Dokumen;
use App\Models\LogAktivitas;
use App\Models\Pegawai;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman bidang: profil dapat DILIHAT seluruh user (banyak pilihan bidang),
 * tetapi hanya bisa DIKELOLA (profil + dokumen privat) oleh anggota bidang itu sendiri / admin.
 */
class BidangController extends Controller
{
    public function index(Request $request): View
    {
        $u = $request->user();
        // rekap kecil per kartu bidang: satu query agregat (portabel MySQL & sqlite)
        $rek = \Illuminate\Support\Facades\DB::table('pegawai')->selectRaw(
            "id_bidang,
             SUM(CASE WHEN jenis_kelamin='L' THEN 1 ELSE 0 END) l,
             SUM(CASE WHEN jenis_kelamin='P' THEN 1 ELSE 0 END) p,
             SUM(CASE WHEN jenis_asn='PNS' THEN 1 ELSE 0 END) pns,
             SUM(CASE WHEN tempat_lahir IS NULL OR tempat_lahir='' OR tanggal_lahir IS NULL
                       OR jenis_kelamin IS NULL OR jenis_kelamin='' OR pangkat_golongan IS NULL OR pangkat_golongan=''
                       OR pendidikan IS NULL OR pendidikan='' OR unit_kerja IS NULL OR unit_kerja=''
                       OR tmt_cpns IS NULL OR tmt_sekarang IS NULL OR tmt_jabatan IS NULL
                      THEN 1 ELSE 0 END) blm")
            ->whereNotNull('id_bidang')->groupBy('id_bidang')->get()->keyBy('id_bidang');

        $bidangList = Bidang::withCount(['pegawais', 'dokumen', 'agenda'])->orderBy('kode')->get()
            ->map(function ($b) use ($u, $rek) {
                $b->boleh_kelola = $u->isAdmin() || $u->id_bidang === $b->id;
                $b->rek = $rek->get($b->id);
                return $b;
            });

        return view('bidang.index', ['bidangList' => $bidangList]);
    }

    /**
     * Anggota bidang = data pegawai dengan id_bidang sama, DITAMBAH pegawai
     * yang bidangnya belum tertaut di data tapi jabatan/unit kerjanya menyebut
     * bidang ini (fallback langsung, tanpa perlu menunggu sinkron).
     */
    private function anggotaBidang(Bidang $bidang)
    {
        $adaUnit = \Illuminate\Support\Facades\Schema::hasColumn('pegawai', 'unit_kerja');
        $utama = Pegawai::where('id_bidang', $bidang->id)->orderBy('nama')->get();
        $tambahan = collect();
        foreach (Pegawai::whereNull('id_bidang')->get() as $p) {
            if ((int) Bidang::tebakDariTeks(trim(((string) $p->jabatan) . ' ' . ($adaUnit ? (string) ($p->unit_kerja ?? '') : ''))) === (int) $bidang->id) {
                $tambahan->push($p);
            }
        }
        $tambahan = $tambahan->sortBy('nama');
        return $utama->concat($tambahan)->values();
    }

    public function show(Request $request, Bidang $bidang): View
    {
        $u = $request->user();
        $anggota = $u->isAdmin() || $u->id_bidang === $bidang->id;

        $dq = Dokumen::where('id_bidang', $bidang->id)->with('uploader');
        if ($anggota) {
            $dq->where(fn ($w) => $w->whereIn('visibility', ['publik', 'bidang'])->orWhere('uploaded_by', $u->id));
        } else {
            $dq->where('visibility', 'publik');
        }

        // daftar anggota dulu; rekap jumlah dihitung dari kumpulan yang SAMA
        // (termasuk anggota fallback tebak-jabatan), jadi angkanya tidak mungkin beda dengan tabelnya
        $pegawais = $this->anggotaBidang($bidang);
        $statistik = [
            'semua'  => $pegawais->count(),
            'l'      => $pegawais->where('jenis_kelamin', 'L')->count(),
            'p'      => $pegawais->where('jenis_kelamin', 'P')->count(),
            'pns'    => $pegawais->where('jenis_asn', 'PNS')->count(),
            'nonpns' => $pegawais->filter(fn ($x) => $x->jenis_asn !== 'PNS')->count(),
        ];

        return view('bidang.show', [
            'b' => $bidang,
            'bolehKelola' => $anggota,
            'semuaBidang' => Bidang::orderBy('kode')->get(),
            'pegawais' => $pegawais,
            'statistik' => $statistik,
            'dokumens' => $dq->latest()->limit(15)->get(),
            'agenda' => Agenda::where('id_bidang', $bidang->id)->orderByDesc('tanggal')->limit(6)->get(),
        ]);
    }

    public function update(Request $request, Bidang $bidang): RedirectResponse
    {
        $u = $request->user();
        abort_unless($u->isAdmin() || $u->id_bidang === $bidang->id, 403, 'Hanya anggota bidang ini yang dapat mengubah halaman bidangnya.');
        $d = $request->validate([
            'nama' => ['required', 'string', 'max:180'],
            'ketua' => ['nullable', 'string', 'max:120'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ]);
        $bidang->update($d);
        LogAktivitas::catat('ubah profil bidang', $bidang->nama);

        return back()->with('sukses', 'Halaman bidang diperbarui.');
    }

    /** Anggota bidang (peran apa pun) menjadwalkan agenda bidangnya sendiri dari halaman bidang. */
    public function tambahAgenda(Request $request, Bidang $bidang): RedirectResponse
    {
        $u = $request->user();
        abort_unless($u->isAdmin() || $u->id_bidang === $bidang->id, 403, 'Hanya anggota bidang ini yang dapat menjadwalkan agenda di sini.');
        $d = $request->validate([
            'judul' => ['required', 'string', 'max:180'],
            'tanggal' => ['required', 'date'],
            'jam_mulai' => ['nullable', 'date_format:H:i'],
            'lokasi' => ['nullable', 'string', 'max:120'],
            'kategori' => ['nullable', 'in:rapat,kegiatan,kunjungan,lainnya'],
        ]);
        $a = Agenda::create($d + ['id_bidang' => $bidang->id, 'created_by' => $u->id, 'status' => 'belum']);
        LogAktivitas::catat('tambah agenda bidang', $a->judul . ' (' . $bidang->nama . ')');

        return back()->with('sukses', 'Agenda bidang ditambahkan dan tampil di halaman ini.');
    }

    /** Anggota bidang (peran apa pun, termasuk staf) menyimpan arsip privat untuk bidangnya sendiri. */
    public function unggahDokumen(Request $request, Bidang $bidang): RedirectResponse
    {
        $u = $request->user();
        abort_unless($u->isAdmin() || $u->id_bidang === $bidang->id, 403, 'Hanya anggota bidang ini yang dapat menyimpan dokumen di sini.');
        $d = $request->validate([
            'judul' => ['required', 'string', 'max:180'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'file' => ['required', 'file', 'max:20480'],
        ]);
        $file = $request->file('file');
        $path = $file->storeAs('dokumen', time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->hashName()), 'public');
        Dokumen::create([
            'judul' => $d['judul'], 'deskripsi' => $d['deskripsi'] ?? null, 'kategori' => 'Arsip Bidang',
            'visibility' => 'bidang', 'id_bidang' => $bidang->id,
            'file_path' => $path, 'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(), 'mime' => $file->getClientMimeType(),
            'uploaded_by' => $u->id,
        ]);
        LogAktivitas::catat('unggah dokumen bidang', $d['judul'] . ' (' . $bidang->nama . ')');

        return back()->with('sukses', 'Dokumen tersimpan sebagai arsip privat bidang — hanya terlihat oleh rekan sebidang dan admin.');
    }
}

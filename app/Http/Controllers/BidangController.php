<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Bidang;
use App\Models\Dokumen;
use App\Models\LogAktivitas;
use App\Models\Pegawai;
use App\Services\KategoriDokumenService;
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
        // rekap kecil per kartu bidang: satu query agregat (portabel MySQL & sqlite).
        // Kriteria "belum lengkap" (blm) dibangun dari daftar kolom yang SAMA dengan
        // penanda di Data Pegawai & halaman /profil (Pegawai::KOLOM_LENGKAP), supaya
        // angka di kartu bidang tidak pernah berbeda dengan tabelnya.
        // Kolom DATE (tmt_*) hanya cek IS NULL — MySQL strict menolak `= ''` untuk DATE.
        $kurang = collect(array_keys(\App\Models\Pegawai::KOLOM_LENGKAP))
            ->map(fn ($c) => in_array($c, \App\Models\Pegawai::KOLOM_DATE, true)
                ? "$c IS NULL"
                : "$c IS NULL OR $c = ''")
            ->implode(' OR ');
        $rek = \Illuminate\Support\Facades\DB::table('pegawai')->selectRaw(
            "id_bidang,
             SUM(CASE WHEN jenis_kelamin='L' THEN 1 ELSE 0 END) l,
             SUM(CASE WHEN jenis_kelamin='P' THEN 1 ELSE 0 END) p,
             SUM(CASE WHEN jenis_asn='PNS' THEN 1 ELSE 0 END) pns,
             SUM(CASE WHEN $kurang THEN 1 ELSE 0 END) blm"
        )
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

        $dq = Dokumen::where('id_bidang', $bidang->id)->with(['uploader', 'kategoriRel.induk.induk']);
        if ($anggota) {
            $dq->where(fn($w) => $w->whereIn('visibility', ['publik', 'bidang'])->orWhere('uploaded_by', $u->id));
        } else {
            $dq->where('visibility', 'publik');
        }

        // daftar anggota dulu; rekap jumlah dihitung dari kumpulan yang SAMA
        // (termasuk anggota fallback tebak-jabatan), jadi angkanya tidak mungkin beda dengan tabelnya
        $pegawais = $this->anggotaBidang($bidang);
        $statistik = [
            'semua' => $pegawais->count(),
            'l' => $pegawais->where('jenis_kelamin', 'L')->count(),
            'p' => $pegawais->where('jenis_kelamin', 'P')->count(),
            'pns' => $pegawais->where('jenis_asn', 'PNS')->count(),
            'nonpns' => $pegawais->filter(fn($x) => $x->jenis_asn !== 'PNS')->count(),
            // Konfirmasi Dinas 10 Sep: rekap anggota mencantumkan non-aktif & pensiun juga
            'nonaktif' => $pegawais->where('status', 'nonaktif')->count(),
            'pensiun' => $pegawais->where('status', 'pensiun')->count(),
        ];

        return view('bidang.show', [
            'b' => $bidang,
            'bolehKelola' => $anggota,
            'semuaBidang' => Bidang::orderBy('kode')->get(),
            'pegawais' => $pegawais,
            'statistik' => $statistik,
            'dokumens' => $dq->latest()->limit(15)->get(),
            'agenda' => Agenda::where('id_bidang', $bidang->id)->orderByDesc('tanggal')->limit(6)->get(),
            'kategoris' => \App\Models\KategoriDokumen::with('anak.anak')->orderBy('nama')->get(),
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
            'kategori' => ['nullable', 'string', 'max:180'],
            'id_kategori' => ['nullable', 'integer', 'exists:kategori_dokumen,id'],
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg,txt',
                'max:10240',
                function ($attribute, $value, $fail) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    $allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'png', 'jpg', 'jpeg', 'txt'];
                    if (!in_array($ext, $allowedExt, true)) {
                        $fail('Ekstensi file tidak diizinkan. Hanya format PDF, Word, Excel, PowerPoint, Gambar, atau Teks (.txt) yang diperbolehkan.');
                    }
                },
            ],
        ]);
        $file = $request->file('file');
        $path = $file->storeAs('dokumen', time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->hashName()), 'public');

        // Prioritas: 1) kategori terpilih dari pohon; 2) teks kategori baru (berjenjang
        // dengan garis miring); 3) fallback 'Arsip Bidang'.
        $idKategori = isset($d['id_kategori']) && $d['id_kategori'] !== '' ? (int) $d['id_kategori'] : null;
        if ($idKategori === null && !empty($d['kategori'])) {
            $idKategori = KategoriDokumenService::dapatkanAtauBuatBerjenjang((string) $d['kategori'])->id;
        }
        if ($idKategori === null) {
            $idKategori = KategoriDokumenService::dapatkanAtauBuat('Arsip Bidang')->id;
        }

        Dokumen::create([
            'judul' => $d['judul'],
            'deskripsi' => $d['deskripsi'] ?? null,
            'kategori' => null,
            'id_kategori' => $idKategori,
            'visibility' => 'bidang',
            'id_bidang' => $bidang->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime' => $file->getClientMimeType(),
            'uploaded_by' => $u->id,
        ]);
        LogAktivitas::catat('unggah dokumen bidang', $d['judul'] . ' (' . $bidang->nama . ')');

        return back()->with('sukses', 'Dokumen tersimpan sebagai arsip privat bidang — hanya terlihat oleh rekan sebidang dan admin.');
    }
}

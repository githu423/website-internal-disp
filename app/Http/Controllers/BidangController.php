<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Bidang;
use App\Models\Dokumen;
use App\Models\KategoriDokumen;
use App\Models\LogAktivitas;
use App\Models\Pegawai;
use App\Services\KategoriDokumenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Halaman bidang.
 *
 * Profil bidang dapat DILIHAT seluruh pengguna (banyak pilihan bidang),
 * tetapi hanya bisa DIKELOLA (profil + dokumen privat) oleh anggota bidang
 * tersebut atau admin.
 */
class BidangController extends Controller
{
    /**
     * Daftar seluruh bidang beserta rekap kecil per kartu.
     */
    public function index(Request $request): View
    {
        $pengguna = $request->user();

        // ===== REKAP JUMLAH PER BIDANG =====
        // Satu query agregat (portabel MySQL & sqlite). Kriteria "belum lengkap"
        // dibangun dari daftar kolom yang SAMA dengan penanda di Data Pegawai &
        // halaman /profil (Pegawai::KOLOM_LENGKAP), supaya angka di kartu bidang
        // tidak pernah berbeda dengan tabelnya.
        // Kolom DATE (tmt_*) hanya cek IS NULL — MySQL strict menolak `= ''` untuk DATE.
        $kurangSyarat = collect(array_keys(Pegawai::KOLOM_LENGKAP))
            ->map(fn ($kolom) => in_array($kolom, Pegawai::KOLOM_DATE, true)
                ? "$kolom IS NULL"
                : "$kolom IS NULL OR $kolom = ''")
            ->implode(' OR ');
        $rekap = DB::table('pegawai')->selectRaw(
            "id_bidang,
             SUM(CASE WHEN jenis_kelamin='L' THEN 1 ELSE 0 END) l,
             SUM(CASE WHEN jenis_kelamin='P' THEN 1 ELSE 0 END) p,
             SUM(CASE WHEN jenis_asn='PNS' THEN 1 ELSE 0 END) pns,
             SUM(CASE WHEN $kurangSyarat THEN 1 ELSE 0 END) blm"
        )
            ->whereNotNull('id_bidang')->groupBy('id_bidang')->get()->keyBy('id_bidang');

        // ===== DAFTAR BIDANG + HAK KELOLA =====
        $bidangList = Bidang::withCount(['pegawais', 'dokumen', 'agenda'])->orderBy('kode')->get()
            ->map(function ($bidangItem) use ($pengguna, $rekap) {
                $bidangItem->boleh_kelola = $pengguna->isAdmin() || $pengguna->id_bidang === $bidangItem->id;
                $bidangItem->rek = $rekap->get($bidangItem->id);

                return $bidangItem;
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
        $punyaUnitKerja = Schema::hasColumn('pegawai', 'unit_kerja');
        $anggotaUtama = Pegawai::where('id_bidang', $bidang->id)->orderBy('nama')->get();
        $anggotaTambahan = collect();
        foreach (Pegawai::whereNull('id_bidang')->get() as $pegawai) {
            if ((int) Bidang::tebakDariTeks(trim(((string) $pegawai->jabatan).' '.($punyaUnitKerja ? (string) ($pegawai->unit_kerja ?? '') : ''))) === (int) $bidang->id) {
                $anggotaTambahan->push($pegawai);
            }
        }
        $anggotaTambahan = $anggotaTambahan->sortBy('nama');

        return $anggotaUtama->concat($anggotaTambahan)->values();
    }

    /**
     * Halaman detail satu bidang: profil, statistik, anggota, dokumen, agenda.
     */
    public function show(Request $request, Bidang $bidang): View
    {
        $pengguna = $request->user();
        $bolehKelola = $pengguna->isAdmin() || $pengguna->id_bidang === $bidang->id;

        // ===== DAFTAR DOKUMEN BIDANG (sesuai hak akses) =====
        $queryDokumen = Dokumen::where('id_bidang', $bidang->id)->with(['uploader', 'kategoriRel.induk.induk']);
        if ($bolehKelola) {
            $queryDokumen->where(fn ($query) => $query->whereIn('visibility', ['publik', 'bidang'])->orWhere('uploaded_by', $pengguna->id));
        } else {
            $queryDokumen->where('visibility', 'publik');
        }

        // ===== STATISTIK ANGGOTA =====
        // Daftar anggota dihitung dulu; rekap jumlah diambil dari kumpulan yang SAMA
        // (termasuk anggota fallback tebak-jabatan), jadi angkanya tidak mungkin beda dengan tabelnya.
        $pegawais = $this->anggotaBidang($bidang);
        $statistik = [
            'semua' => $pegawais->count(),
            'l' => $pegawais->where('jenis_kelamin', 'L')->count(),
            'p' => $pegawais->where('jenis_kelamin', 'P')->count(),
            'pns' => $pegawais->where('jenis_asn', 'PNS')->count(),
            'nonpns' => $pegawais->filter(fn ($item) => $item->jenis_asn !== 'PNS')->count(),
            // Konfirmasi Dinas 10 Sep: rekap anggota mencantumkan non-aktif & pensiun juga
            'nonaktif' => $pegawais->where('status', 'nonaktif')->count(),
            'pensiun' => $pegawais->where('status', 'pensiun')->count(),
        ];

        // ===== RESPONS: TAMPILKAN HALAMAN BIDANG =====
        return view('bidang.show', [
            'b' => $bidang,
            'bolehKelola' => $bolehKelola,
            'semuaBidang' => Bidang::orderBy('kode')->get(),
            'pegawais' => $pegawais,
            'statistik' => $statistik,
            'dokumens' => $queryDokumen->latest()->limit(15)->get(),
            'agenda' => Agenda::where('id_bidang', $bidang->id)->orderByDesc('tanggal')->limit(6)->get(),
            'kategoris' => KategoriDokumen::with('anak.anak')->orderBy('nama')->get(),
        ]);
    }

    /**
     * Memperbarui profil bidang (nama, ketua, deskripsi).
     */
    public function update(Request $request, Bidang $bidang): RedirectResponse
    {
        // ===== PEMERIKSAAN AKSES =====
        $pengguna = $request->user();
        abort_unless($pengguna->isAdmin() || $pengguna->id_bidang === $bidang->id, 403, 'Hanya anggota bidang ini yang dapat mengubah halaman bidangnya.');

        // ===== VALIDASI & SIMPAN =====
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:180'],
            'ketua' => ['nullable', 'string', 'max:120'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ]);
        $bidang->update($data);

        // ===== CATAT LOG & RESPONS =====
        LogAktivitas::catat('ubah profil bidang', $bidang->nama);

        return back()->with('sukses', 'Halaman bidang diperbarui.');
    }

    /**
     * Anggota bidang (peran apa pun) menjadwalkan agenda bidangnya sendiri dari halaman bidang.
     */
    public function tambahAgenda(Request $request, Bidang $bidang): RedirectResponse
    {
        // ===== PEMERIKSAAN AKSES =====
        $pengguna = $request->user();
        abort_unless($pengguna->isAdmin() || $pengguna->id_bidang === $bidang->id, 403, 'Hanya anggota bidang ini yang dapat menjadwalkan agenda di sini.');

        // ===== VALIDASI & SIMPAN =====
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:180'],
            'tanggal' => ['required', 'date'],
            'jam_mulai' => ['nullable', 'date_format:H:i'],
            'lokasi' => ['nullable', 'string', 'max:120'],
            'kategori' => ['nullable', 'in:rapat,kegiatan,kunjungan,lainnya'],
        ]);
        $agendaBaru = Agenda::create($data + ['id_bidang' => $bidang->id, 'created_by' => $pengguna->id, 'status' => 'belum']);

        // ===== CATAT LOG & RESPONS =====
        LogAktivitas::catat('tambah agenda bidang', $agendaBaru->judul.' ('.$bidang->nama.')');

        return back()->with('sukses', 'Agenda bidang ditambahkan dan tampil di halaman ini.');
    }

    /**
     * Anggota bidang (peran apa pun, termasuk staf) menyimpan arsip privat untuk bidangnya sendiri.
     */
    public function unggahDokumen(Request $request, Bidang $bidang): RedirectResponse
    {
        // ===== PEMERIKSAAN AKSES =====
        $pengguna = $request->user();
        abort_unless($pengguna->isAdmin() || $pengguna->id_bidang === $bidang->id, 403, 'Hanya anggota bidang ini yang dapat menyimpan dokumen di sini.');

        // ===== VALIDASI INPUT (termasuk batas 50 MB) =====
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:180'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'kategori' => ['nullable', 'string', 'max:180'],
            'id_kategori' => ['nullable', 'integer', 'exists:kategori_dokumen,id'],
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg,txt',
                'max:51200', // 50 MB (satuan KB)
                function ($attribute, $value, $fail) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    $allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'png', 'jpg', 'jpeg', 'txt'];
                    if (! in_array($ext, $allowedExt, true)) {
                        $fail('Ekstensi file tidak diizinkan. Hanya format PDF, Word, Excel, PowerPoint, Gambar, atau Teks (.txt) yang diperbolehkan.');
                    }
                },
            ],
        ], ['file.max' => 'Ukuran dokumen maksimal 50 MB.']);

        // ===== PENYIMPANAN BERKAS =====
        $file = $request->file('file');
        $path = $file->storeAs('dokumen', time().'_'.preg_replace('/[^A-Za-z0-9._-]/', '_', $file->hashName()), 'public');

        // ===== PENENTUAN KATEGORI =====
        // Prioritas: 1) kategori terpilih dari pohon; 2) teks kategori baru (berjenjang
        // dengan garis miring); 3) fallback 'Arsip Bidang'.
        $idKategori = isset($data['id_kategori']) && $data['id_kategori'] !== '' ? (int) $data['id_kategori'] : null;
        if ($idKategori === null && ! empty($data['kategori'])) {
            $idKategori = KategoriDokumenService::dapatkanAtauBuatBerjenjang((string) $data['kategori'])->id;
        }
        if ($idKategori === null) {
            $idKategori = KategoriDokumenService::dapatkanAtauBuat('Arsip Bidang')->id;
        }

        // ===== PENYIMPANAN DATA DOKUMEN =====
        Dokumen::create([
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'kategori' => null,
            'id_kategori' => $idKategori,
            'visibility' => 'bidang',
            'id_bidang' => $bidang->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime' => $file->getClientMimeType(),
            'uploaded_by' => $pengguna->id,
        ]);

        // ===== CATAT LOG & RESPONS =====
        LogAktivitas::catat('unggah dokumen bidang', $data['judul'].' ('.$bidang->nama.')');

        return back()->with('sukses', 'Dokumen tersimpan sebagai arsip privat bidang — hanya terlihat oleh rekan sebidang dan admin.');
    }
}

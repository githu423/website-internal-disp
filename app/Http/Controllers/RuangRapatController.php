<?php

namespace App\Http\Controllers;

use App\Models\Bidang;
use App\Models\Dokumen;
use App\Models\LogAktivitas;
use App\Models\RuangRapat;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * Pengelolaan Ruang Rapat dan tautan publiknya.
 *
 * Ruang Rapat hanya dikelola admin atau pegawai bidang.
 * Setiap ruang rapat punya tautan token yang bisa dibagikan ke tamu eksternal,
 * dengan pilihan PIN dan batas kedaluwarsa.
 */
class RuangRapatController extends Controller
{
    /**
     * Daftar ruang rapat + kartu ringkasan status (semua/aktif/kedaluwarsa/nonaktif).
     */
    public function index(Request $request): View
    {
        $pengguna = $request->user();

        // ===== 1. FILTER =====
        $status = (string) $request->query('status', 'semua');
        $kataKunci = trim((string) $request->query('q', ''));
        $idBidang = $request->query('id_bidang');

        $queryDasar = RuangRapat::query();
        if ($idBidang) {
            $queryDasar->where('id_bidang', $idBidang);
        }

        // ===== 2. HITUNG METRIK KARTU RINGKASAN =====
        $totalSemua = (clone $queryDasar)->count();
        $totalAktif = (clone $queryDasar)->where('status', 'aktif')->whereDate('berlaku_sampai', '>=', today())->count();
        $totalKedaluwarsa = (clone $queryDasar)->where('status', 'aktif')->whereDate('berlaku_sampai', '<', today())->count();
        $totalNonaktif = (clone $queryDasar)->where('status', 'nonaktif')->count();

        // ===== 3. DAFTAR RUANG RAPAT =====
        $query = (clone $queryDasar)
            ->with(['bidang', 'pembuat'])
            ->withCount('dokumen')
            ->latest();

        if ($kataKunci !== '') {
            $query->where(function ($queryCari) use ($kataKunci) {
                $queryCari->where('judul', 'like', "%{$kataKunci}%")
                    ->orWhere('deskripsi', 'like', "%{$kataKunci}%")
                    ->orWhere('lokasi', 'like', "%{$kataKunci}%")
                    ->orWhere('pic_nama', 'like', "%{$kataKunci}%")
                    ->orWhereHas('bidang', function ($queryBidang) use ($kataKunci) {
                        $queryBidang->where('nama', 'like', "%{$kataKunci}%");
                    });
            });
        }

        if ($status === 'aktif') {
            $query->where('status', 'aktif')->whereDate('berlaku_sampai', '>=', today());
        } elseif ($status === 'kedaluwarsa') {
            $query->where('status', 'aktif')->whereDate('berlaku_sampai', '<', today());
        } elseif ($status === 'nonaktif') {
            $query->where('status', 'nonaktif');
        } else {
            $status = 'semua';
        }

        $ruangRapats = $query->paginate(12)->withQueryString();
        $statusOptions = [
            'semua' => 'Semua status',
            'aktif' => 'Aktif',
            'kedaluwarsa' => 'Kedaluwarsa',
            'nonaktif' => 'Nonaktif',
        ];

        $counts = [
            'semua' => $totalSemua,
            'aktif' => $totalAktif,
            'kedaluwarsa' => $totalKedaluwarsa,
            'nonaktif' => $totalNonaktif,
        ];

        // ===== 4. DATA PENDUKUNG & RESPONS =====
        $bidangs = Bidang::query()->orderBy('nama')->get();

        // 'q' dipertahankan karena menjadi nama variabel di tampilan (ruang-rapat/index).
        return view('ruang-rapat.index', ['ruangRapats' => $ruangRapats, 'status' => $status, 'statusOptions' => $statusOptions, 'q' => $kataKunci, 'counts' => $counts, 'bidangs' => $bidangs, 'idBidang' => $idBidang]);
    }

    /**
     * Form pembuatan ruang rapat (hanya dokumen publik yang bisa dipilih).
     */
    public function create(Request $request): View
    {
        $this->pastikanPengelola($request);
        $dokumen = Dokumen::query()
            ->with('bidang')
            ->where('visibility', 'publik')
            ->orderBy('judul')
            ->get();
        $bidangs = Bidang::query()->orderBy('nama')->get();

        return view('ruang-rapat.create', compact('dokumen', 'bidangs'));
    }

    /**
     * Menyimpan ruang rapat baru beserta dokumen publiknya.
     */
    public function store(Request $request): RedirectResponse
    {
        // ===== 1. PEMERIKSAAN AKSES =====
        $pengguna = $this->pastikanPengelola($request);

        // ===== 2. VALIDASI INPUT =====
        $request->query->remove('pin');
        $pinInput = $request->input('pin');
        $request->merge(['pin' => null]);
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:180'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'tanggal_rapat' => ['nullable', 'date'],
            'lokasi' => ['nullable', 'string', 'max:180'],
            'link_virtual' => ['nullable', 'url', 'max:255'],
            'id_bidang' => ['nullable', 'integer', 'exists:bidang,id'],
            'pic_nama' => ['nullable', 'string', 'max:180'],
            'pic_kontak' => ['nullable', 'string', 'max:180'],
            'izinkan_unduh_default' => ['required', 'boolean'],
            'berlaku_sampai' => ['required', 'date', 'after_or_equal:today'],
            'dokumen' => ['required', 'array', 'min:1', 'max:100'],
            'dokumen.*' => ['required', 'integer', 'distinct', 'exists:dokumen,id'],
            'kelompok' => ['required', 'array'],
            'kelompok.*' => ['required', 'in:agenda,materi,pendukung,notulen'],
            'izinkan_unduh' => ['nullable', 'array'],
            'izinkan_unduh.*' => ['nullable', 'in:0,1'],
        ]);
        $pinBaru = Validator::make(['pin' => $pinInput], [
            'pin' => ['nullable', 'string', 'min:4', 'max:32'],
        ])->validate()['pin'];

        // ===== 3. PASTIKAN SEMUA DOKUMEN PUBLIK =====
        $idDokumen = array_map('intval', $data['dokumen']);
        $dokumenPublik = Dokumen::query()
            ->whereIn('id', $idDokumen)
            ->where('visibility', 'publik')
            ->get()
            ->keyBy('id');

        if ($dokumenPublik->count() !== count(array_unique($idDokumen))) {
            throw ValidationException::withMessages([
                'dokumen' => 'Ruang Rapat hanya dapat memuat dokumen dengan visibilitas Publik.',
            ]);
        }

        foreach ($idDokumen as $id) {
            if (! in_array($data['kelompok'][$id] ?? null, ['agenda', 'materi', 'pendukung', 'notulen'], true)) {
                throw ValidationException::withMessages([
                    'kelompok' => 'Pilih kelompok yang valid untuk setiap dokumen terpilih.',
                ]);
            }
        }

        // ===== 4. SIMPAN DALAM SATU TRANSAKSI =====
        $ruangRapat = DB::transaction(function () use ($pengguna, $data, $idDokumen, $pinBaru): RuangRapat {
            $ruangRapat = RuangRapat::create([
                'judul' => $data['judul'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'tanggal_rapat' => $data['tanggal_rapat'] ?? null,
                'lokasi' => $data['lokasi'] ?? null,
                'link_virtual' => $data['link_virtual'] ?? null,
                'id_bidang' => $data['id_bidang'] ?? ($pengguna->id_bidang ?: null),
                'pic_nama' => $data['pic_nama'] ?? null,
                'pic_kontak' => $data['pic_kontak'] ?? null,
                'izinkan_unduh_default' => (bool) $data['izinkan_unduh_default'],
                'berlaku_sampai' => $data['berlaku_sampai'],
                'status' => 'aktif',
                'dibuat_oleh' => $pengguna->id,
            ]);

            if (! empty($pinBaru)) {
                $ruangRapat->pin_hash = Hash::make($pinBaru);
                $ruangRapat->save();
            }

            $dataPivot = [];
            foreach ($idDokumen as $id) {
                $override = $data['izinkan_unduh'][$id] ?? null;
                $dataPivot[$id] = [
                    'kelompok' => $data['kelompok'][$id],
                    'izinkan_unduh' => $override === null || $override === '' ? null : (bool) $override,
                ];
            }
            $ruangRapat->dokumen()->attach($dataPivot);

            LogAktivitas::catat('buat ruang rapat', $ruangRapat->judul);

            return $ruangRapat;
        });

        return redirect()->route('ruang-rapat.show', $ruangRapat)
            ->with('sukses', 'Ruang Rapat berhasil dibuat. Tautan tamu siap dibagikan.');
    }

    /**
     * Detail satu ruang rapat + tautan publiknya.
     */
    public function show(Request $request, RuangRapat $ruangRapat): View
    {
        $ruangRapat->load(['bidang', 'pembuat', 'dokumen' => fn ($query) => $query->orderBy('judul')]);
        $bidangs = Bidang::query()->orderBy('nama')->get();
        $tautanPublik = route('rapat.buka', ['token' => $ruangRapat->token]);

        return view('ruang-rapat.show', compact('ruangRapat', 'bidangs', 'tautanPublik'));
    }

    /**
     * Memperbarui pengaturan ruang rapat (termasuk PIN).
     */
    public function update(Request $request, RuangRapat $ruangRapat): RedirectResponse
    {
        // ===== 1. PEMERIKSAAN AKSES =====
        $this->pastikanPengelola($request, $ruangRapat);

        // ===== 2. VALIDASI INPUT =====
        $request->query->remove('pin');
        $pinInput = $request->input('pin');
        $request->merge(['pin' => null]);
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:180'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'tanggal_rapat' => ['nullable', 'date'],
            'lokasi' => ['nullable', 'string', 'max:180'],
            'link_virtual' => ['nullable', 'url', 'max:255'],
            'id_bidang' => ['nullable', 'integer', 'exists:bidang,id'],
            'pic_nama' => ['nullable', 'string', 'max:180'],
            'pic_kontak' => ['nullable', 'string', 'max:180'],
            'hapus_pin' => ['sometimes', 'boolean'],
            'izinkan_unduh_default' => ['required', 'boolean'],
            'berlaku_sampai' => ['required', 'date', 'after_or_equal:today'],
        ]);
        $pinBaru = Validator::make(['pin' => $pinInput], [
            'pin' => ['nullable', 'string', 'min:4', 'max:32'],
        ])->validate()['pin'];

        // ===== 3. SIMPAN PERUBAHAN =====
        $kolomDiperbarui = [
            'judul', 'deskripsi', 'tanggal_rapat', 'lokasi', 'link_virtual',
            'pic_nama', 'pic_kontak', 'izinkan_unduh_default', 'berlaku_sampai',
            'id_bidang',
        ];

        $ruangRapat->fill(array_intersect_key($data, array_flip($kolomDiperbarui)));
        $ruangRapat->izinkan_unduh_default = (bool) $data['izinkan_unduh_default'];
        $ruangRapat->save();

        // ===== 4. ATUR PIN (HAPUS / GANTI) =====
        if ($request->boolean('hapus_pin')) {
            $ruangRapat->pin_hash = null;
            $ruangRapat->save();
        } elseif (! empty($pinBaru)) {
            $ruangRapat->pin_hash = Hash::make($pinBaru);
            $ruangRapat->save();
        }

        // ===== CATAT LOG & RESPONS =====
        LogAktivitas::catat('ubah pengaturan ruang rapat', $ruangRapat->judul);

        return back()->with('sukses', 'Pengaturan Ruang Rapat berhasil diperbarui.');
    }

    /**
     * Menonaktifkan tautan ruang rapat.
     */
    public function nonaktifkan(Request $request, RuangRapat $ruangRapat): RedirectResponse
    {
        $this->pastikanPengelola($request, $ruangRapat);
        $ruangRapat->update(['status' => 'nonaktif']);
        LogAktivitas::catat('nonaktifkan ruang rapat', $ruangRapat->judul);

        return back()->with('sukses', 'Tautan Ruang Rapat dinonaktifkan.');
    }

    /**
     * Mengunggah notulen rapat (otomatis menjadi dokumen publik).
     * Batas ukuran berkas: 50 MB.
     */
    public function uploadNotulen(Request $request, RuangRapat $ruangRapat): RedirectResponse
    {
        // ===== 1. PEMERIKSAAN AKSES =====
        $pengguna = $this->pastikanPengelola($request, $ruangRapat);

        // ===== 2. VALIDASI INPUT =====
        $data = $request->validate([
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
            'judul' => ['nullable', 'string', 'max:180'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ], ['file.max' => 'Ukuran dokumen maksimal 50 MB.']);

        // ===== 3. PENYIMPANAN BERKAS =====
        $file = $request->file('file');
        $path = $file->storeAs(
            'dokumen',
            time().'_'.preg_replace('/[^A-Za-z0-9._-]/', '_', $file->hashName()),
            'public'
        );

        // ===== 4. SIMPAN DOKUMEN + KAITKAN KE RUANG RAPAT =====
        try {
            DB::transaction(function () use ($data, $file, $path, $pengguna, $ruangRapat): void {
                $dokumenBaru = Dokumen::create([
                    'judul' => ! empty($data['judul']) ? $data['judul'] : Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 180, ''),
                    'deskripsi' => $data['deskripsi'] ?? null,
                    'kategori' => 'Notulen Rapat',
                    'visibility' => 'publik',
                    'id_bidang' => $ruangRapat->id_bidang,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'mime' => $file->getClientMimeType(),
                    'uploaded_by' => $pengguna->id,
                ]);

                $ruangRapat->dokumen()->attach($dokumenBaru->id, [
                    'kelompok' => 'notulen',
                    'izinkan_unduh' => null,
                ]);

                LogAktivitas::catat('unggah notulen ruang rapat', Str::limit($ruangRapat->judul.' · '.$dokumenBaru->judul, 255, ''));
            });
        } catch (Throwable $kesalahan) {
            // Batalan: hapus berkas yang sudah terlanjur tersimpan.
            Storage::disk('public')->delete($path);
            throw $kesalahan;
        }

        return back()->with('sukses', 'Notulen berhasil diunggah dan ditambahkan ke Ruang Rapat.');
    }

    /**
     * Memastikan pengguna adalah admin atau pegawai bidang.
     */
    private function pastikanPengelola(Request $request, ?RuangRapat $ruangRapat = null): User
    {
        $pengguna = $request->user();
        abort_unless($pengguna && ($pengguna->isAdmin() || $pengguna->isBidang()), 403, 'Ruang Rapat hanya dapat dikelola admin atau pegawai bidang.');

        return $pengguna;
    }
}

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

class RuangRapatController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $status = (string) $request->query('status', 'semua');
        $q = trim((string) $request->query('q', ''));
        $idBidang = $request->query('id_bidang');

        $baseQuery = RuangRapat::query();
        if ($idBidang) {
            $baseQuery->where('id_bidang', $idBidang);
        }

        // Hitungan metrik untuk kartu ringkasan
        $totalSemua = (clone $baseQuery)->count();
        $totalAktif = (clone $baseQuery)->where('status', 'aktif')->whereDate('berlaku_sampai', '>=', today())->count();
        $totalKedaluwarsa = (clone $baseQuery)->where('status', 'aktif')->whereDate('berlaku_sampai', '<', today())->count();
        $totalNonaktif = (clone $baseQuery)->where('status', 'nonaktif')->count();

        $query = (clone $baseQuery)
            ->with(['bidang', 'pembuat'])
            ->withCount('dokumen')
            ->latest();

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('judul', 'like', "%{$q}%")
                    ->orWhere('deskripsi', 'like', "%{$q}%")
                    ->orWhere('lokasi', 'like', "%{$q}%")
                    ->orWhere('pic_nama', 'like', "%{$q}%")
                    ->orWhereHas('bidang', function ($bq) use ($q) {
                        $bq->where('nama', 'like', "%{$q}%");
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

        $bidangs = Bidang::query()->orderBy('nama')->get();

        return view('ruang-rapat.index', compact('ruangRapats', 'status', 'statusOptions', 'q', 'counts', 'bidangs', 'idBidang'));
    }

    public function create(Request $request): View
    {
        $user = $this->pastikanPengelola($request);
        $dokumen = Dokumen::query()
            ->with('bidang')
            ->where('visibility', 'publik')
            ->orderBy('judul')
            ->get();
        $bidangs = Bidang::query()->orderBy('nama')->get();

        return view('ruang-rapat.create', compact('dokumen', 'bidangs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->pastikanPengelola($request);
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
        $pin = Validator::make(['pin' => $pinInput], [
            'pin' => ['nullable', 'string', 'min:4', 'max:32'],
        ])->validate()['pin'];

        $dokumenIds = array_map('intval', $data['dokumen']);
        $dokumenTerpilih = Dokumen::query()
            ->whereIn('id', $dokumenIds)
            ->where('visibility', 'publik')
            ->get()
            ->keyBy('id');

        if ($dokumenTerpilih->count() !== count(array_unique($dokumenIds))) {
            throw ValidationException::withMessages([
                'dokumen' => 'Ruang Rapat hanya dapat memuat dokumen dengan visibilitas Publik.',
            ]);
        }

        foreach ($dokumenIds as $id) {
            if (! in_array($data['kelompok'][$id] ?? null, ['agenda', 'materi', 'pendukung', 'notulen'], true)) {
                throw ValidationException::withMessages([
                    'kelompok' => 'Pilih kelompok yang valid untuk setiap dokumen terpilih.',
                ]);
            }
        }

        $ruangRapat = DB::transaction(function () use ($user, $data, $dokumenIds, $pin): RuangRapat {
            $ruangRapat = RuangRapat::create([
                'judul' => $data['judul'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'tanggal_rapat' => $data['tanggal_rapat'] ?? null,
                'lokasi' => $data['lokasi'] ?? null,
                'link_virtual' => $data['link_virtual'] ?? null,
                'id_bidang' => $data['id_bidang'] ?? ($user->id_bidang ?: null),
                'pic_nama' => $data['pic_nama'] ?? null,
                'pic_kontak' => $data['pic_kontak'] ?? null,
                'izinkan_unduh_default' => (bool) $data['izinkan_unduh_default'],
                'berlaku_sampai' => $data['berlaku_sampai'],
                'status' => 'aktif',
                'dibuat_oleh' => $user->id,
            ]);

            if (! empty($pin)) {
                $ruangRapat->pin_hash = Hash::make($pin);
                $ruangRapat->save();
            }

            $pivot = [];
            foreach ($dokumenIds as $id) {
                $override = $data['izinkan_unduh'][$id] ?? null;
                $pivot[$id] = [
                    'kelompok' => $data['kelompok'][$id],
                    'izinkan_unduh' => $override === null || $override === '' ? null : (bool) $override,
                ];
            }
            $ruangRapat->dokumen()->attach($pivot);

            LogAktivitas::catat('buat ruang rapat', $ruangRapat->judul);

            return $ruangRapat;
        });

        return redirect()->route('ruang-rapat.show', $ruangRapat)
            ->with('sukses', 'Ruang Rapat berhasil dibuat. Tautan tamu siap dibagikan.');
    }

    public function show(Request $request, RuangRapat $ruangRapat): View
    {
        $ruangRapat->load(['bidang', 'pembuat', 'dokumen' => fn ($query) => $query->orderBy('judul')]);
        $bidangs = Bidang::query()->orderBy('nama')->get();
        $tautanPublik = route('rapat.buka', ['token' => $ruangRapat->token]);

        return view('ruang-rapat.show', compact('ruangRapat', 'bidangs', 'tautanPublik'));
    }

    public function update(Request $request, RuangRapat $ruangRapat): RedirectResponse
    {
        $user = $this->pastikanPengelola($request, $ruangRapat);
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
        $pin = Validator::make(['pin' => $pinInput], [
            'pin' => ['nullable', 'string', 'min:4', 'max:32'],
        ])->validate()['pin'];

        $fields = [
            'judul', 'deskripsi', 'tanggal_rapat', 'lokasi', 'link_virtual',
            'pic_nama', 'pic_kontak', 'izinkan_unduh_default', 'berlaku_sampai',
            'id_bidang',
        ];

        $ruangRapat->fill(array_intersect_key($data, array_flip($fields)));
        $ruangRapat->izinkan_unduh_default = (bool) $data['izinkan_unduh_default'];
        $ruangRapat->save();

        if ($request->boolean('hapus_pin')) {
            $ruangRapat->pin_hash = null;
            $ruangRapat->save();
        } elseif (! empty($pin)) {
            $ruangRapat->pin_hash = Hash::make($pin);
            $ruangRapat->save();
        }

        LogAktivitas::catat('ubah pengaturan ruang rapat', $ruangRapat->judul);

        return back()->with('sukses', 'Pengaturan Ruang Rapat berhasil diperbarui.');
    }

    public function nonaktifkan(Request $request, RuangRapat $ruangRapat): RedirectResponse
    {
        $this->pastikanPengelola($request, $ruangRapat);
        $ruangRapat->update(['status' => 'nonaktif']);
        LogAktivitas::catat('nonaktifkan ruang rapat', $ruangRapat->judul);

        return back()->with('sukses', 'Tautan Ruang Rapat dinonaktifkan.');
    }

    public function uploadNotulen(Request $request, RuangRapat $ruangRapat): RedirectResponse
    {
        $user = $this->pastikanPengelola($request, $ruangRapat);
        $data = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg,txt',
                'max:51200',
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

        $file = $request->file('file');
        $path = $file->storeAs(
            'dokumen',
            time().'_'.preg_replace('/[^A-Za-z0-9._-]/', '_', $file->hashName()),
            'public'
        );

        try {
            DB::transaction(function () use ($data, $file, $path, $user, $ruangRapat): void {
                $dokumen = Dokumen::create([
                    'judul' => ! empty($data['judul']) ? $data['judul'] : Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 180, ''),
                    'deskripsi' => $data['deskripsi'] ?? null,
                    'kategori' => 'Notulen Rapat',
                    'visibility' => 'publik',
                    'id_bidang' => $ruangRapat->id_bidang,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'mime' => $file->getClientMimeType(),
                    'uploaded_by' => $user->id,
                ]);

                $ruangRapat->dokumen()->attach($dokumen->id, [
                    'kelompok' => 'notulen',
                    'izinkan_unduh' => null,
                ]);

                LogAktivitas::catat('unggah notulen ruang rapat', Str::limit($ruangRapat->judul.' · '.$dokumen->judul, 255, ''));
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        return back()->with('sukses', 'Notulen berhasil diunggah dan ditambahkan ke Ruang Rapat.');
    }

    private function pastikanPengelola(Request $request, ?RuangRapat $ruangRapat = null): User
    {
        $user = $request->user();
        abort_unless($user && ($user->isAdmin() || $user->isBidang()), 403, 'Ruang Rapat hanya dapat dikelola admin atau pegawai bidang.');

        return $user;
    }
}

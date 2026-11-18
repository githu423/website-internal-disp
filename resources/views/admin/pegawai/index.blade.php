{{-- ===== HALAMAN: Admin · Data Pegawai — daftar, tambah/ubah/hapus, import Excel. ===== --}}
@extends('layouts.dashboard')

@section('title', 'Kelola Pegawai')
@section('header_title')
    <span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Manajemen Data Pegawai, Jabatan, dan Penempatan Bidang Dinas Pariwisata')

@section('content')
    <div class="space-y-6">

        {{-- ===== BAGIAN: Breadcrumbs Mikro ===== --}}
        <nav class="flex items-center text-sm font-medium text-slate-400">
            <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
            <span class="mx-2 text-slate-300">/</span>
            <span class="text-slate-400">Admin — Kepegawaian</span>
            <span class="mx-2 text-slate-300">/</span>
            <span class="text-slate-700 font-semibold">Kelola Pegawai</span>
        </nav>

        {{-- ===== BAGIAN: Header Halaman & Filter Toolbar ===== --}}
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-2 border-b border-slate-200/60">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Data Pegawai Dinas</h1>
                    <span
                        class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                        {{ $total }} orang
                    </span>
                    @if ($belumLengkap > 0 || request('belum'))
                        <a href="{{ request()->fullUrlWithQuery(['belum' => request('belum') ? null : 1]) }}"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold transition {{ request('belum') ? 'bg-rose-600 text-white shadow-sm' : 'bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100' }}">
                            <span>⚠</span> {{ $belumLengkap }} belum lengkap · {{ $lengkap }} lengkap —
                            {{ request('belum') ? 'Reset Filter' : 'Filter Belum Lengkap' }}
                        </a>
                    @endif
                </div>
                <p class="text-sm text-slate-500 mt-1">Daftar aparatur sipil, riwayat kepangkatan, jabatan, dan penugasan
                    bidang kerja.</p>
            </div>

            {{-- ===== BAGIAN: AKSI CEPAT & TAMBAH ===== --}}
            <div class="flex flex-wrap items-center gap-2.5">
                <button data-modal-open="modal-pegawai"
                    class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-xl shadow-sm shadow-purple-500/30 hover:bg-purple-700 hover:shadow-purple-600/40 active:scale-95 transition-all duration-150">
                    <x-icon name="plus" class="h-4 w-4" />
                    Tambah Pegawai Manual
                </button>
            </div>
        </div>

        {{-- ===== BAGIAN: Status Filter Tabs (sinkron Data Pegawai & Dashboard) ===== --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="inline-flex items-center bg-white border border-slate-200 rounded-xl p-1 shadow-sm">
                @foreach (['' => 'Semua', 'aktif' => 'Aktif', 'nonaktif' => 'Non-Aktif', 'pensiun' => 'Pensiun'] as $sv => $slab)
                    <a href="{{ request()->fullUrlWithQuery(['status' => $sv ?: null, 'page' => null]) }}"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ ($statusFilter ?? '') === $sv ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-600 hover:text-purple-600 hover:bg-slate-50' }}">
                        {{ $slab }}@if(isset($statistik))<span
                        class="ml-1 opacity-70">({{ $statistik[$sv ?: 'semua'] ?? 0 }})</span>@endif
                    </a>
                @endforeach
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                <span class="text-slate-400">Rekap:</span>
                <span class="text-emerald-700"><b>{{ $statistik['aktif'] ?? 0 }}</b> Aktif</span>
                <span class="text-amber-700"><b>{{ $statistik['nonaktif'] ?? 0 }}</b> Non-Aktif</span>
                <span class="text-slate-500"><b>{{ $statistik['pensiun'] ?? 0 }}</b> Pensiun</span>
            </div>
        </div>

        {{-- ===== BAGIAN: BILAH ALAT FILTER ===== --}}
        <form method="GET"
            class="bg-white border border-slate-200 rounded-xl p-3.5 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            @if (request('belum'))<input type="hidden" name="belum" value="1">@endif
            @if (($statusFilter ?? '') !== '')<input type="hidden" name="status" value="{{ $statusFilter }}">@endif

            <div class="flex flex-1 flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                <div class="relative flex-1">
                    <input name="q" value="{{ request('q') }}" placeholder="Cari nama, NIP, atau kata kunci..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 pl-9 pr-3.5 py-2 text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                    <span class="absolute left-3 top-2.5 text-slate-400">
                        <x-icon name="search" class="h-4 w-4" />
                    </span>
                </div>

                @php
                    $bidangOpts = ['' => 'Semua Bidang / Penempatan'];
                    foreach ($bidangs as $b) {
                        $bidangOpts[$b->id] = $b->nama;
                    }
                @endphp
                <x-select name="bidang" :options="$bidangOpts" :value="request('bidang')" autoSubmit
                    class="w-full sm:w-64" />
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button type="submit"
                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs md:text-sm rounded-xl shadow-sm transition">
                    Terapkan
                </button>
                @if(request('q') || request('bidang') || request('belum') || ($statusFilter ?? ''))
                    <a href="{{ route('admin.pegawai.index') }}"
                        class="px-3.5 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        {{-- ===== BAGIAN: PESAN PERINGATAN ===== --}}
        @if ($errors->any())
            <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-rose-700 text-sm font-medium">
                <div class="flex items-center gap-2">
                    <span>⚠</span>
                    <span class="font-bold">Gagal menyimpan data pegawai:</span>
                    <span>{{ $errors->first() }}</span>
                </div>
                <p class="text-xs text-rose-600 mt-1">Perbaiki kolom yang dimaksud lalu tekan Simpan lagi — kolom lain boleh
                    dibiarkan kosong.</p>
            </div>
        @endif

        {{-- ===== BAGIAN: Grid Konten: Tabel Pegawai & Panel Impor ===== --}}
        <div class="grid lg:grid-cols-3 gap-5">
            {{-- ===== BAGIAN: KOLOM KIRI — tabel pegawai ===== --}}
            <div class="lg:col-span-2">
                <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
                    <div class="overflow-x-auto thin-scroll">
                        <table class="data w-full min-w-[700px]">
                            <thead>
                                <tr
                                    class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-[11px] uppercase tracking-wider font-extrabold">
                                    <th class="py-3 px-4">Nama / NIP</th>
                                    <th class="py-3 px-3">Jabatan &amp; Golongan</th>
                                    <th class="py-3 px-3">Bidang Penempatan</th>
                                    <th class="py-3 px-3 text-center">Status</th>
                                    <th class="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($pegawai as $p)
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="py-3.5 px-4">
                                            <p class="font-semibold text-[13px] text-slate-900 leading-snug">{{ $p->nama }}</p>

                                            @if ($modeBelum)
                                                <p class="text-[11px] text-slate-400 font-mono mt-0.5">{{ $p->nip }}</p>

                                                @if (method_exists($p, 'kolomKurang') && count($p->kolomKurang()))
                                                    <p class="mt-1 text-[11px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md inline-block"
                                                        title="{{ implode(', ', $p->kolomKurang()) }}">
                                                        belum lengkap: {{ $p->ringkasKurang() }}
                                                    </p>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-3">
                                            <p class="text-xs font-semibold text-slate-800 leading-snug">
                                                {{ $p->jabatan ?? '—' }}</p>
                                            <p class="text-[11px] text-slate-400 mt-0.5">
                                                {{ $p->pangkat_golongan }} · {{ $p->masa_kerja['label'] ?? '—' }}
                                                @if ($p->umur !== null)
                                                    · <span title="Umur terhitung dari tanggal lahir">{{ $p->umur }} th</span>
                                                @endif
                                            </p>
                                        </td>
                                        <td class="py-3.5 px-3">
                                            @if($p->bidang)
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-purple-50 text-purple-700 border border-purple-100">
                                                    {{ $p->bidang->nama }}
                                                </span>
                                            @else
                                                <span class="text-xs text-slate-400 italic">Belum ditempatkan</span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-3 text-center">
                                            @php
                                                $stMapPeg = [
                                                    'aktif' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                                                    'nonaktif' => 'bg-amber-50 text-amber-700 border border-amber-200',
                                                    'pensiun' => 'bg-slate-100 text-slate-600 border border-slate-200',
                                                ];
                                            @endphp
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $stMapPeg[$p->status] ?? 'bg-slate-100 text-slate-600 border-slate-200' }}">
                                                {{ ucfirst($p->status) }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-1">
                                                <button data-modal-open="pg-{{ $p->id }}"
                                                    class="px-2.5 py-1 text-xs font-bold rounded-lg text-purple-700 hover:bg-purple-50 transition">
                                                    Edit
                                                </button>
                                                <form method="POST" action="{{ route('admin.pegawai.destroy', $p) }}"
                                                    data-confirm="Hapus pegawai {{ $p->nama }}?" class="inline">
                                                    @csrf @method('DELETE')
                                                    <button
                                                        class="px-2 py-1 text-xs font-bold rounded-lg text-rose-600 hover:bg-rose-50 transition">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Modal Edit Pegawai --}}
                                    <div id="pg-{{ $p->id }}"
                                        class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 backdrop-blur-sm p-4 grid place-items-center overflow-y-auto">
                                        <div
                                            class="bg-white border border-slate-200 rounded-2xl w-full max-w-lg p-6 shadow-2xl text-sm">
                                            <div class="flex justify-between items-center mb-3 pb-3 border-b border-slate-100">
                                                <div>
                                                    <h3 class="font-extrabold text-base text-slate-900">Edit Data Pegawai</h3>
                                                    <p class="text-xs text-slate-400 mt-0.5">{{ $p->nama }} (NIP: {{ $p->nip }})
                                                    </p>
                                                </div>
                                                <button data-modal-close
                                                    class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400 grid place-items-center transition">✕</button>
                                            </div>
                                            <p
                                                class="mb-3 text-xs text-slate-600 bg-purple-50/70 border border-purple-100 rounded-xl px-3.5 py-2.5">
                                                ✏️ Cukup ubah kolom yang diperlukan lalu tekan <b>Simpan</b>. Kolom lain boleh
                                                dibiarkan kosong.
                                            </p>
                                            <form method="POST" action="{{ route('admin.pegawai.update', $p) }}"
                                                class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                                @csrf @method('PATCH')
                                                <label class="block sm:col-span-2">
                                                    <span
                                                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Nama
                                                        Lengkap <span class="text-rose-500">*</span></span>
                                                    <input name="nama" value="{{ $p->nama }}" required
                                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                                                </label>
                                                <label class="block">
                                                    <span
                                                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">NIP
                                                        <span class="text-rose-500">*</span></span>
                                                    <input name="nip" value="{{ $p->nip }}" required
                                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs font-mono focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                                                </label>
                                                <label class="block">
                                                    <span
                                                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Status
                                                        Kepegawaian</span>
                                                    <select name="status"
                                                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs bg-white focus:outline-none focus:border-purple-400">
                                                        @foreach (['aktif', 'nonaktif', 'pensiun'] as $s)
                                                            <option value="{{ $s }}" @selected($p->status === $s)>{{ ucfirst($s) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </label>
                                                <label class="block">
                                                    <span
                                                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Jenis
                                                        ASN</span>
                                                    <select name="jenis_asn"
                                                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs bg-white focus:outline-none focus:border-purple-400">
                                                        <option value="">— Belum Diketahui —</option>
                                                        @foreach (['PNS', 'PPPK'] as $j)
                                                            <option value="{{ $j }}" @selected($p->jenis_asn === $j)>{{ $j }}</option>
                                                        @endforeach
                                                    </select>
                                                </label>
                                                <label class="block">
                                                    <span
                                                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Jenis
                                                        Kelamin</span>
                                                    <select name="jenis_kelamin"
                                                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs bg-white focus:outline-none focus:border-purple-400">
                                                        <option value="">— Belum Diketahui —</option>
                                                        @foreach (['L' => 'Laki-laki', 'P' => 'Perempuan'] as $k => $v)
                                                            <option value="{{ $k }}" @selected($p->jenis_kelamin === $k)>{{ $v }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </label>
                                                <label class="block">
                                                    <span
                                                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Tempat
                                                        Lahir</span>
                                                    <input name="tempat_lahir" value="{{ $p->tempat_lahir }}"
                                                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                                </label>
                                                <label class="block">
                                                    <span
                                                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Tanggal
                                                        Lahir</span>
                                                    <input type="date" name="tanggal_lahir"
                                                        value="{{ $p->tanggal_lahir?->format('Y-m-d') }}"
                                                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                                </label>
                                                <label class="block">
                                                    <span
                                                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Pangkat/Gol.
                                                        Awal</span>
                                                    <input name="pangkat_awal" value="{{ $p->pangkat_awal }}"
                                                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400"
                                                        placeholder="mis. Pengatur Muda (II/a)">
                                                </label>
                                                <label class="block">
                                                    <span
                                                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">TMT
                                                        CPNS</span>
                                                    <input type="date" name="tmt_cpns"
                                                        value="{{ $p->tmt_cpns?->format('Y-m-d') }}"
                                                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                                </label>
                                                <label class="block">
                                                    <span
                                                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Pangkat/Gol.
                                                        Terakhir</span>
                                                    <input name="pangkat_golongan" value="{{ $p->pangkat_golongan }}"
                                                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400"
                                                        placeholder="mis. Penata (III/c)">
                                                </label>
                                                <label class="block">
                                                    <span
                                                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">TMT
                                                        Pangkat Sekarang</span>
                                                    <input type="date" name="tmt_sekarang"
                                                        value="{{ $p->tmt_sekarang?->format('Y-m-d') }}"
                                                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                                </label>
                                                <label class="block sm:col-span-2">
                                                    <span
                                                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Jabatan
                                                        <span class="normal-case font-medium text-slate-400">(sesuai SK
                                                            resmi)</span></span>
                                                    <input name="jabatan" value="{{ $p->jabatan }}"
                                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:outline-none focus:border-purple-400"
                                                        placeholder="mis. Kepala Sub Bagian Umum dan Kepegawaian...">
                                                </label>
                                                <label class="block">
                                                    <span
                                                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">TMT
                                                        Jabatan</span>
                                                    <input type="date" name="tmt_jabatan"
                                                        value="{{ $p->tmt_jabatan?->format('Y-m-d') }}"
                                                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                                </label>
                                                <label class="block">
                                                    <span
                                                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Pendidikan
                                                        Terakhir</span>
                                                    <input name="pendidikan" value="{{ $p->pendidikan }}"
                                                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400"
                                                        placeholder="mis. S-1 Pariwisata">
                                                </label>
                                                <label class="block sm:col-span-2">
                                                    <span
                                                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Unit
                                                        Kerja <span class="normal-case font-medium text-slate-400">(sesuai data
                                                            berkas)</span></span>
                                                    <input name="unit_kerja" value="{{ $p->unit_kerja }}"
                                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:outline-none focus:border-purple-400">
                                                </label>
                                                <label class="block sm:col-span-2">
                                                    <span
                                                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Penempatan
                                                        Bidang <span class="normal-case font-medium text-slate-400">(menentukan
                                                            agenda &amp; modul bidang)</span></span>
                                                    <select name="id_bidang"
                                                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs bg-white focus:outline-none focus:border-purple-400">
                                                        <option value="">— Belum Ditempatkan —</option>
                                                        @foreach ($bidangs as $b)
                                                            <option value="{{ $b->id }}" @selected($p->id_bidang == $b->id)>
                                                                {{ $b->nama }}</option>
                                                        @endforeach
                                                    </select>
                                                </label>
                                                <label class="block sm:col-span-2">
                                                    <span
                                                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Diklat
                                                        Struktural</span>
                                                    <input name="diklat_struktural" value="{{ $p->diklat_struktural }}"
                                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-1.5 text-xs focus:outline-none focus:border-purple-400"
                                                        placeholder="mis. Diklat PIM Tk. III 2021">
                                                </label>
                                                <label class="block">
                                                    <span
                                                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Diklat
                                                        Fungsional</span>
                                                    <input name="diklat_fungsional" value="{{ $p->diklat_fungsional }}"
                                                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                                </label>
                                                <label class="block">
                                                    <span
                                                        class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Diklat
                                                        Teknis</span>
                                                    <input name="diklat_teknis" value="{{ $p->diklat_teknis }}"
                                                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                                </label>

                                                <button type="submit"
                                                    class="sm:col-span-2 w-full rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 active:scale-[.98] transition shadow-sm shadow-purple-500/30 mt-2">
                                                    Simpan Perubahan Pegawai
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-10 text-center">
                                            <x-empty icon="pegawai" title="Tidak ada data pegawai"
                                                desc="Impor berkas daftar pegawai di panel sebelah kanan atau tambahkan secara manual." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($pegawai->hasPages())
                        <div class="p-3.5 border-t border-slate-100 bg-slate-50/50">
                            {{ $pegawai->links() }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- ===== BAGIAN: KOLOM KANAN — impor & catatan ===== --}}
            <div class="space-y-4">
                <x-import-card :action="route('admin.pegawai.import')" title="Impor Berkas Daftar Pegawai (Excel/CSV)"
                    desc="Format: kolom No., NAMA LENGKAP, N I P, tempat/tanggal lahir, pangkat, TMT CPNS, jabatan, dst. Data lama dengan NIP sama diperbarui secara otomatis; pegawai baru otomatis memperoleh akun akses login SIPARDIS." />

                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm text-sm">
                    <h3 class="font-extrabold text-[14px] text-slate-900 mb-2.5 flex items-center gap-2">
                        <span>💡</span> Info Penempatan &amp; Akun
                    </h3>
                    <ul class="space-y-2 text-xs text-slate-500 leading-relaxed list-disc pl-4">
                        <li>Penempatan bidang menentukan filter agenda internal dan izin input sub-kegiatan bidang kerja.
                        </li>
                        <li>NIP digunakan sebagai username login awal pegawai baru.</li>
                        <li>Riwayat diklat dan TMT otomatis dihitung untuk indikator masa kerja.</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

    {{-- Modal Tambah Pegawai Manual --}}
    {{-- ===== BAGIAN: MODAL TAMBAH/HAPUS PEGAWAI ===== --}}
    <div id="modal-pegawai"
        class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 backdrop-blur-sm p-4 grid place-items-center overflow-y-auto">
        <div class="bg-white border border-slate-200 rounded-2xl w-full max-w-lg p-6 shadow-2xl text-sm">
            <div class="flex justify-between items-center mb-3 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-extrabold text-base text-slate-900">Tambah Pegawai Manual</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Input data aparatur sipil dinas baru.</p>
                </div>
                <button data-modal-close
                    class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400 grid place-items-center transition">✕</button>
            </div>
            <p class="mb-3 text-xs text-slate-600 bg-purple-50/70 border border-purple-100 rounded-xl px-3.5 py-2.5">
                Wajib hanya <b>Nama Lengkap</b> &amp; <b>NIP</b> — kolom lain boleh dilengkapi nanti lewat tombol Edit.
            </p>
            @if ($errors->any())
                <div class="mb-3 rounded-xl bg-rose-50 border border-rose-200 px-3.5 py-2.5 text-rose-700 text-xs font-medium">
                    {{ $errors->first() }}
                </div>
            @endif
            <form method="POST" action="{{ route('admin.pegawai.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                @csrf
                <label class="block sm:col-span-2">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Nama Lengkap <span
                            class="text-rose-500">*</span></span>
                    <input name="nama" required placeholder="Nama lengkap beserta gelar"
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">NIP <span
                            class="text-rose-500">*</span></span>
                    <input name="nip" required placeholder="19XXXXXXXXXXXXXX"
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs font-mono focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Status
                        Kepegawaian</span>
                    <select name="status"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs bg-white focus:outline-none focus:border-purple-400">
                        @foreach (['aktif', 'nonaktif', 'pensiun'] as $s)
                            <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Jenis ASN</span>
                    <select name="jenis_asn"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs bg-white focus:outline-none focus:border-purple-400">
                        <option value="">— Belum Diketahui —</option>
                        <option value="PNS">PNS</option>
                        <option value="PPPK">PPPK</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Jenis Kelamin</span>
                    <select name="jenis_kelamin"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs bg-white focus:outline-none focus:border-purple-400">
                        <option value="">— Belum Diketahui —</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Tempat Lahir</span>
                    <input name="tempat_lahir"
                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Tanggal Lahir</span>
                    <input type="date" name="tanggal_lahir"
                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Pangkat/Gol.
                        Awal</span>
                    <input name="pangkat_awal"
                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400"
                        placeholder="mis. Pengatur Muda (II/a)">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">TMT CPNS</span>
                    <input type="date" name="tmt_cpns"
                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Pangkat/Gol.
                        Terakhir</span>
                    <input name="pangkat_golongan"
                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400"
                        placeholder="mis. Penata (III/c)">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">TMT Pangkat
                        Sekarang</span>
                    <input type="date" name="tmt_sekarang"
                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                </label>
                <label class="block sm:col-span-2">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Jabatan <span
                            class="normal-case font-medium text-slate-400">(sesuai SK resmi)</span></span>
                    <input name="jabatan"
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:outline-none focus:border-purple-400"
                        placeholder="mis. Pengadministrasi Umum Pada Dinas Pariwisata">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">TMT Jabatan</span>
                    <input type="date" name="tmt_jabatan"
                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Pendidikan
                        Terakhir</span>
                    <input name="pendidikan"
                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400"
                        placeholder="mis. S-1">
                </label>
                <label class="block sm:col-span-2">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Unit Kerja <span
                            class="normal-case font-medium text-slate-400">(sesuai file Excel)</span></span>
                    <input name="unit_kerja"
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:outline-none focus:border-purple-400">
                </label>
                <label class="block sm:col-span-2">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Penempatan Bidang
                        <span class="normal-case font-medium text-slate-400">(menentukan kelompok kerja)</span></span>
                    <select name="id_bidang"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs bg-white focus:outline-none focus:border-purple-400">
                        <option value="">— Belum Ditempatkan —</option>
                        @foreach ($bidangs as $b)
                            <option value="{{ $b->id }}">{{ $b->nama }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block sm:col-span-2">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Diklat
                        Struktural</span>
                    <input name="diklat_struktural"
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-1.5 text-xs focus:outline-none focus:border-purple-400"
                        placeholder="mis. Diklat PIM Tk. III 2021">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Diklat
                        Fungsional</span>
                    <input name="diklat_fungsional"
                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Diklat Teknis</span>
                    <input name="diklat_teknis"
                        class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                </label>

                <button type="submit"
                    class="sm:col-span-2 w-full rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 active:scale-[.98] transition shadow-sm shadow-purple-500/30 mt-2">
                    Simpan Data Pegawai
                </button>
            </form>
        </div>
    </div>
@endsection
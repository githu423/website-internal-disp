@extends('layouts.dashboard')

@section('title', 'Ruang Rapat')
@section('header_title')
    <span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Paket dokumen rapat untuk dibagikan secara aman kepada tamu')

@section('content')
    @php
        $u = auth()->user();
    @endphp

    <div class="space-y-6">
        <!-- 1. Breadcrumbs -->
        <nav class="flex items-center text-sm font-medium text-slate-400">
            <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
            <span class="mx-2 text-slate-300">/</span>
            <span class="text-slate-400">Kantor</span>
            <span class="mx-2 text-slate-300">/</span>
            <span class="text-slate-700 font-semibold">Ruang Rapat</span>
        </nav>

        <!-- 2. Header Halaman -->
        <div class="flex flex-col gap-4 border-b border-slate-200/60 pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Ruang Rapat</h1>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-purple-200 bg-purple-50/80 px-2.5 py-0.5 text-xs font-bold text-purple-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-purple-600"></span>
                        {{ $counts['semua'] }} Paket Rapat
                    </span>
                    @if ($counts['aktif'] > 0)
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50/80 px-2.5 py-0.5 text-xs font-bold text-emerald-700">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            {{ $counts['aktif'] }} Aktif
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-slate-500">Kelola paket tautan dokumen rapat tamu dengan kendali keamanan PIN dan masa berlaku otomatis.</p>
            </div>
            @if ($u->isAdmin() || $u->isBidang())
                <a href="{{ route('ruang-rapat.create') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm shadow-purple-500/25 transition hover:from-purple-700 hover:to-indigo-700 active:scale-95">
                    <x-icon name="plus" class="h-4 w-4" /> Buat Ruang Rapat
                </a>
            @endif
        </div>

        @if (session('sukses'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('sukses') }}
            </div>
        @endif

        <!-- 3. Unified Command Toolbar -->
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            {{-- Kiri: Segmented Status Pills --}}
            @php
                $statusTabs = [
                    'semua' => ['label' => 'Semua', 'count' => $counts['semua'], 'dot' => 'bg-purple-500'],
                    'aktif' => ['label' => 'Aktif', 'count' => $counts['aktif'], 'dot' => 'bg-emerald-500'],
                    'kedaluwarsa' => ['label' => 'Kedaluwarsa', 'count' => $counts['kedaluwarsa'], 'dot' => 'bg-amber-500'],
                    'nonaktif' => ['label' => 'Nonaktif', 'count' => $counts['nonaktif'], 'dot' => 'bg-slate-400'],
                ];
            @endphp
            <div class="inline-flex items-center gap-1 overflow-x-auto rounded-2xl bg-slate-100/90 p-1 text-xs thin-scroll">
                @foreach ($statusTabs as $sKey => $sTab)
                    @php
                        $isActiveTab = ($status === $sKey);
                        $url = route('ruang-rapat.index', array_merge(request()->except(['status', 'page']), ['status' => $sKey]));
                    @endphp
                    <a href="{{ $url }}"
                        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-xl px-3 py-1.5 font-bold transition-all active:scale-95 {{ $isActiveTab ? 'bg-white text-slate-900 shadow-sm shadow-slate-200/80 ring-1 ring-slate-200/60' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-200/50' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $sTab['dot'] }}"></span>
                        <span>{{ $sTab['label'] }}</span>
                        <span class="rounded-md px-1.5 py-0.2 text-[10px] {{ $isActiveTab ? 'bg-purple-50 text-purple-700 font-extrabold' : 'text-slate-400 font-semibold' }}">
                            {{ $sTab['count'] }}
                        </span>
                    </a>
                @endforeach
            </div>

            {{-- Kanan: View Switcher (Grid/List) + Compact Search Bar + Filter Bidang --}}
            <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                {{-- View Switcher Buttons --}}
                <div class="inline-flex items-center rounded-xl border border-slate-200 bg-white p-0.5 shadow-sm">
                    <button type="button" id="btn-view-grid" title="Tampilan Kartu"
                        class="btn-view-toggle rounded-lg p-1.5 text-slate-400 transition hover:text-purple-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7" rx="1.5" />
                            <rect x="14" y="3" width="7" height="7" rx="1.5" />
                            <rect x="3" y="14" width="7" height="7" rx="1.5" />
                            <rect x="14" y="14" width="7" height="7" rx="1.5" />
                        </svg>
                    </button>
                    <button type="button" id="btn-view-list" title="Tampilan Tabel/Daftar"
                        class="btn-view-toggle rounded-lg p-1.5 text-slate-400 transition hover:text-purple-600">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>

                {{-- Form Pencarian --}}
                <form method="GET" action="{{ route('ruang-rapat.index') }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="status" value="{{ $status }}">

                    {{-- Search Input Wrapper (Kunci posisi X selalu di dalam border) --}}
                    <div class="relative w-full sm:w-60 md:w-64">
                        <span class="pointer-events-none absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                            <x-icon name="search" class="h-4 w-4" />
                        </span>
                        <input type="text" name="q" value="{{ $q }}"
                            placeholder="Cari judul, lokasi, PIC..."
                            class="block w-full rounded-xl border border-slate-200 bg-white pl-9 pr-8 py-2 text-xs md:text-sm text-slate-800 placeholder-slate-400 outline-none transition hover:border-slate-300 focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10">

                        @if ($q)
                            <a href="{{ route('ruang-rapat.index', array_merge(request()->except(['q', 'page']), ['status' => $status])) }}"
                                title="Hapus pencarian"
                                class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 transition">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                        @endif
                    </div>

                    {{-- Filter Bidang --}}
                    @if ($bidangs->isNotEmpty())
                        <select name="id_bidang" onchange="this.form.submit()"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs md:text-sm text-slate-700 outline-none transition hover:border-slate-300 focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10">
                            <option value="">Semua Bidang</option>
                            @foreach ($bidangs as $b)
                                <option value="{{ $b->id }}" @selected((string) $idBidang === (string) $b->id)>{{ $b->nama }}</option>
                            @endforeach
                        </select>
                    @endif

                    <button type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-3.5 py-2 text-xs md:text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 active:scale-95">
                        Cari
                    </button>

                    @if ($q || $status !== 'semua' || $idBidang)
                        <a href="{{ route('ruang-rapat.index') }}"
                            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs md:text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-purple-600">
                            Reset
                        </a>
                    @endif
                </form>
            </div>
        </div>

        @if ($ruangRapats->count())
            <!-- 4A. TAMPILAN GRID (KARTU) -->
            <div id="container-grid-view" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($ruangRapats as $ruangRapat)
                    @php
                        $kedaluwarsa = $ruangRapat->sudahKedaluwarsa();
                        $labelStatus = $ruangRapat->status === 'nonaktif' ? 'Nonaktif' : ($kedaluwarsa ? 'Kedaluwarsa' : 'Aktif');
                        $badgeStatus = $labelStatus === 'Aktif'
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                            : ($labelStatus === 'Kedaluwarsa' ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-slate-200 bg-slate-100 text-slate-600');
                        $tautanPublik = route('rapat.buka', ['token' => $ruangRapat->token]);
                    @endphp
                    <article class="clean-card group flex h-full flex-col justify-between p-5 transition-all duration-200 hover:-translate-y-1 hover:border-purple-300 hover:shadow-lg">
                        <div>
                            {{-- Baris Atas: Ikon + Status Badges --}}
                            <div class="flex items-start justify-between gap-3">
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl border border-purple-100 bg-gradient-to-br from-purple-50 to-indigo-50 text-purple-600 shadow-sm transition group-hover:scale-105">
                                    <x-icon name="agenda" class="h-5 w-5" />
                                </span>
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    {{-- Status PIN --}}
                                    <span class="rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-[10px] font-bold text-slate-600" title="{{ $ruangRapat->pin_hash ? 'Dilindungi PIN' : 'Bebas PIN' }}">
                                        {{ $ruangRapat->pin_hash ? '🔒 PIN' : '🔓 Bebas PIN' }}
                                    </span>

                                    {{-- Status Aktif/Kedaluwarsa --}}
                                    <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[10px] font-extrabold {{ $badgeStatus }}">
                                        @if ($labelStatus === 'Aktif')
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        @endif
                                        {{ $labelStatus }}
                                    </span>
                                </div>
                            </div>

                            {{-- Judul & Bidang --}}
                            <div class="mt-4">
                                <span class="inline-block rounded-md border border-purple-100 bg-purple-50/70 px-2 py-0.5 text-[11px] font-semibold text-purple-700">
                                    {{ $ruangRapat->bidang?->nama ?: 'Rapat Lintas Bidang' }}
                                </span>

                                <h2 class="mt-2 text-base font-extrabold text-slate-900 group-hover:text-purple-700 transition line-clamp-2">
                                    <a href="{{ route('ruang-rapat.show', $ruangRapat) }}">
                                        {{ $ruangRapat->judul }}
                                    </a>
                                </h2>

                                @if ($ruangRapat->deskripsi)
                                    <p class="mt-1.5 text-xs text-slate-500 line-clamp-2 leading-relaxed">
                                        {{ $ruangRapat->deskripsi }}
                                    </p>
                                @endif
                            </div>

                            {{-- Metadata Detail Rapat --}}
                            <div class="mt-4 space-y-2.5 rounded-xl border border-slate-100 bg-slate-50/50 p-3 text-xs text-slate-600">
                                {{-- Waktu --}}
                                <div class="flex items-start gap-2">
                                    <x-icon name="agenda" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" />
                                    <span class="font-medium text-slate-700">
                                        {{ $ruangRapat->tanggal_rapat?->translatedFormat('d M Y, H:i') ?: 'Waktu belum ditentukan' }}
                                        @if ($ruangRapat->tanggal_rapat) WIB @endif
                                    </span>
                                </div>

                                {{-- Lokasi --}}
                                @if ($ruangRapat->lokasi)
                                    <div class="flex items-start gap-2">
                                        <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span class="truncate text-slate-700">{{ $ruangRapat->lokasi }}</span>
                                    </div>
                                @endif

                                {{-- Link Virtual --}}
                                @if ($ruangRapat->link_virtual)
                                    <div class="flex items-center gap-2">
                                        <svg class="h-3.5 w-3.5 shrink-0 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        <a href="{{ $ruangRapat->link_virtual }}" target="_blank" rel="noopener" class="truncate font-semibold text-purple-600 hover:underline">
                                            Tautan Rapat Virtual ↗
                                        </a>
                                    </div>
                                @endif

                                {{-- PIC --}}
                                @if ($ruangRapat->pic_nama)
                                    <div class="flex items-start gap-2">
                                        <x-icon name="pegawai" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" />
                                        <span class="truncate text-slate-600">
                                            PIC: <strong class="text-slate-700">{{ $ruangRapat->pic_nama }}</strong>
                                            @if ($ruangRapat->pic_kontak)
                                                ({{ $ruangRapat->pic_kontak }})
                                            @endif
                                        </span>
                                    </div>
                                @endif

                                {{-- Dokumen & Berlaku --}}
                                <div class="flex items-center justify-between border-t border-slate-200/60 pt-2 text-[11px] text-slate-500">
                                    <span class="flex items-center gap-1.5 font-semibold text-slate-700">
                                        <x-icon name="dokumen" class="h-3.5 w-3.5 text-slate-400" />
                                        {{ $ruangRapat->dokumen_count }} dokumen
                                    </span>
                                    <span>
                                        s.d. {{ $ruangRapat->berlaku_sampai->translatedFormat('d M Y') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Footer Aksi Kartu --}}
                        <div class="mt-5 border-t border-slate-100 pt-4 space-y-2">
                            <div class="flex items-center gap-2">
                                <button type="button"
                                    data-salin="{{ $tautanPublik }}"
                                    class="btn-salin-kartu inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-purple-300 hover:bg-slate-50 hover:text-purple-600 active:scale-95">
                                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span class="label-salin-teks">Salin Tautan</span>
                                </button>

                                <a href="{{ $tautanPublik }}" target="_blank" rel="noopener"
                                    title="Buka tampilan tamu"
                                    class="grid h-8 w-8 place-items-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-purple-300 hover:text-purple-600 hover:bg-purple-50 active:scale-95">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            </div>

                            <a href="{{ route('ruang-rapat.show', $ruangRapat) }}"
                                class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-purple-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm shadow-purple-500/25 transition hover:bg-purple-700 active:scale-95">
                                <span>{{ ($u->isAdmin() || $u->isBidang()) ? 'Kelola Dokumen & Ruang Rapat' : 'Lihat Informasi & Dokumen' }}</span>
                                <x-icon name="chevron" class="h-3.5 w-3.5 rotate-180" />
                            </a>
                        </div>
                    </article>
                @endforeach

                {{-- Action Card Dotted (Penyeimbang Grid jika dokumen sedikit agar tidak bolong melompong) --}}
                @if (($u->isAdmin() || $u->isBidang()) && $ruangRapats->count() < 3 && empty($q) && $status === 'semua')
                    <a href="{{ route('ruang-rapat.create') }}"
                        class="group flex min-h-[320px] flex-col items-center justify-center rounded-2xl border-2 border-dashed border-purple-200/80 bg-gradient-to-b from-purple-50/30 to-slate-50/50 p-6 text-center transition-all duration-200 hover:border-purple-400 hover:bg-purple-50/60 hover:shadow-md">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl border border-purple-200 bg-white text-purple-600 shadow-sm transition group-hover:scale-110 group-hover:bg-purple-600 group-hover:text-white">
                            <x-icon name="plus" class="h-6 w-6" />
                        </span>
                        <h3 class="mt-3.5 text-sm font-extrabold text-slate-800 group-hover:text-purple-700">Buat Ruang Rapat Baru</h3>
                        <p class="mt-1 max-w-[220px] text-xs text-slate-500 leading-relaxed">
                            Siapkan paket dokumen publik untuk agenda rapat kedinasan berikutnya.
                        </p>
                        <span class="mt-4 inline-flex items-center gap-1 rounded-xl bg-white border border-purple-200 px-3 py-1.5 text-xs font-bold text-purple-600 shadow-xs transition group-hover:border-purple-300">
                            + Tambah Paket
                        </span>
                    </a>
                @endif
            </div>

            <!-- 4B. TAMPILAN LIST / TABEL ARSIP (Sangat rapi mengisi layar secara penuh) -->
            <div id="container-list-view" class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-slate-100 bg-slate-50/80 text-[10.5px] font-extrabold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="py-3.5 pl-5 pr-3">Ruang Rapat</th>
                                <th class="px-3 py-3.5">Bidang</th>
                                <th class="px-3 py-3.5">Jadwal & Lokasi</th>
                                <th class="px-3 py-3.5">PIC</th>
                                <th class="px-3 py-3.5 text-center">Dokumen</th>
                                <th class="px-3 py-3.5">Status & Akses</th>
                                <th class="py-3.5 pl-3 pr-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach ($ruangRapats as $ruangRapat)
                                @php
                                    $kedaluwarsa = $ruangRapat->sudahKedaluwarsa();
                                    $labelStatus = $ruangRapat->status === 'nonaktif' ? 'Nonaktif' : ($kedaluwarsa ? 'Kedaluwarsa' : 'Aktif');
                                    $badgeStatus = $labelStatus === 'Aktif'
                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                        : ($labelStatus === 'Kedaluwarsa' ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-slate-200 bg-slate-100 text-slate-600');
                                    $tautanPublik = route('rapat.buka', ['token' => $ruangRapat->token]);
                                @endphp
                                <tr class="transition hover:bg-purple-50/30">
                                    <td class="py-4 pl-5 pr-3">
                                        <div class="flex items-center gap-3">
                                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-purple-50 text-purple-600">
                                                <x-icon name="agenda" class="h-4 w-4" />
                                            </span>
                                            <div class="min-w-0">
                                                <a href="{{ route('ruang-rapat.show', $ruangRapat) }}" class="font-bold text-slate-900 hover:text-purple-600 transition block truncate max-w-xs md:max-w-sm">
                                                    {{ $ruangRapat->judul }}
                                                </a>
                                                @if ($ruangRapat->deskripsi)
                                                    <p class="mt-0.5 text-[11px] text-slate-400 truncate max-w-xs">{{ $ruangRapat->deskripsi }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap">
                                        <span class="inline-block rounded-md border border-purple-100 bg-purple-50/70 px-2 py-0.5 text-[11px] font-semibold text-purple-700">
                                            {{ $ruangRapat->bidang?->nama ?: 'Lintas Bidang' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap">
                                        <p class="font-semibold text-slate-800">
                                            {{ $ruangRapat->tanggal_rapat?->translatedFormat('d M Y, H:i') ?: 'Waktu belum ditentukan' }}
                                        </p>
                                        <p class="mt-0.5 text-[11px] text-slate-400">
                                            {{ $ruangRapat->lokasi ?: 'Lokasi belum ditentukan' }}
                                        </p>
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap">
                                        <p class="font-medium text-slate-800">{{ $ruangRapat->pic_nama ?: '—' }}</p>
                                        @if ($ruangRapat->pic_kontak)
                                            <p class="text-[11px] text-slate-400">{{ $ruangRapat->pic_kontak }}</p>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4 text-center whitespace-nowrap">
                                        <span class="rounded-lg bg-slate-100 px-2 py-1 font-bold text-slate-700">
                                            {{ $ruangRapat->dokumen_count }} berkas
                                        </span>
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-extrabold {{ $badgeStatus }}">
                                                @if ($labelStatus === 'Aktif')
                                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                @endif
                                                {{ $labelStatus }}
                                            </span>
                                            <span class="rounded-md border border-slate-200 px-1.5 py-0.5 text-[10px] font-bold text-slate-600">
                                                {{ $ruangRapat->pin_hash ? '🔒 PIN' : '🔓 Bebas PIN' }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-[10px] text-slate-400">
                                            s.d. {{ $ruangRapat->berlaku_sampai->translatedFormat('d M Y') }}
                                        </p>
                                    </td>
                                    <td class="py-4 pl-3 pr-5 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button type="button" data-salin="{{ $tautanPublik }}"
                                                class="btn-salin-kartu inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-bold text-slate-600 shadow-xs transition hover:border-purple-300 hover:text-purple-600">
                                                <span class="label-salin-teks">Salin</span>
                                            </button>
                                            <a href="{{ $tautanPublik }}" target="_blank" rel="noopener"
                                                title="Buka tampilan tamu"
                                                class="rounded-lg border border-slate-200 bg-white p-1.5 text-slate-500 shadow-xs transition hover:border-purple-300 hover:text-purple-600">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                            <a href="{{ route('ruang-rapat.show', $ruangRapat) }}"
                                                class="rounded-lg bg-purple-600 px-3 py-1.5 text-[11px] font-bold text-white transition hover:bg-purple-700 shadow-xs">
                                                {{ ($u->isAdmin() || $u->isBidang()) ? 'Kelola' : 'Lihat' }}
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6">
                {{ $ruangRapats->links() }}
            </div>

            {{-- 5. TIPS & PANDUAN PENGELOLAAN DOKUMEN TAMU (PENGISI RUANG & EDUKASI SOP) --}}
            <div class="mt-8 rounded-2xl border border-slate-200/80 bg-gradient-to-r from-slate-50 via-white to-purple-50/20 p-5 sm:p-6 shadow-xs">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <span class="text-[10.5px] font-extrabold uppercase tracking-widest text-purple-600">Panduan Operasional</span>
                        <h3 class="mt-1 text-sm font-extrabold text-slate-900">SOP Distribusi Dokumen Tamu Rapat</h3>
                        <p class="mt-0.5 text-xs text-slate-500">Standar keamanan dalam membagikan dokumen dinas kepada pihak eksternal.</p>
                    </div>
                    @if ($u->isAdmin() || $u->isBidang())
                        <a href="{{ route('ruang-rapat.create') }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-xs transition hover:border-purple-300 hover:text-purple-600">
                            <x-icon name="plus" class="h-3.5 w-3.5" /> Buat Paket Baru
                        </a>
                    @endif
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-3 border-t border-slate-100 pt-4 text-xs text-slate-600">
                    <div class="flex items-start gap-2.5">
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-lg bg-purple-100 text-purple-700 text-xs font-bold">1</span>
                        <div>
                            <p class="font-bold text-slate-800">Hanya Dokumen Publik</p>
                            <p class="mt-0.5 text-[11px] text-slate-500 leading-relaxed">Dokumen rahasia atau privat bidang secara otomatis tidak dapat dipilih untuk tautan tamu.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-lg bg-purple-100 text-purple-700 text-xs font-bold">2</span>
                        <div>
                            <p class="font-bold text-slate-800">Proteksi PIN Tamu</p>
                            <p class="mt-0.5 text-[11px] text-slate-500 leading-relaxed">Gunakan PIN opsional saat membuat rapat penting agar hanya pihak yang diundang yang dapat membuka.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-lg bg-purple-100 text-purple-700 text-xs font-bold">3</span>
                        <div>
                            <p class="font-bold text-slate-800">Kedaluwarsa Otomatis</p>
                            <p class="mt-0.5 text-[11px] text-slate-500 leading-relaxed">Tautan langsung nonaktif setelah tanggal masa berlaku habis, mencegah penyalahgunaan berkas.</p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="clean-card overflow-hidden p-8">
                @if ($q || $status !== 'semua' || $idBidang)
                    <div class="text-center py-6">
                        <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-purple-50 text-purple-600">
                            <x-icon name="search" class="h-6 w-6" />
                        </div>
                        <h3 class="text-base font-extrabold text-slate-900">Ruang Rapat tidak ditemukan</h3>
                        <p class="mt-1 text-xs text-slate-500">Tidak ada paket ruang rapat yang cocok dengan filter atau kata kunci yang dipilih.</p>
                        <a href="{{ route('ruang-rapat.index') }}"
                            class="mt-4 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-purple-600 shadow-sm transition hover:bg-purple-50">
                            Reset Semua Filter
                        </a>
                    </div>
                @else
                    <x-empty icon="agenda" title="Belum ada Ruang Rapat" desc="Paket berisi dokumen publik untuk dibagikan secara aman kepada peserta rapat atau tamu luar.">
                        @if ($u->isAdmin() || $u->isBidang())
                            <a href="{{ route('ruang-rapat.create') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-purple-700 hover:to-indigo-700">
                                <x-icon name="plus" class="h-4 w-4" /> Buat Ruang Rapat Baru
                            </a>
                        @endif
                    </x-empty>
                @endif
            </div>
        @endif
    </div>

    {{-- Script Salin Tautan & View Switcher --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Salin Tautan
            document.querySelectorAll('.btn-salin-kartu').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const url = this.getAttribute('data-salin');
                    const label = this.querySelector('.label-salin-teks');
                    const origText = label ? label.textContent : '';

                    try {
                        await navigator.clipboard.writeText(url);
                    } catch (e) {
                        const tempInput = document.createElement('input');
                        tempInput.value = url;
                        document.body.appendChild(tempInput);
                        tempInput.select();
                        document.execCommand('copy');
                        document.body.removeChild(tempInput);
                    }

                    if (label) label.textContent = 'Tersalin ✓';
                    this.classList.add('border-emerald-300', 'bg-emerald-50', 'text-emerald-700');

                    setTimeout(() => {
                        if (label) label.textContent = origText;
                        this.classList.remove('border-emerald-300', 'bg-emerald-50', 'text-emerald-700');
                    }, 1800);
                });
            });

            // 2. View Mode Toggle (Grid vs List/Tabel)
            const btnGrid = document.getElementById('btn-view-grid');
            const btnList = document.getElementById('btn-view-list');
            const containerGrid = document.getElementById('container-grid-view');
            const containerList = document.getElementById('container-list-view');

            if (btnGrid && btnList && containerGrid && containerList) {
                function setViewMode(mode) {
                    if (mode === 'list') {
                        containerGrid.classList.add('hidden');
                        containerList.classList.remove('hidden');
                        btnList.classList.add('bg-purple-50', 'text-purple-600', 'font-bold');
                        btnGrid.classList.remove('bg-purple-50', 'text-purple-600', 'font-bold');
                        localStorage.setItem('ruang_rapat_view', 'list');
                    } else {
                        containerList.classList.add('hidden');
                        containerGrid.classList.remove('hidden');
                        btnGrid.classList.add('bg-purple-50', 'text-purple-600', 'font-bold');
                        btnList.classList.remove('bg-purple-50', 'text-purple-600', 'font-bold');
                        localStorage.setItem('ruang_rapat_view', 'grid');
                    }
                }

                btnGrid.addEventListener('click', () => setViewMode('grid'));
                btnList.addEventListener('click', () => setViewMode('list'));

                // Baca preferensi tersimpan
                const savedView = localStorage.getItem('ruang_rapat_view') || 'grid';
                setViewMode(savedView);
            }
        });
    </script>
@endsection


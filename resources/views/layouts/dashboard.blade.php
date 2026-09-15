<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · SIPARDIS</title>
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏛️</text></svg>">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>document.documentElement.classList.toggle('sb-pinned', localStorage.getItem('sb-pinned') === '1'); if (window.innerWidth >= 768 && window.innerWidth < 1024) document.documentElement.classList.add('sb-tablet');</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
@php
    $u = auth()->user();
    $r = fn(string ...$p) => collect($p)->some(fn($x) => request()->routeIs($x));
    // lencana = nama bidang orangnya (bukan teks "Bidang" generik); kata "BIDANG/BAGIAN" di depan dibuang biar ringkas
    $bidangBadge = $u->bidang?->nama
        ? \Illuminate\Support\Str::limit(trim(preg_replace('/^(BIDANG|BAGIAN)\s+/iu', '', $u->bidang->nama)), 26, '…')
        : null;
    $belumTertaut = !$u->isAdmin() && !$u->bidang;
@endphp

<body class="text-slate-800 antialiased relative bg-slate-50">

    <div id="app" class="min-h-screen relative z-10">

        <!-- ============ SIDEBAR ============ -->
        <aside id="sidebar"
            class="fixed inset-y-0 left-0 z-50 flex flex-col bg-white border-r border-slate-100 shadow-xl shadow-purple-900/5">
            <div class="flex items-center gap-2.5 px-4 h-16 border-b border-slate-100 shrink-0">
                <img src="{{ asset('images/logo-dispar.jpg') }}" alt="Logo SIPARDIS"
                    class="h-9 w-9 shrink-0 rounded-xl object-cover shadow" />
                <div class="sb-hide leading-tight min-w-0">
                    <p class="font-bold text-xl tracking-tight truncate text-purple-700">SIPARDIS</p>
                    <p class="text-[10.5px] text-slate-500 truncate">Dispar Kab. Ciamis</p>
                </div>
                <button data-sb-toggle title="Buka menu" id="sb-collapse-btn"
                    class="ml-auto h-9 w-9 shrink-0 grid place-items-center rounded-lg border border-slate-200 bg-slate-50 text-slate-500 hover:bg-purple-50 hover:border-purple-200 hover:text-purple-600 active:scale-90 transition lg:hidden"><x-icon
                        name="chevron" class="h-4 w-4" /></button>
                <button id="sb-pin-btn" title="Pin sidebar"
                    class="ml-auto hidden lg:grid h-8 w-8 place-items-center rounded-lg border border-slate-200 bg-slate-50 text-slate-400 hover:bg-purple-50 hover:border-purple-200 hover:text-purple-600 active:scale-90 transition sb-chev"><svg
                        class="w-3.5 h-3.5 transition-transform duration-300 ease-out" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                    </svg></button>
            </div>

            <nav class="flex-1 overflow-y-auto thin-scroll px-2.5 py-3 space-y-4">
                <div>
                    <div class="sb-hide flex items-center px-2.5 pb-2">
                        <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Utama</p>
                        <div class="ml-3 flex-1 h-px bg-gradient-to-r from-slate-200 to-transparent"></div>
                    </div>
                    <div class="space-y-1.5">
                        <x-sb-link :href="route('dashboard')" label="Dashboard" icon="dashboard"
                            :active="$r('dashboard')" />
                        <x-sb-link :href="route('agenda.index')" label="Agenda & Pengingat" icon="agenda"
                            :active="$r('agenda.*')" />
                    </div>
                </div>

                <div>
                    <div class="sb-hide flex items-center px-2.5 pb-2">
                        <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Data &amp;
                            Kinerja</p>
                        <div class="ml-3 flex-1 h-px bg-gradient-to-r from-slate-200 to-transparent"></div>
                    </div>
                    <div class="space-y-1.5">
                        <x-sb-link :href="route('kinerja.publik')" label="Kegiatan & Kinerja" icon="kinerja"
                            :active="$r('kinerja.publik')" />
                        <x-sb-link :href="route('kinerja.capaian')" label="Capaian Kinerja" icon="statistik"
                            :active="$r('kinerja.capaian')" />
                        <x-sb-link :href="route('pad.index')" label="PAD (Retribusi)" icon="pad"
                            :active="$r('pad.*')" />
                        <x-sb-link :href="route('statistik.index')" label="Statistik Pariwisata" icon="statistik"
                            :active="$r('statistik.*')" />
                        <x-sb-link :href="route('pegawai.index')" label="Data Pegawai" icon="pegawai"
                            :active="$r('pegawai.*')" />
                    </div>
                </div>

                <div>
                    <div class="sb-hide flex items-center px-2.5 pb-2">
                        <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Kantor</p>
                        <div class="ml-3 flex-1 h-px bg-gradient-to-r from-slate-200 to-transparent"></div>
                    </div>
                    <div class="space-y-1.5">
                        <x-sb-link :href="route('dokumen.index')" label="Dokumen" icon="dokumen"
                            :active="$r('dokumen.*')" />
                        <x-sb-link :href="route('notifikasi.index')" label="Notifikasi" icon="bell"
                            :active="$r('notifikasi.*')" :badge="$u->notifikasi()->belum()->count() ?: null" />
                        <x-sb-link :href="route('bidang.index')" label="Bidang Dinas" icon="bidang"
                            :active="$r('bidang.*')" />
                    </div>
                </div>

                @if ($u->isAdmin())
                    <div>
                        <div class="sb-hide flex items-center px-2.5 pb-2">
                            <p class="text-[10px] font-extrabold uppercase tracking-widest text-amber-500/80">Admin — Data
                            </p>
                            <div class="ml-3 flex-1 h-px bg-gradient-to-r from-amber-200/50 to-transparent"></div>
                        </div>
                        <div class="space-y-1.5">
                            <x-sb-link :href="route('admin.dashboard')" label="Ringkasan Admin" icon="admin"
                                :active="$r('admin.dashboard')" />
                            <x-sb-link :href="route('admin.kinerja.index')" label="Kelola Kinerja" icon="kinerja"
                                :active="$r('admin.kinerja.*')" />
                            <x-sb-link :href="route('admin.capaian.index')" label="Kelola Capaian Kinerja" icon="statistik"
                                :active="$r('admin.capaian.*')" />
                            <x-sb-link :href="route('admin.statistik.index')" label="Kelola Statistik" icon="statistik"
                                :active="$r('admin.statistik.*')" />
                            <x-sb-link :href="route('admin.pad.index')" label="Kelola PAD" icon="pad"
                                :active="$r('admin.pad.*')" />
                            <x-sb-link :href="route('admin.prestasi.index')" label="Kelola Prestasi" icon="prestasi"
                                :active="$r('admin.prestasi.*')" />
                            <x-sb-link :href="route('admin.pegawai.index')" label="Kelola Pegawai" icon="pegawai"
                                :active="$r('admin.pegawai.*')" />
                            <x-sb-link :href="route('admin.agenda.index')" label="Kelola Agenda" icon="agenda"
                                :active="$r('admin.agenda.*')" />
                            <x-sb-link :href="route('admin.dokumen.index')" label="Kelola Dokumen" icon="dokumen"
                                :active="$r('admin.dokumen.*')" />
                        </div>
                    </div>
                    <div>
                        <div class="sb-hide flex items-center px-2.5 pb-2">
                            <p class="text-[10px] font-extrabold uppercase tracking-widest text-amber-500/80">Admin — Sistem
                            </p>
                            <div class="ml-3 flex-1 h-px bg-gradient-to-r from-amber-200/50 to-transparent"></div>
                        </div>
                        <div class="space-y-1.5">
                            <x-sb-link :href="route('admin.akun.index')" label="Akun & Akses" icon="akun"
                                :active="$r('admin.akun.*')" />
                            <x-sb-link :href="route('admin.log.index')" label="Log Aktivitas" icon="log"
                                :active="$r('admin.log.*')" />
                        </div>
                    </div>
                @endif
            </nav>

            <div class="border-t border-slate-100 p-2.5 shrink-0 flex items-center gap-1.5">
                <a href="{{ route('profil.index') }}" title="Buka profil saya"
                    class="group flex items-center gap-2.5 rounded-xl p-1.5 hover:bg-slate-50 transition flex-1 min-w-0">
                    <div class="relative shrink-0">
                        <span style="width: 36px; height: 36px; min-width: 36px; min-height: 36px;"
                            class="rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold shadow-md shadow-purple-500/20">{{ strtoupper(mb_substr($u->name, 0, 2)) }}</span>
                        <span
                            class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-white"></span>
                    </div>
                    <div class="sb-hide flex-1 min-w-0">
                        <p
                            class="text-[13px] font-semibold text-slate-800 group-hover:text-purple-700 transition truncate">
                            {{ $u->name }}</p>
                        <p class="text-[11px] text-slate-500 truncate">{{ $u->nip ?: 'Tanpa NIP' }} ·
                            {{ $u->isAdmin() ? 'Super Admin' : ucfirst($u->role) }}</p>
                        <p class="text-[10.5px] font-semibold {{ $belumTertaut ? 'text-rose-500' : 'text-purple-600' }} truncate"
                            title="{{ $u->bidang?->nama ?? ($u->isAdmin() ? 'Seluruh Dinas' : 'Belum tertaut ke bidang tertentu') }}">
                            <span class="opacity-70">Bidang:</span>
                            {{ $u->bidang?->nama ?? ($u->isAdmin() ? 'Seluruh Dinas' : 'belum tertaut — tetapkan di Admin ▸ Akun') }}
                        </p>
                    </div>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="shrink-0 sb-hide">@csrf
                    <button title="Keluar"
                        class="h-9 w-9 grid place-items-center rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600 active:scale-90 transition"><x-icon
                            name="logout" class="h-4 w-4" /></button>
                </form>
            </div>
        </aside>
        <div id="sb-overlay" class="fixed inset-0 z-40 bg-slate-900/50 hidden"></div>

        <!-- ============ TOPBAR ============ -->
        <header
            class="topbar fixed inset-x-0 top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200/60 bg-white/80 backdrop-blur-xl px-4 sm:px-6">
            <button data-sb-toggle
                class="lg:hidden h-9 w-9 grid place-items-center rounded-lg text-slate-500 hover:bg-slate-100 active:scale-90 transition"><x-icon
                    name="menu" /></button>
            <div class="min-w-0 flex-1 flex items-center gap-6">
                <div class="min-w-0 shrink-0 hidden sm:block">
                    <h1 class="font-bold text-[15px] leading-tight truncate">
                        @hasSection('header_title')
                            @yield('header_title')
                        @else
                            @yield('title', 'Dashboard')
                        @endif
                    </h1>
                    <p class="text-[11px] text-slate-400 truncate">
                        @yield('subtitle', now()->translatedFormat('l, d F Y'))</p>
                </div>

                <!-- Global Search (disembunyikan di halaman pencarian agar tidak dobel dengan form inline) -->
                @unless (request()->routeIs('search'))
                    {{-- Desktop/laptop: kolom pencarian cepat tampil langsung di topbar --}}
                    <div class="flex-1 max-w-md hidden md:block">
                        <form action="{{ route('search') }}" method="GET" class="relative w-full">
                            <span
                                class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input type="text" name="q" placeholder="Cari pegawai, agenda, dokumen..."
                                class="block w-full pl-10 pr-4 py-2 border border-transparent rounded-full bg-gray-100 text-[13px] placeholder-slate-400 focus:bg-white focus:border-purple-300 focus:ring-2 focus:ring-purple-500/20 outline-none transition-all cursor-text">
                        </form>
                    </div>
                @endunless
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                @unless (request()->routeIs('search'))
                    {{-- Pencarian cepat versi mobile/tablet: hanya tampil di bawah md (layar < 768px),
                         membuka panel #msearch. Sengaja memakai <a> ke route('search') sebagai
                         cadangan: bila berkas JS belum diperbarui, tautan ini tetap membuka halaman
                         pencarian (yang sudah punya kolom pencarian sendiri). --}}
                    <a href="{{ route('search') }}" id="ms-open" aria-label="Buka pencarian cepat"
                        aria-controls="msearch" aria-expanded="false"
                        class="md:hidden h-9 w-9 shrink-0 grid place-items-center rounded-xl border border-slate-200/90 bg-white text-slate-500 hover:bg-purple-50 hover:border-purple-200 hover:text-purple-600 active:scale-90 transition">
                        <x-icon name="search" class="h-5 w-5" />
                    </a>
                @endunless
                <span
                    class="hidden md:inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold max-w-[220px] {{ $u->isAdmin() ? 'bg-amber-50 text-amber-700 border-amber-200' : ($belumTertaut ? 'bg-orange-50 text-orange-700 border-orange-300' : ($u->isBidang() ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-slate-50 text-slate-600 border-slate-200')) }}"
                    title="{{ $u->isAdmin() ? 'Seluruh Dinas' : ($u->bidang?->nama ?? ('Belum tertaut ke bidang tertentu' . ($u->jabatan ? ' — jabatan: ' . $u->jabatan : '') . '. Tetapkan di Admin › Akun & Akses.')) }}">
                    {{ $u->isAdmin() ? 'Admin' : ($bidangBadge ?? 'Bidang: belum tertentu') }}
                </span>

                <div class="relative">
                    <button id="bell-btn"
                        class="relative h-9 w-9 grid place-items-center rounded-xl text-slate-500 hover:bg-slate-100 hover:text-slate-700 active:scale-90 transition">
                        <x-icon name="bell" />
                        <span id="bell-badge"
                            class="hidden absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-bold grid place-items-center ring-2 ring-white">0</span>
                    </button>
                    <div id="bell-panel"
                        class="bell-panel hidden fixed right-2 sm:right-4 top-[62px] w-[min(92vw,21rem)] max-h-[70vh] card overflow-hidden z-[90] shadow-xl">
                        <div class="flex items-center justify-between px-3.5 py-2.5 border-b border-slate-100">
                            <p class="text-sm font-semibold">Notifikasi</p>
                            <a href="{{ route('notifikasi.index') }}"
                                class="text-xs text-purple-600 hover:underline">Semua →</a>
                        </div>
                        <div id="bell-list"
                            class="max-h-72 overflow-y-auto thin-scroll divide-y divide-slate-100 text-sm"></div>
                        <div class="px-3.5 py-2 border-t border-slate-100"><a href="{{ route('notifikasi.bacaSemua') }}"
                                id="notif-baca" class="text-xs text-slate-500 hover:text-purple-600">Tandai semua
                                dibaca</a></div>
                    </div>
                </div>

                <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

                <a href="{{ route('profil.index') }}" title="Profil saya"
                    class="group shrink-0 inline-flex items-center gap-2 rounded-full border border-slate-200/90 bg-white hover:bg-slate-50 hover:border-purple-300 pl-1.5 pr-3 py-1 transition active:scale-95">
                    <div class="relative shrink-0 flex items-center justify-center">
                        <span style="width: 32px; height: 32px; min-width: 32px; min-height: 32px;"
                            class="rounded-full bg-gradient-to-tr from-purple-600 to-indigo-500 flex items-center justify-center text-white text-xs font-bold ring-2 ring-white shadow-sm shrink-0">{{ strtoupper(mb_substr($u->name, 0, 2)) }}</span>
                        <span
                            class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                    </div>
                    <div class="hidden sm:flex flex-col text-left leading-tight">
                        <span
                            class="text-[12.5px] font-semibold text-slate-800 group-hover:text-purple-700 transition truncate max-w-[125px]">{{ $u->name }}</span>
                        <span
                            class="text-[10px] text-slate-400 capitalize">{{ $u->isAdmin() ? 'Super Admin' : ($bidangBadge ?? 'Pegawai Dinas') }}</span>
                    </div>
                    <svg class="hidden sm:block w-3.5 h-3.5 text-slate-400 group-hover:text-slate-600 transition-transform group-hover:translate-y-0.5 ml-0.5"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </a>
            </div>
        </header>

        <!-- ============ PENCARIAN CEPAT (MOBILE) ============ -->
        {{-- Panel pencarian untuk layar < 768px (md:hidden), dibuka oleh tombol #ms-open.
             Hanya memakai route & nama field yang sudah ada (route('search'), name="q"). --}}
        @unless (request()->routeIs('search'))
            <div id="msearch" class="fixed inset-0 z-[80] hidden md:hidden" role="dialog" aria-modal="true"
                aria-label="Pencarian cepat">
                {{-- latar gelap: klik untuk menutup --}}
                <div data-ms-close class="absolute inset-0 bg-slate-900/50 backdrop-blur-[2px]"></div>

                <div
                    class="ms-sheet absolute inset-x-2.5 top-2.5 sm:inset-x-4 sm:top-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                    <form id="ms-form" action="{{ route('search') }}" method="GET" class="p-2.5">
                        <div
                            class="flex items-center gap-2 rounded-xl border border-transparent bg-slate-100 px-3 transition-all focus-within:border-purple-300 focus-within:bg-white focus-within:ring-2 focus-within:ring-purple-500/20">
                            <span class="pointer-events-none shrink-0 text-slate-400"><x-icon name="search"
                                    class="h-4 w-4" /></span>
                            <input id="ms-input" type="search" name="q" inputmode="search" enterkeyhint="search"
                                autocomplete="off" placeholder="Cari pegawai, agenda, dokumen..."
                                class="min-w-0 flex-1 border-0 bg-transparent px-0 py-2.5 text-[14px] placeholder-slate-400 outline-none focus:ring-0">
                            <button type="button" id="ms-clear" aria-label="Bersihkan kata kunci"
                                class="hidden h-6 w-6 shrink-0 grid place-items-center rounded-full bg-slate-200 text-slate-500 transition hover:bg-slate-300 active:scale-90">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2.4" stroke-linecap="round">
                                    <path d="M6 6l12 12M18 6L6 18" />
                                </svg>
                            </button>
                        </div>
                        <div class="mt-2.5 flex items-center gap-1.5">
                            <button type="submit"
                                class="flex-1 rounded-xl bg-purple-600 px-3 py-2 text-[13px] font-bold text-white transition hover:bg-purple-700 active:scale-[.98]">Cari</button>
                            <button type="button" data-ms-close
                                class="rounded-xl border border-slate-200 px-3.5 py-2 text-[13px] font-semibold text-slate-600 transition hover:bg-slate-50 active:scale-[.98]">Tutup</button>
                        </div>
                    </form>

                    <div class="border-t border-slate-100 px-3.5 py-3">
                        <div id="ms-recent-wrap" class="hidden">
                            <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Terakhir dicari
                            </p>
                            <div id="ms-recent-list" class="mt-2 flex flex-wrap gap-1.5"></div>
                        </div>
                        <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Coba kata kunci</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach (['Kepala Dinas', 'Rapat Koordinasi', 'SOP', 'Staf Ahli', 'Workshop'] as $contoh)
                                <a href="{{ route('search', ['q' => $contoh]) }}"
                                    class="inline-flex max-w-full items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-purple-300 hover:bg-purple-50 hover:text-purple-700">
                                    <span class="truncate">{{ $contoh }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- cetakan (template) chip riwayat pencarian — diisi oleh app.js dari localStorage --}}
                    <template id="ms-recent-tpl">
                        <a
                            class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-purple-300 hover:bg-purple-50 hover:text-purple-700">
                            <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 8v4l3 2" />
                                <circle cx="12" cy="12" r="9" />
                            </svg>
                            <span class="ms-recent-text truncate"></span>
                        </a>
                    </template>
                </div>
            </div>
        @endunless

        <!-- ============ KONTEN ============ -->
        <main class="pt-16 min-h-screen">
            <div class="px-4 sm:px-6 py-4 max-w-[1400px] mx-auto">
                @if (session('sukses') || session('gagal'))
                    <div data-flash
                        class="mb-4 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm {{ session('sukses') ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' }}">
                        <x-icon name="{{ session('sukses') ? 'kinerja' : 'alert' }}" class="h-5 w-5 shrink-0 mt-0.5" />
                        <p>{{ session('sukses') ?? session('gagal') }}</p>
                    </div>
                @endif
                @if ($errors->any())
                    <div
                        class="mb-4 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <x-icon name="alert" class="h-5 w-5 shrink-0 mt-0.5" />
                        <div class="min-w-0">
                            <p class="font-bold">Ada isian yang perlu diperbaiki:</p>
                            <ul class="mt-1 space-y-0.5 list-disc pl-4">
                                @foreach ($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>
    <div id="toast-zone"
        class="fixed bottom-4 right-4 z-[70] space-y-2 w-[min(92vw,360px)] pointer-events-none [&>*]:pointer-events-auto">
    </div>
</body>

</html>
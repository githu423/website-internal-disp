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
    {{-- Mode gelap diterapkan sebelum halaman tampil supaya tidak ada kedip putih --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('tema');
                if (t === 'gelap' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
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

            <!-- ============ SIDEBAR FOOTER (PROFIL & LOGOUT) ============ -->
            <div class="border-t border-slate-100 p-2 shrink-0 space-y-1 relative z-30 bg-white">
                <!-- Link Profil Pengguna -->
                <a href="{{ route('profil.index') }}" title="Profil saya — {{ $u->name }}" data-label="Profil saya"
                    class="nav-link group flex items-center gap-2.5 rounded-xl p-2 hover:bg-purple-50 transition min-w-0 {{ $r('profil.*') ? 'is-active bg-purple-50' : '' }}">
                    <div class="relative shrink-0 flex items-center justify-center">
                        <span style="width: 32px; height: 32px; min-width: 32px; min-height: 32px;"
                            class="rounded-xl bg-gradient-to-br from-purple-600 to-indigo-600 flex items-center justify-center text-white text-xs font-bold shadow-sm shadow-purple-500/20">{{ strtoupper(mb_substr($u->name, 0, 2)) }}</span>
                        <span
                            class="absolute -bottom-0.5 -right-0.5 h-2 w-2 rounded-full bg-emerald-400 ring-2 ring-white"></span>
                    </div>
                    <div class="sb-hide flex-1 min-w-0 leading-tight">
                        <p class="text-[13px] font-bold text-slate-800 group-hover:text-purple-700 transition truncate">
                            {{ $u->name }}
                        </p>
                        <p class="text-[10.5px] text-slate-400 truncate mt-0.5">
                            {{ $u->nip ?: 'Tanpa NIP' }} · {{ $u->isAdmin() ? 'Super Admin' : ucfirst($u->role) }}
                        </p>
                        <p class="text-[10px] font-extrabold text-purple-600 truncate mt-0.5">
                            Profil saya &rarr;
                        </p>
                    </div>
                </a>

                <!-- Tombol Logout Khusus Sidebar -->
                <form method="POST" action="{{ route('logout') }}" id="sidebar-logout-form" class="w-full">
                    @csrf
                    <button type="submit" id="sidebar-logout-btn" title="Keluar / Logout dari Sistem" data-label="Keluar (Logout)"
                        class="nav-link group w-full flex items-center gap-2.5 rounded-xl p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 active:scale-95 transition cursor-pointer border-transparent">
                        <span class="nav-ico shrink-0 flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 group-hover:text-rose-600 group-hover:bg-rose-100/50 transition">
                            <x-icon name="logout" class="h-4 w-4" />
                        </span>
                        <div class="sb-hide flex-1 min-w-0 text-left">
                            <span class="font-bold text-[13px] text-rose-600">Keluar (Logout)</span>
                        </div>
                    </button>
                </form>
            </div>
        </aside>
        <div id="sb-overlay" class="fixed inset-0 z-40 bg-slate-900/50 hidden"></div>

        <!-- ============ TOPBAR ============ -->
        <header
            class="topbar fixed inset-x-0 top-0 z-30 flex h-16 items-center justify-between gap-3 sm:gap-6 border-b border-slate-200/60 bg-white/90 backdrop-blur-xl px-3.5 sm:px-6">
            
            <!-- Sisi Kiri: Menu Toggle (Mobile/Tablet) + Judul Header (Desktop) -->
            <div class="flex items-center gap-3 shrink-0">
                <button data-sb-toggle title="Buka menu sidebar"
                    class="lg:hidden h-9 w-9 shrink-0 grid place-items-center rounded-xl text-slate-500 hover:bg-slate-100 hover:text-purple-600 active:scale-90 transition">
                    <x-icon name="menu" class="h-5 w-5" />
                </button>

                <div class="min-w-0 shrink-0 hidden lg:block">
                    <h1 class="font-bold text-[15px] leading-tight truncate text-slate-900">
                        @hasSection('header_title')
                            @yield('header_title')
                        @else
                            @yield('title', 'Dashboard')
                        @endif
                    </h1>
                    <p class="text-[11px] text-slate-400 truncate">
                        @yield('subtitle', now()->translatedFormat('l, d F Y'))
                    </p>
                </div>
            </div>

            <!-- Sisi Tengah: Global Search Box (Responsif di Seluruh Layar) -->
            @unless (request()->routeIs('search'))
                <div class="flex-1 min-w-0 max-w-md mx-2 sm:mx-4">
                    <form action="{{ route('search') }}" method="GET" class="relative w-full">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari pegawai, agenda, dokumen..."
                            class="block w-full pl-10 pr-4 py-2 border border-slate-200 rounded-full bg-slate-100/80 text-xs sm:text-[13px] text-slate-800 placeholder-slate-400 focus:bg-white focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 outline-none transition cursor-text shadow-sm">
                    </form>
                </div>
            @else
                <div class="flex-1 min-w-0"></div>
            @endunless

            <!-- Sisi Kanan: Role Badge, Notifikasi, Profile Avatar (Selalu Menempel Rapi di Kanan) -->
            <div class="flex items-center gap-2 sm:gap-3 shrink-0 ml-auto">
                <span
                    class="hidden md:inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold max-w-[200px] {{ $u->isAdmin() ? 'bg-amber-50 text-amber-700 border-amber-200' : ($belumTertaut ? 'bg-orange-50 text-orange-700 border-orange-300' : ($u->isBidang() ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-slate-50 text-slate-600 border-slate-200')) }}"
                    title="{{ $u->isAdmin() ? 'Seluruh Dinas' : ($u->bidang?->nama ?? ('Belum tertaut ke bidang tertentu' . ($u->jabatan ? ' — jabatan: ' . $u->jabatan : '') . '. Tetapkan di Admin › Akun & Akses.')) }}">
                    {{ $u->isAdmin() ? 'Admin' : ($bidangBadge ?? 'Bidang: belum tertentu') }}
                </span>

                <!-- Tombol Mode Gelap / Terang -->
                <button id="tema-btn" type="button" title="Ganti mode gelap / terang"
                    aria-label="Ganti mode gelap atau terang"
                    class="h-9 w-9 grid place-items-center rounded-xl text-slate-500 hover:bg-slate-100 hover:text-purple-600 active:scale-90 transition">
                    <svg data-tema-ikon="terang" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
                    </svg>
                    <svg data-tema-ikon="gelap" class="hidden h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 3v1.5m0 15V21m9-9h-1.5m-15 0H3m15.36-6.36l-1.06 1.06M6.7 17.3l-1.06 1.06m12.72 0l-1.06-1.06M6.7 6.7L5.64 5.64M16.5 12a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                    </svg>
                </button>

                <!-- Bell Notification -->
                <div class="relative">
                    <button id="bell-btn" title="Notifikasi"
                        class="relative h-9 w-9 grid place-items-center rounded-xl text-slate-500 hover:bg-slate-100 hover:text-purple-600 active:scale-90 transition">
                        <x-icon name="bell" class="h-4 w-4" />
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

                <!-- User Avatar / Profile Dropdown -->
                <div class="relative" id="user-menu-root">
                    <button id="user-menu-btn" title="Menu Pengguna"
                        class="group shrink-0 inline-flex items-center gap-2 rounded-full border border-slate-200/90 bg-white hover:bg-slate-50 hover:border-purple-300 p-0.5 sm:pl-1.5 sm:pr-3 sm:py-1 transition active:scale-95 cursor-pointer">
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
                    </button>

                    <!-- Dropdown Panel -->
                    <div id="user-menu-panel" class="hidden absolute right-0 top-[52px] w-56 bg-white border border-slate-200 rounded-2xl shadow-xl py-1.5 z-[90] text-xs">
                        <div class="px-3.5 py-2.5 border-b border-slate-100">
                            <p class="font-extrabold text-slate-900 truncate">{{ $u->name }}</p>
                            <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ $u->nip ?: 'Tanpa NIP' }} · {{ $u->isAdmin() ? 'Super Admin' : ucfirst($u->role) }}</p>
                        </div>
                        <a href="{{ route('profil.index') }}" class="flex items-center gap-2.5 px-3.5 py-2 text-slate-700 hover:bg-purple-50 hover:text-purple-700 font-semibold transition">
                            <span>👤</span> Profil Saya
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 mt-1 pt-1">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-2.5 px-3.5 py-2 text-rose-600 hover:bg-rose-50 font-semibold transition text-left cursor-pointer">
                                <span>🚪</span> Keluar (Logout)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

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

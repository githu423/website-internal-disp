<!DOCTYPE html>
<html lang="id" @if (request()->cookie('sb') === '1') data-sidebar="collapsed" @endif>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · SIPARDIS</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏛️</text></svg>">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>document.documentElement.dataset.sidebar = localStorage.getItem('sb-collapsed') === '1' ? 'collapsed' : '';</script>
</head>
@php
    $u = auth()->user();
    $r = fn (string ...$p) => collect($p)->some(fn ($x) => request()->routeIs($x));
    // lencana = nama bidang orangnya (bukan teks "Bidang" generik); kata "BIDANG/BAGIAN" di depan dibuang biar ringkas
    $bidangBadge = $u->bidang?->nama
        ? \Illuminate\Support\Str::limit(trim(preg_replace('/^(BIDANG|BAGIAN)\s+/iu', '', $u->bidang->nama)), 26, '…')
        : null;
    $belumTertaut = !$u->isAdmin() && !$u->bidang;
@endphp
<body class="bg-slate-100 text-slate-800 antialiased">
<div id="app" class="min-h-screen">

    <!-- ============ SIDEBAR ============ -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 flex flex-col bg-slate-900 text-slate-100">
        <div class="flex items-center gap-2.5 px-4 h-16 border-b border-white/10 shrink-0">
            <img src="{{ asset('images/logo-dispar.jpg') }}" alt="Logo SIPARDIS" class="h-9 w-9 shrink-0 rounded-xl object-cover shadow" />
            <div class="sb-hide leading-tight min-w-0">
                <p class="font-bold text-[15px] tracking-tight truncate">SIPARDIS</p>
                <p class="text-[10.5px] text-slate-400 truncate">Dispar Kab. Ciamis</p>
            </div>
            <button data-sb-toggle title="Tutup / buka menu" id="sb-collapse-btn"
                class="ml-auto h-8 w-8 shrink-0 grid place-items-center rounded-lg text-slate-300 hover:bg-white/10 hover:text-white active:scale-90 transition lg:hidden"><x-icon name="chevron" class="h-4 w-4" /></button>
            <button data-sb-toggle title="Tutup / buka menu"
                class="ml-auto hidden lg:grid h-8 w-8 place-items-center rounded-lg text-slate-300 hover:bg-white/10 hover:text-white active:scale-90 transition sb-chev"><x-icon name="chevron" class="h-4 w-4 transition-transform duration-200" /></button>
        </div>

        <nav class="flex-1 overflow-y-auto thin-scroll px-2.5 py-3 space-y-4">
            <div>
                <p class="sb-hide px-2.5 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">Utama</p>
                <div class="space-y-0.5">
                    <x-sb-link :href="route('dashboard')" label="Dashboard" icon="dashboard" :active="$r('dashboard')" />
                    <x-sb-link :href="route('agenda.index')" label="Agenda & Pengingat" icon="agenda" :active="$r('agenda.*')" />
                </div>
            </div>

            <div>
                <p class="sb-hide px-2.5 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">Data &amp; Kinerja</p>
                <div class="space-y-0.5">
                    <x-sb-link :href="route('kinerja.publik')" label="Kegiatan & Kinerja" icon="kinerja" :active="$r('kinerja.publik')" />
                    <x-sb-link :href="route('kinerja.capaian')" label="Capaian Kinerja" icon="statistik" :active="$r('kinerja.capaian')" />
                    <x-sb-link :href="route('pad.index')" label="PAD (Retribusi)" icon="pad" :active="$r('pad.*')" />
                    <x-sb-link :href="route('statistik.index')" label="Statistik Pariwisata" icon="statistik" :active="$r('statistik.*')" />
                    <x-sb-link :href="route('pegawai.index')" label="Data Pegawai" icon="pegawai" :active="$r('pegawai.*')" />
                </div>
            </div>

            <div>
                <p class="sb-hide px-2.5 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">Kantor</p>
                <div class="space-y-0.5">
                    <x-sb-link :href="route('dokumen.index')" label="Dokumen" icon="dokumen" :active="$r('dokumen.*')" />
                    <x-sb-link :href="route('notifikasi.index')" label="Notifikasi" icon="bell" :active="$r('notifikasi.*')" :badge="$u->notifikasi()->belum()->count() ?: null" />
                    <x-sb-link :href="route('bidang.index')" label="Bidang Dinas" icon="bidang" :active="$r('bidang.*')" />
                </div>
            </div>

            @if ($u->isAdmin())
            <div>
                <p class="sb-hide px-2.5 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-amber-400/80">Admin</p>
                <div class="space-y-0.5">
                    <x-sb-link :href="route('admin.dashboard')" label="Ringkasan Admin" icon="admin" :active="$r('admin.dashboard')" />
                    <x-sb-link :href="route('admin.kinerja.index')" label="Kelola Kinerja" icon="kinerja" :active="$r('admin.kinerja.*')" />
                    <x-sb-link :href="route('admin.capaian.index')" label="Kelola Capaian Kinerja" icon="statistik" :active="$r('admin.capaian.*')" />
                    <x-sb-link :href="route('admin.statistik.index')" label="Kelola Statistik" icon="statistik" :active="$r('admin.statistik.*')" />
                    <x-sb-link :href="route('admin.pad.index')" label="Kelola PAD" icon="pad" :active="$r('admin.pad.*')" />
                    <x-sb-link :href="route('admin.pegawai.index')" label="Kelola Pegawai" icon="pegawai" :active="$r('admin.pegawai.*')" />
                    <x-sb-link :href="route('admin.agenda.index')" label="Kelola Agenda" icon="agenda" :active="$r('admin.agenda.*')" />
                    <x-sb-link :href="route('admin.dokumen.index')" label="Kelola Dokumen" icon="dokumen" :active="$r('admin.dokumen.*')" />
                    <x-sb-link :href="route('admin.akun.index')" label="Akun & Akses" icon="akun" :active="$r('admin.akun.*')" />
                    <x-sb-link :href="route('admin.prestasi.index')" label="Kelola Prestasi" icon="kinerja" :active="$r('admin.prestasi.*')" />
                    <x-sb-link :href="route('admin.log.index')" label="Log Aktivitas" icon="log" :active="$r('admin.log.*')" />
                </div>
            </div>
            @endif
        </nav>

        <div class="border-t border-white/10 p-2.5 shrink-0">
            <a href="{{ route('profil.index') }}" title="Buka profil saya" class="group flex items-center gap-2.5 rounded-xl p-1.5 hover:bg-white/10 transition">
                <div class="relative shrink-0">
                    <span style="width: 36px; height: 36px; min-width: 36px; min-height: 36px;" class="rounded-xl bg-gradient-to-br from-teal-500 to-emerald-600 flex items-center justify-center text-white text-xs font-bold shadow-md ring-1 ring-white/20">{{ strtoupper(mb_substr($u->name, 0, 2)) }}</span>
                    <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-slate-900" title="Akun Aktif"></span>
                </div>
                <div class="sb-hide flex-1 min-w-0">
                    <p class="text-[13px] font-semibold text-slate-100 group-hover:text-teal-300 transition truncate">{{ $u->name }}</p>
                    <p class="text-[11px] text-slate-400 truncate">{{ $u->nip ?: 'Tanpa NIP' }} · {{ $u->isAdmin() ? 'Super Admin' : ucfirst($u->role) }}</p>
                    <p class="text-[10.5px] font-semibold {{ $belumTertaut ? 'text-orange-300' : 'text-teal-300/90' }} truncate" title="{{ $u->bidang?->nama ?? ($u->isAdmin() ? 'Seluruh Dinas' : 'Belum tertaut ke bidang tertentu') }}">
                        <span class="opacity-70">Bidang:</span> {{ $u->bidang?->nama ?? ($u->isAdmin() ? 'Seluruh Dinas' : 'belum tertaut — tetapkan di Admin ▸ Akun') }}
                    </p>
                </div>
            </a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button title="Keluar" class="h-8 w-8 shrink-0 grid place-items-center rounded-lg text-slate-300 hover:bg-rose-500/20 hover:text-rose-300 active:scale-90 transition"><x-icon name="logout" class="h-4 w-4" /></button>
            </form>
        </div>
    </aside>
    <div id="sb-overlay" class="fixed inset-0 z-40 bg-slate-900/50 hidden"></div>

    <!-- ============ TOPBAR ============ -->
    <header class="topbar fixed inset-x-0 top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 backdrop-blur px-4 sm:px-6">
        <button data-sb-toggle class="lg:hidden h-9 w-9 grid place-items-center rounded-lg text-slate-500 hover:bg-slate-100 active:scale-90 transition"><x-icon name="menu" /></button>
        <div class="min-w-0 flex-1">
            <h1 class="font-bold text-[15px] leading-tight truncate">@yield('title', 'Dashboard')</h1>
            <p class="text-[11px] text-slate-400 truncate">@yield('subtitle', now()->translatedFormat('l, d F Y'))</p>
        </div>

        <div class="flex items-center gap-2 sm:gap-3">
            <span class="hidden md:inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold max-w-[220px] {{ $u->isAdmin() ? 'bg-amber-50 text-amber-700 border-amber-200' : ($belumTertaut ? 'bg-orange-50 text-orange-700 border-orange-300' : ($u->isBidang() ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-slate-50 text-slate-600 border-slate-200')) }}" title="{{ $u->isAdmin() ? 'Seluruh Dinas' : ($u->bidang?->nama ?? ('Belum tertaut ke bidang tertentu' . ($u->jabatan ? ' — jabatan: ' . $u->jabatan : '') . '. Tetapkan di Admin › Akun & Akses.')) }}">
                {{ $u->isAdmin() ? 'Admin' : ($bidangBadge ?? 'Bidang: belum tertentu') }}
            </span>

            <div class="relative">
                <button id="bell-btn" class="relative h-9 w-9 grid place-items-center rounded-xl text-slate-500 hover:bg-slate-100 hover:text-slate-700 active:scale-90 transition">
                    <x-icon name="bell" />
                    <span id="bell-badge" class="hidden absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-500 text-white text-[10px] font-bold grid place-items-center">0</span>
                </button>
                <div id="bell-panel" class="bell-panel hidden fixed right-2 sm:right-4 top-[62px] w-[min(92vw,21rem)] max-h-[70vh] card overflow-hidden z-[90] shadow-xl">
                    <div class="flex items-center justify-between px-3.5 py-2.5 border-b border-slate-100">
                        <p class="text-sm font-semibold">Notifikasi</p>
                        <a href="{{ route('notifikasi.index') }}" class="text-xs text-teal-600 hover:underline">Semua →</a>
                    </div>
                    <div id="bell-list" class="max-h-72 overflow-y-auto thin-scroll divide-y divide-slate-100 text-sm"></div>
                    <div class="px-3.5 py-2 border-t border-slate-100"><a href="{{ route('notifikasi.bacaSemua') }}" id="notif-baca" class="text-xs text-slate-500 hover:text-teal-600">Tandai semua dibaca</a></div>
                </div>
            </div>

            <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

            <a href="{{ route('profil.index') }}" title="Profil saya" class="group shrink-0 inline-flex items-center gap-2 rounded-full border border-slate-200/90 bg-white hover:bg-slate-50 hover:border-teal-300 hover:shadow-sm pl-1.5 pr-3 py-1 transition active:scale-95">
                <div class="relative shrink-0 flex items-center justify-center">
                    <span style="width: 32px; height: 32px; min-width: 32px; min-height: 32px;" class="rounded-full bg-gradient-to-tr from-teal-600 to-emerald-500 flex items-center justify-center text-white text-xs font-bold ring-2 ring-white shadow-sm shrink-0">{{ strtoupper(mb_substr($u->name, 0, 2)) }}</span>
                    <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                </div>
                <div class="hidden sm:flex flex-col text-left leading-tight">
                    <span class="text-[12.5px] font-semibold text-slate-800 group-hover:text-teal-700 transition truncate max-w-[125px]">{{ $u->name }}</span>
                    <span class="text-[10px] text-slate-400 capitalize">{{ $u->isAdmin() ? 'Super Admin' : ($bidangBadge ?? 'Pegawai Dinas') }}</span>
                </div>
                <svg class="hidden sm:block w-3.5 h-3.5 text-slate-400 group-hover:text-slate-600 transition-transform group-hover:translate-y-0.5 ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
            </a>
        </div>
    </header>

    <!-- ============ KONTEN ============ -->
    <main class="pt-16 min-h-screen">
        <div class="px-4 sm:px-6 py-5 max-w-[1400px] mx-auto">
            @if (session('sukses') || session('gagal'))
                <div data-flash class="mb-4 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm {{ session('sukses') ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800' }}">
                    <x-icon name="{{ session('sukses') ? 'kinerja' : 'alert' }}" class="h-5 w-5 shrink-0 mt-0.5" />
                    <p>{{ session('sukses') ?? session('gagal') }}</p>
                </div>
            @endif
            @yield('content')
        </div>
    </main>
</div>
<div id="toast-zone" class="fixed bottom-4 right-4 z-[70] space-y-2 w-[min(92vw,360px)] pointer-events-none [&>*]:pointer-events-auto"></div>
</body>
</html>

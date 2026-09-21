@extends('layouts.dashboard')

@section('title', 'Agenda & Kegiatan')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Jadwal Rapat, Acara, & Agenda Kerja Kedinasan')

@section('content')
@php 
    $u = auth()->user(); 
    $dinas = $items->whereNull('id_bidang')->values();
    $perBidang = $items->whereNotNull('id_bidang')->groupBy(fn ($a) => $a->bidang?->nama ?? 'Bidang')->sortKeys();
    
    $todayYmd = now()->format('Y-m-d');
    $todayMonth = now()->format('Y-m');
    $isCurrentMonth = ($bulanIni === $todayMonth);
    $totalHariIni = $items->filter(fn($a) => $a->tanggal->format('Y-m-d') === $todayYmd)->count();
    $activeFilter = (request('filter') === 'hari-ini' || request('hari_ini') == '1') ? 'hari-ini' : 'semua';
@endphp

<div class="space-y-6">

    <!-- 1. Breadcrumbs Mikro -->
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Kantor</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Agenda &amp; Kegiatan</span>
    </nav>

    <!-- 2. Header Halaman & Navigasi Sortiran -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Agenda &amp; Pengingat Kegiatan</h1>
                <span id="agenda-badge-count" class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                    {{ $items->count() }} Kegiatan
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Kalender kegiatan resmi dinas dan program kerja lintas bidang.</p>
        </div>

        <!-- Bulan Switcher, Shortcut Sortiran Hari Ini & Tombol Admin -->
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Navigasi Bulan -->
            <div class="inline-flex items-center bg-white border border-slate-200 rounded-2xl p-1 shadow-sm">
                <a href="{{ route('agenda.index', ['bulan' => $bulanLalu]) }}" 
                   class="h-8 w-8 grid place-items-center rounded-xl text-slate-500 hover:text-purple-600 hover:bg-slate-50 active:scale-95 transition"
                   title="Bulan Sebelumnya">
                    <x-icon name="chevron" class="h-4 w-4" />
                </a>
                <span class="px-3 text-xs font-bold capitalize text-slate-800 whitespace-nowrap">
                    {{ \Carbon\Carbon::parse($bulanIni . '-01')->translatedFormat('F Y') }}
                </span>
                <a href="{{ route('agenda.index', ['bulan' => $bulanDepan]) }}" 
                   class="h-8 w-8 grid place-items-center rounded-xl text-slate-500 hover:text-purple-600 hover:bg-slate-50 active:scale-95 transition rotate-180"
                   title="Bulan Berikutnya">
                    <x-icon name="chevron" class="h-4 w-4" />
                </a>
            </div>

            <!-- Segmented Filter Shortcut: Semua Bulan Ini vs Hari Ini -->
            <div class="inline-flex items-center bg-white border border-slate-200 rounded-2xl p-1 shadow-sm" id="agenda-filter-container">
                <button type="button" 
                        data-filter-btn="semua"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $activeFilter === 'semua' ? 'bg-purple-600 text-white shadow-sm shadow-purple-500/20' : 'text-slate-600 hover:text-purple-600 hover:bg-slate-50' }}">
                    <span>Semua</span>
                    <span class="text-[11px] px-1.5 py-0.2 rounded-full {{ $activeFilter === 'semua' ? 'bg-purple-700 text-purple-100' : 'bg-slate-100 text-slate-600' }}">
                        {{ $items->count() }}
                    </span>
                </button>

                @if ($isCurrentMonth)
                    <button type="button" 
                            data-filter-btn="hari-ini"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $activeFilter === 'hari-ini' ? 'bg-purple-600 text-white shadow-sm shadow-purple-500/20' : 'text-slate-600 hover:text-purple-600 hover:bg-slate-50' }}">
                        <span>Hari Ini</span>
                        @if ($totalHariIni > 0)
                            <span class="text-[11px] px-1.5 py-0.2 rounded-full {{ $activeFilter === 'hari-ini' ? 'bg-purple-700 text-purple-100' : 'bg-purple-100 text-purple-700 font-extrabold' }}">
                                {{ $totalHariIni }}
                            </span>
                        @endif
                    </button>
                @else
                    <a href="{{ route('agenda.index', ['bulan' => $todayMonth, 'filter' => 'hari-ini']) }}" 
                       class="px-3 py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:text-purple-600 hover:bg-slate-50 transition flex items-center gap-1.5"
                       title="Lihat agenda hari ini">
                        <span>Hari Ini</span>
                    </a>
                @endif
            </div>

            @if($u->isAdmin())
            <a href="{{ route('admin.agenda.index') }}" 
               class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-xs md:text-sm font-semibold rounded-2xl shadow-sm shadow-purple-500/30 hover:bg-purple-700 hover:shadow-purple-600/40 active:scale-95 transition-all duration-150">
                <x-icon name="plus" class="h-4 w-4" />
                Kelola Agenda
            </a>
            @endif
        </div>
    </div>

    <!-- 3. Konten Utama: Daftar Kegiatan (Kiri) & Form / Terdekat (Kanan) -->
    <div class="grid lg:grid-cols-3 gap-5">
        <!-- Kolom Kiri: Agenda Dinas & Bidang -->
        <div class="lg:col-span-2 space-y-4">

            <!-- Banner Info Filter Aktif Hari Ini (Optional Notice) -->
            <div id="filter-today-notice" class="bg-purple-50/80 border border-purple-200/80 rounded-2xl p-3.5 flex items-center justify-between text-xs text-purple-800 {{ $activeFilter === 'hari-ini' ? '' : 'hidden' }}">
                <div class="flex items-center gap-2">
                    <span class="text-base">📅</span>
                    <span>Menampilkan agenda khusus hari ini: <b>{{ now()->translatedFormat('l, d F Y') }}</b></span>
                </div>
                <button type="button" data-filter-btn="semua" class="font-bold underline text-purple-700 hover:text-purple-900 cursor-pointer text-xs">
                    Tampilkan Semua Bulan Ini
                </button>
            </div>

            @if ($items->isEmpty())
                <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center shadow-sm">
                    <x-empty icon="agenda" title="Tidak ada agenda di bulan ini" desc="Agenda dinas dibuat oleh admin; agenda bidang dibuat melalui halaman profil bidang masing-masing." />
                </div>
            @endif

            <!-- Empty State Khusus Shortcut "Hari Ini" Saat Tidak Ada Kegiatan -->
            <div id="empty-today-state" class="bg-white border border-slate-200 rounded-2xl p-10 text-center shadow-sm {{ ($activeFilter === 'hari-ini' && $totalHariIni === 0) ? '' : 'hidden' }}">
                <div class="h-14 w-14 rounded-2xl bg-purple-50 text-purple-600 border border-purple-200/80 flex items-center justify-center mx-auto mb-3 shadow-sm shadow-purple-500/10">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="font-extrabold text-base text-slate-800">Tidak ada agenda untuk hari ini</h3>
                <p class="text-xs text-slate-500 mt-1.5 max-w-md mx-auto leading-relaxed">
                    Belum ada agenda dinas maupun kegiatan bidang yang dijadwalkan pada hari ini ({{ now()->translatedFormat('l, d F Y') }}).
                </p>
                <button type="button" data-filter-btn="semua" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-purple-600 text-white text-xs font-bold hover:bg-purple-700 active:scale-95 transition shadow-sm shadow-purple-500/20">
                    &larr; Lihat Semua Agenda Bulan Ini
                </button>
            </div>

            <!-- Bagian 1: Agenda Dinas (Umum) -->
            @if ($dinas->isNotEmpty())
                <div data-agenda-group="dinas" class="space-y-2.5">
                    <div class="flex items-center gap-3 pt-1 pb-1">
                        <x-bidang-icon dinas size="md" />
                        <div class="flex-1 min-w-0">
                            <h3 class="font-extrabold text-[15px] leading-tight text-slate-900">Agenda Dinas (Umum)</h3>
                            <p class="text-[11.5px] font-medium text-slate-500">
                                <span data-group-count>{{ $dinas->count() }}</span> kegiatan kedinasan
                            </p>
                        </div>
                    </div>
                    <div class="space-y-2.5" data-group-items>
                        @foreach ($dinas as $a)
                            @include('agenda._kartu', ['a' => $a])
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Bagian 2: Agenda Per Bidang -->
            @foreach ($perBidang as $nama => $grup)
                <div data-agenda-group="bidang-{{ $loop->index }}" class="space-y-2.5 pt-2">
                    <div class="flex items-center gap-3 pt-2 pb-1 border-t border-slate-200/60">
                        <x-bidang-icon :bidang="$grup->first()?->bidang" size="md" />
                        <div class="flex-1 min-w-0">
                            <h3 class="font-extrabold text-[15px] leading-tight text-slate-900">{{ $nama }}</h3>
                            <p class="text-[11.5px] font-medium text-slate-500">
                                <span data-group-count>{{ $grup->count() }}</span> kegiatan bidang
                            </p>
                        </div>
                    </div>
                    <div class="space-y-2.5" data-group-items>
                        @foreach ($grup as $a)
                            @include('agenda._kartu', ['a' => $a])
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Kolom Kanan: Tambah Agenda (Admin) & Pengingat 14 Hari -->
        <div class="space-y-4">
            @if ($u->isAdmin())
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                <h3 class="font-extrabold text-[15px] text-slate-900 mb-3.5 flex items-center gap-2.5">
                    <span class="h-8 w-8 rounded-xl bg-purple-50 border border-purple-100 text-purple-600 grid place-items-center">
                        <x-icon name="plus" class="h-4 w-4" />
                    </span>
                    <span>Tambah Agenda Baru</span>
                </h3>
                <form method="POST" action="{{ route('agenda.store') }}" class="space-y-3 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold uppercase tracking-wider text-slate-400 mb-1">Judul Kegiatan *</label>
                        <input name="judul" placeholder="Nama acara / agenda kegiatan" required 
                               class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block font-bold uppercase tracking-wider text-slate-400 mb-1">Tanggal *</label>
                            <input type="date" name="tanggal" value="{{ $isCurrentMonth ? $todayYmd : $bulanIni.'-15' }}" required 
                                   class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                        </div>
                        <div>
                            <label class="block font-bold uppercase tracking-wider text-slate-400 mb-1">Waktu</label>
                            <input type="time" name="jam_mulai" value="09:00" 
                                   class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold uppercase tracking-wider text-slate-400 mb-1">Lokasi</label>
                        <input name="lokasi" placeholder="mis. Aula Dinas Pariwisata" 
                               class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block font-bold uppercase tracking-wider text-slate-400 mb-1">Kategori</label>
                            <select name="kategori" class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:border-purple-400">
                                <option value="rapat">Rapat</option>
                                <option value="kegiatan">Kegiatan</option>
                                <option value="kunjungan">Kunjungan</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold uppercase tracking-wider text-slate-400 mb-1">Pelaksana</label>
                            <select name="id_bidang" class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:border-purple-400">
                                <option value="">Seluruh dinas</option>
                                @foreach (\App\Models\Bidang::orderBy('kode')->get() as $b)
                                    <option value="{{ $b->id }}">{{ $b->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block font-bold uppercase tracking-wider text-slate-400 mb-1">Catatan / Deskripsi</label>
                        <textarea name="deskripsi" rows="2" placeholder="Catatan tambahan (opsional)..." 
                                  class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                        <input type="checkbox" name="beri_tahu" value="1" class="rounded border-slate-300 text-purple-600 focus:ring-purple-500/20" checked> 
                        <span>Kirim notifikasi ke seluruh pegawai</span>
                    </label>
                    <button type="submit" class="w-full rounded-xl bg-purple-600 hover:bg-purple-700 active:scale-[.98] text-white font-bold py-2.5 transition shadow-sm shadow-purple-500/30">
                        Simpan Agenda
                    </button>
                </form>
            </div>
            @endif

            <!-- Card 14 Hari ke Depan -->
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                <h3 class="font-extrabold text-[15px] text-slate-900 mb-3 flex items-center gap-2">
                    <span>📅</span> Agenda 14 Hari ke Depan
                </h3>
                <div class="space-y-1">
                    @forelse ($terdekat as $a)
                        <div class="flex gap-3 py-2.5 {{ $loop->last ? '' : 'border-b border-slate-100' }}">
                            <span class="text-xs font-bold text-purple-700 whitespace-nowrap w-14 shrink-0">
                                {{ $a->tanggal->translatedFormat('d M') }}
                            </span>
                            <x-bidang-icon :bidang="$a->bidang" :dinas="!$a->bidang" size="sm" />
                            <div class="min-w-0 flex-1">
                                <p class="text-[13px] font-semibold text-slate-800 leading-snug truncate">{{ $a->judul }}</p>
                                <p class="text-[11px] text-slate-400 mt-0.5 truncate">
                                    {{ $a->jam_mulai ? substr((string)$a->jam_mulai, 0, 5).' WIB · ' : '' }}{{ $a->lokasi ?: 'Lokasi menyusul' }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-3 text-center">Tidak ada agenda dalam 14 hari ke depan.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Script Interaktif Sortiran Shortcut Hari Ini vs Semua -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const todayYmd = '{{ $todayYmd }}';
    const filterNotice = document.getElementById('filter-today-notice');
    const emptyTodayState = document.getElementById('empty-today-state');
    const badgeCount = document.getElementById('agenda-badge-count');
    const allCards = Array.from(document.querySelectorAll('[data-agenda-card]'));
    const allGroups = Array.from(document.querySelectorAll('[data-agenda-group]'));
    const filterBtns = document.querySelectorAll('[data-filter-btn]');

    function applyFilter(mode) {
        let visibleTotal = 0;

        // 1. Tampilkan / sembunyikan kartu
        allCards.forEach(card => {
            const isToday = card.dataset.tanggal === todayYmd;
            const shouldShow = (mode === 'semua') || (mode === 'hari-ini' && isToday);
            
            if (shouldShow) {
                card.classList.remove('hidden');
                visibleTotal++;
            } else {
                card.classList.add('hidden');
            }
        });

        // 2. Evaluasi visibilitas grup & update angka jumlah per grup
        allGroups.forEach(group => {
            const itemsInGroup = Array.from(group.querySelectorAll('[data-agenda-card]'));
            const visibleInGroup = itemsInGroup.filter(c => !c.classList.contains('hidden'));
            const countElem = group.querySelector('[data-group-count]');
            
            if (countElem) {
                countElem.textContent = visibleInGroup.length;
            }

            if (visibleInGroup.length > 0) {
                group.classList.remove('hidden');
            } else {
                group.classList.add('hidden');
            }
        });

        // 3. Tampilkan empty state jika mode hari ini dan tidak ada kegiatan
        if (mode === 'hari-ini' && visibleTotal === 0) {
            if (emptyTodayState) emptyTodayState.classList.remove('hidden');
        } else {
            if (emptyTodayState) emptyTodayState.classList.add('hidden');
        }

        // 4. Update banner notifikasi & badge total
        if (filterNotice) {
            if (mode === 'hari-ini') {
                filterNotice.classList.remove('hidden');
            } else {
                filterNotice.classList.add('hidden');
            }
        }

        if (badgeCount) {
            badgeCount.textContent = (mode === 'hari-ini' ? `${visibleTotal} Kegiatan Hari Ini` : `${visibleTotal} Kegiatan`);
        }

        // 5. Update gaya tombol filter
        filterBtns.forEach(btn => {
            const btnMode = btn.dataset.filterBtn;
            const badgeSpan = btn.querySelector('span:last-child');
            
            if (btnMode === mode) {
                btn.className = 'px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-purple-600 text-white shadow-sm shadow-purple-500/20';
                if (badgeSpan && btnMode === 'semua') {
                    badgeSpan.className = 'text-[11px] px-1.5 py-0.2 rounded-full bg-purple-700 text-purple-100';
                } else if (badgeSpan && btnMode === 'hari-ini') {
                    badgeSpan.className = 'text-[11px] px-1.5 py-0.2 rounded-full bg-purple-700 text-purple-100';
                }
            } else {
                btn.className = 'px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 text-slate-600 hover:text-purple-600 hover:bg-slate-50';
                if (badgeSpan && btnMode === 'semua') {
                    badgeSpan.className = 'text-[11px] px-1.5 py-0.2 rounded-full bg-slate-100 text-slate-600';
                } else if (badgeSpan && btnMode === 'hari-ini') {
                    badgeSpan.className = 'text-[11px] px-1.5 py-0.2 rounded-full bg-purple-100 text-purple-700 font-extrabold';
                }
            }
        });

        // 6. Sinkronkan ke URL query parameter tanpa reload
        try {
            const url = new URL(window.location.href);
            if (mode === 'hari-ini') {
                url.searchParams.set('filter', 'hari-ini');
            } else {
                url.searchParams.delete('filter');
                url.searchParams.delete('hari_ini');
            }
            window.history.replaceState({}, '', url.toString());
        } catch (e) {}
    }

    // Pasang event listener pada setiap tombol filter
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetMode = this.dataset.filterBtn;
            applyFilter(targetMode);
        });
    });

    // Inisialisasi awal mengikuti parameter URL
    const initialMode = '{{ $activeFilter }}';
    if (initialMode === 'hari-ini') {
        applyFilter('hari-ini');
    }
});
</script>
@endsection

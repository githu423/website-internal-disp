{{-- ===== HALAMAN: Agenda — daftar per bulan + agenda terdekat. ===== --}}
@extends('layouts.dashboard')

@section('title', 'Agenda & Kegiatan')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Jadwal Rapat, Acara, & Agenda Kerja Kedinasan')

@section('content')
@php 
    $u = auth()->user(); 

    /*
     |--------------------------------------------------------------------------
     | Gabungkan acara Ruang Rapat ke Agenda & Kegiatan
     |--------------------------------------------------------------------------
     | Admin & kepala bidang membuat acara rapat melalui menu Ruang Rapat
     | (judul, tanggal_rapat, lokasi, bidang). Selama ini acara tersebut hanya
     | hidup di tabel ruang_rapat sehingga tidak pernah tampil di halaman ini.
     | Di sini data ruang_rapat dibaca (read-only) lalu diubah menjadi objek
     | Agenda semu agar otomatis ikut kalender, daftar, pencarian, dan filter
     | kategori "Rapat" — tanpa menulis/mengubah data apa pun di backend.
     */
    $buatAgendaDariRapat = function (\App\Models\RuangRapat $rapat) use ($u) {
        $jadwal = $rapat->tanggal_rapat;

        $agenda = new \App\Models\Agenda([
            'judul' => $rapat->judul,
            'deskripsi' => $rapat->deskripsi,
            'tanggal' => $jadwal->toDateString(),
            'jam_mulai' => $jadwal->format('H:i'),
            'lokasi' => $rapat->lokasi,
            'kategori' => 'rapat',
            'status' => $jadwal->isPast() ? 'selesai' : 'belum',
            'id_bidang' => $rapat->id_bidang,
            'created_by' => $rapat->dibuat_oleh,
        ]);

        // id semu negatif: tidak mungkin bentrok dengan id agenda asli (positif)
        $agenda->id = -1 * (int) $rapat->id;
        $agenda->sumber = 'ruang_rapat';
        // Admin/bidang masuk ke halaman pengelolaan; pegawai langsung ke halaman rapat umum.
        $agenda->url_kelola = ($u->isAdmin() || $u->isBidang())
            ? route('ruang-rapat.show', $rapat)
            : route('rapat.buka', ['token' => $rapat->token]);
        $agenda->label_kelola = ($u->isAdmin() || $u->isBidang()) ? 'Buka Ruang Rapat' : 'Masuk Rapat';
        $agenda->setRelation('bidang', $rapat->bidang);

        return $agenda;
    };

    // Semua ruang rapat yang punya jadwal ikut tampil — termasuk yang tautan
    // tamunya sudah dinonaktifkan, karena acaranya tetap tercatat sebagai rapat.
    $rapatBulanIni = \App\Models\RuangRapat::query()
        ->with('bidang')
        ->whereNotNull('tanggal_rapat')
        ->whereYear('tanggal_rapat', \Carbon\Carbon::parse($bulanIni . '-01')->year)
        ->whereMonth('tanggal_rapat', \Carbon\Carbon::parse($bulanIni . '-01')->month)
        ->orderBy('tanggal_rapat')
        ->get()
        ->map($buatAgendaDariRapat);

    $items = $items->concat($rapatBulanIni)
        ->sortBy(fn ($a) => $a->tanggal->format('Y-m-d') . ' ' . substr((string) ($a->jam_mulai ?? '00:00'), 0, 5))
        ->values();

    // Acara rapat mendatang juga ikut di kartu "Agenda 14 Hari ke Depan".
    $rapatTerdekat = \App\Models\RuangRapat::query()
        ->with('bidang')
        ->whereNotNull('tanggal_rapat')
        ->where('tanggal_rapat', '>=', now()->startOfDay())
        ->orderBy('tanggal_rapat')
        ->limit(5)
        ->get()
        ->map($buatAgendaDariRapat);

    $terdekat = $terdekat->concat($rapatTerdekat)
        ->sortBy(fn ($a) => $a->tanggal->format('Y-m-d') . ' ' . substr((string) ($a->jam_mulai ?? '00:00'), 0, 5))
        ->take(5)
        ->values();

    $dinas = $items->whereNull('id_bidang')->values();
    $perBidang = $items->whereNotNull('id_bidang')->groupBy(fn ($a) => $a->bidang?->nama ?? 'Bidang')->sortKeys();
    
    $todayYmd = now()->format('Y-m-d');
    $todayMonth = now()->format('Y-m');
    $isCurrentMonth = ($bulanIni === $todayMonth);
    $totalHariIni = $items->filter(fn($a) => $a->tanggal->format('Y-m-d') === $todayYmd)->count();
    $activeFilter = (request('filter') === 'hari-ini' || request('hari_ini') == '1') ? 'hari-ini' : 'semua';

    // Matriks Kalender Bulanan
    $firstDayCarbon = \Carbon\Carbon::parse($bulanIni . '-01');
    $daysInMonth = $firstDayCarbon->daysInMonth;
    $startDayOfWeek = $firstDayCarbon->dayOfWeek; // 0 = Minggu, 1 = Senin, ..., 6 = Sabtu
    $prevMonthDays = (int) $firstDayCarbon->copy()->subMonth()->daysInMonth;
    
    // Grouping agenda berdasarkan tanggal 'Y-m-d'
    $itemsByDate = $items->groupBy(fn($a) => $a->tanggal->format('Y-m-d'));

    // Susun Array Sel Kalender
    $calendarCells = [];
    // Padding hari dari bulan lalu
    for ($i = $startDayOfWeek - 1; $i >= 0; $i--) {
        $calendarCells[] = [
            'day' => $prevMonthDays - $i,
            'isCurrentMonth' => false,
            'dateStr' => '',
            'events' => collect(),
            'isToday' => false,
        ];
    }
    // Hari di bulan aktif
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dateStr = sprintf('%s-%02d', $bulanIni, $d);
        $calendarCells[] = [
            'day' => $d,
            'isCurrentMonth' => true,
            'dateStr' => $dateStr,
            'events' => $itemsByDate->get($dateStr, collect()),
            'isToday' => ($dateStr === $todayYmd),
        ];
    }
    // Padding sisa agar kelipatan 7
    $remaining = (7 - (count($calendarCells) % 7)) % 7;
    for ($i = 1; $i <= $remaining; $i++) {
        $calendarCells[] = [
            'day' => $i,
            'isCurrentMonth' => false,
            'dateStr' => '',
            'events' => collect(),
            'isToday' => false,
        ];
    }

    // Peta Data JSON untuk Modal Detail & Pencarian Dinamis
    $itemsJsonData = [];
    foreach ($items as $item) {
        $dariRuangRapat = ($item->sumber ?? null) === 'ruang_rapat';
        $isHariIni = $item->tanggal->format('Y-m-d') === $todayYmd;
        $lewat = !$isHariIni && $item->status === 'belum' && $item->tanggal->lt(now()->startOfDay());
        $jamText = 'Waktu menyusul';
        if ($item->jam_mulai) {
            $jamText = substr((string)$item->jam_mulai, 0, 5);
            if ($item->jam_selesai) {
                $jamText .= ' – ' . substr((string)$item->jam_selesai, 0, 5);
            }
            $jamText .= ' WIB';
        }

        $itemsJsonData[$item->id] = [
            'id' => $item->id,
            'judul' => $item->judul,
            'kategori' => $item->kategori ?: 'lainnya',
            'tanggal' => $item->tanggal->format('Y-m-d'),
            'tanggal_label' => $item->tanggal->translatedFormat('l, d F Y'),
            'jam' => $jamText,
            'lokasi' => $item->lokasi ?: 'Lokasi menyusul / Belum ditentukan',
            'deskripsi' => $item->deskripsi ?: 'Tidak ada catatan atau uraian tambahan.',
            'status' => $item->status,
            'is_today' => $isHariIni,
            'is_lewat' => $lewat,
            'bidang_nama' => $item->bidang?->nama ?? 'Agenda Dinas (Umum)',
            'sumber' => $dariRuangRapat ? 'ruang_rapat' : 'agenda',
            'url_kelola' => $dariRuangRapat ? $item->url_kelola : null,
            'label_kelola' => $dariRuangRapat ? $item->label_kelola : null,
            'can_manage' => ! $dariRuangRapat && ($u->isAdmin() || $u->id === $item->created_by),
            'url_status' => $dariRuangRapat ? null : route('agenda.status', $item),
            'url_destroy' => $dariRuangRapat ? null : route('agenda.destroy', $item),
        ];
    }
@endphp

<div class="space-y-6">

    {{-- ===== BAGIAN: BREADCRUMB ===== --}}
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Kantor</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Agenda &amp; Kegiatan</span>
    </nav>

    {{-- ===== BAGIAN: TAJUK & NAVIGASI SORTIRAN ===== --}}
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

        {{-- ===== BAGIAN: PINDAH BULAN & SOROTAN CEPAT ===== --}}
        <div class="flex flex-wrap items-center gap-2.5">
            {{-- ===== BAGIAN: Navigasi Bulan ===== --}}
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

            {{-- ===== BAGIAN: Segmented Filter Shortcut: Semua Bulan Ini vs Hari Ini ===== --}}
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

    {{-- ===== BAGIAN: BILAH ALAT (tampilan, kategori, cari) ===== --}}
    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm space-y-3.5">
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            
            {{-- ===== BAGIAN: View Switcher (Kalender Bulanan vs Daftar Kegiatan) ===== --}}
            <div class="inline-flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 self-start md:self-auto shrink-0">
                <button type="button" 
                        id="view-toggle-calendar"
                        onclick="switchAgendaView('calendar')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-2 bg-white text-purple-700 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    Kalender Bulanan
                </button>
                <button type="button" 
                        id="view-toggle-list"
                        onclick="switchAgendaView('list')"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                    </svg>
                    Daftar Kegiatan
                </button>
            </div>

            {{-- ===== BAGIAN: Input Pencarian Real-Time ===== --}}
            <div class="relative flex-1 max-w-md">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" 
                       id="agenda-search-input"
                       placeholder="Cari nama agenda, lokasi, atau uraian..."
                       class="w-full pl-10 pr-8 py-2 text-xs md:text-sm bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-400 focus:bg-white transition" />
                <button type="button" 
                        id="agenda-search-clear" 
                        onclick="document.getElementById('agenda-search-input').value=''; filterAgendaAll();" 
                        class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 hidden">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- ===== BAGIAN: Filter Pill Kategori ===== --}}
        <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-slate-100 text-xs">
            <span class="font-bold text-slate-400 uppercase tracking-wider text-[11px] mr-1">Kategori:</span>
            <button type="button" 
                    data-cat-btn="semua"
                    onclick="setCategoryFilter('semua')"
                    class="px-2.5 py-1 rounded-xl font-bold transition bg-purple-600 text-white shadow-sm shadow-purple-500/20">
                Semua
            </button>
            <button type="button" 
                    data-cat-btn="rapat"
                    onclick="setCategoryFilter('rapat')"
                    class="px-2.5 py-1 rounded-xl font-semibold text-slate-600 hover:text-purple-600 hover:bg-purple-50 border border-slate-200 transition">
                Rapat
            </button>
            <button type="button" 
                    data-cat-btn="kegiatan"
                    onclick="setCategoryFilter('kegiatan')"
                    class="px-2.5 py-1 rounded-xl font-semibold text-slate-600 hover:text-sky-600 hover:bg-sky-50 border border-slate-200 transition">
                Kegiatan
            </button>
            <button type="button" 
                    data-cat-btn="kunjungan"
                    onclick="setCategoryFilter('kunjungan')"
                    class="px-2.5 py-1 rounded-xl font-semibold text-slate-600 hover:text-emerald-600 hover:bg-emerald-50 border border-slate-200 transition">
                Kunjungan
            </button>
            <button type="button" 
                    data-cat-btn="lainnya"
                    onclick="setCategoryFilter('lainnya')"
                    class="px-2.5 py-1 rounded-xl font-semibold text-slate-600 hover:text-amber-600 hover:bg-amber-50 border border-slate-200 transition">
                Lainnya
            </button>
        </div>
    </div>

    {{-- ===== BAGIAN: KONTEN UTAMA ===== --}}
    <div class="grid lg:grid-cols-3 gap-5">
        {{-- ===== BAGIAN: KOLOM KIRI — kalender / daftar ===== --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- ===== BAGIAN: Banner Info Filter Aktif Hari Ini ===== --}}
            <div id="filter-today-notice" class="bg-purple-50/80 border border-purple-200/80 rounded-2xl p-3.5 flex items-center justify-between text-xs text-purple-800 {{ $activeFilter === 'hari-ini' ? '' : 'hidden' }}">
                <div class="flex items-center gap-2">
                    <span class="text-base">📅</span>
                    <span>Menampilkan agenda khusus hari ini: <b>{{ now()->translatedFormat('l, d F Y') }}</b></span>
                </div>
                <button type="button" data-filter-btn="semua" class="font-bold underline text-purple-700 hover:text-purple-900 cursor-pointer text-xs">
                    Tampilkan Semua Bulan Ini
                </button>
            </div>

            {{-- ===== BAGIAN: Empty State Khusus Shortcut "Hari Ini" ===== --}}
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

            {{-- ===== BAGIAN: Empty State Jika Hasil Pencarian / Filter Kosong ===== --}}
            <div id="agenda-empty-search" class="bg-white border border-slate-200 rounded-2xl p-10 text-center shadow-sm hidden">
                <div class="h-12 w-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <h3 class="font-extrabold text-sm text-slate-800">Tidak ada kegiatan yang cocok</h3>
                <p class="text-xs text-slate-500 mt-1">Coba sesuaikan kata kunci pencarian atau ubah filter kategori.</p>
            </div>

            {{-- ===== BAGIAN: TAMPILAN KALENDER BULANAN ===== --}}
            <div id="agenda-calendar-view" class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-sm space-y-4">
                
                {{-- ===== BAGIAN: Keterangan Nama Bulan & Petunjuk ===== --}}
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <div>
                        <h2 class="text-base sm:text-lg font-extrabold text-slate-900 capitalize">
                            {{ \Carbon\Carbon::parse($bulanIni . '-01')->translatedFormat('F Y') }}
                        </h2>
                        <p class="text-xs text-slate-400">Klik pada nama agenda untuk melihat rincian detail.</p>
                    </div>
                    <span class="text-xs font-bold text-purple-700 bg-purple-50 border border-purple-100 px-2.5 py-1 rounded-xl">
                        {{ $items->count() }} Jadwal Bulan Ini
                    </span>
                </div>

                {{-- ===== BAGIAN: Header Hari Kalender (7 Kolom) ===== --}}
                <div class="grid grid-cols-7 gap-1 text-center font-bold text-[11px] sm:text-xs uppercase tracking-wider text-slate-400 py-1 border-b border-slate-100">
                    <span class="text-rose-500">Min</span>
                    <span>Sen</span>
                    <span>Sel</span>
                    <span>Rab</span>
                    <span>Kam</span>
                    <span>Jum</span>
                    <span>Sab</span>
                </div>

                {{-- ===== BAGIAN: Grid Sel Hari ===== --}}
                <div class="grid grid-cols-7 gap-1 sm:gap-1.5" id="calendar-grid-container">
                    @foreach ($calendarCells as $cell)
                        <div class="min-h-[85px] sm:min-h-[110px] p-1.5 sm:p-2 rounded-xl border flex flex-col justify-between transition group {{ !$cell['isCurrentMonth'] ? 'bg-slate-50/40 text-slate-300 border-transparent select-none' : ($cell['isToday'] ? 'ring-2 ring-purple-600 bg-purple-50/20 border-purple-300 shadow-sm' : 'bg-white border-slate-100 hover:border-purple-200 hover:shadow-xs') }}"
                             data-cal-cell
                             data-date="{{ $cell['dateStr'] }}">
                            
                            {{-- ===== BAGIAN: Header Sel: Nomor Tanggal & Indikator Hari Ini ===== --}}
                            <div class="flex items-center justify-between">
                                <span class="text-xs sm:text-sm font-bold inline-flex items-center justify-center w-6 h-6 rounded-full {{ $cell['isToday'] ? 'bg-purple-600 text-white shadow-sm' : ($cell['isCurrentMonth'] ? 'text-slate-700' : 'text-slate-300') }}">
                                    {{ $cell['day'] }}
                                </span>

                                @if ($cell['events']->count() > 0)
                                    <span class="text-[10px] font-extrabold px-1.5 py-0.2 rounded-full bg-slate-100 text-slate-600 sm:hidden">
                                        {{ $cell['events']->count() }}
                                    </span>
                                @endif
                            </div>

                            {{-- ===== BAGIAN: List Mini Event dalam Sel Kalender ===== --}}
                            <div class="space-y-1 mt-1 overflow-hidden" data-cal-events-wrap>
                                @foreach ($cell['events']->take(3) as $ev)
                                    @php
                                        $catColor = match(strtolower($ev->kategori ?? '')) {
                                            'rapat' => 'bg-purple-100 text-purple-700 border-purple-200 hover:bg-purple-200',
                                            'kegiatan' => 'bg-sky-100 text-sky-700 border-sky-200 hover:bg-sky-200',
                                            'kunjungan' => 'bg-emerald-100 text-emerald-700 border-emerald-200 hover:bg-emerald-200',
                                            default => 'bg-amber-100 text-amber-800 border-amber-200 hover:bg-amber-200',
                                        };
                                    @endphp
                                    <div data-cal-event
                                         data-id="{{ $ev->id }}"
                                         data-kategori="{{ strtolower($ev->kategori ?: 'lainnya') }}"
                                         data-search="{{ strtolower($ev->judul . ' ' . $ev->lokasi . ' ' . $ev->deskripsi . ' ' . ($ev->bidang?->nama ?? '')) }}"
                                         onclick="event.stopPropagation(); openAgendaDetailModal({{ $ev->id }})"
                                         class="w-full text-left text-[10.5px] font-semibold px-1.5 py-0.5 rounded-lg truncate cursor-pointer transition hover:opacity-95 active:scale-95 border {{ $catColor }} {{ $ev->status === 'selesai' ? 'line-through opacity-60' : '' }}"
                                         title="{{ $ev->judul }} ({{ $ev->jam_mulai ? substr((string)$ev->jam_mulai, 0, 5) : '' }})">
                                        @if ($ev->jam_mulai)
                                            <span class="font-extrabold">{{ substr((string)$ev->jam_mulai, 0, 5) }}</span>
                                        @endif
                                        <span>{{ $ev->judul }}</span>
                                    </div>
                                @endforeach

                                @if ($cell['events']->count() > 3)
                                    <div class="text-[9.5px] font-bold text-slate-400 pl-1">
                                        +{{ $cell['events']->count() - 3 }} lainnya
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- ===== BAGIAN: Legenda Kategori ===== --}}
                <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center gap-3 sm:gap-4 text-xs text-slate-500">
                    <span class="font-bold text-slate-400 text-[11px] uppercase tracking-wider">Keterangan:</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span> Rapat</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span> Kegiatan</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Kunjungan</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Lainnya</span>
                </div>
            </div>

            {{-- ===== BAGIAN: TAMPILAN DAFTAR KEGIATAN ===== --}}
            <div id="agenda-list-view" class="space-y-4 hidden">
                @if ($items->isEmpty())
                    <div class="bg-white border border-slate-200 rounded-2xl p-10 text-center shadow-sm">
                        <x-empty icon="agenda" title="Tidak ada agenda di bulan ini" desc="Agenda dinas dibuat oleh admin; agenda bidang dibuat melalui halaman profil bidang masing-masing." />
                    </div>
                @endif

                {{-- ===== BAGIAN: Bagian 1: Agenda Dinas (Umum) ===== --}}
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

                {{-- ===== BAGIAN: Bagian 2: Agenda Per Bidang ===== --}}
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

        </div>

        {{-- ===== BAGIAN: KOLOM KANAN — formulir & pengingat ===== --}}
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

            {{-- ===== BAGIAN: PENGINGAT 14 HARI ===== --}}
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                <h3 class="font-extrabold text-[15px] text-slate-900 mb-3 flex items-center gap-2">
                    <span>📅</span> Agenda 14 Hari ke Depan
                </h3>
                <div class="space-y-1">
                    @forelse ($terdekat as $a)
                        <div class="flex gap-3 py-2.5 {{ $loop->last ? '' : 'border-b border-slate-100' }} cursor-pointer hover:bg-slate-50/80 px-2 rounded-xl transition"
                             onclick="openAgendaDetailModal({{ $a->id }})">
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

{{-- ===== BAGIAN: MODAL DETAIL AGENDA ===== --}}
<div id="agenda-detail-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden items-center justify-center p-4">
    <div id="agenda-modal-box" class="bg-white w-full max-w-lg rounded-3xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all duration-200 scale-95 opacity-0">
        {{-- ===== BAGIAN: Header Modal ===== --}}
        <div class="p-6 pb-4 border-b border-slate-100 flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span id="modal-kategori-badge" class="text-[10.5px] font-extrabold uppercase tracking-wider px-2.5 py-0.5 rounded-full border"></span>
                    <span id="modal-status-badge" class="text-[10.5px] font-extrabold px-2.5 py-0.5 rounded-full border"></span>
                </div>
                <h3 id="modal-judul" class="text-lg sm:text-xl font-extrabold text-slate-900 leading-snug"></h3>
            </div>
            <button type="button" 
                    onclick="closeAgendaDetailModal()" 
                    class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition shrink-0"
                    title="Tutup (Esc)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        {{-- ===== BAGIAN: Body Modal ===== --}}
        <div class="p-6 space-y-3.5 text-sm text-slate-600 max-h-[70vh] overflow-y-auto">
            {{-- ===== BAGIAN: Jadwal Pelaksanaan ===== --}}
            <div class="flex items-start gap-3 bg-slate-50 p-3.5 rounded-2xl border border-slate-100">
                <div class="p-2 bg-purple-100 text-purple-700 rounded-xl shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <div class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">Jadwal Pelaksanaan</div>
                    <div id="modal-tanggal" class="font-bold text-slate-800 text-sm"></div>
                    <div id="modal-jam" class="text-xs text-purple-700 font-bold mt-0.5"></div>
                </div>
            </div>

            {{-- ===== BAGIAN: Lokasi Kegiatan ===== --}}
            <div class="flex items-start gap-3 bg-slate-50 p-3.5 rounded-2xl border border-slate-100">
                <div class="p-2 bg-emerald-100 text-emerald-700 rounded-xl shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">Lokasi / Tempat</div>
                    <div id="modal-lokasi" class="font-semibold text-slate-800 text-sm"></div>
                </div>
            </div>

            {{-- ===== BAGIAN: Bidang / Penyelenggara ===== --}}
            <div class="flex items-start gap-3 bg-slate-50 p-3.5 rounded-2xl border border-slate-100">
                <div class="p-2 bg-sky-100 text-sky-700 rounded-xl shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <div>
                    <div class="text-[10.5px] font-bold text-slate-400 uppercase tracking-wider">Pelaksana / Bidang</div>
                    <div id="modal-bidang" class="font-semibold text-slate-800 text-sm"></div>
                </div>
            </div>

            {{-- ===== BAGIAN: Catatan / Deskripsi ===== --}}
            <div class="pt-1">
                <div class="text-[10.5px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Catatan / Uraian Kegiatan</div>
                <div id="modal-deskripsi" class="text-xs sm:text-sm text-slate-600 leading-relaxed bg-slate-50 p-3.5 rounded-2xl border border-slate-100 whitespace-pre-line"></div>
            </div>
        </div>

        {{-- ===== BAGIAN: Footer Modal: Aksi Selesai / Hapus / Tutup ===== --}}
        <div class="p-4 sm:p-5 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
            <div id="modal-actions-container" class="flex items-center gap-2"></div>
            <button type="button" 
                    onclick="closeAgendaDetailModal()" 
                    class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl text-xs transition">
                Tutup
            </button>
        </div>
    </div>
</div>

{{-- ===== BAGIAN: SKRIP INTERAKTIF ===== --}}
<script>
// Data Master Agenda dalam format JSON
window.AGENDA_ITEMS_MAP = {!! json_encode($itemsJsonData) !!};

let currentAgendaView = localStorage.getItem('agenda-pref-view') || 'calendar';
let activeCategoryFilter = 'semua';
let activeTimeFilter = '{{ $activeFilter }}'; // 'semua' atau 'hari-ini'
const todayYmd = '{{ $todayYmd }}';

// 1. Fungsi Switch View (Kalender vs List)
function switchAgendaView(view) {
    currentAgendaView = view;
    localStorage.setItem('agenda-pref-view', view);

    const btnCal = document.getElementById('view-toggle-calendar');
    const btnList = document.getElementById('view-toggle-list');
    const calView = document.getElementById('agenda-calendar-view');
    const listView = document.getElementById('agenda-list-view');

    if (view === 'calendar') {
        calView.classList.remove('hidden');
        listView.classList.add('hidden');

        btnCal.className = 'px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-2 bg-white text-purple-700 shadow-sm';
        btnList.className = 'px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 transition flex items-center gap-2';
    } else {
        calView.classList.add('hidden');
        listView.classList.remove('hidden');

        btnList.className = 'px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-2 bg-white text-purple-700 shadow-sm';
        btnCal.className = 'px-3.5 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 transition flex items-center gap-2';
    }
}

// 2. Fungsi Filter Gabungan (Pencarian, Kategori, Waktu)
function filterAgendaAll() {
    const searchInput = document.getElementById('agenda-search-input');
    const clearBtn = document.getElementById('agenda-search-clear');
    const q = (searchInput?.value || '').toLowerCase().trim();

    if (clearBtn) {
        clearBtn.classList.toggle('hidden', q.length === 0);
    }

    const allCards = Array.from(document.querySelectorAll('[data-agenda-card]'));
    const allGroups = Array.from(document.querySelectorAll('[data-agenda-group]'));
    const allCalEvents = Array.from(document.querySelectorAll('[data-cal-event]'));
    const emptySearchElem = document.getElementById('agenda-empty-search');
    const badgeCount = document.getElementById('agenda-badge-count');

    let visibleCardTotal = 0;

    // Filter untuk Tampilan List View
    allCards.forEach(card => {
        const cDate = card.dataset.tanggal;
        const cCat = card.dataset.kategori;
        const cSearch = card.dataset.search || '';

        const matchTime = (activeTimeFilter === 'semua') || (activeTimeFilter === 'hari-ini' && cDate === todayYmd);
        const matchCat = (activeCategoryFilter === 'semua') || (cCat === activeCategoryFilter);
        const matchSearch = !q || cSearch.includes(q);

        const shouldShow = matchTime && matchCat && matchSearch;
        card.classList.toggle('hidden', !shouldShow);
        if (shouldShow) visibleCardTotal++;
    });

    // Update Visibilitas Grup & Jumlah di List View
    allGroups.forEach(group => {
        const itemsInGroup = Array.from(group.querySelectorAll('[data-agenda-card]'));
        const visibleInGroup = itemsInGroup.filter(c => !c.classList.contains('hidden'));
        const countElem = group.querySelector('[data-group-count]');
        if (countElem) countElem.textContent = visibleInGroup.length;
        group.classList.toggle('hidden', visibleInGroup.length === 0);
    });

    // Filter untuk Tampilan Kalender Bulanan
    allCalEvents.forEach(ev => {
        const evCat = ev.dataset.kategori;
        const evSearch = ev.dataset.search || '';
        const evCell = ev.closest('[data-cal-cell]');
        const cellDate = evCell?.dataset.date;

        const matchTime = (activeTimeFilter === 'semua') || (activeTimeFilter === 'hari-ini' && cellDate === todayYmd);
        const matchCat = (activeCategoryFilter === 'semua') || (evCat === activeCategoryFilter);
        const matchSearch = !q || evSearch.includes(q);

        const shouldShow = matchTime && matchCat && matchSearch;
        ev.classList.toggle('hidden', !shouldShow);
    });

    // Periksa apakah hasil benar-benar kosong
    if (visibleCardTotal === 0 && (q.length > 0 || activeCategoryFilter !== 'semua')) {
        emptySearchElem?.classList.remove('hidden');
    } else {
        emptySearchElem?.classList.add('hidden');
    }

    // Update Angka Badge di Header
    if (badgeCount) {
        badgeCount.textContent = (activeTimeFilter === 'hari-ini' ? `${visibleCardTotal} Kegiatan Hari Ini` : `${visibleCardTotal} Kegiatan`);
    }
}

// 3. Set Filter Kategori
function setCategoryFilter(cat) {
    activeCategoryFilter = cat;
    document.querySelectorAll('[data-cat-btn]').forEach(btn => {
        const isMatch = btn.dataset.catBtn === cat;
        if (isMatch) {
            btn.className = 'px-2.5 py-1 rounded-xl font-bold transition bg-purple-600 text-white shadow-sm shadow-purple-500/20';
        } else {
            btn.className = 'px-2.5 py-1 rounded-xl font-semibold text-slate-600 hover:text-purple-600 hover:bg-purple-50 border border-slate-200 transition';
        }
    });
    filterAgendaAll();
}

// 4. Modal Detail Agenda
function openAgendaDetailModal(id) {
    const data = window.AGENDA_ITEMS_MAP[id];
    if (!data) return;

    const modal = document.getElementById('agenda-detail-modal');
    const box = document.getElementById('agenda-modal-box');
    if (!modal || !box) return;

    // Isi Konten Modal
    document.getElementById('modal-judul').textContent = data.judul;
    document.getElementById('modal-tanggal').textContent = data.tanggal_label;
    document.getElementById('modal-jam').textContent = data.jam;
    document.getElementById('modal-lokasi').textContent = data.lokasi;
    document.getElementById('modal-bidang').textContent = data.bidang_nama;
    document.getElementById('modal-deskripsi').textContent = data.deskripsi;

    // Badge Kategori
    const catBadge = document.getElementById('modal-kategori-badge');
    const catColors = {
        rapat: 'bg-purple-100 text-purple-700 border-purple-200',
        kegiatan: 'bg-sky-100 text-sky-700 border-sky-200',
        kunjungan: 'bg-emerald-100 text-emerald-700 border-emerald-200',
        lainnya: 'bg-amber-100 text-amber-700 border-amber-200'
    };
    catBadge.className = `text-[10.5px] font-extrabold uppercase tracking-wider px-2.5 py-0.5 rounded-full border ${catColors[data.kategori] || 'bg-slate-100 text-slate-600 border-slate-200'}`;
    catBadge.textContent = data.kategori;

    // Badge Status
    const statusBadge = document.getElementById('modal-status-badge');
    if (data.status === 'selesai') {
        statusBadge.className = 'text-[10.5px] font-extrabold px-2.5 py-0.5 rounded-full border bg-emerald-50 text-emerald-700 border-emerald-200';
        statusBadge.textContent = '✓ SELESAI';
        statusBadge.classList.remove('hidden');
    } else if (data.is_today) {
        statusBadge.className = 'text-[10.5px] font-extrabold px-2.5 py-0.5 rounded-full border bg-purple-600 text-white border-purple-600 shadow-sm';
        statusBadge.textContent = '★ HARI INI';
        statusBadge.classList.remove('hidden');
    } else if (data.is_lewat) {
        statusBadge.className = 'text-[10.5px] font-extrabold px-2.5 py-0.5 rounded-full border bg-rose-100 text-rose-700 border-rose-200';
        statusBadge.textContent = 'TERLEWAT';
        statusBadge.classList.remove('hidden');
    } else {
        statusBadge.className = 'text-[10.5px] font-extrabold px-2.5 py-0.5 rounded-full border bg-slate-100 text-slate-600 border-slate-200';
        statusBadge.textContent = 'TERJADWAL';
        statusBadge.classList.remove('hidden');
    }

    // Tombol Aksi di Modal (Status & Hapus jika berhak)
    const actionsBox = document.getElementById('modal-actions-container');
    actionsBox.innerHTML = '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    if (data.sumber === 'ruang_rapat' && data.url_kelola) {
        // Acara dari menu Ruang Rapat: cukup arahkan ke halaman pengelolaannya.
        const kelolaLink = document.createElement('a');
        kelolaLink.href = data.url_kelola;
        kelolaLink.className = 'px-3 py-1.5 rounded-xl border border-purple-200 bg-purple-50 text-purple-700 hover:bg-purple-100 text-xs font-bold transition shadow-sm';
        kelolaLink.textContent = data.label_kelola || 'Buka Ruang Rapat';
        actionsBox.appendChild(kelolaLink);
    } else if (data.can_manage) {
        // Tombol Toggle Selesai
        const statusForm = document.createElement('form');
        statusForm.method = 'POST';
        statusForm.action = data.url_status;
        statusForm.innerHTML = `
            <input type="hidden" name="_token" value="${csrfToken}">
            <input type="hidden" name="_method" value="PATCH">
            <button class="px-3 py-1.5 rounded-xl border text-xs font-bold transition shadow-sm ${data.status === 'selesai' ? 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' : 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'}">
                ${data.status === 'selesai' ? 'Batal Selesai' : '✓ Tandai Selesai'}
            </button>
        `;
        actionsBox.appendChild(statusForm);

        // Tombol Hapus
        const delForm = document.createElement('form');
        delForm.method = 'POST';
        delForm.action = data.url_destroy;
        delForm.dataset.confirm = 'Hapus agenda ini?';
        delForm.innerHTML = `
            <input type="hidden" name="_token" value="${csrfToken}">
            <input type="hidden" name="_method" value="DELETE">
            <button class="px-3 py-1.5 rounded-xl border border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100 text-xs font-bold transition">
                Hapus
            </button>
        `;
        delForm.addEventListener('submit', (e) => {
            if (!confirm('Hapus agenda ini?')) e.preventDefault();
        });
        actionsBox.appendChild(delForm);
    }

    // Tampilkan Modal dengan Transisi Halus
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    requestAnimationFrame(() => {
        box.classList.remove('scale-95', 'opacity-0');
        box.classList.add('scale-100', 'opacity-100');
    });
}

function closeAgendaDetailModal() {
    const modal = document.getElementById('agenda-detail-modal');
    const box = document.getElementById('agenda-modal-box');
    if (!modal || !box) return;

    box.classList.remove('scale-100', 'opacity-100');
    box.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 180);
}

// Tutup modal jika klik di area latar belakang (backdrop)
document.getElementById('agenda-detail-modal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeAgendaDetailModal();
    }
});

// Tutup modal dengan tombol Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAgendaDetailModal();
    }
});

// Event Listener Search Input
document.getElementById('agenda-search-input')?.addEventListener('input', function() {
    filterAgendaAll();
});

// Pasang Event Listener Tombol Shortcut Hari Ini vs Semua di Header
document.querySelectorAll('[data-filter-btn]').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        activeTimeFilter = this.dataset.filterBtn;

        // Update Tampilan Tombol Filter Header
        document.querySelectorAll('[data-filter-btn]').forEach(b => {
            const bMode = b.dataset.filterBtn;
            const badgeSpan = b.querySelector('span:last-child');
            if (bMode === activeTimeFilter) {
                b.className = 'px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-purple-600 text-white shadow-sm shadow-purple-500/20';
                if (badgeSpan) badgeSpan.className = 'text-[11px] px-1.5 py-0.2 rounded-full bg-purple-700 text-purple-100';
            } else {
                b.className = 'px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 text-slate-600 hover:text-purple-600 hover:bg-slate-50';
                if (badgeSpan) badgeSpan.className = 'text-[11px] px-1.5 py-0.2 rounded-full bg-slate-100 text-slate-600';
            }
        });

        // Banner Notice
        const notice = document.getElementById('filter-today-notice');
        if (notice) notice.classList.toggle('hidden', activeTimeFilter !== 'hari-ini');

        // URL Update tanpa refresh
        try {
            const url = new URL(window.location.href);
            if (activeTimeFilter === 'hari-ini') {
                url.searchParams.set('filter', 'hari-ini');
            } else {
                url.searchParams.delete('filter');
                url.searchParams.delete('hari_ini');
            }
            window.history.replaceState({}, '', url.toString());
        } catch (err) {}

        filterAgendaAll();
    });
});

// Inisialisasi awal saat halaman siap
document.addEventListener('DOMContentLoaded', function() {
    switchAgendaView(currentAgendaView);
    if (activeTimeFilter === 'hari-ini') {
        filterAgendaAll();
    }
});
</script>
@endsection

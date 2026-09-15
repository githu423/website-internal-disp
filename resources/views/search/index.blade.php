@extends('layouts.dashboard')
@section('title', $q ? 'Hasil Pencarian: ' . $q : 'Cari')

@section('content')
{{-- ===== HEADER PENCARIAN ===== --}}
<div class="mb-6">
    <p class="text-xs text-gray-400 font-medium mb-1">SIPARDIS <span class="mx-1">/</span> <span class="text-gray-500">Pencarian</span></p>
    <div class="flex flex-wrap items-center gap-4">
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-gray-900">
                @if($q) Hasil untuk "<span class="text-purple-600">{{ $q }}</span>" @else Pencarian Global @endif
            </h2>
            <p class="text-sm text-slate-500 mt-0.5">
                @if($q) Ditemukan <strong>{{ $total }}</strong> hasil dari pegawai, agenda, dan dokumen @else Ketik kata kunci untuk mulai mencari @endif
            </p>
        </div>
        {{-- Form pencarian inline di halaman hasil (di mobile melebar penuh agar mudah diketik) --}}
        <form action="{{ route('search') }}" method="GET" class="w-full sm:flex-1 max-w-md">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input type="text" name="q" value="{{ $q }}" placeholder="Cari pegawai, agenda, dokumen..."
                    autofocus
                    class="block w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl bg-white text-sm placeholder-slate-400 focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 outline-none transition-all shadow-sm">
            </div>
        </form>
    </div>
</div>

@if(!$q)
{{-- State kosong --}}
<div class="clean-card p-12 flex flex-col items-center justify-center text-center">
    <div class="h-16 w-16 rounded-2xl bg-purple-50 text-purple-400 flex items-center justify-center mb-4">
        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    </div>
    <h3 class="font-bold text-lg text-slate-700">Ketik kata kunci di atas</h3>
    <p class="text-sm text-slate-400 mt-1 max-w-xs">Pencarian berjalan di seluruh data pegawai, agenda, dan dokumen secara bersamaan.</p>
    <div class="mt-5 flex flex-wrap gap-2 justify-center">
        @foreach(['Kepala Dinas', 'Rapat Koordinasi', 'SOP', 'Staf Ahli', 'Workshop'] as $contoh)
        <a href="{{ route('search', ['q' => $contoh]) }}" class="text-xs px-3 py-1.5 bg-slate-100 hover:bg-purple-50 hover:text-purple-600 text-slate-600 rounded-full transition-colors font-medium">
            {{ $contoh }}
        </a>
        @endforeach
    </div>
</div>

@elseif($total === 0)
{{-- Tidak ada hasil --}}
<div class="clean-card p-12 flex flex-col items-center justify-center text-center">
    <div class="h-16 w-16 rounded-2xl bg-amber-50 text-amber-400 flex items-center justify-center mb-4">
        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <h3 class="font-bold text-lg text-slate-700">Tidak ada hasil untuk "<span class="text-purple-600">{{ $q }}</span>"</h3>
    <p class="text-sm text-slate-400 mt-1 max-w-sm">Coba periksa ejaan, gunakan kata yang lebih umum, atau coba kata kunci lain.</p>
    <p class="text-xs text-emerald-600 bg-emerald-50 rounded-full px-3 py-1 mt-3 font-semibold">
        💡 Tip: Sistem secara otomatis mencoba mencocokkan kata yang bunyinya mirip (toleransi typo aktif)
    </p>
</div>

@else
{{-- Ada hasil --}}
<div class="space-y-6">

    {{-- ── PEGAWAI ──────────────────────────────────────── --}}
    @if($pegawai->isNotEmpty())
    <div>
        <div class="flex items-center gap-2 mb-3">
            <span class="h-7 w-7 rounded-lg bg-sky-100 text-sky-600 grid place-items-center">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </span>
            <h3 class="font-bold text-[15px] text-slate-800">Pegawai</h3>
            <span class="text-xs text-slate-400 font-medium">{{ $pegawai->count() }} hasil</span>
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            @foreach($pegawai as $p)
            <a href="{{ route('pegawai.index', ['q' => $p->nama]) }}" class="clean-card p-4 flex items-center gap-3.5 hover:-translate-y-0.5 hover:shadow-md hover:border-sky-200 transition-all duration-200 group">
                <span class="h-10 w-10 shrink-0 rounded-xl bg-gradient-to-br from-sky-400 to-indigo-500 text-white font-bold text-sm grid place-items-center shadow-sm">
                    {{ strtoupper(mb_substr($p->nama, 0, 2)) }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-[14px] text-slate-800 truncate group-hover:text-sky-700 transition-colors">{{ $p->nama }}</p>
                    <p class="text-[12px] text-slate-500 truncate">{{ $p->jabatan ?: '—' }} @if($p->bidang) · <span class="text-sky-600">{{ $p->bidang->nama }}</span>@endif</p>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0 {{ $p->status === 'aktif' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : ($p->status === 'pensiun' ? 'bg-slate-100 text-slate-500' : 'bg-amber-50 text-amber-600') }}">
                    {{ ucfirst($p->status) }}
                </span>
            </a>
            @endforeach
        </div>
        @if($pegawai->count() === 5)
        <a href="{{ route('pegawai.index', ['q' => $q]) }}" class="inline-flex items-center gap-1.5 text-xs text-sky-600 font-semibold mt-2.5 hover:underline">
            Lihat semua hasil pegawai →
        </a>
        @endif
    </div>
    @endif

    {{-- ── AGENDA ───────────────────────────────────────── --}}
    @if($agenda->isNotEmpty())
    <div>
        <div class="flex items-center gap-2 mb-3">
            <span class="h-7 w-7 rounded-lg bg-teal-100 text-teal-600 grid place-items-center">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </span>
            <h3 class="font-bold text-[15px] text-slate-800">Agenda</h3>
            <span class="text-xs text-slate-400 font-medium">{{ $agenda->count() }} hasil</span>
        </div>
        <div class="space-y-2.5">
            @foreach($agenda as $a)
            @php $lewat = $a->status === 'belum' && $a->tanggal->lt(now()->startOfDay()); @endphp
            <a href="{{ route('agenda.index') }}" class="clean-card p-4 flex items-start gap-4 hover:-translate-y-0.5 hover:shadow-md hover:border-teal-200 transition-all duration-200 group">
                <div class="h-12 w-12 shrink-0 rounded-xl grid place-items-center text-center {{ $lewat ? 'bg-rose-50 text-rose-600' : 'bg-teal-50 text-teal-700' }}">
                    <div><p class="text-lg font-extrabold leading-none">{{ $a->tanggal->format('d') }}</p><p class="text-[9px] font-bold uppercase">{{ $a->tanggal->translatedFormat('M') }}</p></div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-[14px] text-slate-800 group-hover:text-teal-700 transition-colors {{ $a->status === 'selesai' ? 'line-through' : '' }}">{{ $a->judul }}</p>
                    <p class="text-[12px] text-slate-500 mt-0.5">
                        {{ $a->jam_mulai ? substr((string)$a->jam_mulai,0,5).' WIB' : 'Waktu menyusul' }} ·
                        {{ $a->lokasi ?: 'Lokasi menyusul' }}
                        @if($a->bidang) · <span class="text-sky-600">{{ $a->bidang->nama }}</span>@endif
                    </p>
                </div>
                @if($lewat)<span class="text-[10px] font-bold bg-rose-100 text-rose-600 rounded px-1.5 py-0.5 shrink-0">TERLEWAT</span>@endif
                @if($a->status === 'selesai')<span class="text-[10px] font-bold bg-emerald-50 text-emerald-600 rounded px-1.5 py-0.5 shrink-0">SELESAI</span>@endif
            </a>
            @endforeach
        </div>
        @if($agenda->count() === 5)
        <a href="{{ route('agenda.index') }}" class="inline-flex items-center gap-1.5 text-xs text-teal-600 font-semibold mt-2.5 hover:underline">
            Lihat semua agenda →
        </a>
        @endif
    </div>
    @endif

    {{-- ── DOKUMEN ──────────────────────────────────────── --}}
    @if($dokumen->isNotEmpty())
    <div>
        <div class="flex items-center gap-2 mb-3">
            <span class="h-7 w-7 rounded-lg bg-violet-100 text-violet-600 grid place-items-center">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            </span>
            <h3 class="font-bold text-[15px] text-slate-800">Dokumen</h3>
            <span class="text-xs text-slate-400 font-medium">{{ $dokumen->count() }} hasil</span>
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
            @foreach($dokumen as $d)
            <a href="{{ route('dokumen.show', $d) }}" class="clean-card p-4 flex items-start gap-3.5 hover:-translate-y-0.5 hover:shadow-md hover:border-violet-200 transition-all duration-200 group">
                <span class="h-10 w-10 shrink-0 rounded-xl bg-violet-50 text-violet-500 grid place-items-center border border-violet-100">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-[14px] text-slate-800 truncate group-hover:text-violet-700 transition-colors">{{ $d->judul }}</p>
                    <p class="text-[12px] text-slate-500 truncate">
                        {{ $d->kategori ?? 'Dokumen' }}
                        @if($d->bidang) · <span class="text-sky-600">{{ $d->bidang->nama }}</span>@endif
                        · {{ $d->created_at->diffForHumans() }}
                    </p>
                </div>
            </a>
            @endforeach
        </div>
        @if($dokumen->count() === 5)
        <a href="{{ route('dokumen.index') }}" class="inline-flex items-center gap-1.5 text-xs text-violet-600 font-semibold mt-2.5 hover:underline">
            Lihat semua dokumen →
        </a>
        @endif
    </div>
    @endif

</div>
@endif

{{-- Info toleransi typo --}}
@if($q && $total > 0)
<p class="text-center text-xs text-slate-400 mt-8">
    💡 Hasil juga mencakup kata yang bunyinya mirip (SOUNDEX) — typo kecil tetap terdeteksi.
</p>
@endif

@endsection

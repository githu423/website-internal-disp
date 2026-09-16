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

    <!-- 2. Header Halaman & Navigasi Bulan -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Agenda &amp; Pengingat Kegiatan</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                    {{ $items->count() }} Kegiatan
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Kalender kegiatan resmi dinas dan program lintas bidang kerja.</p>
        </div>

        <!-- Bulan Switcher & Quick Actions -->
        <div class="flex flex-wrap items-center gap-2.5">
            <div class="inline-flex items-center bg-white border border-slate-200 rounded-xl p-1 shadow-sm">
                <a href="{{ route('agenda.index', ['bulan' => $bulanLalu]) }}" 
                   class="h-8 w-8 grid place-items-center rounded-lg text-slate-500 hover:text-purple-600 hover:bg-slate-50 active:scale-95 transition"
                   title="Bulan Sebelumnya">
                    <x-icon name="chevron" class="h-4 w-4" />
                </a>
                <span class="px-3 text-xs font-bold capitalize text-slate-800">
                    {{ \Carbon\Carbon::parse($bulanIni . '-01')->translatedFormat('F Y') }}
                </span>
                <a href="{{ route('agenda.index', ['bulan' => $bulanDepan]) }}" 
                   class="h-8 w-8 grid place-items-center rounded-lg text-slate-500 hover:text-purple-600 hover:bg-slate-50 active:scale-95 transition rotate-180"
                   title="Bulan Berikutnya">
                    <x-icon name="chevron" class="h-4 w-4" />
                </a>
            </div>

            <a href="{{ route('agenda.index') }}" 
               class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:text-purple-600 hover:border-purple-300 transition shadow-sm">
                Hari Ini
            </a>

            @if($u->isAdmin())
            <a href="{{ route('admin.agenda.index') }}" 
               class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-xs md:text-sm font-semibold rounded-xl shadow-sm shadow-purple-500/30 hover:bg-purple-700 hover:shadow-purple-600/40 active:scale-95 transition-all duration-150">
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
            @if ($items->isEmpty())
                <div class="bg-white border border-slate-200 rounded-xl p-10 text-center shadow-sm">
                    <x-empty icon="agenda" title="Tidak ada agenda di bulan ini" desc="Agenda dinas dibuat oleh admin; agenda bidang dibuat melalui halaman profil bidang masing-masing." />
                </div>
            @endif

            @if ($dinas->isNotEmpty())
                <div class="space-y-2.5">
                    <div class="flex items-center gap-3 pt-1 pb-1">
                        <span class="h-8 w-8 rounded-xl bg-slate-900 text-white grid place-items-center shadow-sm text-sm">🏛</span>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-extrabold text-[15px] leading-tight text-slate-900">Agenda Dinas (Umum)</h3>
                            <p class="text-[11.5px] font-medium text-slate-500">{{ $dinas->count() }} kegiatan kedinasan</p>
                        </div>
                    </div>
                    @foreach ($dinas as $a)
                        @include('agenda._kartu', ['a' => $a])
                    @endforeach
                </div>
            @endif

            @foreach ($perBidang as $nama => $grup)
                <div class="space-y-2.5 pt-2">
                    <div class="flex items-center gap-3 pt-2 pb-1 border-t border-slate-200/60">
                        <span class="h-8 w-8 rounded-xl bg-purple-50 text-purple-700 grid place-items-center shadow-sm border border-purple-100 text-sm">🏢</span>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-extrabold text-[15px] leading-tight text-slate-900">{{ $nama }}</h3>
                            <p class="text-[11.5px] font-medium text-slate-500">{{ $grup->count() }} kegiatan bidang</p>
                        </div>
                    </div>
                    @foreach ($grup as $a)
                        @include('agenda._kartu', ['a' => $a])
                    @endforeach
                </div>
            @endforeach
        </div>

        <!-- Kolom Kanan: Tambah Agenda (Admin) & Pengingat 14 Hari -->
        <div class="space-y-4">
            @if ($u->isAdmin())
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
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
                            <input type="date" name="tanggal" value="{{ $bulanIni }}-15" required 
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
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <h3 class="font-extrabold text-[15px] text-slate-900 mb-3 flex items-center gap-2">
                    <span>📅</span> Agenda 14 Hari ke Depan
                </h3>
                <div class="space-y-1">
                    @forelse ($terdekat as $a)
                        <div class="flex gap-3 py-2.5 {{ $loop->last ? '' : 'border-b border-slate-100' }}">
                            <span class="text-xs font-bold text-purple-700 whitespace-nowrap w-14 shrink-0">
                                {{ $a->tanggal->translatedFormat('d M') }}
                            </span>
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
@endsection

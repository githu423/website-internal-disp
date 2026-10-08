@extends('layouts.dashboard')

@section('title', 'Kegiatan & Capaian Kinerja')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Daftar Kegiatan & Capaian Kinerja Dinas Pariwisata')

@section('content')
@php 
    $u = auth()->user(); 
@endphp

<div class="space-y-6">

    <!-- 1. Breadcrumbs Mikro -->
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Data &amp; Kinerja</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Kegiatan &amp; Kinerja</span>
    </nav>

    <!-- 2. Header Halaman & Quick Actions -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Kegiatan &amp; Capaian Kinerja</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                    TA {{ $tahun }}
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Sumber: Kinerja Fisik Dinas — monitoring kegiatan, target, dan realisasi per bidang.</p>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Pilihan Tahun Anggaran (Segmented Pill) -->
            <div class="inline-flex items-center bg-white border border-slate-200 rounded-xl p-1 shadow-sm">
                @foreach ($tahunList as $t)
                    <a href="{{ route('kinerja.publik', array_merge(request()->query(), ['tahun' => $t])) }}" 
                       class="px-3 py-1.5 text-xs font-bold rounded-lg transition {{ $t == $tahun ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-600 hover:text-purple-600 hover:bg-slate-50' }}">
                        {{ $t }}
                    </a>
                @endforeach
            </div>

            <!-- Tombol Sekunder: Matriks Capaian Kinerja -->
            <a href="{{ route('kinerja.capaian') }}" 
               class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2.5 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl shadow-sm hover:bg-slate-50 hover:border-purple-300 hover:text-purple-600 active:scale-95 transition-all duration-150">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Matriks Capaian →
            </a>

            <!-- Tombol Utama (Hanya Admin: Kelola Kinerja) -->
            @if($u->isAdmin())
            <a href="{{ route('admin.kinerja.index', ['tahun' => $tahun]) }}" 
               class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-xl shadow-sm shadow-purple-500/30 hover:bg-purple-700 hover:shadow-purple-600/40 active:scale-95 transition-all duration-150">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Kelola Kinerja
            </a>
            @endif
        </div>
    </div>

    <!-- 3. Lingkup Data (Jika akun terafiliasi ke bidang) -->
    @if ($punyaBidang)
    <div class="flex items-center gap-2 text-xs">
        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Lingkup Data:</span>
        <div class="inline-flex items-center bg-white border border-slate-200 rounded-xl p-1 shadow-sm">
            <a href="{{ route('kinerja.publik', array_merge(request()->query(), ['lingkup' => 'semua'])) }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-bold transition {{ ! $lingkupSaya ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                <span>🏛</span> Seluruh Dinas
            </a>
            <a href="{{ route('kinerja.publik', array_merge(request()->query(), ['lingkup' => 'saya'])) }}" 
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-bold transition {{ $lingkupSaya ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-600 hover:text-purple-600 hover:bg-slate-50' }}">
                <span>🏢</span> Bidang Saya
            </a>
        </div>
    </div>
    @endif

    <!-- 4. Stat Cards Ringkasan Kinerja -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
        <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Sub Kegiatan</p>
                <p class="text-2xl font-black text-slate-900 mt-0.5">{{ (int) $ringkas->total }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">TA {{ $tahun }}</p>
            </div>
            <div class="h-11 w-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100 shrink-0">
                <x-icon name="kinerja" class="h-5 w-5" />
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Tuntas (≥100%)</p>
                <p class="text-2xl font-black text-emerald-600 mt-0.5">{{ (int) $ringkas->tuntas }}</p>
                <p class="text-[11px] text-emerald-600/80 mt-0.5 font-medium">Target tercapai</p>
            </div>
            <div class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shrink-0">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Berjalan (60–99%)</p>
                <p class="text-2xl font-black text-sky-600 mt-0.5">{{ (int) $ringkas->berjalan }}</p>
                <p class="text-[11px] text-sky-600/80 mt-0.5 font-medium">Dalam progres</p>
            </div>
            <div class="h-11 w-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center border border-sky-100 shrink-0">
                <x-icon name="statistik" class="h-5 w-5" />
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Perlu Perhatian (&lt;60%)</p>
                <p class="text-2xl font-black text-rose-600 mt-0.5">{{ (int) $ringkas->lambat }}</p>
                <p class="text-[11px] text-rose-600/80 mt-0.5 font-medium">Perlu percepatan</p>
            </div>
            <div class="h-11 w-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100 shrink-0">
                <x-icon name="alert" class="h-5 w-5" />
            </div>
        </div>
    </div>

    <!-- 5. Kontainer Data Tabel & Filter -->
    <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
        <!-- Toolbar Filter & Pencarian -->
        <div class="p-4 sm:p-5 border-b border-slate-100 bg-slate-50/50">
            <form method="GET" class="flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-center">
                <input type="hidden" name="tahun" value="{{ $tahun }}">
                @if (request('lingkup'))
                    <input type="hidden" name="lingkup" value="{{ request('lingkup') }}">
                @endif
                
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input name="q" value="{{ request('q') }}" placeholder="Cari kegiatan atau indikator…" 
                           class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl text-xs md:text-sm bg-white placeholder-slate-400 focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                </div>

                <div class="flex items-center gap-2">
                    <x-select name="status" :options="['' => 'Semua Status', 'tuntas' => 'Tuntas (≥100%)', 'berjalan' => 'Berjalan (60–99%)', 'lambat' => 'Perlu perhatian (<60%)']" :value="request('status')" class="w-48 sm:w-56" />

                    <button type="submit" 
                            class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs md:text-sm font-semibold hover:bg-slate-800 active:scale-95 transition shadow-sm">
                        Cari
                    </button>
                    @if (request('q') || request('status'))
                        <a href="{{ route('kinerja.publik', array_filter(['tahun' => $tahun, 'lingkup' => request('lingkup')])) }}" 
                           class="px-3.5 py-2 border border-slate-200 bg-white text-slate-500 hover:text-slate-800 rounded-xl text-xs md:text-sm font-semibold transition">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Tabel Data -->
        <div class="overflow-x-auto thin-scroll">
            <table class="data w-full min-w-[920px]">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-[11px] uppercase tracking-wider font-extrabold">
                        <th class="w-12 text-center py-3 px-4">#</th>
                        <th class="py-3 px-4 min-w-[260px]">Kegiatan &amp; Catatan</th>
                        <th class="py-3 px-4 min-w-[200px]">Indikator &amp; Pagu</th>
                        <th class="py-3 px-4">Pelaksana</th>
                        <th class="py-3 px-4">Target</th>
                        <th class="py-3 px-4">Realisasi</th>
                        <th class="py-3 px-4 min-w-[190px]">Capaian</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($items as $k)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="text-slate-400 text-xs text-center font-mono py-3.5 px-4">{{ $items->firstItem() + $loop->index }}</td>
                        <td class="py-3.5 px-4">
                            <p class="font-semibold text-[13px] text-slate-900 leading-snug">{{ $k->nama }}</p>
                            @if ($k->catatan)
                                <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-200/60 rounded-md px-2 py-0.5 mt-1 inline-block">
                                    ⚠ {{ $k->catatan }}
                                </p>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-xs text-slate-600">
                            <span class="font-medium text-slate-800 block">{{ $k->indikator ?? '—' }}</span>
                            <span class="block text-slate-400 font-mono mt-0.5">Pagu: Rp {{ number_format($k->pagu_anggaran, 0, ',', '.') }}</span>
                        </td>
                        <td class="py-3.5 px-4 text-xs text-slate-600">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[11px] font-medium">
                                {{ $k->bidang?->nama ?? 'Dinas' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-xs font-semibold whitespace-nowrap text-slate-800">
                            {{ rtrim(rtrim(number_format((float) $k->target, 2, ',', '.'), '0'), ',') }} 
                            <span class="font-normal text-slate-400">{{ $k->satuan }}</span>
                        </td>
                        <td class="py-3.5 px-4 text-xs font-bold whitespace-nowrap text-purple-700">
                            {{ rtrim(rtrim(number_format((float) $k->capaian, 2, ',', '.'), '0'), ',') }} 
                            <span class="font-normal text-purple-600/70">{{ $k->satuan }}</span>
                        </td>
                        <td class="py-3.5 px-4">
                            <x-prog :persen="$k->persentase" />
                        </td>
                        <td class="py-3.5 px-4 text-center whitespace-nowrap">
                            @php $st = $k->label_status; @endphp
                            @if ($st === 'Tuntas')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Tuntas
                                </span>
                            @elseif ($st === 'Berjalan')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                                    Berjalan
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                    Perlu Perhatian
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-10 text-center">
                            <x-empty icon="kinerja" title="Tidak ada kegiatan yang cocok" desc="Coba kata kunci lain atau pilih tahun anggaran yang sesuai." />
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
            <div class="p-3.5 border-t border-slate-100 bg-slate-50/50">
                {{ $items->links() }}
            </div>
        @endif
    </div>

</div>
@endsection

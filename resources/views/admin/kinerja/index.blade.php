{{-- ===== HALAMAN: Admin · Kinerja — daftar program/kegiatan + import Excel. ===== --}}
@extends('layouts.dashboard')

@section('title', 'Kelola Kinerja & Kegiatan')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Manajemen Kinerja Fisik, Target, & Realisasi Kegiatan Dinas')

@section('content')
@php 
    $u = auth()->user(); 
    $levelOptions = [
        '' => 'Semua Level',
        'sub_kegiatan' => 'Sub Kegiatan',
        'kegiatan' => 'Kegiatan',
        'program' => 'Program',
    ];
@endphp

<div class="space-y-6">

    {{-- ===== BAGIAN: Breadcrumbs Mikro ===== --}}
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Admin — Data</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Kelola Kinerja</span>
    </nav>

    {{-- ===== BAGIAN: Header Halaman & Quick Actions ===== --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Kelola Kinerja Fisik Dinas</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                    TA {{ $tahun }}
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">{{ $summary->total ?? 0 }} sub kegiatan · {{ $summary->tuntas ?? 0 }} tuntas · {{ $summary->lambat ?? 0 }} perlu perhatian — tampil di Dashboard &amp; modul publik.</p>
        </div>

        {{-- ===== BAGIAN: AKSI CEPAT ===== --}}
        <div class="flex flex-wrap items-center gap-2.5">
            {{-- ===== BAGIAN: PILIHAN TAHUN ANGGARAN ===== --}}
            <div class="inline-flex items-center bg-white border border-slate-200 rounded-xl p-1 shadow-sm">
                @foreach ($tahunList as $t)
                    <a href="{{ route('admin.kinerja.index', ['tahun' => $t]) }}" 
                       class="px-3 py-1.5 text-xs font-bold rounded-lg transition {{ $t == $tahun ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-600 hover:text-purple-600 hover:bg-slate-50' }}">
                        {{ $t }}
                    </a>
                @endforeach
            </div>

            {{-- ===== BAGIAN: Tombol Sekunder: Lihat Publik ===== --}}
            <a href="{{ route('kinerja.publik', ['tahun' => $tahun]) }}" 
               class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2.5 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl shadow-sm hover:bg-slate-50 hover:border-purple-300 hover:text-purple-600 active:scale-95 transition-all duration-150">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Lihat Publik
            </a>

            {{-- ===== BAGIAN: Tombol Utama: Tambah Manual ===== --}}
            <button data-modal-open="modal-kinerja" 
                    class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-xl shadow-sm shadow-purple-500/30 hover:bg-purple-700 hover:shadow-purple-600/40 active:scale-95 transition-all duration-150">
                <x-icon name="plus" class="h-4 w-4" />
                Tambah Kegiatan
            </button>
        </div>
    </div>

    {{-- ===== BAGIAN: Stat Cards Ringkasan ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
        <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Sub Kegiatan</p>
                <p class="text-2xl font-black text-slate-900 mt-0.5">{{ (int) ($summary->total ?? 0) }}</p>
                <p class="text-[11px] text-slate-500 mt-0.5">TA {{ $tahun }}</p>
            </div>
            <div class="h-11 w-11 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100 shrink-0">
                <x-icon name="kinerja" class="h-5 w-5" />
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Tuntas (≥100%)</p>
                <p class="text-2xl font-black text-emerald-600 mt-0.5">{{ (int) ($summary->tuntas ?? 0) }}</p>
                <p class="text-[11px] text-emerald-600/80 mt-0.5 font-medium">Target tercapai</p>
            </div>
            <div class="h-11 w-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 shrink-0">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-5 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Perlu Perhatian (&lt;60%)</p>
                <p class="text-2xl font-black text-rose-600 mt-0.5">{{ (int) ($summary->lambat ?? 0) }}</p>
                <p class="text-[11px] text-rose-600/80 mt-0.5 font-medium">Perlu percepatan</p>
            </div>
            <div class="h-11 w-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100 shrink-0">
                <x-icon name="alert" class="h-5 w-5" />
            </div>
        </div>
    </div>

    {{-- ===== BAGIAN: Konten Grid: Tabel Data & Panel Impor/Panduan ===== --}}
    <div class="grid lg:grid-cols-3 gap-5">
        {{-- ===== BAGIAN: KOLOM KIRI — tabel kinerja ===== --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
                {{-- ===== BAGIAN: BILAH ALAT FILTER & PENCARIAN ===== --}}
                <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                    <form method="GET" class="flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-center">
                        <input type="hidden" name="tahun" value="{{ $tahun }}">
                        
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
                            <x-select name="level" :options="$levelOptions" :value="request('level')" class="w-40 sm:w-44" autoSubmit />

                            <button type="submit" 
                                    class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs md:text-sm font-semibold hover:bg-slate-800 active:scale-95 transition shadow-sm">
                                Cari
                            </button>
                            @if (request('q') || request('level'))
                                <a href="{{ route('admin.kinerja.index', ['tahun' => $tahun]) }}" 
                                   class="px-3 py-2 border border-slate-200 bg-white text-slate-500 hover:text-slate-800 rounded-xl text-xs md:text-sm font-semibold transition">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                {{-- ===== BAGIAN: TABEL DATA ===== --}}
                <div class="overflow-x-auto thin-scroll">
                    <table class="data w-full min-w-[760px]">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-[11px] uppercase tracking-wider font-extrabold">
                                <th class="w-12 text-center py-3 px-3">Level</th>
                                <th class="py-3 px-3 min-w-[220px]">Kegiatan &amp; Indikator</th>
                                <th class="py-3 px-3 whitespace-nowrap">Target &rarr; Realisasi</th>
                                <th class="py-3 px-3 min-w-[140px]">Progres</th>
                                <th class="w-24 text-right py-3 px-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($items as $k)
                            <tr class="hover:bg-slate-50/70 transition {{ $k->level === 'program' ? 'bg-purple-50/20' : '' }}">
                                <td class="py-3 px-3 text-center align-top">
                                    @if ($k->level === 'program')
                                        <span class="inline-block text-[10px] font-black uppercase rounded-md px-1.5 py-0.5 bg-slate-900 text-white">
                                            PROG
                                        </span>
                                    @elseif ($k->level === 'kegiatan')
                                        <span class="inline-block text-[10px] font-black uppercase rounded-md px-1.5 py-0.5 bg-sky-50 text-sky-700 border border-sky-200">
                                            KEG
                                        </span>
                                    @else
                                        <span class="inline-block text-[10px] font-black uppercase rounded-md px-1.5 py-0.5 bg-purple-50 text-purple-700 border border-purple-200">
                                            SUB
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 align-top" style="padding-left: {{ $k->level === 'sub_kegiatan' ? '1.25rem' : '0.75rem' }}">
                                    <p class="text-[13px] leading-snug {{ $k->level === 'program' ? 'font-black text-slate-900' : ($k->level === 'kegiatan' ? 'font-bold text-slate-900' : 'font-semibold text-slate-800') }}">
                                        {{ $k->nama }}
                                    </p>
                                    @if ($k->indikator)
                                        <span class="block text-[11px] text-slate-500 font-normal mt-0.5">
                                            🎯 {{ $k->indikator }}
                                        </span>
                                    @endif
                                    @if ($k->pagu_anggaran > 0)
                                        <span class="block text-[10.5px] text-slate-400 font-mono mt-0.5">
                                            Pagu: Rp {{ number_format($k->pagu_anggaran, 0, ',', '.') }}
                                            @if ($k->bidang) · {{ $k->bidang->nama }} @endif
                                        </span>
                                    @endif
                                    @if ($k->catatan)
                                        <span class="inline-block text-[10.5px] text-amber-700 bg-amber-50 border border-amber-200/60 rounded px-1.5 py-0.5 mt-1 font-medium">
                                            ⚠ {{ $k->catatan }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-xs whitespace-nowrap align-top">
                                    <div class="text-[11px] text-slate-500 font-medium">Tgt: <span class="font-semibold text-slate-800">{{ rtrim(rtrim(number_format((float) $k->target, 2, ',', '.'), '0'), ',') }} {{ $k->satuan }}</span></div>
                                    <div class="text-[11px] text-purple-700 font-bold mt-0.5">Real: <span>{{ rtrim(rtrim(number_format((float) $k->capaian, 2, ',', '.'), '0'), ',') }} {{ $k->satuan }}</span></div>
                                </td>
                                <td class="py-3 px-3 align-top">
                                    <x-prog :persen="$k->persentase" />
                                </td>
                                <td class="py-3 px-4 text-right align-top whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        <button data-modal-open="edit-{{ $k->id }}" 
                                                class="px-2 py-1 text-xs font-bold rounded-lg text-purple-700 hover:bg-purple-50 transition" 
                                                title="Edit">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('admin.kinerja.destroy', $k) }}" data-confirm="Hapus data kegiatan ini?" class="inline">
                                            @csrf @method('DELETE')
                                            <button class="px-2 py-1 text-xs font-bold rounded-lg text-rose-600 hover:bg-rose-50 transition" title="Hapus">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            {{-- Modal Edit Baris Kinerja --}}
                            <div id="edit-{{ $k->id }}" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 backdrop-blur-sm p-4 grid place-items-center overflow-y-auto">
                                <div class="bg-white border border-slate-200 rounded-2xl w-full max-w-xl p-6 shadow-2xl text-sm">
                                    <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                                        <div>
                                            <h3 class="font-extrabold text-base text-slate-900">Edit Data Kegiatan</h3>
                                            <p class="text-xs text-slate-400 mt-0.5">ID #{{ $k->id }} · TA {{ $k->tahun }}</p>
                                        </div>
                                        <button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400 grid place-items-center transition">✕</button>
                                    </div>
                                    @if ($errors->any())
                                        <div class="mb-3 rounded-xl bg-rose-50 border border-rose-200 px-3.5 py-2.5 text-rose-700 text-xs font-medium">
                                            {{ $errors->first() }}
                                        </div>
                                    @endif
                                    <form method="POST" action="{{ route('admin.kinerja.update', $k) }}" class="space-y-3.5">
                                        @csrf @method('PATCH')
                                        <div>
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nama Kegiatan / Sub Kegiatan *</label>
                                            <input name="nama" value="{{ $k->nama }}" required 
                                                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tahun Anggaran</label>
                                                <select name="tahun" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm bg-white focus:outline-none focus:border-purple-400">
                                                    @foreach (range(now()->year + 1, now()->year - 4) as $ty)
                                                        <option value="{{ $ty }}" @selected($ty == $k->tahun)>TA {{ $ty }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tingkat Level</label>
                                                <select name="level" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm bg-white focus:outline-none focus:border-purple-400">
                                                    @foreach (['sub_kegiatan' => 'Sub Kegiatan', 'kegiatan' => 'Kegiatan', 'program' => 'Program'] as $lvv => $lvl)
                                                        <option value="{{ $lvv }}" @selected($lvv === $k->level)>{{ $lvl }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Sumber Dana</label>
                                                <input name="sumber_dana" value="{{ $k->sumber_dana }}" placeholder="mis. DAU / DBH" 
                                                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Pagu Anggaran (Rp)</label>
                                                <input type="number" step="1" name="pagu_anggaran" value="{{ (float) $k->pagu_anggaran }}" placeholder="0" 
                                                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Indikator Kinerja</label>
                                            <input name="indikator" value="{{ $k->indikator }}" placeholder="Tolok ukur keberhasilan kegiatan" 
                                                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                                        </div>

                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                            <div>
                                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Target</label>
                                                <input type="number" step="any" name="target" value="{{ (float) $k->target }}" 
                                                       class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-purple-400">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Satuan</label>
                                                <input name="satuan" value="{{ $k->satuan }}" placeholder="Laporan/Keg" 
                                                       class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-purple-400">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Capaian</label>
                                                <input type="number" step="any" name="capaian" value="{{ (float) $k->capaian }}" 
                                                       class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-purple-400">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Progres %</label>
                                                <input type="number" step="any" name="persentase" value="{{ (float) $k->persentase }}" 
                                                       class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-purple-400">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Pelaksana Bidang</label>
                                            <select name="id_bidang" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm bg-white focus:outline-none focus:border-purple-400">
                                                <option value="">— Tidak spesifik (Seluruh Dinas) —</option>
                                                @foreach ($bidangs as $bd)
                                                    <option value="{{ $bd->id }}" @selected($bd->id == $k->id_bidang)>{{ $bd->nama }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Catatan / Kendala Pelaksanaan</label>
                                            <textarea name="catatan" rows="2" placeholder="Tuliskan catatan permasalahan atau status kegiatan..." 
                                                      class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">{{ $k->catatan }}</textarea>
                                        </div>

                                        <button class="w-full rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 active:scale-[.98] transition shadow-sm shadow-purple-500/30">
                                            Simpan Perubahan
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center">
                                    <x-empty icon="kinerja" title="TA {{ $tahun }} belum memiliki data" desc="Unggah file kinerja pada panel kanan, atau tambahkan kegiatan manual." />
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

        {{-- ===== BAGIAN: KOLOM KANAN — impor & panduan ===== --}}
        <div class="space-y-4">
            {{-- ===== BAGIAN: KARTU IMPOR BERKAS ===== --}}
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="flex items-start gap-3.5 mb-3">
                    <span class="h-10 w-10 shrink-0 rounded-xl bg-purple-50 text-purple-600 border border-purple-100 grid place-items-center">
                        <x-icon name="upload" class="h-5 w-5" />
                    </span>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-[15px] text-slate-900">Impor Berkas Kinerja (Excel/CSV)</h3>
                        <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                            Format berkas «Kinerja Fisik Sedinas» — kolom PROGRAM/KEGIATAN/SUB, Pagu, Target, Satuan, dan Capaian.
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.kinerja.import') }}" enctype="multipart/form-data" data-import class="space-y-3">
                    @csrf
                    <label class="block border-2 border-dashed border-slate-200 rounded-xl p-4 text-center hover:border-purple-300 transition cursor-pointer bg-slate-50/50">
                        <input type="file" name="file" accept=".csv,.xlsx,.xls,.ods" required
                            class="block w-full text-xs text-slate-500 file:mx-auto file:mb-2 file:rounded-lg file:border-0 file:bg-purple-600 file:hover:bg-purple-700 file:px-3 file:py-1.5 file:text-white file:font-bold file:cursor-pointer transition">
                        <span class="text-[11px] text-slate-400 block">CSV, Excel (.xlsx, .xls, .ods) maks. 20 MB</span>
                    </label>

                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 rounded-xl border border-slate-200 px-3 py-2 bg-white cursor-pointer hover:border-purple-300 transition">
                        <input type="checkbox" name="ganti_tahun" value="1" checked class="rounded border-slate-300 text-purple-600 focus:ring-purple-500/20"> 
                        Ganti data tahun yang ada di file
                    </label>

                    <button type="submit" class="w-full rounded-xl bg-slate-900 hover:bg-slate-800 active:scale-[.98] disabled:opacity-50 text-white text-xs md:text-sm font-semibold py-2.5 transition shadow-sm">
                        Proses &amp; Impor Berkas
                    </button>
                </form>
            </div>

            @if (is_file(database_path('source/Kinerja Fisik Sedinas.csv')))
                <div class="bg-white border border-slate-200 rounded-xl p-4 text-xs text-slate-500 flex items-center gap-2.5 shadow-sm">
                    <span class="h-6 w-6 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center font-bold text-xs shrink-0">✓</span>
                    <div>
                        <span class="text-slate-700 font-semibold">Kinerja Fisik Sedinas.csv</span>
                        <p class="text-[10.5px] text-slate-400">Berkas acuan resmi tersimpan di server.</p>
                    </div>
                </div>
            @endif

            {{-- ===== BAGIAN: PANDUAN CARA KERJA ===== --}}
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm text-sm">
                <h3 class="font-extrabold text-[14px] text-slate-900 mb-2.5 flex items-center gap-2">
                    <span>💡</span> Panduan &amp; Cara Kerja
                </h3>
                <ul class="space-y-2 text-xs text-slate-500 leading-relaxed list-disc pl-4">
                    <li>Semua kegiatan otomatis muncul di <b class="text-slate-700">Dashboard Utama</b> seluruh pegawai sebagai informasi &amp; pengingat.</li>
                    <li>Status capaian: <b class="text-emerald-600">&ge;100% Tuntas</b>, <b class="text-sky-600">60–99% Berjalan</b>, <b class="text-rose-600">&lt;60% Perlu Perhatian</b>.</li>
                    <li>Grafik bulanan pada dashboard diperbarui dari realisasi per bulan (Jan–Des) di berkas.</li>
                    <li>Anda juga dapat menambah atau menyunting rincian data per baris secara manual.</li>
                </ul>
            </div>
        </div>
    </div>

</div>

{{-- Modal Tambah Kegiatan Manual --}}
{{-- ===== BAGIAN: MODAL TAMBAH/HAPUS KINERJA ===== --}}
<div id="modal-kinerja" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 backdrop-blur-sm p-4 grid place-items-center overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-2xl w-full max-w-xl p-6 shadow-2xl text-sm">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-base text-slate-900">Tambah Kegiatan Manual</h3>
                <p class="text-xs text-slate-400 mt-0.5">Input baris program, kegiatan, atau sub kegiatan dinas baru.</p>
            </div>
            <button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400 grid place-items-center transition">✕</button>
        </div>
        @if ($errors->any())
            <div class="mb-3 rounded-xl bg-rose-50 border border-rose-200 px-3.5 py-2.5 text-rose-700 text-xs font-medium">
                {{ $errors->first() }}
            </div>
        @endif
        <form method="POST" action="{{ route('admin.kinerja.store') }}" class="space-y-3.5">
            @csrf
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nama Kegiatan *</label>
                <input name="nama" placeholder="Contoh: Pengelolaan Destinasi Pariwisata Kabupaten" required 
                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tahun Anggaran</label>
                    <select name="tahun" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm bg-white focus:outline-none focus:border-purple-400">
                        @foreach (range(now()->year + 1, now()->year - 3) as $ty)
                            <option value="{{ $ty }}" @selected($ty == $tahun)>TA {{ $ty }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tingkat Level</label>
                    <select name="level" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm bg-white focus:outline-none focus:border-purple-400">
                        <option value="sub_kegiatan">Sub Kegiatan</option>
                        <option value="kegiatan">Kegiatan</option>
                        <option value="program">Program</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Sumber Dana</label>
                    <input name="sumber_dana" placeholder="DAU / DBH / DAK" 
                           class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Pagu Anggaran (Rp)</label>
                    <input type="number" step="1" name="pagu_anggaran" placeholder="mis. 50000000" 
                           class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Indikator Kinerja</label>
                <input name="indikator" placeholder="Indikator tolok ukur hasil kegiatan" 
                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Target</label>
                    <input type="number" step="any" name="target" placeholder="Target" 
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-purple-400">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Satuan</label>
                    <input name="satuan" placeholder="Dokumen/Keg" 
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-purple-400">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Capaian</label>
                    <input type="number" step="any" name="capaian" placeholder="Realisasi" 
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-purple-400">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Progres %</label>
                    <input type="number" step="any" name="persentase" placeholder="%" 
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:border-purple-400">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Pelaksana Bidang</label>
                <select name="id_bidang" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm bg-white focus:outline-none focus:border-purple-400">
                    <option value="">— Pelaksana (Seluruh Dinas) —</option>
                    @foreach ($bidangs as $bd)
                        <option value="{{ $bd->id }}">{{ $bd->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Catatan / Kendala Pelaksanaan</label>
                <textarea name="catatan" rows="2" placeholder="Catatan permasalahan atau progres kegiatan..." 
                          class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition"></textarea>
            </div>

            <button class="w-full rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 active:scale-[.98] transition shadow-sm shadow-purple-500/30">
                Simpan Kegiatan
            </button>
        </form>
    </div>
</div>
@endsection

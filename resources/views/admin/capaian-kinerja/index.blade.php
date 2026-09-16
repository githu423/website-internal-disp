@extends('layouts.dashboard')

@section('title', 'Kelola Capaian Kinerja')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Matriks Sasaran Strategis, Indikator, dan Target/Realisasi per Tahun')

@section('content')
@php
    $u = auth()->user();
@endphp

<div class="space-y-6">

    <!-- 1. Breadcrumbs Mikro -->
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Admin — Data</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Kelola Capaian Kinerja</span>
    </nav>

    <!-- 2. Header Halaman & Quick Actions -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Kelola Capaian Kinerja &amp; Sasaran</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                    {{ count($sasarans) }} Sasaran Strategis
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Matriks sasaran strategis &rarr; indikator &rarr; target/realisasi per tahun dinas. Ubah manual atau impor berkas resmi.</p>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('kinerja.capaian') }}" 
               class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2.5 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl shadow-sm hover:bg-slate-50 hover:border-purple-300 hover:text-purple-600 active:scale-95 transition-all duration-150">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                Matriks Publik
            </a>
            <a href="{{ route('kinerja.capaian', ['tampilan' => 'statistik']) }}" 
               class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2.5 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl shadow-sm hover:bg-slate-50 hover:border-purple-300 hover:text-purple-600 active:scale-95 transition-all duration-150">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Grafik Tren
            </a>
        </div>
    </div>

    <!-- 3. Top Action Grid: Impor Berkas & Tambah Sasaran Baru -->
    <div class="grid lg:grid-cols-2 gap-5">
        <!-- Card 1: Impor Berkas Capaian -->
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-start gap-3 mb-2">
                    <span class="h-9 w-9 rounded-xl bg-purple-50 text-purple-600 border border-purple-100 grid place-items-center shrink-0">
                        <x-icon name="upload" class="h-4 w-4" />
                    </span>
                    <div>
                        <h3 class="font-bold text-[15px] text-slate-900">Impor Berkas Capaian Kinerja</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Format berkas resmi <code>CAPAIAN KINERJA.csv</code> / Excel: No | Sasaran | Indikator | Satuan | Target | Realisasi | %
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.capaian.import') }}" enctype="multipart/form-data" data-import class="space-y-3 mt-3">
                    @csrf
                    <div class="flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-200/60">
                        <label class="flex items-center gap-1.5 font-semibold cursor-pointer">
                            <input type="radio" name="mode" value="update" checked class="rounded-full border-slate-300 text-purple-600 focus:ring-purple-500/20"> 
                            <span>Gabungkan &amp; perbarui <span class="font-normal text-slate-400">(disarankan)</span></span>
                        </label>
                        <label class="flex items-center gap-1.5 font-semibold cursor-pointer">
                            <input type="radio" name="mode" value="ganti" class="rounded-full border-slate-300 text-purple-600 focus:ring-purple-500/20"> 
                            <span>Ganti total <span class="font-normal text-slate-400">(reset ulang)</span></span>
                        </label>
                    </div>

                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                        <input type="file" name="file" accept=".csv,.xlsx,.xls,.ods,.xlsb,.txt" required 
                               class="flex-1 min-w-[200px] text-xs text-slate-500 file:mr-2.5 file:rounded-xl file:border-0 file:bg-purple-600 file:hover:bg-purple-700 file:px-3 file:py-2 file:text-xs file:font-bold file:text-white file:cursor-pointer cursor-pointer border border-slate-200 rounded-xl p-1 bg-white">
                        <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 active:scale-[.98] text-white text-xs md:text-sm font-semibold rounded-xl shadow-sm shadow-purple-500/30 transition whitespace-nowrap">
                            Impor Berkas
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card 2: Tambah Sasaran Baru -->
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-start gap-3 mb-2">
                    <span class="h-9 w-9 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 grid place-items-center shrink-0">
                        <x-icon name="plus" class="h-4 w-4" />
                    </span>
                    <div>
                        <h3 class="font-bold text-[15px] text-slate-900">Tambah Sasaran Strategis Baru</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Entri sasaran utama dinas sebelum menambahkan indikator dan nilai tahunan.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.capaian.sasaran.store') }}" class="space-y-3 mt-3 text-xs">
                    @csrf
                    <div class="flex gap-2.5">
                        <div class="w-16 shrink-0">
                            <label class="block font-bold uppercase tracking-wider text-slate-400 mb-1">No.</label>
                            <input name="nomor" type="number" min="1" placeholder="Auto" 
                                   class="w-full rounded-xl border border-slate-200 px-2.5 py-1.5 text-sm focus:outline-none focus:border-purple-400">
                        </div>
                        <div class="flex-1">
                            <label class="block font-bold uppercase tracking-wider text-slate-400 mb-1">Judul Sasaran Strategis *</label>
                            <input name="judul" required maxlength="500" placeholder="Contoh: Meningkatnya Kunjungan Wisatawan" 
                                   class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-sm focus:outline-none focus:border-purple-400">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block font-bold uppercase tracking-wider text-slate-400 mb-1">Cakupan Tahun</label>
                            <input name="cakupan" maxlength="160" placeholder="mis. 2024–2026" 
                                   class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                        </div>
                        <div>
                            <label class="block font-bold uppercase tracking-wider text-slate-400 mb-1">Bidang Pelaksana</label>
                            <select name="id_bidang" class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:border-purple-400">
                                <option value="">Lintas Dinas / Semua</option>
                                @foreach ($bidangs as $bd)
                                    <option value="{{ $bd->id }}">{{ $bd->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="w-full px-4 py-2 bg-slate-900 hover:bg-slate-800 active:scale-[.98] text-white text-xs md:text-sm font-semibold rounded-xl shadow-sm transition">
                        + Tambah Sasaran
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- 4. Daftar Sasaran & Indikator Kinerja -->
    <div class="space-y-4">
        @forelse ($sasarans as $s)
            <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
                <!-- Header Sasaran -->
                <div class="px-5 py-3.5 flex flex-wrap items-center gap-3 border-b border-slate-100 bg-slate-50/75">
                    <span class="h-8 w-8 rounded-xl bg-gradient-to-br from-purple-600 to-indigo-600 text-white font-black grid place-items-center shadow-sm shadow-purple-500/20 text-sm shrink-0">
                        {{ $s->nomor }}
                    </span>
                    <div class="flex-1 min-w-[200px]">
                        <div class="font-bold text-[14px] text-slate-900 leading-snug">{{ $s->judul }}</div>
                        <div class="mt-1 flex flex-wrap items-center gap-1.5">
                            <span class="inline-block rounded-md px-2 py-0.5 text-[10px] font-extrabold {{ $s->id_bidang ? 'bg-sky-50 text-sky-700 border border-sky-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                {{ $s->bidang?->nama ?: 'LINTAS DINAS' }}
                            </span>
                            @if ($s->cakupan)
                                <span class="text-[11px] text-slate-400 font-medium">TA: {{ $s->cakupan }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Actions Sasaran: Edit & Hapus -->
                    <div class="flex items-center gap-2">
                        <details class="relative">
                            <summary class="cursor-pointer list-none rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-purple-300 hover:text-purple-700 transition">
                                Ubah ▾
                            </summary>
                            <form method="POST" action="{{ route('admin.capaian.sasaran.update', $s) }}" class="absolute right-0 z-20 mt-1.5 w-80 rounded-2xl border border-slate-200 bg-white p-4 shadow-xl text-xs space-y-2.5">
                                @csrf @method('PATCH')
                                <div class="flex gap-2">
                                    <div class="w-16">
                                        <label class="block font-bold text-slate-500 mb-1">No.</label>
                                        <input name="nomor" value="{{ $s->nomor }}" type="number" class="w-full rounded-xl border border-slate-200 px-2 py-1.5 text-sm focus:outline-none focus:border-purple-400">
                                    </div>
                                    <div class="flex-1">
                                        <label class="block font-bold text-slate-500 mb-1">Cakupan</label>
                                        <input name="cakupan" value="{{ $s->cakupan }}" class="w-full rounded-xl border border-slate-200 px-2.5 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-500 mb-1">Judul Sasaran</label>
                                    <input name="judul" value="{{ $s->judul }}" required class="w-full rounded-xl border border-slate-200 px-2.5 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-500 mb-1">Bidang Pelaksana</label>
                                    <select name="id_bidang" class="w-full rounded-xl border border-slate-200 px-2.5 py-1.5 text-xs bg-white focus:outline-none focus:border-purple-400">
                                        <option value="">Lintas Dinas / Semua</option>
                                        @foreach ($bidangs as $bd)
                                            <option value="{{ $bd->id }}" @selected($s->id_bidang == $bd->id)>{{ $bd->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="w-full rounded-xl bg-purple-600 py-2 text-xs font-bold text-white hover:bg-purple-700 active:scale-95 transition shadow-sm shadow-purple-500/30">
                                    Simpan Perubahan
                                </button>
                            </form>
                        </details>

                        <form method="POST" action="{{ route('admin.capaian.sasaran.destroy', $s) }}" onsubmit="return confirm('Hapus sasaran ini beserta seluruh indikator & nilainya?')">
                            @csrf @method('DELETE')
                            <button class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-100 transition">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Tabel Indikator -->
                <div class="overflow-x-auto thin-scroll">
                    <table class="data w-full min-w-[720px]">
                        <thead>
                            <tr class="text-left bg-slate-50/50 border-b border-slate-200 text-[11px] uppercase tracking-wider font-extrabold text-slate-500">
                                <th class="w-10 text-center py-2.5 px-3">#</th>
                                <th class="py-2.5 px-3">Indikator Kinerja</th>
                                <th class="w-28 py-2.5 px-3">Satuan</th>
                                <th class="w-60 py-2.5 px-3">Tahun Terakhir</th>
                                <th class="w-24 text-right py-2.5 px-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            @forelse ($s->indikator as $i => $ind)
                                @php $terakhir = $ind->tahunan->sortByDesc('tahun')->first(); @endphp
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="text-slate-400 text-center text-xs font-mono py-3 px-3">{{ $i + 1 }}</td>
                                    <td class="font-medium text-slate-800 text-[13px] py-3 px-3">{{ $ind->nama }}</td>
                                    <td class="text-slate-500 text-xs py-3 px-3">{{ $ind->satuan ?: '—' }}</td>
                                    <td class="py-3 px-3 text-xs">
                                        @if ($terakhir)
                                            <span class="font-semibold text-slate-500">TA {{ $terakhir->tahun }}:</span>
                                            <span class="font-bold text-slate-900 ml-1">{{ $terakhir->realisasi ?? '—' }}</span>
                                            @php $num = \App\Models\CapaianIndikator::persenNum($terakhir->persen); @endphp
                                            @if ($num !== null)
                                                <span class="ml-1 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold border {{ $num >= 100 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($num >= 80 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200') }}">
                                                    {{ $terakhir->persen }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-slate-300">Belum ada nilai</span>
                                        @endif
                                    </td>
                                    <td class="text-right py-3 px-4 whitespace-nowrap">
                                        <form method="POST" action="{{ route('admin.capaian.indikator.destroy', $ind) }}" class="inline" onsubmit="return confirm('Hapus indikator ini?')">
                                            @csrf @method('DELETE')
                                            <button class="px-2 py-1 text-xs font-bold rounded-lg text-rose-600 hover:bg-rose-50 transition">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Form Inline: Atur Nilai per Tahun -->
                                <tr class="bg-slate-50/40 border-t border-slate-100">
                                    <td colspan="5" class="py-2.5 px-4">
                                        <details class="group/nilai">
                                            <summary class="cursor-pointer list-none text-xs font-bold text-purple-700 hover:text-purple-800 transition inline-flex items-center gap-1.5">
                                                <span>✏️</span> Atur nilai target &amp; realisasi per tahun / sunting nama indikator ▾
                                            </summary>
                                            <form method="POST" action="{{ route('admin.capaian.nilai', $ind) }}" class="mt-3 p-3.5 rounded-xl border border-slate-200 bg-white space-y-3">
                                                @csrf
                                                <div class="overflow-x-auto thin-scroll">
                                                    <table class="data w-full min-w-[560px] text-xs">
                                                        <thead>
                                                            <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-200">
                                                                <th class="w-16 py-1.5 px-2">Tahun</th>
                                                                <th class="py-1.5 px-2">Target</th>
                                                                <th class="py-1.5 px-2">Realisasi</th>
                                                                <th class="w-28 py-1.5 px-2">Persen (%)</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-slate-100">
                                                            @php $by = $ind->tahunan->keyBy('tahun'); @endphp
                                                            @foreach ($years as $y)
                                                                @php $nn = $by->get($y); @endphp
                                                                <tr>
                                                                    <td class="font-bold text-slate-700 py-1.5 px-2">TA {{ $y }}</td>
                                                                    <td class="py-1.5 px-2">
                                                                        <input name="nilai[{{ $y }}][target]" value="{{ $nn?->target }}" maxlength="120" placeholder="Target" 
                                                                               class="w-full rounded-lg border border-slate-200 px-2.5 py-1 text-xs focus:border-purple-400 focus:outline-none">
                                                                    </td>
                                                                    <td class="py-1.5 px-2">
                                                                        <input name="nilai[{{ $y }}][realisasi]" value="{{ $nn?->realisasi }}" maxlength="120" placeholder="Realisasi" 
                                                                               class="w-full rounded-lg border border-slate-200 px-2.5 py-1 text-xs focus:border-purple-400 focus:outline-none">
                                                                    </td>
                                                                    <td class="py-1.5 px-2">
                                                                        <input name="nilai[{{ $y }}][persen]" value="{{ $nn?->persen }}" maxlength="24" placeholder="%" 
                                                                               class="w-full rounded-lg border border-slate-200 px-2.5 py-1 text-xs focus:border-purple-400 focus:outline-none">
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                            <tr class="border-t-2 border-dashed border-slate-200 bg-purple-50/20">
                                                                <td class="py-1.5 px-2">
                                                                    <select name="t_tahun" class="w-full rounded-lg border border-slate-200 px-1 py-1 text-xs bg-white focus:outline-none focus:border-purple-400">
                                                                        <option value="">+ Tambah Tahun</option>
                                                                        @foreach ($tambahYears as $yy)
                                                                            @if (!$by->has($yy))
                                                                                <option value="{{ $yy }}">{{ $yy }}</option>
                                                                            @endif
                                                                        @endforeach
                                                                    </select>
                                                                </td>
                                                                <td class="py-1.5 px-2">
                                                                    <input name="t_target" placeholder="Target baru" maxlength="120" 
                                                                           class="w-full rounded-lg border border-slate-200 px-2.5 py-1 text-xs focus:border-purple-400 focus:outline-none">
                                                                </td>
                                                                <td class="py-1.5 px-2">
                                                                    <input name="t_realisasi" placeholder="Realisasi baru" maxlength="120" 
                                                                           class="w-full rounded-lg border border-slate-200 px-2.5 py-1 text-xs focus:border-purple-400 focus:outline-none">
                                                                </td>
                                                                <td class="py-1.5 px-2">
                                                                    <input name="t_persen" placeholder="% baru" maxlength="24" 
                                                                           class="w-full rounded-lg border border-slate-200 px-2.5 py-1 text-xs focus:border-purple-400 focus:outline-none">
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <div class="flex flex-col sm:flex-row items-end gap-2.5 pt-2 border-t border-slate-100">
                                                    <div class="flex-1 w-full">
                                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Nama Indikator</label>
                                                        <input name="nama" value="{{ $ind->nama }}" 
                                                               class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                                    </div>
                                                    <div class="w-full sm:w-32">
                                                        <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Satuan</label>
                                                        <input name="satuan" value="{{ $ind->satuan }}" 
                                                               class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                                    </div>
                                                    <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-purple-600 hover:bg-purple-700 active:scale-95 text-white text-xs font-bold rounded-xl shadow-sm shadow-purple-500/30 transition whitespace-nowrap">
                                                        Simpan Nilai &amp; Indikator
                                                    </button>
                                                </div>
                                                <p class="text-[10.5px] text-slate-400">💡 Kosongkan semua sel target &amp; realisasi suatu tahun lalu klik Simpan untuk menghapus baris tahun tersebut.</p>
                                            </form>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-3 px-4 text-slate-400 text-xs italic">
                                        Belum ada indikator terdaftar — tambahkan di bawah.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Form Tambah Indikator Baru -->
                <form method="POST" action="{{ route('admin.capaian.indikator.store') }}" class="px-5 py-3 flex flex-wrap items-center gap-2.5 border-t border-slate-100 bg-slate-50/50">
                    @csrf
                    <input type="hidden" name="sasaran_id" value="{{ $s->id }}">
                    <input name="nama" placeholder="Nama indikator baru…" required maxlength="500" 
                           class="flex-1 min-w-[200px] rounded-xl border border-slate-200 px-3.5 py-1.5 text-xs focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition bg-white">
                    <input name="satuan" placeholder="Satuan (mis. % / Dokumen)" maxlength="80" 
                           class="w-44 rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400 transition bg-white">
                    <button type="submit" class="px-3.5 py-1.5 rounded-xl border border-purple-300 bg-purple-50 text-purple-700 hover:bg-purple-100 text-xs font-bold active:scale-95 transition shadow-sm">
                        + Tambah Indikator
                    </button>
                </form>
            </div>
        @empty
            <div class="bg-white border border-slate-200 rounded-xl p-10 text-center shadow-sm">
                <x-empty icon="kinerja" title="Belum ada data capaian kinerja" desc="Impor berkas resmi di atas, atau tambahkan sasaran secara manual di bagian atas." />
            </div>
        @endforelse
    </div>

</div>
@endsection

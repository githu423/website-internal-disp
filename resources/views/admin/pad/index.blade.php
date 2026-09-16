@extends('layouts.dashboard')

@section('title', 'Kelola PAD')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Manajemen Pendapatan Asli Daerah (Retribusi) Kabupaten Ciamis')

@section('content')
@php 
    $fmt = fn ($v) => number_format((float) $v, 0, ',', '.'); 
@endphp

<div class="space-y-6">

    <!-- 1. Breadcrumbs Mikro -->
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Admin — Data</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Kelola PAD</span>
    </nav>

    <!-- 2. Header Halaman & Quick Actions -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Data PAD — Retribusi Daerah</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                    TA {{ $tahun }}
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">{{ $rows->count() }} objek pendapatan terdata · Pembukuan target, perubahan, realisasi, dan kumulatif kas daerah.</p>
        </div>

        <!-- Quick Actions -->
        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Pilihan Tahun (Segmented Pill) -->
            <div class="inline-flex items-center bg-white border border-slate-200 rounded-xl p-1 shadow-sm">
                @foreach ($tahunList as $t)
                    <a href="{{ route('admin.pad.index', ['tahun' => $t]) }}" 
                       class="px-3 py-1.5 text-xs font-bold rounded-lg transition {{ $t == $tahun ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-600 hover:text-purple-600 hover:bg-slate-50' }}">
                        {{ $t }}
                    </a>
                @endforeach
            </div>

            <!-- Tombol Sekunder: Lihat Publik -->
            <a href="{{ route('pad.index', ['tahun' => $tahun]) }}" 
               class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2.5 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl shadow-sm hover:bg-slate-50 hover:border-purple-300 hover:text-purple-600 active:scale-95 transition-all duration-150">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Lihat Publik
            </a>

            <!-- Tombol Utama: Tambah Manual -->
            <button data-modal-open="modal-pad" 
                    class="cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 text-white text-sm font-semibold rounded-xl shadow-sm shadow-purple-500/30 hover:bg-purple-700 hover:shadow-purple-600/40 active:scale-95 transition-all duration-150">
                <x-icon name="plus" class="h-4 w-4" />
                Tambah Objek PAD
            </button>
        </div>
    </div>

    <!-- 3. Konten Grid: Tabel Data & Panel Impor/Panduan -->
    <div class="grid lg:grid-cols-3 gap-5">
        <!-- Kolom Kiri: Tabel Data PAD -->
        <div class="lg:col-span-2">
            <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
                <div class="overflow-x-auto thin-scroll">
                    <table class="data w-full min-w-[760px]">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-[11px] uppercase tracking-wider font-extrabold">
                                <th class="py-3 px-4">Objek &amp; Rekening</th>
                                <th class="py-3 px-3 text-right">Target</th>
                                <th class="py-3 px-3 text-right">Perubahan</th>
                                <th class="py-3 px-3 text-right">Kumulatif</th>
                                <th class="py-3 px-3 text-right">Sisa Target</th>
                                <th class="py-3 px-3 min-w-[130px]">Progres (%)</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $r)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4">
                                    <p class="font-semibold text-[13px] text-slate-900 leading-snug">{{ $r->objek }}</p>
                                    <p class="text-[11px] text-slate-400 font-mono mt-0.5">{{ $r->kategori ?: 'Retribusi' }} · {{ $r->no_rekening ?: '—' }}</p>
                                </td>
                                <td class="py-3 px-3 text-right text-xs tabular-nums text-slate-700">
                                    {{ $fmt($r->target_tahunan) }}
                                </td>
                                <td class="py-3 px-3 text-right text-xs tabular-nums text-sky-700">
                                    {{ $r->target_perubahan > 0 ? $fmt($r->target_perubahan) : '—' }}
                                </td>
                                <td class="py-3 px-3 text-right text-xs tabular-nums font-bold text-slate-900">
                                    {{ $fmt($r->kumulatif) }}
                                </td>
                                <td class="py-3 px-3 text-right text-xs tabular-nums font-bold {{ $r->sisa_target < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                    {{ $fmt($r->sisa_target) }}
                                </td>
                                <td class="py-3 px-3">
                                    <x-prog :persen="$r->persentase" />
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        <button data-modal-open="pad-{{ $r->id }}" 
                                                class="px-2 py-1 text-xs font-bold rounded-lg text-purple-700 hover:bg-purple-50 transition" 
                                                title="Edit">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('admin.pad.destroy', $r) }}" data-confirm="Hapus data PAD ini?" class="inline">
                                            @csrf @method('DELETE')
                                            <button class="px-2 py-1 text-xs font-bold rounded-lg text-rose-600 hover:bg-rose-50 transition" title="Hapus">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            {{-- Modal Edit PAD --}}
                            <div id="pad-{{ $r->id }}" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 backdrop-blur-sm p-4 grid place-items-center overflow-y-auto">
                                <div class="bg-white border border-slate-200 rounded-2xl w-full max-w-md p-6 shadow-2xl text-sm">
                                    <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
                                        <div>
                                            <h3 class="font-extrabold text-base text-slate-900">Edit Data PAD</h3>
                                            <p class="text-xs text-slate-400 mt-0.5">{{ $r->objek }}</p>
                                        </div>
                                        <button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400 grid place-items-center transition">✕</button>
                                    </div>

                                    <form method="POST" action="{{ route('admin.pad.update', $r) }}" class="space-y-3">
                                        @csrf @method('PATCH')
                                        <input name="tahun" type="hidden" value="{{ $r->tahun }}">
                                        
                                        <div>
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Kategori Retribusi</label>
                                            <input name="kategori" value="{{ $r->kategori }}" placeholder="mis. Retribusi Jasa Usaha" 
                                                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                                        </div>

                                        <div>
                                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Objek Pendapatan *</label>
                                            <input name="objek" value="{{ $r->objek }}" required 
                                                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                            <div>
                                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Target Tahunan</label>
                                                <input type="number" step="1" name="target_tahunan" value="{{ (float) $r->target_tahunan }}" 
                                                       class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Target Perubahan</label>
                                                <input type="number" step="1" name="target_perubahan" value="{{ (float) $r->target_perubahan }}" 
                                                       class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Realisasi Bulan Lalu</label>
                                                <input type="number" step="1" name="realisasi_bulan_lalu" value="{{ (float) $r->realisasi_bulan_lalu }}" 
                                                       class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Realisasi Bulan Ini</label>
                                                <input type="number" step="1" name="realisasi_bulan_ini" value="{{ (float) $r->realisasi_bulan_ini }}" 
                                                       class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Kumulatif Realisasi</label>
                                                <input type="number" step="1" name="kumulatif" value="{{ (float) $r->kumulatif }}" 
                                                       class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Persentase (%)</label>
                                                <input type="number" step="any" name="persentase" value="{{ (float) $r->persentase }}" 
                                                       class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                            </div>
                                            <div class="sm:col-span-2">
                                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Sisa Target</label>
                                                <input type="number" step="1" name="sisa_target" value="{{ (float) $r->sisa_target }}" 
                                                       class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                                            </div>
                                        </div>

                                        <button type="submit" class="w-full rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 active:scale-[.98] transition shadow-sm shadow-purple-500/30 mt-2">
                                            Simpan Perubahan
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center">
                                    <x-empty icon="pad" title="Data PAD TA {{ $tahun }} kosong" desc="Impor file PAD pada panel kanan atau tambahkan data secara manual." />
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Panel Impor & Catatan -->
        <div class="space-y-4">
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <div class="flex items-start gap-3.5 mb-3">
                    <span class="h-10 w-10 shrink-0 rounded-xl bg-purple-50 text-purple-600 border border-purple-100 grid place-items-center">
                        <x-icon name="upload" class="h-5 w-5" />
                    </span>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-[15px] text-slate-900">Impor Berkas PAD (Excel/CSV)</h3>
                        <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                            Format file PAD resmi: blok TAHUN + kolom No., Rekening, Uraian, Target, Realisasi Jan–Des, Jumlah, Sisa, %. Seluruh blok tahun ikut diproses.
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.pad.import') }}" enctype="multipart/form-data" data-import class="space-y-3">
                    @csrf
                    <label class="block border-2 border-dashed border-slate-200 rounded-xl p-4 text-center hover:border-purple-300 transition cursor-pointer bg-slate-50/50">
                        <input type="file" name="file" accept=".csv,.xlsx,.xls,.ods" required
                            class="block w-full text-xs text-slate-500 file:mx-auto file:mb-2 file:rounded-lg file:border-0 file:bg-purple-600 file:hover:bg-purple-700 file:px-3 file:py-1.5 file:text-white file:font-bold file:cursor-pointer transition">
                        <span class="text-[11px] text-slate-400 block">CSV, Excel (.xlsx, .xls) maks. 20 MB</span>
                    </label>

                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-500 rounded-xl border border-slate-200 px-3 py-2 bg-slate-50">
                        <input type="checkbox" checked disabled class="rounded border-slate-300 text-purple-600"> 
                        <span>Otomatis mengganti data per tahun yang ditemukan di berkas</span>
                    </label>

                    <button type="submit" class="w-full rounded-xl bg-slate-900 hover:bg-slate-800 active:scale-[.98] disabled:opacity-50 text-white text-xs md:text-sm font-semibold py-2.5 transition shadow-sm">
                        Proses &amp; Impor Berkas
                    </button>
                </form>
            </div>

            <!-- Panduan & Catatan PAD -->
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm text-sm">
                <h3 class="font-extrabold text-[14px] text-slate-900 mb-2.5 flex items-center gap-2">
                    <span>💡</span> Catatan Sistem
                </h3>
                <ul class="space-y-2 text-xs text-slate-500 leading-relaxed list-disc pl-4">
                    <li>Baris <b class="text-slate-700">Jumlah</b> pada file dihitung otomatis oleh sistem untuk menjaga konsistensi kalkulasi.</li>
                    <li>Kategori dari berkas secara otomatis menjadi pengelompokan pada tabel retribusi.</li>
                    <li>Data ini langsung memperbarui kartu statistik <b class="text-purple-700">PAD {{ $tahun }}</b> di Dashboard Utama seluruh pengguna.</li>
                </ul>
            </div>
        </div>
    </div>

</div>

{{-- Modal Tambah PAD Manual --}}
<div id="modal-pad" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 backdrop-blur-sm p-4 grid place-items-center overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-2xl w-full max-w-md p-6 shadow-2xl text-sm">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
            <div>
                <h3 class="font-extrabold text-base text-slate-900">Tambah Data PAD Manual</h3>
                <p class="text-xs text-slate-400 mt-0.5">Input objek retribusi atau pendapatan dinas baru.</p>
            </div>
            <button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400 grid place-items-center transition">✕</button>
        </div>
        @if ($errors->any())
            <div class="mb-3 rounded-xl bg-rose-50 border border-rose-200 px-3.5 py-2.5 text-rose-700 text-xs font-medium">
                {{ $errors->first() }}
            </div>
        @endif
        <form method="POST" action="{{ route('admin.pad.store') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tahun Anggaran</label>
                    <select name="tahun" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs bg-white focus:outline-none focus:border-purple-400">
                        @foreach (range(now()->year + 1, now()->year - 3) as $ty)
                            <option value="{{ $ty }}" @selected($ty == $tahun)>TA {{ $ty }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Kategori</label>
                    <input name="kategori" placeholder="mis. Jasa Usaha" 
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:outline-none focus:border-purple-400">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">No. Rekening</label>
                <input name="no_rekening" placeholder="mis. 4.1.02.01.01.0001" 
                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs font-mono focus:outline-none focus:border-purple-400">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Objek Pendapatan *</label>
                <input name="objek" placeholder="Nama objek retribusi daerah" required 
                       class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Target Tahunan</label>
                    <input type="number" step="1" name="target_tahunan" placeholder="0" 
                           class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Target Perubahan</label>
                    <input type="number" step="1" name="target_perubahan" placeholder="0" 
                           class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Realisasi Nov</label>
                    <input type="number" step="1" name="realisasi_bulan_lalu" placeholder="0" 
                           class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Realisasi Des</label>
                    <input type="number" step="1" name="realisasi_bulan_ini" placeholder="0" 
                           class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Kumulatif Realisasi</label>
                    <input type="number" step="1" name="kumulatif" placeholder="0" 
                           class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Persentase (%)</label>
                    <input type="number" step="any" name="persentase" placeholder="0" 
                           class="w-full rounded-xl border border-slate-200 px-3 py-1.5 text-xs focus:outline-none focus:border-purple-400">
                </div>
            </div>

            <button type="submit" class="w-full rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 active:scale-[.98] transition shadow-sm shadow-purple-500/30 mt-2">
                Simpan Objek PAD
            </button>
        </form>
    </div>
</div>
@endsection

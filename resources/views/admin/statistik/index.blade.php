@extends('layouts.dashboard')

@section('title', 'Kelola Statistik')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Database Statistik Pariwisata Daerah Kabupaten Ciamis')

@section('content')
<div class="space-y-6">

    <!-- 1. Breadcrumbs Mikro -->
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Admin — Data</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Kelola Statistik</span>
    </nav>

    <!-- 2. Header Halaman & Quick Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Statistik Pariwisata Daerah</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                    {{ $total }} Indikator · {{ count($tahunList) }} Tahun
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Impor berkas resmi atau perbarui data indikator &amp; nilai tahunan secara manual.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('statistik.index') }}" 
               class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2.5 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl shadow-sm hover:bg-slate-50 hover:border-purple-300 hover:text-purple-600 active:scale-95 transition-all duration-150">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Lihat Publik
            </a>
        </div>
    </div>

    <!-- 3. Card Impor Berkas Statistik -->
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
        <div class="flex items-start gap-3.5 mb-3">
            <span class="h-10 w-10 shrink-0 rounded-xl bg-purple-50 text-purple-600 border border-purple-100 grid place-items-center">
                <x-icon name="upload" class="h-5 w-5" />
            </span>
            <div class="flex-1 min-w-0">
                <h3 class="font-bold text-[15px] text-slate-900">Impor Berkas Data Statistik Pariwisata</h3>
                <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                    Format file «Data Statistik Pariwisata»: header <i>No. | Indikator | Satuan | Tahun (2017..2025) | Keterangan</i>. Persen, ribuan, dan angka diformat otomatis. Indikator yang sama diperbarui otomatis tanpa duplikasi.
                </p>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.statistik.import') }}" enctype="multipart/form-data" data-import class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 mt-3">
            @csrf
            <label class="flex-1 min-w-0 border border-slate-200 rounded-xl p-1 bg-white cursor-pointer">
                <input type="file" name="file" accept=".csv,.xlsx,.xls,.ods" required
                    class="block w-full text-xs text-slate-500 file:mr-2.5 file:rounded-lg file:border-0 file:bg-purple-600 file:hover:bg-purple-700 file:px-3 file:py-2 file:text-xs file:font-bold file:text-white file:cursor-pointer transition">
            </label>
            <button type="submit" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 active:scale-[.98] disabled:opacity-50 text-white text-xs md:text-sm font-semibold rounded-xl shadow-sm shadow-purple-500/30 transition whitespace-nowrap">
                Proses &amp; Impor Berkas
            </button>
        </form>
    </div>

    <!-- 4. Card Edit & Entri Manual -->
    <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50/75 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="font-bold text-[15px] text-slate-900">Isi / Koreksi Nilai Manual</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Kosongkan sel = hapus nilai tahun itu · centang 🗑 lalu klik Simpan Semua = hapus baris indikator.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.statistik.store') }}">
            @csrf
            @foreach ($tahunList as $t)
                <input type="hidden" name="tahunList[]" value="{{ $t }}">
            @endforeach

            <div class="overflow-x-auto thin-scroll">
                <table class="data w-full min-w-[1040px]">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-[11px] uppercase tracking-wider font-extrabold">
                            <th class="w-12 text-center py-3 px-3">No.</th>
                            <th class="py-3 px-3 min-w-[280px]">Indikator Statistik</th>
                            <th class="w-28 py-3 px-3">Satuan</th>
                            @foreach ($tahunList as $t)
                                <th class="text-right whitespace-nowrap py-3 px-3">{{ $t }}</th>
                            @endforeach
                            <th class="w-40 py-3 px-3">Keterangan</th>
                            <th class="w-10 text-center py-3 px-3">🗑</th>
                        </tr>
                    </thead>
                    <tbody id="st-rows" class="divide-y divide-slate-100">
                    @foreach ($rows as $i => $r)
                        <tr data-i="{{ $i }}" class="hover:bg-slate-50/70 transition">
                            <td class="py-2.5 px-3">
                                <input type="hidden" name="rows[{{ $i }}][id]" value="{{ $r['id'] }}">
                                <input name="rows[{{ $i }}][no_urut]" value="{{ $r['no'] }}" class="st-cell w-10 text-center font-mono">
                            </td>
                            <td class="py-2.5 px-3">
                                <input name="rows[{{ $i }}][indikator]" value="{{ $r['indikator'] }}" class="st-cell w-full font-medium" maxlength="180">
                            </td>
                            <td class="py-2.5 px-3">
                                <input name="rows[{{ $i }}][satuan]" value="{{ $r['satuan'] }}" class="st-cell w-full" maxlength="40">
                            </td>
                            @foreach ($tahunList as $t)
                                <td class="py-2.5 px-3">
                                    <input name="rows[{{ $i }}][nilai][{{ $t }}]" value="{{ $r['nilai'][$t] ?? '' }}" class="st-cell w-24 text-right font-mono">
                                </td>
                            @endforeach
                            <td class="py-2.5 px-3">
                                <input name="rows[{{ $i }}][keterangan]" value="{{ $r['ket'] }}" class="st-cell w-full" maxlength="255">
                            </td>
                            <td class="text-center py-2.5 px-3">
                                <label class="grid place-items-center h-7 w-7 rounded-lg hover:bg-rose-50 cursor-pointer transition" title="Hapus baris ini saat disimpan">
                                    <input type="checkbox" name="rows[{{ $i }}][hapus]" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500/20">
                                </label>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <template id="st-new">
                <tr class="hover:bg-slate-50/70 transition bg-purple-50/15">
                    <td class="py-2.5 px-3">
                        <input name="rows[__N__][no_urut]" class="st-cell w-10 text-center font-mono">
                    </td>
                    <td class="py-2.5 px-3">
                        <input name="rows[__N__][indikator]" class="st-cell w-full font-medium" placeholder="Nama indikator baru…">
                    </td>
                    <td class="py-2.5 px-3">
                        <input name="rows[__N__][satuan]" class="st-cell w-full" placeholder="Satuan">
                    </td>
                    @foreach ($tahunList as $t)
                        <td class="py-2.5 px-3">
                            <input name="rows[__N__][nilai][{{ $t }}]" class="st-cell w-24 text-right font-mono">
                        </td>
                    @endforeach
                    <td class="py-2.5 px-3">
                        <input name="rows[__N__][keterangan]" class="st-cell w-full" placeholder="Keterangan">
                    </td>
                    <td class="text-center py-2.5 px-3"></td>
                </tr>
            </template>

            <div class="p-4 flex flex-wrap items-center gap-3 border-t border-slate-100 bg-slate-50/50">
                <button type="submit" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 active:scale-95 text-white text-xs md:text-sm font-semibold rounded-xl shadow-sm shadow-purple-500/30 transition">
                    Simpan Semua Perubahan
                </button>
                <button type="button" id="st-tambah" class="rounded-xl border border-dashed border-purple-300 bg-purple-50 hover:bg-purple-100 px-4 py-2 text-xs md:text-sm font-semibold text-purple-700 transition active:scale-95">
                    ➕ Tambah Indikator Baru
                </button>
                @if ($total === 0)
                    <p class="text-xs text-slate-400 ml-2">Belum ada baris — tambah indikator di sini atau gunakan berkas impor di atas.</p>
                @endif
            </div>
        </form>
    </div>

</div>

<style>
.st-cell { 
    height: 32px; 
    border: 1px solid #e2e8f0; 
    border-radius: 8px; 
    background: #ffffff; 
    padding: 0 8px; 
    font-size: 12px; 
    outline: none; 
    transition: all .15s ease;
}
.st-cell:focus { 
    border-color: #a855f7; 
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.15); 
    background: #fff; 
}
</style>
<script>
(function () {
    const tb = document.getElementById('st-rows'), tp = document.getElementById('st-new');
    if (!tb || !tp) return;
    let n = tb.querySelectorAll('tr[data-i]').length;
    document.getElementById('st-tambah')?.addEventListener('click', () => {
        const tr = document.createElement('tr'); 
        tr.dataset.i = String(n);
        tr.className = 'hover:bg-slate-50/70 transition bg-purple-50/15';
        tr.innerHTML = tp.innerHTML.replaceAll('__N__', String(n++));
        tb.appendChild(tr);
        tr.querySelector('input:not([type=hidden])')?.focus();
    });
})();
</script>
@endsection

@extends('layouts.dashboard')
@section('title', 'Kelola Statistik')
@section('content')
<div class="mb-4">
    <h2 class="text-lg font-extrabold">Statistik Pariwisata
        <span class="text-xs font-bold text-slate-400 bg-slate-100 rounded-full px-2 py-1 align-middle">{{ $total }} indikator · {{ count($tahunList) }} tahun</span>
    </h2>
</div>

<x-import-card :action="route('admin.statistik.import')" title="Import Data Statistik (Excel/CSV)"
    desc="Format file &quot;Data Statistik Pariwisata&quot;: baris judul bebas, header No. | Indikator | Satuan | Tahun | Keterangan, baris daftar tahun (mis. 2017..2025), lalu baris datanya. Persen (22.07%), ribuan (1,173,275,000), dan &quot;n/a&quot; dikenali otomatis. Indikator dengan nama sama DIPERBARUI — tidak dobel." />

<div class="card mt-4 overflow-hidden">
    <div class="px-5 pt-4 pb-3 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
        <h3 class="font-bold">Isi / koreksi manual</h3>
        <p class="text-[11px] text-slate-400">Kosongkan sel = hapus nilai tahun itu · centang 🗑 lalu Simpan = hapus baris</p>
    </div>
    <form method="POST" action="{{ route('admin.statistik.store') }}">
        @csrf
        @foreach ($tahunList as $t)<input type="hidden" name="tahunList[]" value="{{ $t }}">@endforeach
        <div class="overflow-x-auto thin-scroll">
            <table class="data w-full min-w-[1040px]">
                <thead><tr>
                    <th class="w-12">No.</th><th>Indikator</th><th class="w-24">Satuan</th>
                    @foreach ($tahunList as $t)<th class="text-right whitespace-nowrap">{{ $t }}</th>@endforeach
                    <th class="w-36">Keterangan</th><th class="w-9"></th>
                </tr></thead>
                <tbody id="st-rows">
                @foreach ($rows as $i => $r)
                    <tr data-i="{{ $i }}">
                        <td><input type="hidden" name="rows[{{ $i }}][id]" value="{{ $r['id'] }}"><input name="rows[{{ $i }}][no_urut]" value="{{ $r['no'] }}" class="st-cell w-10 text-center"></td>
                        <td><input name="rows[{{ $i }}][indikator]" value="{{ $r['indikator'] }}" class="st-cell min-w-[280px]" maxlength="180"></td>
                        <td><input name="rows[{{ $i }}][satuan]" value="{{ $r['satuan'] }}" class="st-cell w-20" maxlength="40"></td>
                        @foreach ($tahunList as $t)<td><input name="rows[{{ $i }}][nilai][{{ $t }}]" value="{{ $r['nilai'][$t] ?? '' }}" class="st-cell w-24 text-right font-mono"></td>@endforeach
                        <td><input name="rows[{{ $i }}][keterangan]" value="{{ $r['ket'] }}" class="st-cell w-32" maxlength="255"></td>
                        <td class="text-center"><label class="grid place-items-center h-6 w-6 rounded-md hover:bg-rose-50 cursor-pointer" title="Hapus baris ini saat disimpan"><input type="checkbox" name="rows[{{ $i }}][hapus]" value="1" class="accent-rose-600"></label></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <template id="st-new">
            <tr>
                <td><input name="rows[__N__][no_urut]" class="st-cell w-10 text-center"></td>
                <td><input name="rows[__N__][indikator]" class="st-cell min-w-[280px]" placeholder="Nama indikator baru…"></td>
                <td><input name="rows[__N__][satuan]" class="st-cell w-20"></td>
                @foreach ($tahunList as $t)<td><input name="rows[__N__][nilai][{{ $t }}]" class="st-cell w-24 text-right font-mono"></td>@endforeach
                <td><input name="rows[__N__][keterangan]" class="st-cell w-32"></td><td></td>
            </tr>
        </template>
        <div class="p-3 flex flex-wrap items-center gap-2 border-t border-slate-100">
            <button class="btn-main">Simpan semua</button>
            <button type="button" id="st-tambah" class="rounded-lg border border-dashed border-teal-400 bg-teal-50/50 hover:bg-teal-50 px-3 py-2 text-sm font-bold text-teal-700 transition">➕ Tambah indikator</button>
            @if ($total === 0)<p class="text-[12px] text-slate-400">Belum ada baris — tambah manual di sini, atau gunakan import file di atas.</p>@endif
        </div>
    </form>
</div>

<style>
.st-cell { height: 30px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f8fafc; padding: 0 6px; font-size: 12px; outline: none; width: 100%; }
.st-cell:focus { border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20,184,166,.15); background: #fff; }
</style>
<script>
(function () {
    const tb = document.getElementById('st-rows'), tp = document.getElementById('st-new');
    if (!tb || !tp) return;
    let n = tb.querySelectorAll('tr[data-i]').length;
    document.getElementById('st-tambah')?.addEventListener('click', () => {
        const tr = document.createElement('tr'); tr.dataset.i = String(n);
        tr.innerHTML = tp.innerHTML.replaceAll('__N__', String(n++));
        tb.appendChild(tr);
        tr.querySelector('input:not([type=hidden])')?.focus();
    });
})();
</script>
@endsection

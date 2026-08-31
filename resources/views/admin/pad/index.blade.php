@extends('layouts.dashboard')
@section('title', 'Kelola PAD')
@section('content')
@php $fmt = fn ($v) => number_format((float) $v, 0, ',', '.'); @endphp
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h2 class="text-lg font-extrabold">Data PAD — Retribusi Daerah <span class="text-slate-400">TA {{ $tahun }}</span></h2>
        <p class="text-sm text-slate-500">{{ $rows->count() }} objek pendapatan tahun ini · manual &amp; import Excel/CSV tersusun otomatis.</p>
    </div>
    <div class="flex items-center gap-2">
        @foreach ($tahunList as $t)
            <a href="{{ route('admin.pad.index', ['tahun' => $t]) }}" @class(['rounded-lg px-3 py-1.5 text-sm font-bold border transition active:scale-95', $t == $tahun ? 'bg-slate-900 text-white border-slate-900' : 'bg-white border-slate-200 text-slate-600 hover:border-teal-400'])>{{ $t }}</a>
        @endforeach
        <button data-modal-open="modal-pad" class="rounded-lg bg-teal-600 hover:bg-teal-700 active:scale-95 text-white text-sm font-bold px-3.5 py-1.5 transition inline-flex items-center gap-1.5"><x-icon name="plus" class="h-4 w-4" /> Tambah manual</button>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 card overflow-hidden">
        <div class="overflow-x-auto thin-scroll max-h-[70vh]">
            <table class="data w-full min-w-[760px]">
                <thead><tr><th>Objek / Rekening</th><th class="text-right">Target</th><th class="text-right">Perubahan</th><th class="text-right">Kumulatif</th><th class="text-right">Sisa</th><th class="w-28">%</th><th></th></tr></thead>
                <tbody>
                @forelse ($rows as $r)
                    <tr>
                        <td><p class="font-semibold text-[13px]">{{ $r->objek }}</p><p class="text-[11px] text-slate-400">{{ $r->kategori }} · {{ $r->no_rekening }}</p></td>
                        <td class="text-right text-xs tabular-nums">{{ $fmt($r->target_tahunan) }}</td>
                        <td class="text-right text-xs tabular-nums text-sky-600">{{ $r->target_perubahan > 0 ? $fmt($r->target_perubahan) : '—' }}</td>
                        <td class="text-right text-xs tabular-nums font-bold">{{ $fmt($r->kumulatif) }}</td>
                        <td class="text-right text-xs tabular-nums {{ $r->sisa_target < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $fmt($r->sisa_target) }}</td>
                        <td><x-prog :persen="$r->persentase" /></td>
                        <td class="text-right whitespace-nowrap">
                            <button data-modal-open="pad-{{ $r->id }}" class="text-[11px] font-bold text-slate-500 hover:text-teal-600">Edit</button>
                            <form method="POST" action="{{ route('admin.pad.destroy', $r) }}" data-confirm="Hapus baris ini?" class="inline">@csrf @method('DELETE')
                                <button class="text-[11px] font-bold text-rose-400 hover:text-rose-600">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <div id="pad-{{ $r->id }}" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 p-4 grid place-items-center overflow-y-auto">
                        <div class="card w-full max-w-md p-6 text-sm">
                            <div class="flex justify-between mb-3"><h3 class="font-bold">Edit: {{ $r->objek }}</h3><button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400">✕</button></div>
                            <form method="POST" action="{{ route('admin.pad.update', $r) }}" class="space-y-2.5">
                                @csrf @method('PATCH')
                                <input name="tahun" type="hidden" value="{{ $r->tahun }}">
                                <input name="kategori" value="{{ $r->kategori }}" placeholder="Kategori retribusi" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                <input name="objek" value="{{ $r->objek }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Objek">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <input type="number" step="1" name="target_tahunan" value="{{ (float) $r->target_tahunan }}" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Target">
                                    <input type="number" step="1" name="target_perubahan" value="{{ (float) $r->target_perubahan }}" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Perubahan">
                                    <input type="number" step="1" name="realisasi_bulan_lalu" value="{{ (float) $r->realisasi_bulan_lalu }}" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Nov">
                                    <input type="number" step="1" name="realisasi_bulan_ini" value="{{ (float) $r->realisasi_bulan_ini }}" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Des">
                                    <input type="number" step="1" name="kumulatif" value="{{ (float) $r->kumulatif }}" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="Kumulatif">
                                    <input type="number" step="any" name="persentase" value="{{ (float) $r->persentase }}" class="rounded-lg border border-slate-300 px-3 py-2" placeholder="%">
                                    <input type="number" step="1" name="sisa_target" value="{{ (float) $r->sisa_target }}" class="rounded-lg border border-slate-300 px-3 py-2 col-span-2" placeholder="Sisa target">
                                </div>
                                <button class="w-full rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5">Simpan</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <tr><td colspan="7"><x-empty icon="pad" title="TA {{ $tahun }} kosong" desc="Import file PAD (panel kanan) atau tambah manual."></x-empty></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-4">
        <x-import-card :action="route('admin.pad.import')" title="Import file PAD (Excel/CSV)"
            desc="Format file PAD resmi: blok TAHUN + kolom No., No. Rekening, Uraian, Target, Realisasi Jan–Des, Jumlah, Sisa, %. Semua blok tahun (2024–2027 dst.) ikut diproses.">
            <label class="flex items-center gap-2 text-xs font-semibold text-slate-500 rounded-lg border border-slate-200 px-3 py-2.5 bg-white cursor-pointer hover:border-teal-400 transition">
                <input type="checkbox" checked disabled class="rounded border-slate-300 text-teal-600"> ganti otomatis per tahun
            </label>
        </x-import-card>
        <div class="card p-5 text-sm">
            <h3 class="font-bold mb-2">💡 Catatan</h3>
            <ul class="space-y-1.5 text-[13px] text-slate-500 list-disc pl-4">
                <li>Baris <b>Jumlah</b> di file tidak diimpor (total dihitung ulang oleh sistem).</li>
                <li>Kategori A/B/C dari file jadi pengelompokan di tabel.</li>
                <li>Data ini langsung mengubah kartu <b>PAD {{ $tahun }}</b> di Dashboard Utama.</li>
            </ul>
        </div>
    </div>
</div>

<div id="modal-pad" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 p-4 grid place-items-center overflow-y-auto">
    <div class="card w-full max-w-md p-6 text-sm">
        <div class="flex justify-between mb-3"><h3 class="font-bold">Tambah Data PAD Manual</h3><button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400">✕</button></div>
        @if ($errors->any())<div class="mb-3 rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-rose-700 text-[13px]">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('admin.pad.store') }}" class="space-y-2.5">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <select name="tahun" class="rounded-lg border border-slate-300 px-3 py-2.5 bg-white">@foreach (range(now()->year + 1, now()->year - 3) as $ty)<option value="{{ $ty }}" @selected($ty == $tahun)>TA {{ $ty }}</option>@endforeach</select>
                <input name="kategori" placeholder="Kategori retribusi" class="rounded-lg border border-slate-300 px-3 py-2.5">
            </div>
            <input name="no_rekening" placeholder="No. rekening (4.1.02….0001)" class="w-full rounded-lg border border-slate-300 px-3 py-2.5">
            <input name="objek" placeholder="Objek pendapatan *" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <input type="number" step="1" name="target_tahunan" placeholder="Target" class="rounded-lg border border-slate-300 px-3 py-2.5">
                <input type="number" step="1" name="target_perubahan" placeholder="Target perubahan" class="rounded-lg border border-slate-300 px-3 py-2.5">
                <input type="number" step="1" name="realisasi_bulan_lalu" placeholder="Realisasi Nov" class="rounded-lg border border-slate-300 px-3 py-2.5">
                <input type="number" step="1" name="realisasi_bulan_ini" placeholder="Realisasi Des" class="rounded-lg border border-slate-300 px-3 py-2.5">
                <input type="number" step="1" name="kumulatif" placeholder="Kumulatif realisasi" class="rounded-lg border border-slate-300 px-3 py-2.5">
                <input type="number" step="any" name="persentase" placeholder="%" class="rounded-lg border border-slate-300 px-3 py-2.5">
            </div>
            <button class="w-full rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5 transition">Simpan</button>
        </form>
    </div>
</div>
@endsection

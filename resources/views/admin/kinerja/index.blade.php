@extends('layouts.dashboard')
@section('title', 'Kelola Kinerja & Kegiatan')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h2 class="text-lg font-extrabold">Kinerja Fisik Sedinas <span class="text-slate-400">TA {{ $tahun }}</span></h2>
        <p class="text-sm text-slate-500">{{ $summary->total ?? 0 }} sub kegiatan · {{ $summary->tuntas ?? 0 }} tuntas · {{ $summary->lambat ?? 0 }} perlu perhatian — tampil otomatis di Dashboard Utama seluruh pegawai.</p>
    </div>
    <div class="flex items-center gap-2">
        @foreach ($tahunList as $t)
            <a href="{{ route('admin.kinerja.index', ['tahun' => $t]) }}" @class(['rounded-lg px-3 py-1.5 text-sm font-bold border transition active:scale-95', $t == $tahun ? 'bg-slate-900 text-white border-slate-900' : 'bg-white border-slate-200 text-slate-600 hover:border-teal-400'])>{{ $t }}</a>
        @endforeach
        <button data-modal-open="modal-kinerja" class="rounded-lg bg-teal-600 hover:bg-teal-700 active:scale-95 text-white text-sm font-bold px-3.5 py-1.5 transition inline-flex items-center gap-1.5"><x-icon name="plus" class="h-4 w-4" /> Tambah manual</button>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2">
        <div class="card overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex flex-wrap gap-2">
                <form class="flex gap-2 flex-1 min-w-[200px]" method="GET">
                    <input type="hidden" name="tahun" value="{{ $tahun }}">
                    <input name="q" value="{{ request('q') }}" placeholder="Cari kegiatan…" class="flex-1 rounded-lg border border-slate-300 px-3 py-1.5 text-sm outline-none focus:border-teal-500">
                    <select name="level" onchange="this.form.submit()" class="rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm">
                        <option value="">Semua level</option>
                        @foreach (['program','kegiatan','sub_kegiatan'] as $lv)<option @selected(request('level') == $lv)>{{ $lv }}</option>@endforeach
                    </select>
                </form>
            </div>
            <div class="overflow-x-auto thin-scroll max-h-[70vh]">
                <table class="data w-full">
                    <tbody>
                    @forelse ($items as $k)
                        <tr class="{{ $k->level === 'program' ? 'bg-slate-50' : '' }}">
                            <td class="w-8">
                                <span class="text-[10px] font-bold uppercase rounded px-1.5 py-0.5 {{ ['program' => 'bg-slate-800 text-white', 'kegiatan' => 'bg-sky-100 text-sky-700', 'sub_kegiatan' => 'bg-teal-50 text-teal-700'][$k->level] }}">{{ $k->level === 'sub_kegiatan' ? 'sub' : mb_substr($k->level, 0, 4) }}</span>
                            </td>
                            <td class="text-[13px] {{ $k->level === 'program' ? 'font-bold' : 'font-medium' }}" style="padding-left: {{ $k->level === 'sub_kegiatan' ? '1rem' : '0' }}">
                                {{ $k->nama }}
                                @if ($k->indikator)<span class="block text-[11px] text-slate-400 font-normal">{{ $k->indikator }}</span>@endif
                                @if ($k->catatan)<span class="block text-[11px] text-amber-600 mt-0.5">⚠ {{ $k->catatan }}</span>@endif
                            </td>
                            <td class="text-xs whitespace-nowrap">{{ rtrim(rtrim(number_format((float) $k->target, 2, ',', '.'), '0'), ',') }} {{ $k->satuan }}<span class="block text-slate-400">→ {{ rtrim(rtrim(number_format((float) $k->capaian, 2, ',', '.'), '0'), ',') }}</span></td>
                            <td class="w-32"><x-prog :persen="$k->persentase" /></td>
                            <td class="w-16 text-right">
                                <button data-modal-open="edit-{{ $k->id }}" class="text-[11px] font-bold text-slate-500 hover:text-teal-600 transition">Edit</button>
                                <form method="POST" action="{{ route('admin.kinerja.destroy', $k) }}" data-confirm="Hapus baris ini?" class="inline">@csrf @method('DELETE')
                                    <button class="text-[11px] font-bold text-rose-400 hover:text-rose-600 transition">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        {{-- modal edit --}}
                        <div id="edit-{{ $k->id }}" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 p-4 grid place-items-center overflow-y-auto">
                            <div class="card w-full max-w-lg p-6 text-sm">
                                <div class="flex justify-between items-center mb-3"><h3 class="font-bold">Edit #{{ $k->id }}</h3><button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400">✕</button></div>
                                <form method="POST" action="{{ route('admin.kinerja.update', $k) }}" class="space-y-2.5">
                                    @csrf @method('PATCH')
                                    <input name="nama" value="{{ $k->nama }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                        <label class="text-xs text-slate-500">Target <input type="number" step="any" name="target" value="{{ (float) $k->target }}" class="mt-0.5 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                                        <label class="text-xs text-slate-500">Satuan <input name="satuan" value="{{ $k->satuan }}" class="mt-0.5 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                                        <label class="text-xs text-slate-500">Capaian <input type="number" step="any" name="capaian" value="{{ (float) $k->capaian }}" class="mt-0.5 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                                        <label class="text-xs text-slate-500">Persentase % <input type="number" step="any" name="persentase" value="{{ (float) $k->persentase }}" class="mt-0.5 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                                        <label class="text-xs text-slate-500 col-span-2">Pagu anggaran <input type="number" step="1" name="pagu_anggaran" value="{{ (float) $k->pagu_anggaran }}" class="mt-0.5 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
                                    </div>
                                    <textarea name="catatan" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Catatan / permasalahan">{{ $k->catatan }}</textarea>
                                    <button class="w-full rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5 transition">Simpan</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <tr><td colspan="5"><x-empty icon="kinerja" title="TA {{ $tahun }} belum punya data" desc="Import file kinerja di panel kanan, atau tambah manual."></x-empty></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($items->hasPages())<div class="p-3">{{ $items->links() }}</div>@endif
        </div>
    </div>

    <div class="space-y-4">
        <x-import-card :action="route('admin.kinerja.import')" title="Import file Kinerja (Excel/CSV)"
            desc="Format file «Kinerja Fisik Sedinas» — blok TAHUN, kolom PROGRAM/KEGIATAN/SUB, Pagu, Target, Satuan, Capaian. Tahun yang ada di file akan diganti otomatis.">
            <label class="flex items-center gap-2 text-xs font-semibold text-slate-500 rounded-lg border border-slate-200 px-3 py-2.5 bg-white cursor-pointer hover:border-teal-400 transition">
                <input type="checkbox" name="ganti_tahun" value="1" checked class="rounded border-slate-300 text-teal-600"> ganti tahun lama
            </label>
        </x-import-card>
        @if (is_file(database_path('source/Kinerja Fisik Sedinas.csv')))
            <div class="card p-4 text-xs text-slate-500 flex items-center gap-2.5">
                <span class="text-emerald-500">✓</span> File sumber <b class="text-slate-700">Kinerja Fisik Sedinas.csv</b> tersimpan di sistem (dipakai saat seeding ulang).
            </div>
        @endif
        <div class="card p-5 text-sm">
            <h3 class="font-bold mb-2">💡 Cara kerja</h3>
            <ul class="space-y-1.5 text-[13px] text-slate-500 list-disc pl-4">
                <li>Semua kegiatan di halaman ini otomatis tampil di <b>Dashboard Utama</b> seluruh pegawai sebagai info &amp; pengingat.</li>
                <li>Status: ≥100% <b class="text-emerald-600">Tuntas</b>, 60–99% <b class="text-sky-600">Berjalan</b>, &lt;60% <b class="text-amber-600">Perlu perhatian</b>.</li>
                <li>Grafik bulanan dashboard ikut ter-update dari capaian per bulan (Jan–Des) di file.</li>
                <li>Bisa juga menambah/mengubah satu baris lewat form manual di kiri.</li>
            </ul>
        </div>
    </div>
</div>

{{-- modal tambah manual --}}
<div id="modal-kinerja" class="modal-shell hidden fixed inset-0 z-[80] bg-slate-900/60 p-4 grid place-items-center overflow-y-auto">
    <div class="card w-full max-w-xl p-6 text-sm">
        <div class="flex justify-between items-center mb-3"><h3 class="font-bold text-[15px]">Tambah Kegiatan Manual</h3><button data-modal-close class="h-8 w-8 rounded-lg hover:bg-slate-100 text-slate-400">✕</button></div>
        @if ($errors->any())<div class="mb-3 rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-rose-700 text-[13px]">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('admin.kinerja.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
            @csrf
            <div class="col-span-2"><input name="nama" placeholder="Nama kegiatan *" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5"></div>
            <select name="tahun" class="rounded-lg border border-slate-300 px-3 py-2.5 bg-white">@foreach (range(now()->year + 1, now()->year - 3) as $ty)<option value="{{ $ty }}" @selected($ty == $tahun)>TA {{ $ty }}</option>@endforeach</select>
            <select name="level" class="rounded-lg border border-slate-300 px-3 py-2.5 bg-white"><option value="sub_kegiatan">Sub Kegiatan</option><option value="kegiatan">Kegiatan</option><option value="program">Program</option></select>
            <input name="sumber_dana" placeholder="Sumber dana (DAU/DBH…)" class="rounded-lg border border-slate-300 px-3 py-2.5">
            <input type="number" step="1" name="pagu_anggaran" placeholder="Pagu anggaran" class="rounded-lg border border-slate-300 px-3 py-2.5">
            <input name="indikator" placeholder="Indikator" class="rounded-lg border border-slate-300 px-3 py-2.5">
            <div class="flex gap-2"><input type="number" step="any" name="target" placeholder="Target" class="w-full rounded-lg border border-slate-300 px-3 py-2.5"><input name="satuan" placeholder="Satuan" class="w-full rounded-lg border border-slate-300 px-3 py-2.5"></div>
            <div class="flex gap-2"><input type="number" step="any" name="capaian" placeholder="Capaian" class="w-full rounded-lg border border-slate-300 px-3 py-2.5"><input type="number" step="any" name="persentase" placeholder="%" class="w-full rounded-lg border border-slate-300 px-3 py-2.5"></div>
            <select name="id_bidang" class="rounded-lg border border-slate-300 px-3 py-2.5 bg-white"><option value="">— Pelaksana —</option>@foreach ($bidangs as $bd)<option value="{{ $bd->id }}">{{ $bd->nama }}</option>@endforeach</select>
            <div class="col-span-2"><textarea name="catatan" rows="2" placeholder="Catatan / permasalahan" class="w-full rounded-lg border border-slate-300 px-3 py-2.5"></textarea></div>
            <button class="col-span-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5 transition">Simpan Kegiatan</button>
        </form>
    </div>
</div>
@endsection

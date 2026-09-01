@extends('layouts.dashboard')
@section('title', 'Kelola Capaian Kinerja')
@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h2 class="text-lg font-extrabold">Kelola Capaian Kinerja</h2>
        <p class="text-sm text-slate-500">Matriks sasaran → indikator → target/realisasi per tahun. Ubah manual atau impor berkas resmi.</p>
    </div>
    <a href="{{ route('kinerja.capaian') }}" class="rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-bold text-white hover:bg-slate-700 active:scale-95 transition">👁 Lihat halaman publik</a>
</div>

<div class="grid lg:grid-cols-2 gap-4 mb-4">
    <div class="card p-4">
        <h3 class="font-bold mb-1">Impor berkas capaian</h3>
        <p class="text-xs text-slate-500 mb-2">Format sama dengan <code>CAPAIAN KINERJA.csv</code> / .xlsx resmi: header <i>No. | Sasaran | Indikator Kinerja | Satuan</i> lalu triplet kolom <i>Target | Realisasi | %</i> tiap tahun.</p>
        <div class="flex flex-wrap gap-x-5 gap-y-1 mb-3 text-sm">
            <label class="flex items-center gap-1.5 font-semibold cursor-pointer"><input type="radio" name="mode" value="update" checked class="accent-teal-600"> Gabungkan &amp; perbarui <span class="font-normal text-slate-500">(disarankan — file yang sama diunggah berulang tidak akan dobel; yang berubah saja yang diperbarui)</span></label>
            <label class="flex items-center gap-1.5 font-semibold cursor-pointer"><input type="radio" name="mode" value="ganti" class="accent-teal-600"> Ganti total <span class="font-normal text-slate-500">(hapus semua, lalu isi ulang)</span></label>
        </div>
        <form method="POST" action="{{ route('admin.capaian.import') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
            @csrf
            <input type="file" name="berkas" accept=".csv,.xlsx,.xls,.ods" required class="flex-1 min-w-[200px] text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-teal-600 file:px-3 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-teal-500">
            <button class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-bold text-white hover:bg-teal-500 active:scale-95 transition">Impor</button>
        </form>
    </div>
    <div class="card p-4">
        <h3 class="font-bold mb-3">Tambah sasaran baru</h3>
        <form method="POST" action="{{ route('admin.capaian.sasaran.store') }}" class="flex flex-wrap items-end gap-2">
            @csrf
            <label class="text-xs font-bold text-slate-500">No.<input name="nomor" type="number" min="1" class="mt-1 block w-16 rounded-lg border border-slate-300 px-2 py-2 text-sm"></label>
            <label class="text-xs font-bold text-slate-500 flex-1 min-w-[180px]">Judul sasaran *<input name="judul" required maxlength="500" class="mt-1 block w-full rounded-lg border border-slate-300 px-2.5 py-2 text-sm"></label>
            <label class="text-xs font-bold text-slate-500">Cakupan tahun<input name="cakupan" maxlength="160" class="mt-1 block w-28 rounded-lg border border-slate-300 px-2.5 py-2 text-sm"></label>
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-700 active:scale-95 transition">Tambah</button>
        </form>
    </div>
</div>

@forelse ($sasarans as $s)
<div class="card mb-4 overflow-hidden">
    <div class="px-4 py-3 flex flex-wrap items-center gap-3 border-b border-slate-100 bg-slate-50/60">
        <span class="grid h-7 w-7 place-items-center rounded-lg bg-slate-900 text-sm font-extrabold text-white">{{ $s->nomor }}</span>
        <div class="flex-1 min-w-[200px]">
            <div class="font-bold leading-snug">{{ $s->judul }}</div>
            @if ($s->cakupan)<div class="text-[11px] text-slate-400 font-medium">Sasaran Tahun : {{ $s->cakupan }}</div>@endif
        </div>
        <details class="relative">
            <summary class="cursor-pointer list-none rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-600 hover:border-teal-400">Ubah ▾</summary>
            <form method="POST" action="{{ route('admin.capaian.sasaran.update', $s) }}" class="absolute right-0 z-20 mt-1 w-80 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                @csrf @method('PATCH')
                <div class="flex gap-2 mb-2">
                    <label class="text-xs font-bold text-slate-500">No.<input name="nomor" value="{{ $s->nomor }}" type="number" class="mt-1 block w-16 rounded-lg border border-slate-300 px-2 py-1.5 text-sm"></label>
                    <label class="text-xs font-bold text-slate-500 flex-1">Cakupan<input name="cakupan" value="{{ $s->cakupan }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"></label>
                </div>
                <label class="text-xs font-bold text-slate-500">Judul<input name="judul" value="{{ $s->judul }}" required class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"></label>
                <button class="mt-2 w-full rounded-lg bg-slate-900 py-1.5 text-sm font-bold text-white hover:bg-slate-700">Simpan</button>
            </form>
        </details>
        <form method="POST" action="{{ route('admin.capaian.sasaran.destroy', $s) }}" onsubmit="return confirm('Hapus sasaran ini beserta seluruh indikator & nilainya?')">
            @csrf @method('DELETE')
            <button class="rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-600 hover:bg-rose-100">Hapus</button>
        </form>
    </div>

    <div class="overflow-x-auto thin-scroll">
        <table class="data w-full min-w-[720px]">
            <thead><tr class="text-left"><th class="w-8">#</th><th>Indikator</th><th class="w-24">Satuan</th><th class="w-56">Tahun terakhir</th><th class="w-24"></th></tr></thead>
            <tbody>
                @forelse ($s->indikator as $i => $ind)
                    @php $terakhir = $ind->tahunan->sortByDesc('tahun')->first(); @endphp
                    <tr class="border-t border-slate-100">
                        <td class="text-slate-400">{{ $i + 1 }}</td>
                        <td class="font-medium">{{ $ind->nama }}</td>
                        <td class="text-slate-500">{{ $ind->satuan ?: '—' }}</td>
                        <td>
                            @if ($terakhir)
                                <span class="text-xs font-bold text-slate-500">{{ $terakhir->tahun }}:</span>
                                <span class="text-sm font-bold">{{ $terakhir->realisasi ?? '—' }}</span>
                                @php $num = \App\Models\CapaianIndikator::persenNum($terakhir->persen); @endphp
                                @if ($num !== null)
                                    <span class="ml-1 rounded px-1.5 py-0.5 text-[11px] font-bold {{ $num >= 100 ? 'bg-emerald-100 text-emerald-800' : ($num >= 80 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-700') }}">{{ $terakhir->persen }}</span>
                                @endif
                            @else
                                <span class="text-slate-400">belum ada nilai</span>
                            @endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <details class="inline-block align-middle">
                                <summary class="cursor-pointer list-none rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-bold text-slate-600 hover:border-teal-400">Nilai</summary>
                            </details>
                            <form method="POST" action="{{ route('admin.capaian.indikator.destroy', $ind) }}" class="inline" onsubmit="return confirm('Hapus indikator ini?')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg border border-rose-200 bg-rose-50 px-2 py-1 text-xs font-bold text-rose-600 hover:bg-rose-100">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <tr class="border-t border-slate-50">
                        <td colspan="5" class="p-0">
                            <div class="hidden peer-open:block"></div>
                            <details class="group/nilai px-3 py-1.5">
                                <summary class="cursor-pointer list-none text-[11px] font-bold text-teal-700 hover:underline">✏️ Atur nilai per tahun &amp; ubah nama indikator</summary>
                                <form method="POST" action="{{ route('admin.capaian.nilai', $ind) }}" class="mt-2">
                                    @csrf
                                    <div class="overflow-x-auto thin-scroll">
                                        <table class="data w-full min-w-[560px] text-sm">
                                            <thead><tr class="text-left text-[11px] uppercase tracking-wide text-slate-400"><th class="w-14">Tahun</th><th>Target</th><th>Realisasi</th><th class="w-24">Persen</th></tr></thead>
                                            <tbody>
                                                @php $by = $ind->tahunan->keyBy('tahun'); @endphp
                                                @foreach ($years as $y)
                                                    @php $nn = $by->get($y); @endphp
                                                    <tr class="border-t border-slate-100">
                                                        <td class="font-bold">{{ $y }}</td>
                                                        <td><input name="nilai[{{ $y }}][target]" value="{{ $nn?->target }}" maxlength="120" class="w-full rounded-md border border-slate-200 px-2 py-1 text-sm focus:border-teal-500 outline-none"></td>
                                                        <td><input name="nilai[{{ $y }}][realisasi]" value="{{ $nn?->realisasi }}" maxlength="120" class="w-full rounded-md border border-slate-200 px-2 py-1 text-sm focus:border-teal-500 outline-none"></td>
                                                        <td><input name="nilai[{{ $y }}][persen]" value="{{ $nn?->persen }}" maxlength="24" class="w-full rounded-md border border-slate-200 px-2 py-1 text-sm focus:border-teal-500 outline-none"></td>
                                                    </tr>
                                                @endforeach
                                                <tr class="border-t-2 border-dashed border-slate-200">
                                                    <td>
                                                        <select name="t_tahun" class="w-full rounded-md border border-slate-200 px-1 py-1 text-sm bg-white">
                                                            <option value="">+ tahun</option>
                                                            @foreach ($tambahYears as $yy)
                                                                @if (!$by->has($yy))<option value="{{ $yy }}">{{ $yy }}</option>@endif
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td><input name="t_target" placeholder="target" maxlength="120" class="w-full rounded-md border border-slate-200 px-2 py-1 text-sm"></td>
                                                    <td><input name="t_realisasi" placeholder="realisasi" maxlength="120" class="w-full rounded-md border border-slate-200 px-2 py-1 text-sm"></td>
                                                    <td><input name="t_persen" placeholder="%" maxlength="24" class="w-full rounded-md border border-slate-200 px-2 py-1 text-sm"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="flex items-end gap-2 mt-2">
                                        <label class="text-xs font-bold text-slate-500 flex-1">Ubah nama indikator<input name="nama" value="{{ $ind->nama }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"></label>
                                        <label class="text-xs font-bold text-slate-500 w-32">Satuan<input name="satuan" value="{{ $ind->satuan }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm"></label>
                                        <button class="rounded-lg bg-teal-600 px-3.5 py-2 text-sm font-bold text-white hover:bg-teal-500 active:scale-95 transition">Simpan nilai</button>
                                    </div>
                                    <p class="mt-1 text-[10px] text-slate-400">Kosongkan semua sel suatu tahun lalu simpan untuk menghapus baris tahun itu. Kosong/n-a di berkas = “—”.</p>
                                </form>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr class="border-t border-slate-100"><td colspan="5" class="py-2 text-slate-400 text-sm">Belum ada indikator — tambahkan di bawah.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <form method="POST" action="{{ route('admin.capaian.indikator.store') }}" class="px-4 py-2.5 flex flex-wrap items-center gap-2 border-t border-slate-100 bg-slate-50/50">
        @csrf
        <input type="hidden" name="sasaran_id" value="{{ $s->id }}">
        <input name="nama" placeholder="Nama indikator baru…" required maxlength="500" class="flex-1 min-w-[200px] rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/30 outline-none">
        <input name="satuan" placeholder="satuan" maxlength="80" class="w-28 rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm">
        <button class="rounded-lg border border-teal-500 px-3 py-1.5 text-sm font-bold text-teal-700 hover:bg-teal-50 active:scale-95 transition">+ Indikator</button>
    </form>
</div>
@empty
<div class="card p-10 text-center text-slate-400">
    Belum ada data capaian kinerja. Impor berkas resmi di atas, atau tambahkan sasaran secara manual di kanan atas.
</div>
@endforelse
@endsection

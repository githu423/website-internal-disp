@extends('layouts.dashboard')
@section('title', 'Kelola Agenda')
@section('content')
<div class="grid lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 card overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex gap-2">
            @foreach (['' => 'Semua', 'belum' => 'Belum', 'selesai' => 'Selesai'] as $kv => $lab)
                <a href="{{ route('admin.agenda.index', $kv ? ['status' => $kv] : []) }}" @class(['rounded-lg px-3 py-1 text-xs font-bold border transition', request('status', '') === $kv ? 'bg-slate-900 text-white border-slate-900' : 'bg-white border-slate-200 text-slate-500 hover:border-teal-400'])>{{ $lab }}</a>
            @endforeach
        </div>
        <div class="overflow-x-auto thin-scroll">
            <table class="data w-full">
                <tbody>
                @forelse ($items as $a)
                    <tr>
                        <td class="w-14 text-center"><p class="text-base font-extrabold leading-none {{ $a->status !== 'belum' ? 'text-slate-300' : 'text-teal-600' }}">{{ $a->tanggal->format('d') }}</p><p class="text-[9px] font-bold uppercase text-slate-400">{{ $a->tanggal->translatedFormat('M Y') }}</p></td>
                        <td><p class="font-semibold text-[13px] {{ $a->status === 'selesai' ? 'line-through text-slate-400' : '' }}">{{ $a->judul }}</p>
                            <p class="text-[11px] text-slate-400">{{ $a->jam_mulai ? substr((string) $a->jam_mulai, 0, 5) . ' · ' : '' }}{{ $a->lokasi ?: 'tanpa lokasi' }} @if ($a->bidang) · {{ $a->bidang->nama }} @endif</p></td>
                        <td class="w-40">
                            <form method="POST" action="{{ route('admin.agenda.update', $a) }}" class="flex gap-1.5">
                                @csrf @method('PATCH')
                                <select name="status" onchange="this.form.submit()" class="rounded border border-slate-200 bg-white text-[11px] font-bold px-1.5 py-1 w-full">
                                    @foreach (['belum','selesai','dibatalkan'] as $st)<option @selected($a->status === $st)>{{ $st }}</option>@endforeach
                                </select>
                            </form>
                        </td>
                        <td class="text-right w-14"><form method="POST" action="{{ route('admin.agenda.destroy', $a) }}" data-confirm="Hapus agenda?">@csrf @method('DELETE')
                            <button class="text-[11px] font-bold text-rose-400 hover:text-rose-600">Hapus</button></form></td>
                    </tr>
                @empty
                    <tr><td><x-empty icon="agenda" title="Belum ada agenda" /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($items->hasPages())<div class="p-3">{{ $items->links() }}</div>@endif
    </div>
    <div class="card p-5 h-fit">
        <h3 class="font-bold mb-3">+ Agenda Baru</h3>
        @if ($errors->any())<div class="mb-3 rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-rose-700 text-[13px]">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('admin.agenda.store') }}" class="space-y-2.5 text-sm">
            @csrf
            <input name="judul" placeholder="Judul *" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none focus:border-teal-500">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <input type="date" name="tanggal" required class="rounded-lg border border-slate-300 px-3 py-2.5 outline-none">
                <input type="time" name="jam_mulai" value="09:00" class="rounded-lg border border-slate-300 px-3 py-2.5 outline-none">
            </div>
            <input name="lokasi" placeholder="Lokasi" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none focus:border-teal-500">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <select name="kategori" class="rounded-lg border border-slate-300 px-3 py-2.5 bg-white"><option value="rapat">Rapat</option><option value="kegiatan">Kegiatan</option><option value="kunjungan">Kunjungan</option><option value="lainnya">Lainnya</option></select>
                <select name="id_bidang" class="rounded-lg border border-slate-300 px-3 py-2.5 bg-white"><option value="">Seluruh dinas</option>@foreach ($bidangs as $b)<option value="{{ $b->id }}">{{ $b->nama }}</option>@endforeach</select>
            </div>
            <textarea name="deskripsi" rows="3" placeholder="Deskripsi" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none focus:border-teal-500"></textarea>
            <label class="flex items-center gap-2 text-[13px] text-slate-600"><input type="checkbox" name="beri_tahu" value="1" checked class="rounded border-slate-300 text-teal-600"> Broadcast notifikasi ke pegawai</label>
            <button class="w-full rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5 transition">Simpan</button>
        </form>
    </div>
</div>
@endsection

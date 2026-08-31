@extends('layouts.dashboard')
@section('title', 'Kelola Dokumen')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <p class="text-sm text-slate-500">Total penyimpanan: <b class="text-slate-800">{{ round($totalUkuran / 1048576, 2) }} MB</b></p>
    <form method="GET" class="flex gap-2">
        <input name="q" value="{{ request('q') }}" placeholder="Cari judul…" class="w-52 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-500">
        <select name="visibility" onchange="this.form.submit()" class="rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm"><option value="">Semua visibilitas</option>@foreach (['publik','bidang','rahasia'] as $v)<option @selected(request('visibility') === $v)>{{ $v }}</option>@endforeach</select>
        <button class="rounded-lg bg-slate-900 text-white px-3.5 py-2 text-sm font-bold hover:bg-slate-700 transition">Cari</button>
    </form>
</div>
<div class="card overflow-hidden">
    <div class="overflow-x-auto thin-scroll">
        <table class="data w-full min-w-[760px]">
            <thead><tr><th>Judul</th><th>Bidang</th><th>Visibilitas</th><th>Ukuran</th><th>Unduhan</th><th></th></tr></thead>
            <tbody>
            @forelse ($dokumen as $d)
                <tr>
                    <td><p class="font-semibold text-[13px]">{{ $d->judul }}</p><p class="text-[11px] text-slate-400">{{ $d->kategori ?: 'Umum' }} · {{ $d->uploader?->name ?? '—' }} · {{ $d->created_at->translatedFormat('d M Y, H:i') }}</p></td>
                    <td class="text-xs text-slate-500">{{ $d->bidang?->nama ?? '—' }}</td>
                    <td><span class="text-[11px] font-bold rounded-full px-2 py-0.5 {{ ['publik' => 'bg-emerald-50 text-emerald-600', 'bidang' => 'bg-sky-50 text-sky-600', 'rahasia' => 'bg-rose-50 text-rose-600'][$d->visibility] }}">{{ ucfirst($d->visibility) }}</span>
                        <p class="text-[10px] text-slate-400 mt-0.5">ubah oleh uploader</p></td>
                    <td class="text-xs tabular-nums">{{ $d->file_size > 1048576 ? round($d->file_size / 1048576, 1) . ' MB' : max(1, round($d->file_size / 1024)) . ' KB' }}</td>
                    <td class="text-xs font-bold">{{ $d->dibaca_count }}×</td>
                    <td class="text-right whitespace-nowrap">
                        <a href="{{ route('dokumen.show', $d) }}" class="text-[11px] font-bold text-slate-500 hover:text-teal-600">Lihat</a>
                        <a href="{{ route('dokumen.unduh', $d) }}" class="text-[11px] font-bold text-slate-500 hover:text-teal-600">Unduh</a>
                        <form method="POST" action="{{ route('admin.dokumen.destroy', $d) }}" data-confirm="Hapus permanen?" class="inline">@csrf @method('DELETE')
                            <button class="text-[11px] font-bold text-rose-400 hover:text-rose-600">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><x-empty icon="dokumen" title="Belum ada dokumen" /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($dokumen->hasPages())<div class="p-3">{{ $dokumen->links() }}</div>@endif
</div>
@endsection

@extends('layouts.dashboard')
@section('title', 'Log Aktivitas')
@section('content')
<div class="mb-4"><form method="GET" class="flex gap-2 w-fit"><input name="q" value="{{ request('q') }}" placeholder="Cari user / aksi…" class="w-72 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-500"><button class="rounded-lg bg-slate-900 text-white px-3.5 py-2 text-sm font-bold hover:bg-slate-700 transition">Cari</button></form></div>
<div class="card overflow-hidden">
    <table class="data w-full">
        <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Keterangan</th><th>IP</th></tr></thead>
        <tbody>
        @forelse ($items as $l)
            <tr><td class="text-xs text-slate-400 whitespace-nowrap">{{ $l->created_at->format('d/m/Y H:i') }}</td>
                <td class="text-[13px] font-semibold">{{ $l->nama }}</td>
                <td><span class="text-[11px] font-bold bg-slate-100 rounded px-1.5 py-0.5">{{ $l->aksi }}</span></td>
                <td class="text-xs text-slate-500">{{ $l->keterangan }}</td>
                <td class="text-[11px] font-mono text-slate-400">{{ $l->ip }}</td></tr>
        @empty
            <tr><td colspan="5"><x-empty icon="log" title="Log kosong" /></td></tr>
        @endforelse
        </tbody>
    </table>
    @if ($items->hasPages())<div class="p-3">{{ $items->links() }}</div>@endif
</div>
@endsection

{{-- kartu satu agenda (dipakai ulang di tiap kelompok agenda dinas / per bidang) --}}
@php $lewat = $a->status === 'belum' && $a->tanggal->lt(now()->startOfDay()); @endphp
<div class="card p-4 flex items-start gap-4 {{ $a->status === 'selesai' ? 'opacity-60' : ($lewat ? 'border-rose-200' : '') }}">
    <div class="h-12 w-12 shrink-0 rounded-xl grid place-items-center text-center {{ $lewat ? 'bg-rose-50 text-rose-600' : 'bg-teal-50 text-teal-700' }}">
        <div><p class="text-lg font-extrabold leading-none">{{ $a->tanggal->format('d') }}</p><p class="text-[9px] font-bold uppercase">{{ $a->tanggal->translatedFormat('M') }}</p></div>
    </div>
    <div class="flex-1 min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <p class="font-bold text-[14px] {{ $a->status === 'selesai' ? 'line-through' : '' }}">{{ $a->judul }}</p>
            @if ($a->kategori)<span class="text-[10px] font-bold uppercase bg-slate-100 text-slate-500 rounded px-1.5 py-0.5">{{ $a->kategori }}</span>@endif
            @if ($lewat)<span class="text-[10px] font-bold bg-rose-100 text-rose-600 rounded px-1.5 py-0.5">TERLEWAT</span>@endif
        </div>
        <p class="text-[12.5px] text-slate-500 mt-1">{{ $a->jam_mulai ? substr((string) $a->jam_mulai, 0, 5) . ($a->jam_selesai ? '–' . substr((string) $a->jam_selesai, 0, 5) : '') . ' WIB' : 'Waktu menyusul' }} · {{ $a->lokasi ?: 'Lokasi menyusul' }} @if ($a->bidang) · <span class="text-sky-600 font-semibold">{{ $a->bidang->nama }}</span>@endif</p>
        @if ($a->deskripsi)<p class="text-xs text-slate-400 mt-1.5 line-clamp-2">{{ $a->deskripsi }}</p>@endif
    </div>
    <div class="flex flex-col gap-1.5 shrink-0">
        @if ($u->isAdmin() || $u->id === $a->created_by)
        <form method="POST" action="{{ route('agenda.status', $a) }}">@csrf @method('PATCH')
            <button class="text-[11px] font-bold rounded-lg border px-2.5 py-1.5 transition active:scale-95 {{ $a->status === 'selesai' ? 'border-slate-200 text-slate-500 hover:bg-slate-50' : 'border-emerald-300 text-emerald-600 hover:bg-emerald-50' }}">{{ $a->status === 'selesai' ? 'Batal' : '✓ Selesai' }}</button>
        </form>
        <form method="POST" action="{{ route('agenda.destroy', $a) }}" data-confirm="Hapus agenda ini?">@csrf @method('DELETE')
            <button class="text-[11px] font-bold rounded-lg border border-rose-200 text-rose-500 px-2.5 py-1.5 hover:bg-rose-50 transition active:scale-95">Hapus</button>
        </form>
        @endif
    </div>
</div>
{{-- kartu satu agenda (dipakai ulang di tiap kelompok agenda dinas / per bidang) --}}
@php 
    $isHariIni = $a->tanggal->isToday();
    $lewat = !$isHariIni && $a->status === 'belum' && $a->tanggal->lt(now()->startOfDay()); 
@endphp
<div data-agenda-card 
     data-tanggal="{{ $a->tanggal->format('Y-m-d') }}" 
     data-is-today="{{ $isHariIni ? '1' : '0' }}"
     class="bg-white border rounded-2xl p-4 sm:p-5 shadow-sm flex items-start gap-4 transition-all duration-150 hover:border-purple-300 {{ $a->status === 'selesai' ? 'opacity-65 border-slate-200' : ($isHariIni ? 'border-purple-300 ring-2 ring-purple-500/15 bg-purple-50/10' : ($lewat ? 'border-rose-200 bg-rose-50/15' : 'border-slate-200')) }}">
    
    <!-- Kotak Tanggal -->
    <div class="h-12 w-12 shrink-0 rounded-2xl grid place-items-center text-center leading-none border {{ $isHariIni ? 'bg-purple-600 text-white border-purple-600 shadow-sm shadow-purple-500/20' : ($lewat ? 'bg-rose-50 text-rose-600 border-rose-100' : ($a->status === 'selesai' ? 'bg-slate-100 text-slate-500 border-slate-200' : 'bg-purple-50 text-purple-700 border-purple-100')) }}">
        <div>
            <p class="text-lg font-extrabold">{{ $a->tanggal->format('d') }}</p>
            <p class="text-[9.5px] font-bold uppercase tracking-wider">{{ $a->tanggal->translatedFormat('M') }}</p>
        </div>
    </div>

    <!-- Informasi Detail Agenda -->
    <div class="flex-1 min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <h4 class="font-bold text-[14px] text-slate-900 leading-snug {{ $a->status === 'selesai' ? 'line-through text-slate-400' : '' }}">
                {{ $a->judul }}
            </h4>
            
            @if ($isHariIni)
                <span class="text-[10px] font-extrabold bg-purple-600 text-white rounded-full px-2.5 py-0.5 shadow-sm shadow-purple-500/20">
                    ★ HARI INI
                </span>
            @endif

            @if ($a->kategori)
                <span class="text-[10px] font-extrabold uppercase rounded-full px-2 py-0.5 border {{ ['rapat' => 'bg-purple-50 text-purple-700 border-purple-200', 'kegiatan' => 'bg-sky-50 text-sky-700 border-sky-200', 'kunjungan' => 'bg-emerald-50 text-emerald-700 border-emerald-200'][$a->kategori] ?? 'bg-slate-100 text-slate-600 border-slate-200' }}">
                    {{ $a->kategori }}
                </span>
            @endif

            @if ($lewat)
                <span class="text-[10px] font-extrabold bg-rose-100 text-rose-700 border border-rose-200 rounded-full px-2 py-0.5">
                    TERLEWAT
                </span>
            @endif

            @if ($a->status === 'selesai')
                <span class="text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full px-2 py-0.5">
                    ✓ SELESAI
                </span>
            @endif
        </div>
        
        <p class="text-xs text-slate-500 mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-0.5">
            <span>🕒 {{ $a->jam_mulai ? substr((string) $a->jam_mulai, 0, 5) . ($a->jam_selesai ? '–' . substr((string) $a->jam_selesai, 0, 5) : '') . ' WIB' : 'Waktu menyusul' }}</span>
            <span class="text-slate-300">·</span>
            <span>📍 {{ $a->lokasi ?: 'Lokasi menyusul' }}</span>
            @if ($a->bidang)
                <span class="text-slate-300">·</span>
                <span class="text-purple-700 font-semibold">🏢 {{ $a->bidang->nama }}</span>
            @endif
        </p>

        @if ($a->deskripsi)
            <p class="text-xs text-slate-500 mt-2 line-clamp-2 leading-relaxed bg-slate-50 p-2 rounded-xl border border-slate-100">
                {{ $a->deskripsi }}
            </p>
        @endif
    </div>

    <!-- Tombol Aksi (Admin / Pembuat) -->
    <div class="flex flex-col sm:flex-row items-center gap-1.5 shrink-0">
        @if ($u->isAdmin() || $u->id === $a->created_by)
            <form method="POST" action="{{ route('agenda.status', $a) }}">
                @csrf @method('PATCH')
                <button class="text-xs font-bold rounded-xl border px-3 py-1.5 transition active:scale-95 shadow-sm {{ $a->status === 'selesai' ? 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' : 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                    {{ $a->status === 'selesai' ? 'Batal' : '✓ Selesai' }}
                </button>
            </form>
            <form method="POST" action="{{ route('agenda.destroy', $a) }}" data-confirm="Hapus agenda ini?">
                @csrf @method('DELETE')
                <button class="text-xs font-bold rounded-xl border border-rose-200 bg-rose-50 text-rose-600 px-3 py-1.5 hover:bg-rose-100 transition active:scale-95">
                    Hapus
                </button>
            </form>
        @endif
    </div>
</div>

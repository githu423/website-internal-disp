@extends('layouts.dashboard')

@section('title', 'Kelola Agenda')
@section('header_title')
<span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Manajemen Jadwal Kegiatan, Rapat, dan Acara Dinas Pariwisata')

@section('content')
<div class="space-y-6">

    <!-- 1. Breadcrumbs Mikro -->
    <nav class="flex items-center text-sm font-medium text-slate-400">
        <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-400">Admin — Kegiatan</span>
        <span class="mx-2 text-slate-300">/</span>
        <span class="text-slate-700 font-semibold">Kelola Agenda</span>
    </nav>

    <!-- 2. Header Halaman & Quick Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Jadwal &amp; Agenda Dinas</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                    {{ $items->total() }} Agenda
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Publikasi dan kelola jadwal dinas, rapat koordinasi, serta acara bidang kerja.</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('agenda.index') }}" 
               class="cursor-pointer inline-flex items-center gap-2 px-3.5 py-2.5 bg-white border border-slate-200 text-slate-700 text-sm font-semibold rounded-xl shadow-sm hover:bg-slate-50 hover:border-purple-300 hover:text-purple-600 active:scale-95 transition-all duration-150">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Lihat Kalender Publik
            </a>
        </div>
    </div>

    <!-- 3. Grid Konten: Tabel Agenda & Form Tambah -->
    <div class="grid lg:grid-cols-3 gap-5">
        <!-- Kolom Kiri: Tabel Data Agenda -->
        <div class="lg:col-span-2 space-y-4">
            <!-- Filter Status Tab -->
            <div class="inline-flex items-center bg-white border border-slate-200 rounded-xl p-1 shadow-sm">
                @foreach (['' => 'Semua Agenda', 'belum' => 'Mendatang / Belum', 'selesai' => 'Selesai'] as $kv => $lab)
                    <a href="{{ route('admin.agenda.index', $kv ? ['status' => $kv] : []) }}" 
                       class="px-3.5 py-1.5 text-xs font-bold rounded-lg transition {{ request('status', '') === $kv ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-600 hover:text-purple-600 hover:bg-slate-50' }}">
                        {{ $lab }}
                    </a>
                @endforeach
            </div>

            <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
                <div class="overflow-x-auto thin-scroll">
                    <table class="data w-full min-w-[620px]">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-[11px] uppercase tracking-wider font-extrabold">
                                <th class="w-20 py-3 px-4 text-center">Tanggal</th>
                                <th class="py-3 px-3">Agenda &amp; Lokasi</th>
                                <th class="w-40 py-3 px-3">Status</th>
                                <th class="w-20 py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($items as $a)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4 text-center">
                                    <div class="inline-flex flex-col items-center justify-center w-12 h-12 rounded-xl border {{ $a->status === 'belum' ? 'bg-purple-50 border-purple-200 text-purple-700' : 'bg-slate-100 border-slate-200 text-slate-400' }}">
                                        <span class="text-base font-black leading-none">{{ $a->tanggal->format('d') }}</span>
                                        <span class="text-[9px] font-bold uppercase mt-0.5">{{ $a->tanggal->translatedFormat('M') }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <p class="font-bold text-[13px] {{ $a->status === 'selesai' ? 'line-through text-slate-400' : 'text-slate-900' }} leading-snug">
                                        {{ $a->judul }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-1 text-xs text-slate-400">
                                        @if ($a->jam_mulai)
                                            <span class="font-mono text-slate-600 font-semibold">🕒 {{ substr((string) $a->jam_mulai, 0, 5) }} WIB</span>
                                            <span>·</span>
                                        @endif
                                        <span>📍 {{ $a->lokasi ?: 'Dinas Pariwisata Ciamis' }}</span>
                                        @if ($a->bidang)
                                            <span>·</span>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10.5px] font-medium bg-purple-50 text-purple-700 border border-purple-100">
                                                {{ $a->bidang->nama }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 px-3">
                                    <form method="POST" action="{{ route('admin.agenda.update', $a) }}">
                                        @csrf @method('PATCH')
                                        <select name="status" onchange="this.form.submit()" 
                                                class="rounded-xl border border-slate-200 bg-white text-xs font-bold px-3 py-1.5 w-full focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                                            @foreach (['belum' => 'Belum Selesai', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'] as $st => $label)
                                                <option value="{{ $st }}" @selected($a->status === $st)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <form method="POST" action="{{ route('admin.agenda.destroy', $a) }}" data-confirm="Hapus agenda ini?" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="px-2.5 py-1 text-xs font-bold rounded-lg text-rose-600 hover:bg-rose-50 transition">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 text-center">
                                    <x-empty icon="agenda" title="Belum ada agenda" desc="Tambahkan agenda baru melalui form di sebelah kanan." />
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($items->hasPages())
                    <div class="p-3.5 border-t border-slate-100 bg-slate-50/50">
                        {{ $items->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Kolom Kanan: Form Tambah Agenda Baru -->
        <div>
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm space-y-4">
                <div class="flex items-start gap-3 pb-3 border-b border-slate-100">
                    <span class="h-9 w-9 shrink-0 rounded-xl bg-purple-50 text-purple-600 border border-purple-100 grid place-items-center">
                        <x-icon name="plus" class="h-4 w-4" />
                    </span>
                    <div>
                        <h3 class="font-extrabold text-[15px] text-slate-900">Tambah Agenda Baru</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Jadwalkan rapat atau kegiatan dinas.</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="rounded-xl bg-rose-50 border border-rose-200 px-3.5 py-2.5 text-rose-700 text-xs font-medium">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.agenda.store') }}" class="space-y-3 text-sm">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Judul Agenda *</label>
                        <input name="judul" placeholder="mis. Rapat Koordinasi Promosi Pariwisata" required 
                               class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Tanggal *</label>
                            <input type="date" name="tanggal" value="{{ now()->format('Y-m-d') }}" required 
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:outline-none focus:border-purple-400">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Waktu Mulai</label>
                            <input type="time" name="jam_mulai" value="09:00" 
                                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs focus:outline-none focus:border-purple-400">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Lokasi Kegiatan</label>
                        <input name="lokasi" placeholder="mis. Ruang Rapat Kadis / Hotel Tyara Plaza" 
                               class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Kategori</label>
                            <select name="kategori" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs bg-white focus:outline-none focus:border-purple-400">
                                <option value="rapat">Rapat</option>
                                <option value="kegiatan">Kegiatan</option>
                                <option value="kunjungan">Kunjungan</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Bidang Kerja</label>
                            <select name="id_bidang" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs bg-white focus:outline-none focus:border-purple-400">
                                <option value="">Seluruh Dinas</option>
                                @foreach ($bidangs as $b)
                                    <option value="{{ $b->id }}">{{ $b->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Deskripsi / Catatan</label>
                        <textarea name="deskripsi" rows="3" placeholder="Tambahkan catatan agenda atau pakaian dinas yang dianjurkan..." 
                                  class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition"></textarea>
                    </div>

                    <label class="flex items-center gap-2.5 text-xs font-semibold text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-200 cursor-pointer">
                        <input type="checkbox" name="beri_tahu" value="1" checked class="rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                        <span>Kirim notifikasi ke pegawai yang bersangkutan</span>
                    </label>

                    <button type="submit" class="w-full rounded-xl bg-purple-600 hover:bg-purple-700 active:scale-[.98] text-white font-bold py-2.5 transition shadow-sm shadow-purple-500/30">
                        Simpan &amp; Jadwalkan
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection

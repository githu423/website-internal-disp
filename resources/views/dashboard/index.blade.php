@extends('layouts.dashboard')
@section('title', 'Dashboard Utama')
@section('content')
@php $u = auth()->user(); @endphp

<div class="flex flex-wrap items-end justify-between gap-3 mb-5">
    <div>
        <h2 class="text-xl font-extrabold tracking-tight">Assalamu'alaikum, {{ $u->name }} 👋</h2>
        <p class="text-sm text-slate-500 mt-0.5">{{ $u->jabatan ?: 'Selamat datang di SIPARDIS' }} · {{ now()->translatedFormat('d F Y') }}</p>
    </div>
    @if ($u->isAdmin())
        <a href="{{ route('admin.kinerja.index') }}" class="rounded-lg bg-slate-900 text-white text-sm font-bold px-4 py-2.5 hover:bg-slate-700 active:scale-[.98] transition inline-flex items-center gap-2"><x-icon name="plus" class="h-4 w-4" /> Kelola Kinerja / Import</a>
    @endif
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 mb-5">
    <x-stat label="Pegawai Aktif" :value="$jumlahPegawai" sub="NSPK & non-NSPK" tone="sky" icon="pegawai" :href="route('pegawai.index')" />
    <x-stat label="Dokumen" :value="$jumlahDokumen" sub="tersimpan terpusat" tone="violet" icon="dokumen" :href="route('dokumen.index')" />
    <x-stat label="Agenda Terdekat" :value="$agendaMendatang->count() . ' / 5'" :sub="$agendaTerlewat > 0 ? $agendaTerlewat . ' terlewat belum selesai' : 'semua tepat waktu'" tone="amber" icon="agenda" :href="route('agenda.index')" />
    <x-stat :label="'PAD ' . $padTahun" :value="$padPersen !== null ? $padPersen . '%' : '—'" :sub="'Realisasi Rp ' . number_format($padSum?->realisasi ?? 0, 0, ',', '.')" tone="emerald" icon="pad" :href="route('pad.index')" />
</div>

<div class="grid lg:grid-cols-3 gap-4 mb-5">
    <div class="card p-5 lg:col-span-2">
        <div class="flex items-center justify-between mb-2">
            @if ($ringkas)
                <h3 class="font-bold">Tingkat Capaian Keseluruhan per Tahun <span class="text-xs font-semibold text-slate-400">{{ $ringkas['labels'][0] }}–{{ $ringkas['labels'][count($ringkas['labels']) - 1] }}</span></h3>
                <a href="{{ route('kinerja.capaian', ['tampilan' => 'statistik']) }}" class="text-xs font-semibold text-teal-600 hover:underline">Statistik capaian →</a>
            @else
                <h3 class="font-bold">Capaian Kinerja per Bulan <span class="text-xs font-semibold text-slate-400">TA {{ $chart['tahun'] }}</span></h3>
                <a href="{{ route('admin.capaian.index') }}" class="text-xs font-semibold text-teal-600 hover:underline">Impor capaian →</a>
            @endif
        </div>
        @if ($ringkas)
            <p class="text-[11px] text-slate-400 mb-1">Persentase indikator yang mencapai target (≥100%) per tahun — rekap dari berkas capaian kinerja.</p>
            <div class="h-64"><canvas data-chart='@json($ringkas)'></canvas></div>
        @else
            <div class="h-64"><canvas data-chart='@json($chart)'></canvas></div>
            <p class="text-xs text-slate-400 mt-2 text-center">Belum ada berkas capaian kinerja diimpor — untuk sementara grafik garis target bulanan TA {{ $chart['tahun'] }}.</p>
        @endif
    </div>

    <div class="card p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-bold">Pengingat Kegiatan</h3>
            <a href="{{ route('agenda.index') }}" class="text-xs font-semibold text-teal-600 hover:underline">Semua agenda →</a>
        </div>
        @forelse ($agendaMendatang as $a)
            @php $hariIni = $a->tanggal->isToday(); $lewat = $a->tanggal->lt(now()->startOfDay()); @endphp
            <div class="flex items-start gap-3 {{ $loop->last ? '' : 'pb-3 border-b border-slate-100' }}">
                <div class="h-11 w-11 shrink-0 rounded-xl grid place-items-center text-center leading-none {{ $lewat ? 'bg-rose-50 text-rose-600' : ($hariIni ? 'bg-amber-50 text-amber-600' : 'bg-teal-50 text-teal-700') }}">
                    <div><p class="text-base font-extrabold">{{ $a->tanggal->format('d') }}</p><p class="text-[9px] font-bold uppercase">{{ $a->tanggal->translatedFormat('M') }}</p></div>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[13px] font-semibold truncate">{{ $a->judul }}</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $a->jam_mulai ? substr((string) $a->jam_mulai, 0, 5) . ' WIB · ' : '' }}{{ $a->lokasi ?: 'Lokasi menyusul' }}</p>
                    @if ($lewat)<span class="inline-block mt-1 text-[10px] font-bold text-rose-600 bg-rose-50 rounded px-1.5 py-0.5">Terlewat</span>
                    @elseif ($hariIni)<span class="inline-block mt-1 text-[10px] font-bold text-amber-700 bg-amber-50 rounded px-1.5 py-0.5">Hari ini</span>@endif
                </div>
            </div>
        @empty
            <x-empty icon="agenda" title="Belum ada agenda mendatang" desc="Agenda yang dibuat admin/bidang akan muncul di sini sebagai pengingat." />
        @endforelse
    </div>
</div>

<div class="card overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-2 px-5 pt-4 pb-3 border-b border-slate-100">
        <h3 class="font-bold text-[15px]">Kegiatan &amp; Kinerja Dinas <span class="text-xs font-semibold text-slate-400">TA {{ $kinerjaTahun }}</span>
            <span class="ml-1 text-[11px] font-bold bg-slate-100 text-slate-500 rounded-full px-2 py-0.5">{{ $kinerjaSelesai }}/{{ $kinerjaTotal }} tuntas</span></h3>
        <div class="flex items-center gap-3 text-xs font-semibold">
            @if ($u->isAdmin())<a href="{{ route('admin.kinerja.index') }}" class="text-teal-600 hover:underline">Kelola data →</a>@endif
            <a href="{{ route('kinerja.publik') }}" class="text-teal-600 hover:underline">Lihat semua →</a>
        </div>
    </div>
    @if ($kinerja->count())
        <div class="overflow-x-auto thin-scroll">
            <table class="data w-full min-w-[720px]">
                <thead><tr><th class="w-8">#</th><th>Kegiatan / Sub Kegiatan</th><th>Pelaksana</th><th>Target</th><th class="min-w-[190px]">Capaian</th><th>Status</th></tr></thead>
                <tbody>
                @foreach ($kinerja as $k)
                    <tr>
                        <td class="text-slate-400 text-xs">{{ $loop->iteration }}</td>
                        <td>
                            <p class="font-semibold text-[13px] leading-snug">{{ $k->nama }}</p>
                            @if ($k->catatan)<p class="text-[11px] text-amber-600 mt-0.5">⚠ {{ $k->catatan }}</p>@endif
                        </td>
                        <td class="text-xs text-slate-500">{{ $k->bidang?->nama ?? '—' }}</td>
                        <td class="text-xs font-semibold tabular-nums whitespace-nowrap">{{ rtrim(rtrim(number_format((float) $k->target, 2, ',', '.'), '0'), ',') }} <span class="font-normal text-slate-400">{{ $k->satuan }}</span></td>
                        <td><x-prog :persen="$k->persentase" /></td>
                        <td>@php $st = $k->label_status; @endphp
                            <span class="text-[11px] font-bold rounded-full px-2 py-1 whitespace-nowrap {{ $st === 'Tuntas' ? 'bg-emerald-50 text-emerald-700' : ($st === 'Berjalan' ? 'bg-sky-50 text-sky-700' : 'bg-amber-50 text-amber-700') }}">{{ $st }}</span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if ($kinerjaTotal > $kinerja->count())
            <div class="px-5 py-2.5 bg-slate-50 text-xs text-slate-500">Menampilkan {{ $kinerja->count() }} dari {{ $kinerjaTotal }} kegiatan — <a class="font-semibold text-teal-600 hover:underline" href="{{ route('kinerja.publik', ['tahun' => $kinerjaTahun]) }}">lihat semuanya</a>.</div>
        @endif
    @else
        <x-empty icon="kinerja" title="Belum ada data kinerja TA {{ $kinerjaTahun }}"
            desc="{{ $u->isAdmin() ? 'Upload file Kinerja Fisik Sedinas (Excel/CSV) atau tambahkan kegiatan manual lewat menu Kelola Kinerja.' : 'Data akan tampil begitu admin memuat file kinerja.' }}">
            @if ($u->isAdmin())<a href="{{ route('admin.kinerja.index') }}" class="inline-block mt-3 rounded-lg bg-slate-900 text-white text-sm font-bold px-4 py-2 hover:bg-slate-700 transition">Import sekarang</a>@endif
        </x-empty>
    @endif
</div>
@endsection

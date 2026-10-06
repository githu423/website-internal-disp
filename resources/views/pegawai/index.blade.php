@extends('layouts.dashboard')

@section('title', 'Data Pegawai')
@section('header_title')
    <span class="font-bold text-xl tracking-tight text-purple-700">SIPARDIS</span>
@endsection
@section('subtitle', 'Aparatur Sipil Negara Dinas Pariwisata Kabupaten Ciamis')

@section('content')
    @php 
        $u = auth()->user(); 
    @endphp

    <div class="space-y-6">

        <!-- 1. Breadcrumbs Mikro -->
        <nav class="flex items-center text-sm font-medium text-slate-400">
            <a href="{{ route('dashboard') }}" class="hover:text-purple-600 transition">SIPARDIS</a>
            <span class="mx-2 text-slate-300">/</span>
            <span class="text-slate-400">Data &amp; Kinerja</span>
            <span class="mx-2 text-slate-300">/</span>
            <span class="text-slate-700 font-semibold">Data Pegawai</span>
        </nav>

        <!-- 2. Header Halaman & Quick Actions -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pb-2 border-b border-slate-200/60">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Aparatur Dinas Pariwisata</h1>
                    <span
                        class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-purple-50 text-purple-700 border border-purple-200">
                        {{ $statistik['semua'] }} Pegawai
                    </span>
                </div>
                <p class="text-sm text-slate-500 mt-1">Database kepegawaian, jabatan, golongan, masa kerja, dan unit
                    penempatan.</p>
            </div>

            <!-- Quick Actions Bar -->
            <div class="flex flex-wrap items-center gap-2.5">
                <form method="GET" class="flex items-center gap-2">
                    @if ($statusFilter)
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                    @endif
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input name="q" value="{{ request('q') }}"
                            placeholder="{{ $bolehKelengkapan ? 'Cari nama/NIP… atau: belum lengkap' : 'Cari nama, NIP, jabatan…' }}"
                            class="w-64 pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs md:text-sm bg-white focus:outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-500/10 transition">
                    </div>
                    <button
                        class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs md:text-sm font-semibold hover:bg-slate-800 active:scale-95 transition shadow-sm">
                        Cari
                    </button>
                </form>

                @if($u->isAdmin())
                    <a href="{{ route('admin.pegawai.index') }}"
                        class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-xs md:text-sm font-semibold rounded-xl shadow-sm shadow-purple-500/30 hover:bg-purple-700 hover:shadow-purple-600/40 active:scale-95 transition-all duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        Kelola Pegawai
                    </a>
                @endif
            </div>
        </div>

        <!-- 3. Status Filter Tabs (Segmented Pills) -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="inline-flex items-center bg-white border border-slate-200 rounded-xl p-1 shadow-sm">
                @foreach (['' => 'Semua', 'aktif' => 'Aktif', 'nonaktif' => 'Non-Aktif', 'pensiun' => 'Pensiun'] as $sv => $slab)
                    <a href="{{ route('pegawai.index', array_merge(request()->query(), ['status' => $sv ?: null, 'page' => null])) }}"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $statusFilter === $sv ? 'bg-purple-600 text-white shadow-sm' : 'text-slate-600 hover:text-purple-600 hover:bg-slate-50' }}">
                        {{ $slab }}
                    </a>
                @endforeach
            </div>

            @if ($bolehKelengkapan && ($statistik['belum'] > 0 || request('belum')))
                <a href="{{ request()->fullUrlWithQuery(['belum' => request('belum') ? null : 1]) }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-bold transition {{ request('belum') ? 'bg-rose-600 text-white border-rose-600 shadow-sm' : 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                    <span>⚠</span> {{ $statistik['belum'] }} pegawai belum lengkap datanya —
                    {{ request('belum') ? 'tampilkan semua' : 'saring' }}
                </a>
            @endif
        </div>

        <!-- 4. Kontainer Tabel Pegawai -->
        <div class="bg-white border border-slate-200 shadow-sm rounded-xl overflow-hidden">
            <div class="overflow-x-auto thin-scroll">
                <table class="data w-full min-w-[880px]">
                    <thead>
                        <tr
                            class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-[11px] uppercase tracking-wider font-extrabold">
                            <th class="py-3 px-4">Nama &amp; Identitas ASN</th>
                            <th class="py-3 px-4">Jabatan</th>
                            <th class="py-3 px-4">Pangkat / Gol.</th>
                            <th class="py-3 px-4">Pendidikan</th>
                            <th class="py-3 px-4">Umur</th>
                            <th class="py-3 px-4">Masa Kerja</th>
                            <th class="py-3 px-4">Unit / Bidang Kerja</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($pegawai as $p)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="h-10 w-10 rounded-xl bg-gradient-to-br from-purple-600 to-indigo-600 text-white grid place-items-center text-xs font-bold shrink-0 shadow-sm shadow-purple-500/20">
                                            {{ strtoupper(mb_substr($p->nama, 0, 2)) }}
                                        </span>
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <p class="font-bold text-[13px] text-slate-900 leading-snug">{{ $p->nama }}</p>
                                                @if ($p->status === 'nonaktif')
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                        NON-AKTIF
                                                    </span>
                                                @elseif ($p->status === 'pensiun')
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                                        PENSIUN
                                                    </span>
                                                @else
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        AKTIF
                                                    </span>
                                                @endif
                                            </div>
                                            @if (request('belum'))
                                                <p class="text-[11px] text-slate-400 font-mono mt-0.5">
                                                    {{ $p->nip ?: 'Tanpa NIP' }} ·
                                                    {{ $p->jenis_kelamin === 'P' ? '♀ Perempuan' : '♂ Laki-laki' }} ·
                                                    {{ $p->jenis_asn }}
                                                </p>
                                                @if ($bolehKelengkapan && count($p->kolomKurang()))
                                                    <p class="mt-0.5 text-[10.5px] font-bold text-rose-600"
                                                        title="{{ implode(', ', $p->kolomKurang()) }}">
                                                        belum lengkap: {{ $p->ringkasKurang() }}
                                                    </p>
                                                @elseif ($bolehKelengkapan)
                                                    <p class="mt-0.5 text-[10.5px] font-bold text-emerald-600">✓ sudah lengkap</p>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-[13px] text-slate-800 font-medium">
                                    {{ $p->jabatan ?? '—' }}
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-600 font-medium">
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px]">
                                        {{ $p->pangkat_golongan ?? '—' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-600">
                                    {{ $p->pendidikan ?? '—' }}
                                </td>
                                <td class="py-3.5 px-4 text-xs font-semibold whitespace-nowrap text-slate-800"
                                    title="Dihitung dari tanggal lahir">
                                    {{ $p->umur !== null ? $p->umur . ' tahun' : '—' }}
                                    <span
                                        class="block font-normal text-[10.5px] text-slate-400">{{ $p->tanggal_lahir?->translatedFormat('d M Y') }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-xs font-semibold whitespace-nowrap text-slate-800">
                                    {{ $p->masa_kerja['label'] }}
                                    <span class="block font-normal text-[10.5px] text-slate-400">sejak
                                        {{ $p->tmt_cpns ? \Carbon\Carbon::parse($p->tmt_cpns)->translatedFormat('M Y') : '—' }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-xs text-slate-600">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[11px] font-medium">
                                        {{ $p->bidang?->nama ?? 'Dinas' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-10 text-center">
                                    <x-empty icon="pegawai"
                                        title="{{ $modeBelum ? 'Semua pegawai sudah lengkap datanya 🎉' : 'Tidak ada pegawai ditemukan' }}" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($pegawai->hasPages())
                <div class="p-3.5 border-t border-slate-100 bg-slate-50/50">
                    {{ $pegawai->links() }}
                </div>
            @endif
        </div>

        <!-- 5. Card Rekap Statistik Keseluruhan -->
        <div
            class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-500">
            <span class="font-extrabold text-[13px] text-slate-800 flex items-center gap-1.5">
                <span>👥</span> Rekap:
            </span>
            <span><b class="text-sm font-black text-slate-900">{{ $statistik['semua'] }}</b> Total</span>
            <span class="text-sky-700">♂ <b>{{ $statistik['l'] }}</b> Laki-laki</span>
            <span class="text-pink-600">♀ <b>{{ $statistik['p'] }}</b> Perempuan</span>
            <span class="text-purple-700"><b>{{ $statistik['pns'] }}</b> PNS</span>
            <span class="text-indigo-700"><b>{{ $statistik['nonpns'] }}</b> Non-PNS (PPPK)</span>
            <span class="text-emerald-700"><b>{{ $statistik['aktif'] }}</b> Aktif</span>
            <span class="text-amber-700"><b>{{ $statistik['nonaktif'] }}</b> Non-Aktif</span>
            <span class="text-slate-500"><b>{{ $statistik['pensiun'] }}</b> Pensiun</span>
            @if ($bolehKelengkapan)
                <span class="text-emerald-700 font-bold ml-auto">✓ {{ $statistik['lengkap'] }} sudah lengkap</span>
            @endif
        </div>

    </div>
@endsection
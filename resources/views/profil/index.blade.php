@extends('layouts.dashboard')
@section('title', 'Profil Saya')
@section('content')
@php
    $isi = fn ($k) => trim((string) ($p->{$k} ?? ''));
    $ceklis = [
        'Tempat & tanggal lahir' => $isi('tempat_lahir') !== '' && $isi('tanggal_lahir') !== '',
        'Jenis kelamin' => $isi('jenis_kelamin') !== '',
        'Pangkat / golongan' => $isi('pangkat_golongan') !== '',
        'Pendidikan terakhir' => $isi('pendidikan') !== '',
        'Unit kerja' => $isi('unit_kerja') !== '',
        'TMT CPNS' => $isi('tmt_cpns') !== '',
        'TMT pangkat sekarang' => $isi('tmt_sekarang') !== '',
        'TMT jabatan' => $isi('tmt_jabatan') !== '',
    ];
    $terisi = count(array_filter($ceklis)); $total = count($ceklis);
    $inp = 'h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm outline-none focus:ring-2 focus:ring-teal-500/30';
@endphp

<div class="grid lg:grid-cols-3 gap-4 items-start">
    <div class="lg:col-span-2 space-y-4 min-w-0">
        <div class="card p-4 sm:p-5">
            <h3 class="font-bold">Isi data kepegawaianmu</h3>
            <p class="text-[12px] text-slate-400 mt-0.5 mb-4">Kolom yang kamu isi di sini langsung melengkapi barismu di
                <a class="text-teal-600 font-semibold hover:underline" href="{{ route('pegawai.index') }}">Data Pegawai</a>,
                dan masa kerja (tahun · bulan · hari) dihitung ulang otomatis mengikuti tanggal hari ini.
                Kosongkan field bila belum tahu — jangan mengarang.</p>

            @if (! $p)
                <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-[12px] text-amber-800">
                    Namamu belum ada di daftar pegawai. Menyimpan form ini akan
                    <b>membuatkan entri baru</b> ber-NIP <b>{{ $u->nip ?: '—' }}</b>.
                    @if (! $u->nip) <br>Minta admin mengisi NIP kamu dulu di Admin ▸ Akun & Akses, lalu simpan lagi di sini. @endif
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-[12px] text-rose-700">
                    <b>Periksa isian:</b> {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('profil.simpan') }}" class="grid sm:grid-cols-2 gap-3">
                @csrf @method('PATCH')
                <label class="sm:col-span-2 block">
                    <span class="label">Nama lengkap <i class="text-rose-500 not-italic">*</i></span>
                    <input class="{{ $inp }}" name="name" maxlength="120" required value="{{ old('name', $u->name) }}">
                </label>
                <label class="block">
                    <span class="label">Tempat lahir</span>
                    <input class="{{ $inp }}" name="tempat_lahir" maxlength="100" placeholder="mis. Ciamis" value="{{ old('tempat_lahir', $isi('tempat_lahir')) }}">
                </label>
                <label class="block">
                    <span class="label">Tanggal lahir</span>
                    <input type="date" class="{{ $inp }}" name="tanggal_lahir" value="{{ old('tanggal_lahir', $p?->tanggal_lahir ?? '') }}">
                </label>
                <label class="block">
                    <span class="label">Jenis kelamin</span>
                    <select class="{{ $inp }}" name="jenis_kelamin">
                        <option value="">— pilih —</option>
                        <option value="L" @selected(old('jenis_kelamin', $isi('jenis_kelamin')) === 'L')>Laki-laki</option>
                        <option value="P" @selected(old('jenis_kelamin', $isi('jenis_kelamin')) === 'P')>Perempuan</option>
                    </select>
                </label>
                <label class="block">
                    <span class="label">Jenis ASN</span>
                    <select class="{{ $inp }}" name="jenis_asn">
                        <option value="">— pilih —</option>
                        <option value="PNS" @selected(old('jenis_asn', $isi('jenis_asn')) === 'PNS')>PNS</option>
                        <option value="PPPK" @selected(old('jenis_asn', $isi('jenis_asn')) === 'PPPK')>PPPK</option>
                    </select>
                </label>
                <label class="block">
                    <span class="label">Pangkat / golongan</span>
                    <input class="{{ $inp }}" name="pangkat_golongan" maxlength="80" list="pangkat-op" placeholder="mis. PENATA (III/c)" value="{{ old('pangkat_golongan', $isi('pangkat_golongan')) }}">
                    <datalist id="pangkat-op">
                        @foreach (['JURU (II/a)','JURU (II/b)','JURU (II/c)','PENGATUR (II/d)','PENGATUR (II/e)','PENGATUR (II/f)','PENATA (III/a)','PENATA (III/b)','PENATA (III/c)','PENATA (III/d)','PENATA TK. I (IV/a)','PENATA TK. I (IV/b)','PENATA TK. I (IV/c)'] as $pk)
                        <option value="{{ $pk }}">@endforeach
                    </datalist>
                </label>
                <label class="block">
                    <span class="label">Pendidikan terakhir</span>
                    <input class="{{ $inp }}" name="pendidikan" maxlength="60" placeholder="mis. S1 Hukum" value="{{ old('pendidikan', $isi('pendidikan')) }}">
                </label>
                <label class="block sm:col-span-2">
                    <span class="label">Unit kerja</span>
                    <input class="{{ $inp }}" name="unit_kerja" maxlength="150" placeholder="mis. Sekretariat" value="{{ old('unit_kerja', $isi('unit_kerja')) }}">
                </label>
                <label class="block sm:col-span-2">
                    <span class="label">Jabatan</span>
                    <input class="{{ $inp }}" name="jabatan" maxlength="150" value="{{ old('jabatan', $isi('jabatan') ?: $u->jabatan) }}">
                </label>
                <label class="block">
                    <span class="label">TMT CPNS</span>
                    <input type="date" class="{{ $inp }}" name="tmt_cpns" value="{{ old('tmt_cpns', $p?->tmt_cpns ?? '') }}">
                </label>
                <label class="block">
                    <span class="label">TMT pangkat sekarang</span>
                    <input type="date" class="{{ $inp }}" name="tmt_sekarang" value="{{ old('tmt_sekarang', $p?->tmt_sekarang ?? '') }}">
                </label>
                <label class="block">
                    <span class="label">TMT jabatan</span>
                    <input type="date" class="{{ $inp }}" name="tmt_jabatan" value="{{ old('tmt_jabatan', $p?->tmt_jabatan ?? '') }}">
                </label>
                <div class="sm:col-span-2 flex items-center justify-between gap-3 pt-1">
                    <p class="text-[11.5px] text-slate-400">Perubahan tercatat di log aktivitas. Admin tetap bisa mengoreksi lewat Excel.</p>
                    <button class="btn-main shrink-0">Simpan profil</button>
                </div>
            </form>
        </div>
    </div>

    <div class="space-y-4">
        <div class="card p-5">
            <div class="flex items-center gap-3 mb-4">
                <span class="h-11 w-11 rounded-full bg-teal-600 grid place-items-center text-white text-sm font-bold">{{ strtoupper(mb_substr($u->name, 0, 2)) }}</span>
                <div class="min-w-0">
                    <p class="font-bold truncate">{{ $u->name }}</p>
                    <p class="text-[11.5px] text-slate-400">{{ $u->nip ?: 'tanpa NIP' }} · {{ ucfirst($u->role) }}</p>
                </div>
            </div>
            <dl class="text-[12.5px] space-y-2">
                <div class="flex items-baseline justify-between gap-3"><dt class="text-slate-400 shrink-0">Bidang</dt><dd class="font-semibold text-right">{{ $u->bidang?->nama ?? 'Seluruh Dinas' }}</dd></div>
                <div class="flex items-baseline justify-between gap-3"><dt class="text-slate-400 shrink-0">Jabatan</dt><dd class="font-semibold text-right">{{ $isi('jabatan') ?: ($u->jabatan ?: '—') }}</dd></div>
                <div class="flex items-baseline justify-between gap-3"><dt class="text-slate-400 shrink-0">Masa kerja</dt><dd class="font-semibold text-right text-teal-700" title="dihitung real-time sampai hari ini">{{ $p?->masa_kerja['label'] ?? '—' }}</dd></div>
                <div class="flex items-baseline justify-between gap-3"><dt class="text-slate-400 shrink-0">TMT CPNS</dt><dd class="font-semibold text-right">{{ $isi('tmt_cpns') ? \Carbon\Carbon::parse($isi('tmt_cpns'))->translatedFormat('d M Y') : '—' }}</dd></div>
            </dl>
        </div>

        <div class="card p-5">
            <div class="flex items-baseline justify-between mb-1">
                <h4 class="font-bold text-[13.5px]">Kelengkapan data</h4>
                <span class="text-[11.5px] font-bold {{ $terisi === $total ? 'text-teal-600' : 'text-amber-600' }}">{{ $terisi }}/{{ $total }}</span>
            </div>
            <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden mb-3"><span class="block h-full rounded-full {{ $terisi === $total ? 'bg-teal-500' : 'bg-amber-400' }} transition-all" style="width: {{ round($terisi / $total * 100) }}%"></span></div>
            <ul class="space-y-1 text-[12px]">
                @foreach ($ceklis as $nama => $sudah)
                    <li class="flex items-center gap-2 {{ $sudah ? 'text-slate-500' : 'text-slate-700' }}">
                        <span class="h-4 w-4 grid place-items-center rounded-full text-[9px] {{ $sudah ? 'bg-teal-100 text-teal-600' : 'bg-amber-100 text-amber-600' }}">{{ $sudah ? '✓' : '•' }}</span>
                        <span class="{{ $sudah ? 'line-through decoration-slate-300' : 'font-medium' }}">{{ $nama }}</span>
                    </li>
                @endforeach
            </ul>
            @if ($terisi === $total)
                <p class="mt-3 text-[11.5px] text-teal-700 font-semibold">Lengkap! Datamu sudah tampil utuh di daftar pegawai.</p>
            @else
                <p class="mt-3 text-[11.5px] text-slate-400">Isi yang tersisa supaya kolom "—" di daftar pegawai terisi dan masa kerjamu akurat.</p>
            @endif
        </div>
    </div>
</div>
@endsection

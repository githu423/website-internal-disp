@extends('tamu-rapat.layout')

@section('title', 'Masukkan PIN')

@section('content')
    <div class="mx-auto max-w-lg">
        <div class="mb-5 text-center">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl border border-purple-100 bg-purple-50 text-purple-600 shadow-sm">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M7 10V7a5 5 0 0110 0v3M5 10h14v11H5z" />
                    <path d="M12 14v3" />
                </svg>
            </span>
            <p class="mt-4 text-xs font-extrabold uppercase tracking-[.16em] text-purple-600">Akses peserta rapat</p>
            <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">Masukkan PIN</h1>
            <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-slate-500">Tautan ini dilindungi PIN. Masukkan PIN yang diberikan oleh penyelenggara untuk melihat dokumen rapat.</p>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <div class="mb-5 rounded-xl border border-purple-100 bg-purple-50/60 p-4">
                <p class="text-[10px] font-extrabold uppercase tracking-wide text-purple-600">Ruang Rapat</p>
                <h2 class="mt-1 text-sm font-bold text-slate-900">{{ $ruangRapat->judul }}</h2>
                @if ($ruangRapat->tanggal_rapat)
                    <p class="mt-1 text-xs text-slate-500">{{ $ruangRapat->tanggal_rapat->translatedFormat('d F Y, H:i') }}</p>
                @endif
            </div>

            @if ($errors->has('pin'))
                <div role="alert" class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-3 text-sm font-medium text-rose-700">{{ $errors->first('pin') }}</div>
            @endif

            <form method="POST" action="{{ route('rapat.pin', ['token' => $ruangRapat->token]) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="pin" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-600">PIN akses</label>
                    <input id="pin" name="pin" type="password" required autofocus autocomplete="one-time-code" inputmode="numeric" maxlength="64" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-3 text-sm text-slate-800 outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20" placeholder="Masukkan PIN">
                </div>
                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-sm shadow-purple-500/30 transition hover:from-purple-700 hover:to-indigo-700">
                    Verifikasi PIN
                </button>
            </form>
            <p class="mt-4 text-center text-[11px] leading-relaxed text-slate-400">Belum menerima PIN? Silakan hubungi penyelenggara rapat.</p>
        </section>
    </div>
@endsection

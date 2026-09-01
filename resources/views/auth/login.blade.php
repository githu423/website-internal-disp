<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · SIPARDIS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-900 text-slate-100 grid place-items-center p-4" style="background-image: radial-gradient(60rem 30rem at 50% -10%, rgba(45,212,191,.16), transparent), radial-gradient(40rem 20rem at 90% 110%, rgba(14,165,233,.14), transparent);">
<div class="w-full max-w-md">
    <div class="text-center mb-7">
        <img src="{{ asset('images/logo-dispar.jpg') }}" alt="Logo SIPARDIS" class="mx-auto h-14 w-14 rounded-2xl object-cover shadow-lg shadow-teal-500/20" />
        <h1 class="mt-3.5 text-2xl font-extrabold tracking-tight">SIPARDIS</h1>
        <p class="text-sm text-slate-400 mt-1">Sistem Informasi Pendapatan, Aparatur &amp; Rencana Dinas<br>Dinas Pariwisata Kabupaten Ciamis</p>
    </div>
    <div class="bg-white text-slate-800 rounded-2xl shadow-2xl p-6 sm:p-8">
        <h2 class="text-lg font-bold">Masuk ke akun dinas</h2>
        <p class="text-sm text-slate-500 mt-0.5 mb-5">Gunakan <b>NIP</b> (atau user admin) dan password Anda.</p>
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 px-3.5 py-2.5 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">NIP / Username</label>
                <input name="nip" value="{{ old('nip') }}" required autofocus autocomplete="username"
                    class="mt-1.5 w-full rounded-lg border-slate-300 border px-3.5 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/30 outline-none transition bg-white placeholder:text-slate-400" placeholder="cth: 1975021119960110 01">
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Password</label>
                <input type="password" name="password" required autocomplete="current-password"
                    class="mt-1.5 w-full rounded-lg border-slate-300 border px-3.5 py-2.5 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/30 outline-none transition bg-white">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500/30"> Ingat saya di perangkat ini</label>
            <button class="w-full rounded-lg bg-gradient-to-r from-teal-500 to-sky-500 hover:from-teal-600 hover:to-sky-600 active:scale-[.99] text-white font-bold py-2.5 text-sm shadow-lg shadow-teal-500/25 transition">Masuk</button>
        </form>
    </div>
    <p class="text-center text-[11px] text-slate-500 mt-5">Untuk demo: admin <code class="text-slate-300">ADMIN0000 / admin123</code> · pegawai login dengan NIP.</p>
</div>
</body>
</html>

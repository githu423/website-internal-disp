<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#7e22ce">
    <title>@yield('title', 'Ruang Rapat') · SIPARDIS</title>
    <link rel="icon" href="{{ asset('images/logo-dispar.jpg') }}">
    <script>
        (function () {
            try {
                var tema = localStorage.getItem('tema');
                if (tema === 'gelap' || (!tema && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-slate-200 bg-white/90 shadow-sm">
            <div class="mx-auto flex max-w-5xl items-center gap-3 px-4 py-4 sm:px-6">
                <img src="{{ asset('images/logo-dispar.jpg') }}" alt="Logo Dinas Pariwisata Kabupaten Ciamis" class="h-11 w-11 rounded-xl border border-slate-100 object-cover shadow-sm">
                <div class="min-w-0">
                    <p class="text-base font-extrabold tracking-tight text-purple-700">SIPARDIS</p>
                    <p class="truncate text-xs text-slate-500">Dinas Pariwisata Kabupaten Ciamis</p>
                </div>
                <span class="hidden rounded-full border border-purple-100 bg-purple-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-purple-700 sm:inline-flex">Ruang Rapat</span>
                <button id="tema-btn" type="button" title="Ganti mode gelap / terang" aria-label="Ganti mode gelap atau terang" class="ml-auto grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-purple-300 hover:bg-slate-50 hover:text-purple-600">
                    <svg data-tema-ikon="terang" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
                    </svg>
                    <svg data-tema-ikon="gelap" class="hidden h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m9-9h-1.5m-15 0H3m15.36-6.36l-1.06 1.06M6.7 17.3l-1.06 1.06m12.72 0l-1.06-1.06M6.7 6.7L5.64 5.64M16.5 12a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                    </svg>
                </button>
            </div>
        </header>

        <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-8 sm:px-6 sm:py-10">
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 bg-white/80">
            <div class="mx-auto flex max-w-5xl flex-col gap-1 px-4 py-5 text-center text-[11px] text-slate-400 sm:px-6">
                <p class="font-semibold text-slate-500">Portal dokumen rapat · SIPARDIS</p>
                <p>&copy; {{ date('Y') }} Dinas Pariwisata Kabupaten Ciamis</p>
            </div>
        </footer>
    </div>
</body>
</html>

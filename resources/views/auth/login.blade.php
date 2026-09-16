<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · SIPARDIS</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏛️</text></svg>">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    /* ===== Latar "Tatar Galuh Ciamis" v4 ===================================
       Selaras dengan tema dashboard: TERANG + aksen UNGU (identitas Kab. Ciamis).
       Siluet khas Ciamis di sekitar kartu:
       • GUNUNG SAWAL — puncak dominan kiri & punggungan kanan, tengah lembah rendah.
       • SITU LENGKONG PANJALU — air + tepian + Nusa Larang + perahu beralayar.
       • SAWAH BERUNDAK — terasering di garis paling bawah. */
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
    .panggung { position: fixed; inset: 0; z-index: 0; overflow: hidden; perspective: 10rem;
        background:
            radial-gradient(60rem 30rem at 50% -10%, rgba(168,85,247,.14), transparent 70%),
            radial-gradient(40rem 20rem at 88% 104%, rgba(99,102,241,.12), transparent 70%),
            linear-gradient(180deg, #faf5ff 0%, #f5f3ff 42%, #ede9fe 66%, #e0e7ff 100%);
    }
    .dunia { position: absolute; inset: 0; transform-style: preserve-3d;
        animation: goyang 34s ease-in-out infinite alternate; }
    @keyframes goyang { from { transform: translate3d(-1.4%, 0, 0); } to { transform: translate3d(1.4%, .4%, 0); } }

    .lapisan { position: absolute; left: -25%; width: 150%; bottom: 0; }

    /* — GUNUNG SAWAL (belakang) */
    .sawal { height: 38%; z-index: 1; transform: translateZ(6px); background: #c4b5fd; opacity: .30;
        clip-path: polygon(0% 72%, 6% 56%, 12% 63%, 19% 26%, 25% 40%, 32% 64%, 40% 78%, 50% 84%, 58% 78%, 66% 62%, 73% 36%, 80% 44%, 86% 58%, 92% 66%, 100% 72%, 100% 100%, 0% 100%); }

    /* — SITU LENGKONG PANJALU (tengah) */
    .situ { height: 17%; z-index: 2; transform: translateZ(20px); opacity: .24; }
    .situ .air { position: absolute; bottom: 0; left: 0; width: 100%; height: 55%; background: #a5b4fc;
        border-top: 1px solid rgba(99,102,241,.30); }
    .situ .tepian { position: absolute; bottom: 54%; left: 0; width: 100%; height: 26%; background: #93a5f3;
        clip-path: polygon(0% 100%, 3% 62%, 6% 100%, 10% 70%, 14% 100%, 18% 58%, 22% 100%, 26% 68%, 30% 100%, 34% 60%, 38% 100%, 42% 72%, 46% 100%, 50% 62%, 54% 100%, 58% 70%, 62% 100%, 66% 58%, 70% 100%, 74% 68%, 78% 100%, 82% 62%, 86% 100%, 90% 70%, 94% 100%, 97% 74%, 100% 100%); }
    .situ .pulau { position: absolute; bottom: 53%; left: 30%; width: 8%; height: 34%; background: #818cf8;
        border-radius: 50% 50% 0 0 / 100% 100% 0 0; }
    .situ .pohonl { position: absolute; bottom: 24%; width: 0; height: 0;
        border-left: 3px solid transparent; border-right: 3px solid transparent; border-bottom: 9px solid #818cf8; }
    .situ .batu { position: absolute; bottom: 53%; left: 66%; width: 2.4%; height: 12%; background: #818cf8;
        border-radius: 50% 50% 0 0 / 100% 100% 0 0; }

    /* — SAWAH BERUNDAK (depan) */
    .sawah { height: 11%; z-index: 3; transform: translateZ(38px); background: #ddd6fe; opacity: .30;
        clip-path: polygon(0% 74%, 12% 74%, 12% 62%, 26% 62%, 26% 50%, 41% 50%, 41% 36%, 58% 36%, 58% 50%, 73% 50%, 73% 62%, 87% 62%, 87% 74%, 100% 74%, 100% 100%, 0% 100%); }

    /* — perahu beralayar di Situ Lengkong */
    .perahu { position: absolute; bottom: 3%; left: 42%; width: 28px; height: 18px; z-index: 4;
        transform: translateZ(26px);
        animation: meluncur 26s ease-in-out infinite alternate; }
    .perahu .lambung { position: absolute; bottom: 0; left: 0; width: 28px; height: 7px; background: #7c3aed; opacity: .45;
        clip-path: polygon(0% 35%, 100% 35%, 80% 100%, 20% 100%); }
    .perahu .layar { position: absolute; bottom: 6px; left: 13px; width: 0; height: 0; opacity: .5;
        border-left: 5px solid transparent; border-right: 5px solid transparent; border-bottom: 12px solid #7c3aed; }
    @keyframes meluncur { from { transform: translate3d(-12px, 0, 0); } to { transform: translate3d(18px, -1px, 0); } }

    /* — burung melintas di langit atas */
    .burung { position: absolute; left: -12%; opacity: 0; line-height: 0;
        animation: terbang 95s linear infinite; }
    @keyframes terbang {
        0%   { transform: translate3d(0, 0, 0); opacity: 0; }
        6%   { opacity: .5; }
        94%  { opacity: .5; }
        100% { transform: translate3d(130vw, -1vh, 0); opacity: 0; }
    }

    /* — kunang-kunang */
    .kunang { position: absolute; width: 4px; height: 4px; border-radius: 9999px;
        background: rgba(168,85,247,.8); box-shadow: 0 0 5px rgba(168,85,247,.5);
        animation: melayang 19s ease-in-out infinite alternate, kedip 7s ease-in-out infinite; }
    @keyframes melayang { from { transform: translate3d(0,0,0); } to { transform: translate3d(2.5vw,-3vh,0); } }
    @keyframes kedip { 0%,100% { opacity: .08; } 50% { opacity: .5; } }

    .konten { position: relative; z-index: 10; }

    /* Hormati prefers-reduced-motion */
    @media (prefers-reduced-motion: reduce) {
        .dunia, .kunang, .perahu, .burung { animation: none !important; }
        .kunang { opacity: .4; }
        .burung { display: none; }
    }
    </style>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 grid place-items-center p-4 relative overflow-hidden">
<div class="panggung" aria-hidden="true">
    <div class="dunia">
        <div class="lapisan sawal"></div>
        <div class="lapisan situ">
            <div class="air"></div>
            <div class="tepian"></div>
            <div class="pulau"><span class="pohonl" style="left: 26%;"></span><span class="pohonl" style="left: 55%; bottom: 32%;"></span></div>
            <span class="batu"></span>
        </div>
        <div class="lapisan sawah"></div>
        <div class="perahu"><span class="lambung"></span><span class="layar"></span></div>
    </div>
    <span class="burung" style="top: 9%; animation-duration: 95s; animation-delay: -38s;"><svg width="17" height="8" viewBox="0 0 18 8" fill="none"><path d="M1 6 Q5 1 9 6 Q13 1 17 6" stroke="rgba(100,116,139,.45)" stroke-width="1.3" stroke-linecap="round"/></svg></span>
    <span class="burung" style="top: 13%; animation-duration: 115s; animation-delay: -72s;"><svg width="13" height="6" viewBox="0 0 18 8" fill="none"><path d="M1 6 Q5 1 9 6 Q13 1 17 6" stroke="rgba(100,116,139,.4)" stroke-width="1.4" stroke-linecap="round"/></svg></span>
    <span class="burung" style="top: 7%; animation-duration: 130s; animation-delay: -12s;"><svg width="10" height="5" viewBox="0 0 18 8" fill="none"><path d="M1 6 Q5 1 9 6 Q13 1 17 6" stroke="rgba(100,116,139,.35)" stroke-width="1.5" stroke-linecap="round"/></svg></span>
    <span class="kunang" style="top: 62%; left: 7%;  animation-delay: -2s, -1s;"></span>
    <span class="kunang" style="top: 71%; left: 15%; animation-delay: -8s, -4s; animation-duration: 23s, 9s;"></span>
    <span class="kunang" style="top: 58%; left: 84%; animation-delay: -13s, -2s;"></span>
    <span class="kunang" style="top: 68%; left: 91%; animation-delay: -5s, -6s; animation-duration: 21s, 8s;"></span>
</div>

<div class="w-full max-w-md konten">
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center h-14 w-14 rounded-2xl bg-white border border-slate-200 shadow-sm">
            <img src="{{ asset('images/logo-dispar.jpg') }}" alt="Logo SIPARDIS" class="h-12 w-12 rounded-xl object-cover" />
        </div>
        <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-purple-700">SIPARDIS</h1>
        <div class="mx-auto mt-1.5 h-1 w-12 rounded-full bg-gradient-to-r from-purple-500 to-amber-400"></div>
        <p class="text-sm mt-3 text-slate-500">Sistem Informasi Pariwisata Dispar<br><span class="text-slate-400">Dinas Pariwisata Kabupaten Ciamis</span></p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <h2 class="text-lg font-bold text-slate-800">Masuk ke akun dinas</h2>
        <p class="text-sm text-slate-500 mt-0.5 mb-5">Gunakan <b class="text-slate-700">NIP</b> (atau user admin) dan password Anda.</p>
        @if ($errors->any())
            <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 px-3.5 py-2.5 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">NIP / Username</label>
                <input name="nip" value="{{ old('nip') }}" required autofocus autocomplete="username"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 outline-none transition shadow-sm"
                    placeholder="cth: 1975021119960110 01">
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Password</label>
                <div class="relative mt-1.5">
                    <input id="password-field" type="password" name="password" required autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full rounded-xl border border-slate-200 bg-white pl-3.5 pr-11 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-purple-400 focus:ring-2 focus:ring-purple-500/20 outline-none transition shadow-sm">
                    <button type="button" id="toggle-password" title="Lihat / Sembunyikan Password"
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-purple-600 focus:outline-none transition cursor-pointer">
                        <!-- Ikon Mata Terbuka (saat terlihat) -->
                        <svg id="eye-open" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <!-- Ikon Mata Tertutup (saat tersembunyi) -->
                        <svg id="eye-closed" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-purple-600 focus:ring-purple-500/30"> Ingat saya di perangkat ini
            </label>
            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 active:scale-[.99] text-white font-bold py-2.5 text-sm shadow-sm shadow-purple-500/30 transition">
                Masuk ke Sistem
            </button>
        </form>
    </div>

    <p class="text-center text-[11px] text-slate-400 mt-5">
        Untuk masuk, gunakan <code class="text-purple-600">NIP</code> &amp; password Anda.<br>
        Password standar: <code class="text-slate-500">DISPARCiamis2026</code>
    </p>
    <p class="text-center text-[10.5px] text-amber-600/80 font-semibold tracking-wide mt-3">✦ Mahayunan Ayunan Kadatuan — Tatar Galuh Ciamis ✦</p>
</div>

<script>
    document.getElementById('toggle-password')?.addEventListener('click', function () {
        const input = document.getElementById('password-field');
        const eyeOpen = document.getElementById('eye-open');
        const eyeClosed = document.getElementById('eye-closed');
        if (!input) return;
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        if (eyeOpen && eyeClosed) {
            eyeOpen.classList.toggle('hidden', !isPassword);
            eyeClosed.classList.toggle('hidden', isPassword);
        }
    });
</script>
</body>
</html>

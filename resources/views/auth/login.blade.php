<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · SIPARDIS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
    /* ===== Latar "Malam Ciamis" v3 (11 Sep 2026) =================================
       Form tetap fokus utama: kartu solid gelap + blur (TIDAK diubah). Siluet
       khas Ciamis dipromosikan ke AREA KOSONG di sekitar kartu:
       • GUNUNG SAWAL — puncak dominan kiri & punggungan kanan menjulang DI
         SISI KARTU (kiri/kanan); bagian tengah dibuat lemah/lembah rendah
         supaya area tepat di belakang kartu tetap bersih.
       • SITU LENGKONG PANJALU — band danau + Nusa Larang + perahu, membentang
         di dasar layar DI BAWAH form.
       • SAWAH BERUNDAK — lapis terdekat di garis paling bawah.
       Semua murni CSS, opacity 18-22% (hanya "napas"), parallax perspective+
       translateZ dengan SATU induk beranimasi. Hormat prefers-reduced-motion. */
    .panggung { position: fixed; inset: 0; z-index: 0; overflow: hidden; perspective: 10rem;
        background:
            radial-gradient(60rem 30rem at 50% -10%, rgba(45,212,191,.16), transparent 70%),
            radial-gradient(40rem 20rem at 88% 104%, rgba(99,102,241,.14), transparent 70%),
            linear-gradient(180deg, #020617 0%, #052b33 42%, #0d3c46 62%, #1d2a63 82%, #262b6e 100%);
    }
    .dunia { position: absolute; inset: 0; transform-style: preserve-3d;
        animation: goyang 34s ease-in-out infinite alternate; }
    @keyframes goyang { from { transform: translate3d(-1.4%, 0, 0); } to { transform: translate3d(1.4%, .4%, 0); } }

    .lapisan { position: absolute; left: -25%; width: 150%; bottom: 0; }

    /* — GUNUNG SAWAL (belakang): puncak dominan kiri (~x19%) + puncak sekunder
       kanan (~x73%) — keduanya di SISI kartu; tengah (x40-60%) lembah rendah. */
    .sawal { height: 38%; z-index: 1; transform: translateZ(6px); background: #33415c; opacity: .18;
        clip-path: polygon(0% 72%, 6% 56%, 12% 63%, 19% 26%, 25% 40%, 32% 64%, 40% 78%, 50% 84%, 58% 78%, 66% 62%, 73% 36%, 80% 44%, 86% 58%, 92% 66%, 100% 72%, 100% 100%, 0% 100%); }

    /* — SITU LENGKONG PANJALU (tengah): air, barisan pohon tepian, pulau
       Nusa Larang (kiri-tengah) dengan pepohonan, + perahu beralayar. */
    .situ { height: 17%; z-index: 2; transform: translateZ(20px); opacity: .20; }
    .situ .air { position: absolute; bottom: 0; left: 0; width: 100%; height: 55%; background: #103049;
        border-top: 1px solid rgba(125,211,252,.18); }
    .situ .tepian { position: absolute; bottom: 54%; left: 0; width: 100%; height: 26%; background: #0d2434;
        clip-path: polygon(0% 100%, 3% 62%, 6% 100%, 10% 70%, 14% 100%, 18% 58%, 22% 100%, 26% 68%, 30% 100%, 34% 60%, 38% 100%, 42% 72%, 46% 100%, 50% 62%, 54% 100%, 58% 70%, 62% 100%, 66% 58%, 70% 100%, 74% 68%, 78% 100%, 82% 62%, 86% 100%, 90% 70%, 94% 100%, 97% 74%, 100% 100%); }
    .situ .pulau { position: absolute; bottom: 53%; left: 30%; width: 8%; height: 34%; background: #0d2434;
        border-radius: 50% 50% 0 0 / 100% 100% 0 0; }
    .situ .pohonl { position: absolute; bottom: 24%; width: 0; height: 0;
        border-left: 3px solid transparent; border-right: 3px solid transparent; border-bottom: 9px solid #0d2434; }
    .situ .batu { position: absolute; bottom: 53%; left: 66%; width: 2.4%; height: 12%; background: #0d2434;
        border-radius: 50% 50% 0 0 / 100% 100% 0 0; }

    /* — SAWAH BERUNDAK (depan): tangga terasering memusat, garis paling bawah. */
    .sawah { height: 11%; z-index: 3; transform: translateZ(38px); background: #081620; opacity: .22;
        clip-path: polygon(0% 74%, 12% 74%, 12% 62%, 26% 62%, 26% 50%, 41% 50%, 41% 36%, 58% 36%, 58% 50%, 73% 50%, 73% 62%, 87% 62%, 87% 74%, 100% 74%, 100% 100%, 0% 100%); }

    /* — perahu beralayar di Situ Lengkong: meluncur sangat pelan (parallax ikut danau) */
    .perahu { position: absolute; bottom: 3%; left: 42%; width: 28px; height: 18px; z-index: 4;
        transform: translateZ(26px);
        animation: meluncur 26s ease-in-out infinite alternate; }
    .perahu .lambung { position: absolute; bottom: 0; left: 0; width: 28px; height: 7px; background: #0a1f2e; opacity: .65;
        clip-path: polygon(0% 35%, 100% 35%, 80% 100%, 20% 100%); }
    .perahu .layar { position: absolute; bottom: 6px; left: 13px; width: 0; height: 0; opacity: .6;
        border-left: 5px solid transparent; border-right: 5px solid transparent; border-bottom: 12px solid #0c2637; }
    @keyframes meluncur { from { transform: translate3d(-12px, 0, 0); } to { transform: translate3d(18px, -1px, 0); } }

    /* — burung melintas di langit atas: kecil, jauh, pucat */
    .burung { position: absolute; left: -12%; opacity: 0; line-height: 0;
        animation: terbang 95s linear infinite; }
    @keyframes terbang {
        0%   { transform: translate3d(0, 0, 0); opacity: 0; }
        6%   { opacity: .55; }
        94%  { opacity: .55; }
        100% { transform: translate3d(130vw, -1vh, 0); opacity: 0; }
    }

    /* — kunang-kunang: 4 titik redup, tenang */
    .kunang { position: absolute; width: 4px; height: 4px; border-radius: 9999px;
        background: rgba(186,230,253,.8); box-shadow: 0 0 5px rgba(94,234,212,.5);
        animation: melayang 19s ease-in-out infinite alternate, kedip 7s ease-in-out infinite; }
    @keyframes melayang { from { transform: translate3d(0,0,0); } to { transform: translate3d(2.5vw,-3vh,0); } }
    @keyframes kedip { 0%,100% { opacity: .06; } 50% { opacity: .5; } }

    .konten { position: relative; z-index: 10; }

    /* WAJIB: hormati pengaturan "kurangi gerakan" — semua diam, burung sembunyi */
    @media (prefers-reduced-motion: reduce) {
        .dunia, .kunang, .perahu, .burung { animation: none !important; }
        .kunang { opacity: .4; }
        .burung { display: none; }
    }
    </style>
</head>
<body class="min-h-screen text-slate-100 grid place-items-center p-4">
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
    <span class="burung" style="top: 9%; animation-duration: 95s; animation-delay: -38s;"><svg width="17" height="8" viewBox="0 0 18 8" fill="none"><path d="M1 6 Q5 1 9 6 Q13 1 17 6" stroke="rgba(226,232,240,.6)" stroke-width="1.3" stroke-linecap="round"/></svg></span>
    <span class="burung" style="top: 13%; animation-duration: 115s; animation-delay: -72s;"><svg width="13" height="6" viewBox="0 0 18 8" fill="none"><path d="M1 6 Q5 1 9 6 Q13 1 17 6" stroke="rgba(226,232,240,.45)" stroke-width="1.4" stroke-linecap="round"/></svg></span>
    <span class="burung" style="top: 7%; animation-duration: 130s; animation-delay: -12s;"><svg width="10" height="5" viewBox="0 0 18 8" fill="none"><path d="M1 6 Q5 1 9 6 Q13 1 17 6" stroke="rgba(226,232,240,.4)" stroke-width="1.5" stroke-linecap="round"/></svg></span>
    <span class="kunang" style="top: 62%; left: 7%;  animation-delay: -2s, -1s;"></span>
    <span class="kunang" style="top: 71%; left: 15%; animation-delay: -8s, -4s; animation-duration: 23s, 9s;"></span>
    <span class="kunang" style="top: 58%; left: 84%; animation-delay: -13s, -2s;"></span>
    <span class="kunang" style="top: 68%; left: 91%; animation-delay: -5s, -6s; animation-duration: 21s, 8s;"></span>
</div>
<div class="w-full max-w-md konten">
    <div class="text-center mb-7">
        <img src="{{ asset('images/logo-dispar.jpg') }}" alt="Logo SIPARDIS" class="mx-auto h-14 w-14 rounded-2xl object-cover shadow-lg shadow-teal-500/20" />
        <h1 class="mt-3.5 text-2xl font-extrabold tracking-tight">SIPARDIS</h1>
        <p class="text-sm mt-1"><span class="italic text-slate-300">Sistem Informasi Pariwisata Dispar</span><br><span class="text-slate-400">Dinas Pariwisata Kabupaten Ciamis</span></p>
    </div>
    <div class="bg-slate-900/70 backdrop-blur-md border border-slate-700/60 text-slate-100 rounded-2xl shadow-2xl p-6 sm:p-8">
        <h2 class="text-lg font-bold">Masuk ke akun dinas</h2>
        <p class="text-sm text-slate-400 mt-0.5 mb-5">Gunakan <b class="text-slate-200">NIP</b> (atau user admin) dan password Anda.</p>
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-rose-500/15 border border-rose-400/40 px-3.5 py-2.5 text-sm text-rose-200">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-300">NIP / Username</label>
                <input name="nip" value="{{ old('nip') }}" required autofocus autocomplete="username"
                    class="mt-1.5 w-full rounded-lg bg-slate-950/60 border-slate-600 border px-3.5 py-2.5 text-sm text-slate-100 placeholder:text-slate-500 focus:border-teal-400 focus:ring-2 focus:ring-teal-400/30 outline-none transition" placeholder="cth: 1975021119960110 01">
            </div>
            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-300">Password</label>
                <input type="password" name="password" required autocomplete="current-password"
                    class="mt-1.5 w-full rounded-lg bg-slate-950/60 border-slate-600 border px-3.5 py-2.5 text-sm text-slate-100 focus:border-teal-400 focus:ring-2 focus:ring-teal-400/30 outline-none transition">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-300"><input type="checkbox" name="remember" class="rounded border-slate-600 bg-slate-950/60 text-teal-500 focus:ring-teal-400/30"> Ingat saya di perangkat ini</label>
            <button class="w-full rounded-lg bg-gradient-to-r from-teal-700 to-sky-700 hover:from-teal-800 hover:to-sky-800 active:scale-[.99] text-white font-bold py-2.5 text-sm shadow-lg shadow-teal-500/25 transition">Masuk</button>
        </form>
    </div>
    <p class="text-center text-[11px] text-slate-400/90 mt-5">Untuk memasuki website masukan <code class="text-slate-300">NIP / Password anda</code> <br>· untuk pasword standar: DISPARCiamis2026
.</p>
</div>
</body>
</html>
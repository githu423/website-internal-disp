<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk ke Sistem · SIPARDIS Dispar Ciamis</title>
    <link rel="icon" href="{{ asset('images/logo-dispar.jpg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-purple-950 via-purple-900 to-slate-900 text-gray-800 antialiased selection:bg-purple-500 selection:text-white relative min-h-screen flex flex-col justify-start lg:justify-center overflow-x-hidden">

    <!-- ================= LATAR BELAKANG GLOBAL SEAMLESS ================= -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
        <!-- Subtle SVG Dot Pattern (opacity-10) di seluruh layar -->
        <svg class="absolute inset-0 w-full h-full opacity-10 text-white fill-current" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="dot-pattern" x="0" y="0" width="24" height="24" patternUnits="userSpaceOnUse">
                    <circle cx="2" cy="2" r="1.5" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#dot-pattern)" />
        </svg>

        <!-- Ambient Light Glow Global Halus -->
        <div class="absolute -top-32 -left-32 w-[32rem] h-[32rem] bg-purple-600/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-32 -right-32 w-[32rem] h-[32rem] bg-indigo-700/20 rounded-full blur-3xl"></div>
    </div>

    <!-- ================= KONTEN UTAMA ================= -->
    <div class="relative z-10 w-full min-h-screen flex flex-col lg:flex-row justify-start lg:justify-between items-center max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-4 lg:py-12">
        
        <!-- ================= PANEL KIRI (Branding Minimalis & Kontras Tinggi) ================= -->
        <div class="w-full lg:w-1/2 max-w-lg text-white mb-4 lg:mb-0 px-4 sm:px-0 lg:pr-8 xl:pr-12 flex flex-col justify-between h-auto lg:min-h-[500px]">
            <div>
                <!-- Logo Instansi Ciamis & Badge Pemerintah -->
                <div class="flex items-center gap-3.5">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-white/95 p-2 shadow-xl shadow-black/30 flex items-center justify-center flex-shrink-0 border border-white/20 backdrop-blur-sm">
                        <img src="{{ asset('images/logo-dispar.jpg') }}" alt="Logo Dinas Pariwisata Ciamis" class="w-full h-full object-contain rounded-xl">
                    </div>
                    <div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-800/80 text-slate-200 border border-purple-500/30 tracking-wide">
                            Pemerintah Kabupaten Ciamis
                        </span>
                        <p class="text-sm font-medium text-slate-200 mt-1">Dinas Pariwisata</p>
                    </div>
                </div>

                <!-- Judul Utama SIPARDIS & Deskripsi -->
                <div class="mt-4 md:mt-12 xl:mt-16">
                    <h1 class="text-3xl sm:text-4xl xl:text-5xl font-extrabold tracking-tight text-white leading-tight">
                        SIPARDIS
                    </h1>
                    <div class="mt-3 md:mt-3.5 h-1.5 w-16 rounded-full bg-gradient-to-r from-amber-400 to-purple-400"></div>
                    <p class="mt-3 md:mt-4 text-lg sm:text-xl font-medium text-slate-200">
                        Sistem Informasi Pariwisata, Administrasi, Rapat, Dokumen, Informasi, Statistik
                    </p>
                    {{-- Sembunyikan paragraf deskripsi panjang di mobile, tetap tampil di tablet dan desktop --}}
                    <p class="mt-3 text-slate-200 text-sm leading-relaxed max-w-md hidden md:block">
                        Portal resmi tata kelola administrasi, persuratan digital, dan pelayanan internal aparatur Dinas Pariwisata Kabupaten Ciamis.
                    </p>
                </div>
            </div>

            <!-- Footer Slogan Sisi Kiri (Tampil di layar besar) -->
            <div class="mt-8 lg:mt-0 pb-2 lg:pb-6 hidden lg:block">
                <p class="text-sm text-slate-200 font-medium tracking-wide flex items-center gap-2">
                    <span class="text-amber-400">✦</span> Mahayunan Ayunan Kadatuan — Tatar Galuh Ciamis
                </p>
                <p class="text-xs text-slate-300/90 mt-1">
                    &copy; {{ date('Y') }} Dinas Pariwisata Kabupaten Ciamis. Hak cipta dilindungi.
                </p>
            </div>
        </div>

        <!-- ================= PANEL KANAN / FLOATING CARD (Pemeran Utama) ================= -->
        <div class="w-full lg:w-1/2 flex items-center justify-center lg:justify-end relative px-4 sm:px-0">
            
            <!-- Ambient Light Glow Besar di Belakang Kartu Login -->
            <div class="absolute -top-12 -left-12 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-12 -right-12 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Kartu Login Putih Murni dengan Aksen Garis Emas di Atas -->
            <div class="w-full max-w-md bg-white p-6 sm:p-8 md:p-10 rounded-2xl rounded-t-2xl border-t-4 border-amber-400 shadow-2xl shadow-black/40 border-x border-b border-slate-100 relative z-10 overflow-hidden">

                <!-- Form Header & Badge -->
                <div class="mb-6">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-100 mb-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse"></span>
                        Portal Akses Aparatur
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-1.5">Masuk ke Sistem</h2>
                    <p class="text-sm text-gray-500">Silakan masukkan NIP dan kata sandi Anda untuk melanjutkan ke dashboard.</p>
                </div>

                <!-- Pesan Error Validasi -->
                @if ($errors->any())
                    <div class="mb-5 rounded-xl bg-red-50 border border-red-200 p-3.5 text-sm text-red-700 flex items-start gap-2.5">
                        <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <span class="font-semibold block text-xs uppercase tracking-wide">Gagal Masuk</span>
                            <span class="text-xs">{{ $errors->first() }}</span>
                        </div>
                    </div>
                @endif

                <!-- Form Login -->
                <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                    @csrf

                    <!-- Input NIP / Username dengan Ikon User & Focus Glow Lembut -->
                    <div>
                        <label for="nip" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1.5">
                            NIP / Username
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            {{-- autofocus: kursor otomatis aktif di kolom NIP saat halaman dimuat --}}
                            <input id="nip" name="nip" type="text" value="{{ old('nip') }}" required autofocus autocomplete="username"
                                class="w-full pl-11 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 placeholder:text-slate-500 outline-none transition text-sm shadow-sm"
                                placeholder="cth: 1975021119960110 01">
                        </div>
                    </div>

                    <!-- Input Password dengan Ikon Lock & Focus Glow Lembut -->
                    <div>
                        <label for="password-field" class="block text-xs font-semibold uppercase tracking-wider text-gray-700 mb-1.5">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input id="password-field" type="password" name="password" required autocomplete="current-password"
                                placeholder="••••••••"
                                class="w-full pl-11 pr-11 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-500 focus:border-transparent text-gray-900 placeholder:text-slate-500 outline-none transition text-sm shadow-sm">
                            <button type="button" id="toggle-password" title="Lihat / Sembunyikan Password"
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-purple-600 focus:outline-none transition cursor-pointer">
                                <!-- Ikon Mata Terbuka -->
                                <svg id="eye-open" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <!-- Ikon Mata Tertutup -->
                                <svg id="eye-closed" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                        {{-- Peringatan Caps Lock: muncul hanya saat input fokus & Caps Lock aktif --}}
                        <p id="caps-lock-warning" class="hidden mt-1.5 text-xs text-amber-600 flex items-center gap-1">
                            <span>⚠</span> <span>Caps Lock menyala — pastikan huruf kecil/besar sudah benar</span>
                        </p>
                    </div>

                    <!-- Baris Flex: Checkbox "Ingat saya" & "Lupa password?" -->
                    <div class="flex items-center justify-between pt-0.5">
                        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                            <span>Ingat saya</span>
                        </label>
                        <button type="button" id="btn-forgot-password" class="text-purple-700 hover:text-purple-800 hover:underline text-sm font-semibold cursor-pointer transition">
                            Lupa password?
                        </button>
                    </div>

                    <!-- Tombol Masuk ke Sistem -->
                    <button type="submit" class="w-full bg-purple-600 text-white font-semibold py-3 rounded-lg shadow-lg shadow-purple-500/30 transition-all duration-200 hover:brightness-110 active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer mt-2">
                        <span>Masuk ke Sistem</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </form>

                <!-- Slogan untuk Mobile -->
                <div class="lg:hidden mt-6 text-center pt-4 border-t border-slate-100">
                    <p class="text-[11px] text-amber-700 font-semibold">✦ Mahayunan Ayunan Kadatuan — Tatar Galuh Ciamis ✦</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Informasi Lupa Password -->
    <div id="forgot-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm hidden" aria-modal="true" role="dialog">
        <div class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-gray-100 transform transition-all">
            <div class="w-12 h-12 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 text-center mb-2">Bantuan Lupa Password</h3>
            <p class="text-sm text-gray-600 text-center leading-relaxed mb-6">
                Untuk menjaga keamanan akun aparatur dan data kedinasan, permohonan reset kata sandi dapat diajukan kepada <strong>Administrator Sistem</strong> atau <strong>Subbagian Kepegawaian &amp; Umum</strong> Dinas Pariwisata Kabupaten Ciamis.
            </p>
            <button type="button" id="close-forgot-modal" class="w-full py-2.5 px-4 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-xl text-sm shadow-md transition cursor-pointer">
                Mengerti
            </button>
        </div>
    </div>

    <script>
        // Toggle Password Visibility
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

        // Modal Lupa Password
        const forgotBtn = document.getElementById('btn-forgot-password');
        const forgotModal = document.getElementById('forgot-modal');
        const closeForgotBtn = document.getElementById('close-forgot-modal');

        forgotBtn?.addEventListener('click', function () {
            forgotModal?.classList.remove('hidden');
        });

        closeForgotBtn?.addEventListener('click', function () {
            forgotModal?.classList.add('hidden');
        });

        forgotModal?.addEventListener('click', function (e) {
            if (e.target === forgotModal) {
                forgotModal.classList.add('hidden');
            }
        });

        // Loading State & Anti Double-Click pada Form Login
        // ENTER KEY: Tidak ada listener keydown/keyup yang memanggil preventDefault() secara luas
        // di halaman ini, sehingga perilaku default form submit via tombol Enter pada kolom NIP
        // maupun Password berjalan normal tanpa memerlukan kode tambahan.
        const loginForm = document.querySelector('form[action*="login"]');
        const submitBtn = loginForm?.querySelector('button[type="submit"]');

        loginForm?.addEventListener('submit', function () {
            if (submitBtn) {
                submitBtn.classList.add('opacity-70', 'cursor-not-allowed');
                submitBtn.innerHTML = `
                    <svg class="animate-spin h-4 w-4 text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Memverifikasi kredensial...</span>
                `;
                setTimeout(() => {
                    submitBtn.disabled = true;
                }, 10);
            }
        });

        // Peringatan Caps Lock pada field password
        const passwordInput = document.getElementById('password-field');
        const capsWarning = document.getElementById('caps-lock-warning');
        let passwordFocused = false;

        function updateCapsWarning(event) {
            if (!passwordInput || !capsWarning) return;
            const capsOn = event.getModifierState('CapsLock');
            capsWarning.classList.toggle('hidden', !(passwordFocused && capsOn));
        }

        passwordInput?.addEventListener('focus', function (e) {
            passwordFocused = true;
            // Cek state Caps Lock saat fokus (butuh event, gunakan keyup pertama)
        });

        passwordInput?.addEventListener('blur', function () {
            passwordFocused = false;
            capsWarning?.classList.add('hidden');
        });

        // keyup mendeteksi perubahan state Caps Lock secara real-time
        passwordInput?.addEventListener('keyup', function (e) {
            updateCapsWarning(e);
        });

        // keydown juga diperlukan agar peringatan muncul segera saat Caps Lock ditekan
        passwordInput?.addEventListener('keydown', function (e) {
            updateCapsWarning(e);
        });
    </script>
</body>
</html>

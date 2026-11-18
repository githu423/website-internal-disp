<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Permintaan login: validasi NIP + password, pembatasan percobaan (rate limit),
 * dan pemblokiran akun non-aktif / pensiun.
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nip' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return ['nip.required' => 'NIP / Username wajib diisi.', 'password.required' => 'Password wajib diisi.'];
    }

    /**
     * Memverifikasi kredensial lalu mengecek status akun.
     */
    public function authenticate(): void
    {
        // ===== 1. BATASI PERCOBAAN LOGIN =====
        $this->ensureIsNotRateLimited();

        // ===== 2. COCOKKAN KREDENSIAL =====
        if (! Auth::attempt($this->only('nip', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'nip' => 'NIP atau password salah.',
            ]);
        }

        // ===== 3. BLOKIR AKUN NON-AKTIF & PENSIUN (PESAN BERBEDA) =====
        $user = Auth::user();
        if ($user && $user->status === 'nonaktif') {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'nip' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator kepegawaian.',
            ]);
        }
        if ($user && $user->status === 'pensiun') {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'nip' => 'Terima kasih atas pengabdian Anda. Akun dalam status pensiun tidak dapat mengakses sistem. Selamat menikmati masa purnabakti.',
            ]);
        }
        if ($user && $user->status !== 'aktif') {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'nip' => 'Akun Anda tidak aktif. Silakan hubungi administrator.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Pastikan belum melebihi batas percobaan login.
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }
        event(new Lockout($this));
        $seconds = RateLimiter::availableIn($this->throttleKey());
        throw ValidationException::withMessages([
            'nip' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    /**
     * Kunci rate-limit per NIP + IP.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('nip')).'|'.$this->ip());
    }
}

<?php

namespace App\Http\Requests\Auth;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan format email yang valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());

            $komunitas = $user->komunitasRelawan;
            $pesan = match (true) {
                $komunitas && ! $user->hasVerifiedEmail() => 'Email pendaftaran Anda belum diverifikasi. Silakan klik tautan verifikasi yang dikirim ke email Anda.',
                $komunitas?->status_pendaftaran === 'menunggu' => 'Pendaftaran komunitas Anda sedang menunggu persetujuan Tim Startup ALIR.',
                $komunitas?->status_pendaftaran === 'ditolak' => 'Pendaftaran komunitas Anda ditolak. Silakan periksa email Anda untuk alasan dan tautan perbaikan data.',
                default => 'Akun Anda sedang dinonaktifkan. Hubungi Tim Startup ALIR.',
            };

            throw ValidationException::withMessages([
                'email' => $pesan,
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        $user->forceFill(['last_login_at' => now()])->save();
        AuditLog::catat('auth.login', $user, 'Login berhasil', $user);
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}

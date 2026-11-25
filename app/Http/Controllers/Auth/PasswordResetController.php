<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan format email yang valid.',
        ]);

        $email = Str::lower((string) $request->input('email'));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user && $user->is_active) {
            Password::sendResetLink(['email' => $user->email]);
        }

        // Pesan selalu seragam untuk mencegah email enumeration.
        return back()->with('success', 'Jika email terdaftar dan aktif, tautan atur ulang kata sandi telah dikirim ke email Anda.');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan format email yang valid.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                if (! $user->is_active) {
                    throw ValidationException::withMessages([
                        'email' => 'Akun Anda belum aktif atau sedang dinonaktifkan. Hubungi Tim Startup ALIR.',
                    ]);
                }

                if (Hash::check($password, $user->password)) {
                    throw ValidationException::withMessages([
                        'password' => 'Kata sandi baru tidak boleh sama dengan kata sandi sebelumnya.',
                    ]);
                }

                $user->forceFill([
                    'password' => $password,
                    'wajib_ganti_sandi' => false,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->cabutSesiLain();

                event(new PasswordReset($user));

                AuditLog::catat('auth.reset_sandi', $user, 'Kata sandi diatur ulang via tautan email', $user);
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Kata sandi berhasil diatur ulang. Silakan masuk.')
            : back()->withInput($request->only('email'))->withErrors(['email' => 'Token reset tidak valid atau sudah kedaluwarsa.']);
    }
}

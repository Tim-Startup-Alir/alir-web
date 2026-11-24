<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForcePasswordChangeController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if (! $request->user()->wajib_ganti_sandi) {
            return redirect($this->tujuanDashboard($request->user()));
        }

        return view('auth.force-password-change');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->wajib_ganti_sandi) {
            return redirect($this->tujuanDashboard($user));
        }

        $validated = $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::min(8),
                function (string $attribute, mixed $value, Closure $fail) use ($user): void {
                    if (is_string($value) && Hash::check($value, $user->password)) {
                        $fail('Kata sandi baru tidak boleh sama dengan kata sandi sebelumnya.');
                    }
                },
            ],
        ], [
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
        ]);

        $user->forceFill([
            'password' => $validated['password'],
            'wajib_ganti_sandi' => false,
            'remember_token' => Str::random(60),
        ])->save();

        $request->session()->regenerate();
        $user->cabutSesiLain($request->session()->getId());

        AuditLog::catat('auth.ganti_sandi', $user, 'Mengganti kata sandi awal wajib', $user);

        return redirect($this->tujuanDashboard($user))->with('success', 'Kata sandi berhasil diperbarui. Selamat datang di ALIR.');
    }

    private function tujuanDashboard(mixed $user): string
    {
        return $user->isAdmin()
            ? route('admin.dashboard')
            : route('komunitas.dashboard');
    }
}

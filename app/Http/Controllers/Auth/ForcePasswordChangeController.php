<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForcePasswordChangeController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if (! $request->user()->wajib_ganti_sandi) {
            return redirect($request->user()->isAdmin() ? route('admin.dashboard') : route('komunitas.dashboard'));
        }

        return view('auth.force-password-change');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

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

        $user->update([
            'password' => $validated['password'],
            'wajib_ganti_sandi' => false,
        ]);

        AuditLog::catat('auth.ganti_sandi', $user, 'Mengganti kata sandi awal wajib', $user);

        $tujuan = $user->isAdmin()
            ? route('admin.dashboard')
            : route('komunitas.dashboard');

        return redirect($tujuan)->with('success', 'Kata sandi berhasil diperbarui. Selamat datang di ALIR.');
    }
}

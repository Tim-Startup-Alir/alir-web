<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Memastikan pengguna terautentikasi, aktif, dan memiliki salah satu role yang diizinkan.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Anda sedang dinonaktifkan. Hubungi Tim Startup ALIR.']);
        }

        $allowed = collect($roles)->contains(function (string $role) use ($user): bool {
            return match ($role) {
                'admin' => $user->isAdmin(),
                'komunitas_relawan' => $user->isKomunitas(),
                default => false,
            };
        });

        if (! $allowed) {
            abort(403, 'Anda tidak memiliki hak akses ke halaman ini.');
        }

        return $next($request);
    }
}

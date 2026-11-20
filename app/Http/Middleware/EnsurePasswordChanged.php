<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Memaksa pengguna yang memiliki flag wajib_ganti_sandi = true untuk mengganti sandi terlebih dahulu.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->wajib_ganti_sandi && ! $request->routeIs('password.force.*', 'logout')) {
            return redirect()->route('password.force.edit')
                ->with('warning', 'Demi keamanan, silakan ganti kata sandi awal Anda terlebih dahulu sebelum melanjutkan.');
        }

        return $next($request);
    }
}

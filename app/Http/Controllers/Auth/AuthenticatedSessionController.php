<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if ($user->wajib_ganti_sandi) {
            return redirect()->route('password.force.edit');
        }

        $tujuan = $user->isAdmin()
            ? route('admin.dashboard')
            : route('komunitas.dashboard');

        $intended = $request->session()->pull('url.intended');
        $path = $intended ? (parse_url($intended, PHP_URL_PATH) ?: '') : '';
        $intendedSesuaiRole = $intended && (
            ($user->isAdmin() && str_starts_with($path, '/admin'))
            || ($user->isKomunitas() && str_starts_with($path, '/komunitas'))
        );

        return redirect($intendedSesuaiRole ? $intended : $tujuan);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            AuditLog::catat('auth.logout', $user, 'Logout', $user);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar dari sistem.');
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\PendaftaranVerifikasiEmailNotification;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect($request->user()->isAdmin() ? route('admin.dashboard') : route('komunitas.dashboard'));
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->fulfill();
        }

        $tujuan = $request->user()->isAdmin() ? route('admin.dashboard') : route('komunitas.dashboard');

        return redirect($tujuan)->with('success', 'Email berhasil diverifikasi.');
    }

    public function send(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect($user->isAdmin() ? route('admin.dashboard') : route('komunitas.dashboard'));
        }

        $komunitas = $user->komunitasRelawan;
        if ($komunitas) {
            $user->notify(new PendaftaranVerifikasiEmailNotification($komunitas));
        } else {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('success', 'Tautan verifikasi baru telah dikirim ke email Anda.');
    }
}

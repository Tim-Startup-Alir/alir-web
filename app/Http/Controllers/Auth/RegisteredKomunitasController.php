<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PerbaikanKomunitasRequest;
use App\Http\Requests\Auth\RegisterKomunitasRequest;
use App\Models\KomunitasRelawan;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\PendaftaranKomunitasService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegisteredKomunitasController extends Controller
{
    public function __construct(private readonly PendaftaranKomunitasService $service) {}

    public function create(): View
    {
        return view('auth.register', [
            'wilayahAktif' => Wilayah::aktif()->orderBy('nama_kelurahan')->get(),
        ]);
    }

    public function store(RegisterKomunitasRequest $request): RedirectResponse
    {
        try {
            $komunitas = $this->service->daftar(
                $request->validated(),
                $request->file('dokumen_pendukung')
            );

            return redirect()->route('register.sent')
                ->with('registered_email', $komunitas->user->email)
                ->with('registered_nama', $komunitas->nama);
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['pernyataan_luar_cakupan' => $e->getMessage()]);
        }
    }

    public function sent(): View
    {
        return view('auth.register-sent', [
            'email' => session('registered_email'),
            'nama' => session('registered_nama'),
        ]);
    }

    public function verifyEmail(Request $request, int $id, string $hash): View
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403, 'Tautan verifikasi email tidak valid.');
        }

        $this->service->verifikasiEmail($user);

        return view('auth.register-verified', [
            'komunitas' => $user->komunitasRelawan,
        ]);
    }

    public function editPerbaikan(KomunitasRelawan $komunitas): View
    {
        if ($komunitas->status_pendaftaran !== 'ditolak') {
            return view('auth.register-perbaikan-unavailable', [
                'komunitas' => $komunitas,
            ]);
        }

        $komunitas->load(['user', 'wilayah']);

        return view('auth.register-perbaikan', [
            'komunitas' => $komunitas,
            'poskoUtama' => $komunitas->poskoUtama(),
            'wilayahAktif' => Wilayah::aktif()->orderBy('nama_kelurahan')->get(),
        ]);
    }

    public function updatePerbaikan(PerbaikanKomunitasRequest $request, KomunitasRelawan $komunitas): RedirectResponse|View
    {
        if ($komunitas->status_pendaftaran !== 'ditolak') {
            return view('auth.register-perbaikan-unavailable', [
                'komunitas' => $komunitas,
            ]);
        }

        try {
            $komunitas = $this->service->ajukanUlang(
                $komunitas,
                $request->validated(),
                $request->file('dokumen_pendukung')
            );

            return view('auth.register-perbaikan-sent', [
                'komunitas' => $komunitas,
            ]);
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['pernyataan_luar_cakupan' => $e->getMessage()]);
        }
    }
}

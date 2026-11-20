<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfilKomunitasRequest;
use App\Models\KomunitasRelawan;
use App\Models\Wilayah;
use App\Services\ProfilKomunitasService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProfilKomunitasController extends Controller
{
    public function __construct(private readonly ProfilKomunitasService $service) {}

    public function editAdmin(KomunitasRelawan $komunitas): View
    {
        Gate::authorize('update', $komunitas);
        $komunitas->load(['user', 'wilayah']);

        return view('admin.profil.edit', [
            'komunitas' => $komunitas,
            'poskoUtama' => $komunitas->poskoUtama(),
            'wilayahAktif' => Wilayah::aktif()->orderBy('nama_kelurahan')->get(),
        ]);
    }

    public function updateAdmin(UpdateProfilKomunitasRequest $request, KomunitasRelawan $komunitas): RedirectResponse
    {
        Gate::authorize('update', $komunitas);

        try {
            $this->service->update($komunitas, $request->user(), $request->validated());

            return redirect()->route('admin.profil.edit', $komunitas)
                ->with('success', "Profil komunitas {$komunitas->nama} berhasil diperbarui.");
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['pernyataan_luar_cakupan' => $e->getMessage()]);
        }
    }

    public function editKomunitas(Request $request, ?KomunitasRelawan $komunitas = null): View
    {
        $target = $komunitas ?? $request->user()->komunitasRelawan;
        abort_if($target === null, 404, 'Profil komunitas belum tersedia.');

        Gate::authorize('view', $target);
        $target->load(['user', 'wilayah']);

        return view('komunitas.profil.edit', [
            'komunitas' => $target,
            'poskoUtama' => $target->poskoUtama(),
        ]);
    }

    public function updateKomunitas(UpdateProfilKomunitasRequest $request, ?KomunitasRelawan $komunitas = null): RedirectResponse
    {
        $target = $komunitas ?? $request->user()->komunitasRelawan;
        abort_if($target === null, 404, 'Profil komunitas belum tersedia.');

        Gate::authorize('update', $target);

        try {
            $this->service->update($target, $request->user(), $request->validated());

            return redirect()->route('komunitas.profil.edit')
                ->with('success', 'Profil komunitas dan posko utama berhasil diperbarui.');
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['pernyataan_luar_cakupan' => $e->getMessage()]);
        }
    }
}

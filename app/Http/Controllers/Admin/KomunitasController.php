<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreKomunitasRequest;
use App\Http\Requests\Admin\UpdateKomunitasRequest;
use App\Models\KomunitasRelawan;
use App\Models\Wilayah;
use App\Services\KomunitasService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KomunitasController extends Controller
{
    public function __construct(private readonly KomunitasService $service) {}

    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $status = $request->string('status')->toString();

        $komunitas = KomunitasRelawan::query()
            ->with(['user', 'wilayah', 'posko' => fn ($q) => $q->orderBy('id')])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nama', 'like', "%{$search}%")
                        ->orWhere('nama_pic', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%"));
                });
            })
            ->when($status === 'aktif', fn ($q) => $q->whereHas('user', fn ($u) => $u->where('is_active', true)))
            ->when($status === 'nonaktif', fn ($q) => $q->whereHas('user', fn ($u) => $u->where('is_active', false)))
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('admin.komunitas.index', [
            'komunitasList' => $komunitas,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.komunitas.create', [
            'wilayahAktif' => Wilayah::aktif()->orderBy('nama_kelurahan')->get(),
        ]);
    }

    public function store(StoreKomunitasRequest $request): RedirectResponse
    {
        try {
            $komunitas = $this->service->create($request->validated());

            return redirect()->route('admin.komunitas.index')
                ->with('success', "Komunitas {$komunitas->nama} beserta akun dan posko utama berhasil dibuat.");
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['pernyataan_luar_cakupan' => $e->getMessage()]);
        }
    }

    public function edit(KomunitasRelawan $komunitas): View
    {
        $komunitas->load(['user', 'wilayah', 'posko' => fn ($q) => $q->orderBy('id')]);

        return view('admin.komunitas.edit', [
            'komunitas' => $komunitas,
            'wilayahAktif' => Wilayah::aktif()->orderBy('nama_kelurahan')->get(),
        ]);
    }

    public function update(UpdateKomunitasRequest $request, KomunitasRelawan $komunitas): RedirectResponse
    {
        $this->service->updateMaster($komunitas, $request->validated());

        return redirect()->route('admin.komunitas.index')
            ->with('success', "Data master komunitas {$komunitas->nama} berhasil diperbarui.");
    }

    public function toggleStatus(KomunitasRelawan $komunitas): RedirectResponse
    {
        $komunitas = $this->service->toggleStatus($komunitas);
        $status = $komunitas->user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.komunitas.index')
            ->with('success', "Komunitas {$komunitas->nama} berhasil {$status}.");
    }

    public function destroy(KomunitasRelawan $komunitas): RedirectResponse
    {
        try {
            $nama = $komunitas->nama;
            $this->service->delete($komunitas);

            return redirect()->route('admin.komunitas.index')
                ->with('success', "Komunitas {$nama} dan akun penggunanya berhasil dihapus.");
        } catch (DomainException $e) {
            return redirect()->route('admin.komunitas.index')
                ->with('error', $e->getMessage());
        }
    }
}

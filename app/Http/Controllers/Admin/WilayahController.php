<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWilayahRequest;
use App\Http\Requests\Admin\UpdateWilayahRequest;
use App\Models\Wilayah;
use App\Services\WilayahService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WilayahController extends Controller
{
    public function __construct(private readonly WilayahService $service) {}

    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();

        $wilayah = Wilayah::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nama_kelurahan', 'like', "%{$search}%")
                        ->orWhere('kecamatan', 'like', "%{$search}%")
                        ->orWhere('kode_adm4', 'like', "%{$search}%");
                });
            })
            ->withCount(['komunitasRelawan', 'laporanBanjir'])
            ->orderBy('nama_kelurahan')
            ->paginate(10)
            ->withQueryString();

        return view('admin.wilayah.index', [
            'wilayahList' => $wilayah,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.wilayah.create');
    }

    public function store(StoreWilayahRequest $request): RedirectResponse
    {
        $wilayah = $this->service->create($request->validated());

        return redirect()->route('admin.wilayah.index')
            ->with('success', "Wilayah {$wilayah->nama_kelurahan} berhasil ditambahkan.");
    }

    public function edit(Wilayah $wilayah): View
    {
        return view('admin.wilayah.edit', ['wilayah' => $wilayah]);
    }

    public function update(UpdateWilayahRequest $request, Wilayah $wilayah): RedirectResponse
    {
        $this->service->update($wilayah, $request->validated());

        return redirect()->route('admin.wilayah.index')
            ->with('success', "Wilayah {$wilayah->nama_kelurahan} berhasil diperbarui.");
    }

    public function toggleStatus(Wilayah $wilayah): RedirectResponse
    {
        $wilayah = $this->service->toggleStatus($wilayah);
        $status = $wilayah->is_aktif ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.wilayah.index')
            ->with('success', "Wilayah {$wilayah->nama_kelurahan} berhasil {$status}.");
    }

    public function destroy(Wilayah $wilayah): RedirectResponse
    {
        try {
            $nama = $wilayah->nama_kelurahan;
            $this->service->delete($wilayah);

            return redirect()->route('admin.wilayah.index')
                ->with('success', "Wilayah {$nama} berhasil dihapus.");
        } catch (DomainException $e) {
            return redirect()->route('admin.wilayah.index')
                ->with('error', $e->getMessage());
        }
    }
}

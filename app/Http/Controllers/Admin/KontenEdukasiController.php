<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\KontenEdukasiRequest;
use App\Models\KontenEdukasi;
use App\Services\KontenEdukasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KontenEdukasiController extends Controller
{
    public function __construct(private readonly KontenEdukasiService $service) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', KontenEdukasi::class);

        $kategori = $request->string('kategori')->toString();
        $status = $request->string('status')->toString();
        $q = trim($request->string('q')->toString());

        $daftar = KontenEdukasi::query()
            ->when(in_array($kategori, KontenEdukasi::KATEGORI, true), fn ($w) => $w->where('kategori', $kategori))
            ->when(in_array($status, ['draft', 'terbit'], true), fn ($w) => $w->where('status', $status))
            ->when($q !== '', fn ($w) => $w->where('judul', 'like', '%'.addcslashes($q, '\\%_').'%'))
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.edukasi.index', compact('daftar', 'kategori', 'status', 'q'));
    }

    public function create(): View
    {
        Gate::authorize('create', KontenEdukasi::class);

        return view('admin.edukasi.create');
    }

    public function store(KontenEdukasiRequest $request): RedirectResponse
    {
        $konten = $this->service->simpan($request->validated());

        return redirect()->route('admin.edukasi.index')
            ->with('success', $konten->isTerbit() ? 'Konten edukasi diterbitkan.' : 'Konten edukasi disimpan sebagai draft.');
    }

    public function edit(KontenEdukasi $konten): View
    {
        Gate::authorize('update', $konten);

        return view('admin.edukasi.edit', ['konten' => $konten]);
    }

    public function update(KontenEdukasiRequest $request, KontenEdukasi $konten): RedirectResponse
    {
        Gate::authorize('update', $konten);
        $this->service->ubah($konten, $request->validated());

        return redirect()->route('admin.edukasi.index')->with('success', 'Konten edukasi diperbarui.');
    }

    public function destroy(KontenEdukasi $konten): RedirectResponse
    {
        Gate::authorize('delete', $konten);
        $this->service->hapus($konten);

        return redirect()->route('admin.edukasi.index')->with('success', 'Konten edukasi dihapus.');
    }

    public function status(Request $request, KontenEdukasi $konten): RedirectResponse
    {
        Gate::authorize('update', $konten);
        $data = $request->validate(['status' => ['required', Rule::in(['draft', 'terbit'])]]);
        $this->service->ubahStatus($konten, $data['status']);

        return back()->with('success', $data['status'] === 'terbit' ? 'Konten diterbitkan.' : 'Konten dikembalikan ke draft.');
    }
}

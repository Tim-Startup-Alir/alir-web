<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\JadwalKesiapsiagaanRequest;
use App\Models\JadwalKesiapsiagaan;
use App\Models\KomunitasRelawan;
use App\Services\JadwalKesiapsiagaanService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class JadwalKesiapsiagaanController extends Controller
{
    public function __construct(private readonly JadwalKesiapsiagaanService $service) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', JadwalKesiapsiagaan::class);

        $komunitasId = $request->integer('komunitas_relawan_id') ?: null;
        $hari = $request->string('hari')->toString();
        $status = $request->string('status_ketersediaan')->toString();

        $urutanHari = "CASE hari WHEN 'senin' THEN 1 WHEN 'selasa' THEN 2 WHEN 'rabu' THEN 3 WHEN 'kamis' THEN 4 WHEN 'jumat' THEN 5 WHEN 'sabtu' THEN 6 ELSE 7 END";

        $jadwalList = JadwalKesiapsiagaan::query()
            ->with('komunitasRelawan')
            ->when($komunitasId, fn ($q) => $q->where('komunitas_relawan_id', $komunitasId))
            ->when($hari !== '', fn ($q) => $q->where('hari', $hari))
            ->when($status !== '', fn ($q) => $q->where('status_ketersediaan', $status))
            ->orderBy('komunitas_relawan_id')
            ->orderByRaw($urutanHari)
            ->orderBy('jam_mulai')
            ->paginate(15)
            ->withQueryString();

        return view('admin.jadwal.index', [
            'jadwalList' => $jadwalList,
            'komunitasList' => KomunitasRelawan::orderBy('nama')->get(),
            'filterKomunitas' => $komunitasId,
            'filterHari' => $hari,
            'filterStatus' => $status,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', JadwalKesiapsiagaan::class);

        return view('admin.jadwal.create', [
            'komunitasList' => KomunitasRelawan::orderBy('nama')->get(),
        ]);
    }

    public function store(JadwalKesiapsiagaanRequest $request): RedirectResponse
    {
        Gate::authorize('create', JadwalKesiapsiagaan::class);

        try {
            $this->service->create($request->validated());

            return redirect()->route('admin.jadwal.index')
                ->with('success', 'Jadwal kesiapsiagaan berhasil ditambahkan.');
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['jam_mulai' => $e->getMessage()]);
        }
    }

    public function edit(JadwalKesiapsiagaan $jadwal): View
    {
        Gate::authorize('update', $jadwal);

        return view('admin.jadwal.edit', [
            'jadwal' => $jadwal,
            'komunitasList' => KomunitasRelawan::orderBy('nama')->get(),
        ]);
    }

    public function update(JadwalKesiapsiagaanRequest $request, JadwalKesiapsiagaan $jadwal): RedirectResponse
    {
        Gate::authorize('update', $jadwal);

        try {
            $this->service->update($jadwal, $request->validated());

            return redirect()->route('admin.jadwal.index')
                ->with('success', 'Jadwal kesiapsiagaan berhasil diperbarui.');
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['jam_mulai' => $e->getMessage()]);
        }
    }

    public function destroy(JadwalKesiapsiagaan $jadwal): RedirectResponse
    {
        Gate::authorize('delete', $jadwal);

        $this->service->delete($jadwal);

        return redirect()->route('admin.jadwal.index')
            ->with('success', 'Jadwal kesiapsiagaan berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers\Komunitas;

use App\Http\Controllers\Controller;
use App\Models\JadwalKesiapsiagaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class JadwalKesiapsiagaanController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', JadwalKesiapsiagaan::class);

        $komunitas = $request->user()->komunitasRelawan;
        abort_if($komunitas === null, 404, 'Profil komunitas belum tersedia.');

        $urutanHari = "CASE hari WHEN 'senin' THEN 1 WHEN 'selasa' THEN 2 WHEN 'rabu' THEN 3 WHEN 'kamis' THEN 4 WHEN 'jumat' THEN 5 WHEN 'sabtu' THEN 6 ELSE 7 END";

        $jadwalList = $komunitas->jadwalKesiapsiagaan()
            ->orderByRaw($urutanHari)
            ->orderBy('jam_mulai')
            ->paginate(15);

        return view('komunitas.jadwal.index', [
            'komunitas' => $komunitas,
            'jadwalList' => $jadwalList,
        ]);
    }
}

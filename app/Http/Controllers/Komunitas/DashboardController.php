<?php

namespace App\Http\Controllers\Komunitas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $komunitas = $request->user()->komunitasRelawan()
            ->with(['wilayah', 'posko' => fn ($q) => $q->orderBy('id')])
            ->first();

        $poskoUtama = $komunitas?->posko->first();
        $jadwalAktif = $komunitas?->shiftSiaga();

        return view('komunitas.dashboard', [
            'komunitas' => $komunitas,
            'poskoUtama' => $poskoUtama,
            'jadwalAktif' => $jadwalAktif,
            'totalJadwal' => $komunitas?->jadwalKesiapsiagaan()->count() ?? 0,
        ]);
    }
}

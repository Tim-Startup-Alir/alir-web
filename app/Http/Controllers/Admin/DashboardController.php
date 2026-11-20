<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\JadwalKesiapsiagaan;
use App\Models\KomunitasRelawan;
use App\Models\Wilayah;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'totalWilayah' => Wilayah::count(),
            'wilayahAktif' => Wilayah::where('is_aktif', true)->count(),
            'totalKomunitas' => KomunitasRelawan::count(),
            'komunitasSiaga' => JadwalKesiapsiagaan::siagaPada()->distinct('komunitas_relawan_id')->count('komunitas_relawan_id'),
            'totalJadwal' => JadwalKesiapsiagaan::count(),
            'auditTerbaru' => AuditLog::with('user')->latest('waktu')->limit(8)->get(),
        ]);
    }
}

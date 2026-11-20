<?php

use App\Models\JadwalKesiapsiagaan;
use App\Models\KomunitasRelawan;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\JadwalBentrokService;
use Carbon\Carbon;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('seeder komunitas default menghasilkan 21 shift per komunitas dan tidak ada bentrok', function () {
    $this->seed([WilayahSeeder::class, KomunitasDefaultSeeder::class]);

    $service = app(JadwalBentrokService::class);
    $komunitasList = KomunitasRelawan::with('jadwalKesiapsiagaan')->get();

    expect($komunitasList)->toHaveCount(3);

    foreach ($komunitasList as $komunitas) {
        expect($komunitas->jadwalKesiapsiagaan)->toHaveCount(21);

        foreach ($komunitas->jadwalKesiapsiagaan as $jadwal) {
            $bentrok = $service->cariBentrok(
                $komunitas->id,
                $jadwal->hari,
                $jadwal->jam_mulai,
                $jadwal->jam_selesai,
                $jadwal->id
            );
            expect($bentrok)->toBeNull();
        }
    }
});

test('jadwal bentrok service mendeteksi irisan, mengizinkan bersentuhan batas, dan mendukung lintas tengah malam', function () {
    $this->seed([WilayahSeeder::class, KomunitasDefaultSeeder::class]);
    $komunitas = KomunitasRelawan::firstOrFail();
    $komunitas->jadwalKesiapsiagaan()->delete();

    $seninMalam = JadwalKesiapsiagaan::create([
        'komunitas_relawan_id' => $komunitas->id,
        'hari' => 'senin',
        'nama_shift' => 'Malam',
        'jam_mulai' => '22:00',
        'jam_selesai' => '06:00',
        'status_ketersediaan' => 'tersedia',
        'nama_pic' => 'PIC Malam',
        'nomor_whatsapp_pic' => '081234567890',
    ]);

    $mingguMalam = JadwalKesiapsiagaan::create([
        'komunitas_relawan_id' => $komunitas->id,
        'hari' => 'minggu',
        'nama_shift' => 'Malam Minggu',
        'jam_mulai' => '22:00',
        'jam_selesai' => '06:00',
        'status_ketersediaan' => 'tersedia',
        'nama_pic' => 'PIC Minggu',
        'nomor_whatsapp_pic' => '081234567890',
    ]);

    $service = app(JadwalBentrokService::class);

    // Bersentuhan batas tepat di 06:00 (Selasa) -> AMAN
    expect($service->cariBentrok($komunitas->id, 'selasa', '06:00', '14:00'))->toBeNull();

    // Bersentuhan batas tepat di 22:00 (Senin) -> AMAN
    expect($service->cariBentrok($komunitas->id, 'senin', '14:00', '22:00'))->toBeNull();

    // Bentrok lintas tengah malam: Selasa 05:00-10:00 beririsan dengan Senin 22:00-06:00
    $hitSelasa = $service->cariBentrok($komunitas->id, 'selasa', '05:00', '10:00');
    expect($hitSelasa?->id)->toBe($seninMalam->id);

    // Bentrok melingkar Minggu malam ke Senin pagi: Senin 04:00-08:00 beririsan dengan Minggu 22:00-06:00
    $hitSenin = $service->cariBentrok($komunitas->id, 'senin', '04:00', '08:00');
    expect($hitSenin?->id)->toBe($mingguMalam->id);

    // Edit diri sendiri dengan jam yang sama -> tidak dianggap bentrok dengan diri sendiri
    expect($service->cariBentrok($komunitas->id, 'senin', '22:00', '06:00', $seninMalam->id))->toBeNull();

    // Shift 24 jam penuh (08:00 - 08:00) -> bentrok dengan shift Senin malam
    expect($service->cariBentrok($komunitas->id, 'senin', '08:00', '08:00'))->not->toBeNull();
});

test('scope siagaPada menguji batas waktu, siaga_terbatas, tidak_tersedia, lintas tengah malam, dan komunitas nonaktif', function () {
    $this->seed([WilayahSeeder::class]);
    $wilayah = Wilayah::firstOrFail();

    $userAktif = User::factory()->create();
    $komAktif = KomunitasRelawan::create([
        'user_id' => $userAktif->id,
        'wilayah_id' => $wilayah->id,
        'nama' => 'Komunitas Aktif',
        'nama_pic' => 'PIC',
        'nomor_whatsapp_pic' => '081234567890',
        'status_pendaftaran' => 'disetujui',
    ]);

    $userNonaktif = User::factory()->nonaktif()->create();
    $komNonaktif = KomunitasRelawan::create([
        'user_id' => $userNonaktif->id,
        'wilayah_id' => $wilayah->id,
        'nama' => 'Komunitas Nonaktif',
        'nama_pic' => 'PIC Nonaktif',
        'nomor_whatsapp_pic' => '081234567891',
        'status_pendaftaran' => 'disetujui',
    ]);

    // Shift biasa Senin 08:00-16:00 (tersedia)
    JadwalKesiapsiagaan::create([
        'komunitas_relawan_id' => $komAktif->id,
        'hari' => 'senin',
        'jam_mulai' => '08:00',
        'jam_selesai' => '16:00',
        'status_ketersediaan' => 'tersedia',
        'nama_pic' => 'PIC 1',
        'nomor_whatsapp_pic' => '081234567890',
    ]);

    // Shift Senin 16:00-22:00 (siaga_terbatas -> dihitung tersedia F14)
    JadwalKesiapsiagaan::create([
        'komunitas_relawan_id' => $komAktif->id,
        'hari' => 'senin',
        'jam_mulai' => '16:00',
        'jam_selesai' => '22:00',
        'status_ketersediaan' => 'siaga_terbatas',
        'nama_pic' => 'PIC 2',
        'nomor_whatsapp_pic' => '081234567890',
    ]);

    // Shift lintas tengah malam Minggu 22:00-06:00 (tersedia) -> mencakup Senin 03:00
    JadwalKesiapsiagaan::create([
        'komunitas_relawan_id' => $komAktif->id,
        'hari' => 'minggu',
        'jam_mulai' => '22:00',
        'jam_selesai' => '06:00',
        'status_ketersediaan' => 'tersedia',
        'nama_pic' => 'PIC Malam',
        'nomor_whatsapp_pic' => '081234567890',
    ]);

    // Shift Selasa 08:00-16:00 (tidak_tersedia)
    JadwalKesiapsiagaan::create([
        'komunitas_relawan_id' => $komAktif->id,
        'hari' => 'selasa',
        'jam_mulai' => '08:00',
        'jam_selesai' => '16:00',
        'status_ketersediaan' => 'tidak_tersedia',
        'nama_pic' => 'PIC Off',
        'nomor_whatsapp_pic' => '081234567890',
    ]);

    // Komunitas nonaktif punya jadwal Senin 08:00-16:00
    JadwalKesiapsiagaan::create([
        'komunitas_relawan_id' => $komNonaktif->id,
        'hari' => 'senin',
        'jam_mulai' => '08:00',
        'jam_selesai' => '16:00',
        'status_ketersediaan' => 'tersedia',
        'nama_pic' => 'PIC X',
        'nomor_whatsapp_pic' => '081234567891',
    ]);

    // Senin 2026-11-16
    $senin0800 = Carbon::parse('2026-11-16 08:00:00');
    $senin1000 = Carbon::parse('2026-11-16 10:00:00');
    $senin1600 = Carbon::parse('2026-11-16 16:00:00');
    $senin0300 = Carbon::parse('2026-11-16 03:00:00');
    $selasa1000 = Carbon::parse('2026-11-17 10:00:00');
    $rabu1000 = Carbon::parse('2026-11-18 10:00:00');

    // Tepat di jam_mulai (08:00) -> masuk
    expect(JadwalKesiapsiagaan::where('komunitas_relawan_id', $komAktif->id)->siagaPada($senin0800)->count())->toBe(1);
    // Di dalam jam (10:00) -> masuk
    expect(JadwalKesiapsiagaan::where('komunitas_relawan_id', $komAktif->id)->siagaPada($senin1000)->count())->toBe(1);
    // Tepat di jam_selesai shift pagi (16:00): shift pagi selesai, shift siaga_terbatas mulai -> hanya 1 (siaga_terbatas)
    $jadwal1600 = JadwalKesiapsiagaan::where('komunitas_relawan_id', $komAktif->id)->siagaPada($senin1600)->first();
    expect($jadwal1600)->not->toBeNull();
    expect($jadwal1600->status_ketersediaan)->toBe('siaga_terbatas');

    // Lintas tengah malam dari Minggu 22:00 -> masih aktif pada Senin 03:00
    expect(JadwalKesiapsiagaan::where('komunitas_relawan_id', $komAktif->id)->siagaPada($senin0300)->count())->toBe(1);

    // Tidak tersedia pada Selasa 10:00
    expect(JadwalKesiapsiagaan::where('komunitas_relawan_id', $komAktif->id)->siagaPada($selasa1000)->count())->toBe(0);

    // Hari lain tanpa shift (Rabu 10:00)
    expect(JadwalKesiapsiagaan::where('komunitas_relawan_id', $komAktif->id)->siagaPada($rabu1000)->count())->toBe(0);

    // KomunitasRelawan::siagaPada mengecualikan komunitas dengan users.is_active = false
    $siagaIds = KomunitasRelawan::siagaPada($senin1000)->pluck('id')->all();
    expect($siagaIds)->toContain($komAktif->id);
    expect($siagaIds)->not->toContain($komNonaktif->id);
});

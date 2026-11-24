<?php

use App\Models\AuditLog;
use App\Models\KomunitasRelawan;
use App\Models\LaporanBanjir;
use App\Models\Penugasan;
use App\Models\User;
use App\Models\Wilayah;
use App\Notifications\PenugasanRelawanNotification;
use App\Services\RekomendasiPenugasanService;
use Database\Factories\LaporanBanjirFactory;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-11-16 10:00:00');
    $this->seed([WilayahSeeder::class, UserSeeder::class, KomunitasDefaultSeeder::class]);
    KomunitasRelawan::query()->with('user')->get()->each(function (KomunitasRelawan $komunitas) {
        $komunitas->user?->update(['wajib_ganti_sandi' => false]);
        $komunitas->jadwalKesiapsiagaan()->delete();
        $komunitas->jadwalKesiapsiagaan()->create([
            'hari' => 'senin',
            'jam_mulai' => '08:00',
            'jam_selesai' => '20:00',
            'status_ketersediaan' => 'tersedia',
            'nama_pic' => 'PIC Siaga',
            'nomor_whatsapp_pic' => '081234567890',
        ]);
    });
});

afterEach(function () {
    Carbon::setTestNow();
});

function laporanUntukPenugasan(string $status = 'terverifikasi'): LaporanBanjir
{
    $wilayah = Wilayah::where('nama_kelurahan', 'Tirto')->firstOrFail();

    return LaporanBanjirFactory::new()
        ->koordinat((float) $wilayah->latitude, (float) $wilayah->longitude)
        ->create([
            'wilayah_id' => $wilayah->id,
            'status' => $status,
            'tingkat_kepercayaan' => 'tinggi',
            'alamat_singkat' => 'Jalan Siaga',
        ]);
}

function kandidatSiaga(LaporanBanjir $laporan): array
{
    return collect(app(RekomendasiPenugasanService::class)->untukLaporan($laporan)['kelompok'])
        ->flatMap(fn (array $group) => $group['kandidat'])
        ->first(fn (array $candidate) => $candidate['bisa_ditugaskan']);
}

test('admin dashboard opens with soft-deleted assignment scope enabled', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('admin view edit and delete controls are present for every assignment status', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komunitas = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $statuses = ['menunggu', 'diterima', 'ditolak', 'menuju_lokasi', 'ditangani', 'selesai', 'dibatalkan'];
    $penugasan = collect();

    foreach ($statuses as $status) {
        $laporan = laporanUntukPenugasan();
        $penugasan->push(Penugasan::create([
            'laporan_banjir_id' => $laporan->id,
            'komunitas_relawan_id' => $komunitas->id,
            'ditugaskan_oleh' => $admin->id,
            'jumlah_personil_tugas' => 2,
            'stage' => $status,
            'alasan_tolak' => $status === 'ditolak' ? 'Kendala personel.' : null,
        ]));
    }

    $response = $this->actingAs($admin)
        ->get(route('admin.penugasan.index', ['status' => 'sudah_ditugaskan']))
        ->assertOk();

    foreach ($penugasan as $tugas) {
        $response->assertSee('lihat-penugasan-'.$tugas->id, false)
            ->assertSee('edit-penugasan-'.$tugas->id, false)
            ->assertSee('hapus-penugasan-'.$tugas->id, false)
            ->assertSee('Informasi laporan')
            ->assertSee('Penugasan saat ini')
            ->assertSee('Komunitas penerima');

        $this->get(route('admin.penugasan.show', $tugas))
            ->assertOk()
            ->assertSee('P-'.$tugas->laporanBanjir->kode_pelacakan)
            ->assertSee(ucfirst(str_replace('_', ' ', $tugas->stage)));
    }
});

test('admin page shows actual reports and assignment status tabs', function () {
    $belumDitugaskan = laporanUntukPenugasan();
    $belumDitugaskan->update(['severity' => 'berat']);
    $sudahDitugaskan = laporanUntukPenugasan();
    $komunitas = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $penugasan = Penugasan::create([
        'laporan_banjir_id' => $sudahDitugaskan->id,
        'komunitas_relawan_id' => $komunitas->id,
        'ditugaskan_oleh' => $admin->id,
        'jumlah_personil_tugas' => 2,
        'stage' => 'menunggu',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.penugasan.index', ['status' => 'belum_ditugaskan']));

    $response->assertOk()
        ->assertSee('LAPORAN')
        ->assertSee('Pilih komunitas relawan untuk menangani laporan banjir.')
        ->assertDontSee('Haversine')
        ->assertSee('data-lucide="clock"', false)
        ->assertSee('data-lucide="users"', false)
        ->assertSee('data-lucide="triangle-alert"', false)
        ->assertSee('.assignment-stat-icon{display:flex;align-items:center;justify-content:center;', false)
        ->assertSee('.assignment-stat-copy>span{', false)
        ->assertDontSee('.assignment-stat span{', false)
        ->assertSee('P-'.$belumDitugaskan->kode_pelacakan.' &middot; Tirto', false)
        ->assertSee('<strong>'.$belumDitugaskan->jumlah_warga.'</strong> warga', false)
        ->assertSee('tag red">Keparahan Berat', false)
        ->assertSee('tag amber">Belum ditugaskan', false)
        ->assertSee('data-dialog-id="assign-dialog-'.$belumDitugaskan->id.'-'.$komunitas->id.'"', false)
        ->assertSee('name="komunitas_relawan_id" value="'.$komunitas->id.'"', false)
        ->assertSee('name="jumlah_personil_tugas" min="1"', false)
        ->assertSee('name="armada_perahu_tugas" min="0"', false)
        ->assertSee('name="catatan"', false)
        ->assertSee('class="report-action-button" data-toggle-recommendations', false)
        ->assertSee('Lihat rekomendasi')
        ->assertSee('Rekomendasi terbaik')
        ->assertSee('sisa ')
        ->assertDontSee('class="task-section"', false)
        ->assertDontSee('id="laporan-'.$sudahDitugaskan->id.'"', false);

    expect($response->getContent())->toMatch('/Skor \d+\/100/')
        ->toMatch('/(?:\d+ m|\d+\.\d km)/');

    $this->get(route('admin.penugasan.index', ['status' => 'semua']))
        ->assertOk()
        ->assertSee('id="laporan-'.$belumDitugaskan->id.'"', false)
        ->assertSee('id="laporan-'.$sudahDitugaskan->id.'"', false)
        ->assertDontSee('class="task-section"', false);

    $assignedResponse = $this->get(route('admin.penugasan.index', ['status' => 'sudah_ditugaskan']))
        ->assertOk()
        ->assertSee('P-'.$sudahDitugaskan->kode_pelacakan, false)
        ->assertSee('class="task-section"', false)
        ->assertSee('data-task-view="table" aria-pressed="true"', false)
        ->assertSee('data-task-view-panel="table"', false)
        ->assertSee('data-task-view-panel="card"', false)
        ->assertDontSee('id="laporan-'.$sudahDitugaskan->id.'"', false)
        ->assertDontSee('id="laporan-'.$belumDitugaskan->id.'"', false)
        ->assertDontSee('class="groups"', false)
        ->assertDontSee('class="candidate-list"', false)
        ->assertDontSee('Lihat penugasan')
        ->assertSee('lihat-penugasan-'.$penugasan->id, false)
        ->assertSee('edit-penugasan-'.$penugasan->id, false)
        ->assertSee('hapus-penugasan-'.$penugasan->id, false);

    $cardResponse = $this->get(route('admin.penugasan.index', [
        'status' => 'sudah_ditugaskan',
        'view' => 'card',
    ]))->assertOk()
        ->assertSee('data-task-view="card" aria-pressed="true"', false)
        ->assertSee('data-task-view-panel="table"', false)
        ->assertSee('data-task-view-panel="card"', false)
        ->assertSee('data-assignment-id="'.$penugasan->id.'"', false)
        ->assertSee($komunitas->nama)
        ->assertSee('Keparahan')
        ->assertSee('data-dialog-id="lihat-penugasan-'.$penugasan->id.'"', false)
        ->assertSee('data-dialog-id="edit-penugasan-'.$penugasan->id.'"', false)
        ->assertSee('data-dialog-id="hapus-penugasan-'.$penugasan->id.'"', false)
        ->assertSee('id="hapus-penugasan-'.$penugasan->id.'"', false);

    $cardHtml = $cardResponse->getContent();
    expect($assignedResponse->getContent())->toMatch('/data-task-view-panel="card"\s+hidden/')
        ->and($cardHtml)->toMatch('/data-task-view-panel="table"\s+hidden/')
        ->and(substr_count($cardHtml, 'data-dialog-id="lihat-penugasan-'.$penugasan->id.'"'))->toBe(2)
        ->and(substr_count($cardHtml, 'data-dialog-id="edit-penugasan-'.$penugasan->id.'"'))->toBe(2)
        ->and(substr_count($cardHtml, 'data-dialog-id="hapus-penugasan-'.$penugasan->id.'"'))->toBe(2)
        ->and($assignedResponse->viewData('daftarTugas')->total())->toBe(1)
        ->and($cardResponse->viewData('daftarTugas')->total())->toBe(1);
});

test('admin assignment page excludes Demo F-05 reports without deleting their assignments', function () {
    $laporanDemo = laporanUntukPenugasan();
    $laporanDemo->update(['kode_pelacakan' => 'DEMO-F05-PKR-H03']);
    $laporanAktual = laporanUntukPenugasan();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komunitas = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();

    $penugasanDemo = Penugasan::create([
        'laporan_banjir_id' => $laporanDemo->id,
        'komunitas_relawan_id' => $komunitas->id,
        'ditugaskan_oleh' => $admin->id,
        'jumlah_personil_tugas' => 1,
        'stage' => 'menunggu',
    ]);
    $penugasanAktual = Penugasan::create([
        'laporan_banjir_id' => $laporanAktual->id,
        'komunitas_relawan_id' => $komunitas->id,
        'ditugaskan_oleh' => $admin->id,
        'jumlah_personil_tugas' => 1,
        'stage' => 'menunggu',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.penugasan.index', ['status' => 'sudah_ditugaskan']))
        ->assertOk()
        ->assertDontSee('DEMO-F05-PKR-H03')
        ->assertSee('P-'.$laporanAktual->kode_pelacakan, false);

    expect($response->viewData('daftarTugas')->total())->toBe(1)
        ->and(Penugasan::whereKey($penugasanDemo->id)->exists())->toBeTrue()
        ->and(Penugasan::whereKey($penugasanAktual->id)->exists())->toBeTrue()
        ->and($response->viewData('jumlahLaporan')['semua'])->toBe(1);
});

test('admin can assign an eligible community and the in-app notification is persisted', function () {
    $laporan = laporanUntukPenugasan();
    $kandidat = kandidatSiaga($laporan);
    expect($kandidat)->not->toBeNull();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komunitas = $kandidat['komunitas'];

    $this->actingAs($admin)->post(route('admin.penugasan.store', $laporan), [
        'komunitas_relawan_id' => $komunitas->id,
        'jumlah_personil_tugas' => 2,
        'armada_perahu_tugas' => 1,
        'catatan' => 'Bawa perlengkapan keselamatan.',
    ])->assertRedirect()->assertSessionHas('success');

    $penugasan = Penugasan::where('laporan_banjir_id', $laporan->id)->firstOrFail();
    expect($penugasan->stage)->toBe('menunggu')
        ->and((int) $penugasan->komunitas_relawan_id)->toBe((int) $komunitas->id)
        ->and((int) $penugasan->laporan_banjir_id)->toBe((int) $laporan->id)
        ->and($penugasan->jumlah_personil_tugas)->toBe(2)
        ->and($penugasan->armada_perahu_tugas)->toBe(1)
        ->and($penugasan->catatan)->toBe('Bawa perlengkapan keselamatan.')
        ->and($penugasan->created_at)->not->toBeNull()
        ->and($laporan->fresh()->status)->toBe('ditugaskan')
        ->and(AuditLog::where('aksi', 'penugasan.buat')->where('subjek_id', $penugasan->id)->exists())->toBeTrue()
        ->and($komunitas->user->notifications()->where('type', PenugasanRelawanNotification::class)->exists())->toBeTrue();

    $this->actingAs($komunitas->user)
        ->get(route('komunitas.penugasan.index'))
        ->assertOk()
        ->assertSee('Jalan Siaga')
        ->assertSee('<strong>2</strong> personel', false)
        ->assertSee('1 perahu')
        ->assertSee('Bawa perlengkapan keselamatan.')
        ->assertSee(route('komunitas.penugasan.show', $penugasan), false)
        ->assertDontSee('Terima tugas');

    $komunitasLain = KomunitasRelawan::where('id', '!=', $komunitas->id)->firstOrFail();
    $this->actingAs($komunitasLain->user)
        ->get(route('komunitas.penugasan.index'))
        ->assertOk()
        ->assertDontSee('Jalan Siaga')
        ->assertDontSee(route('komunitas.penugasan.show', $penugasan), false);
    $this->get(route('komunitas.penugasan.show', $penugasan))
        ->assertNotFound();

    $this->actingAs($admin)
        ->get(route('admin.penugasan.index', ['status' => 'sudah_ditugaskan']))
        ->assertOk()
        ->assertSee('Jalan Siaga')
        ->assertSee($komunitas->nama)
        ->assertSee('Menunggu');

    $this->actingAs($komunitas->user)
        ->get(route('komunitas.penugasan.show', $penugasan))
        ->assertOk()
        ->assertSee('Bawa perlengkapan keselamatan.')
        ->assertSee('Terima tugas')
        ->assertSee('Alasan penolakan');

    $this->post(route('komunitas.penugasan.respon', $penugasan), [
        'stage' => 'diterima',
    ])->assertRedirect()->assertSessionHas('success');

    expect($penugasan->fresh()->stage)->toBe('diterima')
        ->and($penugasan->fresh()->waktu_diterima)->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.penugasan.index', ['status' => 'sudah_ditugaskan']))
        ->assertOk()
        ->assertSee('Diterima');

    foreach ([
        ['stage' => 'menuju_lokasi', 'catatan' => 'Relawan menuju lokasi.'],
        ['stage' => 'ditangani', 'catatan' => 'Penanganan sedang berlangsung.'],
        ['stage' => 'selesai', 'catatan' => 'Penanganan selesai.'],
    ] as $progress) {
        $this->actingAs($komunitas->user)
            ->patch(route('komunitas.penugasan.progres', $penugasan), $progress)
            ->assertRedirect(route('komunitas.penugasan.show', $penugasan))
            ->assertSessionHas('success');
    }

    $penugasan->refresh();
    expect($penugasan->stage)->toBe('selesai')
        ->and($penugasan->waktu_menuju_lokasi)->not->toBeNull()
        ->and($penugasan->waktu_ditangani)->not->toBeNull()
        ->and($penugasan->waktu_selesai)->not->toBeNull()
        ->and($penugasan->catatanPerTahap())->toMatchArray([
            'menuju_lokasi' => 'Relawan menuju lokasi.',
            'ditangani' => 'Penanganan sedang berlangsung.',
            'selesai' => 'Penanganan selesai.',
        ]);
});

test('recommendations are limited to the three pilot villages and exclude out-of-scope communities', function () {
    $outsideWilayah = Wilayah::create([
        'kode_adm4' => '99.99.99.9999',
        'nama_kelurahan' => 'Kelurahan Luar Cakupan',
        'kecamatan' => 'Kecamatan Uji',
        'latitude' => -6.8,
        'longitude' => 109.8,
        'radius_cakupan_m' => 1500,
        'is_aktif' => true,
    ]);
    $komunitasLuar = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $komunitasLuar->update(['wilayah_id' => $outsideWilayah->id]);

    $komunitasDenganPoskoLuar = KomunitasRelawan::where('nama', 'Komunitas Relawan Degayu')->firstOrFail();
    $komunitasDenganPoskoLuar->posko()->firstOrFail()->update([
        'latitude' => -6.0,
        'longitude' => 108.0,
    ]);

    $hasil = app(RekomendasiPenugasanService::class)->untukLaporan(laporanUntukPenugasan());
    $namaKelompok = collect($hasil['kelompok'])->pluck('nama')->all();
    sort($namaKelompok);
    $namaKandidat = collect($hasil['kelompok'])
        ->flatMap(fn (array $group) => $group['kandidat'])
        ->pluck('komunitas.nama')
        ->all();

    expect($namaKelompok)->toBe(['Degayu', 'Pasirkraton Kramat', 'Tirto'])
        ->and($namaKandidat)->not->toContain($komunitasLuar->nama)
        ->and($namaKandidat)->not->toContain($komunitasDenganPoskoLuar->nama)
        ->and($namaKelompok)->not->toContain('Luar cakupan ALIR')
        ->and($namaKelompok)->not->toContain('Kelurahan Luar Cakupan');
});

test('new assignments appear first when community assignment timestamps tie', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komunitas = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();

    for ($i = 0; $i < 10; $i++) {
        $laporanLama = laporanUntukPenugasan();
        Penugasan::create([
            'laporan_banjir_id' => $laporanLama->id,
            'komunitas_relawan_id' => $komunitas->id,
            'ditugaskan_oleh' => $admin->id,
            'jumlah_personil_tugas' => 1,
            'stage' => 'selesai',
        ]);
    }

    $laporanBaru = laporanUntukPenugasan();
    $kandidat = collect(app(RekomendasiPenugasanService::class)->untukLaporan($laporanBaru)['kelompok'])
        ->flatMap(fn (array $group) => $group['kandidat'])
        ->first(fn (array $item) => (int) $item['komunitas']->id === (int) $komunitas->id);
    expect($kandidat['bisa_ditugaskan'])->toBeTrue();

    $this->actingAs($admin)->post(route('admin.penugasan.store', $laporanBaru), [
        'komunitas_relawan_id' => $komunitas->id,
        'jumlah_personil_tugas' => 1,
        'armada_perahu_tugas' => 0,
    ])->assertRedirect()->assertSessionHas('success');

    $penugasanBaru = Penugasan::where('laporan_banjir_id', $laporanBaru->id)->firstOrFail();
    $response = $this->actingAs($komunitas->user)
        ->get(route('komunitas.penugasan.index'))
        ->assertOk()
        ->assertSee('dusk="penugasan-'.$penugasanBaru->id.'"', false);

    expect((int) $response->viewData('penugasan')->first()->id)->toBe((int) $penugasanBaru->id);
});

test('admin can edit a pending assignment and the change is audited and notified', function () {
    $laporan = laporanUntukPenugasan();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komunitas = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $penugasan = Penugasan::create([
        'laporan_banjir_id' => $laporan->id,
        'komunitas_relawan_id' => $komunitas->id,
        'ditugaskan_oleh' => $admin->id,
        'jumlah_personil_tugas' => 2,
        'armada_perahu_tugas' => 0,
        'stage' => 'menunggu',
    ]);

    $this->actingAs($admin)->patch(route('admin.penugasan.update', $penugasan), [
        'jumlah_personil_tugas' => 1,
        'armada_perahu_tugas' => 0,
        'catatan' => 'Perubahan jumlah personel.',
    ])->assertRedirect()->assertSessionHas('success');

    expect($penugasan->fresh()->jumlah_personil_tugas)->toBe(1)
        ->and($penugasan->fresh()->catatan)->toBe('Perubahan jumlah personel.')
        ->and(AuditLog::where('aksi', 'penugasan.perbarui')->where('subjek_id', $penugasan->id)->exists())->toBeTrue()
        ->and($komunitas->user->notifications()->where('type', PenugasanRelawanNotification::class)->exists())->toBeTrue();
});

test('edit action targets and updates the selected assignment in both admin views', function () {
    $laporan = laporanUntukPenugasan();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komunitas = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $penugasan = Penugasan::create([
        'laporan_banjir_id' => $laporan->id,
        'komunitas_relawan_id' => $komunitas->id,
        'ditugaskan_oleh' => $admin->id,
        'jumlah_personil_tugas' => 2,
        'armada_perahu_tugas' => 0,
        'stage' => 'menunggu',
    ]);

    $jumlahPersonilSaatIni = 2;
    foreach (['table' => 3, 'card' => 4] as $view => $jumlahPersonil) {
        $url = route('admin.penugasan.index', [
            'status' => 'sudah_ditugaskan',
            'view' => $view,
        ]);
        $this->actingAs($admin)
            ->get($url)
            ->assertOk()
            ->assertSee('data-dialog-id="edit-penugasan-'.$penugasan->id.'"', false)
            ->assertSee('id="edit-penugasan-'.$penugasan->id.'"', false)
            ->assertSee('action="'.route('admin.penugasan.update', $penugasan).'"', false)
            ->assertSee('name="jumlah_personil_tugas" min="1" value="'.$jumlahPersonilSaatIni.'" required', false);

        $this->from($url)->patch(route('admin.penugasan.update', $penugasan), [
            'jumlah_personil_tugas' => $jumlahPersonil,
            'armada_perahu_tugas' => 0,
            'catatan' => "Disimpan dari mode {$view}.",
        ])->assertRedirect($url)->assertSessionHas('success');

        expect($penugasan->fresh()->jumlah_personil_tugas)->toBe($jumlahPersonil)
            ->and($penugasan->fresh()->catatan)->toBe("Disimpan dari mode {$view}.");
        $jumlahPersonilSaatIni = $jumlahPersonil;
    }
});

test('editing a cancelled assignment note works when its read-only fields are omitted', function () {
    $laporan = laporanUntukPenugasan();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komunitas = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $penugasan = Penugasan::create([
        'laporan_banjir_id' => $laporan->id,
        'komunitas_relawan_id' => $komunitas->id,
        'ditugaskan_oleh' => $admin->id,
        'jumlah_personil_tugas' => 2,
        'armada_perahu_tugas' => 1,
        'stage' => 'dibatalkan',
    ]);

    $url = route('admin.penugasan.index', ['status' => 'sudah_ditugaskan']);
    $this->actingAs($admin)->from($url)
        ->patch(route('admin.penugasan.update', $penugasan), [
            'catatan' => 'Catatan untuk penugasan yang dibatalkan.',
        ])
        ->assertRedirect($url)
        ->assertSessionHas('success');

    expect($penugasan->fresh()->catatan)->toBe('Catatan untuk penugasan yang dibatalkan.')
        ->and($penugasan->fresh()->jumlah_personil_tugas)->toBe(2)
        ->and($penugasan->fresh()->armada_perahu_tugas)->toBe(1);
});

test('admin can cancel a pending assignment without deleting it', function () {
    $laporan = laporanUntukPenugasan();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komunitas = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $penugasan = Penugasan::create([
        'laporan_banjir_id' => $laporan->id,
        'komunitas_relawan_id' => $komunitas->id,
        'ditugaskan_oleh' => $admin->id,
        'jumlah_personil_tugas' => 2,
        'stage' => 'menunggu',
    ]);

    $this->actingAs($admin)->post(route('admin.penugasan.batalkan', $penugasan), [
        'alasan' => 'Perubahan kebutuhan lapangan.',
    ])->assertRedirect()->assertSessionHas('success');

    expect($penugasan->fresh()->stage)->toBe('dibatalkan')
        ->and(Penugasan::whereKey($penugasan->id)->exists())->toBeTrue()
        ->and(AuditLog::where('aksi', 'penugasan.batalkan')->where('subjek_id', $penugasan->id)->exists())->toBeTrue()
        ->and($komunitas->user->notifications()->where('type', PenugasanRelawanNotification::class)->exists())->toBeTrue();
});

test('admin can edit notes after acceptance but cannot cancel after it has left the waiting stage', function () {
    $laporan = laporanUntukPenugasan();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komunitas = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $penugasan = Penugasan::create([
        'laporan_banjir_id' => $laporan->id,
        'komunitas_relawan_id' => $komunitas->id,
        'ditugaskan_oleh' => $admin->id,
        'jumlah_personil_tugas' => 2,
        'stage' => 'menunggu',
    ]);
    $penugasan->perbaruiStage('diterima');

    $this->actingAs($admin)->patch(route('admin.penugasan.update', $penugasan), [
        'jumlah_personil_tugas' => 1,
        'armada_perahu_tugas' => 0,
        'catatan' => 'Catatan koordinasi lapangan.',
    ])->assertRedirect()->assertSessionHas('success');

    $this->post(route('admin.penugasan.batalkan', $penugasan), [
        'alasan' => 'Tidak boleh dibatalkan setelah diterima.',
    ])->assertRedirect()->assertSessionHasErrors('penugasan');

    expect($penugasan->fresh()->stage)->toBe('diterima')
        ->and($penugasan->fresh()->catatan)->toBe('Catatan koordinasi lapangan.')
        ->and(AuditLog::where('aksi', 'penugasan.perbarui')->where('subjek_id', $penugasan->id)->exists())->toBeTrue()
        ->and(AuditLog::where('aksi', 'penugasan.batalkan')->where('subjek_id', $penugasan->id)->exists())->toBeFalse();
});

test('admin can safely edit notes for progressing completed and cancelled assignments', function () {
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komunitas = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();

    foreach (['menuju_lokasi', 'ditangani', 'selesai', 'dibatalkan'] as $stage) {
        $laporan = laporanUntukPenugasan();
        $penugasan = Penugasan::create([
            'laporan_banjir_id' => $laporan->id,
            'komunitas_relawan_id' => $komunitas->id,
            'ditugaskan_oleh' => $admin->id,
            'jumlah_personil_tugas' => 2,
            'stage' => $stage,
        ]);

        $this->actingAs($admin)->patch(route('admin.penugasan.update', $penugasan), [
            'jumlah_personil_tugas' => 1,
            'armada_perahu_tugas' => 0,
            'catatan' => "Catatan admin untuk {$stage}.",
        ])->assertRedirect()->assertSessionHas('success');

        expect($penugasan->fresh()->stage)->toBe($stage)
            ->and($penugasan->fresh()->jumlah_personil_tugas)->toBe(2)
            ->and($penugasan->fresh()->catatan)->toBe("Catatan admin untuk {$stage}.")
            ->and(AuditLog::where('aksi', 'penugasan.perbarui')->where('subjek_id', $penugasan->id)->exists())->toBeTrue();
    }
});

test('admin can reassign a rejected assignment through edit', function () {
    $laporan = laporanUntukPenugasan();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komunitasAwal = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $penugasan = Penugasan::create([
        'laporan_banjir_id' => $laporan->id,
        'komunitas_relawan_id' => $komunitasAwal->id,
        'ditugaskan_oleh' => $admin->id,
        'jumlah_personil_tugas' => 2,
        'stage' => 'menunggu',
    ]);
    $penugasan->perbaruiStage('ditolak', 'Kendala personel.');

    $kandidat = collect(app(RekomendasiPenugasanService::class)->untukLaporan($laporan->fresh())['kelompok'])
        ->flatMap(fn (array $group) => $group['kandidat'])
        ->first(fn (array $candidate) => $candidate['bisa_ditugaskan']
            && (int) $candidate['komunitas']->id !== (int) $komunitasAwal->id);
    expect($kandidat)->not->toBeNull();

    $this->actingAs($admin)->patch(route('admin.penugasan.update', $penugasan), [
        'komunitas_relawan_id' => $kandidat['komunitas']->id,
        'jumlah_personil_tugas' => 2,
        'armada_perahu_tugas' => 0,
        'catatan' => 'Dialihkan setelah penolakan.',
        'alasan_pengalihan' => 'Komunitas awal menolak tugas.',
    ])->assertRedirect()->assertSessionHas('success');

    expect($penugasan->fresh()->stage)->toBe('dibatalkan')
        ->and(Penugasan::where('laporan_banjir_id', $laporan->id)->where('id', '!=', $penugasan->id)->first()?->stage)->toBe('menunggu')
        ->and(AuditLog::where('aksi', 'penugasan.alihkan_buat')->where('deskripsi', 'like', '%Komunitas awal menolak tugas.%')->exists())->toBeTrue();
});

test('admin deletion preserves rejected history and blocks active field assignments', function () {
    $laporanDitolak = laporanUntukPenugasan();
    $laporanAktif = laporanUntukPenugasan();
    $laporanSelesai = laporanUntukPenugasan();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komunitas = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $ditolak = Penugasan::create([
        'laporan_banjir_id' => $laporanDitolak->id,
        'komunitas_relawan_id' => $komunitas->id,
        'ditugaskan_oleh' => $admin->id,
        'jumlah_personil_tugas' => 2,
        'stage' => 'ditolak',
        'alasan_tolak' => 'Tidak ada personel.',
    ]);
    $aktif = Penugasan::create([
        'laporan_banjir_id' => $laporanAktif->id,
        'komunitas_relawan_id' => $komunitas->id,
        'ditugaskan_oleh' => $admin->id,
        'jumlah_personil_tugas' => 2,
        'stage' => 'diterima',
    ]);
    $selesai = Penugasan::create([
        'laporan_banjir_id' => $laporanSelesai->id,
        'komunitas_relawan_id' => $komunitas->id,
        'ditugaskan_oleh' => $admin->id,
        'jumlah_personil_tugas' => 2,
        'stage' => 'selesai',
    ]);

    $this->actingAs($admin)->delete(route('admin.penugasan.destroy', $ditolak), [
        'alasan' => 'Duplikasi data penugasan.',
    ])->assertRedirect()->assertSessionHas('success');

    $this->delete(route('admin.penugasan.destroy', $aktif), [
        'alasan' => 'Tidak boleh menghapus tugas aktif.',
    ])->assertRedirect()->assertSessionHasErrors('penugasan');

    $this->delete(route('admin.penugasan.destroy', $selesai), [
        'alasan' => 'Arsip duplikat.',
    ])->assertRedirect()->assertSessionHas('success');

    expect(Penugasan::withTrashed()->whereKey($ditolak->id)->firstOrFail()->deleted_at)->not->toBeNull()
        ->and(AuditLog::where('aksi', 'penugasan.hapus')->where('subjek_id', $ditolak->id)->exists())->toBeTrue()
        ->and(Penugasan::withTrashed()->whereKey($selesai->id)->firstOrFail()->deleted_at)->not->toBeNull()
        ->and(AuditLog::where('aksi', 'penugasan.hapus')->where('subjek_id', $selesai->id)->exists())->toBeTrue()
        ->and($aktif->fresh()->stage)->toBe('diterima');
});

test('recommendation scoring uses configured fixed weights and limited readiness stays assignable', function () {
    config(['alir.penugasan.radius_rekomendasi_km' => 8]);
    $laporan = laporanUntukPenugasan();
    $komunitas = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $posko = $komunitas->posko()->firstOrFail();
    $posko->update(['latitude' => $laporan->latitude, 'longitude' => $laporan->longitude]);
    $komunitas->jadwalKesiapsiagaan()->update(['status_ketersediaan' => 'siaga_terbatas']);

    $kandidat = collect(app(RekomendasiPenugasanService::class)->untukLaporan($laporan)['kelompok'])
        ->flatMap(fn (array $group) => $group['kandidat'])
        ->first(fn (array $candidate) => (int) $candidate['komunitas']->id === (int) $komunitas->id);

    expect($kandidat['skor_komponen']['jarak'])->toEqual(40)
        ->and($kandidat['skor_komponen']['kapasitas'])->toEqual(25)
        ->and($kandidat['skor_komponen']['wilayah'])->toEqual(20)
        ->and($kandidat['skor_komponen']['jadwal'])->toBe(7.5)
        ->and($kandidat['skor_rekomendasi'])->toBe(92.5)
        ->and($kandidat['status_jadwal'])->toBe('siaga_terbatas')
        ->and($kandidat['bisa_ditugaskan'])->toBeTrue();
});

test('not-ready communities remain visible but disabled with the agreed readiness labels', function () {
    $laporan = laporanUntukPenugasan();
    $komunitas = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $komunitas->jadwalKesiapsiagaan()->update(['status_ketersediaan' => 'tidak_tersedia']);
    expect($komunitas->jadwalKesiapsiagaan()->pluck('status_ketersediaan')->all())->toBe(['tidak_tersedia']);
    $kandidat = collect(app(RekomendasiPenugasanService::class)->untukLaporan($laporan)['kelompok'])
        ->flatMap(fn (array $group) => $group['kandidat'])
        ->first(fn (array $candidate) => (int) $candidate['komunitas']->id === (int) $komunitas->id);

    expect($kandidat['status_jadwal'])->toBe('belum_siaga')
        ->and($kandidat['bisa_ditugaskan'])->toBeFalse();

    $this->actingAs(User::where('email', 'admin@alir.test')->firstOrFail())
        ->get(route('admin.penugasan.index'))
        ->assertOk()
        ->assertSee('<div class="candidate disabled', false)
        ->assertSee('Komunitas belum siaga.')
        ->assertDontSee('candidate-actions" disabled', false);

    $komunitas->jadwalKesiapsiagaan()->update(['status_ketersediaan' => 'tersedia']);
    Carbon::setTestNow('2026-11-16 21:00:00');
    $kandidatDiLuarJam = collect(app(RekomendasiPenugasanService::class)->untukLaporan($laporan->fresh())['kelompok'])
        ->flatMap(fn (array $group) => $group['kandidat'])
        ->first(fn (array $candidate) => (int) $candidate['komunitas']->id === (int) $komunitas->id);

    expect($kandidatDiLuarJam['status_jadwal'])->toBe('diluar_jam')
        ->and($kandidatDiLuarJam['bisa_ditugaskan'])->toBeFalse();
});

test('assignment rejects over-capacity requests and community accounts cannot use admin controls', function () {
    $laporan = laporanUntukPenugasan();
    $kandidat = kandidatSiaga($laporan);
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();

    $this->actingAs($kandidat['komunitas']->user)
        ->get(route('admin.penugasan.index'))
        ->assertForbidden();

    $this->actingAs($admin)->from(route('admin.penugasan.index'))
        ->post(route('admin.penugasan.store', $laporan), [
            'komunitas_relawan_id' => $kandidat['komunitas']->id,
            'jumlah_personil_tugas' => $kandidat['sisa_personil'] + 1,
            'armada_perahu_tugas' => 0,
        ])
        ->assertRedirect(route('admin.penugasan.index'))
        ->assertSessionHasErrors('jumlah_personil_tugas');

    expect(Penugasan::where('laporan_banjir_id', $laporan->id)->exists())->toBeFalse()
        ->and($laporan->fresh()->status)->toBe('terverifikasi');
});

test('community rejection returns the report to the unassigned queue and leaves an audit record', function () {
    $laporan = laporanUntukPenugasan();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komunitas = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $penugasan = Penugasan::create([
        'laporan_banjir_id' => $laporan->id,
        'komunitas_relawan_id' => $komunitas->id,
        'ditugaskan_oleh' => $admin->id,
        'jumlah_personil_tugas' => 2,
        'stage' => 'menunggu',
    ]);

    $this->actingAs($komunitas->user)->post(route('komunitas.penugasan.respon', $penugasan), [
        'stage' => 'ditolak',
        'alasan_tolak' => 'Kendala personel pada shift.',
    ])->assertRedirect()->assertSessionHas('success');

    expect($penugasan->fresh()->stage)->toBe('ditolak')
        ->and($laporan->fresh()->status)->toBe('terverifikasi')
        ->and(AuditLog::where('aksi', 'penugasan.ditolak')->where('subjek_id', $penugasan->id)->exists())->toBeTrue();
});

test('community assignment list opens a separate detail view without inline response or progress forms', function () {
    $laporan = laporanUntukPenugasan();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komunitas = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $penugasan = Penugasan::create([
        'laporan_banjir_id' => $laporan->id,
        'komunitas_relawan_id' => $komunitas->id,
        'ditugaskan_oleh' => $admin->id,
        'jumlah_personil_tugas' => 2,
        'stage' => 'menunggu',
    ]);

    $this->actingAs($komunitas->user)
        ->get(route('komunitas.penugasan.index'))
        ->assertOk()
        ->assertSee(route('komunitas.penugasan.show', $penugasan), false)
        ->assertSee('Lihat detail')
        ->assertDontSee('name="stage" value="diterima"', false)
        ->assertDontSee(route('komunitas.penugasan.progres', $penugasan), false);

    $this->get(route('komunitas.penugasan.show', $penugasan))
        ->assertOk()
        ->assertSee('Respons penugasan')
        ->assertSee('Terima tugas')
        ->assertSee('Alasan penolakan');
});

test('community progresses sequentially and progress events are audited', function () {
    $laporan = laporanUntukPenugasan();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komunitas = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $penugasan = Penugasan::create([
        'laporan_banjir_id' => $laporan->id,
        'komunitas_relawan_id' => $komunitas->id,
        'ditugaskan_oleh' => $admin->id,
        'jumlah_personil_tugas' => 2,
        'stage' => 'diterima',
    ]);

    $this->actingAs($komunitas->user)
        ->from(route('komunitas.penugasan.show', $penugasan))
        ->patch(route('komunitas.penugasan.progres', $penugasan), [
            'stage' => 'ditangani',
        ])->assertRedirect(route('komunitas.penugasan.show', $penugasan))
        ->assertSessionHasErrors('stage');

    foreach ([
        ['menuju_lokasi', 'Berangkat menuju lokasi.'],
        ['ditangani', 'Penanganan dimulai.'],
        ['selesai', 'Penanganan selesai.'],
    ] as [$stage, $catatan]) {
        $this->patch(route('komunitas.penugasan.progres', $penugasan), [
            'stage' => $stage,
            'catatan' => $catatan,
        ])->assertRedirect(route('komunitas.penugasan.show', $penugasan))
            ->assertSessionHas('success');
    }

    expect($penugasan->fresh()->stage)->toBe('selesai')
        ->and($penugasan->fresh()->waktu_menuju_lokasi)->not->toBeNull()
        ->and($penugasan->fresh()->waktu_ditangani)->not->toBeNull()
        ->and($penugasan->fresh()->waktu_selesai)->not->toBeNull()
        ->and($penugasan->fresh()->catatan_tahap)->toMatchArray([
            'menuju_lokasi' => 'Berangkat menuju lokasi.',
            'ditangani' => 'Penanganan dimulai.',
            'selesai' => 'Penanganan selesai.',
        ])
        ->and(AuditLog::whereIn('aksi', [
            'penugasan.menuju_lokasi',
            'penugasan.ditangani',
            'penugasan.selesai',
        ])->where('subjek_id', $penugasan->id)->count())->toBe(3)
        ->and($laporan->fresh()->status)->toBe('selesai');

    $this->get(route('komunitas.penugasan.show', $penugasan))
        ->assertOk()
        ->assertSee('Riwayat progres')
        ->assertSee('Penanganan selesai.')
        ->assertSee('Penugasan selesai');
});

test('rejected community assignment shows reason and never offers field progress', function () {
    $laporan = laporanUntukPenugasan();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $komunitas = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $penugasan = Penugasan::create([
        'laporan_banjir_id' => $laporan->id,
        'komunitas_relawan_id' => $komunitas->id,
        'ditugaskan_oleh' => $admin->id,
        'jumlah_personil_tugas' => 2,
        'stage' => 'menunggu',
    ]);
    $this->actingAs($komunitas->user)->post(route('komunitas.penugasan.respon', $penugasan), [
        'stage' => 'ditolak',
        'alasan_tolak' => 'Tidak ada relawan pada shift tersebut.',
    ])->assertRedirect()->assertSessionHas('success');

    $this->get(route('komunitas.penugasan.show', $penugasan))
        ->assertOk()
        ->assertSee('Alasan penolakan')
        ->assertSee('Tidak ada relawan pada shift tersebut.')
        ->assertDontSee('Pembaruan progres')
        ->assertDontSee(route('komunitas.penugasan.progres', $penugasan), false);
});

test('admin can reassign a pending task atomically and record the reason', function () {
    $laporan = laporanUntukPenugasan();
    $admin = User::where('email', 'admin@alir.test')->firstOrFail();
    $lama = KomunitasRelawan::where('nama', 'Komunitas Relawan Tirto')->firstOrFail();
    $penugasanLama = Penugasan::create([
        'laporan_banjir_id' => $laporan->id,
        'komunitas_relawan_id' => $lama->id,
        'ditugaskan_oleh' => $admin->id,
        'jumlah_personil_tugas' => 2,
        'stage' => 'menunggu',
    ]);
    $kandidat = collect(app(RekomendasiPenugasanService::class)->untukLaporan($laporan->fresh())['kelompok'])
        ->flatMap(fn (array $group) => $group['kandidat'])
        ->first(fn (array $candidate) => $candidate['bisa_ditugaskan']);
    expect($kandidat)->not->toBeNull()->and($kandidat['komunitas']->id)->not->toBe($lama->id);

    $this->actingAs($admin)->patch(route('admin.penugasan.update', $penugasanLama), [
        'komunitas_relawan_id' => $kandidat['komunitas']->id,
        'jumlah_personil_tugas' => 2,
        'armada_perahu_tugas' => 0,
        'alasan_pengalihan' => 'Komunitas awal tidak dapat menerima.',
    ])->assertRedirect()->assertSessionHas('success');

    $penugasanLama->refresh();
    $baru = Penugasan::where('laporan_banjir_id', $laporan->id)->where('id', '!=', $penugasanLama->id)->firstOrFail();
    expect($penugasanLama->stage)->toBe('dibatalkan')
        ->and($baru->stage)->toBe('menunggu')
        ->and(Penugasan::where('laporan_banjir_id', $laporan->id)->count())->toBe(2)
        ->and(AuditLog::where('aksi', 'penugasan.alihkan_batalkan')->where('deskripsi', 'like', '%Komunitas awal tidak dapat menerima.%')->exists())->toBeTrue()
        ->and($lama->user->notifications()->where('type', PenugasanRelawanNotification::class)->exists())->toBeTrue()
        ->and($kandidat['komunitas']->user->notifications()->where('type', PenugasanRelawanNotification::class)->exists())->toBeTrue();
});

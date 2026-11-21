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
        ->assertSee('Rekomendasi komunitas diurutkan berdasarkan jarak (Haversine). ETA dihitung 15 km/jam.')
        ->assertSee('P-'.$belumDitugaskan->kode_pelacakan.' &middot; Tirto', false)
        ->assertSee('<strong>'.$belumDitugaskan->jumlah_warga.'</strong> warga', false)
        ->assertSee('tag red">Keparahan Berat', false)
        ->assertSee('tag amber">Belum ditugaskan', false)
        ->assertSee('data-dialog-id="assign-dialog-'.$belumDitugaskan->id.'-'.$komunitas->id.'"', false)
        ->assertSee('name="jumlah_personil_tugas" min="1"', false)
        ->assertSee('name="armada_perahu_tugas" min="0"', false)
        ->assertSee('name="catatan"', false)
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

    $this->get(route('admin.penugasan.index', ['status' => 'sudah_ditugaskan']))
        ->assertOk()
        ->assertSee('id="laporan-'.$sudahDitugaskan->id.'"', false)
        ->assertSee('P-'.$sudahDitugaskan->kode_pelacakan, false)
        ->assertSee('class="task-section"', false)
        ->assertDontSee('id="laporan-'.$belumDitugaskan->id.'"', false)
        ->assertDontSee('class="groups"', false)
        ->assertDontSee('class="candidate-list"', false)
        ->assertSee('lihat-penugasan-'.$penugasan->id, false)
        ->assertSee('edit-penugasan-'.$penugasan->id, false)
        ->assertSee('hapus-penugasan-'.$penugasan->id, false);
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
        ->and($penugasan->jumlah_personil_tugas)->toBe(2)
        ->and($penugasan->armada_perahu_tugas)->toBe(1)
        ->and($laporan->fresh()->status)->toBe('ditugaskan')
        ->and(AuditLog::where('aksi', 'penugasan.buat')->where('subjek_id', $penugasan->id)->exists())->toBeTrue()
        ->and($komunitas->user->notifications()->where('type', PenugasanRelawanNotification::class)->exists())->toBeTrue();

    $this->actingAs($komunitas->user)
        ->get(route('komunitas.penugasan.index'))
        ->assertOk()
        ->assertSee('Jalan Siaga')
        ->assertSee('Terima tugas')
        ->assertSee('Tolak');
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

test('admin cannot edit or cancel an assignment after it has left the waiting stage', function () {
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
    ])->assertRedirect()->assertSessionHasErrors('penugasan');

    $this->post(route('admin.penugasan.batalkan', $penugasan), [
        'alasan' => 'Tidak boleh dibatalkan setelah diterima.',
    ])->assertRedirect()->assertSessionHasErrors('penugasan');

    expect($penugasan->fresh()->stage)->toBe('diterima')
        ->and(AuditLog::whereIn('aksi', ['penugasan.perbarui', 'penugasan.batalkan'])->where('subjek_id', $penugasan->id)->exists())->toBeFalse();
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
        ->assertSee('<button type="button" class="btn btn-coral btn-sm candidate-assign" disabled>Tugaskan</button>', false);

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

<?php

use App\Models\AuditLog;
use App\Models\DistribusiBantuan;
use App\Models\Dokumentasi;
use App\Models\KomunitasRelawan;
use App\Models\LaporanBanjir;
use App\Models\Penugasan;
use App\Models\User;
use App\Models\Wilayah;
use App\Support\BatasUnggah;
use Database\Factories\DokumentasiFactory;
use Database\Factories\LaporanBanjirFactory;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->seed([WilayahSeeder::class, UserSeeder::class, KomunitasDefaultSeeder::class]);
    User::query()->update(['wajib_ganti_sandi' => false]);
});

function dokKomunitas(string $kelurahan = 'Tirto'): KomunitasRelawan
{
    return KomunitasRelawan::where('nama', "Komunitas Relawan {$kelurahan}")->firstOrFail();
}

function dokAdmin(): User
{
    return User::where('email', 'admin@alir.test')->firstOrFail();
}

/** Laporan yang ditangani komunitas (penugasan diterima). */
function dokLaporanDitangani(KomunitasRelawan $komunitas): LaporanBanjir
{
    $laporan = LaporanBanjirFactory::new()->create(['wilayah_id' => $komunitas->wilayah_id]);
    Penugasan::create([
        'laporan_banjir_id' => $laporan->id,
        'komunitas_relawan_id' => $komunitas->id,
        'ditugaskan_oleh' => dokAdmin()->id,
        'stage' => 'diterima',
    ]);

    return $laporan;
}

function dokDistribusi(KomunitasRelawan $komunitas, LaporanBanjir $laporan): DistribusiBantuan
{
    return DistribusiBantuan::create([
        'komunitas_relawan_id' => $komunitas->id,
        'laporan_banjir_id' => $laporan->id,
        'item' => 'Beras',
        'jumlah' => 20,
        'tanggal' => now()->toDateString(),
    ]);
}

function dokPng(): string
{
    return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/ic0AAAAASUVORK5CYII=');
}

function dokFile(string $nama = 'aksi.png'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($nama, dokPng());
}

function dokDisimpan(KomunitasRelawan $komunitas, string $status = 'menunggu', ?string $alasan = null): Dokumentasi
{
    Storage::disk('local')->put('dokumentasi/contoh.jpg', dokPng());

    return DokumentasiFactory::new()->status($status, $alasan)->create([
        'komunitas_relawan_id' => $komunitas->id,
        'laporan_banjir_id' => dokLaporanDitangani($komunitas)->id,
    ]);
}

test('komunitas mengunggah foto yang tertaut laporan dan logistik, tersimpan Menunggu Moderasi', function () {
    $komunitas = dokKomunitas();
    $laporan = dokLaporanDitangani($komunitas);
    $distribusi = dokDistribusi($komunitas, $laporan);

    $this->actingAs($komunitas->user)->post(route('komunitas.dokumentasi.store'), [
        'laporan_banjir_id' => $laporan->id,
        'distribusi_bantuan_id' => $distribusi->id,
        'keterangan' => 'Pembagian beras di RT 03',
        'berkas' => dokFile(),
    ])->assertRedirect(route('komunitas.dokumentasi.index'));

    $dok = Dokumentasi::firstOrFail();
    expect($dok->status_moderasi)->toBe('menunggu')
        ->and($dok->jenis_media)->toBe('foto')
        ->and($dok->laporan_banjir_id)->toBe($laporan->id)
        ->and($dok->distribusi_bantuan_id)->toBe($distribusi->id)
        ->and($dok->keterangan)->toBe('Pembagian beras di RT 03');
    Storage::disk('local')->assertExists($dok->media_path);
    expect(AuditLog::where('aksi', 'dokumentasi.unggah')->exists())->toBeTrue();
});

test('video mp4 diterima dan dicatat sebagai video', function () {
    $komunitas = dokKomunitas();
    $laporan = dokLaporanDitangani($komunitas);

    $this->actingAs($komunitas->user)->post(route('komunitas.dokumentasi.store'), [
        'laporan_banjir_id' => $laporan->id,
        'berkas' => UploadedFile::fake()->create('aksi.mp4', 2000, 'video/mp4'),
    ])->assertRedirect(route('komunitas.dokumentasi.index'));

    expect(Dokumentasi::firstOrFail()->jenis_media)->toBe('video');
});

test('berkas dengan tipe atau ukuran tidak sesuai ditolak', function () {
    $komunitas = dokKomunitas();
    $laporan = dokLaporanDitangani($komunitas);
    $kirim = fn ($berkas) => $this->actingAs($komunitas->user)->from(route('komunitas.dokumentasi.create'))
        ->post(route('komunitas.dokumentasi.store'), ['laporan_banjir_id' => $laporan->id, 'berkas' => $berkas]);

    $kirim(UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload'))->assertSessionHasErrors('berkas');
    $kirim(UploadedFile::fake()->create('dok.pdf', 100, 'application/pdf'))->assertSessionHasErrors('berkas');
    $kirim(UploadedFile::fake()->create('besar.jpg', 6000, 'image/jpeg'))->assertSessionHasErrors('berkas');
    $kirim(UploadedFile::fake()->create('besar.mp4', 52000, 'video/mp4'))->assertSessionHasErrors('berkas');
    $this->actingAs($komunitas->user)->post(route('komunitas.dokumentasi.store'), ['laporan_banjir_id' => $laporan->id])
        ->assertSessionHasErrors('berkas');

    expect(Dokumentasi::count())->toBe(0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

test('laporan wajib dan hanya laporan yang ditangani komunitas yang boleh ditautkan', function () {
    $komunitas = dokKomunitas();
    $lain = dokKomunitas('Degayu');
    $laporanLain = dokLaporanDitangani($lain);
    $distribusiLain = dokDistribusi($lain, $laporanLain);
    $laporanSendiri = dokLaporanDitangani($komunitas);

    $this->actingAs($komunitas->user)->post(route('komunitas.dokumentasi.store'), ['berkas' => dokFile()])
        ->assertSessionHasErrors('laporan_banjir_id');
    $this->actingAs($komunitas->user)->post(route('komunitas.dokumentasi.store'), ['laporan_banjir_id' => $laporanLain->id, 'berkas' => dokFile()])
        ->assertSessionHasErrors('laporan_banjir_id');
    $this->actingAs($komunitas->user)->post(route('komunitas.dokumentasi.store'), [
        'laporan_banjir_id' => $laporanSendiri->id, 'distribusi_bantuan_id' => $distribusiLain->id, 'berkas' => dokFile(),
    ])->assertSessionHasErrors('laporan_banjir_id');

    expect(Dokumentasi::count())->toBe(0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

test('akses unggah dibatasi login dan role', function () {
    $this->get(route('komunitas.dokumentasi.index'))->assertRedirect(route('login'));
    $this->actingAs(dokAdmin())->get(route('komunitas.dokumentasi.index'))->assertForbidden();
    $this->actingAs(dokKomunitas()->user)->get(route('komunitas.dokumentasi.create'))->assertOk();
    $this->actingAs(dokKomunitas()->user)->get(route('komunitas.dokumentasi.index'))->assertOk();
});

test('komunitas hanya dapat melihat berkas miliknya sendiri', function () {
    $dok = dokDisimpan(dokKomunitas());

    $this->actingAs(dokKomunitas()->user)->get(route('komunitas.dokumentasi.media', $dok))->assertOk();
    $this->actingAs(dokKomunitas('Degayu')->user)->get(route('komunitas.dokumentasi.media', $dok))->assertForbidden();
    $this->get(route('galeri.media', $dok))->assertNotFound();
});

test('admin menyetujui dokumentasi dan hanya yang disetujui tampil di galeri publik', function () {
    $komunitas = dokKomunitas();
    $menunggu = dokDisimpan($komunitas);
    $ditolak = dokDisimpan($komunitas, 'ditolak', 'Foto buram');
    $setuju = dokDisimpan($komunitas);

    $this->actingAs(dokAdmin())->post(route('admin.dokumentasi.setujui', $setuju))->assertRedirect();
    expect($setuju->fresh()->status_moderasi)->toBe('disetujui');

    $this->get(route('galeri.media', $setuju))->assertOk();
    $this->get(route('galeri.media', $menunggu))->assertNotFound();
    $this->get(route('galeri.media', $ditolak))->assertNotFound();

    $html = $this->get(route('galeri'))->assertOk()->getContent();
    expect($html)->toContain(route('galeri.media', $setuju))
        ->not->toContain(route('galeri.media', $menunggu))
        ->not->toContain(route('galeri.media', $ditolak));
});

test('admin menolak dokumentasi dengan alasan yang terlihat oleh komunitas', function () {
    $komunitas = dokKomunitas();
    $dok = dokDisimpan($komunitas);
    $admin = dokAdmin();

    $this->actingAs($admin)->post(route('admin.dokumentasi.tolak', $dok), ['alasan_tolak' => ''])->assertSessionHasErrors('alasan_tolak');
    expect($dok->fresh()->status_moderasi)->toBe('menunggu');

    $this->actingAs($admin)->post(route('admin.dokumentasi.tolak', $dok), ['alasan_tolak' => 'Foto tidak sesuai laporan'])->assertRedirect();
    expect($dok->fresh()->status_moderasi)->toBe('ditolak');

    $this->actingAs($komunitas->user)->get(route('komunitas.dokumentasi.index'))->assertOk()
        ->assertSee('Foto tidak sesuai laporan')->assertSee('Ditolak');
    $this->get(route('galeri'))->assertDontSee(route('galeri.media', $dok));
});

test('moderasi hanya untuk admin dan hanya sekali', function () {
    $komunitas = dokKomunitas();
    $dok = dokDisimpan($komunitas);

    $this->actingAs($komunitas->user)->post(route('admin.dokumentasi.setujui', $dok))->assertForbidden();
    $this->actingAs($komunitas->user)->get(route('admin.dokumentasi.index'))->assertForbidden();

    $this->actingAs(dokAdmin())->post(route('admin.dokumentasi.setujui', $dok));
    $this->actingAs(dokAdmin())->post(route('admin.dokumentasi.tolak', $dok), ['alasan_tolak' => 'Terlambat menolak'])
        ->assertSessionHas('error');
    expect($dok->fresh()->status_moderasi)->toBe('disetujui');
});

test('galeri mengelompokkan per wilayah dan komunitas serta dapat difilter wilayah', function () {
    $tirto = dokKomunitas();
    $degayu = dokKomunitas('Degayu');
    $a = dokDisimpan($tirto, 'disetujui');
    $b = dokDisimpan($degayu, 'disetujui');

    $html = $this->get(route('galeri'))->assertOk()->getContent();
    expect($html)->toContain('Komunitas Relawan Tirto')->toContain('Komunitas Relawan Degayu')
        ->toContain(route('galeri.media', $a))->toContain(route('galeri.media', $b));

    $filter = $this->get(route('galeri', ['wilayah' => Wilayah::where('nama_kelurahan', 'Tirto')->value('id')]))->getContent();
    expect($filter)->toContain(route('galeri.media', $a))->not->toContain(route('galeri.media', $b));
});

test('halaman moderasi admin dan form unggah komunitas tampil dengan data di semua status', function () {
    $komunitas = dokKomunitas();
    dokDisimpan($komunitas);
    dokDisimpan($komunitas, 'disetujui');
    dokDisimpan($komunitas, 'ditolak', 'Foto buram');
    dokDistribusi($komunitas, dokLaporanDitangani($komunitas));

    foreach (['menunggu', 'disetujui', 'ditolak'] as $status) {
        $this->actingAs(dokAdmin())->get(route('admin.dokumentasi.index', ['status' => $status]))->assertOk();
    }
    $this->actingAs(dokAdmin())->get(route('admin.dokumentasi.index'))->assertSee('Setujui')->assertSee('Tolak');
    $this->actingAs($komunitas->user)->get(route('komunitas.dokumentasi.create'))->assertOk()->assertSee('Beras');
    $this->actingAs($komunitas->user)->get(route('komunitas.dokumentasi.index'))->assertOk()->assertSee('Foto buram');
});

test('batas unggah efektif tidak pernah melebihi batas PHP maupun config', function () {
    $iniKb = fn (string $k) => intdiv(ini_parse_quantity(ini_get($k)), 1024);
    $batasPhp = min($iniKb('upload_max_filesize'), $iniKb('post_max_size') - 256);

    expect(BatasUnggah::efektifKb(100))->toBe(100)
        ->and(BatasUnggah::efektifKb(PHP_INT_MAX >> 12))->toBe($batasPhp)
        ->and(BatasUnggah::videoKb())->toBeLessThanOrEqual($batasPhp)
        ->and(BatasUnggah::mb(40704))->toBe(39);

    // form unggah menampilkan batas yang benar-benar bisa dipakai
    $komunitas = dokKomunitas();
    dokLaporanDitangani($komunitas);
    $mb = BatasUnggah::mb(BatasUnggah::videoKb());
    $this->actingAs($komunitas->user)->get(route('komunitas.dokumentasi.create'))->assertOk()->assertSee("Video MP4 atau WebM maks {$mb} MB");
});

test('komunitas memperbaiki dokumentasi yang ditolak lalu kembali Menunggu Moderasi', function () {
    $komunitas = dokKomunitas();
    $dok = dokDisimpan($komunitas, 'ditolak', 'Foto buram');
    $laporanBaru = dokLaporanDitangani($komunitas);
    $pathLama = $dok->media_path;

    $this->actingAs($komunitas->user)->get(route('komunitas.dokumentasi.edit', $dok))->assertOk()->assertSee('Foto buram');

    $this->actingAs($komunitas->user)->put(route('komunitas.dokumentasi.update', $dok), [
        'laporan_banjir_id' => $laporanBaru->id,
        'keterangan' => 'Foto sudah diganti yang lebih jelas',
        'berkas' => dokFile('baru.png'),
    ])->assertRedirect(route('komunitas.dokumentasi.index'));

    $dok->refresh();
    expect($dok->status_moderasi)->toBe('menunggu')
        ->and($dok->alasan_tolak)->toBeNull()
        ->and($dok->keterangan)->toBe('Foto sudah diganti yang lebih jelas')
        ->and($dok->laporan_banjir_id)->toBe($laporanBaru->id)
        ->and($dok->media_path)->not->toBe($pathLama);
    Storage::disk('local')->assertMissing($pathLama);
    Storage::disk('local')->assertExists($dok->media_path);
    expect(AuditLog::where('aksi', 'dokumentasi.perbaiki')->where('deskripsi', 'like', '%Foto buram%')->exists())->toBeTrue();
});

test('perbaikan tanpa mengganti berkas mempertahankan berkas lama', function () {
    $komunitas = dokKomunitas();
    $dok = dokDisimpan($komunitas, 'ditolak', 'Catatan kurang jelas');

    $this->actingAs($komunitas->user)->put(route('komunitas.dokumentasi.update', $dok), [
        'laporan_banjir_id' => $dok->laporan_banjir_id,
        'keterangan' => 'Catatan sudah dilengkapi',
    ])->assertRedirect(route('komunitas.dokumentasi.index'));

    $dok->refresh();
    expect($dok->status_moderasi)->toBe('menunggu')->and($dok->media_path)->toBe('dokumentasi/contoh.jpg');
    Storage::disk('local')->assertExists('dokumentasi/contoh.jpg');
});

test('perbaikan hanya untuk dokumentasi ditolak milik sendiri dengan tautan yang valid', function () {
    $komunitas = dokKomunitas();
    $lain = dokKomunitas('Degayu');
    $ditolak = dokDisimpan($komunitas, 'ditolak', 'Salah');
    $menunggu = dokDisimpan($komunitas);
    $disetujui = dokDisimpan($komunitas, 'disetujui');
    $data = fn (Dokumentasi $d) => ['laporan_banjir_id' => $d->laporan_banjir_id, 'keterangan' => 'x'];

    $this->actingAs($lain->user)->get(route('komunitas.dokumentasi.edit', $ditolak))->assertForbidden();
    $this->actingAs($lain->user)->put(route('komunitas.dokumentasi.update', $ditolak), $data($ditolak))->assertForbidden();
    $this->actingAs($komunitas->user)->get(route('komunitas.dokumentasi.edit', $menunggu))->assertForbidden();
    $this->actingAs($komunitas->user)->put(route('komunitas.dokumentasi.update', $disetujui), $data($disetujui))->assertForbidden();
    $this->actingAs(dokAdmin())->get(route('komunitas.dokumentasi.edit', $ditolak))->assertForbidden();

    $laporanLain = dokLaporanDitangani($lain);
    $this->actingAs($komunitas->user)->put(route('komunitas.dokumentasi.update', $ditolak), ['laporan_banjir_id' => $laporanLain->id])
        ->assertSessionHasErrors('laporan_banjir_id');
    $this->actingAs($komunitas->user)->put(route('komunitas.dokumentasi.update', $ditolak), $data($ditolak) + ['berkas' => UploadedFile::fake()->create('x.exe', 10, 'application/x-msdownload')])
        ->assertSessionHasErrors('berkas');
    expect($ditolak->fresh()->status_moderasi)->toBe('ditolak')->and($disetujui->fresh()->status_moderasi)->toBe('disetujui');
});

test('komunitas dapat menghapus dokumentasi menunggu atau ditolak tetapi tidak yang disetujui', function () {
    $komunitas = dokKomunitas();
    $lain = dokKomunitas('Degayu');

    $menunggu = dokDisimpan($komunitas);
    $this->actingAs($lain->user)->delete(route('komunitas.dokumentasi.destroy', $menunggu))->assertForbidden();
    $this->actingAs(dokAdmin())->delete(route('komunitas.dokumentasi.destroy', $menunggu))->assertForbidden();
    $this->actingAs($komunitas->user)->delete(route('komunitas.dokumentasi.destroy', $menunggu))->assertRedirect(route('komunitas.dokumentasi.index'));
    expect(Dokumentasi::find($menunggu->id))->toBeNull();
    Storage::disk('local')->assertMissing('dokumentasi/contoh.jpg');
    expect(AuditLog::where('aksi', 'dokumentasi.hapus')->count())->toBe(1);

    $ditolak = dokDisimpan($komunitas, 'ditolak', 'Salah');
    $this->actingAs($komunitas->user)->delete(route('komunitas.dokumentasi.destroy', $ditolak))->assertRedirect();
    expect(Dokumentasi::find($ditolak->id))->toBeNull();

    $disetujui = dokDisimpan($komunitas, 'disetujui');
    $this->actingAs($komunitas->user)->delete(route('komunitas.dokumentasi.destroy', $disetujui))->assertForbidden();
    expect(Dokumentasi::find($disetujui->id))->not->toBeNull();
    Storage::disk('local')->assertExists('dokumentasi/contoh.jpg');
});

test('tombol Perbaiki dan Hapus tampil sesuai status di daftar komunitas', function () {
    $komunitas = dokKomunitas();
    $ditolak = dokDisimpan($komunitas, 'ditolak', 'Salah');
    $menunggu = dokDisimpan($komunitas);
    $disetujui = dokDisimpan($komunitas, 'disetujui');

    $html = $this->actingAs($komunitas->user)->get(route('komunitas.dokumentasi.index'))->assertOk()->getContent();
    $hapus = fn ($d) => 'action="'.route('komunitas.dokumentasi.destroy', $d).'"';
    expect($html)->toContain(route('komunitas.dokumentasi.edit', $ditolak))
        ->toContain($hapus($ditolak))
        ->toContain($hapus($menunggu))
        ->not->toContain(route('komunitas.dokumentasi.edit', $menunggu))
        ->not->toContain(route('komunitas.dokumentasi.edit', $disetujui))
        ->not->toContain($hapus($disetujui));
});

test('halaman lacak laporan menampilkan hanya media dokumentasi yang disetujui, tanpa catatan', function () {
    $komunitas = dokKomunitas();
    $disetujui = dokDisimpan($komunitas, 'disetujui');
    $disetujui->update(['keterangan' => 'Bukti sembako tersalur']);
    $menunggu = dokDisimpan($komunitas);
    $menunggu->update(['laporan_banjir_id' => $disetujui->laporan_banjir_id, 'keterangan' => 'Rahasia menunggu']);
    $kode = $disetujui->laporanBanjir->kode_pelacakan;

    $this->get(route('lacak.show', $kode))->assertOk()
        ->assertSee('Dokumentasi penanganan')
        ->assertDontSee('Bukti sembako tersalur')
        ->assertSee(route('galeri.media', $disetujui))
        ->assertDontSee('Rahasia menunggu')
        ->assertDontSee($komunitas->nama);

    $disetujui->update(['status_moderasi' => 'menunggu']);
    $this->get(route('lacak.show', $kode))->assertOk()->assertDontSee('Dokumentasi penanganan');
});

test('galeri publik dipaginasi dan tidak memuat semua dokumentasi sekaligus', function () {
    $komunitas = dokKomunitas();
    $laporan = dokLaporanDitangani($komunitas);
    $ids = collect(range(1, 30))->map(fn ($i) => DokumentasiFactory::new()->create([
        'komunitas_relawan_id' => $komunitas->id, 'laporan_banjir_id' => $laporan->id,
        'status_moderasi' => 'disetujui', 'media_path' => "dokumentasi/g{$i}.jpg", 'created_at' => now()->subMinutes($i),
    ])->id);

    $hal1 = $this->get(route('galeri'))->assertOk()->getContent();
    expect(substr_count($hal1, 'data-gl-open'))->toBeGreaterThanOrEqual(24)->toBeLessThan(30)
        ->and($hal1)->toContain('Menampilkan 1-24 dari 30 dokumentasi')->toContain('page=2');

    $hal2 = $this->get(route('galeri', ['page' => 2]))->assertOk()->getContent();
    expect($hal2)->toContain('Menampilkan 25-30 dari 30 dokumentasi')
        ->toContain(route('galeri.media', $ids->last()));
});

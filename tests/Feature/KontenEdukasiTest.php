<?php

use App\Models\KontenEdukasi;
use App\Models\User;
use App\Services\KontenEdukasiService;
use Database\Factories\KontenEdukasiFactory;
use Database\Seeders\KomunitasDefaultSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([WilayahSeeder::class, UserSeeder::class, KomunitasDefaultSeeder::class]);
    User::query()->update(['wajib_ganti_sandi' => false]);
    $this->admin = User::where('email', 'admin@alir.test')->firstOrFail();
});

function dataKonten(array $override = []): array
{
    return $override + [
        'judul' => 'Tas siaga untuk keluarga',
        'kategori' => 'kesiapsiagaan',
        'tipe' => 'artikel',
        'isi' => 'Siapkan senter dan air minum.',
        'status' => 'terbit',
    ];
}

test('admin menambah, melihat, mengubah, dan menghapus konten edukasi', function () {
    $this->actingAs($this->admin)->post(route('admin.edukasi.store'), dataKonten())->assertRedirect(route('admin.edukasi.index'));

    $konten = KontenEdukasi::firstOrFail();
    expect($konten->slug)->toBe('tas-siaga-untuk-keluarga')->and($konten->status)->toBe('terbit')->and($konten->diterbitkan_pada)->not->toBeNull();

    $this->actingAs($this->admin)->get(route('admin.edukasi.index'))->assertOk()->assertSee('Tas siaga untuk keluarga');
    $this->actingAs($this->admin)->get(route('admin.edukasi.edit', $konten))->assertOk();

    $this->actingAs($this->admin)->put(route('admin.edukasi.update', $konten), dataKonten(['judul' => 'Tas siaga lengkap', 'kategori' => 'pencegahan']))
        ->assertRedirect(route('admin.edukasi.index'));
    $konten->refresh();
    expect($konten->judul)->toBe('Tas siaga lengkap')->and($konten->kategori)->toBe('pencegahan')->and($konten->slug)->toBe('tas-siaga-lengkap');

    $this->actingAs($this->admin)->delete(route('admin.edukasi.destroy', $konten))->assertRedirect(route('admin.edukasi.index'));
    expect(KontenEdukasi::count())->toBe(0);
});

test('kelola konten hanya untuk admin', function () {
    $komunitas = User::where('role', User::ROLE_KOMUNITAS)->firstOrFail();

    $this->get(route('admin.edukasi.index'))->assertRedirect(route('login'));
    $this->actingAs($komunitas)->get(route('admin.edukasi.index'))->assertForbidden();
    $this->actingAs($komunitas)->post(route('admin.edukasi.store'), dataKonten())->assertForbidden();
    expect(KontenEdukasi::count())->toBe(0);
});

test('validasi kategori, tipe, isi artikel, dan tautan video', function () {
    $post = fn (array $d) => $this->actingAs($this->admin)->post(route('admin.edukasi.store'), dataKonten($d));

    $post(['kategori' => 'ngawur'])->assertSessionHasErrors('kategori');
    $post(['tipe' => 'podcast'])->assertSessionHasErrors('tipe');
    $post(['isi' => ''])->assertSessionHasErrors('isi');
    $post(['tipe' => 'video_sematan', 'isi' => null, 'media_url' => 'https://evil.example/video'])->assertSessionHasErrors('media_url');
    $post(['tipe' => 'video_sematan', 'isi' => null, 'media_url' => ''])->assertSessionHasErrors('media_url');
    expect(KontenEdukasi::count())->toBe(0);

    $post(['tipe' => 'video_sematan', 'isi' => null, 'media_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'])->assertSessionDoesntHaveErrors();
    expect(KontenEdukasi::firstOrFail()->media_url)->toBe('https://www.youtube.com/embed/dQw4w9WgXcQ');
});

test('slug tetap unik untuk judul yang sama', function () {
    $this->actingAs($this->admin)->post(route('admin.edukasi.store'), dataKonten());
    $this->actingAs($this->admin)->post(route('admin.edukasi.store'), dataKonten());

    expect(KontenEdukasi::pluck('slug')->all())->toBe(['tas-siaga-untuk-keluarga', 'tas-siaga-untuk-keluarga-2']);
});

test('status dapat diubah antara draft dan terbit', function () {
    $konten = KontenEdukasiFactory::new()->draft()->create();

    $this->actingAs($this->admin)->patch(route('admin.edukasi.status', $konten), ['status' => 'terbit'])->assertRedirect();
    expect($konten->fresh()->status)->toBe('terbit')->and($konten->fresh()->diterbitkan_pada)->not->toBeNull();

    $this->actingAs($this->admin)->patch(route('admin.edukasi.status', $konten), ['status' => 'draft']);
    expect($konten->fresh()->status)->toBe('draft');
});

test('pengunjung hanya melihat konten terbit dan dapat membukanya', function () {
    $terbit = KontenEdukasiFactory::new()->create(['judul' => 'Artikel terbit publik', 'slug' => 'artikel-terbit-publik']);
    $draft = KontenEdukasiFactory::new()->draft()->create(['judul' => 'Artikel masih draft', 'slug' => 'artikel-masih-draft']);

    $this->get(route('edukasi'))->assertOk()->assertSee('Artikel terbit publik')->assertDontSee('Artikel masih draft');
    $this->get(route('edukasi.show', $terbit))->assertOk()->assertSee('Artikel terbit publik');
    $this->get(route('edukasi.show', $draft))->assertNotFound();
});

test('konten dapat difilter per kategori dan dicari dengan kata kunci', function () {
    KontenEdukasiFactory::new()->create(['judul' => 'Membersihkan saluran', 'slug' => 'a', 'kategori' => 'pencegahan', 'isi' => 'Singkirkan sampah.']);
    KontenEdukasiFactory::new()->create(['judul' => 'Perlengkapan darurat', 'slug' => 'b', 'kategori' => 'kesiapsiagaan', 'isi' => 'Senter dan baterai cadangan.']);
    KontenEdukasiFactory::new()->draft()->create(['judul' => 'Rahasia sampah', 'slug' => 'c', 'kategori' => 'pencegahan', 'isi' => 'sampah']);

    $kategori = $this->get(route('edukasi', ['kategori' => 'kesiapsiagaan']))->assertOk();
    $kategori->assertSee('Perlengkapan darurat')->assertDontSee('Membersihkan saluran');

    $cari = $this->get(route('edukasi', ['q' => 'sampah']))->assertOk();
    $cari->assertSee('Membersihkan saluran')->assertDontSee('Perlengkapan darurat')->assertDontSee('Rahasia sampah');

    $this->get(route('edukasi', ['q' => 'baterai']))->assertSee('Perlengkapan darurat');
    $this->get(route('edukasi', ['q' => 'tidak-ada-kata-ini']))->assertOk()->assertSee('Tidak ada konten yang cocok');
    $this->get(route('edukasi', ['kategori' => '<script>']))->assertOk();
});

test('embedUrl hanya menerima YouTube dan Vimeo', function () {
    expect(KontenEdukasiService::embedUrl('https://youtu.be/dQw4w9WgXcQ'))->toBe('https://www.youtube.com/embed/dQw4w9WgXcQ')
        ->and(KontenEdukasiService::embedUrl('https://vimeo.com/123456'))->toBe('https://player.vimeo.com/video/123456')
        ->and(KontenEdukasiService::embedUrl('http://www.youtube.com/watch?v=dQw4w9WgXcQ'))->toBeNull()
        ->and(KontenEdukasiService::embedUrl('https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ'))->toBeNull()
        ->and(KontenEdukasiService::embedUrl('javascript:alert(1)'))->toBeNull();
});

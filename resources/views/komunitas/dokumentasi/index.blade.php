@extends('layouts.komunitas')

@section('title', 'Dokumentasi Aksi')
@section('topbar_title', 'Dokumentasi Aksi')
@section('topbar_subtitle', 'Unggah bukti kegiatan lapangan untuk dimoderasi Tim Startup ALIR')

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">F-08 DOKUMENTASI</span>
            <h2>Dokumentasi Aksi</h2>
            <p class="muted">Hanya dokumentasi yang disetujui yang tampil di galeri publik.</p>
        </div>
        <a href="{{ route('komunitas.dokumentasi.create') }}" class="btn btn-coral" dusk="unggah-dokumentasi">
            <i data-lucide="upload"></i>Unggah Dokumentasi
        </a>
    </div>

    @if ($daftar->isEmpty())
        <div class="card" style="margin-top:20px">
            <div class="empty">Belum ada dokumentasi. Unggah foto atau video kegiatan setelah menangani laporan atau menyalurkan bantuan.</div>
        </div>
    @else
        <div class="dk-grid">
            @foreach ($daftar as $d)
                @php $badge = ['menunggu' => 'amber', 'disetujui' => 'teal', 'ditolak' => 'red'][$d->status_moderasi]; @endphp
                <article class="dk-card" dusk="dokumentasi-{{ $d->id }}">
                    <div class="dk-media">
                        @if ($d->isVideo())
                            <video src="{{ route('komunitas.dokumentasi.media', $d) }}" controls preload="metadata" data-poster-frame></video>
                        @else
                            <img src="{{ route('komunitas.dokumentasi.media', $d) }}" alt="Dokumentasi {{ $d->laporanBanjir?->kode_pelacakan }}" loading="lazy">
                        @endif
                        <span class="dk-kind"><i data-lucide="{{ $d->isVideo() ? 'video' : 'image' }}"></i>{{ $d->isVideo() ? 'Video' : 'Foto' }}</span>
                    </div>
                    <div class="dk-body">
                        <div class="dk-meta">
                            <span class="tag {{ $badge }}">{{ $d->labelStatus() }}</span>
                            <span class="tag">{{ $d->created_at->translatedFormat('d M Y') }}</span>
                        </div>
                        @if ($d->keterangan)<p class="dk-note">{{ $d->keterangan }}</p>@endif
                        <div class="dk-link">Laporan: <b class="mono">{{ $d->laporanBanjir?->kode_pelacakan ?? '-' }}</b>
                            @if ($d->distribusiBantuan) · Logistik: <b>{{ $d->distribusiBantuan->jumlah }} {{ $d->distribusiBantuan->item }}</b>@endif
                        </div>
                        @if ($d->status_moderasi === 'ditolak')
                            <div class="dk-reject" role="note"><b>Alasan penolakan</b>{{ $d->alasan_tolak }}</div>
                        @elseif ($d->status_moderasi === 'menunggu')
                            <div class="xs muted">Sedang diperiksa Tim Startup ALIR.</div>
                        @endif

                        @if ($d->status_moderasi !== 'disetujui')
                            <div class="dk-actions">
                                @if ($d->status_moderasi === 'ditolak')
                                    <a href="{{ route('komunitas.dokumentasi.edit', $d) }}" class="btn btn-coral btn-sm" dusk="perbaiki-{{ $d->id }}"><i data-lucide="pencil"></i>Perbaiki</a>
                                @endif
                                <form method="POST" action="{{ route('komunitas.dokumentasi.destroy', $d) }}" onsubmit="return confirm('Hapus dokumentasi ini? Tindakan ini tidak dapat dibatalkan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-sm" dusk="hapus-{{ $d->id }}"><i data-lucide="trash-2"></i>Hapus</button>
                                </form>
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-4">{{ $daftar->links() }}</div>
    @endif
@endsection

@push('scripts')
    @include('partials.video-poster')
@endpush

@extends('layouts.komunitas')

@section('title', 'Unggah Dokumentasi')
@section('topbar_title', 'Unggah Dokumentasi')
@section('topbar_subtitle', 'Foto atau video kegiatan yang tertaut ke laporan dan logistik')

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">F-08 DOKUMENTASI</span>
            <h2>Unggah Dokumentasi</h2>
            <p class="muted">Setelah dikirim, dokumentasi berstatus <b>Menunggu Moderasi</b> sampai disetujui Tim Startup ALIR.</p>
        </div>
        <a href="{{ route('komunitas.dokumentasi.index') }}" class="btn btn-ghost btn-sm">Kembali</a>
    </div>

    @if ($laporanList->isEmpty())
        <div class="card max-w-4xl" style="margin-top:20px">
            <div class="empty">Komunitas Anda belum memiliki laporan yang ditangani, sehingga belum ada yang bisa didokumentasikan. Dokumentasi dapat diunggah setelah menerima penugasan.</div>
        </div>
    @else
        <div class="card max-w-4xl" style="margin-top:20px">
            <form method="POST" action="{{ route('komunitas.dokumentasi.store') }}" enctype="multipart/form-data" class="col gap-4" novalidate id="form-dok">
                @csrf
                @include('komunitas.dokumentasi._form')

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('komunitas.dokumentasi.index') }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-coral" dusk="kirim-dokumentasi"><i data-lucide="send"></i>Kirim untuk Moderasi</button>
                </div>
            </form>
        </div>
    @endif
@endsection

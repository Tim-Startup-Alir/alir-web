@extends('layouts.komunitas')

@section('title', 'Perbaiki Dokumentasi')
@section('topbar_title', 'Perbaiki Dokumentasi')
@section('topbar_subtitle', 'Perbaiki dokumentasi yang ditolak lalu ajukan kembali')

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">F-08 DOKUMENTASI</span>
            <h2>Perbaiki Dokumentasi</h2>
            <p class="muted">Setelah dikirim, dokumentasi kembali berstatus <b>Menunggu Moderasi</b>.</p>
        </div>
        <a href="{{ route('komunitas.dokumentasi.index') }}" class="btn btn-ghost btn-sm">Kembali</a>
    </div>

    <div class="card max-w-4xl" style="margin-top:20px">
        <div class="dk-reject" role="note" style="margin-bottom:18px"><b>Alasan penolakan</b>{{ $dokumentasi->alasan_tolak }}</div>

        <form method="POST" action="{{ route('komunitas.dokumentasi.update', $dokumentasi) }}" enctype="multipart/form-data" class="col gap-4" novalidate id="form-dok">
            @csrf
            @method('PUT')
            @include('komunitas.dokumentasi._form')

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('komunitas.dokumentasi.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-coral" dusk="kirim-perbaikan"><i data-lucide="send"></i>Kirim Perbaikan</button>
            </div>
        </form>
    </div>
@endsection

@extends('layouts.register')

@section('title', 'Pengajuan Ulang Terkirim')

@section('content')
    <div class="card text-center py-10 px-6">
        <div class="w-16 h-16 rounded-full mx-auto mb-5 grid place-items-center" style="background:var(--teal-tint);color:var(--teal-ink)">
            <i data-lucide="check" class="w-8 h-8"></i>
        </div>
        <h2 class="mb-3">Pengajuan ulang terkirim</h2>
        <div class="tag water mb-4">{{ $komunitas->nama }}</div>
        <p class="muted max-w-md mx-auto mb-6">
            Data perbaikan komunitas Anda telah kami terima dan kembali masuk antrean verifikasi Tim Startup ALIR.
            Pemberitahuan hasil peninjauan akan dikirimkan ke email <strong>{{ $komunitas->user->email }}</strong>.
        </p>
        <a class="btn btn-coral" href="{{ route('login') }}">
            <span>Ke halaman masuk</span>
            <i data-lucide="arrow-right"></i>
        </a>
    </div>
@endsection

@extends('layouts.register')

@section('title', 'Pendaftaran Terkirim')

@section('content')
    <div class="card text-center py-10 px-6">
        <div class="w-16 h-16 rounded-full mx-auto mb-5 grid place-items-center" style="background:var(--teal-tint);color:var(--teal-ink)">
            <i data-lucide="mail-check" class="w-8 h-8"></i>
        </div>
        <h2 class="mb-3">Pendaftaran terkirim</h2>
        @if ($nama)
            <div class="tag water mb-4">{{ $nama }}</div>
        @endif
        <p class="muted max-w-md mx-auto mb-6">
            Kami telah mengirimkan tautan verifikasi ke email
            <strong>{{ $email ?? 'Anda' }}</strong>.
            Silakan klik tautan di email tersebut agar pendaftaran masuk ke antrean peninjauan Tim Startup ALIR.
        </p>
        <a class="btn btn-coral" href="{{ route('login') }}">
            <span>Ke halaman masuk</span>
            <i data-lucide="arrow-right"></i>
        </a>
    </div>
@endsection

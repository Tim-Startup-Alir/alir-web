@extends('layouts.register')

@section('title', 'Tautan Perbaikan Tidak Tersedia')

@section('content')
    <div class="card text-center py-10 px-6">
        <div class="w-16 h-16 rounded-full mx-auto mb-5 grid place-items-center" style="background:var(--amber-tint);color:var(--amber-ink)">
            <i data-lucide="info" class="w-8 h-8"></i>
        </div>
        <h2 class="mb-3">Pendaftaran tidak dalam status ditolak</h2>
        <p class="muted max-w-md mx-auto mb-6">
            Pendaftaran <strong>{{ $komunitas->nama }}</strong> saat ini berstatus
            <strong>{{ $komunitas->status_pendaftaran }}</strong>, sehingga form perbaikan tidak lagi diperlukan.
        </p>
        <a class="btn btn-navy" href="{{ route('login') }}">
            <span>Ke halaman masuk</span>
            <i data-lucide="arrow-right"></i>
        </a>
    </div>
@endsection

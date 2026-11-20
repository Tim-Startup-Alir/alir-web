@extends('layouts.register')

@section('title', 'Email Terverifikasi')

@section('content')
    <div class="card text-center py-10 px-6">
        <div class="w-16 h-16 rounded-full mx-auto mb-5 grid place-items-center" style="background:var(--teal-tint);color:var(--teal-ink)">
            <i data-lucide="shield-check" class="w-8 h-8"></i>
        </div>
        <h2 class="mb-3">Email berhasil diverifikasi</h2>
        @if ($komunitas)
            <div class="tag water mb-4">{{ $komunitas->nama }}</div>
        @endif
        <p class="muted max-w-md mx-auto mb-6">
            Pendaftaran komunitas Anda kini sudah masuk ke antrean peninjauan Tim Startup ALIR.
            Kami akan mengirimkan hasil persetujuan atau catatan perbaikan ke alamat email Anda.
        </p>
        <a class="btn btn-coral" href="{{ route('login') }}">
            <span>Ke halaman masuk</span>
            <i data-lucide="arrow-right"></i>
        </a>
    </div>
@endsection

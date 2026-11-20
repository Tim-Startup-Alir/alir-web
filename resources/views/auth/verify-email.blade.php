@extends('layouts.guest')

@section('title', 'Verifikasi Email')

@section('content')
    <h2>Verifikasi alamat email</h2>
    <p class="sub">Silakan buka tautan verifikasi yang telah dikirim ke email <strong>{{ auth()->user()->email }}</strong> sebelum masuk ke portal.</p>

    @if (session('success') || session('status'))
        <div class="mb-4">
            <x-flash-message />
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button class="go" type="submit">
            <span>Kirim ulang email verifikasi</span>
            <i data-lucide="send" class="arr"></i>
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm font-bold text-slate-500 hover:text-slate-800">Keluar</button>
    </form>
@endsection

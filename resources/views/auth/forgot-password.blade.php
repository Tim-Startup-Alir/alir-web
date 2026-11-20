@extends('layouts.guest')

@section('title', 'Lupa Kata Sandi')

@section('content')
    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf
        <h2>Lupa kata sandi?</h2>
        <p class="sub">Masukkan email akun Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi.</p>

        @if (session('success') || session('status'))
            <div class="mb-4">
                <x-flash-message />
            </div>
        @endif

        <div class="fld">
            <label for="email">Email</label>
            <div class="fi">
                <i data-lucide="mail"></i>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    placeholder="nama@alir.test"
                    autocomplete="email"
                    required
                    autofocus
                    class="{{ $errors->has('email') ? 'err' : '' }}"
                >
            </div>
            @error('email')
                <div class="err-msg">{{ $message }}</div>
            @enderror
        </div>

        <button class="go" type="submit">
            <span>Kirim tautan reset</span>
            <i data-lucide="arrow-right" class="arr"></i>
        </button>

        <div class="lg-foot">
            <a href="{{ route('login') }}">&larr; Kembali ke halaman masuk</a>
            <a class="m" href="{{ url('/') }}">Kembali ke situs</a>
        </div>
    </form>
@endsection

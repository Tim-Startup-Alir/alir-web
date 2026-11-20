@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf
        <h2>Selamat datang kembali</h2>
        <p class="sub">Masuk untuk mengelola wilayah, relawan, dan penanganan banjir.</p>

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
                    autocomplete="username"
                    required
                    autofocus
                    class="{{ $errors->has('email') ? 'err' : '' }}"
                >
            </div>
            @error('email')
                <div class="err-msg">{{ $message }}</div>
            @enderror
        </div>

        <div class="fld">
            <label for="password">Kata sandi</label>
            <div class="fi">
                <i data-lucide="lock"></i>
                <input
                    id="password"
                    name="password"
                    type="password"
                    placeholder="Masukkan kata sandi"
                    autocomplete="current-password"
                    required
                    class="{{ $errors->has('password') ? 'err' : '' }}"
                >
                <button type="button" class="eye" data-toggle-password="password" aria-label="Tampilkan sandi">
                    <i data-lucide="eye"></i>
                </button>
            </div>
            @error('password')
                <div class="err-msg">{{ $message }}</div>
            @enderror
        </div>

        <div class="opt">
            <label>
                <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                Ingat saya
            </label>
            <a href="{{ route('password.request') }}">Lupa sandi?</a>
        </div>

        <button class="go" type="submit" dusk="login-submit">
            <span>Masuk</span>
            <i data-lucide="arrow-right" class="arr"></i>
        </button>

        <div class="lg-foot">
            <span>Komunitas baru? <a href="{{ route('register') }}">Daftar di sini</a></span>
            <a class="m" href="{{ url('/') }}">Kembali ke situs</a>
        </div>
    </form>
@endsection

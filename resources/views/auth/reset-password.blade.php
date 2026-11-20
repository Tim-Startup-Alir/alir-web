@extends('layouts.guest')

@section('title', 'Atur Ulang Kata Sandi')

@section('content')
    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <h2>Atur ulang kata sandi</h2>
        <p class="sub">Buat kata sandi baru minimal 8 karakter untuk akun Anda.</p>

        <div class="fld">
            <label for="email">Email</label>
            <div class="fi">
                <i data-lucide="mail"></i>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $email) }}"
                    required
                    class="{{ $errors->has('email') ? 'err' : '' }}"
                >
            </div>
            @error('email')
                <div class="err-msg">{{ $message }}</div>
            @enderror
        </div>

        <div class="fld">
            <label for="password">Kata sandi baru</label>
            <div class="fi">
                <i data-lucide="lock"></i>
                <input
                    id="password"
                    name="password"
                    type="password"
                    placeholder="Minimal 8 karakter"
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

        <div class="fld">
            <label for="password_confirmation">Konfirmasi kata sandi baru</label>
            <div class="fi">
                <i data-lucide="lock"></i>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    placeholder="Ulangi kata sandi baru"
                    required
                >
            </div>
        </div>

        <button class="go" type="submit">
            <span>Simpan kata sandi</span>
            <i data-lucide="arrow-right" class="arr"></i>
        </button>
    </form>
@endsection

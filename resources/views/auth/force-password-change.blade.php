@extends('layouts.guest')

@section('title', 'Wajib Ganti Kata Sandi')

@section('content')
    <form method="POST" action="{{ route('password.force.update') }}" novalidate>
        @csrf
        @method('PUT')

        <h2>Ganti kata sandi awal</h2>
        <p class="sub">Akun Anda masih memakai kata sandi awal. Buat kata sandi baru sebelum mengakses portal.</p>

        @if (session('warning'))
            <div class="mb-4">
                <x-flash-message />
            </div>
        @endif

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
                    autofocus
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
                <button type="button" class="eye" data-toggle-password="password_confirmation" aria-label="Tampilkan sandi">
                    <i data-lucide="eye"></i>
                </button>
            </div>
        </div>

        <button class="go" type="submit" dusk="force-password-submit">
            <span>Simpan &amp; lanjutkan</span>
            <i data-lucide="arrow-right" class="arr"></i>
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm font-bold text-slate-500 hover:text-slate-800">Keluar dari akun ini</button>
    </form>
@endsection

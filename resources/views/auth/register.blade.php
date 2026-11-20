@extends('layouts.register')

@section('title', 'Daftar Komunitas Relawan')

@section('content')
    <form method="POST" action="{{ route('register.store') }}" enctype="multipart/form-data" novalidate>
        @csrf

        <h2>Daftar relawan</h2>
        <p class="sub">Isi data komunitas atau tim Anda. Kolom bertanda <span style="color:var(--red)">*</span> wajib diisi.</p>

        @if ($errors->any())
            <div class="mb-4">
                <x-flash-message />
            </div>
        @endif

        @include('partials.register-fields', ['wilayahAktif' => $wilayahAktif])

        <div class="reg-sec">
            <h3><b>5</b>Akun masuk</h3>
            <div class="grid-alir g2">
                <div class="field">
                    <label for="password">Kata sandi <span class="text-red-600">*</span></label>
                    <input id="password" name="password" type="password" class="input {{ $errors->has('password') ? 'err' : '' }}" autocomplete="new-password" placeholder="Minimal 8 karakter" required>
                    @error('password')<div class="err-msg">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">Ulangi kata sandi <span class="text-red-600">*</span></label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="input" autocomplete="new-password" placeholder="Ketik ulang" required>
                </div>
            </div>
        </div>

        <label class="check items-start mb-4">
            <input type="checkbox" name="setuju_syarat" value="1" @checked(old('setuju_syarat')) required>
            <span class="text-sm text-slate-600">Saya menyatakan data benar dan menyetujui syarat penggunaan serta kebijakan privasi ALIR.</span>
        </label>
        @error('setuju_syarat')<div class="err-msg mb-3">{{ $message }}</div>@enderror

        <button class="go" type="submit" dusk="register-submit">
            <span>Kirim pendaftaran</span>
            <i data-lucide="arrow-right" class="arr"></i>
        </button>

        <div class="lg-foot">
            <span>Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></span>
            <a class="m" href="{{ url('/') }}">Kembali ke situs</a>
        </div>
    </form>
@endsection

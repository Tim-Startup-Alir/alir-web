@extends('layouts.register')

@section('title', 'Perbaiki Data Pendaftaran')

@section('content')
    <form method="POST" action="{{ \Illuminate\Support\Facades\URL::signedRoute('register.perbaikan.update', ['komunitas' => $komunitas->id]) }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')

        <h2>Perbaiki data pendaftaran</h2>
        <p class="sub">Silakan perbaiki data komunitas Anda berdasarkan catatan dari Tim Startup ALIR.</p>

        <div class="flash warn mb-5" role="alert">
            <i data-lucide="triangle-alert" class="w-5 h-5 shrink-0 mt-0.5"></i>
            <div>
                <div class="font-bold">Catatan Penolakan dari Tim Startup ALIR:</div>
                <div class="text-sm mt-1">{{ $komunitas->alasan_tolak_registrasi }}</div>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-4">
                <x-flash-message />
            </div>
        @endif

        @include('partials.register-fields', [
            'komunitas' => $komunitas,
            'poskoUtama' => $poskoUtama,
            'wilayahAktif' => $wilayahAktif,
        ])

        <label class="check items-start mb-4">
            <input type="checkbox" name="setuju_syarat" value="1" @checked(old('setuju_syarat', true)) required>
            <span class="text-sm text-slate-600">Saya menyatakan data perbaikan ini benar dan siap ditinjau ulang oleh Tim Startup ALIR.</span>
        </label>
        @error('setuju_syarat')<div class="err-msg mb-3">{{ $message }}</div>@enderror

        <button class="go" type="submit" dusk="perbaikan-submit">
            <span>Ajukan ulang pendaftaran</span>
            <i data-lucide="arrow-right" class="arr"></i>
        </button>
    </form>
@endsection

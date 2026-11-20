@extends('layouts.admin')

@section('title', 'Ubah Profil Komunitas')

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">PROFIL KOMUNITAS</span>
            <h2>{{ $komunitas->nama }}</h2>
            <p class="muted">Perbarui informasi operasional, kontak PIC, kapasitas, dan posko utama komunitas.</p>
        </div>
        <a href="{{ route('admin.komunitas.index') }}" class="btn btn-ghost btn-sm">Kembali</a>
    </div>

    <div class="card max-w-4xl">
        <form method="POST" action="{{ route('admin.profil.update', $komunitas) }}" class="col gap-5" novalidate>
            @csrf
            @method('PUT')

            @include('partials.profil-form', [
                'komunitas' => $komunitas,
                'poskoUtama' => $poskoUtama,
                'wilayahAktif' => $wilayahAktif,
            ])

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('admin.komunitas.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-coral" dusk="submit-profil">Simpan Profil</button>
            </div>
        </form>
    </div>
@endsection

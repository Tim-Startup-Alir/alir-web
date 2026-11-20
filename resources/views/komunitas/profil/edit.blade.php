@extends('layouts.komunitas')

@section('title', 'Profil & Posko')

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">PORTAL KOMUNITAS</span>
            <h2>Profil &amp; Posko Utama</h2>
            <p class="muted">Perbarui kontak PIC, jumlah personel, armada perahu, dan koordinat posko utama komunitas Anda.</p>
        </div>
    </div>

    <div class="card max-w-4xl">
        <form method="POST" action="{{ route('komunitas.profil.update') }}" class="col gap-5" novalidate>
            @csrf
            @method('PUT')

            @include('partials.profil-form', [
                'komunitas' => $komunitas,
                'poskoUtama' => $poskoUtama,
            ])

            <div class="flex justify-end gap-3 pt-2">
                <button type="submit" class="btn btn-coral" dusk="submit-profil">Simpan Profil</button>
            </div>
        </form>
    </div>
@endsection

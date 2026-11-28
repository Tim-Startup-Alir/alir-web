@extends('layouts.admin')

@section('title', 'Ubah Konten Edukasi')

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">F-08 EDUKASI</span>
            <h2>Ubah Konten Edukasi</h2>
        </div>
        <a href="{{ route('admin.edukasi.index') }}" class="btn btn-ghost btn-sm">Kembali</a>
    </div>

    <div class="card max-w-4xl" style="margin-top:20px">
        <form method="POST" action="{{ route('admin.edukasi.update', $konten) }}" class="col gap-4" novalidate>
            @csrf @method('PUT')
            @include('admin.edukasi._form', ['konten' => $konten])
            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('admin.edukasi.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-coral" dusk="simpan-konten">Simpan Perubahan</button>
            </div>
        </form>
    </div>
@endsection

@extends('layouts.admin')

@section('title', 'Tambah Wilayah')

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">MASTER DATA WILAYAH</span>
            <h2>Tambah Wilayah</h2>
        </div>
        <a href="{{ route('admin.wilayah.index') }}" class="btn btn-ghost btn-sm">Kembali</a>
    </div>

    <div class="card max-w-3xl">
        <form method="POST" action="{{ route('admin.wilayah.store') }}" class="col gap-4" novalidate>
            @csrf
            @include('admin.wilayah._form')
            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('admin.wilayah.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-coral" dusk="submit-wilayah">Simpan Wilayah</button>
            </div>
        </form>
    </div>
@endsection

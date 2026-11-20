@extends('layouts.admin')

@section('title', 'Ubah Jadwal Kesiapsiagaan')

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">KESIAPSIAGAAN</span>
            <h2>Ubah Jadwal Shift</h2>
        </div>
        <a href="{{ route('admin.jadwal.index') }}" class="btn btn-ghost btn-sm">Kembali</a>
    </div>

    <div class="card max-w-4xl">
        <form method="POST" action="{{ route('admin.jadwal.update', $jadwal) }}" class="col gap-4" novalidate>
            @csrf
            @method('PUT')
            @include('admin.jadwal._form', ['jadwal' => $jadwal, 'komunitasList' => $komunitasList])
            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('admin.jadwal.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-coral" dusk="submit-jadwal">Simpan Perubahan</button>
            </div>
        </form>
    </div>
@endsection

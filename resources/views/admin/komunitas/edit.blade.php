@extends('layouts.admin')

@section('title', 'Ubah Master Komunitas')

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">MASTER DATA KOMUNITAS</span>
            <h2>Ubah Master: {{ $komunitas->nama }}</h2>
            <p class="muted">Mengubah identitas master komunitas, akun login, dan kelurahan layanan.</p>
        </div>
        <a href="{{ route('admin.komunitas.index') }}" class="btn btn-ghost btn-sm">Kembali</a>
    </div>

    <div class="card max-w-4xl">
        <form method="POST" action="{{ route('admin.komunitas.update', $komunitas) }}" class="col gap-4" novalidate>
            @csrf
            @method('PUT')

            <div class="grid-alir g2">
                <div class="field">
                    <label for="nama">Nama Komunitas <span class="text-red-600">*</span></label>
                    <input id="nama" name="nama" type="text" class="input {{ $errors->has('nama') ? 'err' : '' }}" value="{{ old('nama', $komunitas->nama) }}" required>
                    @error('nama')<div class="err-msg">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="wilayah_id">Kelurahan Layanan <span class="text-red-600">*</span></label>
                    <select id="wilayah_id" name="wilayah_id" class="input {{ $errors->has('wilayah_id') ? 'err' : '' }}" required>
                        @foreach ($wilayahAktif as $w)
                            <option value="{{ $w->id }}" @selected((string) old('wilayah_id', $komunitas->wilayah_id) === (string) $w->id)>
                                {{ $w->nama_kelurahan }} ({{ $w->kecamatan }})
                            </option>
                        @endforeach
                    </select>
                    @error('wilayah_id')<div class="err-msg">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="email">Email Login <span class="text-red-600">*</span></label>
                    <input id="email" name="email" type="email" class="input {{ $errors->has('email') ? 'err' : '' }}" value="{{ old('email', $komunitas->user->email) }}" required>
                    @error('email')<div class="err-msg">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="nama_pic">Nama PIC <span class="text-red-600">*</span></label>
                    <input id="nama_pic" name="nama_pic" type="text" class="input {{ $errors->has('nama_pic') ? 'err' : '' }}" value="{{ old('nama_pic', $komunitas->nama_pic) }}" required>
                    @error('nama_pic')<div class="err-msg">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="nomor_whatsapp_pic">Nomor WhatsApp PIC <span class="text-red-600">*</span></label>
                    <input id="nomor_whatsapp_pic" name="nomor_whatsapp_pic" type="text" class="input {{ $errors->has('nomor_whatsapp_pic') ? 'err' : '' }}" value="{{ old('nomor_whatsapp_pic', $komunitas->nomor_whatsapp_pic) }}" required>
                    @error('nomor_whatsapp_pic')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="grid-alir g3">
                <div class="field">
                    <label for="total_anggota">Total Anggota <span class="text-red-600">*</span></label>
                    <input id="total_anggota" name="total_anggota" type="number" min="0" class="input {{ $errors->has('total_anggota') ? 'err' : '' }}" value="{{ old('total_anggota', $komunitas->total_anggota) }}" required>
                    @error('total_anggota')<div class="err-msg">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="kapasitas_max">Kapasitas Siap Maks. <span class="text-red-600">*</span></label>
                    <input id="kapasitas_max" name="kapasitas_max" type="number" min="0" class="input {{ $errors->has('kapasitas_max') ? 'err' : '' }}" value="{{ old('kapasitas_max', $komunitas->kapasitas_max) }}" required>
                    @error('kapasitas_max')<div class="err-msg">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="jumlah_perahu">Jumlah Perahu <span class="text-red-600">*</span></label>
                    <input id="jumlah_perahu" name="jumlah_perahu" type="number" min="0" class="input {{ $errors->has('jumlah_perahu') ? 'err' : '' }}" value="{{ old('jumlah_perahu', $komunitas->jumlah_perahu) }}" required>
                    @error('jumlah_perahu')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('admin.komunitas.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-coral" dusk="submit-komunitas">Simpan Perubahan</button>
            </div>
        </form>
    </div>
@endsection

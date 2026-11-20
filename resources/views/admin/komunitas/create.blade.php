@extends('layouts.admin')

@section('title', 'Tambah Komunitas Relawan')

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">MASTER DATA KOMUNITAS</span>
            <h2>Tambah Komunitas Relawan</h2>
            <p class="muted">Membuat akun pengguna, profil komunitas (status disetujui &amp; wajib ganti sandi), serta posko utama sekaligus.</p>
        </div>
        <a href="{{ route('admin.komunitas.index') }}" class="btn btn-ghost btn-sm">Kembali</a>
    </div>

    <div class="card max-w-4xl">
        <form method="POST" action="{{ route('admin.komunitas.store') }}" class="col gap-5" novalidate>
            @csrf

            <h3 class="text-base">1. Identitas &amp; Akun Komunitas</h3>
            <div class="grid-alir g2">
                <div class="field">
                    <label for="nama">Nama Komunitas <span class="text-red-600">*</span></label>
                    <input id="nama" name="nama" type="text" class="input {{ $errors->has('nama') ? 'err' : '' }}" value="{{ old('nama') }}" required>
                    @error('nama')<div class="err-msg">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="wilayah_id">Kelurahan Layanan <span class="text-red-600">*</span></label>
                    <select id="wilayah_id" name="wilayah_id" class="input {{ $errors->has('wilayah_id') ? 'err' : '' }}" required>
                        <option value="">-- Pilih kelurahan aktif --</option>
                        @foreach ($wilayahAktif as $w)
                            <option value="{{ $w->id }}" @selected((string) old('wilayah_id') === (string) $w->id)>
                                {{ $w->nama_kelurahan }} ({{ $w->kecamatan }})
                            </option>
                        @endforeach
                    </select>
                    @error('wilayah_id')<div class="err-msg">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="email">Email Login <span class="text-red-600">*</span></label>
                    <input id="email" name="email" type="email" class="input {{ $errors->has('email') ? 'err' : '' }}" value="{{ old('email') }}" required>
                    @error('email')<div class="err-msg">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="password">Kata Sandi Awal (min. 8 karakter) <span class="text-red-600">*</span></label>
                    <input id="password" name="password" type="password" class="input {{ $errors->has('password') ? 'err' : '' }}" required>
                    @error('password')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr class="border-slate-200">

            <h3 class="text-base">2. Penanggung Jawab &amp; Kapasitas</h3>
            <div class="grid-alir g2">
                <div class="field">
                    <label for="nama_pic">Nama PIC <span class="text-red-600">*</span></label>
                    <input id="nama_pic" name="nama_pic" type="text" class="input {{ $errors->has('nama_pic') ? 'err' : '' }}" value="{{ old('nama_pic') }}" required>
                    @error('nama_pic')<div class="err-msg">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="nomor_whatsapp_pic">Nomor WhatsApp PIC <span class="text-red-600">*</span></label>
                    <input id="nomor_whatsapp_pic" name="nomor_whatsapp_pic" type="text" class="input {{ $errors->has('nomor_whatsapp_pic') ? 'err' : '' }}" value="{{ old('nomor_whatsapp_pic') }}" placeholder="081234567890" required>
                    @error('nomor_whatsapp_pic')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="grid-alir g3">
                <div class="field">
                    <label for="total_anggota">Total Anggota <span class="text-red-600">*</span></label>
                    <input id="total_anggota" name="total_anggota" type="number" min="0" class="input {{ $errors->has('total_anggota') ? 'err' : '' }}" value="{{ old('total_anggota', 10) }}" required>
                    @error('total_anggota')<div class="err-msg">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="kapasitas_max">Kapasitas Siap Maks. <span class="text-red-600">*</span></label>
                    <input id="kapasitas_max" name="kapasitas_max" type="number" min="0" class="input {{ $errors->has('kapasitas_max') ? 'err' : '' }}" value="{{ old('kapasitas_max', 8) }}" required>
                    @error('kapasitas_max')<div class="err-msg">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="jumlah_perahu">Jumlah Perahu <span class="text-red-600">*</span></label>
                    <input id="jumlah_perahu" name="jumlah_perahu" type="number" min="0" class="input {{ $errors->has('jumlah_perahu') ? 'err' : '' }}" value="{{ old('jumlah_perahu', 1) }}" required>
                    @error('jumlah_perahu')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr class="border-slate-200">

            <h3 class="text-base">3. Posko Utama</h3>
            <div class="grid-alir g2">
                <div class="field">
                    <label for="posko_nama">Nama Posko (kosongkan untuk default)</label>
                    <input id="posko_nama" name="posko_nama" type="text" class="input {{ $errors->has('posko_nama') ? 'err' : '' }}" value="{{ old('posko_nama') }}" placeholder="Posko Utama Kelurahan">
                    @error('posko_nama')<div class="err-msg">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="posko_radius_m">Radius Layanan (meter) <span class="text-red-600">*</span></label>
                    <input id="posko_radius_m" name="posko_radius_m" type="number" min="100" class="input {{ $errors->has('posko_radius_m') ? 'err' : '' }}" value="{{ old('posko_radius_m', 1500) }}" required>
                    @error('posko_radius_m')<div class="err-msg">{{ $message }}</div>@enderror
                </div>

                <div class="field md:col-span-2">
                    <label for="posko_alamat">Alamat Posko</label>
                    <input id="posko_alamat" name="posko_alamat" type="text" class="input {{ $errors->has('posko_alamat') ? 'err' : '' }}" value="{{ old('posko_alamat') }}">
                    @error('posko_alamat')<div class="err-msg">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="posko_latitude">Latitude Posko <span class="text-red-600">*</span></label>
                    <input id="posko_latitude" name="posko_latitude" type="text" class="input {{ $errors->has('posko_latitude') ? 'err' : '' }}" value="{{ old('posko_latitude') }}" placeholder="-6.8928121" required>
                    @error('posko_latitude')<div class="err-msg">{{ $message }}</div>@enderror
                </div>

                <div class="field">
                    <label for="posko_longitude">Longitude Posko <span class="text-red-600">*</span></label>
                    <input id="posko_longitude" name="posko_longitude" type="text" class="input {{ $errors->has('posko_longitude') ? 'err' : '' }}" value="{{ old('posko_longitude') }}" placeholder="109.6515333" required>
                    @error('posko_longitude')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200">
                <label class="check">
                    <input type="checkbox" name="pernyataan_luar_cakupan" value="1" @checked(old('pernyataan_luar_cakupan'))>
                    <span class="text-sm font-semibold text-amber-900">
                        Jika posko berada di luar 3 kelurahan pilot, komunitas menyatakan siap menanggung risiko jarak layanan.
                    </span>
                </label>
                @error('pernyataan_luar_cakupan')
                    <div class="err-msg mt-2">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('admin.komunitas.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-coral" dusk="submit-komunitas">Simpan Komunitas</button>
            </div>
        </form>
    </div>
@endsection

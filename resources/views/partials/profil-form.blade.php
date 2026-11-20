@php
    $isAdmin = auth()->user()->isAdmin();
@endphp

<h3 class="text-base">Informasi Komunitas</h3>
<div class="grid-alir g2">
    <div class="field">
        <label for="nama">Nama Komunitas</label>
        @if ($isAdmin)
            <input id="nama" name="nama" type="text" class="input {{ $errors->has('nama') ? 'err' : '' }}" value="{{ old('nama', $komunitas->nama) }}" required>
            @error('nama')<div class="err-msg">{{ $message }}</div>@enderror
        @else
            <input id="nama" type="text" class="input" value="{{ $komunitas->nama }}" disabled>
            <span class="xs muted">Hanya dapat diubah oleh Tim Startup ALIR.</span>
        @endif
    </div>

    <div class="field">
        <label for="wilayah_id">Kelurahan Layanan</label>
        @if ($isAdmin)
            <select id="wilayah_id" name="wilayah_id" class="input {{ $errors->has('wilayah_id') ? 'err' : '' }}" required>
                @foreach ($wilayahAktif as $w)
                    <option value="{{ $w->id }}" @selected((string) old('wilayah_id', $komunitas->wilayah_id) === (string) $w->id)>
                        {{ $w->nama_kelurahan }} ({{ $w->kecamatan }})
                    </option>
                @endforeach
            </select>
            @error('wilayah_id')<div class="err-msg">{{ $message }}</div>@enderror
        @else
            <input id="wilayah_id" type="text" class="input" value="{{ $komunitas->wilayah?->nama_kelurahan }} ({{ $komunitas->wilayah?->kecamatan }})" disabled>
            <span class="xs muted">Hanya dapat diubah oleh Tim Startup ALIR.</span>
        @endif
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

<hr class="border-slate-200">

<div class="flex flex-wrap items-center justify-between gap-2">
    <h3 class="text-base">Posko Utama</h3>
    @if ($poskoUtama && $poskoUtama->diLuarCakupan())
        <span class="tag amber">Di luar cakupan 3 kelurahan pilot</span>
    @elseif ($poskoUtama)
        <span class="tag teal">Di dalam cakupan kelurahan</span>
    @endif
</div>

<div class="grid-alir g2">
    <div class="field">
        <label for="posko_nama">Nama Posko Utama <span class="text-red-600">*</span></label>
        <input id="posko_nama" name="posko_nama" type="text" class="input {{ $errors->has('posko_nama') ? 'err' : '' }}" value="{{ old('posko_nama', $poskoUtama->nama ?? "Posko Utama {$komunitas->wilayah?->nama_kelurahan}") }}" required>
        @error('posko_nama')<div class="err-msg">{{ $message }}</div>@enderror
    </div>

    <div class="field">
        <label for="posko_radius_m">Radius Layanan (meter) <span class="text-red-600">*</span></label>
        <input id="posko_radius_m" name="posko_radius_m" type="number" min="100" class="input {{ $errors->has('posko_radius_m') ? 'err' : '' }}" value="{{ old('posko_radius_m', $poskoUtama->radius_m ?? 1500) }}" required>
        @error('posko_radius_m')<div class="err-msg">{{ $message }}</div>@enderror
    </div>

    <div class="field md:col-span-2">
        <label for="posko_alamat">Alamat Posko</label>
        <input id="posko_alamat" name="posko_alamat" type="text" class="input {{ $errors->has('posko_alamat') ? 'err' : '' }}" value="{{ old('posko_alamat', $poskoUtama->alamat ?? '') }}">
        @error('posko_alamat')<div class="err-msg">{{ $message }}</div>@enderror
    </div>

    <div class="field">
        <label for="posko_latitude">Latitude Posko <span class="text-red-600">*</span></label>
        <input id="posko_latitude" name="posko_latitude" type="text" class="input {{ $errors->has('posko_latitude') ? 'err' : '' }}" value="{{ old('posko_latitude', $poskoUtama->latitude ?? $komunitas->wilayah?->latitude) }}" required>
        @error('posko_latitude')<div class="err-msg">{{ $message }}</div>@enderror
    </div>

    <div class="field">
        <label for="posko_longitude">Longitude Posko <span class="text-red-600">*</span></label>
        <input id="posko_longitude" name="posko_longitude" type="text" class="input {{ $errors->has('posko_longitude') ? 'err' : '' }}" value="{{ old('posko_longitude', $poskoUtama->longitude ?? $komunitas->wilayah?->longitude) }}" required>
        @error('posko_longitude')<div class="err-msg">{{ $message }}</div>@enderror
    </div>
</div>

<div class="p-4 rounded-2xl bg-amber-50 border border-amber-200">
    <label class="check">
        <input type="checkbox" name="pernyataan_luar_cakupan" value="1" @checked(old('pernyataan_luar_cakupan', $poskoUtama?->pernyataan_luar_cakupan_pada !== null))>
        <span class="text-sm font-semibold text-amber-900">
            Jika koordinat posko berada di luar 3 kelurahan pilot, komunitas menyatakan siap menanggung risiko jarak layanan.
        </span>
    </label>
    @error('pernyataan_luar_cakupan')
        <div class="err-msg mt-2">{{ $message }}</div>
    @enderror
</div>

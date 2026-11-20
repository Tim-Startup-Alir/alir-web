@php
    $isEdit = isset($jadwal);
@endphp

<div class="grid-alir g2">
    <div class="field">
        <label for="komunitas_relawan_id">Komunitas Relawan <span class="text-red-600">*</span></label>
        <select id="komunitas_relawan_id" name="komunitas_relawan_id" class="input {{ $errors->has('komunitas_relawan_id') ? 'err' : '' }}" required>
            <option value="">-- Pilih komunitas --</option>
            @foreach ($komunitasList as $k)
                <option value="{{ $k->id }}" @selected((string) old('komunitas_relawan_id', $jadwal->komunitas_relawan_id ?? '') === (string) $k->id)>
                    {{ $k->nama }}
                </option>
            @endforeach
        </select>
        @error('komunitas_relawan_id')<div class="err-msg">{{ $message }}</div>@enderror
    </div>

    <div class="field">
        <label for="hari">Hari Shift Dimulai <span class="text-red-600">*</span></label>
        <select id="hari" name="hari" class="input {{ $errors->has('hari') ? 'err' : '' }}" required>
            @foreach (\App\Models\JadwalKesiapsiagaan::HARI as $h)
                <option value="{{ $h }}" @selected(old('hari', $jadwal->hari ?? 'senin') === $h)>
                    {{ ucfirst($h) }}
                </option>
            @endforeach
        </select>
        @error('hari')<div class="err-msg">{{ $message }}</div>@enderror
    </div>
</div>

<div class="grid-alir g3">
    <div class="field">
        <label for="nama_shift">Nama Shift (opsional)</label>
        <input
            id="nama_shift"
            name="nama_shift"
            type="text"
            class="input {{ $errors->has('nama_shift') ? 'err' : '' }}"
            value="{{ old('nama_shift', $jadwal->nama_shift ?? '') }}"
            placeholder="Contoh: Pagi / Malam"
        >
        @error('nama_shift')<div class="err-msg">{{ $message }}</div>@enderror
    </div>

    <div class="field">
        <label for="jam_mulai">Jam Mulai <span class="text-red-600">*</span></label>
        <input
            id="jam_mulai"
            name="jam_mulai"
            type="time"
            class="input {{ $errors->has('jam_mulai') ? 'err' : '' }}"
            value="{{ old('jam_mulai', $isEdit ? substr($jadwal->jam_mulai, 0, 5) : '08:00') }}"
            required
        >
        @error('jam_mulai')<div class="err-msg">{{ $message }}</div>@enderror
    </div>

    <div class="field">
        <label for="jam_selesai">Jam Selesai <span class="text-red-600">*</span></label>
        <input
            id="jam_selesai"
            name="jam_selesai"
            type="time"
            class="input {{ $errors->has('jam_selesai') ? 'err' : '' }}"
            value="{{ old('jam_selesai', $isEdit ? substr($jadwal->jam_selesai, 0, 5) : '16:00') }}"
            required
        >
        <span class="xs muted">Jika &le; jam mulai, shift berlanjut melewati tengah malam ke hari berikutnya.</span>
        @error('jam_selesai')<div class="err-msg">{{ $message }}</div>@enderror
    </div>
</div>

<div class="grid-alir g3">
    <div class="field">
        <label for="status_ketersediaan">Status Ketersediaan <span class="text-red-600">*</span></label>
        <select id="status_ketersediaan" name="status_ketersediaan" class="input {{ $errors->has('status_ketersediaan') ? 'err' : '' }}" required>
            <option value="tersedia" @selected(old('status_ketersediaan', $jadwal->status_ketersediaan ?? 'tersedia') === 'tersedia')>Tersedia</option>
            <option value="siaga_terbatas" @selected(old('status_ketersediaan', $jadwal->status_ketersediaan ?? '') === 'siaga_terbatas')>Siaga Terbatas</option>
            <option value="tidak_tersedia" @selected(old('status_ketersediaan', $jadwal->status_ketersediaan ?? '') === 'tidak_tersedia')>Tidak Tersedia</option>
        </select>
        @error('status_ketersediaan')<div class="err-msg">{{ $message }}</div>@enderror
    </div>

    <div class="field">
        <label for="nama_pic">Nama PIC Shift <span class="text-red-600">*</span></label>
        <input
            id="nama_pic"
            name="nama_pic"
            type="text"
            class="input {{ $errors->has('nama_pic') ? 'err' : '' }}"
            value="{{ old('nama_pic', $jadwal->nama_pic ?? '') }}"
            required
        >
        @error('nama_pic')<div class="err-msg">{{ $message }}</div>@enderror
    </div>

    <div class="field">
        <label for="nomor_whatsapp_pic">Nomor WhatsApp PIC <span class="text-red-600">*</span></label>
        <input
            id="nomor_whatsapp_pic"
            name="nomor_whatsapp_pic"
            type="text"
            class="input {{ $errors->has('nomor_whatsapp_pic') ? 'err' : '' }}"
            value="{{ old('nomor_whatsapp_pic', $jadwal->nomor_whatsapp_pic ?? '') }}"
            placeholder="081234567890"
            required
        >
        @error('nomor_whatsapp_pic')<div class="err-msg">{{ $message }}</div>@enderror
    </div>
</div>

<div class="field">
    <label for="keterangan">Keterangan (opsional)</label>
    <input
        id="keterangan"
        name="keterangan"
        type="text"
        class="input {{ $errors->has('keterangan') ? 'err' : '' }}"
        value="{{ old('keterangan', $jadwal->keterangan ?? '') }}"
        placeholder="Catatan kesiapsiagaan shift"
    >
    @error('keterangan')<div class="err-msg">{{ $message }}</div>@enderror
</div>

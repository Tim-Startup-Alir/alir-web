@extends('layouts.admin')

@section('title', 'Kelola Komunitas Relawan')
@section('topbar_title', 'Master Data Komunitas')
@section('topbar_subtitle', 'Akun, wilayah layanan, kapasitas, dan status aktif komunitas relawan')

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">MASTER DATA</span>
            <h2>Komunitas Relawan</h2>
            <p class="muted">Buat akun komunitas beserta posko utama, atur status aktif, atau kelola profil operasional.</p>
        </div>
        <a href="{{ route('admin.komunitas.create') }}" class="btn btn-coral" dusk="add-komunitas-btn">
            <i data-lucide="plus"></i>Tambah Komunitas
        </a>
    </div>

    @if ($antreanPendaftaran->isNotEmpty())
        <div class="card">
            <div class="row between mb-4">
                <div>
                    <h3>Antrean Pendaftaran Baru</h3>
                    <p class="xs muted">Pendaftar yang sudah memverifikasi email dan menunggu persetujuan Tim Startup ALIR.</p>
                </div>
                <span class="tag amber">{{ $antreanPendaftaran->count() }} menunggu</span>
            </div>

            <div class="col gap-3">
                @foreach ($antreanPendaftaran as $calon)
                    @php
                        $poskoCalon = $calon->posko->first();
                    @endphp
                    <div class="p-4 rounded-2xl border border-amber-200 bg-amber-50/70 space-y-3" dusk="pendaftaran-card-{{ $calon->id }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="font-bold text-base">{{ $calon->nama }}</div>
                                <div class="xs muted">
                                    Kelurahan {{ $calon->wilayah?->nama_kelurahan ?? '-' }} &middot;
                                    PIC {{ $calon->nama_pic }} ({{ $calon->nomor_whatsapp_pic }}) &middot;
                                    {{ $calon->user?->email }}
                                </div>
                                <div class="xs muted mt-1">
                                    {{ $calon->total_anggota }} anggota (siap {{ $calon->kapasitas_max }}) &middot;
                                    {{ $calon->jumlah_perahu }} perahu
                                    @if ($poskoCalon)
                                        &middot; Posko: {{ $poskoCalon->nama }} ({{ $poskoCalon->latitude }}, {{ $poskoCalon->longitude }})
                                        @if ($poskoCalon->diLuarCakupan())
                                            <span class="tag amber ml-1">Di luar cakupan</span>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                @if ($calon->dokumen_pendukung_path)
                                    <a href="{{ route('admin.komunitas.dokumen', $calon) }}" target="_blank" class="btn btn-ghost btn-sm" dusk="dokumen-komunitas-{{ $calon->id }}">
                                        <i data-lucide="file-text"></i>Dokumen
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('admin.komunitas.setujui', $calon) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-teal btn-sm" dusk="setujui-komunitas-{{ $calon->id }}">
                                        Setujui
                                    </button>
                                </form>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.komunitas.tolak', $calon) }}" class="flex flex-wrap items-center gap-2 pt-2 border-t border-amber-200/80">
                            @csrf
                            <input
                                type="text"
                                name="alasan_tolak_registrasi"
                                class="input flex-1 min-w-[240px]"
                                style="height:38px"
                                placeholder="Tulis alasan penolakan / catatan perbaikan..."
                                required
                            >
                            <button type="submit" class="btn btn-red btn-sm" dusk="tolak-komunitas-{{ $calon->id }}">
                                Tolak &amp; Kirim Perbaikan
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card">
        <form method="GET" action="{{ route('admin.komunitas.index') }}" class="flex flex-wrap items-center gap-3 mb-5">
            <input
                type="text"
                name="q"
                value="{{ $search }}"
                class="input max-w-sm"
                placeholder="Cari nama komunitas, PIC, atau email..."
            >
            <select name="status" class="input max-w-[180px]">
                <option value="">Semua status</option>
                <option value="aktif" @selected($status === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected($status === 'nonaktif')>Nonaktif</option>
            </select>
            <button type="submit" class="btn btn-navy btn-sm">Filter</button>
            @if ($search !== '' || $status !== '')
                <a href="{{ route('admin.komunitas.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
        </form>

        @if ($komunitasList->isEmpty())
            <div class="empty">Belum ada data komunitas relawan yang cocok.</div>
        @else
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Komunitas &amp; Akun</th>
                            <th>Kelurahan Layanan</th>
                            <th>PIC &amp; WhatsApp</th>
                            <th>Kapasitas &amp; Perahu</th>
                            <th>Posko Utama</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($komunitasList as $k)
                            @php
                                $posko = $k->posko->first();
                                $aktif = $k->user?->is_active ?? false;
                            @endphp
                            <tr dusk="komunitas-row-{{ $k->id }}">
                                <td>
                                    <div class="font-bold">{{ $k->nama }}</div>
                                    <div class="xs muted">{{ $k->user?->email }}</div>
                                </td>
                                <td>
                                    <div class="font-semibold">{{ $k->wilayah?->nama_kelurahan ?? '-' }}</div>
                                    <div class="xs muted">{{ $k->wilayah?->kecamatan }}</div>
                                </td>
                                <td>
                                    <div class="small font-semibold">{{ $k->nama_pic }}</div>
                                    <div class="xs muted mono">{{ $k->nomor_whatsapp_pic }}</div>
                                </td>
                                <td class="small">
                                    <div>{{ $k->kapasitas_max }}/{{ $k->total_anggota }} personel</div>
                                    <div class="xs muted">{{ $k->jumlah_perahu }} perahu</div>
                                </td>
                                <td class="small">
                                    @if ($posko)
                                        <div class="font-semibold">{{ $posko->nama }}</div>
                                        <div class="xs muted">Radius {{ $posko->radius_m }} m</div>
                                    @else
                                        <span class="xs muted">Belum ada posko</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="tag {{ $aktif ? 'teal' : 'red' }}">
                                        {{ $aktif ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        @if (Route::has('admin.profil.edit'))
                                            <a href="{{ route('admin.profil.edit', $k) }}" class="btn btn-ghost btn-sm" dusk="profil-komunitas-{{ $k->id }}">
                                                Profil &amp; Posko
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.komunitas.edit', $k) }}" class="btn btn-ghost btn-sm" dusk="edit-komunitas-{{ $k->id }}">
                                            Master
                                        </a>
                                        <form method="POST" action="{{ route('admin.komunitas.toggle-status', $k) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-ghost btn-sm" dusk="toggle-komunitas-{{ $k->id }}">
                                                {{ $aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.komunitas.destroy', $k) }}" onsubmit="return confirm('Hapus komunitas {{ $k->nama }} beserta akunnya?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-red btn-sm" dusk="delete-komunitas-{{ $k->id }}">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $komunitasList->links() }}
            </div>
        @endif
    </div>

    @if ($pendaftaranDitolak->isNotEmpty())
        <div class="card">
            <div class="row between mb-3">
                <h3>Pendaftaran Ditolak (Menunggu Perbaikan)</h3>
                <span class="tag red">{{ $pendaftaranDitolak->count() }}</span>
            </div>
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Komunitas &amp; Email</th>
                            <th>Kelurahan</th>
                            <th>Alasan Penolakan</th>
                            <th>Ditolak Pada</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendaftaranDitolak as $ditolak)
                            <tr>
                                <td>
                                    <div class="font-bold">{{ $ditolak->nama }}</div>
                                    <div class="xs muted">{{ $ditolak->user?->email }}</div>
                                </td>
                                <td>{{ $ditolak->wilayah?->nama_kelurahan ?? '-' }}</td>
                                <td class="small">{{ $ditolak->alasan_tolak_registrasi }}</td>
                                <td class="xs muted">{{ $ditolak->diverifikasi_pada?->format('d M Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection

@extends('layouts.admin')

@section('title', 'Kelola Wilayah')
@section('topbar_title', 'Master Data Wilayah')
@section('topbar_subtitle', 'Kelurahan cakupan operasional ALIR di Kota Pekalongan')

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">MASTER DATA</span>
            <h2>Wilayah Cakupan</h2>
            <p class="muted">Tambah, ubah, nonaktifkan, atau hapus kelurahan cakupan pelaporan dan layanan.</p>
        </div>
        <a href="{{ route('admin.wilayah.create') }}" class="btn btn-coral" dusk="add-wilayah-btn">
            <i data-lucide="plus"></i>Tambah Wilayah
        </a>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('admin.wilayah.index') }}" class="flex flex-wrap items-center gap-3 mb-5">
            <input
                type="text"
                name="q"
                value="{{ $search }}"
                class="input max-w-sm"
                placeholder="Cari kelurahan, kecamatan, atau kode ADM4..."
            >
            <button type="submit" class="btn btn-navy btn-sm">Cari</button>
            @if ($search !== '')
                <a href="{{ route('admin.wilayah.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
        </form>

        @if ($wilayahList->isEmpty())
            <div class="empty">Belum ada data wilayah yang cocok.</div>
        @else
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Kode ADM4</th>
                            <th>Kelurahan &amp; Kecamatan</th>
                            <th>Koordinat Pusat</th>
                            <th>Cakupan (FR-24)</th>
                            <th>Pemakaian</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($wilayahList as $w)
                            <tr dusk="wilayah-row-{{ $w->id }}">
                                <td class="mono font-bold">{{ $w->kode_adm4 }}</td>
                                <td>
                                    <div class="font-bold">{{ $w->nama_kelurahan }}</div>
                                    <div class="xs muted">{{ $w->kecamatan }}</div>
                                </td>
                                <td class="mono small">
                                    {{ $w->latitude }}, {{ $w->longitude }}
                                </td>
                                <td>
                                    @if (! empty($w->batas_wilayah))
                                        <span class="tag teal">Poligon GeoJSON</span>
                                    @else
                                        <span class="tag water">Radius {{ $w->radiusCakupan() }} m</span>
                                    @endif
                                </td>
                                <td class="small">
                                    <div>{{ $w->komunitas_relawan_count }} komunitas</div>
                                    <div class="xs muted">{{ $w->laporan_banjir_count }} laporan</div>
                                </td>
                                <td>
                                    <span class="tag {{ $w->is_aktif ? 'teal' : 'red' }}">
                                        {{ $w->is_aktif ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.wilayah.edit', $w) }}" class="btn btn-ghost btn-sm" dusk="edit-wilayah-{{ $w->id }}">
                                            Ubah
                                        </a>
                                        <form method="POST" action="{{ route('admin.wilayah.toggle-status', $w) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-ghost btn-sm" dusk="toggle-wilayah-{{ $w->id }}">
                                                {{ $w->is_aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.wilayah.destroy', $w) }}" onsubmit="return confirm('Hapus wilayah {{ $w->nama_kelurahan }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-red btn-sm" dusk="delete-wilayah-{{ $w->id }}">
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
                {{ $wilayahList->links() }}
            </div>
        @endif
    </div>
@endsection

@extends('layouts.admin')

@section('title', 'Konten Edukasi')
@section('topbar_title', 'Konten Edukasi')
@section('topbar_subtitle', 'Kelola artikel, infografis, dan video edukasi kebencanaan')

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">F-08 EDUKASI</span>
            <h2>Konten Edukasi</h2>
            <p class="muted">Hanya konten berstatus <b>Terbit</b> yang tampil di halaman Edukasi publik.</p>
        </div>
        <a href="{{ route('admin.edukasi.create') }}" class="btn btn-coral" dusk="tambah-konten">
            <i data-lucide="plus"></i>Tambah Konten
        </a>
    </div>

    <div class="card" style="margin-top:20px">
        <form method="GET" action="{{ route('admin.edukasi.index') }}" class="kn-filter">
            <input type="search" name="q" value="{{ $q }}" class="input" placeholder="Cari judul..." aria-label="Cari judul">
            <select name="kategori" class="input" aria-label="Kategori">
                <option value="">Semua kategori</option>
                @foreach (\App\Models\KontenEdukasi::LABEL_KATEGORI as $k => $label)
                    <option value="{{ $k }}" @selected($kategori === $k)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="input" aria-label="Status">
                <option value="">Semua status</option>
                <option value="terbit" @selected($status === 'terbit')>Terbit</option>
                <option value="draft" @selected($status === 'draft')>Draft</option>
            </select>
            <button type="submit" class="btn btn-navy btn-sm">Filter</button>
            @if ($q !== '' || $kategori !== '' || $status !== '')
                <a href="{{ route('admin.edukasi.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
        </form>

        @if ($daftar->isEmpty())
            <div class="empty">Belum ada konten edukasi yang sesuai.</div>
        @else
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                        <tr><th>Judul</th><th>Kategori</th><th>Tipe</th><th>Status</th><th>Diperbarui</th><th class="text-right">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($daftar as $k)
                            <tr dusk="konten-row-{{ $k->id }}">
                                <td><div class="font-bold">{{ $k->judul }}</div><div class="xs muted mono">/edukasi/{{ $k->slug }}</div></td>
                                <td><span class="tag water">{{ $k->labelKategori() }}</span></td>
                                <td class="small">{{ $k->labelTipe() }}</td>
                                <td><span class="tag {{ $k->isTerbit() ? 'teal' : 'amber' }}">{{ $k->isTerbit() ? 'Terbit' : 'Draft' }}</span></td>
                                <td class="small">{{ $k->updated_at->translatedFormat('d M Y') }}</td>
                                <td class="text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        @if ($k->isTerbit())
                                            <a href="{{ route('edukasi.show', $k) }}" class="btn btn-ghost btn-sm" target="_blank" rel="noopener">Lihat</a>
                                        @endif
                                        <form method="POST" action="{{ route('admin.edukasi.status', $k) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $k->isTerbit() ? 'draft' : 'terbit' }}">
                                            <button type="submit" class="btn btn-ghost btn-sm">{{ $k->isTerbit() ? 'Tarik' : 'Terbitkan' }}</button>
                                        </form>
                                        <a href="{{ route('admin.edukasi.edit', $k) }}" class="btn btn-ghost btn-sm" dusk="edit-konten-{{ $k->id }}">Ubah</a>
                                        <form method="POST" action="{{ route('admin.edukasi.destroy', $k) }}" onsubmit="return confirm('Hapus konten &quot;{{ e($k->judul) }}&quot;?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-red btn-sm" dusk="hapus-konten-{{ $k->id }}">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $daftar->links() }}</div>
        @endif
    </div>
@endsection

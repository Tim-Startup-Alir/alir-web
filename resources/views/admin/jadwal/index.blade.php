@extends('layouts.admin')

@section('title', 'Jadwal Kesiapsiagaan')
@section('topbar_title', 'Jadwal Kesiapsiagaan')
@section('topbar_subtitle', 'Pengaturan shift mingguan komunitas relawan untuk rekomendasi penugasan')

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">KESIAPSIAGAAN</span>
            <h2>Jadwal Kesiapsiagaan</h2>
            <p class="muted">Shift mingguan berulang termasuk dukungan shift lintas tengah malam.</p>
        </div>
        <a href="{{ route('admin.jadwal.create') }}" class="btn btn-coral" dusk="add-jadwal-btn">
            <i data-lucide="plus"></i>Tambah Jadwal
        </a>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('admin.jadwal.index') }}" class="flex flex-wrap items-center gap-3 mb-5">
            <select name="komunitas_relawan_id" class="input max-w-xs">
                <option value="">Semua komunitas</option>
                @foreach ($komunitasList as $k)
                    <option value="{{ $k->id }}" @selected($filterKomunitas === $k->id)>{{ $k->nama }}</option>
                @endforeach
            </select>

            <select name="hari" class="input max-w-[160px]">
                <option value="">Semua hari</option>
                @foreach (\App\Models\JadwalKesiapsiagaan::HARI as $h)
                    <option value="{{ $h }}" @selected($filterHari === $h)>{{ ucfirst($h) }}</option>
                @endforeach
            </select>

            <select name="status_ketersediaan" class="input max-w-[180px]">
                <option value="">Semua status</option>
                <option value="tersedia" @selected($filterStatus === 'tersedia')>Tersedia</option>
                <option value="siaga_terbatas" @selected($filterStatus === 'siaga_terbatas')>Siaga Terbatas</option>
                <option value="tidak_tersedia" @selected($filterStatus === 'tidak_tersedia')>Tidak Tersedia</option>
            </select>

            <button type="submit" class="btn btn-navy btn-sm">Filter</button>
            @if ($filterKomunitas || $filterHari !== '' || $filterStatus !== '')
                <a href="{{ route('admin.jadwal.index') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
        </form>

        @if ($jadwalList->isEmpty())
            <div class="empty">Belum ada jadwal kesiapsiagaan yang sesuai.</div>
        @else
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Komunitas</th>
                            <th>Hari &amp; Shift</th>
                            <th>Jam</th>
                            <th>PIC Shift</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($jadwalList as $j)
                            <tr dusk="jadwal-row-{{ $j->id }}">
                                <td>
                                    <div class="font-bold">{{ $j->komunitasRelawan?->nama }}</div>
                                </td>
                                <td>
                                    <div class="font-semibold">{{ ucfirst($j->hari) }}</div>
                                    <div class="xs muted">{{ $j->label }}</div>
                                </td>
                                <td class="mono small">
                                    {{ substr($j->jam_mulai, 0, 5) }} - {{ substr($j->jam_selesai, 0, 5) }}
                                    @if ($j->isLintasTengahMalam())
                                        <span class="tag water ml-1">Lintas hari</span>
                                    @endif
                                </td>
                                <td class="small">
                                    <div class="font-semibold">{{ $j->nama_pic }}</div>
                                    <div class="xs muted mono">{{ $j->nomor_whatsapp_pic }}</div>
                                </td>
                                <td>
                                    @php
                                        $badge = match ($j->status_ketersediaan) {
                                            'tersedia' => ['teal', 'Tersedia'],
                                            'siaga_terbatas' => ['amber', 'Siaga Terbatas'],
                                            default => ['red', 'Tidak Tersedia'],
                                        };
                                    @endphp
                                    <span class="tag {{ $badge[0] }}">{{ $badge[1] }}</span>
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.jadwal.edit', $j) }}" class="btn btn-ghost btn-sm" dusk="edit-jadwal-{{ $j->id }}">
                                            Ubah
                                        </a>
                                        <form method="POST" action="{{ route('admin.jadwal.destroy', $j) }}" onsubmit="return confirm('Hapus jadwal shift ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-red btn-sm" dusk="delete-jadwal-{{ $j->id }}">
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
                {{ $jadwalList->links() }}
            </div>
        @endif
    </div>
@endsection

@extends('layouts.komunitas')

@section('title', 'Jadwal Kesiapsiagaan')

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">PORTAL KOMUNITAS</span>
            <h2>Jadwal Kesiapsiagaan</h2>
            <p class="muted">Daftar shift mingguan komunitas Anda (dikelola oleh Tim Startup ALIR).</p>
        </div>
    </div>

    <div class="card">
        @if ($jadwalList->isEmpty())
            <div class="empty">Belum ada jadwal kesiapsiagaan yang terdaftar untuk komunitas Anda.</div>
        @else
            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Hari</th>
                            <th>Shift</th>
                            <th>Jam</th>
                            <th>PIC Shift</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($jadwalList as $j)
                            <tr>
                                <td class="font-bold">{{ ucfirst($j->hari) }}</td>
                                <td>{{ $j->label }}</td>
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

@extends('layouts.admin')

@section('title', 'Cari Kode Laporan')
@section('topbar_title', 'Cari Kode Laporan')
@section('topbar_subtitle', 'Temukan laporan banjir dari kode ALR yang disebutkan pelapor')

@php
    $warnaSeverity = ['ringan' => 'teal', 'sedang' => 'amber', 'berat' => 'red'];
    $warnaStatus = [
        'menunggu_verifikasi' => 'amber', 'terverifikasi' => 'water', 'ditugaskan' => 'navy',
        'ditangani' => 'navy', 'selesai' => 'teal', 'ditolak' => 'red', 'digabung' => '',
    ];
@endphp

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">LAPORAN</span>
            <h2>Cari Kode Laporan</h2>
            <p class="muted">Pelapor menerima kode unik (contoh <span class="mono">ALR-7K2M9P</span>) setelah mengirim laporan. Masukkan kode tersebut untuk melihat detailnya.</p>
        </div>
    </div>

    <div class="card mb-5">
        <form method="GET" action="{{ route('admin.laporan.cari') }}" class="flex flex-wrap items-center gap-3" dusk="form-cari-laporan">
            <input type="text" name="kode" value="{{ $input }}" class="input mono max-w-xs uppercase"
                   placeholder="ALR-XXXXXX" maxlength="30" autocomplete="off" autofocus dusk="input-kode">
            <button type="submit" class="btn btn-navy btn-sm" dusk="btn-cari-kode"><i data-lucide="search"></i>Cari</button>
            @if ($input !== '')
                <a href="{{ route('admin.laporan.cari') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif
        </form>
        @error('kode')<p class="err-msg mt-2">{{ $message }}</p>@enderror
    </div>

    @if ($kode !== '')
        @if ($laporan)
            <div class="card mb-5" dusk="hasil-laporan">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div>
                        <div class="xs muted">Kode laporan</div>
                        <div class="mono font-bold text-2xl">{{ $laporan->kode_pelacakan }}</div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="tag {{ $warnaStatus[$laporan->status] ?? '' }}">{{ $statusLabel }}</span>
                        <span class="tag {{ $warnaSeverity[$laporan->severity] ?? '' }}">Keparahan {{ ucfirst($laporan->severity) }}</span>
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-[1fr_260px]">
                    <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 text-sm">
                        <div><dt class="xs muted">Kelurahan</dt><dd class="font-bold">{{ $laporan->wilayah?->nama_kelurahan ?? '-' }}</dd><dd class="xs muted">{{ $laporan->wilayah?->kecamatan }}</dd></div>
                        <div><dt class="xs muted">Dilaporkan</dt><dd class="font-bold">{{ $laporan->created_at?->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }}</dd><dd class="xs muted">{{ $laporan->created_at?->diffForHumans() }}</dd></div>
                        <div class="sm:col-span-2"><dt class="xs muted">Alamat singkat</dt><dd>{{ $laporan->alamat_singkat }}</dd></div>
                        <div><dt class="xs muted">Perkiraan warga terdampak</dt><dd class="font-bold">{{ number_format($laporan->jumlah_warga, 0, ',', '.') }} orang</dd></div>
                        <div><dt class="xs muted">Koordinat ({{ $laporan->sumber_lokasi === 'gps' ? 'GPS' : 'pin peta' }})</dt><dd class="mono">{{ $laporan->latitude }}, {{ $laporan->longitude }}</dd></div>
                        <div><dt class="xs muted">Pelapor</dt><dd>{{ $laporan->nama_pelapor ?: 'Tanpa nama' }}</dd></div>
                        <div>
                            <dt class="xs muted">WhatsApp pelapor</dt>
                            <dd class="mono">{{ $laporan->nomor_wa_pelapor }}</dd>
                            <dd class="xs muted">{{ $laporan->setuju_dihubungi ? 'Setuju dihubungi' : 'Belum menyetujui dihubungi' }}</dd>
                        </div>
                        @if ($laporan->catatan)
                            <div class="sm:col-span-2"><dt class="xs muted">Catatan pelapor</dt><dd>{{ $laporan->catatan }}</dd></div>
                        @endif
                    </dl>

                    <div>
                        @forelse ($laporan->foto as $foto)
                            <a href="{{ route('admin.laporan.foto', [$laporan, $foto]) }}" target="_blank" rel="noopener">
                                <img src="{{ route('admin.laporan.foto', [$laporan, $foto]) }}" alt="Foto laporan {{ $laporan->kode_pelacakan }}"
                                     class="w-full rounded-xl object-cover aspect-[4/3] bg-slate-100" loading="lazy">
                            </a>
                        @empty
                            <div class="empty">Tidak ada foto.</div>
                        @endforelse
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 mt-5">
                    <a href="{{ route('admin.peta-laporan.index', ['laporan' => $laporan->id]) }}" class="btn btn-coral btn-sm" dusk="lihat-di-peta">
                        <i data-lucide="map"></i>Lihat di Peta Laporan
                    </a>
                </div>
            </div>
        @else
            <div class="card mb-5">
                <div class="empty" dusk="laporan-tidak-ditemukan">Laporan dengan kode <b class="mono">{{ $kode }}</b> tidak ditemukan. Periksa kembali kode dari pelapor.</div>
            </div>
        @endif
    @endif

    <div class="grid gap-5 lg:grid-cols-2">
        <div class="card">
            <div class="row between mb-3">
                <h3>Notifikasi Laporan Baru</h3>
                <span class="tag">{{ $notifikasi->count() }} belum dibaca</span>
            </div>
            @if ($notifikasi->isEmpty())
                <div class="empty">Tidak ada notifikasi laporan baru.</div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($notifikasi as $n)
                        <a href="{{ route('admin.laporan.cari', ['kode' => $n->data['kode_pelacakan'] ?? '']) }}" class="py-2.5 flex items-center justify-between gap-3 text-sm hover:text-[var(--water-deep)]">
                            <span>{{ $n->data['pesan'] ?? '-' }}</span>
                            <span class="xs muted whitespace-nowrap">{{ $n->created_at?->diffForHumans() }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card">
            <div class="row between mb-3">
                <h3>Laporan Terbaru</h3>
                <span class="tag">{{ $terbaru->count() }}</span>
            </div>
            @if ($terbaru->isEmpty())
                <div class="empty">Belum ada laporan masuk.</div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($terbaru as $item)
                        <a href="{{ route('admin.laporan.cari', ['kode' => $item->kode_pelacakan]) }}" class="py-2.5 flex flex-wrap items-center justify-between gap-2 text-sm">
                            <span class="flex items-center gap-2">
                                <b class="mono">{{ $item->kode_pelacakan }}</b>
                                <span class="muted">{{ $item->wilayah?->nama_kelurahan }}</span>
                            </span>
                            <span class="flex items-center gap-2">
                                <span class="tag {{ $warnaSeverity[$item->severity] ?? '' }}">{{ ucfirst($item->severity) }}</span>
                                <span class="tag {{ $warnaStatus[$item->status] ?? '' }}">{{ \App\Enums\StatusLaporan::labelDari($item->status) }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

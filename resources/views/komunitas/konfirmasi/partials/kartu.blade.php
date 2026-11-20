<article class="konfirmasi-card" dusk="konfirmasi-item-{{ $laporan->id }}">
    <div>
        <h4>{{ $laporan->alamat_singkat }}</h4>
        <p>{{ $laporan->wilayah?->nama_kelurahan ?? 'Wilayah tidak tersedia' }} · {{ ucfirst($laporan->severity) }} · {{ $laporan->jumlah_warga }} warga</p>
        <p>{{ $laporan->created_at?->format('d M Y H:i') }}</p>
        @if ($laporan->jarak_posko_m !== null)
            <p>Jarak posko terdekat: {{ number_format($laporan->jarak_posko_m, 0, ',', '.') }} m</p>
        @endif
    </div>
    <div class="konfirmasi-actions">
        <x-badge-kepercayaan :level="$laporan->tingkat_kepercayaan" />
        <a class="btn btn-primary" href="{{ route('komunitas.konfirmasi.show', $laporan) }}" dusk="konfirmasi-buka-{{ $laporan->id }}">Lihat dan konfirmasi</a>
    </div>
</article>

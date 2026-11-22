@php
    $community = $candidate['komunitas'];
    $canAssign = $candidate['bisa_ditugaskan'];
    $best = $canAssign && (int) $community->id === $bestCandidateId;
    [$availabilityLabel, $availabilityClass] = match (true) {
        $candidate['sisa_personil'] < 1 => ['Penuh', 'red'],
        in_array($candidate['status_jadwal'], ['diluar_jam', 'belum_siaga'], true) => [
            $candidate['status_jadwal'] === 'diluar_jam' ? 'Di luar jam' : 'Belum siaga',
            'navy',
        ],
        $candidate['status_jadwal'] === 'siaga_terbatas' => ['Siaga terbatas', 'amber'],
        $candidate['jumlah_penugasan_aktif'] > 0 => ['Sibuk', 'amber'],
        default => ['Siap sekarang', 'teal'],
    };
    $dialogId = 'assign-dialog-'.$laporan->id.'-'.$community->id;
    $jarak = $candidate['jarak_km'];
@endphp
<div class="candidate {{ $canAssign ? '' : 'disabled' }} {{ $best ? 'best' : '' }}">
    <div class="candidate-details">
        <div class="candidate-main">
            <div class="candidate-name">{{ $community->nama }}</div>
            <div class="candidate-meta">
                @if ($candidate['posko'])
                    <span>{{ $candidate['posko']->nama ?? 'Posko komunitas' }}</span>
                @endif
                @if ($candidate['wilayah_posko'])
                    <span>{{ str_replace('Pasirkratonkramat','Pasirkraton Kramat',$candidate['wilayah_posko']->nama_kelurahan) }}</span>
                @endif
                <span>
                    {{ $jarak === null
                        ? 'Jarak tidak tersedia'
                        : ($jarak < 1 ? number_format($jarak * 1000, 0).' m' : number_format($jarak, 1).' km') }}
                </span>
                @if ($candidate['eta_menit'] !== null)<span>ETA {{ $candidate['eta_menit'] }} mnt</span>@endif
                <span>sisa {{ $candidate['sisa_personil'] }}/{{ $candidate['kapasitas_max'] }} personel</span>
                <span>sisa {{ $candidate['sisa_perahu'] }}/{{ $candidate['jumlah_perahu'] }} perahu</span>
                @if ($candidate['jumlah_penugasan_aktif'] > 0)
                    <span>{{ $candidate['jumlah_penugasan_aktif'] }} tugas aktif</span>
                @endif
                <span>{{ $candidate['label_jadwal'] }}</span>
            </div>
        </div>
        <div class="candidate-status">
            <span class="tag teal">Aktif &amp; terverifikasi</span>
            <span class="tag {{ $availabilityClass }}">{{ $availabilityLabel }}</span>
            @if ($candidate['wilayah_sesuai'])<span class="tag navy">Sesuai wilayah layanan</span>@endif
            @if ($candidate['sudah_ditugaskan'])<span class="tag navy">Sudah ditugaskan</span>@endif
            @if ($best)<span class="tag teal">Rekomendasi terbaik</span>@endif
        </div>
        @if (! $canAssign)
            <div class="candidate-reason">{{ $candidate['alasan_tidak_bisa'] }}</div>
        @endif
    </div>
    <div class="candidate-side">
        <span class="candidate-score">Skor {{ number_format(round($candidate['skor_rekomendasi']), 0) }}/100</span>
        @if ($candidate['skor_komponen'])
            <div class="candidate-meta">
                Jarak {{ number_format($candidate['skor_komponen']['jarak'],1) }} ·
                Kapasitas {{ number_format($candidate['skor_komponen']['kapasitas'],1) }} ·
                Wilayah {{ number_format($candidate['skor_komponen']['wilayah'],1) }} ·
                Siaga {{ number_format($candidate['skor_komponen']['jadwal'],1) }}
            </div>
        @endif
        @if ($canAssign)
            <button type="button" class="btn btn-coral btn-sm candidate-actions" data-select-candidate data-dialog-id="{{ $dialogId }}">Tugaskan</button>
        @endif
    </div>
    @if ($canAssign)
        <dialog class="assign-dialog" id="{{ $dialogId }}" aria-labelledby="{{ $dialogId }}-title">
            <div class="assign-dialog-content">
                <div class="assign-dialog-head">
                    <h3 id="{{ $dialogId }}-title">Konfirmasi penugasan</h3>
                    <button type="button" class="dialog-close" data-close-dialog aria-label="Tutup">&times;</button>
                </div>
                <div class="assign-dialog-summary">
                    <p><strong>Laporan:</strong> P-{{ $laporan->kode_pelacakan }} &middot; {{ $laporan->wilayah?->nama_kelurahan ?? 'Wilayah tidak diketahui' }}</p>
                    <p><strong>Jumlah warga:</strong> {{ $laporan->jumlah_warga }}</p>
                    <p><strong>Komunitas:</strong> {{ $community->nama }}</p>
                </div>
                <form method="POST" action="{{ route('admin.penugasan.store', $laporan) }}" class="assign-dialog-form" data-submit-state>
                    @csrf
                    <input type="hidden" name="komunitas_relawan_id" value="{{ $community->id }}">
                    <label>Personel (maks. {{ $candidate['sisa_personil'] }})
                        <input type="number" name="jumlah_personil_tugas" min="1" max="{{ $candidate['sisa_personil'] }}" required>
                    </label>
                    <label>Perahu (maks. {{ $candidate['sisa_perahu'] }})
                        <input type="number" name="armada_perahu_tugas" min="0" max="{{ $candidate['sisa_perahu'] }}" value="0">
                    </label>
                    <label class="assign-dialog-note">Catatan (opsional)
                        <textarea name="catatan" maxlength="1000"></textarea>
                    </label>
                    <div class="assign-dialog-actions">
                        <button type="button" class="btn btn-navy btn-sm" data-close-dialog>Batal</button>
                        <button type="submit" class="btn btn-coral btn-sm">Konfirmasi penugasan</button>
                    </div>
                </form>
            </div>
        </dialog>
    @endif
</div>

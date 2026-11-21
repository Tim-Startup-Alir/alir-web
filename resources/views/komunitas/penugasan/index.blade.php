@extends('layouts.komunitas')

@section('title', 'Penugasan')
@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">OPERASI LAPANGAN</span>
            <h2>Penugasan</h2>
            <p class="muted">Tinjau tugas baru dan pantau perkembangan penugasan komunitas.</p>
        </div>
    </div>

    @if ($penugasan->isEmpty())
        <div class="empty">Belum ada penugasan untuk komunitas Anda.</div>
    @else
        <div class="grid-alir g2">
            @foreach ($penugasan as $tugas)
                <article class="card" dusk="penugasan-{{ $tugas->id }}">
                    <div class="row between items-start gap-3">
                        <div>
                            <span class="kicker">P-{{ str_pad((string) $tugas->laporan_banjir_id, 5, '0', STR_PAD_LEFT) }}</span>
                            <h3 class="mt-1">{{ $tugas->laporanBanjir?->alamat_singkat ?? 'Lokasi laporan' }}</h3>
                            <p class="xs muted">
                                {{ $tugas->laporanBanjir?->wilayah?->nama_kelurahan ?? 'Wilayah tidak diketahui' }}
                                &middot; {{ $tugas->created_at?->translatedFormat('d M Y, H:i') }}
                            </p>
                        </div>
                        <span class="tag {{ $tugas->stage === 'menunggu' ? 'amber' : (in_array($tugas->stage, ['diterima', 'menuju_lokasi', 'ditangani'], true) ? 'teal' : 'navy') }}">
                            {{ ucfirst(str_replace('_', ' ', $tugas->stage)) }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 mt-3 text-sm">
                        <span>{{ $tugas->jumlah_personil_tugas }} personel</span>
                        <span>{{ $tugas->armada_perahu_tugas }} perahu</span>
                        @if ($tugas->jarak_km !== null)<span>{{ number_format($tugas->jarak_km, 2) }} km dari posko</span>@endif
                    </div>
                    @if ($tugas->catatan)
                        <div class="mt-3 p-3 rounded-xl bg-slate-50 text-sm">{{ $tugas->catatan }}</div>
                    @endif
                    @if ($tugas->stage === 'menunggu')
                        <div class="flex flex-wrap gap-2 mt-4">
                            <form method="POST" action="{{ route('komunitas.penugasan.respon', $tugas) }}" data-submit-state>
                                @csrf
                                <input type="hidden" name="stage" value="diterima">
                                <button class="btn btn-teal btn-sm" type="submit">Terima tugas</button>
                            </form>
                            <details>
                                <summary class="btn btn-ghost btn-sm cursor-pointer">Tolak</summary>
                                <form method="POST" action="{{ route('komunitas.penugasan.respon', $tugas) }}" class="mt-3 flex flex-wrap gap-2" data-submit-state>
                                    @csrf
                                    <input type="hidden" name="stage" value="ditolak">
                                    <input class="input min-w-[220px]" name="alasan_tolak" required maxlength="1000" placeholder="Alasan penolakan">
                                    <button class="btn btn-red btn-sm" type="submit">Kirim penolakan</button>
                                </form>
                            </details>
                        </div>
                    @elseif ($tugas->stage === 'ditolak' && $tugas->alasan_tolak)
                        <p class="xs muted mt-3">Alasan penolakan: {{ $tugas->alasan_tolak }}</p>
                    @endif
                </article>
            @endforeach
        </div>
        <div class="mt-5">{{ $penugasan->links() }}</div>
    @endif
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-submit-state]').forEach((form) => {
            form.addEventListener('submit', () => {
                const button = form.querySelector('button[type="submit"]');
                if (!button || !form.reportValidity()) return;
                button.disabled = true;
                button.textContent = 'Mengirim...';
            });
        });
        if (window.lucide) window.lucide.createIcons();
    </script>
@endpush

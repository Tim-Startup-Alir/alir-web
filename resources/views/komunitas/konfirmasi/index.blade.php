@extends('layouts.komunitas')

@section('title', 'Konfirmasi Lapangan')
@section('topbar_title', 'Konfirmasi Lapangan')
@section('topbar_subtitle', 'Tinjau kondisi laporan yang dapat dijangkau komunitas Anda')

@section('content')
    <div class="pg-h">
        <div>
            <span class="kicker">F-03 PEMETAAN TITIK BANJIR</span>
            <h2>Konfirmasi Lapangan</h2>
            <p class="muted">Pilih laporan yang diminta, ditugaskan, atau berada dalam radius posko.</p>
        </div>
    </div>

    @if (session('kepercayaan_terbaru'))
        <section class="card konfirmasi-success" role="status" aria-live="polite" dusk="konfirmasi-success">
            <div><strong>Konfirmasi {{ session('kepercayaan_terbaru.kode') }} tersimpan.</strong><p>Tingkat kepercayaan laporan terbaru:</p></div>
            <x-badge-kepercayaan :level="session('kepercayaan_terbaru.level')" />
        </section>
    @endif

    @error('konfirmasi')
        <p class="card konfirmasi-alert" role="alert" aria-live="assertive">{{ $message }}</p>
    @enderror

    <section class="card konfirmasi-section" aria-labelledby="diminta-title">
        <h3 id="diminta-title">Diminta Tim Startup ALIR</h3>
        @if ($diminta->isEmpty())
            <p class="empty" role="status">Belum ada permintaan konfirmasi.</p>
        @else
            <div class="konfirmasi-list">
                @foreach ($diminta as $item)
                    @include('komunitas.konfirmasi.partials.kartu', ['laporan' => $item])
                @endforeach
            </div>
        @endif
    </section>

    <section class="card konfirmasi-section" aria-labelledby="sekitar-title">
        <h3 id="sekitar-title">Laporan di sekitar posko atau penugasan</h3>
        @if ($sekitar->isEmpty())
            <p class="empty" role="status">Tidak ada laporan lain yang dapat dikonfirmasi saat ini.</p>
        @else
            <div class="konfirmasi-list">
                @foreach ($sekitar as $item)
                    @include('komunitas.konfirmasi.partials.kartu', ['laporan' => $item])
                @endforeach
            </div>
        @endif
    </section>
@endsection

@push('styles')
<style>
.konfirmasi-section{padding:clamp(14px,2vw,22px);margin-bottom:16px}.konfirmasi-section h3{margin:0 0 14px}.konfirmasi-list{display:grid;gap:10px}.konfirmasi-card{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px;border:1px solid var(--s-200);border-radius:var(--r-md);background:var(--surface)}.konfirmasi-card h4{margin:0 0 5px}.konfirmasi-card p{margin:3px 0;color:var(--s-600)}.konfirmasi-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}.konfirmasi-success{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px;margin-bottom:16px}.konfirmasi-success p{margin:5px 0 0}.konfirmasi-alert{padding:12px;color:var(--red-ink);border-color:var(--red)}
@media(max-width:520px){.konfirmasi-card,.konfirmasi-success{align-items:flex-start;flex-direction:column}.konfirmasi-actions,.konfirmasi-actions .btn{width:100%}}
</style>
@endpush

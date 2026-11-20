@props(['level' => null])
@php
    $badge = match ($level) {
        'tinggi' => ['teal', 'Tinggi'],
        'sedang' => ['amber', 'Sedang'],
        'rendah' => ['red', 'Rendah'],
        default => ['water', 'Belum dinilai'],
    };
@endphp
<span {{ $attributes->merge(['class' => 'tag '.$badge[0]]) }}>{{ $badge[1] }}</span>

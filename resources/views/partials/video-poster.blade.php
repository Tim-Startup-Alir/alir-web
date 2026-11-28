{{-- Menampilkan cuplikan frame pada <video data-poster-frame> sebelum diputar (tanpa berkas poster terpisah). --}}
<script>
document.querySelectorAll('video[data-poster-frame]').forEach(function (v) {
    v.preload = 'auto';
    v.addEventListener('loadedmetadata', function () {
        v.currentTime = Math.max(1, (v.duration || 4) * 0.25);
    }, { once: true });
});
</script>

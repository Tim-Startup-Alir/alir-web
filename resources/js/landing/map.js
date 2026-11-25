/* Helper Leaflet + OpenStreetMap untuk peta pin lokasi laporan (PBI-005). */
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

export const CENTER = [-6.8845, 109.675];

export function baseMap(el, opts = {}) {
    const map = L.map(el, { center: opts.center || CENTER, zoom: opts.zoom || 14, scrollWheelZoom: false, zoomControl: true });
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);
    // aktifkan scroll-zoom hanya setelah peta diklik, agar scroll halaman tidak "tersangkut"
    map.once('focus', () => map.scrollWheelZoom.enable());
    // ukuran kontainer bisa berubah saat modal beranimasi / layout selesai: ukur ulang setiap kali berubah
    if ('ResizeObserver' in window) {
        const ro = new ResizeObserver(() => map.invalidateSize({ pan: false }));
        ro.observe(el);
        map.on('unload', () => ro.disconnect());
    }
    setTimeout(() => map.invalidateSize(), 250);
    return map;
}

export const pinIcon = () =>
    L.divIcon({
        className: '',
        iconSize: [34, 44],
        iconAnchor: [17, 42],
        html: `<svg width="34" height="44" viewBox="0 0 34 44" style="filter:drop-shadow(0 4px 6px rgba(11,30,51,.35))"><path d="M17 1C8.2 1 1 8 1 16.7 1 28.5 17 43 17 43s16-14.5 16-26.3C33 8 25.8 1 17 1Z" fill="#FF6A3D" stroke="#fff" stroke-width="2"/><circle cx="17" cy="16.5" r="6" fill="#0B1E33"/></svg>`,
    });

/** gambar wilayah cakupan: poligon GeoJSON bila ada, jika belum lingkaran radius dari pusat kelurahan */
export function drawCoverage(map, wilayah = []) {
    const style = { color: '#FF6A3D', weight: 1.5, dashArray: '5 6', fillColor: '#FF6A3D', fillOpacity: 0.06, interactive: false };
    const layers = wilayah.map((w) => {
        const layer = w.batas ? L.geoJSON(w.batas, { style, interactive: false }) : L.circle([w.lat, w.lng], { ...style, radius: w.radius });
        layer.addTo(map);
        L.tooltip({ permanent: true, direction: 'center', className: 'ld-cov-label' }).setLatLng([w.lat, w.lng]).setContent(w.nama).addTo(map);
        return layer;
    });
    return layers.length ? L.featureGroup(layers).getBounds() : null;
}

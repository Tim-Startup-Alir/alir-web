@extends('layouts.admin')

@section('title', 'Peta Laporan')
@section('topbar_title', 'Peta Laporan')
@section('topbar_subtitle', 'Tinjau laporan banjir dan indikasi duplikat di sekitar titik')

@section('content')
    <div class="pg-h">
        <div><span class="kicker">F-03 PEMETAAN TITIK BANJIR</span><h2>Peta Laporan</h2><p class="muted">Laporan sekitar memakai radius {{ config('alir.kepercayaan.radius_m') }} m dan rentang ±{{ config('alir.kepercayaan.rentang_jam') }} jam.</p></div>
    </div>
    <section class="card peta-page" id="peta-laporan" aria-label="Peta laporan banjir"
        data-url="{{ route('admin.peta-laporan.data') }}" data-detail-url="{{ url('/admin/peta-laporan') }}"
        data-foto-base="{{ url('/admin/laporan') }}" data-radius="{{ config('alir.kepercayaan.radius_m') }}"
        data-rentang="{{ config('alir.kepercayaan.rentang_jam') }}" data-center-lat="{{ $center[0] }}"
        data-center-lng="{{ $center[1] }}" data-zoom="{{ config('alir.peta.zoom_default') }}"
        data-selected="{{ $selectedId ?? '' }}">
        <div class="peta-controls" aria-label="Filter laporan">
            <label class="input-label" for="filter-status">Status laporan</label>
            <select class="input" id="filter-status" multiple aria-label="Filter status laporan" dusk="peta-filter-status">
                @foreach ($statuses as $status)<option value="{{ $status }}">{{ str_replace('_', ' ', ucfirst($status)) }}</option>@endforeach
            </select>
            <label class="input-label" for="filter-wilayah">Wilayah</label>
            <select class="input" id="filter-wilayah" aria-label="Filter wilayah laporan" dusk="peta-filter-wilayah">
                <option value="">Semua wilayah</option>
                @foreach ($wilayahList as $wilayah)<option value="{{ $wilayah->id }}">{{ $wilayah->nama_kelurahan }}</option>@endforeach
            </select>
            <label class="input-label" for="filter-kepercayaan">Tingkat kepercayaan</label>
            <select class="input" id="filter-kepercayaan" multiple aria-label="Filter tingkat kepercayaan laporan" dusk="peta-filter-kepercayaan">
                <option value="tinggi">Tinggi</option><option value="sedang">Sedang</option><option value="rendah">Rendah</option>
            </select>
        </div>
        <section class="peta-rules" aria-labelledby="rules-title">
            <h3 id="rules-title">Aturan penilaian</h3>
            <p>Tinggi: minimal {{ config('alir.kepercayaan.laporan_tinggi') }} pelapor independen atau konfirmasi sesuai.</p>
            <p>Sedang: minimal {{ config('alir.kepercayaan.laporan_sedang') }} pelapor independen; foto menaikkan ke sedang: {{ config('alir.kepercayaan.foto_menaikkan_ke_sedang') ? 'Ya' : 'Tidak' }}.</p>
            <p>Radius {{ config('alir.kepercayaan.radius_m') }} m, jendela ±{{ config('alir.kepercayaan.rentang_jam') }} jam. Nilai diatur lewat .env.</p>
        </section>
        <div id="peta-state" class="peta-state" role="status" aria-live="polite">Memuat peta laporan…</div>
        <div id="peta-map" class="peta-map" role="application" aria-label="Peta interaktif titik laporan" tabindex="0" dusk="peta-map"></div>
        <template id="peta-confidence-badge"><x-badge-kepercayaan :level="null" /></template>
        <p id="peta-terpotong" class="peta-truncated" role="status" aria-live="polite" hidden>Jumlah titik dibatasi. Perbesar peta atau persempit filter.</p>
        <div class="peta-layout">
            <section class="peta-detail" aria-labelledby="detail-title" aria-live="polite" dusk="peta-detail">
                <h3 id="detail-title">Detail laporan terpilih</h3><div id="detail-content" class="empty">Pilih titik pada peta untuk melihat detail laporan.</div>
            </section>
            <section class="peta-nearby" aria-labelledby="nearby-title" aria-live="polite">
                <h3 id="nearby-title">Laporan sekitar</h3><div id="nearby-content" class="empty">Pilih laporan untuk memuat laporan dalam radius dan rentang waktu.</div>
            </section>
        </div>
    </section>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
<style>
.peta-page{padding:clamp(12px,2vw,24px);display:grid;gap:14px}.peta-controls{display:grid;grid-template-columns:auto minmax(160px,1fr) auto minmax(160px,1fr);gap:10px;align-items:center}.peta-controls select[multiple]{min-height:44px}.peta-map{height:min(58vh,620px);min-height:320px;border-radius:var(--r-md);border:1px solid var(--s-200);z-index:0}.peta-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:14px}.peta-detail,.peta-nearby,.peta-rules{min-width:0;padding:16px;border:1px solid var(--s-200);border-radius:var(--r-md);background:var(--surface)}.peta-detail h3,.peta-nearby h3,.peta-rules h3{margin:0 0 12px;font-size:17px}.peta-rules p{margin:5px 0;color:var(--s-600)}.peta-state{color:var(--s-600);min-height:1.5em}.peta-truncated{padding:12px;border-radius:var(--r-sm);background:var(--amber-tint);color:var(--amber-ink)}.peta-gallery,.peta-list{display:grid;gap:10px}.peta-gallery{grid-template-columns:repeat(auto-fill,minmax(84px,1fr));margin:12px 0}.peta-photo{display:block;width:100%;height:84px;object-fit:cover;border-radius:var(--r-sm);border:1px solid var(--s-200)}.peta-item{display:flex;gap:12px;align-items:center;text-align:left;width:100%;padding:10px;border:1px solid var(--s-200);border-radius:var(--r-sm);background:var(--surface);cursor:pointer}.peta-item:hover,.peta-item:focus-visible{border-color:var(--water);outline:2px solid var(--water-tint)}.peta-item img{width:64px;height:64px;object-fit:cover;border-radius:8px}.peta-muted{opacity:.62}.peta-meta{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px}.peta-dl{display:grid;grid-template-columns:auto 1fr;gap:6px 12px;margin:12px 0}.peta-dl dt{color:var(--s-500)}.peta-dl dd{margin:0;font-weight:600;overflow-wrap:anywhere}.peta-error{color:var(--red-ink)}
@media(max-width:700px){.peta-controls{grid-template-columns:1fr}.peta-map{height:52vh;min-height:290px}.peta-layout{grid-template-columns:1fr}.peta-page{padding:12px}}
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('peta-laporan');
    if (!root || !window.L) return;
    const map = L.map('peta-map').setView([Number(root.dataset.centerLat), Number(root.dataset.centerLng)], Number(root.dataset.zoom));
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
    const markers = L.markerClusterGroup(); map.addLayer(markers); const nearbyMarkers=[];
    const markerIndex = new Map(); let circle = null; let bboxRequest = null; let detailRequest = null; let timer = null; let selectedPayload = null;
    const state = document.getElementById('peta-state'); const selectedNode = document.getElementById('detail-content'); const nearbyNode = document.getElementById('nearby-content');
    const statusFilter = document.getElementById('filter-status'); const wilayahFilter = document.getElementById('filter-wilayah'); const confidenceFilter = document.getElementById('filter-kepercayaan');
    const text = (tag, value, cls = '') => { const node = document.createElement(tag); node.textContent = value ?? '—'; if (cls) node.className = cls; return node; };
    const badge = (level) => { const node=document.getElementById('peta-confidence-badge').content.firstElementChild.cloneNode(true); node.textContent=({tinggi:'Tinggi',sedang:'Sedang',rendah:'Rendah'})[level] || 'Belum dinilai'; node.className='tag '+({tinggi:'teal',sedang:'amber',rendah:'red'})[level]; if(!level) node.className='tag water'; return node; };
    const statusLabel = value => String(value || '').replaceAll('_', ' ');
    const statusBadge = value => text('span', statusLabel(value), 'tag '+(value==='ditolak'?'red':value==='selesai'?'teal':value==='digabung'?'water':'amber'));
    function addPhotos(parent, photos) { const gallery = document.createElement('div'); gallery.className = 'peta-gallery'; if (!photos?.length) { gallery.append(text('span', 'Belum ada foto.', 'muted')); parent.append(gallery); return; } photos.forEach(photo => { const link=document.createElement('a'); link.href=photo.url; link.target='_blank'; link.rel='noopener'; link.setAttribute('aria-label','Buka foto laporan di tab baru'); const img=document.createElement('img'); img.src=photo.url; img.alt='Foto laporan'; img.className='peta-photo'; link.append(img); gallery.append(link); }); parent.append(gallery); }
    function renderDetail(payload) {
        const report=payload.laporan; selectedNode.replaceChildren(); selectedNode.append(text('strong', report.alamat_singkat));
        const dl=document.createElement('dl'); dl.className='peta-dl'; [['Kode',report.kode],['Wilayah',report.wilayah],['Tingkat dampak',report.severity],['Jumlah warga',report.jumlah_warga],['Waktu lapor',new Date(report.dibuat_pada).toLocaleString('id-ID')],['Catatan',report.catatan]].forEach(([k,v])=>{dl.append(text('dt',k),text('dd',v));}); dl.append(text('dt','Status'),text('dd','')); dl.lastChild.append(statusBadge(report.status));
        selectedNode.append(dl,badge(report.tingkat_kepercayaan)); addPhotos(selectedNode,report.foto);
        if (report.kepercayaan) {
            const section=document.createElement('section'); section.append(text('h4','Dasar penilaian'));
            const reasons=document.createElement('ul'); report.kepercayaan.alasan.forEach(reason=>reasons.append(text('li',reason)));
            section.append(reasons,text('p',`${report.kepercayaan.jumlah_pelapor_independen} pelapor independen.`));
            if(report.kepercayaan.konfirmasi){const confirmation=report.kepercayaan.konfirmasi;const waktu=confirmation.waktu?new Date(confirmation.waktu).toLocaleString('id-ID'):'waktu tidak tercatat';section.append(text('p',`Konfirmasi ${statusLabel(confirmation.hasil)} oleh ${confirmation.komunitas || 'komunitas'} pada ${waktu}.`));}
            selectedNode.append(section);
        }
        if (report.konfirmasi?.length) { const section=document.createElement('section'); section.append(text('h4','Konfirmasi lapangan')); report.konfirmasi.forEach(item=>section.append(text('p',item.hasil || item.status || JSON.stringify(item)))); selectedNode.append(section); }
    }
    function renderNearby(items) {
        nearbyNode.replaceChildren(); nearbyMarkers.forEach(marker=>{map.removeLayer(marker);markerIndex.forEach((indexed,id)=>{if(indexed===marker) markerIndex.delete(id);});}); nearbyMarkers.length=0; const statuses=[...statusFilter.selectedOptions].map(option=>option.value); const wilayahName=wilayahFilter.selectedOptions[0]?.textContent.trim(); const levels=[...confidenceFilter.selectedOptions].map(option=>option.value); const visibleItems=items.filter(item=>(!statuses.length||statuses.includes(item.status))&&(!wilayahFilter.value||item.wilayah===wilayahName)&&(!levels.length||levels.includes(item.kepercayaan?.level||item.tingkat_kepercayaan))); if (!visibleItems.length) { nearbyNode.append(text('p',items.length?'Tidak ada laporan sekitar yang sesuai filter.':'Tidak ada laporan lain dalam radius dan rentang waktu ini.','empty')); return; }
        const list=document.createElement('div'); list.className='peta-list'; visibleItems.forEach(item=>{let marker=markerIndex.get(item.id);if(!marker){marker=L.circleMarker([item.lat,item.lng],{radius:8,color:getComputedStyle(root).getPropertyValue('--teal-ink').trim(),fillColor:getComputedStyle(root).getPropertyValue('--teal').trim(),fillOpacity:0.9});marker.on('click',()=>selectReport(item.id));marker.addTo(map);markerIndex.set(item.id,marker);nearbyMarkers.push(marker);}const button=document.createElement('button');button.type='button';button.className='peta-item'+(['ditolak','digabung'].includes(item.status)?' peta-muted':'');button.dataset.laporanId=String(item.id);button.setAttribute('aria-label',`Pilih laporan ${item.kode}, jarak ${Math.round(item.jarak_m)} meter`); if(item.foto?.length){const img=document.createElement('img');img.src=item.foto[0].url;img.alt='Foto laporan';button.append(img);}const body=document.createElement('span');body.append(text('strong',item.alamat_singkat),text('span',`${Math.round(item.jarak_m)} m · ${item.selisih_menit} menit · ${statusLabel(item.status)}`,'peta-meta'));body.append(statusBadge(item.status),badge(item.kepercayaan?.level||item.tingkat_kepercayaan));button.append(body);button.addEventListener('click',()=>{map.setView(marker.getLatLng(),Math.max(map.getZoom(),16));marker.fire('click');});list.append(button);}); nearbyNode.append(list);
    }
    async function selectReport(id) {
        if(detailRequest) detailRequest.abort(); selectedPayload=null; detailRequest=new AbortController(); state.textContent='Memuat detail laporan…'; selectedNode.textContent='Memuat detail laporan…'; nearbyNode.textContent='Memuat laporan sekitar…';
        try { const response=await fetch(`${root.dataset.detailUrl}/${encodeURIComponent(id)}/sekitar`,{signal:detailRequest.signal,headers:{Accept:'application/json'}}); if(!response.ok) throw new Error('Detail laporan gagal dimuat.'); const payload=await response.json(); selectedPayload=payload; renderDetail(payload); renderNearby(payload.sekitar); if(circle) map.removeLayer(circle); circle=L.circle([payload.laporan.lat,payload.laporan.lng],{radius:Number(payload.parameter.radius_m)}).addTo(map); state.textContent=`Menampilkan laporan dalam ${payload.parameter.radius_m} m dan ±${payload.parameter.rentang_jam} jam.`; }
        catch(error){if(error.name!=='AbortError'){selectedNode.textContent='Detail laporan gagal dimuat.';nearbyNode.textContent='Laporan sekitar gagal dimuat.';state.textContent=error.message;state.classList.add('peta-error');}}
    }
    async function loadMarkers() {
        if(bboxRequest) bboxRequest.abort(); bboxRequest=new AbortController(); state.textContent='Memuat titik laporan…';
        const bounds=map.getBounds();const query=new URLSearchParams({south:bounds.getSouth(),west:bounds.getWest(),north:bounds.getNorth(),east:bounds.getEast()});
        [...statusFilter.selectedOptions].forEach(option=>query.append('status[]',option.value)); [...confidenceFilter.selectedOptions].forEach(option=>query.append('kepercayaan[]',option.value)); if(wilayahFilter.value) query.set('wilayah_id',wilayahFilter.value);
        try { const response=await fetch(`${root.dataset.url}?${query}`,{signal:bboxRequest.signal,headers:{Accept:'application/json'}}); if(!response.ok) throw new Error('Titik laporan gagal dimuat.');const payload=await response.json();markers.clearLayers();markerIndex.clear();payload.data.forEach(item=>{const marker=L.marker([item.lat,item.lng],{title:item.kode,alt:`Laporan ${item.kode}`});const popup=document.createElement('div');popup.append(text('strong',item.kode),badge(item.tingkat_kepercayaan));marker.bindPopup(popup);marker.on('click',()=>selectReport(item.id));markers.addLayer(marker);markerIndex.set(item.id,marker);});document.getElementById('peta-terpotong').hidden=!payload.terpotong;state.textContent=payload.data.length?`${payload.data.length} titik laporan ditampilkan.`:'Tidak ada laporan pada area peta ini.';state.classList.remove('peta-error');if(!payload.data.length) state.classList.add('empty'); else state.classList.remove('empty');const selected=Number(root.dataset.selected);if(selected&&markerIndex.has(selected)){root.dataset.selected='';selectReport(selected);}}
        catch(error){if(error.name!=='AbortError'){state.textContent=error.message;state.classList.add('peta-error');}}
    }
    map.on('moveend',()=>{clearTimeout(timer);timer=setTimeout(loadMarkers,300);}); const changeFilters=()=>{if(selectedPayload) renderNearby(selectedPayload.sekitar);loadMarkers();}; statusFilter.addEventListener('change',changeFilters);wilayahFilter.addEventListener('change',changeFilters);confidenceFilter.addEventListener('change',changeFilters);loadMarkers();
});
</script>
@endpush

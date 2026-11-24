@extends('layouts.admin')

@section('title', 'Peta Laporan')
@section('topbar_title', 'Peta Laporan')
@section('topbar_subtitle', 'Tinjau laporan banjir dan indikasi duplikat di sekitar titik')

@section('content')
    <div class="pg-h">
        <div><span class="kicker">Pemetaan Titik Banjir</span><h2>Peta Laporan</h2><p class="muted">Laporan sekitar memakai radius {{ config('alir.kepercayaan.radius_m') }} m dan selisih maksimal {{ config('alir.kepercayaan.rentang_jam') }} jam sebelum atau sesudah laporan acuan.</p></div>
    </div>
    <section class="card peta-page" id="peta-laporan" aria-label="Peta laporan banjir"
        data-url="{{ route('admin.peta-laporan.data') }}" data-detail-url="{{ url('/admin/peta-laporan') }}"
        data-foto-base="{{ url('/admin/laporan') }}" data-radius="{{ config('alir.kepercayaan.radius_m') }}"
        data-rentang="{{ config('alir.kepercayaan.rentang_jam') }}" data-center-lat="{{ $center[0] }}"
        data-center-lng="{{ $center[1] }}" data-zoom="{{ config('alir.peta.zoom_default') }}"
        data-selected="{{ $selectedId ?? '' }}">
        <div class="peta-controls" aria-label="Filter laporan">
            {{-- Status dropdown --}}
            <div class="peta-dropdown" id="filter-status" dusk="peta-filter-status">
                <button type="button" class="peta-dropdown-btn" aria-haspopup="listbox" aria-expanded="false">
                    <span class="peta-dropdown-label">Status laporan</span>
                    <svg class="peta-dropdown-icon" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div class="peta-dropdown-menu" role="listbox" aria-label="Filter status laporan" hidden>
                    @foreach ($statuses as $status)
                    <label class="peta-dropdown-item"><input type="checkbox" name="status[]" value="{{ $status }}"><span>{{ str_replace('_', ' ', ucfirst($status)) }}</span></label>
                    @endforeach
                </div>
            </div>
            {{-- Wilayah dropdown --}}
            <div class="peta-dropdown" id="filter-wilayah" dusk="peta-filter-wilayah">
                <button type="button" class="peta-dropdown-btn" aria-haspopup="listbox" aria-expanded="false">
                    <span class="peta-dropdown-label">Semua wilayah</span>
                    <svg class="peta-dropdown-icon" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div class="peta-dropdown-menu" role="listbox" aria-label="Filter wilayah laporan" hidden>
                    @foreach ($wilayahList as $wilayah)
                    <label class="peta-dropdown-item"><input type="radio" name="wilayah" value="{{ $wilayah->id }}"><span>{{ $wilayah->nama_kelurahan }}</span></label>
                    @endforeach
                    <label class="peta-dropdown-item"><input type="radio" name="wilayah" value="" checked><span>Semua wilayah</span></label>
                </div>
            </div>
            {{-- Kepercayaan dropdown --}}
            <div class="peta-dropdown" id="filter-kepercayaan" dusk="peta-filter-kepercayaan">
                <button type="button" class="peta-dropdown-btn" aria-haspopup="listbox" aria-expanded="false">
                    <span class="peta-dropdown-label">Tingkat kepercayaan</span>
                    <svg class="peta-dropdown-icon" width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div class="peta-dropdown-menu" role="listbox" aria-label="Filter tingkat kepercayaan" hidden>
                    <label class="peta-dropdown-item"><input type="checkbox" name="kepercayaan[]" value="tinggi"><span>Tinggi</span></label>
                    <label class="peta-dropdown-item"><input type="checkbox" name="kepercayaan[]" value="sedang"><span>Sedang</span></label>
                    <label class="peta-dropdown-item"><input type="checkbox" name="kepercayaan[]" value="rendah"><span>Rendah</span></label>
                </div>
            </div>
        </div>
        <section class="peta-rules" aria-labelledby="rules-title">
            <h3 id="rules-title">Aturan penilaian</h3>
            <p class="trust-rules-intro">Tingkat kepercayaan dihitung otomatis dari laporan lain yang masuk dalam radius {{ config('alir.kepercayaan.radius_m') }} m dan dalam {{ config('alir.kepercayaan.rentang_jam') }} jam sebelum atau sesudah laporan ini.</p>
            <ul class="trust-rules-levels" aria-label="Tingkat kepercayaan">
                <li><span class="trust-dot trust-dot-high" aria-hidden="true"></span><span><strong>Tinggi</strong> - {{ config('alir.kepercayaan.laporan_tinggi') }} pelapor berbeda atau lebih, atau relawan sudah memastikan banjir di lokasi.</span></li>
                <li><span class="trust-dot trust-dot-medium" aria-hidden="true"></span><span><strong>Sedang</strong> - {{ config('alir.kepercayaan.laporan_sedang') }} pelapor berbeda.</span></li>
                <li><span class="trust-dot trust-dot-low" aria-hidden="true"></span><span><strong>Rendah</strong> - hanya 1 pelapor.</span></li>
            </ul>
            <h4 class="trust-rules-notes-title">Catatan</h4>
            <ul class="trust-rules-notes">
                <li>Pelapor berbeda = nomor WhatsApp berbeda. Beberapa laporan dari nomor yang sama dihitung sebagai 1 pelapor.</li>
                <li>Laporan ini ikut dihitung.</li>
                <li>Ada foto atau tidak tidak mengubah tingkat kepercayaan. Foto hanya membantu Anda menilai.</li>
                <li>Jika relawan menyatakan banjir tidak terkonfirmasi, tingkat tidak turun; kami tampilkan sebagai “Dibantah lapangan” untuk pertimbangan Anda.</li>
            </ul>
        </section>
        <div id="peta-state" class="peta-state" role="status" aria-live="polite">Memuat peta laporan…</div>
        <template id="peta-confidence-badge"><x-badge-kepercayaan :level="null" /></template>
        <p id="peta-terpotong" class="peta-truncated" role="status" aria-live="polite" hidden>Jumlah titik dibatasi. Perbesar peta atau persempit filter.</p>
        @include('admin.peta-laporan.partials.detail', ['mapId' => 'peta-map'])
        <div class="peta-layout">
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
.peta-page{padding:clamp(12px,2vw,24px);display:grid;gap:14px}.peta-controls{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-start}.peta-dropdown{position:relative;flex:1 1 180px;min-width:160px}.peta-dropdown-btn{display:flex;align-items:center;justify-content:space-between;width:100%;padding:8px 12px;background:var(--surface);border:1px solid var(--s-200);border-radius:var(--r-sm);cursor:pointer;font:inherit;color:inherit;transition:border-color .15s}.peta-dropdown-btn:hover,.peta-dropdown-btn:focus-visible{border-color:var(--water)}.peta-dropdown-btn[aria-expanded="true"]{border-color:var(--water);box-shadow:0 0 0 2px var(--water-tint)}.peta-dropdown-btn[aria-expanded="true"] .peta-dropdown-icon{transform:rotate(180deg)}.peta-dropdown-icon{transition:transform .15s;flex-shrink:0;margin-left:8px}.peta-dropdown-label{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.peta-dropdown-menu{position:absolute;top:calc(100% + 4px);left:0;right:0;background:var(--surface);border:1px solid var(--s-200);border-radius:var(--r-sm);box-shadow:0 4px 12px rgba(0,0,0,.1);padding:6px;z-index:20;max-height:220px;overflow-y:auto}.peta-dropdown-item{display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:var(--r-sm);cursor:pointer;user-select:none;font-size:14px;transition:background .1s}.peta-dropdown-item:hover{background:var(--s-50,.05)}.peta-dropdown-item input{margin:0;accent-color:var(--water,#3b82f6)}.peta-map{height:min(58vh,620px);min-height:320px;border-radius:var(--r-md);border:1px solid var(--s-200);z-index:0}.peta-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:14px}.peta-detail,.peta-nearby,.peta-rules{min-width:0;padding:16px;border:1px solid var(--s-200);border-radius:var(--r-md);background:var(--surface)}.peta-detail h3,.peta-nearby h3,.peta-rules h3{margin:0 0 12px;font-size:17px}.peta-rules p{margin:5px 0;color:var(--s-600);font-size:14px;line-height:1.55}.trust-rules-intro{max-width:80ch}.trust-rules-levels,.trust-rules-notes{display:grid;gap:9px;margin:12px 0;padding:0;list-style:none;color:var(--s-600);font-size:14px;line-height:1.55}.trust-rules-levels li{display:flex;align-items:flex-start;gap:9px}.trust-dot{width:10px;height:10px;flex:0 0 10px;margin-top:6px;border-radius:50%}.trust-dot-high{background:var(--teal)}.trust-dot-medium{background:var(--amber)}.trust-dot-low{background:var(--red)}.trust-rules-levels strong{color:var(--navy)}.trust-rules-notes{padding-left:19px;list-style:disc}.trust-rules-notes-title{margin:16px 0 4px;color:var(--navy);font-size:14px}.peta-state{color:var(--s-600);min-height:1.5em}.peta-truncated{padding:12px;border-radius:var(--r-sm);background:var(--amber-tint);color:var(--amber-ink)}.peta-gallery,.peta-list{display:grid;gap:10px}.peta-gallery{grid-template-columns:repeat(auto-fill,minmax(84px,1fr));margin:12px 0}.peta-photo{display:block;width:100%;height:84px;object-fit:cover;border-radius:var(--r-sm);border:1px solid var(--s-200)}.peta-item{display:flex;gap:12px;align-items:center;text-align:left;width:100%;padding:10px;border:1px solid var(--s-200);border-radius:var(--r-sm);background:var(--surface);cursor:pointer}.peta-item:hover,.peta-item:focus-visible{border-color:var(--water);outline:2px solid var(--water-tint)}.peta-item img{width:64px;height:64px;object-fit:cover;border-radius:8px}.peta-muted{opacity:.62}.peta-meta{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px}.peta-dl{display:grid;grid-template-columns:auto 1fr;gap:6px 12px;margin:12px 0}.peta-dl dt{color:var(--s-500)}.peta-dl dd{margin:0;font-weight:600;overflow-wrap:anywhere}.peta-error{color:var(--red-ink)}
.peta-selection-grid,.peta-info-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:14px}.peta-map{height:min(42vh,420px);min-height:300px}.peta-photo-empty{height:min(42vh,420px);min-height:300px;display:grid;place-items:center;border-radius:var(--r-md);background:repeating-linear-gradient(135deg,#E2E8F0 0 12px,#F1F5F9 12px 24px);color:var(--s-500);font-weight:700}.peta-photo-empty img{width:100%;height:100%;object-fit:cover;border-radius:inherit}.peta-info-card{min-width:0;padding:20px;border:1px solid var(--s-200);border-radius:var(--r-md);background:#fff;box-shadow:var(--sh-sm)}.peta-info-card h3{margin:0 0 12px;font-size:17px}.peta-trust-count{font-size:20px;font-weight:800;color:var(--navy)}.peta-confirmation-state{margin:10px 0}.peta-dl dd{text-align:right;font-weight:700}
@media(max-width:700px){.peta-controls{grid-template-columns:1fr}.peta-selection-grid,.peta-info-grid{grid-template-columns:1fr}.peta-map,.peta-photo-empty{height:300px;min-height:280px}.peta-layout{grid-template-columns:1fr}.peta-page{padding:12px}}
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
    const state = document.getElementById('peta-state'); const photoNode = document.getElementById('detail-photo'); const confidenceNode = document.getElementById('detail-confidence'); const factsNode = document.getElementById('detail-facts'); const nearbyNode = document.getElementById('nearby-content');
    const statusFilter = document.getElementById('filter-status'); const wilayahFilter = document.getElementById('filter-wilayah'); const confidenceFilter = document.getElementById('filter-kepercayaan');
    // Dropdown toggle logic
    document.querySelectorAll('.peta-dropdown-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const menu = btn.nextElementSibling;
            const isOpen = !menu.hidden;
            document.querySelectorAll('.peta-dropdown-menu').forEach(m => { m.hidden = true; m.previousElementSibling.setAttribute('aria-expanded','false'); });
            if (!isOpen) { menu.hidden = false; btn.setAttribute('aria-expanded','true'); }
        });
    });
    document.addEventListener('click', () => { document.querySelectorAll('.peta-dropdown-menu').forEach(m => { m.hidden = true; m.previousElementSibling.setAttribute('aria-expanded','false'); }); });
    document.querySelectorAll('.peta-dropdown-menu').forEach(m => m.addEventListener('click', e => e.stopPropagation()));
    // Helper: get checked values from a dropdown container
    const getChecked = (container) => [...container.querySelectorAll('input:checked')].map(i => i.value).filter(v => v);
    // Update button label after selection
    function updateLabel(container, defaultText) {
        const vals = [...container.querySelectorAll('input:checked')].map(i => i.closest('label').querySelector('span').textContent.trim()).filter(Boolean);
        const label = container.querySelector('.peta-dropdown-label');
        label.textContent = vals.length ? vals.join(', ') : defaultText;
    }
    statusFilter.querySelectorAll('input').forEach(i => i.addEventListener('change', () => { updateLabel(statusFilter, 'Status laporan'); changeFilters(); }));
    wilayahFilter.querySelectorAll('input').forEach(i => i.addEventListener('change', () => { updateLabel(wilayahFilter, 'Semua wilayah'); changeFilters(); }));
    confidenceFilter.querySelectorAll('input').forEach(i => i.addEventListener('change', () => { updateLabel(confidenceFilter, 'Tingkat kepercayaan'); changeFilters(); }));
    const text = (tag, value, cls = '') => { const node = document.createElement(tag); node.textContent = value ?? '—'; if (cls) node.className = cls; return node; };
    const badge = (level) => { const node=document.getElementById('peta-confidence-badge').content.firstElementChild.cloneNode(true); node.textContent=({tinggi:'Tinggi',sedang:'Sedang',rendah:'Rendah'})[level] || 'Belum dinilai'; node.className='tag '+({tinggi:'teal',sedang:'amber',rendah:'red'})[level]; if(!level) node.className='tag water'; return node; };
    const statusLabel = value => String(value || '').replaceAll('_', ' ');
    const confirmationLabel = value => value==='terkonfirmasi'?'Banjir terkonfirmasi':value==='tidak_terkonfirmasi'?'Dibantah lapangan':'Belum ada hasil';
    const statusBadge = value => text('span', statusLabel(value), 'tag '+(value==='ditolak'?'red':value==='selesai'?'teal':value==='digabung'?'water':'amber'));
    function addPhotos(parent, photos) { parent.replaceChildren(); parent.classList.add('peta-photo-empty'); parent.setAttribute('aria-label', photos?.length ? 'Foto laporan' : 'Tidak ada foto'); if (!photos?.length) { parent.textContent='Tidak ada foto'; return; } const img=document.createElement('img'); img.src=photos[0].url; img.alt='Foto laporan banjir'; parent.append(img); }
    function renderDetail(payload) {
        const report=payload.laporan; addPhotos(photoNode,report.foto); confidenceNode.classList.remove('empty'); factsNode.classList.remove('empty'); confidenceNode.replaceChildren(badge(report.tingkat_kepercayaan));
        const dl=document.createElement('dl'); dl.className='peta-dl'; [['Pelapor','Anonim'],['Kode',report.kode],['Warga terdampak',report.jumlah_warga],['Laporan sekitar',payload.sekitar.length],['Koordinat',`${Number(report.lat).toFixed(5)}, ${Number(report.lng).toFixed(5)}`],['Wilayah',report.wilayah],['Waktu lapor',new Date(report.dibuat_pada).toLocaleString('id-ID')]].forEach(([k,v])=>{dl.append(text('dt',k),text('dd',v));}); factsNode.replaceChildren(dl);
        if (report.kepercayaan) {
            confidenceNode.append(text('p',`${report.kepercayaan.jumlah_pelapor_independen} laporan independen`,'peta-trust-count'));
            const confirmation=report.kepercayaan.konfirmasi;
            confidenceNode.append(text('p',confirmation?.hasil==='tidak_terkonfirmasi'?'Dibantah lapangan':confirmation?.hasil==='terkonfirmasi'?'Banjir terkonfirmasi':'Belum ada konfirmasi lapangan','peta-confirmation-state'));
            if(confirmation?.komunitas) confidenceNode.append(text('p',`Dikonfirmasi oleh ${confirmation.komunitas}.`,'muted'));
        }
        if (report.konfirmasi?.length) {
            const section=document.createElement('section'); section.append(text('h4','Konfirmasi lapangan'));
            report.konfirmasi.forEach(item=>{
                const entry=document.createElement('article'); entry.className='peta-confirmation';
                const waktu=item.waktu?new Date(item.waktu).toLocaleString('id-ID'):'waktu tidak tercatat';
                entry.append(text('p',`Hasil: ${confirmationLabel(item.hasil)} · ${item.komunitas || 'komunitas'} · ${waktu}`));
                if(item.catatan) entry.append(text('p',item.catatan));
                if(item.foto_url){const link=document.createElement('a');link.setAttribute('href',item.foto_url);link.setAttribute('target','_blank');link.setAttribute('rel','noopener');link.textContent='Lihat foto bukti';entry.append(link);}
                section.append(entry);
            });
            confidenceNode.append(section);
        }
    }
    function renderNearby(items) {
        nearbyNode.replaceChildren(); nearbyMarkers.forEach(marker=>{map.removeLayer(marker);markerIndex.forEach((indexed,id)=>{if(indexed===marker) markerIndex.delete(id);});}); nearbyMarkers.length=0; const statuses=getChecked(statusFilter); const wilayahVal=getChecked(wilayahFilter)[0]||''; const levels=getChecked(confidenceFilter); const visibleItems=items.filter(item=>(!statuses.length||statuses.includes(item.status))&&(!wilayahVal||String(item.wilayah_id)===wilayahVal)&&(!levels.length||levels.includes(item.kepercayaan?.level||item.tingkat_kepercayaan))); if (!visibleItems.length) { nearbyNode.append(text('p',items.length?'Tidak ada laporan sekitar yang sesuai filter.':'Tidak ada laporan lain dalam radius dan rentang waktu ini.','empty')); return; }
        const list=document.createElement('div'); list.className='peta-list'; visibleItems.forEach(item=>{let marker=markerIndex.get(item.id);if(!marker){marker=L.circleMarker([item.lat,item.lng],{radius:8,color:getComputedStyle(root).getPropertyValue('--teal-ink').trim(),fillColor:getComputedStyle(root).getPropertyValue('--teal').trim(),fillOpacity:0.9});marker.on('click',()=>selectReport(item.id));marker.addTo(map);markerIndex.set(item.id,marker);nearbyMarkers.push(marker);}const button=document.createElement('button');button.type='button';button.className='peta-item'+(['ditolak','digabung'].includes(item.status)?' peta-muted':'');button.dataset.laporanId=String(item.id);button.setAttribute('aria-label',`Pilih laporan ${item.kode}, jarak ${Math.round(item.jarak_m)} meter`); if(item.foto?.length){const img=document.createElement('img');img.src=item.foto[0].url;img.alt='Foto laporan';button.append(img);}const body=document.createElement('span');body.append(text('strong',item.alamat_singkat),text('span',`${Math.round(item.jarak_m)} m · ${item.selisih_menit} menit · ${statusLabel(item.status)}`,'peta-meta'));body.append(statusBadge(item.status),badge(item.kepercayaan?.level||item.tingkat_kepercayaan));button.append(body);button.addEventListener('click',()=>{map.setView(marker.getLatLng(),Math.max(map.getZoom(),16));marker.fire('click');});list.append(button);}); nearbyNode.append(list);
    }
    async function selectReport(id) {
        if(detailRequest) detailRequest.abort(); selectedPayload=null; detailRequest=new AbortController(); state.textContent='Memuat detail laporan…'; confidenceNode.textContent='Memuat detail laporan…'; factsNode.textContent='Memuat detail laporan…'; nearbyNode.textContent='Memuat laporan sekitar…';
        try { const response=await fetch(`${root.dataset.detailUrl}/${encodeURIComponent(id)}/sekitar`,{signal:detailRequest.signal,headers:{Accept:'application/json'}}); if(!response.ok) throw new Error('Detail laporan gagal dimuat.'); const payload=await response.json(); selectedPayload=payload; renderDetail(payload); renderNearby(payload.sekitar); if(circle) map.removeLayer(circle); circle=L.circle([payload.laporan.lat,payload.laporan.lng],{radius:Number(payload.parameter.radius_m),color:'#FF6A3D',dashArray:'7 7',fillColor:'#FF6A3D',fillOpacity:0.08}).addTo(map); state.textContent=`Menampilkan laporan dalam ${payload.parameter.radius_m} m dan maksimal selisih ${payload.parameter.rentang_jam} jam.`; }
        catch(error){if(error.name!=='AbortError'){confidenceNode.textContent='Detail laporan gagal dimuat.';factsNode.textContent='Detail laporan gagal dimuat.';nearbyNode.textContent='Laporan sekitar gagal dimuat.';state.textContent=error.message;state.classList.add('peta-error');}}
    }
    async function loadMarkers() {
        if(bboxRequest) bboxRequest.abort(); bboxRequest=new AbortController(); state.textContent='Memuat titik laporan…';
        const bounds=map.getBounds();const query=new URLSearchParams({south:bounds.getSouth(),west:bounds.getWest(),north:bounds.getNorth(),east:bounds.getEast()});
        getChecked(statusFilter).forEach(v=>query.append('status[]',v)); getChecked(confidenceFilter).forEach(v=>query.append('kepercayaan[]',v)); const wVal=getChecked(wilayahFilter)[0]; if(wVal) query.set('wilayah_id',wVal);
        try { const response=await fetch(`${root.dataset.url}?${query}`,{signal:bboxRequest.signal,headers:{Accept:'application/json'}}); if(!response.ok) throw new Error('Titik laporan gagal dimuat.');const payload=await response.json();markers.clearLayers();markerIndex.clear();payload.data.forEach(item=>{const marker=L.marker([item.lat,item.lng],{title:item.kode,alt:`Laporan ${item.kode}`});const popup=document.createElement('div');popup.append(text('strong',item.kode),badge(item.tingkat_kepercayaan));marker.bindPopup(popup);marker.on('click',()=>selectReport(item.id));markers.addLayer(marker);markerIndex.set(item.id,marker);});document.getElementById('peta-terpotong').hidden=!payload.terpotong;state.textContent=payload.data.length?`${payload.data.length} titik laporan ditampilkan.`:'Tidak ada laporan pada area peta ini.';state.classList.remove('peta-error');if(!payload.data.length) state.classList.add('empty'); else state.classList.remove('empty');const selected=Number(root.dataset.selected);if(selected&&markerIndex.has(selected)){root.dataset.selected='';selectReport(selected);}}
        catch(error){if(error.name!=='AbortError'){state.textContent=error.message;state.classList.add('peta-error');}}
    }
    map.on('moveend',()=>{clearTimeout(timer);timer=setTimeout(loadMarkers,300);}); const changeFilters=()=>{if(selectedPayload) renderNearby(selectedPayload.sekitar);loadMarkers();}; loadMarkers();
});
</script>
@endpush

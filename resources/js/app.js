
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);
window.Chart = Chart;

/* ===== sidebar: collapse (desktop) + drawer (mobile) ===== */
const html = document.documentElement;
const sidebar = document.getElementById('sidebar');
if (html.dataset.sidebar !== 'collapsed' && localStorage.getItem('sb-collapsed') === '1') html.dataset.sidebar = 'collapsed';

function persistSb() { localStorage.setItem('sb-collapsed', html.dataset.sidebar === 'collapsed' ? '1' : '0'); }
document.querySelectorAll('[data-sb-toggle]').forEach((btn) => btn.addEventListener('click', () => {
    if (window.innerWidth < 1024) { sidebar.classList.toggle('sbx-open'); document.getElementById('sb-overlay')?.classList.toggle('show'); }
    else { html.dataset.sidebar = html.dataset.sidebar === 'collapsed' ? '' : 'collapsed'; if (html.dataset.sidebar === '') delete html.dataset.sidebar; persistSb(); }
}));
document.getElementById('sb-overlay')?.addEventListener('click', () => { sidebar.classList.remove('sbx-open'); document.getElementById('sb-overlay').classList.remove('show'); });
window.addEventListener('resize', () => { if (window.innerWidth >= 1024) { sidebar.classList.remove('sbx-open'); } });

/* ===== dropdown notifikasi ===== */
const bell = document.getElementById('bell-btn'), bellPanel = document.getElementById('bell-panel');
async function renderBellList() {
    const box = document.getElementById('bell-list'); if (!box) return;
    try {
        const d = await (await fetch('/notifikasi/unread', { headers: { Accept: 'application/json' } })).json();
        box.innerHTML = '';
        if (!d.items.length) { box.innerHTML = '<p class="px-4 py-6 text-center text-xs text-slate-400">Tidak ada notifikasi baru 🎉</p>'; return; }
        d.items.forEach((it) => {
            const a = document.createElement('a');
            a.className = 'block px-3.5 py-2.5 hover:bg-slate-50 transition';
            a.href = it.url || '/notifikasi';
            a.innerHTML = '<p class="font-semibold text-[13px] text-slate-800 truncate"></p><p class="text-xs text-slate-500 mt-0.5 line-clamp-2"></p><p class="text-[10px] text-slate-400 mt-1"></p>';
            a.children[0].textContent = it.judul; a.children[1].textContent = it.isi || '';
            a.children[2].textContent = it.waktu;
            box.appendChild(a);
        });
    } catch (e) { box.innerHTML = '<p class="px-4 py-4 text-center text-xs text-slate-400">Gagal memuat.</p>'; }
}
bell?.addEventListener('click', (e) => { e.stopPropagation(); bellPanel.classList.toggle('hidden'); if (!bellPanel.classList.contains('hidden')) renderBellList(); });
document.addEventListener('click', () => bellPanel?.classList.add('hidden'));
bellPanel?.addEventListener('click', (e) => e.stopPropagation());

/* ===== flash auto-dismiss ===== */
document.querySelectorAll('[data-flash]').forEach((el) => setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .5s'; setTimeout(() => el.remove(), 500); }, 6000));

/* ===== confirm hapus ===== */
document.querySelectorAll('form[data-confirm]').forEach((f) => f.addEventListener('submit', (e) => {
    if (!confirm(f.dataset.confirm)) e.preventDefault();
}));

/* ===== polling notifikasi + floating toast ===== */
const seen = JSON.parse(localStorage.getItem('sb-seen-notifs') || '[]');
async function pollNotifs() {
    try {
        const r = await fetch('/notifikasi/unread', { headers: { 'Accept': 'application/json' } });
        if (!r.ok) return;
        const d = await r.json();
        const badge = document.getElementById('bell-badge');
        if (badge) { badge.textContent = d.belum; badge.classList.toggle('hidden', !d.belum); }
        d.items.forEach((it) => {
            if (seen.includes(it.id)) return;
            seen.push(it.id);
            if (seen.length > 80) seen.splice(0, seen.length - 80);
            localStorage.setItem('sb-seen-notifs', JSON.stringify(seen));
            showToast(it);
        });
    } catch (e) { /* offline */ }
}
function showToast(it) {
    if ('Notification' in window && Notification.permission === 'granted') {
        const n = new Notification(it.judul, { body: it.isi || '', tag: 'sb-' + it.id });
        n.onclick = () => { window.focus(); if (it.url) location.href = it.url; };
        return;
    }
    const wrap = document.getElementById('toast-zone');
    if (!wrap) return;
    const t = document.createElement('div');
    t.className = 'toast card px-4 py-3 flex items-start gap-3 shadow-lg pointer-events-auto cursor-pointer max-w-sm';
    t.innerHTML = `<span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal-50 text-teal-600">🔔</span>
        <div class="min-w-0"><p class="text-sm font-semibold text-slate-800 truncate"></p><p class="text-xs text-slate-500 mt-0.5 line-clamp-2"></p></div>`;
    t.querySelector('p').textContent = it.judul;
    t.querySelectorAll('p')[1].textContent = it.isi || '';
    t.addEventListener('click', () => { if (it.url) location.href = it.url; t.remove(); });
    wrap.appendChild(t);
    setTimeout(() => t.remove(), 9000);
}
document.getElementById('notif-baca')?.addEventListener('click', (e) => {
    e.preventDefault();
    fetch('/notifikasi/baca-semua', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } })
        .then(() => { renderBellList(); pollNotifs(); });
});
document.getElementById('notif-permit')?.addEventListener('click', async (e) => {
    e.preventDefault();
    if ('Notification' in window) await Notification.requestPermission();
    pollNotifs();
});
pollNotifs();
setInterval(pollNotifs, 75000);

/* ===== grafik ===== */
document.querySelectorAll('canvas[data-chart]').forEach((cv) => {
    const data = JSON.parse(cv.dataset.chart);
    const type = cv.dataset.type || 'line';
    const ds = [];
    if (Array.isArray(data.datasets)) {
        data.datasets.forEach((d) => ds.push({
            label: d.label, data: d.data, type: d.type || undefined,
            borderColor: d.color || '#14b8a6',
            backgroundColor: d.type === 'bar' ? (d.color || '#14b8a6') + '55' : (d.fill ? (d.color || '#14b8a6') + '22' : 'transparent'),
            borderWidth: 2, borderDash: d.dash ? [6, 4] : [], tension: .35, fill: !!d.fill,
            yAxisID: d.axis || 'y',
        }));
    } else {
        if (data.target) ds.push({ label: 'Target', data: data.target, borderColor: '#94a3b8', backgroundColor: 'rgba(148,163,184,.15)', borderWidth: 2, borderDash: [6, 4], tension: .35, fill: false });
        if (data.capaian) ds.push({ label: 'Capaian', data: data.capaian, borderColor: '#14b8a6', backgroundColor: 'rgba(20,184,166,.18)', borderWidth: 2, tension: .35, fill: true });
    }
    const scales = { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } };
    if (Array.isArray(data.datasets) && data.datasets.some((d) => d.axis === 'y1'))
        scales.y1 = { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { callback: (v) => v + '%' } };
    new Chart(cv, { type, data: { labels: data.labels, datasets: ds },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 12, font: { size: 11 } } } }, scales } });
});

/* ===== form import: state saat submit ===== */
document.querySelectorAll('form[data-import]').forEach((f) => f.addEventListener('submit', () => {
    const b = f.querySelector('[type=submit]'); if (!b) return;
    if (!f.querySelector('input[type=file]')?.files.length) { alert('Pilih file CSV/Excel dulu ya.'); event.preventDefault(); return; }
    b.disabled = true; b.textContent = 'Mengurai file…';
}));

/* modal edit generik: [data-modal-open=id] */
document.querySelectorAll('[data-modal-open]').forEach((btn) => btn.addEventListener('click', () => {
    document.getElementById(btn.dataset.modalOpen)?.classList.remove('hidden');
}));
document.querySelectorAll('[data-modal-close]').forEach((btn) => btn.addEventListener('click', () => {
    btn.closest('.modal-shell')?.classList.add('hidden');
}));

/* service worker */
if ("serviceWorker" in navigator && location.protocol !== "file:") {
    window.addEventListener("load", () => navigator.serviceWorker.register("/sw.js").catch(() => {}));
}

/* Esc menutup panel/modal */
document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    document.getElementById('bell-panel')?.classList.add('hidden');
    document.querySelectorAll('.modal-shell:not(.hidden)').forEach((m) => m.classList.add('hidden'));
    sidebar.classList.remove('sbx-open'); document.getElementById('sb-overlay')?.classList.remove('show');
});

/* ===== nama file tampil saat pilih file upload (tanpa memindah input, tanpa teks dobel) ===== */
document.querySelectorAll('input[type=file]').forEach((f) => {
    if (f.dataset.fnameOwn) return;
    f.dataset.fnameOwn = '1';
    f.classList.add('fname-own');
    const lbl = document.createElement('span');
    lbl.className = 'fname block truncate pt-1 text-[12px] font-semibold text-slate-600';
    f.insertAdjacentElement('afterend', lbl);
    const upd = () => { lbl.textContent = f.files.length ? '📎 ' + f.files[0].name : ''; lbl.style.display = f.files.length ? '' : 'none'; };
    f.addEventListener('change', upd);
    upd();
});
// teks "No file chosen"/nama bawaan browser dibuat transparan — HANYA warna teksnya;
// tombol tetap terlihat karena tiap input di app menyetel warna tombolnya sendiri (class file:*)
const fnameCss = document.createElement('style');
fnameCss.textContent = 'input.fname-own{color:transparent}';
document.head.appendChild(fnameCss);

/* ===== pratinjau Excel & Word langsung di browser (tampilan usaha mirip aslinya) ===== */
const office = document.getElementById('office-host');
if (office) {
    const esc = (x) => String(x).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    (async () => {
        try {
            const res = await fetch(office.dataset.src);
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const buf = await res.arrayBuffer();
            if (office.dataset.kind === 'xlsx') {
                const XLSX = await import('xlsx');
                const wb = XLSX.read(buf, { type: 'array', cellStyles: true });
                const names = wb.SheetNames.slice(0, 10);
                const sheetHtml = (name) => {
                    const ws = wb.Sheets[name];
                    if (!ws || !ws['!ref']) return '<p class="text-slate-400 text-sm">Sheet kosong.</p>';
                    const rg = XLSX.utils.decode_range(ws['!ref']);
                    const maxR = Math.min(rg.e.r, 400), maxC = Math.min(rg.e.c, 40);
                    const merges = ws['!merges'] || [], cov = {}, anc = {};
                    merges.forEach((m) => {
                        anc[m.s.r + '_' + m.s.c] = { rs: m.e.r - m.s.r + 1, cs: m.e.c - m.s.c + 1 };
                        for (let r = m.s.r; r <= m.e.r; r++) for (let c = m.s.c; c <= m.e.c; c++) if (!(r === m.s.r && c === m.s.c)) cov[r + '_' + c] = 1;
                    });
                    const cols = ws['!cols'] || [];
                    let colg = '<colgroup><col style="width:38px">';
                    for (let c = rg.s.c; c <= maxC; c++) {
                        const cc = cols[c] || {}; const px = cc.wpx || (cc.wch ? Math.round(cc.wch * 7.3) : 0);
                        colg += '<col style="width:' + (px ? Math.min(Math.max(px, 56), 360) : 128) + 'px">';
                    }
                    colg += '</colgroup>';
                    let h = '<table class="xls">' + colg + '<thead><tr><th class="rowhdr"></th>';
                    for (let c = rg.s.c; c <= maxC; c++) h += '<th>' + XLSX.utils.encode_col(c) + '</th>';
                    h += '</tr></thead><tbody>';
                    for (let r = rg.s.r; r <= maxR; r++) {
                        h += '<tr><th class="rowhdr">' + (r + 1) + '</th>';
                        for (let c = rg.s.c; c <= maxC; c++) {
                            const k = r + '_' + c; if (cov[k]) continue;
                            const cell = ws[XLSX.utils.encode_cell({ r, c })];
                            let txt = '', sty = '', cls = '';
                            if (cell) {
                                txt = cell.w !== undefined ? String(cell.w) : (cell.v !== undefined ? String(cell.v) : '');
                                if (typeof cell.v === 'number') cls = 'num';
                                const st = cell.s || {};
                                if (st.font && st.font.bold) sty += 'font-weight:700;';
                                if (st.fill && st.fill.fgColor && st.fill.fgColor.rgb && /^[0-9A-Fa-f]{6,8}$/.test(st.fill.fgColor.rgb)) sty += 'background-color:#' + st.fill.fgColor.rgb.slice(-6) + ';';
                                if (st.alignment && st.alignment.horizontal) sty += 'text-align:' + (st.alignment.horizontal === 'center' ? 'center' : st.alignment.horizontal === 'right' ? 'right' : 'left') + ';';
                            }
                            const a = anc[k];
                            h += '<td' + (cls ? ' class="' + cls + '"' : '') + (sty ? ' style="' + sty + '"' : '') + (a ? ' colspan="' + a.cs + '" rowspan="' + a.rs + '"' : '') + '>' + esc(txt) + '</td>';
                        }
                        h += '</tr>';
                    }
                    return h + '</tbody></table>';
                };
                let tabs = '<div class="mb-2 flex flex-wrap gap-1">';
                names.forEach((n, ix) => { tabs += '<button data-i="' + ix + '" class="sheet-tab rounded-lg border px-2.5 py-1 text-[11.5px] font-bold ' + (ix ? 'border-slate-200 bg-white text-slate-500 hover:border-teal-400' : 'border-slate-900 bg-slate-900 text-white') + '">' + esc(n) + '</button>'; });
                tabs += '</div><div class="sheet-body overflow-x-auto thin-scroll rounded-lg border border-slate-200 bg-white"></div>';
                office.innerHTML = '<style>#office-host .xls{border-collapse:separate;border-spacing:0;font-size:12px;table-layout:fixed}#office-host .xls th,#office-host .xls td{border-right:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;padding:4px 8px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:360px;background:#fff}#office-host .xls thead th{position:sticky;top:0;z-index:2;background:#f1f5f9;color:#475569;font-weight:800;text-align:center;min-width:56px}#office-host .xls .rowhdr{position:sticky;left:0;z-index:1;background:#f1f5f9;color:#64748b;font-weight:700;text-align:center;min-width:38px}#office-host .xls td.num{text-align:right;font-variant-numeric:tabular-nums}#office-host .xls td{text-align:left}</style>' + tabs;
                const body = office.querySelector('.sheet-body');
                const show = (ix) => { body.innerHTML = sheetHtml(names[ix]); office.querySelectorAll('.sheet-tab').forEach((b, i2) => { const on = i2 === ix; b.className = b.className.replace(/border-slate-200 bg-white text-slate-500 hover:border-teal-400|border-slate-900 bg-slate-900 text-white/g, on ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-500 hover:border-teal-400'); }); };
                office.querySelectorAll('.sheet-tab').forEach((b) => b.addEventListener('click', () => show(+b.dataset.i)));
                show(0);
            } else {
                const { renderAsync } = await import('docx-preview');
                office.innerHTML = '<style>#office-host{background:#e2e8f0;padding:14px}#office-host .docx-wrapper{background:#e2e8f0;padding:0}#office-host .docx-wrapper>section.docx{box-shadow:0 4px 18px rgba(15,23,42,.14);margin-bottom:14px}</style><div id="docx-mount"></div>';
                await renderAsync(buf, document.getElementById('docx-mount'), undefined, { inWrapper: true, breakPages: true, ignoreLastRenderedPageBreak: false, renderHeaders: true, renderFooters: true, renderFootnotes: true, images: 'blob' });
            }
        } catch (e) {
            office.innerHTML = '<p class="text-sm text-rose-600">Pratinjau gagal: ' + esc(e.message || e) + ' — silakan unduh file.</p>';
        }
    })();
}

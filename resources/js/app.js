import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);
window.Chart = Chart;

/* ===== sidebar: fluid on hover + pin toggle ===== */
const html = document.documentElement;
const sidebar = document.getElementById('sidebar');

function persistSb() { localStorage.setItem('sb-pinned', html.classList.contains('sb-pinned') ? '1' : '0'); }

/* Tablet (768-1023px): pasang kelas sb-tablet — sidebar jadi ikon-saja menetap dan
   konten mengambil sisa lebar. Kelas dilepas otomatis saat lebar berubah, dan drawer
   yang terbuka ditutup agar tidak ada kondisi tanggung antar mode. */
function sbMode() {
    const w = window.innerWidth;
    const tablet = w >= 768 && w < 1024;
    if (html.classList.contains('sb-tablet') !== tablet) {
        html.classList.toggle('sb-tablet', tablet);
        sidebar?.classList.remove('sbx-open');
        document.getElementById('sb-overlay')?.classList.remove('show');
    }
    if (w >= 1024) { sidebar?.classList.remove('sbx-open'); document.getElementById('sb-overlay')?.classList.remove('show'); }
}
sbMode();
window.addEventListener('resize', sbMode);

/* judul tooltip tombol toggle mengikuti kondisi (perkecil/perbesar, buka/tutup menu) */
function sbTitles() {
    const chev = document.getElementById('sb-collapse-btn');
    const desk = document.getElementById('sb-pin-btn');
    if (desk) desk.title = html.classList.contains('sb-pinned') ? 'Unpin sidebar' : 'Pin sidebar';
    if (chev) chev.title = sidebar?.classList.contains('sbx-open') ? 'Tutup menu' : 'Buka menu';
}
sbTitles();

document.querySelectorAll('[data-sb-toggle]').forEach((btn) => btn.addEventListener('click', () => {
    if (window.innerWidth < 1024) { sidebar.classList.toggle('sbx-open'); document.getElementById('sb-overlay')?.classList.toggle('show'); }
    sbTitles();
}));

const pinBtn = document.getElementById('sb-pin-btn');
if (pinBtn) {
    pinBtn.addEventListener('click', () => {
        html.classList.toggle('sb-pinned');
        persistSb();
        sbTitles();
    });
}
document.getElementById('sb-overlay')?.addEventListener('click', () => { sidebar.classList.remove('sbx-open'); document.getElementById('sb-overlay').classList.remove('show'); });

/* tooltip nama menu saat sidebar ikon-saja (desktop/tablet ber-mouse) — fade-in halus.
   Diposisikan fixed agar tidak terpotong area scroll nav; perangkat sentuh memakai
   fallback atribut title. */
if (window.matchMedia('(hover: hover)').matches) {
    sidebar?.querySelectorAll('.nav-link[data-label]').forEach((a) => {
        let tip = null;
        a.addEventListener('mouseenter', () => {
            const ikonSaja = !html.classList.contains('sb-pinned') && !sidebar.matches(':hover') || html.classList.contains('sb-tablet');
            if (!ikonSaja || sidebar.classList.contains('sbx-open')) return;
            tip = document.createElement('div');
            tip.className = 'sb-tip';
            tip.textContent = a.dataset.label;
            document.body.appendChild(tip);
            const r = a.getBoundingClientRect();
            tip.style.top = (r.top + r.height / 2) + 'px';
            tip.style.left = (r.right + 10) + 'px';
            requestAnimationFrame(() => tip.classList.add('show'));
        });
        a.addEventListener('mouseleave', () => { tip?.remove(); tip = null; });
    });
}

/* ===== mobile search drawer toggle ===== */
const mobileSearchToggle = document.getElementById('mobile-search-toggle');
const mobileSearchDrawer = document.getElementById('mobile-search-drawer');
const mobileSearchClose = document.getElementById('mobile-search-close');
const mobileSearchInput = document.getElementById('mobile-search-input');

if (mobileSearchToggle && mobileSearchDrawer) {
    mobileSearchToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = !mobileSearchDrawer.classList.contains('hidden');
        if (isOpen) {
            mobileSearchDrawer.classList.add('hidden');
        } else {
            mobileSearchDrawer.classList.remove('hidden');
            setTimeout(() => mobileSearchInput?.focus(), 80);
        }
    });

    mobileSearchClose?.addEventListener('click', () => {
        mobileSearchDrawer.classList.add('hidden');
    });

    document.addEventListener('click', (e) => {
        if (!mobileSearchDrawer.contains(e.target) && e.target !== mobileSearchToggle && !mobileSearchToggle.contains(e.target)) {
            mobileSearchDrawer.classList.add('hidden');
        }
    });
}

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
bell?.addEventListener('click', (e) => { 
    e.stopPropagation(); 
    document.getElementById('user-menu-panel')?.classList.add('hidden');
    bellPanel?.classList.toggle('hidden'); 
    if (bellPanel && !bellPanel.classList.contains('hidden')) { renderBellList(); pollNotifs(); } 
});
document.addEventListener('click', () => bellPanel?.classList.add('hidden'));
bellPanel?.addEventListener('click', (e) => e.stopPropagation());

/* ===== user profile menu dropdown (topbar) ===== */
const userMenuBtn = document.getElementById('user-menu-btn');
const userMenuPanel = document.getElementById('user-menu-panel');
if (userMenuBtn && userMenuPanel) {
    userMenuBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        bellPanel?.classList.add('hidden');
        userMenuPanel.classList.toggle('hidden');
    });
    document.addEventListener('click', (e) => {
        if (!userMenuPanel.contains(e.target) && e.target !== userMenuBtn && !userMenuBtn.contains(e.target)) {
            userMenuPanel.classList.add('hidden');
        }
    });
}

/* ===== flash auto-dismiss ===== */
document.querySelectorAll('[data-flash]').forEach((el) => setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .5s'; setTimeout(() => el.remove(), 500); }, 6000));

/* ===== confirm hapus ===== */
document.querySelectorAll('form[data-confirm]').forEach((f) => f.addEventListener('submit', (e) => {
    if (!confirm(f.dataset.confirm)) e.preventDefault();
}));

/* ===== polling notifikasi + floating toast + Web Notification API (HANYA dijalankan bila user sudah login) ===== */
const seen = JSON.parse(localStorage.getItem('sb-seen-notifs') || '[]');

function playNotifSound() {
    try {
        const audio = new Audio('/sounds/notif.mp3');
        audio.volume = 0.6;
        const p = audio.play();
        if (p !== undefined) {
            p.catch(() => {
                // Autoplay fallback: Web Audio API synthesis
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    if (ctx.state === 'suspended') ctx.resume();
                    const now = ctx.currentTime;
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(659.25, now);
                    osc.frequency.setValueAtTime(880.0, now + 0.12);
                    gain.gain.setValueAtTime(0.18, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.38);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now);
                    osc.stop(now + 0.38);
                } catch (err) {}
            });
        }
    } catch (e) {}
}

function showNotification(it) {
    // 1. Suara notifikasi pendek
    playNotifSound();

    // 2. Notifikasi Browser Asli (Native Web Notification API)
    if ('Notification' in window && Notification.permission === 'granted') {
        try {
            const n = new Notification(it.judul, {
                body: it.isi || 'Pemberitahuan baru dari SIPARDIS Dinas Pariwisata',
                icon: '/images/logo-dispar.jpg',
                badge: '/images/logo-dispar.jpg',
                tag: 'sb-' + it.id,
            });
            n.onclick = () => {
                window.focus();
                if (it.url) location.href = it.url;
            };
        } catch (err) {}
    }

    // 3. Floating Toast di layar aplikasi
    showToast(it);
}

async function pollNotifs() {
    if (!document.getElementById('bell-btn') && !document.getElementById('sidebar')) return;
    try {
        const r = await fetch('/notifikasi/unread', { headers: { 'Accept': 'application/json' } });
        if (!r.ok) return;
        const d = await r.json();
        const badge = document.getElementById('bell-badge');
        if (badge) { badge.textContent = d.belum; badge.classList.toggle('hidden', !d.belum); }
        
        let hasNew = false;
        d.items.forEach((it) => {
            if (seen.includes(it.id)) return;
            seen.push(it.id);
            if (seen.length > 100) seen.splice(0, seen.length - 100);
            localStorage.setItem('sb-seen-notifs', JSON.stringify(seen));
            showNotification(it);
            hasNew = true;
        });
    } catch (e) { /* offline / unauthenticated */ }
}

function showToast(it) {
    const wrap = document.getElementById('toast-zone');
    if (!wrap) return;
    const t = document.createElement('div');
    t.className = 'toast card px-4 py-3 flex items-start gap-3 shadow-lg pointer-events-auto cursor-pointer max-w-sm';
    t.innerHTML = `<span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-purple-50 text-purple-600">🔔</span>
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

// Inisialisasi polling & minta izin notifikasi HANYA saat user sudah login (berada di layout dashboard)
if (document.getElementById('bell-btn') || document.getElementById('sidebar')) {
    // 1. Minta izin notifikasi browser secara otomatis jika belum pernah dipilih
    if ('Notification' in window && Notification.permission === 'default') {
        setTimeout(() => {
            Notification.requestPermission().catch(() => {});
        }, 1200);
    }

    // 2. Polling tiap 20 detik
    pollNotifs();
    setInterval(pollNotifs, 20000);

    // 3. Refresh saat tab kembali difokuskan
    window.addEventListener('focus', () => pollNotifs());
    document.addEventListener('visibilitychange', () => { if (!document.hidden) pollNotifs(); });
}

/* ===== grafik — adaptif tema gelap/terang (ungu #150f23/#1e1533) ===== */
const _sipardisCharts = [];
window._sipardisCharts = _sipardisCharts;
function _isDarkTheme() { return document.documentElement.classList.contains('dark'); }
function _hexToRgba(hex, alpha) {
    try {
        let h = String(hex || '').trim().replace('#', '');
        if (h.length === 3) h = h.split('').map((c) => c + c).join('');
        if (h.length !== 6) return hex;
        const r = parseInt(h.slice(0, 2), 16), g = parseInt(h.slice(2, 4), 16), b = parseInt(h.slice(4, 6), 16);
        if (Number.isNaN(r) || Number.isNaN(g) || Number.isNaN(b)) return hex;
        return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
    } catch (e) { return hex; }
}
function _mapDatasetColor(hex) {
    if (!_isDarkTheme()) return hex || '#14b8a6';
    const k = String(hex || '').toLowerCase();
    const m = {
        '#0f172a': '#c084fc',
        '#1e293b': '#c084fc',
        '#334155': '#a78bfa',
        '#475569': '#a99fc4',
        '#64748b': '#b8a9d9',
        '#94a3b8': '#b8a9d9',
        '#94a3b8a0': '#b8a9d9',
        '#14b8a6': '#2dd4bf',
        '#0d9488': '#2dd4bf',
        '#0f766e': '#2dd4bf',
        '#059669': '#34d399',
        '#10b981': '#34d399',
        '#0891b2': '#38bdf8',
        '#0ea5e9': '#38bdf8',
        '#0284c7': '#38bdf8',
        '#7c3aed': '#a78bfa',
        '#8b5cf6': '#a78bfa',
        '#a855f7': '#c084fc',
        '#9333ea': '#c084fc',
        '#f97316': '#fb923c',
        '#ea580c': '#fb923c',
        '#f59e0b': '#fbbf24',
        '#d97706': '#fbbf24',
        '#e11d48': '#fb7185',
        '#be123c': '#fb7185',
    };
    return m[k] || hex || '#c084fc';
}
function _gridColor() { return _isDarkTheme() ? 'rgba(56,42,85,0.62)' : '#f1f5f9'; }
function _tickColor() { return _isDarkTheme() ? '#a99fc4' : '#64748b'; }
function _legendColor() { return _isDarkTheme() ? '#ede9f6' : '#334155'; }
function _borderColor() { return _isDarkTheme() ? '#382a55' : '#e2e8f0'; }
function _tooltipBg() { return _isDarkTheme() ? '#1e1533' : '#0f172a'; }
function _buildScales(data) {
    const isDark = _isDarkTheme();
    const gridC = _gridColor();
    const tickC = _tickColor();
    const borderC = _borderColor();
    const s = {
        y: {
            beginAtZero: true,
            grid: { color: gridC, drawBorder: false },
            ticks: { color: tickC, font: { size: 11 } },
            border: { color: borderC, display: false },
        },
        x: {
            grid: { display: false, drawBorder: false },
            ticks: { color: tickC, font: { size: 11 } },
            border: { color: borderC, display: false },
        },
    };
    if (Array.isArray(data.datasets) && data.datasets.some((d) => d.axis === 'y1')) {
        s.y1 = {
            position: 'right',
            beginAtZero: true,
            grid: { drawOnChartArea: false, drawBorder: false },
            ticks: { color: tickC, font: { size: 11 }, callback: (v) => v + '%' },
            border: { color: borderC, display: false },
        };
    }
    return s;
}
function _buildDatasets(data) {
    const isDark = _isDarkTheme();
    const ds = [];
    if (Array.isArray(data.datasets)) {
        data.datasets.forEach((d) => {
            const raw = d.color || '#14b8a6';
            const col = _mapDatasetColor(raw);
            const bg = d.type === 'bar'
                ? _hexToRgba(col, 0.55)
                : (d.fill ? _hexToRgba(col, isDark ? 0.20 : 0.14) : 'transparent');
            ds.push({
                label: d.label,
                data: d.data,
                type: d.type || undefined,
                borderColor: col,
                backgroundColor: bg,
                borderWidth: 2.2,
                borderDash: d.dash ? [6, 4] : [],
                tension: 0.35,
                fill: !!d.fill,
                pointRadius: 2.5,
                pointHoverRadius: 4,
                pointBackgroundColor: col,
                pointBorderColor: isDark ? '#1e1533' : '#ffffff',
                pointBorderWidth: 1.2,
                yAxisID: d.axis || 'y',
                spanGaps: true,
                _origColor: raw,
            });
        });
    } else {
        if (data.target) {
            const _rawT = '#94a3b8';
            const col = _mapDatasetColor(_rawT);
            ds.push({
                label: 'Target', data: data.target,
                borderColor: col,
                backgroundColor: _hexToRgba(col, isDark ? 0.10 : 0.15),
                borderWidth: 2, borderDash: [6, 4], tension: 0.35, fill: false,
                pointRadius: 2, pointHoverRadius: 3.5,
                pointBackgroundColor: col, pointBorderColor: isDark ? '#1e1533' : '#ffffff', pointBorderWidth: 1.2,
                spanGaps: true,
                _origColor: _rawT,
            });
        }
        if (data.capaian) {
            const _rawC = '#14b8a6';
            const col = _mapDatasetColor(_rawC);
            ds.push({
                label: 'Capaian', data: data.capaian,
                borderColor: col,
                backgroundColor: _hexToRgba(col, isDark ? 0.22 : 0.18),
                borderWidth: 2.2, tension: 0.35, fill: true,
                pointRadius: 2.5, pointHoverRadius: 4,
                pointBackgroundColor: col, pointBorderColor: isDark ? '#1e1533' : '#ffffff', pointBorderWidth: 1.2,
                spanGaps: true,
                _origColor: _rawC,
            });
        }
    }
    return ds;
}
function _chartOptions(scales) {
    const isDark = _isDarkTheme();
    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: {
            legend: {
                display: true, position: 'top',
                labels: { boxWidth: 12, font: { size: 11 }, color: _legendColor(), usePointStyle: true, pointStyle: 'circle', padding: 18 },
            },
            tooltip: {
                backgroundColor: _tooltipBg(),
                titleColor: isDark ? '#ede9f6' : '#ffffff',
                bodyColor: isDark ? '#d6cfe8' : '#e2e8f0',
                borderColor: isDark ? '#382a55' : 'transparent',
                borderWidth: isDark ? 1 : 0,
                padding: 10,
                cornerRadius: 10,
                displayColors: true,
            },
        },
        scales,
    };
}
function _renderCharts() {
    document.querySelectorAll('canvas[data-chart]').forEach((cv) => {
        if (cv._sipardisChart) { try { cv._sipardisChart.destroy(); } catch (e) {} }
        let data;
        try { data = JSON.parse(cv.dataset.chart); } catch (e) { return; }
        const type = cv.dataset.type || 'line';
        const ds = _buildDatasets(data);
        const scales = _buildScales(data);
        const opts = _chartOptions(scales);
        const ch = new Chart(cv, { type, data: { labels: data.labels, datasets: ds }, options: opts });
        cv._sipardisChart = ch;
        _sipardisCharts.push(ch);
    });
}
_renderCharts();
window._sipardisRefreshCharts = function () {
    // dipanggil saat toggle tema atau sistem berubah — update warna tanpa reload
    _sipardisCharts.forEach((ch) => {
        if (!ch || !ch.options) return;
        const isDark = _isDarkTheme();
        const rawData = ch.data;
        // update dataset warna (pakai label untuk mapping balik? pakai borderColor asal terang -> map)
        ch.data.datasets.forEach((ds) => {
            const cur = ds.borderColor || '#14b8a6';
            const asLight = ds._origColor || (() => {
                const lk = String(cur).toLowerCase();
                const rev = { '#c084fc': '#0f172a', '#a78bfa': '#334155', '#b8a9d9': '#94a3b8', '#2dd4bf': '#14b8a6', '#34d399': '#059669', '#38bdf8': '#0ea5e9', '#fb923c': '#f97316', '#fbbf24': '#f59e0b', '#fb7185': '#e11d48' };
                return rev[lk] || cur;
            })();
            const next = _mapDatasetColor(asLight);
            ds.borderColor = next;
            ds.pointBackgroundColor = next;
            ds.pointBorderColor = isDark ? '#1e1533' : '#ffffff';
            if (ds.type === 'bar') ds.backgroundColor = _hexToRgba(next, 0.55);
            else if (ds.fill) ds.backgroundColor = _hexToRgba(next, isDark ? 0.22 : (next.toLowerCase() === '#2dd4bf' ? 0.18 : 0.14));
            else ds.backgroundColor = ds.borderDash && ds.borderDash.length ? _hexToRgba(next, isDark ? 0.10 : 0.15) : 'transparent';
        });
        ch.options.plugins.legend.labels.color = _legendColor();
        ch.options.plugins.tooltip.backgroundColor = _tooltipBg();
        ch.options.plugins.tooltip.titleColor = isDark ? '#ede9f6' : '#ffffff';
        ch.options.plugins.tooltip.bodyColor = isDark ? '#d6cfe8' : '#e2e8f0';
        ch.options.plugins.tooltip.borderColor = isDark ? '#382a55' : 'transparent';
        ch.options.plugins.tooltip.borderWidth = isDark ? 1 : 0;
        const gridC = _gridColor(), tickC = _tickColor(), borderC = _borderColor();
        ['y', 'x', 'y1'].forEach((k) => {
            const sc = ch.options.scales[k];
            if (!sc) return;
            if (sc.grid) { if (k !== 'y1') sc.grid.color = gridC; }
            if (sc.ticks) sc.ticks.color = tickC;
            if (sc.border) sc.border.color = borderC;
        });
        ch.update();
    });
};
// juga jaga bila canvas ditambah dinamis (mis. Livewire) — observer ringan
try {
    const mo = new MutationObserver((muts) => {
        let need = false;
        muts.forEach((m) => m.addedNodes && m.addedNodes.forEach((n) => {
            if (n.querySelectorAll && n.querySelectorAll('canvas[data-chart]').length) need = true;
            if (n.matches && n.matches('canvas[data-chart]')) need = true;
        }));
        if (need) _renderCharts();
    });
    mo.observe(document.body, { childList: true, subtree: true });
} catch (e) {}


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
    window.addEventListener("load", () => navigator.serviceWorker.register("/sw.js").catch(() => { }));
}

/* Esc menutup panel/modal */
document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    document.getElementById('bell-panel')?.classList.add('hidden');
    document.getElementById('mobile-search-drawer')?.classList.add('hidden');
    document.querySelectorAll('.modal-shell:not(.hidden)').forEach((m) => m.classList.add('hidden'));
    sidebar.classList.remove('sbx-open'); document.getElementById('sb-overlay')?.classList.remove('show');
});

/* ===== nama file tampil saat pilih file upload ===== */
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
const fnameCss = document.createElement('style');
fnameCss.textContent = 'input.fname-own{color:transparent}';
document.head.appendChild(fnameCss);

/* ===== pratinjau Excel, Word, PPTX langsung di browser ===== */
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
            } else if (office.dataset.kind === 'pptx') {
                const { init } = await import('pptx-preview');
                const render = () => {
                    office.innerHTML = '';
                    const wrap = document.createElement('div');
                    wrap.style.width = '100%';
                    office.appendChild(wrap);
                    const cw = Math.max(320, wrap.clientWidth || office.clientWidth || 960);
                    const ch = Math.round(cw * 0.5625);
                    const previewer = init(wrap, { width: cw, height: ch, mode: 'slide' });
                    previewer.preview(buf);
                };
                render();
                const box = document.getElementById('preview-box') || office;
                let rT;
                const rerender = () => { clearTimeout(rT); rT = setTimeout(render, 90); };
                document.addEventListener('fullscreenchange', () => { if (document.fullscreenElement === box || !document.fullscreenElement) rerender(); });
                document.addEventListener('webkitfullscreenchange', () => rerender());
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

/* ===== dropdown pilihan custom bertema (pengganti <select> native) ===== */
(function () {
    const CHECK_SVG = '<svg class="h-4 w-4 shrink-0 text-purple-600 cs-check" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';

    function closeMenu(root) {
        root.querySelector('[data-cs-menu]')?.classList.add('hidden');
        root.querySelector('[data-cs-chevron]')?.classList.remove('rotate-180');
        root.classList.remove('is-open');
    }
    function openMenu(root) {
        // Tutup custom select lain yang sedang terbuka
        document.querySelectorAll('[data-custom-select].is-open').forEach(function (other) {
            if (other !== root) closeMenu(other);
        });
        root.querySelector('[data-cs-menu]')?.classList.remove('hidden');
        root.querySelector('[data-cs-chevron]')?.classList.add('rotate-180');
        root.classList.add('is-open');
    }

    document.querySelectorAll('[data-custom-select]').forEach(function (root) {
        const trigger = root.querySelector('[data-cs-trigger]');
        const menu = root.querySelector('[data-cs-menu]');
        const label = root.querySelector('[data-cs-label]');
        const input = root.querySelector('input[type=hidden]');
        const options = Array.from(root.querySelectorAll('[data-cs-option]'));
        if (!trigger || !menu || !input) return;

        const setActive = function (value) {
            input.value = value;
            options.forEach(function (opt) {
                const on = opt.dataset.value === value;
                opt.classList.toggle('bg-purple-50/90', on);
                opt.classList.toggle('text-purple-700', on);
                opt.classList.toggle('font-bold', on);
                opt.classList.toggle('text-slate-600', !on);
                const oldCheck = opt.querySelector('.cs-check');
                if (oldCheck) oldCheck.remove();
                if (on) {
                    opt.insertAdjacentHTML('beforeend', CHECK_SVG);
                    const span = opt.querySelector('span');
                    if (label && span) label.textContent = span.textContent.trim();
                }
            });
        };

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            menu.classList.contains('hidden') ? openMenu(root) : closeMenu(root);
        });

        options.forEach(function (opt) {
            opt.addEventListener('click', function (e) {
                e.stopPropagation();
                setActive(opt.dataset.value);
                closeMenu(root);
                if (root.dataset.autosubmit === '1' && input.form) {
                    if (input.form.requestSubmit) input.form.requestSubmit();
                    else input.form.submit();
                }
            });
        });

        window.addEventListener('resize', function () { closeMenu(root); });
    });

    document.addEventListener('click', function (e) {
        document.querySelectorAll('[data-custom-select].is-open').forEach(function (root) {
            const menu = root.querySelector('[data-cs-menu]');
            const trigger = root.querySelector('[data-cs-trigger]');
            if (menu && !menu.contains(e.target) && trigger && !trigger.contains(e.target)) closeMenu(root);
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('[data-custom-select].is-open').forEach(closeMenu);
    });
})();

/* ===== Mode gelap (nuansa ungu Ciamis) =====================================
   Kelas `dark` sudah dipasang lebih awal oleh skrip inline di <head> supaya
   tidak ada kedip putih. Di sini hanya tombol pengalih + penyimpanan pilihan.
   Pilihan tersimpan di localStorage; bila belum pernah memilih, mengikuti
   preferensi sistem. */
(function () {
    const akar = document.documentElement;

    function segarkanIkon() {
        const gelap = akar.classList.contains('dark');
        document.querySelectorAll('[data-tema-ikon]').forEach(function (el) {
            const untukGelap = el.dataset.temaIkon === 'gelap';
            el.classList.toggle('hidden', untukGelap !== gelap);
        });
        document.querySelectorAll('#tema-btn').forEach(function (b) {
            b.setAttribute('title', gelap ? 'Beralih ke mode terang' : 'Beralih ke mode gelap');
            b.setAttribute('aria-pressed', gelap ? 'true' : 'false');
        });
    }

    segarkanIkon();

    document.querySelectorAll('#tema-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const jadiGelap = !akar.classList.contains('dark');
            akar.classList.toggle('dark', jadiGelap);
            try {
                localStorage.setItem('tema', jadiGelap ? 'gelap' : 'terang');
            } catch (e) {}
            segarkanIkon();
            try { window._sipardisRefreshCharts && window._sipardisRefreshCharts(); } catch (e2) {}
        });
    });

    // Ikut berubah bila pengguna belum pernah memilih manual & tema sistem berganti
    try {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
            if (localStorage.getItem('tema')) return;
            akar.classList.toggle('dark', e.matches);
            segarkanIkon();
            try { window._sipardisRefreshCharts && window._sipardisRefreshCharts(); } catch (e2) {}
        });
    } catch (e) {}
    // sinkron chart bila kelas dark diubah dari luar (mis. skrip inline head)
    try {
        let _temaPrevDark = akar.classList.contains('dark');
        const _obsTema = new MutationObserver(function () {
            const cur = akar.classList.contains('dark');
            if (cur === _temaPrevDark) return;
            _temaPrevDark = cur;
            try { window._sipardisRefreshCharts && window._sipardisRefreshCharts(); } catch (e2) {}
        });
        _obsTema.observe(akar, { attributes: true, attributeFilter: ['class'] });
    } catch (e) {}
})();

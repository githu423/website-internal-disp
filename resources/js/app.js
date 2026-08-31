
import Chart from 'chart.js/auto';

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
    if (data.target) ds.push({ label: 'Target', data: data.target, borderColor: '#94a3b8', backgroundColor: 'rgba(148,163,184,.15)', borderWidth: 2, borderDash: [6, 4], tension: .35, fill: false });
    if (data.capaian) ds.push({ label: 'Capaian', data: data.capaian, borderColor: '#14b8a6', backgroundColor: 'rgba(20,184,166,.18)', borderWidth: 2, tension: .35, fill: true });
    new Chart(cv, { type, data: { labels: data.labels, datasets: ds },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true, position: 'top', labels: { boxWidth: 12, font: { size: 11 } } } }, scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } } } });
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

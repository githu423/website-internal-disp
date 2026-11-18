/* ===== pratinjau Excel, Word, PPTX langsung di browser ===== */
const office = document.getElementById('office-host');
if (office) {
    const esc = (x) => String(x).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    (async () => {
        try {
            const res = await fetch(office.dataset.src, { credentials: 'same-origin', headers: { Accept: 'application/octet-stream' } });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            if (res.redirected || (res.headers.get('content-type') || '').includes('text/html')) {
                throw new Error('Akses dokumen berubah. Kembali ke ruang rapat atau masuk ulang.');
            }
            const buf = await res.arrayBuffer();
            // ===== PRATINJAU XLSX / XLS =====
            if (office.dataset.kind === 'xlsx') {
                const XLSX = await import('xlsx');
                const wb = XLSX.read(buf, { type: 'array', cellStyles: true, sheetRows: 401 });
                const names = wb.SheetNames.slice(0, 10);
                if (!names.length) throw new Error('Spreadsheet tidak memiliki lembar yang dapat ditampilkan.');
                const sheetHtml = (name) => {
                    const ws = wb.Sheets[name];
                    if (!ws || !ws['!ref']) return '<p class="text-slate-400 text-sm">Sheet kosong.</p>';
                    const rg = XLSX.utils.decode_range(ws['!ref']);
                    const maxR = Math.min(rg.e.r, 400), maxC = Math.min(rg.e.c, 40);
                    const merges = ws['!merges'] || [], cov = {}, anc = {};
                    merges.forEach((m) => {
                        if (m.s.r > maxR || m.s.c > maxC) return;
                        const endR = Math.min(m.e.r, maxR), endC = Math.min(m.e.c, maxC);
                        anc[m.s.r + '_' + m.s.c] = { rs: endR - m.s.r + 1, cs: endC - m.s.c + 1 };
                        for (let r = m.s.r; r <= endR; r++) for (let c = m.s.c; c <= endC; c++) if (!(r === m.s.r && c === m.s.c)) cov[r + '_' + c] = 1;
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
                let tabs = '<p class="mb-2 text-xs text-slate-500">Pratinjau terbatas pada 10 lembar, 401 baris dan 41 kolom pertama per lembar.</p><div class="mb-2 flex flex-wrap gap-1">';
                names.forEach((n, ix) => { tabs += '<button data-i="' + ix + '" class="sheet-tab rounded-lg border px-2.5 py-1 text-[11.5px] font-bold ' + (ix ? 'border-slate-200 bg-white text-slate-500 hover:border-teal-400' : 'border-slate-900 bg-slate-900 text-white') + '">' + esc(n) + '</button>'; });
                tabs += '</div><div class="sheet-body overflow-x-auto thin-scroll rounded-lg border border-slate-200 bg-white"></div>';
                office.innerHTML = '<style>#office-host .xls{border-collapse:separate;border-spacing:0;font-size:12px;table-layout:fixed}#office-host .xls th,#office-host .xls td{border-right:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;padding:4px 8px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:360px;background:#fff}#office-host .xls thead th{position:sticky;top:0;z-index:2;background:#f1f5f9;color:#475569;font-weight:800;text-align:center;min-width:56px}#office-host .xls .rowhdr{position:sticky;left:0;z-index:1;background:#f1f5f9;color:#64748b;font-weight:700;text-align:center;min-width:38px}#office-host .xls td.num{text-align:right;font-variant-numeric:tabular-nums}#office-host .xls td{text-align:left}</style>' + tabs;
                const body = office.querySelector('.sheet-body');
                const show = (ix) => { body.innerHTML = sheetHtml(names[ix]); office.querySelectorAll('.sheet-tab').forEach((b, i2) => { const on = i2 === ix; b.className = b.className.replace(/border-slate-200 bg-white text-slate-500 hover:border-teal-400|border-slate-900 bg-slate-900 text-white/g, on ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-500 hover:border-teal-400'); }); };
                office.querySelectorAll('.sheet-tab').forEach((b) => b.addEventListener('click', () => show(+b.dataset.i)));
                show(0);
            // ===== PRATINJAU PPTX =====
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
            // ===== PRATINJAU DOCX =====
            } else {
                const { renderAsync } = await import('docx-preview');
                office.innerHTML = '<style>#office-host{background:#e2e8f0;padding:14px}#office-host .docx-wrapper{background:#e2e8f0;padding:0}#office-host .docx-wrapper>section.docx{box-shadow:0 4px 18px rgba(15,23,42,.14);margin-bottom:14px}</style><div id="docx-mount"></div>';
                await renderAsync(buf, document.getElementById('docx-mount'), undefined, { inWrapper: true, breakPages: true, ignoreLastRenderedPageBreak: false, renderHeaders: true, renderFooters: true, renderFootnotes: true, images: 'blob' });
            }
        } catch (e) {
            // ===== PRATINJAU GAGAL: BERI PESAN, JANGAN PAKSA UNDUH =====
            office.innerHTML = '<p class="text-sm text-rose-600">Pratinjau gagal: ' + esc(e.message || e) + ' Dokumen tidak diunduh otomatis. Coba muat ulang atau hubungi penyelenggara untuk versi PDF.</p>';
        }
    })();
}


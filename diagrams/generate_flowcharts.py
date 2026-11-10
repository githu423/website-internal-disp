import os
import subprocess
import re

DIAGRAMS_DIR = r"C:\laravel10\website-internal-disp\diagrams"
os.makedirs(DIAGRAMS_DIR, exist_ok=True)
EDGE_PATH = r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"

def svg_terminator(cx, cy, w, h, text):
    rx = h // 2
    return f'''
    <rect x="{cx - w//2}" y="{cy - h//2}" width="{w}" height="{h}" rx="{rx}" ry="{rx}" fill="#FFFFFF" stroke="#000000" stroke-width="1.8" />
    <text x="{cx}" y="{cy + 5}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-size="13" font-weight="bold" fill="#000000">{text}</text>
    '''

def svg_process(cx, cy, w, h, text):
    lines = text.split("\n")
    ts = []
    start_y = cy - (len(lines)-1)*8 + 4
    for i, line in enumerate(lines):
        ts.append(f'<tspan x="{cx}" y="{start_y + i*16}">{line}</tspan>')
    return f'''
    <rect x="{cx - w//2}" y="{cy - h//2}" width="{w}" height="{h}" fill="#FFFFFF" stroke="#000000" stroke-width="1.6" />
    <text x="{cx}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-size="12.5" fill="#000000">{''.join(ts)}</text>
    '''

def svg_io(cx, cy, w, h, text, offset=14):
    p1 = f"{cx - w//2 + offset},{cy - h//2}"
    p2 = f"{cx + w//2},{cy - h//2}"
    p3 = f"{cx + w//2 - offset},{cy + h//2}"
    p4 = f"{cx - w//2},{cy + h//2}"
    lines = text.split("\n")
    ts = []
    start_y = cy - (len(lines)-1)*8 + 4
    for i, line in enumerate(lines):
        ts.append(f'<tspan x="{cx}" y="{start_y + i*16}">{line}</tspan>')
    return f'''
    <polygon points="{p1} {p2} {p3} {p4}" fill="#FFFFFF" stroke="#000000" stroke-width="1.6" />
    <text x="{cx}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-size="12.5" fill="#000000">{''.join(ts)}</text>
    '''

def svg_decision(cx, cy, w, h, text):
    p1 = f"{cx},{cy - h//2}"
    p2 = f"{cx + w//2},{cy}"
    p3 = f"{cx},{cy + h//2}"
    p4 = f"{cx - w//2},{cy}"
    lines = text.split("\n")
    ts = []
    start_y = cy - (len(lines)-1)*7 + 4
    for i, line in enumerate(lines):
        ts.append(f'<tspan x="{cx}" y="{start_y + i*15}">{line}</tspan>')
    return f'''
    <polygon points="{p1} {p2} {p3} {p4}" fill="#FFFFFF" stroke="#000000" stroke-width="1.6" />
    <text x="{cx}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-size="12" font-weight="bold" fill="#000000">{''.join(ts)}</text>
    '''

def svg_arrow(x1, y1, x2, y2, label=None, lx=None, ly=None):
    res = f'<line x1="{x1}" y1="{y1}" x2="{x2}" y2="{y2}" stroke="#000000" stroke-width="1.4" marker-end="url(#arrow)" />'
    if label and lx and ly:
        res += f'<text x="{lx}" y="{ly}" font-family="Times New Roman" font-size="11.5" font-weight="bold" fill="#000000">{label}</text>'
    return res

def svg_path_arrow(d, label=None, lx=None, ly=None):
    res = f'<path d="{d}" stroke="#000000" stroke-width="1.4" fill="none" marker-end="url(#arrow)" />'
    if label and lx and ly:
        res += f'<text x="{lx}" y="{ly}" font-family="Times New Roman" font-size="11.5" font-weight="bold" fill="#000000">{label}</text>'
    return res

def wrap_svg(w, h, title, subtitle, content):
    return f'''<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * {{ margin: 0; padding: 0; box-sizing: border-box; }}
        body {{
            background: #FFFFFF;
            font-family: 'Times New Roman', Times, serif;
            display: flex;
            justify-content: center;
            align-items: center;
            width: {w}px;
            height: {h}px;
        }}
    </style>
</head>
<body>
    <svg width="{w}" height="{h}" viewBox="0 0 {w} {h}" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <marker id="arrow" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="6" markerHeight="6" orient="auto">
                <path d="M 0 1 L 10 5 L 0 9 z" fill="#000000" />
            </marker>
        </defs>

        <!-- System Boundary Box -->
        <rect x="25" y="25" width="{w - 50}" height="{h - 50}" fill="#FFFFFF" stroke="#000000" stroke-width="1.6" />
        
        <!-- Header -->
        <text x="45" y="55" font-family="'Times New Roman', Times, serif" font-size="12" fill="#000000">«flowchart»</text>
        <text x="45" y="75" font-family="'Times New Roman', Times, serif" font-size="16" font-weight="bold" fill="#000000">{title}</text>
        <text x="45" y="94" font-family="'Times New Roman', Times, serif" font-size="12" font-style="italic" fill="#444444">{subtitle}</text>

        <!-- Content Elements -->
        {content}

        <!-- Bottom Caption -->
        <text x="{w // 2}" y="{h - 12}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-style="italic" font-size="12" fill="#000000">
            {title} - Website Internal Dinas Pariwisata
        </text>
    </svg>
</body>
</html>
'''

# ----------------- 1. FLOWCHART AUTH / LOGIN MULTI-ROLE -----------------
def generate_flowchart_auth():
    w, h = 820, 880
    c = []
    cx = 380
    
    # Elements
    c.append(svg_terminator(cx, 130, 140, 36, "Mulai"))
    c.append(svg_arrow(cx, 148, cx, 185))
    
    c.append(svg_process(cx, 205, 200, 40, "Menampilkan Halaman Login"))
    c.append(svg_arrow(cx, 225, cx, 260))
    
    c.append(svg_io(cx, 280, 220, 40, "Input NIP & Password"))
    c.append(svg_arrow(cx, 300, cx, 340))
    
    c.append(svg_decision(cx, 380, 180, 80, "Validasi Akun\n& Status Aktif?"))
    
    # If No: Loop back
    c.append(svg_path_arrow(f"M {cx + 90} 380 L 620 380 L 620 205 L {cx + 100} 205", label="Tidak (Gagal)", lx=485, ly=372))
    
    # If Yes: Check Role
    c.append(svg_arrow(cx, 420, cx, 475, label="Ya", lx=390, ly=445))
    c.append(svg_decision(cx, 515, 180, 80, "Pemeriksaan\nRole User?"))
    
    # Role branches
    # Left: Admin
    c.append(svg_path_arrow(f"M {cx - 90} 515 L 170 515 L 170 610", label="Admin", lx=230, ly=508))
    c.append(svg_process(170, 635, 160, 50, "Akses Penuh Menu\n& Dashboard Admin"))
    
    # Center: Bidang
    c.append(svg_arrow(cx, 555, cx, 610, label="Bidang", lx=cx + 8, ly=580))
    c.append(svg_process(cx, 635, 170, 50, "Akses Menu Terkait\nBidang Sendiri"))
    
    # Right: Pegawai
    c.append(svg_path_arrow(f"M {cx + 90} 515 L 590 515 L 590 610", label="Pegawai", lx=490, ly=508))
    c.append(svg_process(590, 635, 160, 50, "Akses Menu Publik\n& Read-Only"))
    
    # Join into Selesai
    c.append(svg_path_arrow(f"M 170 660 L 170 730 L {cx - 70} 730 L {cx - 70} 780"))
    c.append(svg_path_arrow(f"M {cx} 660 L {cx} 780"))
    c.append(svg_path_arrow(f"M 590 660 L 590 730 L {cx + 70} 730 L {cx + 70} 780"))
    
    c.append(svg_terminator(cx, 800, 140, 38, "Selesai"))
    
    html = wrap_svg(w, h, "Flowchart Autentikasi & Otorisasi Pengguna", "Alur proses masuk sistem (login) dan percabangan hak akses per role", ''.join(c))
    return html, w, h

# ----------------- 2. FLOWCHART RUANG RAPAT & TAMU -----------------
def generate_flowchart_rapat():
    w, h = 880, 940
    c = []
    cx = 400
    
    c.append(svg_terminator(cx, 130, 140, 36, "Mulai"))
    c.append(svg_arrow(cx, 148, cx, 185))
    
    c.append(svg_process(cx, 210, 240, 50, "Admin/Bidang Mengisi Form\nPengajuan Ruang Rapat"))
    c.append(svg_arrow(cx, 235, cx, 275))
    
    c.append(svg_decision(cx, 315, 200, 80, "Pilih Jadwal\n& Status Bentrok?"))
    
    # If bentrok
    c.append(svg_path_arrow(f"M {cx + 100} 315 L 670 315 L 670 210 L {cx + 120} 210", label="Ya (Bentrok)", lx=520, ly=307))
    
    # If not bentrok
    c.append(svg_arrow(cx, 355, cx, 400, label="Tidak", lx=cx + 8, ly=375))
    
    c.append(svg_process(cx, 425, 250, 50, "Sistem Menyimpan Data Rapat,\nGenerate Token & PIN Unik"))
    c.append(svg_arrow(cx, 450, cx, 490))
    
    c.append(svg_process(cx, 515, 240, 50, "Admin/Bidang Mengunggah\nMateri Dokumen Rapat"))
    c.append(svg_arrow(cx, 540, cx, 580))
    
    c.append(svg_io(cx, 605, 250, 50, "Tautan Rapat Dibagikan\nkepada Peserta Rapat / Tamu"))
    c.append(svg_arrow(cx, 630, cx, 670))
    
    c.append(svg_process(cx, 695, 250, 50, "Tamu Mengakses Tautan,\nInput PIN & Presensi Kehadiran"))
    c.append(svg_arrow(cx, 720, cx, 760))
    
    c.append(svg_process(cx, 785, 240, 50, "Tamu Melihat & Mengunduh\nMateri / Notulen Rapat"))
    c.append(svg_arrow(cx, 810, cx, 850))
    
    c.append(svg_terminator(cx, 870, 140, 38, "Selesai"))
    
    html = wrap_svg(w, h, "Flowchart Peminjaman Ruang Rapat & Akses Tamu", "Alur pengajuan jadwal rapat, upload materi, serta presensi kehadiran tamu", ''.join(c))
    return html, w, h

# ----------------- 3. FLOWCHART MANAJEMEN DOKUMEN -----------------
def generate_flowchart_dokumen():
    w, h = 840, 920
    c = []
    cx = 380
    
    c.append(svg_terminator(cx, 130, 140, 36, "Mulai"))
    c.append(svg_arrow(cx, 148, cx, 185))
    
    c.append(svg_process(cx, 210, 240, 50, "User Membuka Modul Dokumen\n(Pencarian / Pengelolaan)"))
    c.append(svg_arrow(cx, 235, cx, 275))
    
    c.append(svg_decision(cx, 315, 180, 80, "Aksi yang\nDipilih?"))
    
    # Branch 1: Upload (Kiri)
    c.append(svg_path_arrow(f"M {cx - 90} 315 L 170 315 L 170 380", label="Upload", lx=220, ly=307))
    c.append(svg_decision(170, 420, 160, 80, "Role Admin\natau Bidang?"))
    
    # Upload allowed
    c.append(svg_arrow(170, 460, 170, 510, label="Ya", lx=180, ly=485))
    c.append(svg_io(170, 535, 170, 50, "Unggah File Dokumen\n& Isi Metadata"))
    c.append(svg_arrow(170, 560, 170, 610))
    c.append(svg_process(170, 635, 170, 50, "Simpan ke Storage Public\n& Catat ke Database"))
    
    # Upload not allowed
    c.append(svg_path_arrow(f"M 90 420 L 50 420 L 50 740 L {cx - 70} 740", label="Bukan (Ditolak 403)", lx=30, ly=500))
    
    # Branch 2: Unduh / Lihat (Kanan)
    c.append(svg_path_arrow(f"M {cx + 90} 315 L 590 315 L 590 380", label="Unduh/Lihat", lx=460, ly=307))
    c.append(svg_process(590, 405, 180, 50, "User Memilih Dokumen\ndari Daftar / Filter"))
    c.append(svg_arrow(590, 430, 590, 470))
    
    c.append(svg_decision(590, 510, 170, 80, "Cek Visibility\n(Publik/Bidang)?"))
    c.append(svg_arrow(590, 550, 590, 600, label="Diizinkan", lx=600, ly=575))
    c.append(svg_process(590, 625, 180, 50, "Sistem Mengirimkan File\n& Update Counter Baca"))
    
    # Denied visibility
    c.append(svg_path_arrow(f"M 675 510 L 730 510 L 730 740 L {cx + 70} 740", label="Akses Terbatas", lx=680, ly=495))
    
    # Connect to Selesai
    c.append(svg_path_arrow(f"M 170 660 L 170 740 L {cx - 70} 740 L {cx - 70} 800"))
    c.append(svg_path_arrow(f"M 590 650 L 590 740 L {cx + 70} 740 L {cx + 70} 800"))
    
    c.append(svg_terminator(cx, 820, 140, 38, "Selesai"))
    
    html = wrap_svg(w, h, "Flowchart Manajemen & Akses Dokumen", "Alur verifikasi upload berkas dinas dan kontrol hak akses unduh dokumen", ''.join(c))
    return html, w, h

# ----------------- 4. FLOWCHART KESELURUHAN SISTEM -----------------
def generate_flowchart_keseluruhan():
    w, h = 900, 960
    c = []
    cx = 450
    
    c.append(svg_terminator(cx, 130, 140, 36, "Mulai"))
    c.append(svg_arrow(cx, 148, cx, 185))
    
    c.append(svg_process(cx, 205, 220, 40, "Login Akun Pengguna"))
    c.append(svg_arrow(cx, 225, cx, 265))
    
    c.append(svg_process(cx, 290, 240, 50, "Masuk Halaman Dashboard Utama\n(Statistik, PAD, Agenda, Dokumen)"))
    c.append(svg_arrow(cx, 315, cx, 360))
    
    c.append(svg_decision(cx, 400, 200, 80, "Pilih Modul\nOperasional?"))
    
    # 4 Columns of features
    # 1. Admin Master (Far left: 130)
    c.append(svg_path_arrow(f"M {cx - 100} 400 L 130 400 L 130 475", label="Admin Only", lx=220, ly=390))
    c.append(svg_process(130, 510, 150, 70, "Kelola Master User,\nData Pegawai, PAD,\nKinerja & Log"))
    
    # 2. Dokumen & Agenda (Mid-left: 330)
    c.append(svg_path_arrow(f"M {cx - 50} 430 L 330 450 L 330 475"))
    c.append(svg_process(330, 510, 150, 70, "Kelola Dokumen\n& Jadwal Agenda\n(Bidang / Admin)"))
    
    # 3. Ruang Rapat (Mid-right: 570)
    c.append(svg_path_arrow(f"M {cx + 50} 430 L 570 450 L 570 475"))
    c.append(svg_process(570, 510, 150, 70, "Peminjaman Ruang\nRapat & Akses Tamu\n(Admin / Bidang)"))
    
    # 4. Layanan Pegawai (Far right: 770)
    c.append(svg_path_arrow(f"M {cx + 100} 400 L 770 400 L 770 475", label="Semua User", lx=620, ly=390))
    c.append(svg_process(770, 510, 150, 70, "Lihat Data Publik,\nProfil, Notifikasi\n& Unduh Dokumen"))
    
    # Convergence: Simpan / Update database
    c.append(svg_path_arrow(f"M 130 545 L 130 635 L {cx - 110} 635 L {cx - 110} 670"))
    c.append(svg_path_arrow(f"M 330 545 L 330 635 L {cx - 40} 635 L {cx - 40} 670"))
    c.append(svg_path_arrow(f"M 570 545 L 570 635 L {cx + 40} 635 L {cx + 40} 670"))
    c.append(svg_path_arrow(f"M 770 545 L 770 635 L {cx + 110} 635 L {cx + 110} 670"))
    
    c.append(svg_process(cx, 695, 260, 50, "Sistem Memproses Transaksi\n& Memperbarui Basis Data"))
    c.append(svg_arrow(cx, 720, cx, 765))
    
    c.append(svg_decision(cx, 805, 190, 80, "Lanjutkan Aktivitas\natau Keluar?"))
    c.append(svg_path_arrow(f"M {cx + 95} 805 L 850 805 L 850 290 L {cx + 120} 290", label="Lanjut", lx=800, ly=795))
    
    c.append(svg_arrow(cx, 845, cx, 890, label="Logout", lx=cx + 8, ly=865))
    c.append(svg_terminator(cx, 910, 140, 38, "Selesai"))
    
    html = wrap_svg(w, h, "Flowchart Alur Kerja Sistem Keseluruhan", "Arsitektur alur kerja menyeluruh Website Internal Dinas Pariwisata", ''.join(c))
    return html, w, h

# ----------------- EXECUTE ALL -----------------
flowcharts = [
    ("flowchart_keseluruhan", generate_flowchart_keseluruhan),
    ("flowchart_autentikasi", generate_flowchart_auth),
    ("flowchart_ruang_rapat", generate_flowchart_rapat),
    ("flowchart_dokumen", generate_flowchart_dokumen),
]

for base_name, gen_func in flowcharts:
    html_content, w, h = gen_func()
    html_path = os.path.join(DIAGRAMS_DIR, f"{base_name}.html")
    svg_path = os.path.join(DIAGRAMS_DIR, f"{base_name}.svg")
    png_path = os.path.join(DIAGRAMS_DIR, f"{base_name}.png")
    
    with open(html_path, "w", encoding="utf-8") as f:
        f.write(html_content)
        
    match = re.search(r'(<svg.*?</svg>)', html_content, re.DOTALL)
    if match:
        with open(svg_path, "w", encoding="utf-8") as f:
            f.write('<?xml version="1.0" encoding="UTF-8"?>\n' + match.group(1))
            
    cmd = [
        EDGE_PATH,
        "--headless=new",
        "--disable-gpu",
        f"--window-size={w},{h}",
        f"--screenshot={png_path}",
        f"file:///{html_path.replace(os.sep, '/')}"
    ]
    print(f"Rendering {base_name}.png ({w}x{h})...")
    subprocess.run(cmd, capture_output=True)

print("SUCCESS: All flowcharts generated in SVG, PNG, and HTML!")

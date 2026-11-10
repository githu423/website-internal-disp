import os
import subprocess

DIAGRAMS_DIR = r"C:\laravel10\website-internal-disp\diagrams"
os.makedirs(DIAGRAMS_DIR, exist_ok=True)
EDGE_PATH = r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"

def render_stick_figure(cx, cy, name):
    return f'''
    <!-- Actor: {name} -->
    <g class="actor">
        <circle cx="{cx}" cy="{cy - 35}" r="14" fill="#FFFFFF" stroke="#000000" stroke-width="1.8" />
        <line x1="{cx}" y1="{cy - 21}" x2="{cx}" y2="{cy + 16}" stroke="#000000" stroke-width="1.8" />
        <line x1="{cx - 24}" y1="{cy - 8}" x2="{cx + 24}" y2="{cy - 8}" stroke="#000000" stroke-width="1.8" />
        <line x1="{cx}" y1="{cy + 16}" x2="{cx - 16}" y2="{cy + 50}" stroke="#000000" stroke-width="1.8" />
        <line x1="{cx}" y1="{cy + 16}" x2="{cx + 16}" y2="{cy + 50}" stroke="#000000" stroke-width="1.8" />
        <text x="{cx}" y="{cy + 72}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-size="15" font-weight="bold" fill="#000000">
            {name}
        </text>
    </g>
    '''

def create_single_actor_diagram(actor_name, use_cases, title_sub="Website Internal Dinas Pariwisata"):
    n = len(use_cases)
    # Height dynamically adjusted so ovals never crowd or overlap
    height = max(700, 120 + n * 58)
    width = 780
    
    actor_x = 90
    actor_y = height // 2
    
    box_x = 200
    box_y = 35
    box_w = 520
    box_h = height - 80
    
    oval_cx = box_x + box_w // 2
    oval_rx = 125
    oval_ry = 22
    
    start_y = box_y + 75
    end_y = box_y + box_h - 40
    step_y = (end_y - start_y) / (n - 1) if n > 1 else 0
    
    lines_svg = []
    ovals_svg = []
    
    for i, uc in enumerate(use_cases):
        cy = int(start_y + i * step_y)
        
        # Line from actor to left boundary of oval
        lines_svg.append(f'<line x1="{actor_x + 24}" y1="{actor_y}" x2="{oval_cx - oval_rx}" y2="{cy}" stroke="#000000" stroke-width="1.2" />')
        
        ovals_svg.append(f'''
        <g class="use-case">
            <ellipse cx="{oval_cx}" cy="{cy}" rx="{oval_rx}" ry="{oval_ry}" fill="#FFFFFF" stroke="#000000" stroke-width="1.5" />
            <text x="{oval_cx}" y="{cy + 5}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-size="13.5" font-weight="bold" fill="#000000">
                {uc}
            </text>
        </g>
        ''')
        
    actor_svg = render_stick_figure(actor_x, actor_y, actor_name)
    caption = f"Use Case Diagram {actor_name} - {title_sub}"
    
    html = f'''<!DOCTYPE html>
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
            width: {width}px;
            height: {height + 30}px;
        }}
    </style>
</head>
<body>
    <svg width="{width}" height="{height + 30}" viewBox="0 0 {width} {height + 30}" xmlns="http://www.w3.org/2000/svg">
        <!-- System Boundary Box -->
        <rect x="{box_x}" y="{box_y}" width="{box_w}" height="{box_h}" fill="#FFFFFF" stroke="#000000" stroke-width="1.5" />
        
        <!-- System Title at Top-Left of Box -->
        <text x="{box_x + 18}" y="{box_y + 24}" font-family="'Times New Roman', Times, serif" font-size="12" fill="#000000">«subsystem»</text>
        <text x="{box_x + 18}" y="{box_y + 44}" font-family="'Times New Roman', Times, serif" font-size="15" font-weight="bold" fill="#000000">Website Internal Dispar</text>
        
        <!-- Association Lines -->
        {''.join(lines_svg)}
        
        <!-- Actor -->
        {actor_svg}
        
        <!-- Use Cases -->
        {''.join(ovals_svg)}
        
        <!-- Bottom Caption -->
        <text x="{width // 2}" y="{height + 15}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-style="italic" font-size="12" fill="#000000">
            {caption}
        </text>
    </svg>
</body>
</html>
'''
    return html, width, height + 30

# ----------------- KESELURUHAN (ALL 3 ROLES) -----------------
def create_all_roles_diagram():
    width = 960
    height = 980
    
    # Left: Admin & Bidang | Right: Pegawai
    admin_x = 95
    admin_y = 280
    
    bidang_x = 95
    bidang_y = 660
    
    pegawai_x = width - 95
    pegawai_y = 480
    
    box_x = 210
    box_y = 35
    box_w = 540
    box_h = height - 70
    
    oval_cx = box_x + box_w // 2
    oval_rx = 135
    oval_ry = 21
    
    # 14 Essential Use Cases covering the entire system
    cases = [
        ("Login & Logout", ["Admin", "Bidang", "Pegawai"]),
        ("Kelola Profil & Sandi", ["Admin", "Bidang", "Pegawai"]),
        ("Akses Dashboard", ["Admin", "Bidang", "Pegawai"]),
        ("Kelola Data Dokumen", ["Admin", "Bidang"]),
        ("Lihat & Unduh Dokumen", ["Admin", "Bidang", "Pegawai"]),
        ("Kelola Agenda Kegiatan", ["Admin", "Bidang"]),
        ("Lihat Jadwal Agenda", ["Pegawai"]),
        ("Peminjaman Ruang Rapat", ["Admin", "Bidang"]),
        ("Lihat Data Pegawai", ["Admin", "Bidang", "Pegawai"]),
        ("Lihat Kinerja, Statistik & PAD", ["Admin", "Bidang", "Pegawai"]),
        ("Kelola Informasi Bidang", ["Admin", "Bidang"]),
        ("Lihat & Baca Notifikasi", ["Admin", "Bidang", "Pegawai"]),
        ("Kelola Data Master & Import", ["Admin"]),
        ("Kelola Akun & Log Aktivitas", ["Admin"]),
    ]
    
    n = len(cases)
    start_y = box_y + 75
    end_y = box_y + box_h - 40
    step_y = (end_y - start_y) / (n - 1)
    
    lines_svg = []
    ovals_svg = []
    
    for i, (uc, roles) in enumerate(cases):
        cy = int(start_y + i * step_y)
        
        if "Admin" in roles:
            lines_svg.append(f'<line x1="{admin_x + 24}" y1="{admin_y}" x2="{oval_cx - oval_rx}" y2="{cy}" stroke="#000000" stroke-width="1.2" />')
        if "Bidang" in roles:
            lines_svg.append(f'<line x1="{bidang_x + 24}" y1="{bidang_y}" x2="{oval_cx - oval_rx}" y2="{cy}" stroke="#000000" stroke-width="1.2" />')
        if "Pegawai" in roles:
            lines_svg.append(f'<line x1="{pegawai_x - 24}" y1="{pegawai_y}" x2="{oval_cx + oval_rx}" y2="{cy}" stroke="#000000" stroke-width="1.2" />')
            
        ovals_svg.append(f'''
        <g class="use-case">
            <ellipse cx="{oval_cx}" cy="{cy}" rx="{oval_rx}" ry="{oval_ry}" fill="#FFFFFF" stroke="#000000" stroke-width="1.5" />
            <text x="{oval_cx}" y="{cy + 5}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-size="13" font-weight="bold" fill="#000000">
                {uc}
            </text>
        </g>
        ''')
        
    admin_svg = render_stick_figure(admin_x, admin_y, "Admin")
    bidang_svg = render_stick_figure(bidang_x, bidang_y, "Bidang")
    pegawai_svg = render_stick_figure(pegawai_x, pegawai_y, "Pegawai")
    caption = "Use Case Diagram Lengkap - Website Internal Dinas Pariwisata"
    
    html = f'''<!DOCTYPE html>
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
            width: {width}px;
            height: {height + 30}px;
        }}
    </style>
</head>
<body>
    <svg width="{width}" height="{height + 30}" viewBox="0 0 {width} {height + 30}" xmlns="http://www.w3.org/2000/svg">
        <rect x="{box_x}" y="{box_y}" width="{box_w}" height="{box_h}" fill="#FFFFFF" stroke="#000000" stroke-width="1.5" />
        
        <text x="{box_x + 18}" y="{box_y + 24}" font-family="'Times New Roman', Times, serif" font-size="12" fill="#000000">«subsystem»</text>
        <text x="{box_x + 18}" y="{box_y + 44}" font-family="'Times New Roman', Times, serif" font-size="15" font-weight="bold" fill="#000000">Website Internal Dispar</text>
        
        {''.join(lines_svg)}
        
        {admin_svg}
        {bidang_svg}
        {pegawai_svg}
        
        {''.join(ovals_svg)}
        
        <text x="{width // 2}" y="{height + 15}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-style="italic" font-size="12" fill="#000000">
            {caption}
        </text>
    </svg>
</body>
</html>
'''
    return html, width, height + 30

# ----------------- DATA FOR website-internal-disp -----------------

admin_cases = [
    "Login & Logout",
    "Kelola Profil & Ganti Password",
    "Akses Dashboard Admin",
    "Kelola Pengguna & Sinkron Peran",
    "Kelola Data Pegawai & Import",
    "Kelola Semua Dokumen",
    "Kelola Agenda Kegiatan",
    "Kelola Peminjaman Ruang Rapat",
    "Kelola Kinerja & Sasaran/Indikator",
    "Kelola Statistik Pariwisata & PAD",
    "Kelola Prestasi & Featured",
    "Lihat Log Aktivitas Sistem",
    "Lihat & Baca Notifikasi",
]

bidang_cases = [
    "Login & Logout",
    "Kelola Profil & Ganti Password",
    "Akses Dashboard",
    "Kelola Profil & Anggota Bidang",
    "Kelola Dokumen Bidang",
    "Lihat & Unduh Dokumen",
    "Kelola Agenda Bidang",
    "Kelola & Pinjam Ruang Rapat",
    "Upload Notulen Rapat",
    "Lihat Kinerja, Statistik & PAD",
    "Lihat Daftar Pegawai",
    "Lihat & Baca Notifikasi",
]

pegawai_cases = [
    "Login & Logout",
    "Kelola Profil Pribadi & Ganti Password",
    "Akses Dashboard",
    "Lihat & Unduh Dokumen Publik",
    "Lihat Jadwal Agenda Kegiatan",
    "Lihat Profil & Anggota Bidang",
    "Lihat Daftar Pegawai",
    "Lihat Data Kinerja & Statistik",
    "Lihat Data PAD Retribusi",
    "Lihat & Baca Notifikasi",
]

# Generate and Render
diagrams = [
    ("use_case_admin", "Admin", admin_cases),
    ("use_case_bidang", "Bidang", bidang_cases),
    ("use_case_pegawai", "Pegawai", pegawai_cases),
]

for file_base, actor_name, cases in diagrams:
    html_content, w, h = create_single_actor_diagram(actor_name, cases)
    html_path = os.path.join(DIAGRAMS_DIR, f"{file_base}.html")
    png_path = os.path.join(DIAGRAMS_DIR, f"{file_base}.png")
    
    with open(html_path, "w", encoding="utf-8") as f:
        f.write(html_content)
        
    cmd = [
        EDGE_PATH,
        "--headless=new",
        "--disable-gpu",
        f"--window-size={w},{h}",
        f"--screenshot={png_path}",
        f"file:///{html_path.replace(os.sep, '/')}"
    ]
    print(f"Rendering {file_base}.png...")
    subprocess.run(cmd, capture_output=True)

# Generate All Roles
html_all, w_all, h_all = create_all_roles_diagram()
html_all_path = os.path.join(DIAGRAMS_DIR, "use_case_keseluruhan.html")
png_all_path = os.path.join(DIAGRAMS_DIR, "use_case_keseluruhan.png")

with open(html_all_path, "w", encoding="utf-8") as f:
    f.write(html_all)

cmd_all = [
    EDGE_PATH,
    "--headless=new",
    "--disable-gpu",
    f"--window-size={w_all},{h_all}",
    f"--screenshot={png_all_path}",
    f"file:///{html_all_path.replace(os.sep, '/')}"
]
print("Rendering use_case_keseluruhan.png...")
subprocess.run(cmd_all, capture_output=True)

print("SUCCESS: All diagrams created and saved in website-internal-disp/diagrams")

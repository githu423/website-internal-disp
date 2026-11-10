import os
import subprocess

DIAGRAMS_DIR = r"C:\laravel10\website-internal-disp\diagrams"
os.makedirs(DIAGRAMS_DIR, exist_ok=True)
EDGE_PATH = r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"

def render_entity(x, y, w, h, name):
    return f'''
    <rect x="{x - w/2}" y="{y - h/2}" width="{w}" height="{h}" rx="2" fill="#FFFFFF" stroke="#000000" stroke-width="2" />
    <text x="{x}" y="{y + 6}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-size="15" font-weight="bold" fill="#000000">{name}</text>
    '''

def render_relation(x, y, w, h, name):
    # Diamond: top, right, bottom, left
    pts = f"{x},{y - h/2} {x + w/2},{y} {x},{y + h/2} {x - w/2},{y}"
    return f'''
    <polygon points="{pts}" fill="#FFFFFF" stroke="#000000" stroke-width="1.8" />
    <text x="{x}" y="{y + 5}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-size="12" font-weight="bold" fill="#000000">{name}</text>
    '''

def render_attribute(cx, cy, rx, ry, name, attr_type="regular", parent_x=0, parent_y=0):
    # Line to parent entity
    line = f'<line x1="{parent_x}" y1="{parent_y}" x2="{cx}" y2="{cy}" stroke="#000000" stroke-width="1.1" />'
    
    # Text decoration
    u_open = '<tspan text-decoration="underline">' if attr_type == "pk" else '<tspan>'
    u_close = '</tspan>'
    
    # Dash array if foreign key
    stroke_dash = 'stroke-dasharray="4,3"' if attr_type == "fk" else ''
    
    oval = f'''
    <ellipse cx="{cx}" cy="{cy}" rx="{rx}" ry="{ry}" fill="#FFFFFF" stroke="#000000" stroke-width="1.3" {stroke_dash} />
    <text x="{cx}" y="{cy + 4}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-size="11.5" fill="#000000">
        {u_open}{name}{u_close}
    </text>
    '''
    return line + oval

def create_chen_erd():
    width = 2400
    height = 1750
    
    # Entities Layout (Coordinates cx, cy)
    entities = {
        "BIDANG": {"x": 1200, "y": 280, "w": 160, "h": 50},
        "USERS": {"x": 1200, "y": 700, "w": 160, "h": 50},
        "PEGAWAI": {"x": 550, "y": 280, "w": 160, "h": 50},
        "DOKUMEN": {"x": 1850, "y": 700, "w": 170, "h": 50},
        "AGENDA": {"x": 550, "y": 700, "w": 160, "h": 50},
        "RUANG_RAPAT": {"x": 1850, "y": 1200, "w": 180, "h": 50},
        "LOG_AKTIVITAS": {"x": 750, "y": 1200, "w": 180, "h": 50},
        "NOTIFIKASI": {"x": 1200, "y": 1200, "w": 160, "h": 50},
    }
    
    # Attributes definition per entity
    # (name, type: 'pk' | 'fk' | 'regular', offset_x, offset_y, rx, ry)
    attributes = {
        "BIDANG": [
            ("id", "pk", -140, -90, 35, 18),
            ("kode", "regular", -70, -110, 42, 18),
            ("nama", "regular", 10, -120, 48, 18),
            ("ketua", "regular", 90, -110, 45, 18),
            ("deskripsi", "regular", 150, -80, 50, 18),
        ],
        "USERS": [
            ("id", "pk", -140, -70, 35, 18),
            ("name", "regular", -150, 0, 42, 18),
            ("nip", "regular", -140, 70, 40, 18),
            ("password", "regular", 140, -70, 50, 18),
            ("role", "regular", 150, 0, 40, 18),
            ("id_bidang", "fk", 140, 70, 52, 18),
            ("status", "regular", 0, 80, 42, 18),
        ],
        "PEGAWAI": [
            ("id", "pk", -130, -90, 35, 18),
            ("nip", "regular", -60, -110, 40, 18),
            ("nama", "regular", 20, -120, 45, 18),
            ("jabatan", "regular", 100, -100, 48, 18),
            ("pangkat_gol", "regular", -140, 0, 60, 18),
            ("jenis_asn", "regular", -140, 70, 52, 18),
            ("id_bidang", "fk", -70, 100, 52, 18),
            ("status", "regular", 10, 105, 42, 18),
        ],
        "DOKUMEN": [
            ("id", "pk", 140, -100, 35, 18),
            ("judul", "regular", 70, -125, 45, 18),
            ("kategori", "regular", -10, -130, 48, 18),
            ("visibility", "regular", -90, -120, 55, 18),
            ("file_path", "regular", 150, -30, 55, 18),
            ("file_size", "regular", 155, 40, 52, 18),
            ("id_bidang", "fk", 140, 105, 52, 18),
            ("uploaded_by", "fk", 60, 115, 62, 18),
        ],
        "AGENDA": [
            ("id", "pk", -130, -90, 35, 18),
            ("judul", "regular", -60, -115, 45, 18),
            ("tanggal", "regular", 20, -120, 48, 18),
            ("jam_mulai", "regular", 100, -100, 52, 18),
            ("lokasi", "regular", -140, 0, 45, 18),
            ("status", "regular", -140, 70, 42, 18),
            ("id_bidang", "fk", -70, 100, 52, 18),
            ("created_by", "fk", 20, 105, 58, 18),
        ],
        "RUANG_RAPAT": [
            ("id", "pk", -150, -70, 35, 18),
            ("judul", "regular", -140, 10, 45, 18),
            ("tanggal_rapat", "regular", -140, 80, 62, 18),
            ("lokasi", "regular", -60, 120, 45, 18),
            ("token", "regular", 20, 125, 45, 18),
            ("status", "regular", 100, 115, 42, 18),
            ("id_bidang", "fk", 155, 60, 52, 18),
            ("dibuat_oleh", "fk", 155, -20, 60, 18),
        ],
        "LOG_AKTIVITAS": [
            ("id", "pk", -130, -80, 35, 18),
            ("user_id", "fk", -50, -100, 48, 18),
            ("nama", "regular", 30, -105, 45, 18),
            ("aksi", "regular", 110, -85, 42, 18),
            ("ip", "regular", -130, 40, 35, 18),
            ("created_at", "regular", -60, 90, 55, 18),
        ],
        "NOTIFIKASI": [
            ("id", "pk", -130, -70, 35, 18),
            ("user_id", "fk", -60, -95, 48, 18),
            ("judul", "regular", 20, -100, 45, 18),
            ("tipe", "regular", 100, -80, 40, 18),
            ("dibaca_at", "regular", -70, 80, 55, 18),
            ("url", "regular", 20, 85, 38, 18),
        ],
    }
    
    # Relationships (Chen notation: Diamond between entities)
    # (name, entity1, entity2, card1, card2, rx, ry)
    relationships = [
        # BIDANG - PEGAWAI: "Memiliki"
        {"name": "Memiliki", "e1": "BIDANG", "e2": "PEGAWAI", "x": 875, "y": 280, "c1": "1", "c2": "N", "w": 110, "h": 50},
        # BIDANG - USERS: "Menaungi"
        {"name": "Menaungi", "e1": "BIDANG", "e2": "USERS", "x": 1200, "y": 490, "c1": "1", "c2": "N", "w": 110, "h": 50},
        # BIDANG - DOKUMEN: "Kepemilikan"
        {"name": "Memiliki", "e1": "BIDANG", "e2": "DOKUMEN", "x": 1560, "y": 460, "c1": "1", "c2": "N", "w": 110, "h": 50},
        # USERS - AGENDA: "Membuat"
        {"name": "Membuat", "e1": "USERS", "e2": "AGENDA", "x": 875, "y": 700, "c1": "1", "c2": "N", "w": 110, "h": 50},
        # USERS - DOKUMEN: "Mengunggah"
        {"name": "Mengunggah", "e1": "USERS", "e2": "DOKUMEN", "x": 1525, "y": 700, "c1": "1", "c2": "N", "w": 120, "h": 50},
        # USERS - RUANG_RAPAT: "Mengajukan"
        {"name": "Mengajukan", "e1": "USERS", "e2": "RUANG_RAPAT", "x": 1560, "y": 970, "c1": "1", "c2": "N", "w": 120, "h": 50},
        # USERS - NOTIFIKASI: "Menerima"
        {"name": "Menerima", "e1": "USERS", "e2": "NOTIFIKASI", "x": 1200, "y": 960, "c1": "1", "c2": "N", "w": 110, "h": 50},
        # USERS - LOG_AKTIVITAS: "Mencatat"
        {"name": "Mencatat", "e1": "USERS", "e2": "LOG_AKTIVITAS", "x": 950, "y": 970, "c1": "1", "c2": "N", "w": 110, "h": 50},
    ]

    elements = []

    # 1. Draw Relationship Lines and Diamonds
    for r in relationships:
        e1 = entities[r["e1"]]
        e2 = entities[r["e2"]]
        rx, ry = r["x"], r["y"]
        rw, rh = r["w"], r["h"]
        
        # Line from E1 to Diamond
        elements.append(f'<line x1="{e1["x"]}" y1="{e1["y"]}" x2="{rx}" y2="{ry}" stroke="#000000" stroke-width="1.5" />')
        # Line from Diamond to E2
        elements.append(f'<line x1="{rx}" y1="{ry}" x2="{e2["x"]}" y2="{e2["y"]}" stroke="#000000" stroke-width="1.5" />')
        
        # Cardinality labels near entities
        # Near E1
        dx1 = 1 if rx > e1["x"] else (-1 if rx < e1["x"] else 0)
        dy1 = 1 if ry > e1["y"] else (-1 if ry < e1["y"] else 0)
        cx1 = e1["x"] + dx1 * (e1["w"]/2 + 20)
        cy1 = e1["y"] + dy1 * (e1["h"]/2 + 15)
        elements.append(f'<text x="{cx1}" y="{cy1}" font-family="Times New Roman" font-size="14" font-weight="bold" fill="#000000">{r["c1"]}</text>')
        
        # Near E2
        dx2 = 1 if rx > e2["x"] else (-1 if rx < e2["x"] else 0)
        dy2 = 1 if ry > e2["y"] else (-1 if ry < e2["y"] else 0)
        cx2 = e2["x"] + dx2 * (e2["w"]/2 + 20)
        cy2 = e2["y"] + dy2 * (e2["h"]/2 + 15)
        elements.append(f'<text x="{cx2}" y="{cy2}" font-family="Times New Roman" font-size="14" font-weight="bold" fill="#000000">{r["c2"]}</text>')
        
        # Render diamond
        elements.append(render_relation(rx, ry, rw, rh, r["name"]))

    # 2. Draw Attributes
    for ent_name, attr_list in attributes.items():
        ent = entities[ent_name]
        for a_name, a_type, ox, oy, rx, ry in attr_list:
            ax = ent["x"] + ox
            ay = ent["y"] + oy
            elements.append(render_attribute(ax, ay, rx, ry, a_name, a_type, ent["x"], ent["y"]))

    # 3. Draw Entities (on top)
    for ent_name, ent in entities.items():
        elements.append(render_entity(ent["x"], ent["y"], ent["w"], ent["h"], ent_name))

    caption = "Entity Relationship Diagram (ERD) - Notasi Chen Klasik | Website Internal Dinas Pariwisata"

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
            height: {height}px;
        }}
    </style>
</head>
<body>
    <svg width="{width}" height="{height}" viewBox="0 0 {width} {height}" xmlns="http://www.w3.org/2000/svg">
        <!-- Main Boundary Box (Same style as Use Case) -->
        <rect x="40" y="40" width="{width - 80}" height="{height - 100}" fill="#FFFFFF" stroke="#000000" stroke-width="1.8" />
        
        <!-- System Title at Top-Left of Box (Standard UML / System Boundary) -->
        <text x="65" y="75" font-family="'Times New Roman', Times, serif" font-size="13" fill="#000000">«subsystem»</text>
        <text x="65" y="98" font-family="'Times New Roman', Times, serif" font-size="18" font-weight="bold" fill="#000000">ERD Website Internal Dispar (Notasi Chen)</text>
        
        <!-- Legend Box on Top-Right -->
        <rect x="{width - 440}" y="60" width="370" height="125" fill="#FFFFFF" stroke="#000000" stroke-width="1.2" />
        <text x="{width - 255}" y="85" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-size="13" font-weight="bold" fill="#000000">Keterangan Simbol (Notasi Chen)</text>
        
        <!-- Legend items -->
        <rect x="{width - 420}" y="98" width="28" height="16" fill="#FFFFFF" stroke="#000000" stroke-width="1.5" />
        <text x="{width - 380}" y="111" font-family="'Times New Roman', Times, serif" font-size="12">Entitas (Persegi Panjang)</text>
        
        <polygon points="{width - 406},125 {width - 392},133 {width - 406},141 {width - 420},133" fill="#FFFFFF" stroke="#000000" stroke-width="1.5" />
        <text x="{width - 380}" y="137" font-family="'Times New Roman', Times, serif" font-size="12">Relasi (Belah Ketupat)</text>
        
        <ellipse cx="{width - 406}" cy="160" rx="14" ry="9" fill="#FFFFFF" stroke="#000000" stroke-width="1.2" />
        <text x="{width - 380}" y="164" font-family="'Times New Roman', Times, serif" font-size="12">Atribut (<tspan text-decoration="underline">Garis Bawah</tspan> = PK, Garis Putus = FK)</text>

        <!-- Diagram Elements -->
        {''.join(elements)}
        
        <!-- Bottom Caption -->
        <text x="{width // 2}" y="{height - 25}" text-anchor="middle" font-family="'Times New Roman', Times, serif" font-style="italic" font-size="14" fill="#000000">
            {caption}
        </text>
    </svg>
</body>
</html>
'''
    return html, width, height

html_content, w, h = create_chen_erd()
html_path = os.path.join(DIAGRAMS_DIR, "erd_chen.html")
svg_path = os.path.join(DIAGRAMS_DIR, "erd_chen.svg")
png_path = os.path.join(DIAGRAMS_DIR, "erd_chen.png")

with open(html_path, "w", encoding="utf-8") as f:
    f.write(html_content)

# Extract SVG alone for direct vector insertion into Word
import re
svg_content = re.search(r'(<svg.*?</svg>)', html_content, re.DOTALL).group(1)
with open(svg_path, "w", encoding="utf-8") as f:
    f.write('<?xml version="1.0" encoding="UTF-8"?>\n' + svg_content)

print(f"Rendering high-res PNG (scale 2x: {w}x{h})...")
cmd = [
    EDGE_PATH,
    "--headless=new",
    "--disable-gpu",
    f"--window-size={w},{h}",
    f"--screenshot={png_path}",
    f"file:///{html_path.replace(os.sep, '/')}"
]
subprocess.run(cmd, capture_output=True)

print("SUCCESS: Chen ERD generated in SVG, PNG, and HTML!")

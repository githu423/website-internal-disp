import os
import re

DIAGRAMS_DIR = r"C:\laravel10\website-internal-disp\diagrams"

files = ["use_case_admin", "use_case_bidang", "use_case_pegawai", "use_case_keseluruhan"]
for base in files:
    h_path = os.path.join(DIAGRAMS_DIR, f"{base}.html")
    s_path = os.path.join(DIAGRAMS_DIR, f"{base}.svg")
    if os.path.exists(h_path):
        with open(h_path, "r", encoding="utf-8") as f:
            content = f.read()
        match = re.search(r'(<svg.*?</svg>)', content, re.DOTALL)
        if match:
            with open(s_path, "w", encoding="utf-8") as f:
                f.write('<?xml version="1.0" encoding="UTF-8"?>\n' + match.group(1))

print("All SVG vector files generated successfully!")

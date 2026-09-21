# Panduan Pemasangan — Kategori & Subkategori Dokumen

Patch ini menambahkan **kategori dokumen bertingkat (induk → anak)** yang dipakai
bersama untuk **semua dokumen**, terlepas dari status aksesnya.

> Status akses (Publik / Privat Bidang / Rahasia) tetap melekat pada **tiap dokumen**,
> bukan pada kategorinya. Dokumen dengan kategori yang sama masuk ke kelompok yang sama
> meskipun status aksesnya berbeda.

## Cara Pasang

1. Taruh/overwrite seluruh file patch ke folder project (timpa).
2. Backup database dulu (disarankan).
3. Jalankan migrasi:

   ```bash
   php artisan migrate
   ```

4. Bersihkan cache tampilan:

   ```bash
   php artisan optimize:clear
   ```

> `composer`/`npm` **tidak perlu**. Aset frontend memakai kelas Tailwind yang sudah ada;
> tidak ada build Vite tambahan.

## Yang Berubah

### Backend

- Migrasi baru: tabel `kategori_dokumen` (id, nama, induk_id, timestamps) dengan
  aturan nama unik dalam induk yang sama.
- Kolom baru `dokumen.id_kategori` (FK ke `kategori_dokumen`), sebelah kolom `kategori` lama.
- Migrasi backfill: kategori teks lama (termasuk **Arsip Bidang**) dipindahkan ke pohon
  kategori sehingga dokumen lama tetap bisa difilter.
- `Dokumen` → relasi `kategoriRel` + aksesor `kategori_label` (contoh: `Laporan / 2026 / TW I`).
- Layanan `KategoriDokumenService`: `dapatkanAtauBuat()`, `cari()`, dan
  `dapatkanAtauBuatBerjenjang()` untuk membuat pohon dari teks `Laporan / 2026 / TW I`.
- Unggahan halaman **Bidang** kini menerima `id_kategori`/`kategori`; bila kosong tetap
  fallback ke **Arsip Bidang** (perilaku lama).
- Filter kategori di halaman Dokumen kini memakai id kategori dan **ikut menyertakan
  subkategori** di bawahnya.

### Frontend

- Halaman **Dokumen (universal)**: filter kategori bertingkat; kartu menampilkan
  `kategori / subkategori`; modal unggah punya pemilih kategori + isian "buat kategori baru"
  (teks berjenjang).
- Halaman **Bidang**: form unggah punya pemilih kategori/subkategori + isian kategori baru;
  daftar dokumen menampilkan jejak kategori.
- Admin dokumen & pencarian global menampilkan `kategori_label`.

## Catatan

- Kedalaman pohon yang dipakai UI sampai **3 tingkat** (induk / anak / cucu).
  Kategori baru yang lebih dalam tetap tersimpan, tapi tampilannya diringkas.
- Tes regresi baru: `tests/Feature/KategoriDokumenTest.php` (7 tes).
- Pemeriksaan otomatis (CI) hadir di workflow `verify.yml`.

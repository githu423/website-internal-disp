# Panduan Setup SIPARDIS untuk Rekan Tim

Dokumen ini untuk **rekan kerja yang baru pertama kali mengambil project ini**.
Kalau Anda sudah punya project-nya dan hanya ingin update, lompat ke bagian
[Update Harian](#update-harian).

---

## A. Yang harus terpasang dulu (sekali seumur hidup)

| Kebutuhan | Versi | Cara cek |
|---|---|---|
| PHP | **8.3 atau lebih baru** | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 20 / 22 LTS | `node -v` |
| npm | 10+ | `npm -v` |
| MySQL / MariaDB | 8.x / 10.x | `mysql --version` |
| Git | terbaru | `git --version` |

> **Windows:** paling gampang pakai **Laragon** atau **XAMPP** (sudah berisi PHP +
> MySQL), lalu pasang **Composer** dan **Node.js** terpisah.
>
> Pastikan ekstensi PHP ini aktif di `php.ini` (hapus `;` di depannya):
> `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip`, `gd`, `curl`.
> Ekstensi `zip` & `gd` wajib karena project memakai PhpSpreadsheet
> untuk impor/ekspor Excel.

---

## B. Ambil project pertama kali

Jalankan di terminal VS Code, di folder tempat Anda menyimpan project
(misal `C:\Laravel10\` atau `~/projects`):

```bash
# 1. Unduh project dari GitHub
git clone https://github.com/githu423/website-internal-disp.git
cd website-internal-disp

# 2. Pasang dependensi PHP
composer install

# 3. Pasang dependensi JavaScript
npm install

# 4. Siapkan file konfigurasi .env
cp .env.example .env          # Windows PowerShell: copy .env.example .env

# 5. Buat kunci aplikasi
php artisan key:generate
```

### 6. Buat database

Buat database kosong bernama **`dispar_web`** lewat phpMyAdmin / HeidiSQL,
atau lewat terminal:

```bash
mysql -u root -p -e "CREATE DATABASE dispar_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Lalu buka file `.env`, sesuaikan bagian ini dengan setelan MySQL Anda:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dispar_web
DB_USERNAME=root
DB_PASSWORD=          # isi kalau MySQL Anda pakai password
```

### 7. Isi struktur & data awal

```bash
php artisan migrate --seed
```

Perintah ini membuat seluruh tabel sekaligus mengisi data awal: daftar bidang,
akun admin, data pegawai, kinerja, PAD, dan capaian kinerja.

### 8. Tautkan folder penyimpanan berkas

```bash
php artisan storage:link
```

Wajib — tanpa ini dokumen yang diunggah tidak bisa dibuka.

### 9. Build aset tampilan

```bash
npm run build
```

### 10. Jalankan

```bash
php artisan serve
```

Buka **http://localhost:8000**

**Akun admin bawaan:**
- NIP: `ADMIN0000`
- Password: `admin123`

> Ganti password ini kalau dipakai di server sungguhan.

---

## C. Ringkasan cepat (salin-tempel sekaligus)

Untuk yang sudah paham dan semua prasyarat sudah terpasang:

```bash
git clone https://github.com/githu423/website-internal-disp.git
cd website-internal-disp
composer install
npm install
cp .env.example .env
php artisan key:generate
mysql -u root -p -e "CREATE DATABASE dispar_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

---

## Update Harian

Kalau project sudah ada dan hanya ingin mengambil perubahan terbaru:

```bash
git pull origin main
composer install          # kalau composer.json berubah
npm install               # kalau package.json berubah
php artisan migrate       # kalau ada migrasi baru
npm run build             # kalau ada perubahan CSS/JS
php artisan view:clear
```

Kalau ragu perubahannya apa saja, jalankan semuanya — aman, tidak merusak data.

---

## Mode Pengembangan (saat sedang ngoding)

Buka **dua terminal** terpisah:

```bash
# Terminal 1 — server Laravel
php artisan serve

# Terminal 2 — Vite, agar perubahan CSS/JS langsung terlihat tanpa build ulang
npm run dev
```

---

## Kalau Ada Masalah

| Gejala | Solusi |
|---|---|
| Tampilan berantakan / tanpa gaya | `npm run build` lalu `php artisan view:clear` |
| Perubahan Blade tidak muncul | `php artisan view:clear` |
| `SQLSTATE[HY000] [1049] Unknown database` | Database `dispar_web` belum dibuat — ulangi langkah 6 |
| `No application encryption key` | `php artisan key:generate` |
| Dokumen/gambar tidak bisa dibuka | `php artisan storage:link` |
| `Class "ZipArchive" not found` | Aktifkan ekstensi `zip` di `php.ini`, restart server |
| Error aneh setelah `git pull` | `php artisan optimize:clear` lalu `composer dump-autoload` |
| Ingin mulai ulang dari data bersih | `php artisan migrate:fresh --seed` ⚠️ **menghapus semua data** |

---

## Catatan Tema Gelap

Aplikasi punya mode gelap bernuansa ungu khas Dispar Ciamis. Tombolnya ada di
**kanan atas** (ikon bulan / matahari), di sebelah lonceng notifikasi. Pilihan
tersimpan otomatis di browser masing-masing pengguna; kalau belum pernah
memilih, tampilan mengikuti setelan tema sistem operasi.

Karena mode gelap dibangun dari CSS, setelah `git pull` yang menyertakan
perubahan tema, **wajib jalankan `npm run build`** (atau cukup `git pull` saja
kalau folder `public/build` ikut ter-commit — di repo ini memang ikut).

<p align="center">
  <img src="public/assets/img/logo.png" alt="SIK-T Logo" height="80">
</p>

<h1 align="center">SIK-T — Sistem Informasi Kelulusan Terpadu</h1>

<p align="center">
  Platform manajemen kelulusan sekolah berbasis web yang modern, lengkap, dan siap digunakan.<br>
  Dibangun dengan <strong>Laravel 12</strong>, <strong>Node.js (Baileys)</strong>, dan antarmuka <strong>Materialize Admin</strong>.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/version-1.0.0-blue?style=flat-square" alt="Version">
  <img src="https://img.shields.io/badge/PHP-8.2+-8892BF?style=flat-square&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/Node.js-18+-339933?style=flat-square&logo=node.js&logoColor=white" alt="Node.js">
  <img src="https://img.shields.io/badge/License-Proprietary-red?style=flat-square" alt="License">
</p>

---

## 📋 Tentang

**SIK-T** adalah sistem informasi kelulusan terpadu yang dirancang khusus untuk sekolah menengah (SMA/SMK) di Indonesia. Sistem ini menyediakan solusi lengkap mulai dari pengelolaan data siswa, manajemen nilai, penerbitan dokumen kelulusan (SKL, Transkrip, Sertifikat UKK), hingga notifikasi otomatis melalui WhatsApp Gateway.

### Mengapa SIK-T?

- **All-in-One** — Satu platform untuk seluruh proses kelulusan
- **WhatsApp Integrated** — Blast notifikasi & auto-respond cek kelulusan
- **Dokumen Otomatis** — SKL, Transkrip, dan Sertifikat UKK dengan penomoran dinamis
- **Portal Siswa** — Siswa dapat mengakses hasil kelulusan secara mandiri
- **Self-Update** — Update aplikasi langsung dari panel admin, tanpa akses terminal

---

## ✨ Fitur Unggulan

| Fitur | Deskripsi |
|-------|-----------|
| 🎓 **Manajemen Kelulusan** | Penetapan status Lulus/Tidak Lulus per siswa, penomoran dokumen dinamis/statis |
| 📄 **Cetak SKL & Transkrip** | Generate PDF otomatis dengan QR Code verifikasi keaslian |
| 📜 **Sertifikat UKK (SMK)** | Sertifikat kompetensi dengan mapping asesor per jurusan |
| 📊 **Leger Nilai Semester** | Input nilai semester 1-6 dengan autosave dan import/export Excel |
| 📱 **WhatsApp Gateway** | Blast notifikasi massal, auto-respond cek kelulusan via chat |
| 👨‍🎓 **Portal Siswa** | Dashboard interaktif dengan animasi amplop & confetti |
| 🔐 **QR Verification** | Setiap dokumen memiliki QR Code untuk validasi publik |
| 📦 **Backup Database** | Backup dan download database langsung dari panel admin |
| 🔄 **Self-Update** | Update aplikasi dari panel admin (mendukung Git & ZIP) |
| 🌐 **Multi Bahasa** | Mendukung Bahasa Indonesia dan English |
| 🔍 **Command Palette** | Quick search internal dengan shortcut `Ctrl + K` |

---

## 📌 Persyaratan Sistem

| Komponen | Minimum |
|----------|---------|
| PHP | 8.2 atau lebih baru |
| Composer | 2.x |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Node.js | 18.x atau lebih baru |
| NPM | 9.x atau lebih baru |
| Web Server | Nginx / Apache / LiteSpeed |

---

## 🚀 Panduan Instalasi

### 🖥 Instalasi Lokal (MAMP / XAMPP / Laragon)

```bash
# 1. Clone repository
git clone https://github.com/Jawara46/sik.git
cd sik

# 2. Install dependencies
composer install
npm install && npm run build

# 3. Konfigurasi environment
cp .env.example .env
php artisan key:generate

# 4. Sesuaikan .env (database, APP_URL, dll)
# DB_HOST=127.0.0.1
# DB_DATABASE=sik_db
# DB_USERNAME=root
# DB_PASSWORD=root

# 5. Migrasi database
php artisan migrate --force

# 6. Link storage
php artisan storage:link

# 7. Jalankan aplikasi
php artisan serve
# Akses di http://localhost:8000
```

**WhatsApp Gateway (opsional):**
```bash
cd wapi
npm install
npm start
# Gateway berjalan di http://127.0.0.1:3000
```

---

### 🌐 Instalasi di VPS / aaPanel

#### Prasyarat
- VPS dengan aaPanel terinstall
- Website PHP sudah dibuat (domain/subdomain)
- MySQL database sudah dibuat

#### Langkah-langkah

```bash
# 1. SSH ke VPS, masuk ke direktori website
cd /www/wwwroot/domain.com

# 2. Clone repository
git clone https://github.com/Jawara46/sik.git .

# 3. Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# 4. Konfigurasi environment
cp .env.example .env
php artisan key:generate

# 5. Edit .env sesuai database aaPanel
nano .env
# APP_URL=https://domain.com
# DB_HOST=127.0.0.1
# DB_DATABASE=nama_database
# DB_USERNAME=user_database
# DB_PASSWORD=password_database

# 6. Migrasi dan optimasi
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

#### Konfigurasi Nginx (aaPanel)

Pada pengaturan website di aaPanel, set **Document Root** ke folder `public`:
```
/www/wwwroot/domain.com/public
```

Tambahkan konfigurasi Nginx berikut:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

#### WhatsApp Gateway (PM2)
```bash
# Install PM2 global
npm install -g pm2

# Jalankan WAPI
cd /www/wwwroot/domain.com/wapi
pm2 start app.js --name sik-wapi
pm2 save
pm2 startup
```

---

### 📦 Instalasi di Shared Hosting (cPanel)

> Untuk hosting yang tidak menyediakan akses SSH, gunakan metode ZIP.

1. **Download** release terbaru dari halaman [Releases](https://github.com/Jawara46/sik/releases)
2. **Upload** file ZIP ke hosting via File Manager cPanel
3. **Extract** ke dalam folder `public_html` atau subdomain yang diinginkan
4. **Buat database MySQL** melalui cPanel → MySQL Databases
5. **Edit file `.env`** (rename `.env.example` menjadi `.env`):
   - Sesuaikan `APP_URL`, `DB_*`, dan `APP_KEY`
6. **Akses URL** `/install` untuk menjalankan wizard setup (jika tersedia), atau jalankan migrasi secara manual

> **Catatan:** Pastikan document root mengarah ke folder `public/`.

---

## ⚙️ Konfigurasi WhatsApp Gateway

SIK-T menggunakan **Baileys** (library WhatsApp Web tidak resmi) untuk gateway WhatsApp.

```bash
cd wapi
npm install
```

### Environment Variables (opsional)
```env
PORT=3000
WAPI_HOST=127.0.0.1
APP_PORTAL_URL=https://domain.com
DB_SOCKET=/path/to/mysql.sock  # Untuk instalasi lokal via socket
```

### Menjalankan Gateway
```bash
# Development
npm start

# Production (dengan PM2)
pm2 start app.js --name sik-wapi
```

### Menghubungkan WhatsApp
1. Buka menu **Konfigurasi Sistem → WhatsApp Center** di panel admin
2. Scan QR Code dengan aplikasi WhatsApp di HP
3. Setelah terhubung, status berubah menjadi **Connected**
4. Tes kirim pesan untuk memastikan gateway aktif

---

## 🔄 Update Aplikasi

### Via Panel Admin (Direkomendasikan)
1. Buka **Konfigurasi Sistem → Tentang Aplikasi**
2. Klik **"Periksa Update"**
3. Jika tersedia, klik **"Jalankan Update"**
4. Sistem akan otomatis mendeteksi metode instalasi (Git/ZIP) dan melakukan update

### Via Terminal (Git)
```bash
cd /path/to/sik
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
```

---

## 🔑 Akses Default

| Role | URL | Username | Password |
|------|-----|----------|----------|
| Admin | `/admin/login` | `admin@sik.local` | `password` |
| Siswa | `/` | NISN | Tanggal Lahir (DDMMYYYY) |

> ⚠️ **Segera ubah password default** setelah instalasi pertama.

---

## 📁 Struktur Proyek

```
sik/
├── app/                    # Logic aplikasi (Controllers, Services, Models)
├── config/                 # File konfigurasi
├── database/               # Migrasi dan seeder
├── lang/                   # File translasi (id, en)
├── public/                 # Document root web server
├── resources/views/        # Template Blade
├── routes/                 # Definisi routing
├── storage/                # File upload, cache, log
├── wapi/                   # WhatsApp Gateway (Node.js + Baileys)
├── .env.example            # Template environment
├── composer.json           # Dependencies PHP
└── package.json            # Dependencies frontend
```

---

## 📝 Lisensi

**Hak Cipta © 2026 Yazid Digital.** Seluruh hak dilindungi undang-undang.

Perangkat lunak ini bersifat **proprietary** dan hanya boleh digunakan sesuai dengan ketentuan lisensi yang diberikan oleh pengembang. Dilarang keras mendistribusikan, memodifikasi, atau menggunakan kode sumber untuk keperluan komersial tanpa izin tertulis.

---

## 📞 Kontak & Dukungan

| | |
|---|---|
| 🌐 Website | [yazid.my.id](https://yazid.my.id) |
| 📱 WhatsApp | [081311112309](https://wa.me/6281311112309) |
| 📧 Email | hello@yazid.my.id |

---

<p align="center">
  <sub>Dibangun dengan ❤️ oleh <strong>Yazid Digital</strong> untuk dunia pendidikan Indonesia.</sub>
</p>

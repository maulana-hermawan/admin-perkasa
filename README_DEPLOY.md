# PANDUAN DEPLOY — Perkasa Mulia Training Center v2.0

## Persyaratan Server
- PHP 8.0+ (disarankan 8.1/8.2)
- MySQL 5.7+ / MariaDB 10.5+
- Hosting shared (Hostinger, Niagahoster) ✅ kompatibel
- cURL extension (untuk Fonnte WA)
- OpenSSL extension (untuk 2FA)

---

## LANGKAH DEPLOY

### 1. Upload File ke Server

Upload seluruh isi folder ini ke direktori `public_html/admin/` di Hostinger.

Struktur yang diharapkan di server:
```
public_html/
└── admin/
    ├── index.php         ← entry point admin
    ├── login.php
    ├── login-2fa.php
    ├── logout.php
    ├── daftar.php        ← form pendaftaran publik
    ├── portal-tutor.php
    ├── portal-siswa.php
    ├── setup.php         ← onboarding wizard (sekali pakai)
    ├── api.php
    ├── app/
    ├── assets/
    ├── database/
    └── storage/
```

### 2. Setup Database

**Opsi A — Install Bersih (HAPUS DATA LAMA):**
1. Login phpMyAdmin di Hostinger panel
2. Pilih database: `u741010203_adminperkasa`
3. Tab "Import" → pilih file: `database/install.sql`
4. Klik "Go"

**Opsi B — Jalankan per migration (pertahankan data):**
Jalankan file dalam folder `database/migrations/` secara berurutan:
```
001_initial_schema.sql
002_cleanup_orphan.sql
003_add_audit_columns.sql
004_add_indexes.sql
005_add_new_tables.sql
006_add_users_role_owner.sql
007_unify_transaksi_kategori.sql
008_nilai_mapel_psikologi.sql
009_add_tutor_payment_fields.sql
010_add_2fa_and_cabang.sql
```

### 3. Setup File .env

```bash
cp .env.example .env
```

Edit file `.env`, isi nilai yang sesuai:

```env
# Database — isi sesuai panel Hostinger
DB_HOST=localhost
DB_USER=u741010203_admin
DB_PASS=PASSWORD_DATABASE_ANDA
DB_NAME=u741010203_adminperkasa
DB_PORT=3306

# Aplikasi
APP_NAME=Perkasa Mulia Training Center
APP_URL=https://bimbelperkasa.id/admin
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Jakarta

# Session
SESSION_NAME=pk_sess
SESSION_LIFETIME=7200

# Security
CSRF_TOKEN_LENGTH=32

# Fonnte WhatsApp — daftar di fonnte.com
FONNTE_TOKEN=TOKEN_DARI_FONNTE
FONNTE_SENDER=

# Psikotes CAT — sesuaikan URL webhook
CAT_WEBHOOK_SECRET=perkasa-cat-secret-2026
CAT_DB_HOST=localhost
CAT_DB_USER=u741010203_admincbt
CAT_DB_PASS=PASSWORD_CAT_DB
CAT_DB_NAME=u741010203_cbtperkasa

# Public API
API_JWT_SECRET=ISI_DENGAN_STRING_ACAK_MINIMAL_32_KARAKTER
API_KEY=

# Admin WA untuk notif pendaftaran baru
ADMIN_WA_NUMBER=08123456789
```

### 4. Buat Folder storage yang Writable

Di File Manager Hostinger, pastikan folder berikut ada dan dapat ditulis:
```
admin/storage/cache/
admin/storage/logs/
admin/storage/backups/
admin/storage/uploads/
admin/storage/exports/
```

Atau via SSH:
```bash
mkdir -p storage/{cache,logs,backups,uploads,exports}
chmod 755 storage storage/*
```

### 5. Proteksi Folder storage

Tambahkan `.htaccess` di dalam `storage/`:
```apache
Options -Indexes
Deny from all
```

### 6. Setup Psikotes CAT (Integrasi)

Edit file `psikotes-cat-modified/simpan_hasil.php` dan upload **menggantikan** file original di folder psikotes CAT Anda.

File ini sudah dimodifikasi untuk mengirim webhook ke admin panel setelah setiap hasil ujian disimpan.

**Penting:** Sesuaikan URL webhook di baris:
```php
$url = "$scheme://$host/admin/app/api/webhook-cat.php";
```

---

## LOGIN PERTAMA

**Admin Panel:**
- URL: `https://bimbelperkasa.id/admin/`
- Email: `admin@perkasa.id`
- Password: `password`
- **GANTI PASSWORD INI SEGERA setelah login pertama!**

**Portal Tutor:**
- URL: `https://bimbelperkasa.id/admin/portal-tutor.php`
- Gunakan akun tutor yang dibuat di menu "Manajemen Tutor"

**Portal Siswa:**
- URL: `https://bimbelperkasa.id/admin/portal-siswa.php`
- Gunakan akun siswa yang dibuat otomatis saat mendaftar

**Form Pendaftaran Publik:**
- URL: `https://bimbelperkasa.id/admin/daftar.php`
- Bisa dibagikan ke calon siswa, tidak perlu login

---

## FITUR UTAMA

| Fitur | URL | Keterangan |
|-------|-----|------------|
| Dashboard | `/admin/` | Statistik & analitik |
| Data Siswa | `?page=siswa` | CRUD + trash bin |
| Buku Kas | `?page=keuangan` | Filter cepat periode |
| Jadwal | `?page=jadwal` | Kalender & list view |
| Rekap Nilai | `?page=nilai` | Import Excel tersedia |
| Manajemen Tutor | `?page=tutor` | CRUD + rekap gaji |
| Pembayaran SPP | `?page=pembayaran` | Rekam & sync Buku Kas |
| Absensi | `?page=attendance` | Bulk mark per jadwal |
| Laporan | `?page=laporan` | Export CSV/Excel |
| Pendaftaran Online | `?page=pendaftaran` | Antrian verifikasi |
| Pengaturan | `?page=settings` | Password + 2FA TOTP |

---

## BACKUP OTOMATIS (CRON)

Di Hostinger panel → Cron Job, tambahkan:

```
0 2 * * * /usr/bin/php /home/username/public_html/admin/database/scripts/backup.php
```

Backup disimpan di `storage/backups/` dengan retensi 30 hari.

---

## PUBLIC API

**Dapatkan token:**
```bash
POST /admin/api.php?path=token
Content-Type: application/json

{"email":"admin@perkasa.id","password":"PASSWORD","api_key":"API_KEY_DARI_ENV"}
```

**Gunakan token:**
```bash
GET /admin/api.php?path=siswa
Authorization: Bearer TOKEN_YANG_DIDAPAT
```

**Endpoint tersedia:**
- `POST /token` — Autentikasi
- `GET /siswa` — List siswa (param: status, limit, offset)
- `GET /jadwal` — List jadwal (param: dari, sampai)
- `GET /nilai/{siswa_id}` — Nilai per siswa

---

## TROUBLESHOOTING

**Halaman putih / 500 error:**
1. Cek `storage/logs/php_errors.log`
2. Pastikan file `.env` sudah dibuat dari `.env.example`
3. Pastikan folder `storage/cache/` bisa ditulis

**Login gagal terus:**
1. Pastikan DB_PASS di `.env` benar
2. Cek `storage/logs/php_errors.log` untuk error koneksi DB

**2FA tidak bisa diakses:**
1. Pastikan PHP extension `hash` aktif (biasanya default)
2. Waktu server harus sinkron (NTP) untuk TOTP

**Webhook CAT tidak jalan:**
1. Cek URL webhook sudah benar di `simpan_hasil.php`
2. Cek `storage/logs/php_errors.log`
3. Pastikan `CAT_WEBHOOK_SECRET` di `.env` sama dengan yang di `simpan_hasil.php`

**WhatsApp tidak terkirim:**
1. Cek `FONNTE_TOKEN` di `.env` sudah diisi
2. Cek saldo/limit Fonnte di dashboard fonnte.com

---

## STRUKTUR FOLDER

```
admin/
├── index.php              Router utama admin
├── login.php              Halaman login
├── login-2fa.php          Verifikasi TOTP
├── logout.php             Logout
├── daftar.php             Form pendaftaran publik (no auth)
├── portal-tutor.php       Portal tutor (mobile-first)
├── portal-siswa.php       Portal siswa (mobile-first)
├── setup.php              Wizard setup pertama kali
├── api.php                Public API entry point
├── app/
│   ├── api/               Internal API endpoints
│   ├── config/            database.php, app.php
│   ├── core/              auth, db, helpers, cache, validator
│   ├── layouts/           admin.php, tutor.php, siswa.php
│   ├── lib/               TOTP, JWT, SimpleXLSX
│   └── modules/           Semua modul aplikasi
├── assets/css/app.css     Design tokens & custom CSS
├── database/
│   ├── install.sql        ← GUNAKAN INI untuk fresh install
│   ├── migrations/        File migration per versi
│   └── scripts/           backup.php, migrate_passwords.php
└── storage/               Cache, logs, backup, upload
```

---

*Dibuat oleh Claude AI untuk Perkasa Mulia Training Center v2.0*
*Dilarang deploy tanpa mengganti password default admin!*

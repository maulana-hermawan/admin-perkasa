# Arsitektur Sistem — Perkasa Mulia Training Center v2.0

## Gambaran Besar

```
Browser
  │
  ├── index.php          ← Admin dashboard (semua halaman admin)
  ├── login.php          ← Autentikasi
  ├── portal-siswa.php   ← Portal mandiri siswa
  ├── portal-tutor.php   ← Portal mandiri tutor
  └── api.php            ← REST API (JWT auth)

index.php
  │  1. Load config (database.php, app.php)
  │  2. check_auth() — redirect ke login jika belum login
  │  3. check_role(['admin','owner']) — 403 jika bukan admin
  │  4. Validasi $page + $action (whitelist)
  │  5. require controller → return $view_data
  └── require admin.php layout → extract($view_data) → require view
```

---

## Request Lifecycle (Admin)

```
GET/POST ?page=siswa&action=store
         │
         ▼
    index.php
    ├── require app/config/database.php   (DB connect, konstanta)
    ├── require app/config/app.php        (API keys, APP_URL)
    ├── require app/core/db.php           (fungsi DB)
    ├── require app/core/helpers.php      (fungsi umum)
    ├── require app/core/auth.php         (session)
    ├── require app/core/validator.php    (validasi)
    ├── require app/core/cache.php        (cache)
    │
    ├── session_bootstrap()              ← init session aman
    ├── check_auth()                     ← guard — redirect /login.php
    ├── check_role(['admin','owner'])    ← guard — 403
    │
    ├── validate $page ∈ ALLOWED_PAGES  ← fallback ke 'dashboard'
    ├── validate $action ∈ ALLOWED_ACTIONS ← fallback ke 'index'
    │
    ├── $view_data = require app/modules/siswa/controller.php
    │                   (controller membaca $action, $page dari scope global)
    │
    └── require app/layouts/admin.php
            ├── extract($view_data)      ← semua key jadi variabel
            ├── require _partials/sidebar.php
            ├── require _partials/topbar.php
            ├── require _partials/flash.php
            └── require $view_data['view']   ← view file modul
```

---

## Lapisan Keamanan

### 1. Authentication (app/core/auth.php)
- Session PHP dengan `session_regenerate_id(true)` saat login
- Bcrypt password hashing (cost=12)
- Rate limiting: 5 percobaan gagal → lockout 5 menit
- Session timeout: 2 jam (SESSION_LIFETIME)
- Timing attack mitigation: `usleep(rand(100000, 300000))`
- Migrasi otomatis plaintext → bcrypt saat login pertama

### 2. CSRF Protection
- Token per-session disimpan di `$_SESSION['_csrf_token']`
- Setiap form POST harus menyertakan `<?= csrf_field() ?>`
- Controller harus memanggil `csrf_check()` di awal handler POST
- Untuk aksi destruktif via GET (delete, restore): gunakan `csrf_check_get()`

### 3. SQL Injection Prevention
- Semua query WAJIB menggunakan prepared statements via fungsi `db_*`
- Tidak ada interpolasi string langsung ke query
- Tipe data binding: `i`(int), `s`(string), `d`(double), `b`(blob)

### 4. XSS Prevention
- Semua output user data WAJIB melalui `e()` = `htmlspecialchars($v, ENT_QUOTES)`
- Layout tidak pernah echo tanpa `e()`
- Alpine.js binding menggunakan `:text-content` atau `x-text` (otomatis escape)

### 5. Role-Based Access Control
```
admin  → akses index.php (semua modul)
owner  → akses index.php (semua modul + fitur owner)
siswa  → hanya portal-siswa.php
tutor  → hanya portal-tutor.php
```
- `check_role(['admin','owner'])` dipanggil di `index.php` untuk semua route admin
- Aksi owner-only dilindungi dengan `auth_is(['owner'])` per blok

### 6. Input Whitelist
- `$ALLOWED_PAGES` di index.php mencegah path traversal ke modul lain
- `$ALLOWED_ACTIONS` mencegah aksi yang tidak terdaftar
- Semua integer ID diambil dengan `get_int()` — tidak bisa injection
- Enum fields divalidasi dengan `->enum($field, $allowed_values)`

### 7. Soft Delete
- Semua deletion menggunakan `deleted_at = NOW()`, bukan DELETE permanen
- Query selalu menyertakan `AND deleted_at IS NULL`
- Audit log menyimpan data sebelum penghapusan

### 8. Audit Log
```php
log_action('ACTION_NAME', 'table_name', $record_id, $old_values, $new_values)
// Tersimpan di tabel audit_log dengan IP + user agent
```

### 9. 2FA (TOTP)
- Implementasi TOTP di `app/lib/TOTP.php`
- Secret tersimpan terenkripsi di `users.totp_secret`
- Aktif per user (opsional) melalui `settings`

---

## Pola Caching

Cache berbasis file untuk shared hosting (tidak ada Redis/APCu).

```php
// Pola dasar: cache_remember
$data = cache_remember('cache_key', function() {
    return db_fetch_all("SELECT ...");
}, 900); // TTL 900 detik = 15 menit

// Cache keys yang digunakan dashboard:
// 'dashboard_trend_chart'     → 15 menit (query berat 6 bulan)
// 'dashboard_membership_expiring' → 10 menit
// 'db_cohort_lulus'           → 1 jam
// 'db_top_program'            → 30 menit
```

**Storage:** `storage/cache/{key}.cache` (PHP serialize)  
**Invalidasi:** Otomatis saat TTL habis. Manual: `cache_delete($key)` atau `cache_flush()`.

---

## Database Layer

### Koneksi
Singleton MySQLi. Koneksi dibuat sekali per request di `db_connect()`.

```
app/config/database.php  → definisikan DB_HOST, DB_USER, DB_PASS, DB_NAME
app/core/db.php          → db_connect() menggunakan konstanta tersebut
```

### Catatan Kolom Khusus (Generated Columns)
Kolom berikut di-generate otomatis oleh MySQL — **JANGAN di-INSERT**:
- `penilaian_akademik.total_skor` — SUM(nilai_twk + nilai_tiu + nilai_tkp)
- `penilaian_psikologi.rata_psikologi` — AVG(kecerdasan + kecermatan + kepribadian)
- `penilaian_psikologi.status_psikologi` — berdasarkan rata_psikologi

### Relasi Utama
```
users (1) ──── (1) siswa              [siswa.user_id]
users (1) ──── (1) tutor              [melalui email/login]
siswa (1) ──── (N) membership_siswa   [membership_siswa.siswa_id]
program (1) ── (N) membership_siswa   [membership_siswa.program_id]
jadwal (1) ─── (N) jadwal_tutor       [jadwal_tutor.jadwal_id]
tutor (1) ──── (N) jadwal_tutor       [jadwal_tutor.tutor_id]
siswa (1) ──── (N) penilaian_*        [penilaian_*.siswa_id]
siswa (1) ──── (N) pembayaran_siswa   [pembayaran_siswa.siswa_id]
siswa (1) ──── (N) attendance_siswa   [attendance_siswa.siswa_id]
jadwal (1) ─── (N) attendance_siswa   [attendance_siswa.jadwal_id]
```

---

## Layout Admin (app/layouts/admin.php)

### Alpine.js Global Store (pkApp)
```javascript
{
    sidebarOpen: Boolean,    // true di desktop (≥992px), false di mobile
    darkMode: Boolean,       // dari localStorage 'pk_dark'
    notifCount: Number,
    searchQuery: String,

    toggleSidebar()          // toggle sidebar open/closed
    toggleDark()             // toggle dark mode
    init()                   // setup watchers + resize listener
}
```

### JavaScript Global Helpers
```javascript
pkToast(msg, type, duration)       // Toast notification (success/danger/warning/info)
pkConfirm(message, onConfirm, {})  // Konfirmasi modal sebelum aksi destruktif
pkValidateForm(formEl)             // Client-side form validation (Bootstrap)
```

### Alpine.js Auto-init
- Semua `<select class="ts-search">` otomatis diinisialisasi sebagai Tom Select
- Semua `<form class="pk-validate">` otomatis divalidasi + spinner saat submit

---

## Portal Siswa & Tutor

Portal ini menggunakan layout terpisah dan **tidak** melalui `index.php`:

```
portal-siswa.php
├── require app/layouts/siswa.php
└── Module files (bukan controller pattern):
    ├── app/modules/portal_siswa/dashboard.php
    ├── app/modules/portal_siswa/nilai.php
    ├── app/modules/portal_siswa/jadwal.php
    ├── app/modules/portal_siswa/tagihan.php
    └── app/modules/portal_siswa/profil.php
```

Setiap file portal langsung output HTML — tidak return `$view_data`.

---

## API (api.php)

- JWT authentication (`app/lib/JWT.php`)
- Router di `app/api/v1/_router.php`
- Webhook CAT di `app/api/webhook-cat.php`

```
GET/POST api.php?v=1&endpoint=xxx
         │
         ├── Verifikasi JWT token di header Authorization
         └── Route ke handler di _router.php
```

---

## File .env — Konfigurasi Lengkap

```env
# Database
DB_HOST=localhost
DB_USER=u741010203_admin
DB_PASS=Perkasa2026
DB_NAME=u741010203_adminperkasa
DB_PORT=3306

# Aplikasi
APP_NAME=Perkasa Mulia Training Center
APP_URL=https://bimbelperkasa.id/admin-baru
APP_ENV=production       # production | development
APP_DEBUG=false          # true = tampilkan error, false = sembunyikan
APP_TIMEZONE=Asia/Jakarta

# Session
SESSION_NAME=pk_sess
SESSION_LIFETIME=7200    # 2 jam
CSRF_TOKEN_LENGTH=32

# WhatsApp (Fonnte)
FONNTE_TOKEN=            # API token Fonnte
FONNTE_SENDER=           # Nomor pengirim WA (format: 628xxx)
ADMIN_WA_NUMBER=08123456789

# Upload
UPLOAD_MAX_SIZE=5242880  # 5MB
UPLOAD_ALLOWED_TYPES=jpg,jpeg,png,pdf

# CAT Psikotes
CAT_WEBHOOK_SECRET=perkasa-cat-secret-2026
CAT_DB_HOST=localhost
CAT_DB_USER=u741010203_admincbt
CAT_DB_PASS=Perkasa2026
CAT_DB_NAME=u741010203_cbtperkasa

# API
API_JWT_SECRET=ganti-dengan-string-acak-minimal-32-karakter
API_KEY=
```

# Perkasa Mulia Training Center — Admin v2.1
## Blueprint untuk Pengembangan AI

> Baca file ini sebelum menyentuh kode apapun. Berisi semua konvensi, pola, dan lokasi penting.
> **Versi:** 2.1 (Mei 2026) — multi-program, paket bundel, redesign SPP

---

## Identitas Aplikasi

**Nama:** Sistem Manajemen Perkasa Mulia Training Center
**Stack:** PHP 8.x + MySQL/MariaDB + Bootstrap 5.3 + Alpine.js + Chart.js
**Hosting:** Shared hosting (Hostinger) — tidak ada Composer, tidak ada Redis
**URL Production:** `https://bimbelperkasa.id/admin-baru`
**DB:** `u741010203_adminperkasa`
**Login default:** `admin@bimbelperkasa.id` / `Perkasa2026`

---

## Apa yang Berubah di v2.1

| Area | Perubahan |
|---|---|
| **Program** | `tipe_program` ENUM(`Program`, `Fasilitas`, `Paket`) — Program reguler ↔ jadwal, Fasilitas seperti mess, Paket = bundel |
| **Membership** | `biaya_per_bulan` per siswa — harga acuan program tidak lagi mengikat, admin bisa custom |
| **Multi-program** | Satu siswa boleh punya >1 membership aktif simultan |
| **Pembayaran SPP** | Form 3-langkah: pilih siswa → centang program (1+ program, harga editable) → pilih jumlah bulan |
| **Auto-extend** | Tiap pembayaran perpanjang `tanggal_selesai_aktif` = +31 hari × jumlah_bulan |
| **Auto-kas** | Pembayaran auto-insert ke `transaksi_keuangan` kategori `Bayar Program` |
| **Tabel baru** | `paket_komponen` (paket → program), `pembayaran_siswa_detail` (line items) |
| **Modul baru** | `program/` — manajemen program reguler/fasilitas/paket |
| **NIS format** | `PMTC-YYNNNN` (tanpa dash tengah, contoh `PMTC-260001`) |
| **Skor jasmani** | Per-item: `skor_lari`, `skor_pullup`, …, `skor_renang` + `nilai_samapta_a/b/ab/gabungan` |

---

## Struktur Direktori

```
admin-baru/
├── index.php                        ← FRONT CONTROLLER (baca dulu)
├── daftar.php                       ← Form pendaftaran publik (tanpa login)
├── login.php / logout.php / login-2fa.php
├── portal-siswa.php                 ← Entry portal siswa (mobile-first)
├── portal-tutor.php                 ← Entry portal tutor (mobile-first)
├── .env                             ← Konfigurasi DB + API keys
├── app/
│   ├── config/
│   │   ├── database.php             ← Load .env, definisikan APP_URL/DB_*/CAT_DB_*
│   │   └── app.php                  ← FONNTE_TOKEN, ADMIN_WA_NUMBER
│   ├── core/
│   │   ├── db.php                   ← db_fetch/_all/_insert/_query/_begin/_commit/_rollback
│   │   ├── helpers.php              ← e(), post(), get(), flash(), csrf_*, format_rupiah, log_action
│   │   ├── auth.php                 ← session_bootstrap, check_auth, auth_id/name/role/is
│   │   ├── cache.php                ← cache_remember/get/set (file-based)
│   │   └── validator.php            ← validate($data)->required()->min(3)->fails()
│   ├── layouts/
│   │   ├── admin.php                ← Sidebar + topbar + footer (favicon + footer logo)
│   │   ├── siswa.php                ← Layout portal siswa (header + bottom nav)
│   │   ├── tutor.php                ← Layout portal tutor (header + bottom nav)
│   │   └── _partials/
│   │       ├── sidebar.php          ← Nav + brand logo
│   │       ├── topbar.php           ← Search + notif bell + user dropdown
│   │       └── flash.php
│   └── modules/
│       ├── dashboard/
│       ├── siswa/                   ← Data Keanggotaan
│       ├── program/                 ← ★ BARU v2.1
│       ├── keuangan/                ← Buku Kas & Arus
│       ├── jadwal/                  ← Jadwal & Tutor (hanya program tipe='Program')
│       ├── nilai/                   ← Rekap Nilai (jasmani/mapel/SKD/psikologi)
│       ├── tutor/
│       ├── pembayaran/              ← ★ REDESIGN v2.1 — multi-program
│       ├── attendance/
│       ├── laporan/
│       ├── pendaftaran/
│       ├── settings/
│       ├── portal_siswa/
│       └── portal_tutor/
├── assets/
│   ├── css/app.css                  ← Design tokens + komponen kustom
│   └── logo.svg                     ← Logo (167KB SVG)
├── psikotes-cat-modified/           ← App CAT (DB terpisah)
└── database/
    ├── install.sql                  ← Fresh install (DROP+CREATE+SEED)
    ├── u741010203_adminperkasa.sql  ← Dump produksi (read-only)
    └── migrations/
        ├── 001..010_*.sql           ← Migration awal (sudah dijalankan)
        ├── 011_add_binjas_skor_columns.sql       ← Skor per item binjas
        └── 012_program_paket_spp_redesign.sql    ← ★ v2.1
```

---

## Routing — `index.php`

Pola: `?page=MODULE&action=ACTION`

```php
$ALLOWED_PAGES = ['dashboard', 'siswa', 'keuangan', 'jadwal', 'nilai', 'tutor',
                  'pembayaran', 'attendance', 'laporan', 'settings', 'pendaftaran',
                  'program'];   // ← v2.1

$ALLOWED_ACTIONS = [/* standar */, 'get_memberships'];   // ← v2.1 AJAX
```

Controller wajib return `$view_data = ['title','view', ...]`. Layout `admin.php` lalu `extract($view_data); include $view_file;`.

---

## Database — Schema-Safe Pattern (WAJIB)

Karena migration mungkin belum dijalankan di server, semua query bergantung kolom/tabel baru wajib di-guard:

```php
static $_has_col = null;
if ($_has_col === null) {
    $_has_col = (bool)db_value(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='program' AND COLUMN_NAME='tipe_program'"
    );
}
$tipe_sel = $_has_col ? "p.tipe_program" : "'Program' AS tipe_program";
$rows = db_fetch_all("SELECT p.id, {$tipe_sel} FROM program p WHERE ...");
```

Pola ini sudah dipakai di: `pembayaran/controller.php`, `program/controller.php`, `siswa/controller.php`.

### Tabel & kolom baru di v2.1

| Tabel | Kolom baru / catatan |
|---|---|
| `program` | `tipe_program` ENUM('Program','Fasilitas','Paket') |
| `membership_siswa` | `biaya_per_bulan` DECIMAL — NULL = pakai default `program.biaya_bulanan` |
| `pembayaran_siswa` | `jumlah_bulan` INT, `keterangan_program` VARCHAR. `periode_bulan` & `membership_id` jadi nullable |
| `paket_komponen` ★ | many-to-many `paket_id` ↔ `program_id` |
| `pembayaran_siswa_detail` ★ | line item per program per pembayaran (`subtotal` GENERATED) |
| `penilaian_binjas` (m011) | `skor_lari`, `skor_pullup`, `skor_situp`, `skor_pushup`, `skor_shuttle`, `skor_lunges`, `skor_renang`, `nilai_samapta_a`, `nilai_samapta_b`, `nilai_ab`, `nilai_gabungan` |

### Konvensi DB

- **Soft delete** pakai `deleted_at` (bukan hard delete) untuk: siswa, tutor, program, pembayaran, transaksi, penilaian_*
- **Audit** — setiap CUD panggil `log_action('VERB','table',$id,$old,$new)`
- **Generated columns** (jangan INSERT manual): `total_skor` (akademik), `rata_psikologi`/`status_psikologi`, `rata_mapel`, `subtotal` (pembayaran_siswa_detail)
- **NIS format:** `PMTC-YYNNNN`

---

## Modul Pembayaran SPP — Alur v2.1

```
[1] Pilih siswa
     ↓ AJAX: GET ?page=pembayaran&action=get_memberships&siswa_id=12
        → JSON {memberships: [{id, nama_program, tipe_program, biaya_per_bulan, ...}]}

[2] Render daftar program (grouped by tipe)
     - Setiap baris: checkbox + nama + harga input editable + sisa hari membership
     - Default harga: COALESCE(membership.biaya_per_bulan, program.biaya_bulanan)

[3] Pilih jumlah bulan (1/2/3/6/12/custom)

[4] Live total = Σ (harga[program_i] × jumlah_bulan)

[5] Input nominal bayar (Lunas / DP 50% / manual)

[6] POST store
     db_begin()
       INSERT pembayaran_siswa            (1 header)
       FOREACH program selected:
         INSERT pembayaran_siswa_detail   (1 line item)
         UPDATE membership_siswa SET
           tanggal_selesai_aktif = DATE_ADD(GREATEST(tgl, CURDATE()), INTERVAL ? DAY),
           biaya_per_bulan = ?            (jika ≠ default)
       IF nominal_bayar > 0:
         INSERT transaksi_keuangan        (kategori='Bayar Program')
     db_commit()
```

**Status pembayaran auto-derived:**
- `bayar = 0` → `Belum Bayar`
- `bayar < tagihan` → `Cicilan`
- `bayar ≥ tagihan` → `Lunas`

---

## Konvensi Frontend

### Alpine.js — `x-cloak` wajib

```html
<div x-show="sidebarOpen" x-cloak>...</div>
```
+ CSS: `[x-cloak] { display: none !important; }`

### Chart.js — defer guard
```js
document.addEventListener('DOMContentLoaded', () => { new Chart(...); });
```

### Logo standar

```html
<img src="<?= rtrim(APP_URL,'/') ?>/assets/logo.svg"
     alt="Logo PMTC"
     style="width:32px;height:32px;object-fit:contain;filter:brightness(0) invert(1);"
     onerror="this.outerHTML='<span style=\'font-size:1.3rem;\'>🏋️</span>'">
```

### Design tokens
```css
--pk-navy-900:   #001233;   /* Sidebar bg */
--pk-yellow-500: #ffc107;   /* Logo accent */
--pk-page-bg:    #f0f2f5;   /* Body bg */
--pk-card-bg:    #ffffff;
```

### Kelas CSS Kustom
```html
<div class="pk-card p-4">       <!-- Card putih dengan shadow -->
<div class="pk-stat-card">      <!-- Stat card di dashboard -->
<table class="table pk-table">  <!-- Tabel custom -->
<button class="btn btn-xs">     <!-- Tombol ekstra kecil -->
.prog-row                       <!-- ★ v2.1 row di pembayaran -->
.tipe-opt                       <!-- ★ v2.1 radio tipe program -->
```

---

## Role System

| Role    | Akses                              |
|---------|------------------------------------|
| `admin` | Semua modul (index.php)            |
| `owner` | Semua modul + laporan khusus       |
| `siswa` | Portal siswa (portal-siswa.php)    |
| `tutor` | Portal tutor (portal-tutor.php)    |

---

## Hal-Hal yang TIDAK BOLEH Dilakukan

1. **Jangan SELECT tanpa prepared statement** — pakai `db_fetch/_all` dengan `?`
2. **Jangan echo data user langsung** — `e($var)` atau `<?= e($var) ?>`
3. **Jangan POST tanpa `csrf_check()`**
4. **Jangan deklarasi PHP function di dalam loop** — pakai `if (!function_exists())` guard atau pindah ke top file *(bug rekap nilai v2.0)*
5. **Jangan pakai `\'` di dalam `<?= ?>` echo** — itu sintaks heredoc, bukan PHP echo
6. **Jangan type string dengan spasi** — `"iisddssssi"` bukan `"iisddssss i"` *(bind_param gagal silent)*
7. **Jangan pakai nama kolom lama:**
   - ❌ `b_indonesia`, `pu`, `wk` → ✅ `bahasa_indonesia`, `pengetahuan_umum`, `wawasan_kebangsaan`
   - ❌ INSERT ke kolom GENERATED
8. **Jangan asumsi migration sudah jalan** — selalu schema-safe check
9. **Jangan buat CSS-only hide untuk elemen Alpine** — pakai `x-show + x-cloak`
10. **Jangan hardcode `APP_URL`** — pakai konstanta
11. **Jangan query langsung ke DB CAT** tanpa `CAT_DB_*` constants

---

## Lessons Learned — Bug Catatan

| Bug | Sebab | Pelajaran |
|---|---|---|
| Rekap nilai jasmani hilang mulai siswa #2 | `function jas_cell()` di dalam `foreach` | Function di top file + `if (!function_exists())` |
| Catat pembayaran `bind_param` error | Type string `"iisddssss i"` ada spasi | Type string TANPA spasi |
| Tambah program error `update&id=0` | `!empty([])` returns true | Guard: `isset && !empty && !empty($id)` |
| Sidebar logo muncul 2× | CSS-based hide brand text — render sebelum Alpine init | `x-show + x-cloak` di elemen, bukan CSS class |
| `daftar.php` fatal `session_bootstrap()` undefined | Race load auth.php | Cek `session_status()` dulu |
| Query `tipe_program` error | Migration belum dijalankan | Schema-safe check |

---

## Cara Menambah Modul Baru — Checklist

1. ✅ Buat folder `app/modules/{nama}/` (`controller.php` + `*.view.php`)
2. ✅ Tambah ke `$ALLOWED_PAGES` di `index.php`
3. ✅ Tambah custom action ke `$ALLOWED_ACTIONS` jika ada
4. ✅ Tambah link ke `app/layouts/_partials/sidebar.php`
5. ✅ Controller wajib return `['title','view',...]`
6. ✅ POST handler wajib `csrf_check()` di awal
7. ✅ Setiap query bergantung kolom baru → schema-safe check
8. ✅ Setiap CUD → `log_action()`
9. ✅ Update `docs/MODULES.md` dengan action list
10. ✅ Update `docs/DATABASE.md` jika ada tabel baru
11. ✅ Buat migration di `database/migrations/`

---

## Integrasi Eksternal

### WhatsApp — Fonnte API
```php
send_wa('08123456789', 'Pesan teks');
$msg = wa_template('pembayaran_konfirmasi',
    ['nama'=>$siswa['nama_lengkap'], 'nominal'=>format_rupiah($n), 'periode'=>'Mei 2026']);
// Template lain: membership_expired, jadwal_reminder, nilai_psikologi
```

### CAT (Computer Assisted Test / Psikotes)
- URL: `psikotes-cat-modified/`
- Hasil dikirim via webhook ke `app/api/webhook-cat.php`
- DB terpisah (`CAT_DB_*`), disinkron ke `penilaian_psikologi`

---

## Dependensi Frontend (CDN)

| Library | Versi | Catatan |
|---------|-------|---------|
| Bootstrap | 5.3.3 | CSS + JS bundle |
| Bootstrap Icons | 1.11.3 | Icon font |
| Alpine.js | 3.14.1 | `defer` — `x-cloak` wajib |
| Chart.js | 4.4.3 | Init dalam `DOMContentLoaded` |
| Tom Select | 2.3.1 | Dropdown dengan search |

---

## File Dokumentasi Lengkap

```
docs/
├── ARCHITECTURE.md   — Arsitektur, request flow, security
├── DATABASE.md       — Schema lengkap 19 tabel + relasi (★ v2.1 update)
├── MODULES.md        — Setiap modul: action, query, view variables (★ v2.1)
├── CONVENTIONS.md    — Pattern kode, anti-pattern, debugging tips (★ v2.1)
└── ROADMAP.md        — ★ BARU — Rencana pengembangan & prioritas
```

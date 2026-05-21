# Referensi Modul — Perkasa Mulia Training Center v2.0

Setiap modul = folder di `app/modules/{modul}/`.  
URL pola: `index.php?page={modul}&action={action}`

---

## 1. dashboard

**Controller:** `app/modules/dashboard/controller.php`  
**View:** `app/modules/dashboard/view.php`

### Actions
| Action | Method | Deskripsi |
|--------|--------|-----------|
| `index` | GET | Halaman utama dashboard |

### Parameter GET
- `periode` — hari_ini / minggu_ini / bulan_ini / 30_hari / tahun_ini / custom
- `tgl_dari`, `tgl_sampai` — digunakan saat `periode=custom`

### Data yang dikembalikan ke view
```php
'siswa_aktif'          // int — jumlah siswa status Aktif
'siswa_total'          // int — total siswa
'siswa_baru'           // int — siswa baru dalam periode
'delta_siswa'          // array ['pct'=>float|null, 'up'=>bool]
'masuk_now'            // float — total pemasukan periode
'delta_masuk'          // array perubahan vs periode sebelumnya
'keluar_now'           // float — total pengeluaran periode
'delta_keluar'         // array perubahan
'saldo_kas'            // float — saldo kumulatif semua waktu
'delta_saldo'          // array perubahan
'chart_labels'         // array string — ['Nov 2025', ..., 'Apr 2026']
'chart_masuk'          // array float — pemasukan per bulan (6 bulan)
'chart_keluar'         // array float — pengeluaran per bulan
'chart_saldo_kumulatif'// array float — saldo kumulatif per bulan
'membership_expiring'  // array — membership ±7 hari dari sekarang
'expired_count'        // int — sudah expired
'jadwal_mendatang'     // array — jadwal 5 hari ke depan
'top_siswa'            // array — top 5 skor SKD periode ini
'qa_programs'          // array — untuk quick action modal
'qa_tutors'            // array
'qa_siswa'             // array — siswa aktif (max 100)
'cohort_lulus'         // array — konversi lulus per tahun
'top_program'          // array — program terpopuler
'att_bulan'            // array — absensi bulan ini per jadwal
'tagihan_stats'        // array — ringkasan tagihan bulan ini
```

### Cache Keys
- `dashboard_trend_chart` — 15 menit
- `dashboard_membership_expiring` — 10 menit
- `db_cohort_lulus` — 1 jam
- `db_top_program` — 30 menit

---

## 2. siswa

**Controller:** `app/modules/siswa/controller.php`  
**Views:** `list.view.php`, `form.view.php`, `detail.view.php`, `trash.view.php`

### Actions
| Action | Method | Deskripsi |
|--------|--------|-----------|
| `index` | GET | Daftar siswa dengan pagination + filter |
| `create` | GET | Form tambah siswa baru |
| `store` | POST | Simpan siswa baru + buat akun + membership |
| `edit` | GET | Form edit siswa |
| `update` | POST | Update data siswa |
| `delete` | GET | Soft delete siswa |
| `detail` | GET | Profil lengkap siswa (tab-based) |
| `tab_data` | GET/AJAX | Lazy load data tab (JSON) |
| `bulk` | POST | Aksi massal (delete/aktifkan/nonaktifkan) |
| `trash` | GET | Daftar siswa yang dihapus (30 hari terakhir) |
| `restore` | GET | Pulihkan siswa dari trash |
| `reset_password` | POST | Reset password + kirim via WA |

### Filter di `index`
- `q` — pencarian nama / nomor_induk / WA
- `status` — Aktif / Tidak Aktif / Lulus / Keluar
- `program` — program_id
- `target` — target_seleksi
- `sort` — nama / nis / status / program / daftar / expired
- `dir` — asc / desc
- `per_page` — 10 / 25 / 50 / 100
- `page` — nomor halaman

### Tab Detail (tab_data AJAX)
```
GET index.php?page=siswa&action=tab_data&id=ID&tab=TAB

tab = membership  → array riwayat membership
tab = nilai       → {akademik:[], binjas:[], mapel:[], psikologi:[]}
tab = kehadiran   → {rows:[], hadir:N, total:N, pct:N}
tab = pembayaran  → array riwayat pembayaran
```

### Form Fields (store/update)
```
nama            — string, required
wa              — string, nomor WA
email           — string, untuk akun login
password        — string, minimal 6 karakter (store saja)
jenis_kelamin   — L / P
tanggal_lahir   — YYYY-MM-DD
nama_ortu       — string
wa_ortu         — string
asal_sekolah    — string
target_seleksi  — string
alamat          — string
status          — Aktif / Tidak Aktif / Lulus / Keluar
keterangan_lulus — string (jika status=Lulus)
program_id      — int (untuk buat membership)
tgl_mulai       — YYYY-MM-DD
tgl_selesai     — YYYY-MM-DD
```

### Nomor Induk Auto-generate
Format: `PMTC-YY-NNNN` (contoh: `PMTC-26-0001`)

---

## 3. keuangan

**Controller:** `app/modules/keuangan/controller.php`  
**Views:** `list.view.php`, `form.view.php`

### Actions
| Action | Method | Deskripsi |
|--------|--------|-----------|
| `index` | GET | Buku kas dengan filter + ringkasan |
| `create` | GET | Form transaksi baru |
| `store` | POST | Simpan transaksi |
| `edit` | GET | Form edit transaksi |
| `update` | POST | Update transaksi |
| `delete` | GET | Hapus transaksi |

### Filter di `index`
- `f_arus` — Pemasukan / Pengeluaran
- `f_kategori` — kategori transaksi
- `f_mulai`, `f_selesai` — range tanggal
- `preset` — bulan_ini / bulan_lalu / 3_bulan / tahun_ini

### Form Fields
```
jenis_arus              — Pemasukan / Pengeluaran, required
kategori                — string, required
keterangan_transaksi    — text
nominal                 — decimal, required
tanggal_transaksi       — datetime
```

---

## 4. jadwal

**Controller:** `app/modules/jadwal/controller.php`  
**Views:** `calendar.view.php`, `list.view.php`, `form.view.php`

### Actions
| Action | Method | Deskripsi |
|--------|--------|-----------|
| `index` | GET | Kalender jadwal (default) |
| `create` | GET | Form jadwal baru |
| `store` | POST | Simpan jadwal + assign tutor |
| `edit` | GET | Form edit jadwal |
| `update` | POST | Update jadwal + update jadwal_tutor |
| `delete` | GET | Hapus jadwal + jadwal_tutor |
| `get_json` | GET/AJAX | Fetch data jadwal untuk modal (JSON) |

### Filter Kalender
- `bulan` — YYYY-MM (default: bulan ini)
- `view` — calendar / list

### Form Fields
```
program_id      — int
nama_kegiatan   — Jasmani / Renang / Akademik / Psikologi / Tryout
materi          — string
tanggal         — YYYY-MM-DD, required
waktu_mulai     — HH:MM, required
waktu_selesai   — HH:MM, required
lokasi          — string
tutor_ids[]     — array int (multi-select tutor)
```

---

## 5. nilai

**Controller:** `app/modules/nilai/controller.php`  
**Views:** `list.view.php`, `form.view.php`, `import.view.php`, `rekap_nilai.view.php`

### Actions
| Action | Method | Deskripsi |
|--------|--------|-----------|
| `index` | GET | List nilai (semua kelompok) |
| `create` | GET | Form input nilai |
| `store_jasmani` | POST | Simpan nilai jasmani |
| `store_mapel` | POST | Simpan nilai mapel |
| `store_skd` | POST | Simpan nilai SKD |
| `store_psikologi` | POST | Simpan nilai psikologi |
| `edit` | GET | Form edit nilai |
| `update` | POST | Update nilai |
| `delete` | GET | Hapus nilai (soft) |
| `import` | GET | Halaman import massal |
| `process_import` | POST | Proses file Excel/CSV |
| `rekap_nilai` | GET | Rekap nilai semua siswa |
| `export_rekap_nilai` | GET | CSV export rekap nilai |
| `hitung_binjas` | GET/AJAX | Hitung skor binjas otomatis |

### Form Fields — Jasmani
```
siswa_id            — int, required
tanggal_tes         — date, required
lari_jarak_meter    — int
pullup_repetisi     — int
pushup_repetisi     — int
situp_repetisi      — int
```

### Form Fields — Mapel
```
siswa_id            — int, required
tanggal_tes         — date, required
bahasa_indonesia    — int (0-100)
bahasa_inggris      — int (0-100)
matematika          — int (0-100)
pengetahuan_umum    — int (0-100)
wawasan_kebangsaan  — int (0-100)
```

### Form Fields — SKD
```
siswa_id            — int, required
tanggal_tes         — date, required
nilai_twk           — int
nilai_tiu           — int
nilai_tkp           — int
kategori_tes        — SKD / SKB / Tryout / Simulasi
```

### Form Fields — Psikologi
```
siswa_id            — int, required
tanggal_tes         — date, required
kecerdasan          — int (0-100)
kecermatan          — int (0-100)
kepribadian         — int (0-100)
```

### Filter Rekap Nilai
- `q` — nama / NIS siswa
- `f_program` — program_id
- `f_tgl_dari`, `f_tgl_sampai` — range tanggal
- `f_kelompok[]` — array: jasmani / mapel / skd / psikologi

---

## 6. tutor

**Controller:** `app/modules/tutor/controller.php`  
**Views:** `list.view.php`, `form.view.php`, `rekap_gaji.view.php`, `rekap_tutor.view.php`

### Actions
| Action | Method | Deskripsi |
|--------|--------|-----------|
| `index` | GET | Daftar tutor |
| `create` | GET | Form tutor baru |
| `store` | POST | Simpan tutor |
| `edit` | GET | Form edit tutor |
| `update` | POST | Update tutor |
| `delete` | GET | Soft delete tutor |
| `rekap_gaji` | GET | Rekap honor/gaji per bulan |
| `bayar_gaji` | POST | Tandai gaji sudah dibayar |
| `rekap_tutor` | GET | Rekap sesi per tutor + filter |
| `export_rekap_tutor` | GET | CSV export rekap tutor |

### Form Fields
```
nama_lengkap    — string, required
nomor_hp        — string
nomor_wa        — string
email           — string
spesialisasi    — string (Jasmani, Renang, Akademik, dll)
tarif_per_sesi  — decimal
bank_nama       — string
bank_rekening   — string
bank_atas_nama  — string
status_aktif    — 1 / 0
```

### Filter Rekap Tutor
- `f_tutor` — tutor_id
- `f_program` — program_id
- `f_kegiatan` — Jasmani / Renang / Akademik / Psikologi / Tryout
- `f_dari`, `f_sampai` — range tanggal

---

## 6b. program ★ BARU v2.1

**Controller:** `app/modules/program/controller.php`
**Views:** `list.view.php`, `form.view.php`

### Actions
| Action | Method | Deskripsi |
|--------|--------|-----------|
| `index` | GET | Daftar program dikelompokkan per `tipe_program` |
| `create` | GET | Form tambah program |
| `store` | POST | Insert program + komponen paket (jika tipe=Paket) |
| `edit` | GET | Form edit |
| `update` | POST | Update + reset komponen paket |
| `delete` | GET | Soft delete (set `is_active=0`, `deleted_at=NOW()`) |

### Form Fields
```
nama_program     — varchar, required
tipe_program     — ENUM('Program','Fasilitas','Paket')
deskripsi        — text
biaya_bulanan    — decimal (acuan, bukan tetap)
is_active        — bool
komponen[]       — int[] (hanya jika tipe=Paket)
```

### Schema-safe pattern
Semua query bergantung `tipe_program` atau `paket_komponen` dibungkus check `information_schema`:
```php
static $_has_tipe_u = null;
if ($_has_tipe_u === null) $_has_tipe_u = (bool)db_value(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='program' AND COLUMN_NAME='tipe_program'"
);
```

### Data ke view
```php
'daftar'         // array — semua program (grouped by tipe_program di view)
'tipe_filter'    // string — filter aktif
'item'           // array|null — saat edit
'komponen_ids'   // array — program_id yang sudah jadi komponen paket
'daftar_program' // array — pilihan program untuk komponen paket
```

---

## 7. pembayaran ★ REDESIGN v2.1

**Controller:** `app/modules/pembayaran/controller.php`
**Views:** `list.view.php`, `form.view.php`

### Actions
| Action | Method | Deskripsi |
|--------|--------|-----------|
| `index` | GET | Daftar pembayaran dengan stats |
| `create` | GET | Form tagihan baru (3-langkah multi-program) |
| `store` | POST | Multi-INSERT + auto-extend membership + auto-kas |
| `edit` | GET | Form edit (hanya nominal bayar yang bisa diubah) |
| `update` | POST | Update nominal + auto-kas selisih |
| `delete` | GET | Soft delete |
| **`get_memberships`** ★ | GET (AJAX) | Return JSON membership aktif siswa |

### Flow Form Pembayaran (Create)
```
[1] Pilih siswa
     ↓ AJAX get_memberships?siswa_id=12 → JSON
[2] Render daftar program siswa (grouped by tipe)
     - Setiap baris: checkbox + nama + harga editable + sisa hari
[3] Pilih jumlah bulan (1/2/3/6/12/custom)
[4] Live total = Σ (harga × bulan)
[5] Input nominal bayar
[6] Submit → multi-INSERT dalam transaction
```

### Form Fields
```
siswa_id            — int, required
membership_ids[]    — int[], required (boleh 1+ program)
biaya_per_bulan[N]  — decimal per membership_id (boleh edit dari default)
jumlah_bulan        — int, required
nominal_bayar       — decimal
metode_pembayaran   — Tunai/Transfer/QRIS/Lainnya
tanggal_bayar       — date
keterangan          — text (opsional)
```

### Logic Saat Store
```php
db_begin();
$pay_id = db_insert(/* pembayaran_siswa header */);
foreach ($membership_ids as $mid) {
    db_insert(/* pembayaran_siswa_detail line */);
    db_query(/* UPDATE membership_siswa
                SET tanggal_selesai_aktif = DATE_ADD(GREATEST(tgl, CURDATE()), INTERVAL ? DAY)
                WHERE id = ? */, "ii", [31 * $jumlah_bulan, $mid]);
    if ($harga_custom !== $harga_default) {
        db_query(/* UPDATE biaya_per_bulan */);
    }
}
if ($bayar > 0) {
    db_insert(/* transaksi_keuangan kategori='Bayar Program' */);
}
db_commit();
```

### Status Bayar (auto-derived)
- `nominal_bayar = 0` → `Belum Bayar`
- `0 < nominal_bayar < tagihan` → `Cicilan`
- `nominal_bayar ≥ tagihan` → `Lunas`

### Edit Mode
Hanya bisa edit `nominal_bayar` (untuk pelunasan cicilan). Detail program & jumlah bulan tidak bisa diubah pasca-create. Selisih bayar otomatis insert ke `transaksi_keuangan`.

---

## 8. attendance

**Controller:** `app/modules/attendance/controller.php`  
**Views:** `list.view.php`, `form.view.php`

### Actions
| Action | Method | Deskripsi |
|--------|--------|-----------|
| `index` | GET | Daftar jadwal (pilih untuk absen) |
| `form` | GET | Form absensi per jadwal |
| `store_bulk` | POST | Simpan absensi massal |

### Filter index
- `bulan` — YYYY-MM
- `kegiatan` — Jasmani / Renang / dll

### Form Fields (store_bulk)
```
jadwal_id               — int, required
status_hadir[siswa_id]  — Hadir / Izin / Sakit / Alpa (per siswa)
keterangan[siswa_id]    — string opsional
```

---

## 9. laporan

**Controller:** `app/modules/laporan/controller.php`  
**Views:** `view.php`, `rapor.view.php`

### Actions
| Action | Method | Deskripsi |
|--------|--------|-----------|
| `index` | GET | Halaman pilihan laporan |
| `rapor` | GET | Rapor nilai siswa (printable/PDF) |
| `export_siswa` | GET | CSV: data siswa |
| `export_transaksi` | GET | CSV: transaksi keuangan |
| `export_gaji` | GET | CSV: rekap gaji tutor |
| `export_nilai` | GET | CSV: rekap nilai semua siswa |

### Rapor Nilai (rapor)
```
GET index.php?page=laporan&action=rapor&id=SISWA_ID&tgl_dari=YYYY-MM-DD&tgl_sampai=YYYY-MM-DD
```
- Standalone HTML yang bisa di-print atau Save as PDF via browser
- Menampilkan 4 seksi: Jasmani, Mapel, SKD, Psikologi
- Filter periode opsional

### Export CSV
```
GET index.php?page=laporan&action=export_siswa
GET index.php?page=laporan&action=export_transaksi&bulan=2026-05
GET index.php?page=laporan&action=export_gaji&bulan=2026-05
GET index.php?page=laporan&action=export_nilai
```

---

## 10. pendaftaran

**Controller:** `app/modules/pendaftaran/controller.php`  
**Views:** `list.view.php`

### Actions
| Action | Method | Deskripsi |
|--------|--------|-----------|
| `index` | GET | Antrian pendaftaran |
| `terima` | POST | Terima calon → buat akun + siswa + kirim WA |
| `tolak` | POST | Tolak + kirim WA |
| `proses` | POST | Tandai sedang diproses |

### Filter index
- `status` — Baru / Dihubungi / Diterima / Ditolak
- `q` — pencarian nama / WA

### Saat Terima (terima)
1. Insert ke `users` (role=siswa, password auto-generated)
2. Insert ke `siswa` (auto-generate nomor_induk)
3. Update `calon_siswa.status = 'Diterima'`
4. Kirim WA ke nomor calon

---

## 11. settings

**Controller:** `app/modules/settings/controller.php`  
**Views:** `view.php`, `setup_2fa.view.php`

### Actions
| Action | Method | Deskripsi |
|--------|--------|-----------|
| `index` | GET | Halaman pengaturan akun |
| `update_password` | POST | Ganti password |
| `update_profile` | POST | Update nama display |
| `setup_2fa` | GET | Halaman setup 2FA (QR code) |
| `enable_2fa` | POST | Verifikasi TOTP code + aktifkan |
| `disable_2fa` | POST | Nonaktifkan 2FA |

---

## Portal Siswa (portal-siswa.php)

Layout terpisah dari admin. Modules di `app/modules/portal_siswa/`.

| File | Halaman |
|------|---------|
| `dashboard.php` | Dashboard siswa (jadwal, nilai ringkas) |
| `nilai.php` | Riwayat nilai siswa sendiri |
| `jadwal.php` | Jadwal latihan bulan ini |
| `tagihan.php` | Status pembayaran SPP |
| `profil.php` | Edit profil siswa |

---

## Portal Tutor (portal-tutor.php)

Layout terpisah. Modules di `app/modules/portal_tutor/`.

| File | Halaman |
|------|---------|
| `dashboard.php` | Dashboard tutor |
| `jadwal.php` | Jadwal mengajar |
| `attendance.php` | Input absensi siswa (dari sisi tutor) |
| `gaji.php` | Rekap honor/gaji |
| `nilai.php` | Input nilai (dari sisi tutor) |

---

## Menambah Action Baru ke Modul yang Ada

### Contoh: Tambah `rekap_bulanan` ke modul `keuangan`

**Step 1:** Tambah ke whitelist di `index.php` (baris ~57):
```php
$ALLOWED_ACTIONS = [..., 'rekap_bulanan'];
```

**Step 2:** Tambah handler di `app/modules/keuangan/controller.php`:
```php
if ($action === 'rekap_bulanan') {
    $bulan = get('bulan', date('Y-m'));
    $rows  = db_fetch_all(
        "SELECT kategori, SUM(nominal) AS total, COUNT(*) AS count
         FROM transaksi_keuangan
         WHERE jenis_arus=? AND DATE_FORMAT(tanggal_transaksi,'%Y-%m')=? AND deleted_at IS NULL
         GROUP BY kategori ORDER BY total DESC",
        "ss", ["Pemasukan", $bulan]
    );
    return [
        'title'       => 'Rekap Bulanan',
        'view'        => __DIR__ . '/rekap_bulanan.view.php',
        'breadcrumbs' => [['label' => 'Keuangan', 'url' => 'index.php?page=keuangan'], ['label' => 'Rekap Bulanan']],
        'rows'        => $rows,
        'bulan'       => $bulan,
    ];
}
```

**Step 3:** Buat `app/modules/keuangan/rekap_bulanan.view.php`

**Step 4:** (Opsional) Tambah tombol di `list.view.php` keuangan.

---

## Menambah Modul Baru Sepenuhnya

### Contoh: Modul `inventaris`

**Step 1:** Tambah ke `$ALLOWED_PAGES` di `index.php`:
```php
$ALLOWED_PAGES = [..., 'inventaris'];
```

**Step 2:** Tambah action ke `$ALLOWED_ACTIONS`:
```php
$ALLOWED_ACTIONS = [..., 'checkout', 'checkin'];  // action kustom
```

**Step 3:** Buat file:
```
app/modules/inventaris/
├── controller.php
├── list.view.php
└── form.view.php
```

**Step 4:** Tambah ke sidebar (`app/layouts/_partials/sidebar.php`):
```php
$nav_items = [
    ...
    ['page' => 'inventaris', 'icon' => 'bi-box-seam-fill', 'label' => 'Inventaris'],
];
```

**Step 5:** Buat tabel DB jika perlu (dengan `CREATE TABLE IF NOT EXISTS`).

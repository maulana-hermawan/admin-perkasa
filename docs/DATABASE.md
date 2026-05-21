# Database Schema — Perkasa Mulia Training Center v2.1

**Database:** `u741010203_adminperkasa`
**Engine:** InnoDB, charset UTF8MB4
**Total tabel:** 21 tabel + 1 view _(★ v2.1: +`paket_komponen`, +`pembayaran_siswa_detail`)_

---

## Daftar Tabel

| # | Tabel | Fungsi |
|---|-------|--------|
| 1 | `users` | Akun login semua role |
| 2 | `cabang` | Data cabang/lokasi |
| 3 | `siswa` | Data anggota/peserta |
| 4 | `tutor` | Data coach/pelatih |
| 5 | `program` | Program latihan / fasilitas / paket _(★ v2.1: +`tipe_program`)_ |
| 5b | **`paket_komponen`** ★ | Many-to-many paket ↔ program |
| 6 | `membership_siswa` | Program yang diikuti siswa _(★ v2.1: +`biaya_per_bulan`)_ |
| 7 | `jadwal` | Jadwal sesi latihan |
| 8 | `jadwal_tutor` | Penugasan tutor ke jadwal |
| 9 | `attendance_siswa` | Kehadiran siswa per sesi |
| 10 | `absensi_tutor` | Kehadiran tutor per jadwal |
| 11 | `penilaian_binjas` | Nilai jasmani _(m011: +skor per item, +Samapta A/B/AB)_ |
| 12 | `penilaian_mapel` | Nilai mata pelajaran |
| 13 | `penilaian_akademik` | Nilai SKD (tryout) |
| 14 | `penilaian_psikologi` | Nilai psikologi |
| 15 | `pembayaran_siswa` | Tagihan & pembayaran SPP _(★ v2.1: +`jumlah_bulan`, +`keterangan_program`)_ |
| 15b | **`pembayaran_siswa_detail`** ★ | Line items per program per pembayaran |
| 16 | `transaksi_keuangan` | Buku kas (pemasukan & pengeluaran) |
| 17 | `calon_siswa` | Antrian pendaftaran (dari `daftar.php`) |
| 18 | `notifikasi` | Notifikasi in-app |
| 19 | `audit_log` | Log semua aktivitas CRUD |

---

## Schema Detail

### 1. users
Akun login untuk semua tipe pengguna.

```sql
CREATE TABLE users (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email               VARCHAR(100) NOT NULL UNIQUE,
    password            VARCHAR(255) NOT NULL,           -- bcrypt hash
    role                ENUM('admin','owner','siswa','tutor') NOT NULL DEFAULT 'siswa',
    nama_display        VARCHAR(100) DEFAULT NULL,
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at       DATETIME DEFAULT NULL,
    failed_login_count  INT NOT NULL DEFAULT 0,
    locked_until        DATETIME DEFAULT NULL,
    totp_secret         VARCHAR(32) DEFAULT NULL,        -- TOTP 2FA secret
    totp_enabled        TINYINT(1) NOT NULL DEFAULT 0,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          DATETIME DEFAULT NULL
);
```

**Query umum:**
```php
// Cek role user
$u = db_fetch("SELECT * FROM users WHERE id=?", "i", [$id]);

// Login validation
$u = db_fetch("SELECT * FROM users WHERE email=? AND deleted_at IS NULL", "s", [$email]);

// Update terakhir login
db_query("UPDATE users SET last_login_at=NOW(), failed_login_count=0 WHERE id=?", "i", [$id]);
```

---

### 2. cabang
```sql
CREATE TABLE cabang (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_cabang VARCHAR(100) NOT NULL,
    alamat      TEXT DEFAULT NULL,
    telepon     VARCHAR(20) DEFAULT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

---

### 3. siswa
Data anggota/peserta bimbel.

```sql
CREATE TABLE siswa (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED DEFAULT NULL,           -- FK ke users (nullable)
    nomor_induk      VARCHAR(20) DEFAULT NULL UNIQUE,     -- Format: PMTC-YY-NNNN
    nama_lengkap     VARCHAR(100) NOT NULL,
    jenis_kelamin    ENUM('L','P') DEFAULT NULL,
    tanggal_lahir    DATE DEFAULT NULL,
    nomor_wa         VARCHAR(20) DEFAULT NULL,
    nama_ortu        VARCHAR(100) DEFAULT NULL,
    nomor_wa_ortu    VARCHAR(20) DEFAULT NULL,
    asal_sekolah     VARCHAR(100) DEFAULT NULL,
    target_seleksi   VARCHAR(100) DEFAULT NULL,           -- Polri, TNI AD, TNI AL, dll
    alamat           TEXT DEFAULT NULL,
    status_siswa     ENUM('Aktif','Tidak Aktif','Lulus','Keluar') NOT NULL DEFAULT 'Aktif',
    keterangan_lulus VARCHAR(255) DEFAULT NULL,           -- contoh: "Diterima Polri 2026"
    catatan          TEXT DEFAULT NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at       DATETIME DEFAULT NULL                -- soft delete
);
```

**Query umum:**
```php
// List dengan membership
$rows = db_fetch_all(
    "SELECT s.*, p.nama_program, m.tanggal_selesai_aktif, m.status_membership
     FROM siswa s
     LEFT JOIN (SELECT siswa_id, MAX(id) mid FROM membership_siswa GROUP BY siswa_id) mm
           ON s.id = mm.siswa_id
     LEFT JOIN membership_siswa m ON mm.mid = m.id
     LEFT JOIN program p ON m.program_id = p.id
     WHERE s.deleted_at IS NULL
     ORDER BY s.nama_lengkap"
);

// Generate nomor induk
$last = db_value("SELECT nomor_induk FROM siswa WHERE nomor_induk LIKE ? ORDER BY id DESC LIMIT 1", "s", ["PMTC-26-%"]);
```

---

### 4. tutor
Data coach/pelatih.

```sql
CREATE TABLE tutor (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap    VARCHAR(100) NOT NULL,
    nomor_hp        VARCHAR(20) DEFAULT NULL,
    nomor_wa        VARCHAR(20) DEFAULT NULL,
    email           VARCHAR(100) DEFAULT NULL,
    spesialisasi    VARCHAR(100) DEFAULT NULL,            -- Jasmani, Renang, Akademik, dll
    tarif_per_sesi  DECIMAL(12,2) DEFAULT 0,
    status_aktif    TINYINT(1) NOT NULL DEFAULT 1,
    bank_nama       VARCHAR(50) DEFAULT NULL,             -- BCA, BRI, Mandiri, dll
    bank_rekening   VARCHAR(30) DEFAULT NULL,
    bank_atas_nama  VARCHAR(100) DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      DATETIME DEFAULT NULL
);
```

**Query umum:**
```php
// List aktif dengan jumlah sesi bulan ini
$rows = db_fetch_all(
    "SELECT t.*, COUNT(jt.jadwal_id) AS sesi_bulan_ini
     FROM tutor t
     LEFT JOIN jadwal_tutor jt ON jt.tutor_id = t.id
     LEFT JOIN jadwal j ON jt.jadwal_id = j.id AND DATE_FORMAT(j.tanggal,'%Y-%m') = ?
     WHERE t.deleted_at IS NULL AND t.status_aktif = 1
     GROUP BY t.id ORDER BY t.nama_lengkap",
    "s", [date('Y-m')]
);
```

---

### 5. program
Program pelatihan yang tersedia.

```sql
CREATE TABLE program (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_program  VARCHAR(100) NOT NULL,
    tipe_program  ENUM('Program','Fasilitas','Paket') NOT NULL DEFAULT 'Program',  -- ★ v2.1
    kategori_program ENUM('Jasmani','Akademik','Fasilitas') NOT NULL DEFAULT 'Akademik',
    deskripsi     TEXT DEFAULT NULL,
    durasi_bulan  INT NOT NULL DEFAULT 6,
    biaya_bulanan DECIMAL(12,2) DEFAULT 0 COMMENT 'Harga ACUAN, bukan harga tetap',  -- ★ v2.1
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    deleted_at    DATETIME DEFAULT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

**★ v2.1 — `tipe_program`:**
- `Program` — terhubung ke jadwal (Akademik Intensif/Reguler, Jasmani Intensif/Reguler, Kelas Tambahan, Private)
- `Fasilitas` — non-jadwal (Mess)
- `Paket` — bundel 2+ program, komponennya di tabel `paket_komponen`

**★ v2.1 — `biaya_bulanan`:** hanya **acuan**. Harga aktual per siswa disimpan di `membership_siswa.biaya_per_bulan`. Admin bisa custom harga per individu.

**Data default (setelah migration 012):**
| Nama | Tipe | Acuan/Bulan |
|------|------|-------------|
| Akademik Intensif | Program | Rp 1.500.000 |
| Akademik Reguler | Program | Rp 1.000.000 |
| Jasmani Intensif | Program | Rp 1.200.000 |
| Jasmani Reguler | Program | Rp 800.000 |
| Kelas Tambahan | Program | Rp 300.000 |
| Private | Program | Rp 500.000 |
| Mess / Fasilitas | Fasilitas | Rp 800.000 |
| Paket Intensif (Akademik+Jasmani Intensif) | Paket | Rp 2.500.000 |
| Paket Reguler (Akademik+Jasmani Reguler) | Paket | Rp 1.700.000 |

---

### 5b. paket_komponen ★ v2.1
Many-to-many relationship antara paket dan program komponennya.

```sql
CREATE TABLE paket_komponen (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paket_id    INT UNSIGNED NOT NULL COMMENT 'FK ke program (tipe=Paket)',
    program_id  INT UNSIGNED NOT NULL COMMENT 'FK ke program (tipe=Program)',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_paket_program (paket_id, program_id),
    INDEX idx_paket   (paket_id),
    INDEX idx_program (program_id)
);
```

**Contoh:**
- Paket Intensif (id=8) → komponen: Akademik Intensif (id=1) + Jasmani Intensif (id=3)
- Paket Reguler (id=9) → komponen: Akademik Reguler (id=2) + Jasmani Reguler (id=4)

---

### 6. membership_siswa
Program yang sedang/pernah diikuti siswa. **★ v2.1 — satu siswa boleh multi-membership aktif simultan.**

```sql
CREATE TABLE membership_siswa (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    siswa_id             INT UNSIGNED NOT NULL,
    program_id           INT UNSIGNED NOT NULL,
    biaya_per_bulan      DECIMAL(12,2) NULL COMMENT 'Custom per siswa. NULL=pakai program.biaya_bulanan',  -- ★ v2.1
    tanggal_mulai_aktif  DATE NOT NULL,
    tanggal_selesai_aktif DATE NOT NULL,
    status_membership    ENUM('Berjalan','Selesai','Cuti','Dibatalkan') NOT NULL DEFAULT 'Berjalan',
    created_by           INT UNSIGNED DEFAULT NULL,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id),
    FOREIGN KEY (program_id) REFERENCES program(id)
);
```

**★ v2.1 — `biaya_per_bulan`:**
- NULL → pakai harga acuan dari `program.biaya_bulanan`
- Filled → harga custom yang sudah dinegosiasi admin per siswa ini
- Auto-update saat admin edit harga di form pembayaran SPP

**Query harga aktif:**
```sql
SELECT m.id, m.siswa_id, m.program_id,
       COALESCE(m.biaya_per_bulan, p.biaya_bulanan) AS harga_aktif
FROM membership_siswa m JOIN program p ON m.program_id = p.id
WHERE m.siswa_id = ? AND m.status_membership IN ('Aktif','Berjalan');
```

**Query umum:**
```php
// Membership aktif + hampir expired (14 hari ke depan)
$expiring = db_fetch_all(
    "SELECT s.nama_lengkap, s.nomor_wa, m.tanggal_selesai_aktif, p.nama_program,
            DATEDIFF(m.tanggal_selesai_aktif, CURDATE()) AS sisa_hari
     FROM membership_siswa m
     JOIN siswa s ON m.siswa_id = s.id
     JOIN program p ON m.program_id = p.id
     WHERE m.status_membership = 'Berjalan'
       AND m.tanggal_selesai_aktif BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                                       AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)
       AND s.deleted_at IS NULL
     ORDER BY m.tanggal_selesai_aktif"
);
```

---

### 7. jadwal
Jadwal sesi latihan.

```sql
CREATE TABLE jadwal (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program_id      INT UNSIGNED DEFAULT NULL,
    nama_kegiatan   ENUM('Jasmani','Renang','Akademik','Psikologi','Tryout') NOT NULL,
    materi          VARCHAR(200) DEFAULT NULL,
    tanggal         DATE NOT NULL,
    waktu_mulai     TIME NOT NULL,
    waktu_selesai   TIME NOT NULL,
    lokasi          VARCHAR(100) DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES program(id)
    -- CATATAN: TIDAK ADA kolom created_by di tabel ini
);
```

**Query umum:**
```php
// Jadwal bulan tertentu dengan nama tutor
$jadwal = db_fetch_all(
    "SELECT j.*, p.nama_program,
            GROUP_CONCAT(t.nama_lengkap ORDER BY t.nama_lengkap SEPARATOR ', ') AS nama_tutor_list,
            COUNT(jt.tutor_id) AS jumlah_tutor
     FROM jadwal j
     LEFT JOIN program p ON j.program_id = p.id
     LEFT JOIN jadwal_tutor jt ON j.id = jt.jadwal_id
     LEFT JOIN tutor t ON jt.tutor_id = t.id
     WHERE DATE_FORMAT(j.tanggal, '%Y-%m') = ?
     GROUP BY j.id
     ORDER BY j.tanggal ASC, j.waktu_mulai ASC",
    "s", [$bulan]
);
```

---

### 8. jadwal_tutor
Penugasan (many-to-many) tutor ke jadwal.

```sql
CREATE TABLE jadwal_tutor (
    jadwal_id   INT UNSIGNED NOT NULL,
    tutor_id    INT UNSIGNED NOT NULL,
    PRIMARY KEY (jadwal_id, tutor_id),
    FOREIGN KEY (jadwal_id) REFERENCES jadwal(id) ON DELETE CASCADE,
    FOREIGN KEY (tutor_id)  REFERENCES tutor(id)
);
```

**Query umum:**
```php
// Saat simpan jadwal: hapus semua tutor lama, insert tutor baru
db_query("DELETE FROM jadwal_tutor WHERE jadwal_id=?", "i", [$jadwal_id]);
foreach ($tutor_ids as $tid) {
    db_query("INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (?,?)", "ii", [$jadwal_id, $tid]);
}
```

---

### 9. attendance_siswa
Kehadiran siswa per sesi jadwal.

```sql
CREATE TABLE attendance_siswa (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    siswa_id       INT UNSIGNED NOT NULL,
    jadwal_id      INT UNSIGNED NOT NULL,
    status_hadir   ENUM('Hadir','Izin','Sakit','Alpa') NOT NULL DEFAULT 'Hadir',
    keterangan     VARCHAR(255) DEFAULT NULL,
    dicatat_oleh   INT UNSIGNED DEFAULT NULL,            -- users.id
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_siswa_jadwal (siswa_id, jadwal_id),
    FOREIGN KEY (siswa_id) REFERENCES siswa(id),
    FOREIGN KEY (jadwal_id) REFERENCES jadwal(id)
);
```

---

### 10. absensi_tutor
Kehadiran tutor per jadwal.

```sql
CREATE TABLE absensi_tutor (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jadwal_id     INT UNSIGNED NOT NULL,
    tutor_id      INT UNSIGNED NOT NULL,
    status_hadir  ENUM('Hadir','Izin','Sakit','Alpa') NOT NULL DEFAULT 'Hadir',
    keterangan    VARCHAR(255) DEFAULT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

---

### 11. penilaian_binjas
Nilai jasmani / samapta.

```sql
CREATE TABLE penilaian_binjas (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    siswa_id            INT UNSIGNED NOT NULL,
    tanggal_tes         DATE NOT NULL,
    lari_jarak_meter    INT DEFAULT NULL,                -- Jarak lari (meter, waktu dikonversi ke meter)
    pullup_repetisi     INT DEFAULT NULL,
    pushup_repetisi     INT DEFAULT NULL,
    situp_repetisi      INT DEFAULT NULL,
    skor_akhir          DECIMAL(5,2) DEFAULT NULL,       -- Skor gabungan
    predikat            ENUM('Baik Sekali','Baik','Cukup','Kurang') DEFAULT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at          DATETIME DEFAULT NULL,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id)
    -- CATATAN: TIDAK ADA total_skor, skor_lari, skor_pullup, target_kategori
);
```

**Scoring:** Logika perhitungan skor ada di `app/core/binjas_scoring.php`.

---

### 12. penilaian_mapel
Nilai mata pelajaran.

```sql
CREATE TABLE penilaian_mapel (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    siswa_id            INT UNSIGNED NOT NULL,
    tanggal_tes         DATE NOT NULL,
    bahasa_indonesia    INT DEFAULT NULL,                -- Skor 0-100
    bahasa_inggris      INT DEFAULT NULL,
    matematika          INT DEFAULT NULL,
    pengetahuan_umum    INT DEFAULT NULL,
    wawasan_kebangsaan  INT DEFAULT NULL,
    rata_mapel          DECIMAL(5,2) GENERATED ALWAYS AS (
                            (COALESCE(bahasa_indonesia,0) + COALESCE(bahasa_inggris,0) +
                             COALESCE(matematika,0) + COALESCE(pengetahuan_umum,0) +
                             COALESCE(wawasan_kebangsaan,0)) / 5
                        ) STORED,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at          DATETIME DEFAULT NULL,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id)
    -- CATATAN: TIDAK ADA kolom b_indonesia, pu, wk, komputer, status_mapel
    -- rata_mapel adalah GENERATED — jangan di-INSERT
);
```

**INSERT yang benar:**
```php
db_insert(
    "INSERT INTO penilaian_mapel (siswa_id, tanggal_tes, bahasa_indonesia, bahasa_inggris, matematika, pengetahuan_umum, wawasan_kebangsaan)
     VALUES (?,?,?,?,?,?,?)",
    "issiiiii",
    [$siswa_id, $tgl, $b_indo, $b_ing, $mat, $pu, $wk]
);
```

---

### 13. penilaian_akademik
Nilai SKD (Seleksi Kompetensi Dasar) / tryout.

```sql
CREATE TABLE penilaian_akademik (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    siswa_id      INT UNSIGNED NOT NULL,
    tanggal_tes   DATE NOT NULL,
    nilai_twk     INT DEFAULT NULL,                     -- Tes Wawasan Kebangsaan
    nilai_tiu     INT DEFAULT NULL,                     -- Tes Intelegensia Umum
    nilai_tkp     INT DEFAULT NULL,                     -- Tes Karakteristik Pribadi
    total_skor    INT GENERATED ALWAYS AS (
                      COALESCE(nilai_twk,0) + COALESCE(nilai_tiu,0) + COALESCE(nilai_tkp,0)
                  ) STORED,                             -- GENERATED — JANGAN DI-INSERT
    kategori_tes  ENUM('SKD','SKB','Tryout','Simulasi') NOT NULL DEFAULT 'SKD',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at    DATETIME DEFAULT NULL,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id)
    -- CATATAN: TIDAK ADA passing_grade_status, sumber
    -- total_skor adalah GENERATED — jangan di-INSERT
);
```

**Standar kelulusan SKD CPNS:**
- TWK ≥ 65
- TIU ≥ 80  
- TKP ≥ 166

**INSERT yang benar:**
```php
db_insert(
    "INSERT INTO penilaian_akademik (siswa_id, tanggal_tes, nilai_twk, nilai_tiu, nilai_tkp, kategori_tes)
     VALUES (?,?,?,?,?,?)",
    "isiiis",
    [$siswa_id, $tgl, $twk, $tiu, $tkp, $kategori]
);
```

---

### 14. penilaian_psikologi
Nilai tes psikologi.

```sql
CREATE TABLE penilaian_psikologi (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    siswa_id         INT UNSIGNED NOT NULL,
    tanggal_tes      DATE NOT NULL,
    kecerdasan       INT DEFAULT NULL,                  -- Skor 0-100
    kecermatan       INT DEFAULT NULL,
    kepribadian      INT DEFAULT NULL,
    rata_psikologi   DECIMAL(5,2) GENERATED ALWAYS AS (
                         (COALESCE(kecerdasan,0) + COALESCE(kecermatan,0) + COALESCE(kepribadian,0)) / 3
                     ) STORED,                         -- GENERATED — jangan di-INSERT
    status_psikologi VARCHAR(20) GENERATED ALWAYS AS (
                         IF((COALESCE(kecerdasan,0) + COALESCE(kecermatan,0) + COALESCE(kepribadian,0))/3 >= 70,
                            'Lulus', 'Tidak Lulus')
                     ) STORED,                         -- GENERATED — jangan di-INSERT
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at       DATETIME DEFAULT NULL,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id)
);
```

**INSERT yang benar:**
```php
db_insert(
    "INSERT INTO penilaian_psikologi (siswa_id, tanggal_tes, kecerdasan, kecermatan, kepribadian)
     VALUES (?,?,?,?,?)",
    "isiii",
    [$siswa_id, $tgl, $kecerdasan, $kecermatan, $kepribadian]
);
```

---

### 15. pembayaran_siswa
Tagihan dan pembayaran SPP. **★ v2.1 — redesigned untuk multi-program per pembayaran.**

```sql
CREATE TABLE pembayaran_siswa (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    siswa_id            INT UNSIGNED NOT NULL,
    membership_id       INT UNSIGNED DEFAULT NULL,         -- ★ v2.1: jadi nullable, detail di table baru
    jumlah_bulan        INT UNSIGNED NOT NULL DEFAULT 1,   -- ★ v2.1
    keterangan_program  VARCHAR(255) DEFAULT NULL,         -- ★ v2.1: ringkasan "Akademik + Jasmani (3 bln)"
    periode_bulan       DATE DEFAULT NULL,                 -- ★ v2.1: jadi nullable (legacy)
    nominal_tagihan     DECIMAL(12,2) NOT NULL DEFAULT 0,
    nominal_bayar       DECIMAL(12,2) NOT NULL DEFAULT 0,
    status_bayar        ENUM('Belum Bayar','Cicilan','Lunas') NOT NULL DEFAULT 'Belum Bayar',
    tanggal_bayar       DATE DEFAULT NULL,
    metode_pembayaran   VARCHAR(50) DEFAULT NULL,
    keterangan          TEXT DEFAULT NULL,
    created_by          INT UNSIGNED DEFAULT NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          DATETIME DEFAULT NULL,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id)
);
```

**★ v2.1 — Cara pembayaran disimpan:**
- 1 row di `pembayaran_siswa` = 1 transaksi pembayaran (header)
- N rows di `pembayaran_siswa_detail` = line item per program yang dibayar
- `keterangan_program` = ringkasan human-readable (auto-generated dari detail)

---

### 15b. pembayaran_siswa_detail ★ v2.1
Line items per program per pembayaran.

```sql
CREATE TABLE pembayaran_siswa_detail (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pembayaran_id   INT UNSIGNED NOT NULL,
    membership_id   INT UNSIGNED NULL,
    program_id      INT UNSIGNED NULL,
    nama_program    VARCHAR(100) NOT NULL,         -- snapshot
    biaya_per_bulan DECIMAL(12,2) NOT NULL DEFAULT 0,
    jumlah_bulan    INT UNSIGNED NOT NULL DEFAULT 1,
    subtotal        DECIMAL(15,2) GENERATED ALWAYS AS (biaya_per_bulan * jumlah_bulan) STORED,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pembayaran (pembayaran_id),
    INDEX idx_membership (membership_id)
);
```

**★ Catatan:**
- `subtotal` adalah **GENERATED ALWAYS** — jangan INSERT manual
- `nama_program` di-snapshot saat pembayaran (kalau program di-rename nanti, history tetap akurat)
- Saat insert detail: trigger update `tanggal_selesai_aktif` di `membership_siswa` (bukan via DB trigger, lewat code di `pembayaran/controller.php`)

**Query rekap pembayaran per siswa:**
```sql
SELECT ps.id, ps.tanggal_bayar, ps.nominal_bayar, ps.status_bayar,
       GROUP_CONCAT(psd.nama_program SEPARATOR ', ') AS programs,
       SUM(psd.subtotal) AS total
FROM pembayaran_siswa ps
LEFT JOIN pembayaran_siswa_detail psd ON psd.pembayaran_id = ps.id
WHERE ps.siswa_id = ? AND ps.deleted_at IS NULL
GROUP BY ps.id ORDER BY ps.tanggal_bayar DESC;
```

---

### 16. transaksi_keuangan
Buku kas pemasukan dan pengeluaran.

```sql
CREATE TABLE transaksi_keuangan (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jenis_arus              ENUM('Pemasukan','Pengeluaran') NOT NULL,
    kategori                VARCHAR(100) DEFAULT NULL,   -- Unified kategori
    keterangan_transaksi    TEXT DEFAULT NULL,
    nominal                 DECIMAL(15,2) NOT NULL DEFAULT 0,
    tanggal_transaksi       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by              INT UNSIGNED DEFAULT NULL,
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at              DATETIME DEFAULT NULL
);
```

**Kategori Pemasukan:** Bayar Program, Biaya Admin, Kelas Tambahan, Private, Lainnya  
**Kategori Pengeluaran:** Belanja Operasional, Belanja Modal, Belanja Pegawai, Gaji Tutor, Lainnya

**Query umum:**
```php
// Ringkasan saldo
$saldo = db_fetch(
    "SELECT
        SUM(CASE WHEN jenis_arus='Pemasukan' THEN nominal ELSE 0 END) AS total_masuk,
        SUM(CASE WHEN jenis_arus='Pengeluaran' THEN nominal ELSE 0 END) AS total_keluar,
        SUM(CASE WHEN jenis_arus='Pemasukan' THEN nominal ELSE -nominal END) AS saldo
     FROM transaksi_keuangan WHERE deleted_at IS NULL"
);

// Trend 6 bulan (untuk chart)
for ($i = 5; $i >= 0; $i--) {
    $bln = date('Y-m', strtotime("-{$i} months"));
    $masuk  = db_value("SELECT COALESCE(SUM(nominal),0) FROM transaksi_keuangan WHERE jenis_arus='Pemasukan' AND DATE_FORMAT(tanggal_transaksi,'%Y-%m')=?", "s", [$bln]);
    $keluar = db_value("SELECT COALESCE(SUM(nominal),0) FROM transaksi_keuangan WHERE jenis_arus='Pengeluaran' AND DATE_FORMAT(tanggal_transaksi,'%Y-%m')=?", "s", [$bln]);
}
```

---

### 17. calon_siswa
Antrian pendaftaran (form publik).

```sql
CREATE TABLE calon_siswa (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap     VARCHAR(100) NOT NULL,
    nomor_wa         VARCHAR(20) NOT NULL,
    tanggal_lahir    DATE DEFAULT NULL,
    jenis_kelamin    ENUM('L','P') DEFAULT NULL,
    asal_sekolah     VARCHAR(100) DEFAULT NULL,
    target_seleksi   VARCHAR(100) DEFAULT NULL,
    alamat           TEXT DEFAULT NULL,
    email            VARCHAR(100) DEFAULT NULL,
    status           ENUM('Baru','Dihubungi','Diterima','Ditolak') NOT NULL DEFAULT 'Baru',
    catatan_admin    TEXT DEFAULT NULL,
    diproses_oleh    INT UNSIGNED DEFAULT NULL,          -- users.id
    diproses_at      DATETIME DEFAULT NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

---

### 18. notifikasi
Notifikasi in-app per user.

```sql
CREATE TABLE notifikasi (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    judul       VARCHAR(200) NOT NULL,
    pesan       TEXT DEFAULT NULL,
    url_action  VARCHAR(300) DEFAULT NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

---

### 19. audit_log
Log semua aktivitas CRUD admin.

```sql
CREATE TABLE audit_log (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED DEFAULT NULL,
    action       VARCHAR(50) NOT NULL,                  -- CREATE_SISWA, UPDATE_NILAI, dll
    target_table VARCHAR(50) DEFAULT NULL,
    target_id    INT UNSIGNED DEFAULT NULL,
    old_values   JSON DEFAULT NULL,
    new_values   JSON DEFAULT NULL,
    ip_address   VARCHAR(45) DEFAULT NULL,
    user_agent   VARCHAR(500) DEFAULT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

**Konvensi nama action:**
```
CREATE_{ENTITY}   → CREATE_SISWA, CREATE_JADWAL
UPDATE_{ENTITY}   → UPDATE_NILAI, UPDATE_TUTOR
DELETE_{ENTITY}   → DELETE_PEMBAYARAN
BULK_DELETE_{...} → BULK_DELETE_SISWA
LOGIN_SUCCESS     → Login berhasil
LOGIN_FAILED      → Login gagal
```

---

## Relasi Antar Tabel

```
users ──────────────── siswa (siswa.user_id)
users ──────────────── calon_siswa (calon_siswa.diproses_oleh)
users ──────────────── audit_log (audit_log.user_id)
users ──────────────── notifikasi (notifikasi.user_id)

siswa ──────────────── membership_siswa (membership_siswa.siswa_id)
program ────────────── membership_siswa (membership_siswa.program_id)

jadwal ─────────────── jadwal_tutor (jadwal_tutor.jadwal_id)
tutor ──────────────── jadwal_tutor (jadwal_tutor.tutor_id)

siswa ──────────────── attendance_siswa (attendance_siswa.siswa_id)
jadwal ─────────────── attendance_siswa (attendance_siswa.jadwal_id)

tutor ──────────────── absensi_tutor (absensi_tutor.tutor_id)
jadwal ─────────────── absensi_tutor (absensi_tutor.jadwal_id)

siswa ──────────────── penilaian_binjas (penilaian_binjas.siswa_id)
siswa ──────────────── penilaian_mapel (penilaian_mapel.siswa_id)
siswa ──────────────── penilaian_akademik (penilaian_akademik.siswa_id)
siswa ──────────────── penilaian_psikologi (penilaian_psikologi.siswa_id)

siswa ──────────────── pembayaran_siswa (pembayaran_siswa.siswa_id)
membership_siswa ────── pembayaran_siswa (pembayaran_siswa.membership_id)
```

---

## Query Penting yang Sering Dipakai

### Siswa dengan membership aktif terbaru
```sql
SELECT s.*, p.nama_program, m.tanggal_mulai_aktif, m.tanggal_selesai_aktif,
       m.status_membership, p.biaya_bulanan
FROM siswa s
LEFT JOIN (
    SELECT siswa_id, MAX(id) AS mid FROM membership_siswa GROUP BY siswa_id
) mm ON s.id = mm.siswa_id
LEFT JOIN membership_siswa m ON mm.mid = m.id
LEFT JOIN program p ON m.program_id = p.id
WHERE s.deleted_at IS NULL
```

### Top skor SKD per siswa
```sql
SELECT s.nama_lengkap, s.nomor_induk,
       MAX(pa.total_skor) AS skor_tertinggi,
       COUNT(pa.id)       AS jumlah_tes,
       AVG(pa.total_skor) AS rata_rata
FROM penilaian_akademik pa
JOIN siswa s ON pa.siswa_id = s.id
WHERE s.deleted_at IS NULL AND pa.kategori_tes = 'SKD'
GROUP BY pa.siswa_id
ORDER BY skor_tertinggi DESC
LIMIT 10
```

### Rekap tutor dengan filter tanggal
```sql
SELECT t.nama_lengkap, t.spesialisasi, t.tarif_per_sesi,
       COUNT(DISTINCT j.id) AS jumlah_sesi,
       COUNT(DISTINCT j.id) * COALESCE(t.tarif_per_sesi, 0) AS total_honor,
       GROUP_CONCAT(DISTINCT j.nama_kegiatan ORDER BY j.nama_kegiatan SEPARATOR ', ') AS kegiatan
FROM tutor t
JOIN jadwal_tutor jt ON jt.tutor_id = t.id
JOIN jadwal j ON jt.jadwal_id = j.id
WHERE t.deleted_at IS NULL
  AND j.tanggal BETWEEN ? AND ?
GROUP BY t.id
ORDER BY total_honor DESC
```

### Arus kas 6 bulan (untuk chart)
```sql
SELECT
    DATE_FORMAT(tanggal_transaksi,'%Y-%m') AS bulan,
    SUM(CASE WHEN jenis_arus='Pemasukan' THEN nominal ELSE 0 END) AS masuk,
    SUM(CASE WHEN jenis_arus='Pengeluaran' THEN nominal ELSE 0 END) AS keluar
FROM transaksi_keuangan
WHERE tanggal_transaksi >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
  AND deleted_at IS NULL
GROUP BY DATE_FORMAT(tanggal_transaksi,'%Y-%m')
ORDER BY bulan ASC
```

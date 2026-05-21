# Database Migrations — Perkasa Mulia Training Center v2.0

> **⚠️ WAJIB BACA SEBELUM MENJALANKAN APA PUN**

---

## Prasyarat

- **BACKUP PENUH** database produksi sebelum menjalankan migration mana pun
  ```bash
  mysqldump -u USER -p DB_NAME > backup_$(date +%Y%m%d_%H%M%S).sql
  ```
- PHP 7.4+ (untuk `migrate_passwords.php`)
- Akses phpMyAdmin atau MySQL CLI
- MariaDB 10.4+ / MySQL 5.7+ (server saat ini: MariaDB 11.8 di Hostinger — ✅ aman)

---

## Urutan Eksekusi (WAJIB BERURUTAN)

| #   | File                                       | Tujuan                                       | DB Existing | DB Fresh |
|-----|--------------------------------------------|----------------------------------------------|-------------|----------|
| 001 | `001_initial_schema.sql`                   | DDL lengkap untuk fresh install              | ❌ SKIP     | ✅ JALANKAN |
| 002 | `002_cleanup_orphan.sql`                   | Bersihkan data invalid & konflik role        | ✅ WAJIB    | ❌ SKIP |
| 003 | `003_add_audit_columns.sql`                | Tambah kolom audit, security, field baru     | ✅ WAJIB    | ❌ SKIP |
| 004 | `004_add_indexes.sql`                      | Tambah index performa                        | ✅ WAJIB    | ❌ SKIP |
| 005 | `005_add_new_tables.sql`                   | Buat tabel baru (audit_log, dll)             | ✅ WAJIB    | ❌ SKIP |
| 006 | `006_add_users_role_owner.sql`             | Tambah role 'owner' ke enum users            | ✅ WAJIB    | ❌ SKIP |
| 007 | `007_unify_transaksi_kategori.sql`         | Migrasi kategori transaksi ke unified field  | ✅ WAJIB    | ❌ SKIP |
| 008 | `008_nilai_mapel_psikologi.sql`            | Restructure kolom mapel & psikologi          | ✅ WAJIB    | ❌ SKIP |
| 009 | `009_add_tutor_payment_fields.sql`         | Field gaji tutor (rate, periode)             | ✅ WAJIB    | ❌ SKIP |
| 010 | `010_add_2fa_and_cabang.sql`               | 2FA TOTP + tabel cabang                      | ✅ WAJIB    | ❌ SKIP |
| 011 | `011_add_binjas_skor_columns.sql` ★        | Skor per item binjas + Samapta A/B/AB        | ✅ WAJIB    | ✅ termasuk |
| 012 | `012_program_paket_spp_redesign.sql` ★ v2.1| `tipe_program`, `paket_komponen`, SPP redesign | ✅ WAJIB | ✅ termasuk |
| —   | `scripts/migrate_passwords.php`            | Hash semua password plaintext                | ✅ WAJIB    | ✅ WAJIB |

★ = migration v2.1 (Mei 2026)

---

## Cara Menjalankan (DB Existing — Production)

### Via phpMyAdmin (Hostinger)

1. Login ke phpMyAdmin di Hostinger hPanel
2. Pilih database `u741010203_adminperkasa`
3. Klik tab **SQL**
4. Copy-paste isi file migration **satu per satu** sesuai urutan
5. Klik **Go / Execute**
6. Pastikan tidak ada error merah sebelum lanjut ke file berikutnya

### Via MySQL CLI

```bash
# Ganti USER, PASS, DB_NAME sesuai credentials Anda
DB_USER="u741010203_admin"
DB_PASS="PASSWORD_ANDA"
DB_NAME="u741010203_adminperkasa"
DB_HOST="127.0.0.1"

# Jalankan berurutan:
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME < migrations/002_cleanup_orphan.sql
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME < migrations/003_add_audit_columns.sql
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME < migrations/004_add_indexes.sql
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME < migrations/005_add_new_tables.sql
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME < migrations/006_add_users_role_owner.sql
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME < migrations/007_unify_transaksi_kategori.sql
```

### Menjalankan migrate_passwords.php

```bash
# Via CLI (direkomendasikan):
php database/scripts/migrate_passwords.php

# Via browser (darurat saja — jangan biarkan terbuka lama!):
# https://yourdomain.com/migrate_passwords.php?secret=GANTI_SECRET_UNIK_INI
```

> **Setelah selesai: HAPUS `migrate_passwords.php` dari server!**

---

## Temuan Kritis di Data Existing

Hasil analisis schema lama (`u741010203_adminperkasa.sql`) — ditemukan sebelum migration:

| # | Masalah | Tabel | Detail | Penanganan |
|---|---------|-------|--------|------------|
| 1 | **KRITIS** Password plaintext | `users` | Semua 21 user memakai `password123` | `migrate_passwords.php` |
| 2 | **TINGGI** Data konflik role | `tutor` | tutor.user_id 1–16 menunjuk ke users ber-role `siswa` | `002_cleanup_orphan.sql` Section D |
| 3 | **SEDANG** Enum invalid | `membership_siswa` id=15 | `status_membership=''` (bukan enum valid) | `002_cleanup_orphan.sql` Section B |
| 4 | **SEDANG** Enum invalid | `transaksi_keuangan` id=15 | `kategori_pengeluaran=''` | `002_cleanup_orphan.sql` Section C |
| 5 | **INFO** Tidak ada `updated_at`, `deleted_at` | Semua tabel kecuali `users`/`siswa` | Tidak bisa soft-delete & audit | `003_add_audit_columns.sql` |
| 6 | **INFO** Tidak ada index pada kolom filter utama | `siswa`, `jadwal`, `transaksi` | Query lambat saat data besar | `004_add_indexes.sql` |

---

## Rollback Per File

Setiap file migration memiliki **komentar ROLLBACK** di dalamnya. Panduan umum:

### Rollback 002 (Cleanup)
Tidak ada rollback otomatis untuk data yang sudah diperbaiki. Restore dari backup.

### Rollback 003 (Kolom audit)
Jalankan `ALTER TABLE ... DROP COLUMN ...` untuk setiap kolom yang ditambahkan.
Lihat komentar `-- ROLLBACK:` di dalam file `003_add_audit_columns.sql`.

### Rollback 005 (Tabel baru)
```sql
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_log, notifikasi, pembayaran_siswa, attendance_siswa;
ALTER TABLE transaksi_keuangan DROP FOREIGN KEY fk_tk_pembayaran;
SET FOREIGN_KEY_CHECKS = 1;
```

### Rollback 007 (Unified kategori)
Kolom lama (`kategori_pemasukan`, `kategori_pengeluaran`) **sengaja tidak dihapus**
selama masa transisi. Cukup drop kolom `kategori` jika rollback dibutuhkan:
```sql
ALTER TABLE transaksi_keuangan DROP COLUMN kategori;
```

---

## Idempoten — Aman Dijalankan Ulang

Semua file migration (002–007) dirancang **idempoten**:
- Pakai `CREATE TABLE IF NOT EXISTS`
- Pakai stored procedure `add_column_if_not_exists` dan `add_index_if_not_exists`
- `UPDATE` hanya menarget baris dengan nilai kosong/invalid
- Aman dijalankan ulang jika eksekusi sebelumnya terganggu di tengah jalan

---

## Checklist Setelah Migration

- [ ] `002`: Tidak ada error, verifikasi SELECT di Section F menampilkan data bersih
- [ ] `003`: Semua kolom baru muncul di INFORMATION_SCHEMA.COLUMNS
- [ ] `004`: Index baru terlihat di INFORMATION_SCHEMA.STATISTICS
- [ ] `005`: Tabel `audit_log`, `notifikasi`, `pembayaran_siswa`, `attendance_siswa` terbuat
- [ ] `006`: `ENUM users.role` sudah include `'owner'`
- [ ] `007`: Semua baris `transaksi_keuangan` memiliki kolom `kategori` terisi
- [ ] `migrate_passwords.php`: Output menunjukkan "0 plaintext remaining"
- [ ] Login admin berhasil dengan password lama
- [ ] File `migrate_passwords.php` **sudah dihapus** dari server

---

## Skema Setelah Migration (v2.0)

```
users              ←── siswa (user_id)
                   ←── tutor (user_id)
                   ←── audit_log (user_id)
                   ←── notifikasi (user_id)

program            ←── jadwal (program_id)
                   ←── membership_siswa (program_id)

siswa              ←── membership_siswa (siswa_id)
                   ←── penilaian_akademik (siswa_id)
                   ←── penilaian_binjas (siswa_id)
                   ←── attendance_siswa (siswa_id)
                   ←── pembayaran_siswa (siswa_id)
                   ←── transaksi_keuangan (siswa_id)

jadwal             ←── jadwal_tutor (jadwal_id)
                   ←── absensi_tutor (jadwal_id)
                   ←── attendance_siswa (jadwal_id)

tutor              ←── jadwal_tutor (tutor_id)
                   ←── absensi_tutor (tutor_id)

membership_siswa   ←── pembayaran_siswa (membership_id)
                   ←── transaksi_keuangan (membership_id)
```

---

*Dokumen ini bagian dari PERKASA v2.0 Blueprint — Phase 1, Task 1: Database Migration*
*Dibuat: 2026-05-02*

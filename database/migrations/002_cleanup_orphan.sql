-- ============================================================
-- 002_cleanup_orphan.sql
-- PERKASA MULIA TRAINING CENTER v2.0
-- CLEANUP: Perbaiki data tidak konsisten SEBELUM migration
-- ============================================================
-- Dibuat  : 2026-05-02
-- Jalankan: SEBELUM 003_add_audit_columns.sql
-- ROLLBACK: Lihat bagian ROLLBACK di bawah setiap section
-- ⚠️  BACKUP DB DULU SEBELUM MENJALANKAN SCRIPT INI!
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ============================================================
-- SECTION A: Audit kondisi data (jalankan SELECT dulu, review)
-- ============================================================

-- A1. Cek password plaintext (semua harus diganti nanti di migrate_passwords.php)
-- SELECT id, email, role,
--        IF(password LIKE '$2y$%', 'HASHED', 'PLAINTEXT') AS pw_status
-- FROM users;

-- A2. Cek membership_siswa dengan status enum kosong/invalid
-- SELECT id, siswa_id, status_membership FROM membership_siswa
-- WHERE status_membership NOT IN ('Berjalan','Selesai','Cuti') OR status_membership IS NULL OR status_membership = '';

-- A3. Cek transaksi_keuangan dengan kategori kosong
-- SELECT id, jenis_arus, kategori_pengeluaran, kategori_pemasukan FROM transaksi_keuangan
-- WHERE (jenis_arus='Pengeluaran' AND (kategori_pengeluaran IS NULL OR kategori_pengeluaran = ''))
--    OR (jenis_arus='Pemasukan'   AND (kategori_pemasukan   IS NULL OR kategori_pemasukan   = ''));

-- A4. Cek tutor yang user_id-nya bukan role tutor (data konflik)
-- SELECT t.id AS tutor_id, t.nama_lengkap, u.email, u.role
-- FROM tutor t JOIN users u ON t.user_id = u.id
-- WHERE u.role != 'tutor';

-- A5. Cek siswa yang tidak punya user_id valid
-- SELECT s.id, s.nomor_induk, s.nama_lengkap, s.user_id
-- FROM siswa s LEFT JOIN users u ON s.user_id = u.id
-- WHERE u.id IS NULL;

-- ============================================================
-- SECTION B: Fix membership_siswa status kosong
-- ============================================================
-- Temuan: id=15 memiliki status_membership='' (empty string, bukan enum valid)
-- Fix: default ke 'Selesai' jika tanggal_selesai_aktif sudah lewat, else 'Berjalan'

UPDATE `membership_siswa`
SET `status_membership` = CASE
    WHEN `tanggal_selesai_aktif` < CURDATE() THEN 'Selesai'
    ELSE 'Berjalan'
END
WHERE `status_membership` = ''
   OR `status_membership` IS NULL
   OR `status_membership` NOT IN ('Berjalan','Selesai','Cuti');

-- Log perubahan
SELECT CONCAT(
    '[CLEANUP] membership_siswa: Fixed ',
    ROW_COUNT(),
    ' rows dengan status invalid → auto-set berdasarkan tanggal'
) AS cleanup_log;

-- ROLLBACK (jika perlu):
-- UPDATE membership_siswa SET status_membership = '' WHERE id = 15;

-- ============================================================
-- SECTION C: Fix transaksi_keuangan kategori kosong
-- ============================================================
-- Temuan: id=15 kategori_pengeluaran='' (empty string, invalid enum)
-- Fix: set ke 'Belanja Operasional' (paling umum untuk catch-all)

UPDATE `transaksi_keuangan`
SET `kategori_pengeluaran` = 'Belanja Operasional'
WHERE `jenis_arus` = 'Pengeluaran'
  AND (`kategori_pengeluaran` = '' OR `kategori_pengeluaran` IS NULL);

UPDATE `transaksi_keuangan`
SET `kategori_pemasukan` = 'Lainnya'
WHERE `jenis_arus` = 'Pemasukan'
  AND (`kategori_pemasukan` = '' OR `kategori_pemasukan` IS NULL);

SELECT CONCAT(
    '[CLEANUP] transaksi_keuangan: Fixed invalid kategori'
) AS cleanup_log;

-- ROLLBACK:
-- UPDATE transaksi_keuangan SET kategori_pengeluaran = '' WHERE id = 15;

-- ============================================================
-- SECTION D: Fix user role conflict (tutor records → users dengan role siswa)
-- ============================================================
-- Temuan: tutor.user_id 2-16 merujuk ke users yang role='siswa'
-- Ini terjadi karena data seed/dummy memakai user yang sama
--
-- STRATEGI AMAN:
-- Jangan hapus data, tapi tambahkan users baru dengan role='tutor'
-- untuk setiap tutor yang user_id-nya role bukan 'tutor'.
-- Kemudian update tutor.user_id ke user baru.
-- Tutor tanpa akun valid akan dibuatkan placeholder user.

-- Step D1: Identifikasi tutor yang perlu user baru
-- (user_id-nya role='siswa', bukan 'tutor')
DROP TEMPORARY TABLE IF EXISTS `tmp_tutor_fix`;
CREATE TEMPORARY TABLE `tmp_tutor_fix` AS
    SELECT t.id AS tutor_id,
           t.nama_lengkap,
           t.nomor_wa,
           u.email AS old_email,
           u.role  AS old_role
    FROM `tutor` t
    JOIN `users` u ON t.user_id = u.id
    WHERE u.role != 'tutor';

-- Step D2: Buat user placeholder untuk setiap tutor yang konflik
-- Format email: tutor_<id>@internal.bimbelperkasa.id
-- Password: placeholder (akan di-reset via migrate_passwords.php)
-- CATATAN: Nama display diisi dari nama_lengkap tutor

INSERT IGNORE INTO `users` (`email`, `password`, `role`, `nama_display`, `is_active`)
SELECT
    CONCAT('tutor_', t.id, '@internal.bimbelperkasa.id') AS email,
    'NEEDS_RESET' AS password,  -- Akan diganti oleh migrate_passwords.php
    'tutor' AS role,
    t.nama_lengkap AS nama_display,
    1 AS is_active
FROM `tutor` t
JOIN `users` u ON t.user_id = u.id
WHERE u.role != 'tutor';

SELECT CONCAT('[CLEANUP] users: Created ', ROW_COUNT(), ' placeholder user untuk tutor') AS cleanup_log;

-- Step D3: Update tutor.user_id ke user placeholder yang baru dibuat
UPDATE `tutor` t
JOIN `users` u_old ON t.user_id = u_old.id AND u_old.role != 'tutor'
JOIN `users` u_new ON u_new.email = CONCAT('tutor_', t.id, '@internal.bimbelperkasa.id')
SET t.user_id = u_new.id;

SELECT CONCAT('[CLEANUP] tutor: Updated ', ROW_COUNT(), ' tutor.user_id ke placeholder user') AS cleanup_log;

-- Step D4: Pastikan users admin punya nama_display (untuk migrasi kolom baru nanti)
-- Admin user (id=1) belum punya nama_display — tambahkan sementara
-- (akan diisi proper saat 003_add_audit_columns.sql dijalankan)

-- ============================================================
-- SECTION E: Normalisasi nomor WA (buang spasi dan strip +62)
-- ============================================================
-- Nomor WA tutor banyak yang '8888888888888' (placeholder)
-- Siswa sudah format 08xxxxxxxx — biarkan dulu, normalisasi di Phase 2

-- Hapus spasi dalam nomor WA siswa
UPDATE `siswa`
SET `nomor_wa` = REPLACE(REPLACE(REPLACE(`nomor_wa`, ' ', ''), '-', ''), '+', '')
WHERE `nomor_wa` REGEXP '[[:space:]]|[-+]';

UPDATE `tutor`
SET `nomor_wa` = REPLACE(REPLACE(REPLACE(`nomor_wa`, ' ', ''), '-', ''), '+', '')
WHERE `nomor_wa` IS NOT NULL
  AND `nomor_wa` REGEXP '[[:space:]]|[-+]';

SELECT '[CLEANUP] Normalisasi nomor WA selesai' AS cleanup_log;

-- ============================================================
-- SECTION F: Verifikasi akhir (jalankan untuk konfirmasi)
-- ============================================================

-- F1. Summary status setelah cleanup
SELECT
    'users'            AS tabel,
    COUNT(*)           AS total,
    SUM(IF(role='admin',1,0)) AS admin,
    SUM(IF(role='tutor',1,0)) AS tutor,
    SUM(IF(role='siswa',1,0)) AS siswa
FROM users
UNION ALL
SELECT 'siswa', COUNT(*), NULL, NULL, NULL FROM siswa
UNION ALL
SELECT 'tutor', COUNT(*), NULL, NULL, NULL FROM tutor
UNION ALL
SELECT 'membership_siswa', COUNT(*), NULL, NULL, NULL FROM membership_siswa
UNION ALL
SELECT 'transaksi_keuangan', COUNT(*), NULL, NULL, NULL FROM transaksi_keuangan;

-- F2. Pastikan tidak ada lagi data konflik
SELECT 'Cek tutor tanpa user valid:' AS cek,
       COUNT(*) AS jumlah
FROM tutor t
LEFT JOIN users u ON t.user_id = u.id AND u.role = 'tutor'
WHERE u.id IS NULL;

SELECT 'Cek membership status invalid:' AS cek,
       COUNT(*) AS jumlah
FROM membership_siswa
WHERE status_membership NOT IN ('Berjalan','Selesai','Cuti')
   OR status_membership IS NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF 002_cleanup_orphan.sql
-- Next: Jalankan 003_add_audit_columns.sql
-- ============================================================

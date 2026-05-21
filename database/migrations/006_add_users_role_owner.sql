-- ============================================================
-- 006_add_users_role_owner.sql
-- PERKASA MULIA TRAINING CENTER v2.0
-- Tambah role 'owner' ke ENUM users.role
-- ============================================================
-- Dibuat  : 2026-05-02
-- Prasyarat: 005_add_new_tables.sql sudah dijalankan
-- Idempoten: YA — cek existing enum sebelum MODIFY
-- ROLLBACK : ALTER TABLE users MODIFY COLUMN role ENUM('admin','tutor','siswa')
--            (hanya aman jika tidak ada user dengan role='owner')
-- ============================================================

SET NAMES utf8mb4;

-- Cek apakah 'owner' sudah ada di ENUM
SET @has_owner = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'users'
      AND COLUMN_NAME  = 'role'
      AND COLUMN_TYPE LIKE '%owner%'
);

-- Tambah 'owner' ke ENUM jika belum ada
SET @sql_role = IF(@has_owner = 0,
    'ALTER TABLE `users` MODIFY COLUMN `role`
     ENUM(\'admin\',\'tutor\',\'siswa\',\'owner\') NOT NULL DEFAULT \'siswa\'',
    'SELECT "[006] users.role enum sudah include owner — skipped" AS migration_log'
);
PREPARE stmt FROM @sql_role;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Catatan: Jangan INSERT user owner di sini.
-- Buat user owner secara manual via migrate_passwords.php atau phpMyAdmin
-- setelah semua migration selesai.

SELECT '[006] Migration selesai: Role owner tersedia.' AS final_status;

-- ============================================================
-- END OF 006_add_users_role_owner.sql
-- Next: Jalankan 007_unify_transaksi_kategori.sql
-- ============================================================

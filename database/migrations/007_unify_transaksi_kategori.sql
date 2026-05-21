-- ============================================================
-- 007_unify_transaksi_kategori.sql
-- PERKASA MULIA TRAINING CENTER v2.0
-- Unifikasi kategori_pemasukan + kategori_pengeluaran → `kategori` (satu kolom)
-- ============================================================
-- Dibuat  : 2026-05-02
-- Prasyarat: 003_add_audit_columns.sql sudah dijalankan
-- Idempoten: YA — cek kolom sebelum ADD/DROP
-- ROLLBACK : Kolom lama (kategori_pemasukan, kategori_pengeluaran) DIPERTAHANKAN
--            selama masa transisi. DROP manual setelah v2.0 stabil 30 hari.
-- ============================================================

SET NAMES utf8mb4;

-- Step 1: Tambah kolom unified `kategori` jika belum ada
DROP PROCEDURE IF EXISTS `add_column_if_not_exists`;
DELIMITER $$
CREATE PROCEDURE `add_column_if_not_exists`(
    IN p_table  VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_ddl    TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME  = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_ddl);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        SELECT CONCAT('[007] Added column: ', p_table, '.', p_column) AS migration_log;
    ELSE
        SELECT CONCAT('[007] Skipped (exists): ', p_table, '.', p_column) AS migration_log;
    END IF;
END$$
DELIMITER ;

CALL add_column_if_not_exists('transaksi_keuangan', 'kategori',
    '`kategori` VARCHAR(50) NOT NULL DEFAULT \'\' AFTER `jenis_arus`');

DROP PROCEDURE IF EXISTS `add_column_if_not_exists`;

-- Step 2: Migrasi data dari kolom lama ke `kategori`
-- Hanya update baris yang kolom `kategori` masih kosong
-- (idempoten: baris yang sudah terisi tidak akan diubah)

UPDATE `transaksi_keuangan`
SET `kategori` = CASE
    -- Pemasukan
    WHEN `jenis_arus` = 'Pemasukan' AND `kategori_pemasukan` IS NOT NULL AND `kategori_pemasukan` != ''
        THEN `kategori_pemasukan`
    WHEN `jenis_arus` = 'Pemasukan'
        THEN 'Lainnya'
    -- Pengeluaran
    WHEN `jenis_arus` = 'Pengeluaran' AND `kategori_pengeluaran` IS NOT NULL AND `kategori_pengeluaran` != ''
        THEN `kategori_pengeluaran`
    WHEN `jenis_arus` = 'Pengeluaran'
        THEN 'Belanja Operasional'
    ELSE 'Lainnya'
END
WHERE `kategori` = '' OR `kategori` IS NULL;

SELECT CONCAT('[007] Migrated ', ROW_COUNT(), ' rows transaksi_keuangan ke kolom kategori unified.') AS migration_log;

-- Step 3: Tambah index untuk kolom kategori baru
DROP PROCEDURE IF EXISTS `add_index_if_not_exists`;
DELIMITER $$
CREATE PROCEDURE `add_index_if_not_exists`(
    IN p_table  VARCHAR(64),
    IN p_index  VARCHAR(64),
    IN p_ddl    TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME  = p_table
          AND INDEX_NAME  = p_index
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` ', p_ddl);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        SELECT CONCAT('[007] Added index: ', p_table, '.', p_index) AS migration_log;
    ELSE
        SELECT CONCAT('[007] Skipped index (exists): ', p_table, '.', p_index) AS migration_log;
    END IF;
END$$
DELIMITER ;

CALL add_index_if_not_exists('transaksi_keuangan', 'idx_kategori', '(`kategori`)');

DROP PROCEDURE IF EXISTS `add_index_if_not_exists`;

-- ============================================================
-- Step 4: Verifikasi hasil migrasi
-- ============================================================
SELECT
    jenis_arus,
    kategori_pemasukan   AS kategori_lama_masuk,
    kategori_pengeluaran AS kategori_lama_keluar,
    kategori             AS kategori_baru,
    COUNT(*)             AS jumlah
FROM transaksi_keuangan
GROUP BY jenis_arus, kategori_pemasukan, kategori_pengeluaran, kategori
ORDER BY jenis_arus, kategori;

-- ============================================================
-- CATATAN PENTING — JANGAN DROP KOLOM LAMA SEKARANG
-- ============================================================
-- Kolom kategori_pemasukan & kategori_pengeluaran DIPERTAHANKAN
-- sampai kode PHP lama tidak merujuk ke kolom tersebut.
-- Setelah Phase 2 selesai dan semua query PHP sudah pakai `kategori`,
-- jalankan script berikut SECARA MANUAL:
--
-- ALTER TABLE transaksi_keuangan
--     DROP COLUMN kategori_pemasukan,
--     DROP COLUMN kategori_pengeluaran;
--
-- ============================================================

SELECT '[007] Migration selesai: Kolom kategori unified berhasil diisi dari data lama.' AS final_status;

-- ============================================================
-- END OF 007_unify_transaksi_kategori.sql
-- ============================================================

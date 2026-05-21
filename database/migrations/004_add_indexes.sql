-- ============================================================
-- 004_add_indexes.sql
-- PERKASA MULIA TRAINING CENTER v2.0
-- Tambah index performa — idempoten via DROP IF EXISTS + CREATE
-- ============================================================
-- Dibuat  : 2026-05-02
-- Prasyarat: 003_add_audit_columns.sql sudah dijalankan
-- ROLLBACK : DROP INDEX [nama_index] ON [tabel];
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Helper procedure: ADD INDEX IF NOT EXISTS
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
        SELECT CONCAT('[004] Added index: ', p_table, '.', p_index) AS migration_log;
    ELSE
        SELECT CONCAT('[004] Skipped (exists): ', p_table, '.', p_index) AS migration_log;
    END IF;
END$$
DELIMITER ;

-- ============================================================
-- USERS
-- ============================================================
CALL add_index_if_not_exists('users', 'idx_role_active',      '(`role`, `is_active`)');
CALL add_index_if_not_exists('users', 'idx_deleted_at',       '(`deleted_at`)');

-- ============================================================
-- SISWA
-- ============================================================
CALL add_index_if_not_exists('siswa', 'idx_status_deleted',   '(`status_siswa`, `deleted_at`)');
CALL add_index_if_not_exists('siswa', 'idx_target_seleksi',   '(`target_seleksi`)');
CALL add_index_if_not_exists('siswa', 'idx_created_at',       '(`created_at`)');

-- ============================================================
-- JADWAL
-- ============================================================
CALL add_index_if_not_exists('jadwal', 'idx_tanggal',         '(`tanggal`)');
CALL add_index_if_not_exists('jadwal', 'idx_program_tanggal', '(`program_id`, `tanggal`)');

-- ============================================================
-- TRANSAKSI KEUANGAN
-- ============================================================
CALL add_index_if_not_exists('transaksi_keuangan', 'idx_jenis_tanggal', '(`jenis_arus`, `tanggal_transaksi`)');
CALL add_index_if_not_exists('transaksi_keuangan', 'idx_tanggal',       '(`tanggal_transaksi`)');

-- ============================================================
-- PENILAIAN AKADEMIK
-- ============================================================
CALL add_index_if_not_exists('penilaian_akademik', 'idx_siswa_tanggal', '(`siswa_id`, `tanggal_tes`)');
CALL add_index_if_not_exists('penilaian_akademik', 'idx_kategori',      '(`kategori_tes`)');

-- ============================================================
-- PENILAIAN BINJAS
-- ============================================================
CALL add_index_if_not_exists('penilaian_binjas', 'idx_siswa_tanggal',   '(`siswa_id`, `tanggal_tes`)');

-- ============================================================
-- MEMBERSHIP SISWA
-- ============================================================
CALL add_index_if_not_exists('membership_siswa', 'idx_status',          '(`status_membership`)');
CALL add_index_if_not_exists('membership_siswa', 'idx_siswa_status',    '(`siswa_id`, `status_membership`)');

-- ============================================================
-- Cleanup
-- ============================================================
DROP PROCEDURE IF EXISTS `add_index_if_not_exists`;

SET FOREIGN_KEY_CHECKS = 1;

SELECT '[004] Migration selesai: Index performa berhasil ditambahkan.' AS final_status;

-- ============================================================
-- END OF 004_add_indexes.sql
-- Next: Jalankan 005_add_new_tables.sql
-- ============================================================

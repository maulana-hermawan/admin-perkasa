-- ============================================================
-- 005_add_new_tables.sql
-- PERKASA MULIA TRAINING CENTER v2.0
-- Buat tabel BARU: audit_log, notifikasi, pembayaran_siswa, attendance_siswa
-- ============================================================
-- Dibuat  : 2026-05-02
-- Prasyarat: 003_add_audit_columns.sql + 004_add_indexes.sql sudah dijalankan
-- Idempoten: YA — semua pakai CREATE TABLE IF NOT EXISTS
-- ROLLBACK : DROP TABLE [nama_tabel];  (aman, tabel baru — tidak ada data lama)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ============================================================
-- AUDIT LOG — rekam semua CRUD + event security
-- ============================================================
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id`            BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `user_id`       INT(11)             NULL    COMMENT 'NULL = system/guest',
    `action`        VARCHAR(100)        NOT NULL COMMENT 'CREATE_SISWA, LOGIN_FAILED, dll',
    `target_table`  VARCHAR(50)         NOT NULL,
    `target_id`     INT UNSIGNED        NULL,
    `old_values`    JSON                NULL,
    `new_values`    JSON                NULL,
    `ip_address`    VARCHAR(45)         NULL,
    `user_agent`    VARCHAR(255)        NULL,
    `created_at`    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_date`  (`user_id`, `created_at`),
    INDEX `idx_target`     (`target_table`, `target_id`),
    INDEX `idx_action`     (`action`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit trail semua aksi penting';

-- ============================================================
-- NOTIFIKASI — queue in-app & WhatsApp via Fonnte
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifikasi` (
    `id`            INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    `user_id`       INT(11)             NULL    COMMENT 'NULL = broadcast semua user aktif',
    `channel`       ENUM('in_app','whatsapp','email') NOT NULL DEFAULT 'in_app',
    `judul`         VARCHAR(200)        NOT NULL,
    `pesan`         TEXT                NOT NULL,
    `link_url`      VARCHAR(255)        NULL,
    `is_read`       TINYINT(1)          NOT NULL DEFAULT 0,
    `sent_at`       DATETIME            NULL,
    `sent_status`   ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    `retry_count`   TINYINT UNSIGNED    NOT NULL DEFAULT 0,
    `created_at`    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_unread`     (`user_id`, `is_read`),
    INDEX `idx_channel_status`  (`channel`, `sent_status`),
    INDEX `idx_created_at`      (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Queue notifikasi in-app dan WhatsApp';

-- ============================================================
-- PEMBAYARAN SISWA — rekap SPP per bulan per siswa
-- ============================================================
CREATE TABLE IF NOT EXISTS `pembayaran_siswa` (
    `id`                INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    `siswa_id`          INT(11)             NOT NULL,
    `membership_id`     INT(11)             NOT NULL,
    `periode_bulan`     DATE                NOT NULL COMMENT 'Tanggal awal bulan: 2026-04-01',
    `nominal_tagihan`   DECIMAL(15,2)       NOT NULL DEFAULT 0.00,
    `nominal_bayar`     DECIMAL(15,2)       NOT NULL DEFAULT 0.00,
    `status_bayar`      ENUM('Lunas','Cicilan','Belum Bayar') NOT NULL DEFAULT 'Belum Bayar',
    `tanggal_bayar`     DATETIME            NULL,
    `metode_pembayaran` ENUM('Tunai','Transfer','QRIS','Lainnya') NULL,
    `bukti_path`        VARCHAR(255)        NULL,
    `keterangan`        VARCHAR(255)        NULL,
    `created_by`        INT(11)             NULL,
    `created_at`        DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_siswa_membership_periode` (`siswa_id`, `membership_id`, `periode_bulan`),
    INDEX `idx_siswa`       (`siswa_id`),
    INDEX `idx_status`      (`status_bayar`),
    INDEX `idx_periode`     (`periode_bulan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Rekap pembayaran SPP bulanan siswa';

-- ============================================================
-- ATTENDANCE SISWA — kehadiran siswa per sesi jadwal
-- ============================================================
CREATE TABLE IF NOT EXISTS `attendance_siswa` (
    `id`                INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    `siswa_id`          INT(11)             NOT NULL,
    `jadwal_id`         INT(11)             NOT NULL,
    `status_hadir`      ENUM('Hadir','Izin','Sakit','Alpa') NOT NULL DEFAULT 'Alpa',
    `keterangan`        VARCHAR(255)        NULL,
    `dicatat_oleh`      INT(11)             NULL    COMMENT 'tutor_id atau user_id admin',
    `created_at`        DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_siswa_jadwal`    (`siswa_id`, `jadwal_id`),
    INDEX `idx_siswa`               (`siswa_id`),
    INDEX `idx_jadwal`              (`jadwal_id`),
    INDEX `idx_status`              (`status_hadir`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kehadiran siswa per sesi jadwal, dicatat oleh tutor';

-- ============================================================
-- Foreign Keys untuk tabel baru
-- (Menggunakan INT(11) di FK karena tabel parent masih INT(11))
-- ============================================================

-- audit_log
ALTER TABLE `audit_log`
    ADD CONSTRAINT `fk_al_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- notifikasi
ALTER TABLE `notifikasi`
    ADD CONSTRAINT `fk_notif_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- pembayaran_siswa
ALTER TABLE `pembayaran_siswa`
    ADD CONSTRAINT `fk_ps_siswa`
        FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_ps_membership`
        FOREIGN KEY (`membership_id`) REFERENCES `membership_siswa` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_ps_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- attendance_siswa
ALTER TABLE `attendance_siswa`
    ADD CONSTRAINT `fk_as_siswa`
        FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_as_jadwal`
        FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE;

-- ============================================================
-- Link transaksi_keuangan ke pembayaran_siswa
-- (FK pembayaran_id harus ditambah setelah tabel pembayaran_siswa ada)
-- ============================================================
DROP PROCEDURE IF EXISTS `add_fk_if_not_exists`;
DELIMITER $$
CREATE PROCEDURE `add_fk_if_not_exists`(
    IN p_table      VARCHAR(64),
    IN p_fk_name    VARCHAR(64),
    IN p_ddl        TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA   = DATABASE()
          AND TABLE_NAME     = p_table
          AND CONSTRAINT_NAME = p_fk_name
          AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD CONSTRAINT `', p_fk_name, '` ', p_ddl);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
        SELECT CONCAT('[005] Added FK: ', p_table, '.', p_fk_name) AS migration_log;
    ELSE
        SELECT CONCAT('[005] Skipped FK (exists): ', p_table, '.', p_fk_name) AS migration_log;
    END IF;
END$$
DELIMITER ;

CALL add_fk_if_not_exists('transaksi_keuangan', 'fk_tk_pembayaran',
    'FOREIGN KEY (`pembayaran_id`) REFERENCES `pembayaran_siswa` (`id`) ON DELETE SET NULL');

DROP PROCEDURE IF EXISTS `add_fk_if_not_exists`;

SET FOREIGN_KEY_CHECKS = 1;

SELECT '[005] Migration selesai: Tabel baru audit_log, notifikasi, pembayaran_siswa, attendance_siswa berhasil dibuat.' AS final_status;

-- ============================================================
-- ROLLBACK (jika perlu rollback seluruh file ini):
-- SET FOREIGN_KEY_CHECKS = 0;
-- DROP TABLE IF EXISTS audit_log, notifikasi, pembayaran_siswa, attendance_siswa;
-- ALTER TABLE transaksi_keuangan DROP FOREIGN KEY fk_tk_pembayaran;
-- SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================
-- END OF 005_add_new_tables.sql
-- Next: Jalankan 006_add_users_role_owner.sql
-- ============================================================

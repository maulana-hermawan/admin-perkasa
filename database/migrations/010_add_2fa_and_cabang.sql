-- 010_add_2fa_and_cabang.sql
-- Phase 4: 2FA TOTP + multi-cabang scaffold
-- Idempoten: ADD COLUMN IF NOT EXISTS
-- ============================================================

-- 2FA untuk tabel users
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `totp_secret`  VARCHAR(64)     NULL     COMMENT 'Base32 TOTP secret (null = 2FA belum aktif)',
    ADD COLUMN IF NOT EXISTS `totp_enabled` TINYINT(1)  NOT NULL DEFAULT 0 COMMENT '1 = 2FA aktif';

-- Tabel cabang (untuk multi-cabang Phase 4)
CREATE TABLE IF NOT EXISTS `cabang` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `kode`          VARCHAR(10)     NOT NULL UNIQUE,
    `nama`          VARCHAR(100)    NOT NULL,
    `alamat`        TEXT            NULL,
    `nomor_wa`      VARCHAR(20)     NULL,
    `is_aktif`      TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed cabang default (Bogor)
INSERT IGNORE INTO `cabang` (id, kode, nama, alamat, is_aktif)
VALUES (1, 'BGR', 'Perkasa Mulia Training Center — Bogor', 'Bogor, Jawa Barat', 1);

-- Tambah cabang_id ke tabel utama (DEFAULT 1 = cabang Bogor)
ALTER TABLE `siswa`
    ADD COLUMN IF NOT EXISTS `cabang_id` INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE `tutor`
    ADD COLUMN IF NOT EXISTS `cabang_id` INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE `jadwal`
    ADD COLUMN IF NOT EXISTS `cabang_id` INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE `transaksi_keuangan`
    ADD COLUMN IF NOT EXISTS `cabang_id` INT UNSIGNED NOT NULL DEFAULT 1;

-- Tabel calon_siswa untuk pendaftaran online
CREATE TABLE IF NOT EXISTS `calon_siswa` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nama_lengkap`      VARCHAR(150)    NOT NULL,
    `nomor_wa`          VARCHAR(20)     NOT NULL,
    `email`             VARCHAR(150)    NULL,
    `tanggal_lahir`     DATE            NULL,
    `asal_sekolah`      VARCHAR(150)    NULL,
    `target_seleksi`    VARCHAR(50)     NULL,
    `jenis_kelamin`     ENUM('L','P')   NULL,
    `alamat`            TEXT            NULL,
    `catatan_pendaftar` TEXT            NULL,
    `status`            ENUM('Baru','Diproses','Diterima','Ditolak') NOT NULL DEFAULT 'Baru',
    `catatan_admin`     TEXT            NULL,
    `diproses_oleh`     INT UNSIGNED    NULL,
    `diproses_at`       DATETIME        NULL,
    `cabang_id`         INT UNSIGNED    NOT NULL DEFAULT 1,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_cabang` (`cabang_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Antrian pendaftar dari form publik, pending verifikasi admin';

SELECT 'Migration 010 applied: 2FA columns, cabang table, calon_siswa table.' AS status;

-- ============================================================
-- 008_nilai_mapel_psikologi.sql
-- PERKASA MULIA TRAINING CENTER v2.0
-- Tambah tabel penilaian_mapel + penilaian_psikologi
-- Seed 4 paket program resmi
-- ============================================================
-- Prasyarat: 001–007 sudah dijalankan
-- Idempoten: CREATE TABLE IF NOT EXISTS
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ============================================================
-- 1. TABEL: penilaian_mapel
--    Nilai mata pelajaran akademik per siswa per sesi
-- ============================================================
CREATE TABLE IF NOT EXISTS `penilaian_mapel` (
    `id`              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `siswa_id`        INT UNSIGNED     NOT NULL,
    `tanggal_tes`     DATE             NOT NULL,
    -- 6 Mata Pelajaran
    `b_indonesia`     DECIMAL(5,2)     NOT NULL DEFAULT 0 COMMENT 'Bahasa Indonesia, min passing 61',
    `b_inggris`       DECIMAL(5,2)     NOT NULL DEFAULT 0 COMMENT 'Bahasa Inggris, min passing 61',
    `matematika`      DECIMAL(5,2)     NOT NULL DEFAULT 0 COMMENT 'Matematika, min passing 61',
    `pu`              DECIMAL(5,2)     NOT NULL DEFAULT 0 COMMENT 'Pengetahuan Umum, min passing 61',
    `wk`              DECIMAL(5,2)     NOT NULL DEFAULT 0 COMMENT 'Wawasan Kebangsaan, min passing 61',
    `komputer`        DECIMAL(5,2)     NOT NULL DEFAULT 0 COMMENT 'Komputer/TIK, min passing 61',
    -- Rekap otomatis
    `rata_mapel`      DECIMAL(5,2)     GENERATED ALWAYS AS (
                          ROUND((b_indonesia + b_inggris + matematika + pu + wk + komputer) / 6, 2)
                      ) STORED COMMENT 'Rata-rata 6 mapel (auto)',
    `status_mapel`    ENUM('Lulus','Tidak Lulus','Belum Dinilai')
                      GENERATED ALWAYS AS (
                          CASE
                              WHEN b_indonesia >= 61 AND b_inggris >= 61 AND matematika >= 61
                               AND pu >= 61 AND wk >= 61 AND komputer >= 61 THEN 'Lulus'
                              ELSE 'Tidak Lulus'
                          END
                      ) STORED COMMENT 'Semua mapel ≥ 61 = Lulus',
    `catatan`         TEXT             NULL,
    `sumber`          ENUM('Manual','CAT') NOT NULL DEFAULT 'Manual',
    `created_by`      INT UNSIGNED     NULL,
    `created_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_siswa_tanggal` (`siswa_id`, `tanggal_tes`),
    INDEX `idx_tanggal`       (`tanggal_tes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Nilai mata pelajaran akademik per sesi';

-- ============================================================
-- 2. TABEL: penilaian_psikologi
--    3 sub-tes psikologi per siswa per sesi
-- ============================================================
CREATE TABLE IF NOT EXISTS `penilaian_psikologi` (
    `id`              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `siswa_id`        INT UNSIGNED     NOT NULL,
    `tanggal_tes`     DATE             NOT NULL,
    -- 3 sub-tes psikologi
    `kecerdasan`      DECIMAL(5,2)     NOT NULL DEFAULT 0 COMMENT 'Tes kecerdasan (IQ test), min 61',
    `kecermatan`      DECIMAL(5,2)     NOT NULL DEFAULT 0 COMMENT 'Tes kecermatan/ketelitian, min 61',
    `kepribadian`     DECIMAL(5,2)     NOT NULL DEFAULT 0 COMMENT 'Tes kepribadian, min 61',
    -- Rekap otomatis
    `rata_psikologi`  DECIMAL(5,2)     GENERATED ALWAYS AS (
                          ROUND((kecerdasan + kecermatan + kepribadian) / 3, 2)
                      ) STORED COMMENT 'Rata-rata 3 sub-tes (auto)',
    `status_psikologi` ENUM('Lulus','Tidak Lulus','Belum Dinilai')
                      GENERATED ALWAYS AS (
                          CASE
                              WHEN kecerdasan >= 61 AND kecermatan >= 61 AND kepribadian >= 61
                              THEN 'Lulus'
                              ELSE 'Tidak Lulus'
                          END
                      ) STORED COMMENT 'Semua sub-tes ≥ 61 = Lulus',
    `catatan`         TEXT             NULL,
    `sumber`          ENUM('Manual','CAT') NOT NULL DEFAULT 'Manual',
    `sumber_referensi` VARCHAR(100)    NULL COMMENT 'session_id dari psikotes CAT',
    `created_by`      INT UNSIGNED     NULL,
    `created_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_siswa_tanggal` (`siswa_id`, `tanggal_tes`),
    INDEX `idx_sumber_ref`    (`sumber_referensi`),
    INDEX `idx_tanggal`       (`tanggal_tes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Nilai psikologi per sesi (kecerdasan, kecermatan, kepribadian)';

-- ============================================================
-- 3. FOREIGN KEYS
-- ============================================================
ALTER TABLE `penilaian_mapel`
    ADD CONSTRAINT `fk_pm_siswa`      FOREIGN KEY (`siswa_id`)   REFERENCES `siswa` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_pm_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `penilaian_psikologi`
    ADD CONSTRAINT `fk_pps_siswa`      FOREIGN KEY (`siswa_id`)   REFERENCES `siswa` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_pps_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- ============================================================
-- 4. SEED: 4 Paket Program resmi PMTC v2
-- ============================================================
-- Hapus paket lama jika masih ada data seed/dummy
DELETE FROM `program` WHERE `nama_program` IN (
    'Akademik Intensif', 'Akademik Reguler',
    'Jasmani Intensif', 'Jasmani Reguler'
);

INSERT INTO `program`
    (`nama_program`, `kategori_program`, `deskripsi`, `durasi_bulan`, `biaya_bulanan`, `is_active`)
VALUES
    ('Akademik Intensif', 'Akademik',
     'Program akademik intensif: SKD (TWK/TIU/TKP), Mata Pelajaran (B.Indonesia, B.Inggris, Matematika, PU, WK, Komputer), dan Psikologi. Jadwal 5x/minggu.',
     6, 1500000, 1),
    ('Akademik Reguler', 'Akademik',
     'Program akademik reguler: SKD dan Mata Pelajaran pilihan. Jadwal 3x/minggu.',
     6, 1000000, 1),
    ('Jasmani Intensif', 'Jasmani',
     'Program jasmani intensif: Samapta A (Lari 12 menit), Samapta B (Pull Up, Sit Up, Push Up, Shuttle Run), dan Renang. Jadwal 6x/minggu.',
     6, 1200000, 1),
    ('Jasmani Reguler', 'Jasmani',
     'Program jasmani reguler: Samapta A dan B. Jadwal 4x/minggu.',
     6, 800000, 1);

SELECT CONCAT('[008] Seed program: ', ROW_COUNT(), ' paket ditambahkan.') AS migration_log;

-- ============================================================
-- 5. Verifikasi
-- ============================================================
SELECT nama_program, kategori_program, biaya_bulanan
FROM program WHERE is_active = 1 ORDER BY kategori_program, nama_program;

SELECT 'penilaian_mapel'     AS tabel, COUNT(*) AS rows FROM penilaian_mapel
UNION ALL
SELECT 'penilaian_psikologi' AS tabel, COUNT(*) AS rows FROM penilaian_psikologi;

SET FOREIGN_KEY_CHECKS = 1;

SELECT '[008] Migration selesai: tabel mapel + psikologi + 4 program seed.' AS final_status;

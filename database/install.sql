-- ============================================================
-- PERKASA MULIA TRAINING CENTER v2.0
-- MASTER INSTALL SQL — Fresh Install dari Awal
-- 
-- Database: u741010203_adminperkasa  (nama TIDAK diubah)
-- Charset : utf8mb4
-- Engine  : InnoDB
--
-- Cara pakai:
--   1. Login phpMyAdmin / Hostinger panel
--   2. Pilih database: u741010203_adminperkasa
--   3. Import file ini (SQL)
--   4. Selesai — semua tabel siap
--
-- PERINGATAN: Script ini DROP semua tabel lama, lalu CREATE ulang.
--             Semua data lama AKAN HILANG.
--             Backup dulu sebelum menjalankan di production!
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ============================================================
-- DROP semua tabel lama (urutan terbalik — anak dulu)
-- ============================================================
DROP TABLE IF EXISTS `audit_log`;
DROP TABLE IF EXISTS `attendance_siswa`;
DROP TABLE IF EXISTS `absensi_tutor`;
DROP TABLE IF EXISTS `pembayaran_siswa`;
DROP TABLE IF EXISTS `penilaian_binjas`;
DROP TABLE IF EXISTS `penilaian_akademik`;
DROP TABLE IF EXISTS `penilaian_mapel`;
DROP TABLE IF EXISTS `penilaian_psikologi`;
DROP TABLE IF EXISTS `jadwal_tutor`;
DROP TABLE IF EXISTS `jadwal`;
DROP TABLE IF EXISTS `membership_siswa`;
DROP TABLE IF EXISTS `transaksi_keuangan`;
DROP TABLE IF EXISTS `calon_siswa`;
DROP TABLE IF EXISTS `notifikasi`;
DROP TABLE IF EXISTS `siswa`;
DROP TABLE IF EXISTS `tutor`;
DROP TABLE IF EXISTS `program`;
DROP TABLE IF EXISTS `cabang`;
DROP TABLE IF EXISTS `users`;

-- ============================================================
-- TABEL: cabang
-- ============================================================
CREATE TABLE `cabang` (
    `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `kode`       VARCHAR(10)     NOT NULL UNIQUE,
    `nama`       VARCHAR(100)    NOT NULL,
    `alamat`     TEXT            NULL,
    `nomor_wa`   VARCHAR(20)     NULL,
    `is_aktif`   TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `cabang` (id, kode, nama, alamat, is_aktif)
VALUES (1, 'BGR', 'Perkasa Mulia Training Center — Bogor', 'Bogor, Jawa Barat', 1);

-- ============================================================
-- TABEL: users
-- ============================================================
CREATE TABLE `users` (
    `id`                  INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    `email`               VARCHAR(150)        NOT NULL,
    `password`            VARCHAR(255)        NOT NULL COMMENT 'bcrypt via password_hash()',
    `role`                ENUM('admin','tutor','siswa','owner') NOT NULL DEFAULT 'siswa',
    `nama_display`        VARCHAR(150)        NOT NULL DEFAULT '',
    `is_active`           TINYINT(1)          NOT NULL DEFAULT 1,
    `last_login_at`       DATETIME            NULL,
    `failed_login_count`  INT UNSIGNED        NOT NULL DEFAULT 0,
    `locked_until`        DATETIME            NULL,
    `totp_secret`         VARCHAR(64)         NULL     COMMENT 'Base32 TOTP secret (null = 2FA off)',
    `totp_enabled`        TINYINT(1)          NOT NULL DEFAULT 0,
    `created_at`          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`          DATETIME            NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email` (`email`),
    INDEX `idx_role_active` (`role`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin default: email=admin@perkasa.id, password=Admin1234!
-- GANTI PASSWORD INI SEGERA SETELAH LOGIN PERTAMA!
INSERT INTO `users` (email, password, role, nama_display, is_active)
VALUES ('admin@perkasa.id', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin Perkasa', 1);
-- Password di atas adalah: password (bcrypt)

-- ============================================================
-- TABEL: program
-- ============================================================
CREATE TABLE `program` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nama_program`     VARCHAR(100)    NOT NULL,
    `kategori_program` ENUM('Jasmani','Akademik','Fasilitas') NOT NULL DEFAULT 'Akademik',
    `deskripsi`        TEXT            NULL,
    `durasi_bulan`     INT UNSIGNED    NOT NULL DEFAULT 6,
    `biaya_bulanan`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `is_active`        TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`       DATETIME        NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_active` (`is_active`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `program` (nama_program, kategori_program, deskripsi, durasi_bulan, biaya_bulanan, is_active) VALUES
('Akademik Intensif', 'Akademik', 'Program akademik intensif: SKD, Mapel (B.Indonesia/Inggris/Mat/PU/WK/Komputer), Psikologi. 5x/minggu.', 6, 1500000, 1),
('Akademik Reguler',  'Akademik', 'Program akademik reguler: SKD dan Mapel pilihan. 3x/minggu.', 6, 1000000, 1),
('Jasmani Intensif',  'Jasmani',  'Program jasmani intensif: Samapta A+B, Renang. 6x/minggu.', 6, 1200000, 1),
('Jasmani Reguler',   'Jasmani',  'Program jasmani reguler: Samapta A+B. 4x/minggu.', 6, 800000, 1);

-- ============================================================
-- TABEL: tutor
-- ============================================================
CREATE TABLE `tutor` (
    `id`             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`        INT UNSIGNED    NOT NULL UNIQUE,
    `nama_lengkap`   VARCHAR(150)    NOT NULL,
    `nomor_wa`       VARCHAR(20)     NULL,
    `spesialisasi`   VARCHAR(100)    NULL,
    `tarif_per_sesi` DECIMAL(12,2)   NULL COMMENT 'Tarif per sesi (Rupiah)',
    `bank_nama`      VARCHAR(50)     NULL,
    `bank_rekening`  VARCHAR(30)     NULL,
    `bank_atas_nama` VARCHAR(100)    NULL,
    `status_aktif`   TINYINT(1)      NOT NULL DEFAULT 1,
    `cabang_id`      INT UNSIGNED    NOT NULL DEFAULT 1,
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`     DATETIME        NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id`  (`user_id`),
    INDEX `idx_status`   (`status_aktif`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: siswa
-- ============================================================
CREATE TABLE `siswa` (
    `id`                   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`              INT UNSIGNED    NOT NULL UNIQUE,
    `nomor_induk`          VARCHAR(20)     NOT NULL UNIQUE COMMENT 'PMTC-YY-NNNN',
    `nama_lengkap`         VARCHAR(150)    NOT NULL,
    `nomor_wa`             VARCHAR(20)     NULL,
    `nama_ortu`            VARCHAR(150)    NULL,
    `tanggal_lahir`        DATE            NULL,
    `jenis_kelamin`        ENUM('L','P')   NULL,
    `alamat`               TEXT            NULL,
    `asal_sekolah`         VARCHAR(150)    NULL,
    `target_seleksi`       VARCHAR(50)     NULL COMMENT 'Polri, TNI AD, Akmil, dst',
    `status_siswa`         ENUM('Aktif','Tidak Aktif','Lulus','Dropout') NOT NULL DEFAULT 'Aktif',
    `keterangan_lulus`     TEXT            NULL,
    `catatan`              TEXT            NULL,
    `foto_path`            VARCHAR(255)    NULL,
    `cabang_id`            INT UNSIGNED    NOT NULL DEFAULT 1,
    `created_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`           DATETIME        NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id`     (`user_id`),
    INDEX `idx_nomor_induk` (`nomor_induk`),
    INDEX `idx_status`      (`status_siswa`, `deleted_at`),
    INDEX `idx_nama`        (`nama_lengkap`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: membership_siswa
-- ============================================================
CREATE TABLE `membership_siswa` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `siswa_id`              INT UNSIGNED    NOT NULL,
    `program_id`            INT UNSIGNED    NOT NULL,
    `tanggal_mulai_aktif`   DATE            NOT NULL,
    `tanggal_selesai_aktif` DATE            NOT NULL,
    `status_membership`     ENUM('Aktif','Berjalan','Selesai','Dibatalkan') NOT NULL DEFAULT 'Aktif',
    `created_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_siswa`       (`siswa_id`),
    INDEX `idx_program`     (`program_id`),
    INDEX `idx_status_end`  (`status_membership`, `tanggal_selesai_aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: transaksi_keuangan
-- ============================================================
CREATE TABLE `transaksi_keuangan` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `jenis_arus`            ENUM('Pemasukan','Pengeluaran') NOT NULL,
    `kategori`              VARCHAR(80)     NULL COMMENT 'SPP Siswa, Gaji Tutor, Operasional, dst',
    `kategori_pemasukan`    VARCHAR(80)     NULL COMMENT 'Legacy compat',
    `kategori_pengeluaran`  VARCHAR(80)     NULL COMMENT 'Legacy compat',
    `nominal`               DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `keterangan_transaksi`  TEXT            NULL,
    `tanggal_transaksi`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by`            INT UNSIGNED    NULL,
    `cabang_id`             INT UNSIGNED    NOT NULL DEFAULT 1,
    `created_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`            DATETIME        NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_jenis`       (`jenis_arus`),
    INDEX `idx_tanggal`     (`tanggal_transaksi`),
    INDEX `idx_kategori`    (`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: jadwal
-- ============================================================
CREATE TABLE `jadwal` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `program_id`    INT UNSIGNED    NULL,
    `nama_kegiatan` VARCHAR(100)    NOT NULL,
    `materi`        VARCHAR(200)    NULL,
    `tanggal`       DATE            NOT NULL,
    `waktu_mulai`   TIME            NOT NULL,
    `waktu_selesai` TIME            NOT NULL,
    `lokasi`        VARCHAR(150)    NULL DEFAULT 'Perkasa Mulia Training Center',
    `cabang_id`     INT UNSIGNED    NOT NULL DEFAULT 1,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_tanggal`    (`tanggal`),
    INDEX `idx_program`    (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: jadwal_tutor
-- ============================================================
CREATE TABLE `jadwal_tutor` (
    `jadwal_id` INT UNSIGNED NOT NULL,
    `tutor_id`  INT UNSIGNED NOT NULL,
    PRIMARY KEY (`jadwal_id`, `tutor_id`),
    INDEX `idx_tutor` (`tutor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: absensi_tutor
-- ============================================================
CREATE TABLE `absensi_tutor` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `jadwal_id`     INT UNSIGNED    NOT NULL,
    `tutor_id`      INT UNSIGNED    NOT NULL,
    `status_hadir`  ENUM('Hadir','Izin','Sakit','Alpa') NOT NULL DEFAULT 'Hadir',
    `keterangan`    VARCHAR(255)    NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_jadwal_tutor` (`jadwal_id`, `tutor_id`),
    INDEX `idx_tutor` (`tutor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: attendance_siswa
-- ============================================================
CREATE TABLE `attendance_siswa` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `siswa_id`      INT UNSIGNED    NOT NULL,
    `jadwal_id`     INT UNSIGNED    NOT NULL,
    `status_hadir`  ENUM('Hadir','Izin','Sakit','Alpa') NOT NULL DEFAULT 'Alpa',
    `keterangan`    VARCHAR(255)    NULL,
    `dicatat_oleh`  INT UNSIGNED    NULL COMMENT 'tutor_id atau user_id admin',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_siswa_jadwal` (`siswa_id`, `jadwal_id`),
    INDEX `idx_siswa`   (`siswa_id`),
    INDEX `idx_jadwal`  (`jadwal_id`),
    INDEX `idx_status`  (`status_hadir`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kehadiran siswa per sesi jadwal';

-- ============================================================
-- TABEL: penilaian_binjas
-- ============================================================
CREATE TABLE `penilaian_binjas` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `siswa_id`         INT UNSIGNED    NOT NULL,
    `tanggal_tes`      DATE            NOT NULL,
    `institusi`        ENUM('polri','tni') NOT NULL DEFAULT 'polri',
    `gender`           ENUM('pria','wanita') NOT NULL DEFAULT 'pria',
    `lari_jarak_meter` DECIMAL(7,2)    NOT NULL DEFAULT 0,
    `pullup_repetisi`  DECIMAL(5,2)    NOT NULL DEFAULT 0,
    `situp_repetisi`   DECIMAL(5,2)    NOT NULL DEFAULT 0,
    `pushup_repetisi`  DECIMAL(5,2)    NOT NULL DEFAULT 0,
    `shuttlerun_detik` DECIMAL(6,2)    NOT NULL DEFAULT 0,
    `lunges_repetisi`  DECIMAL(5,2)    NULL,
    `renang_detik`     DECIMAL(6,2)    NULL,
    `skor_akhir`       DECIMAL(6,2)    NULL COMMENT 'Dihitung otomatis via binjas_scoring.php',
    `predikat`         VARCHAR(30)     NULL,
    `catatan`          TEXT            NULL,
    `created_by`       INT UNSIGNED    NULL,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`       DATETIME        NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_siswa`   (`siswa_id`),
    INDEX `idx_tanggal` (`tanggal_tes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: penilaian_akademik (SKD / CPNS style)
-- ============================================================
CREATE TABLE `penilaian_akademik` (
    `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `siswa_id`     INT UNSIGNED    NOT NULL,
    `tanggal_tes`  DATE            NOT NULL,
    `kategori_tes` ENUM('SKD','Tryout','Simulasi') NOT NULL DEFAULT 'SKD',
    `nilai_twk`    DECIMAL(6,2)    NOT NULL DEFAULT 0 COMMENT 'TWK min 65',
    `nilai_tiu`    DECIMAL(6,2)    NOT NULL DEFAULT 0 COMMENT 'TIU min 80',
    `nilai_tkp`    DECIMAL(6,2)    NOT NULL DEFAULT 0 COMMENT 'TKP min 166',
    `total_skor`   DECIMAL(8,2)    GENERATED ALWAYS AS (`nilai_twk` + `nilai_tiu` + `nilai_tkp`) STORED,
    `catatan`      TEXT            NULL,
    `created_by`   INT UNSIGNED    NULL,
    `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`   DATETIME        NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_siswa`    (`siswa_id`),
    INDEX `idx_tanggal`  (`tanggal_tes`),
    INDEX `idx_kategori` (`kategori_tes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: penilaian_mapel
-- ============================================================
CREATE TABLE `penilaian_mapel` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `siswa_id`         INT UNSIGNED    NOT NULL,
    `tanggal_tes`      DATE            NOT NULL,
    `matematika`       DECIMAL(5,2)    NOT NULL DEFAULT 0,
    `bahasa_indonesia` DECIMAL(5,2)    NOT NULL DEFAULT 0,
    `bahasa_inggris`   DECIMAL(5,2)    NOT NULL DEFAULT 0,
    `pengetahuan_umum` DECIMAL(5,2)    NOT NULL DEFAULT 0,
    `wawasan_kebangsaan` DECIMAL(5,2)  NOT NULL DEFAULT 0,
    `rata_mapel`       DECIMAL(5,2)    GENERATED ALWAYS AS (
        ROUND((`matematika`+`bahasa_indonesia`+`bahasa_inggris`+`pengetahuan_umum`+`wawasan_kebangsaan`)/5,2)
    ) STORED,
    `catatan`          TEXT            NULL,
    `created_by`       INT UNSIGNED    NULL,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `deleted_at`       DATETIME        NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_siswa`   (`siswa_id`),
    INDEX `idx_tanggal` (`tanggal_tes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: penilaian_psikologi
-- ============================================================
CREATE TABLE `penilaian_psikologi` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `siswa_id`         INT UNSIGNED    NOT NULL,
    `tanggal_tes`      DATE            NOT NULL,
    `kecerdasan`       DECIMAL(5,2)    NOT NULL DEFAULT 0,
    `kecermatan`       DECIMAL(5,2)    NOT NULL DEFAULT 0,
    `kepribadian`      DECIMAL(5,2)    NOT NULL DEFAULT 0,
    `rata_psikologi`   DECIMAL(5,2)    GENERATED ALWAYS AS (
        ROUND((`kecerdasan`+`kecermatan`+`kepribadian`)/3,2)
    ) STORED,
    `status_psikologi` ENUM('Lulus','Tidak Lulus','Belum Dinilai') GENERATED ALWAYS AS (
        CASE WHEN `kecerdasan`>=61 AND `kecermatan`>=61 AND `kepribadian`>=61 THEN 'Lulus' ELSE 'Tidak Lulus' END
    ) STORED,
    `catatan`          TEXT            NULL,
    `sumber`           ENUM('Manual','CAT') NOT NULL DEFAULT 'Manual',
    `sumber_referensi` VARCHAR(100)    NULL COMMENT 'session_id CAT jika sumber=CAT',
    `created_by`       INT UNSIGNED    NULL,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`       DATETIME        NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_siswa`   (`siswa_id`),
    INDEX `idx_tanggal` (`tanggal_tes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: pembayaran_siswa
-- ============================================================
CREATE TABLE `pembayaran_siswa` (
    `id`                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `siswa_id`            INT UNSIGNED    NOT NULL,
    `membership_id`       INT UNSIGNED    NULL,
    `periode_bulan`       DATE            NOT NULL COMMENT 'Format: YYYY-MM-01',
    `nominal_tagihan`     DECIMAL(15,2)   NOT NULL DEFAULT 0,
    `nominal_bayar`       DECIMAL(15,2)   NOT NULL DEFAULT 0,
    `status_bayar`        ENUM('Lunas','Cicilan','Belum Bayar') NOT NULL DEFAULT 'Belum Bayar',
    `tanggal_bayar`       DATETIME        NULL,
    `metode_pembayaran`   ENUM('Tunai','Transfer','QRIS','Lainnya') NULL,
    `bukti_path`          VARCHAR(255)    NULL,
    `keterangan`          VARCHAR(255)    NULL,
    `created_by`          INT UNSIGNED    NULL,
    `created_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`          DATETIME        NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_siswa_periode` (`siswa_id`, `periode_bulan`),
    INDEX `idx_siswa`    (`siswa_id`),
    INDEX `idx_status`   (`status_bayar`),
    INDEX `idx_periode`  (`periode_bulan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: audit_log
-- ============================================================
CREATE TABLE `audit_log` (
    `id`           BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED        NULL COMMENT 'NULL = system/guest',
    `action`       VARCHAR(100)        NOT NULL,
    `target_table` VARCHAR(50)         NOT NULL,
    `target_id`    INT UNSIGNED        NULL,
    `old_values`   JSON                NULL,
    `new_values`   JSON                NULL,
    `ip_address`   VARCHAR(45)         NULL,
    `user_agent`   VARCHAR(255)        NULL,
    `created_at`   DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user`   (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_target` (`target_table`, `target_id`),
    INDEX `idx_date`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: notifikasi
-- ============================================================
CREATE TABLE `notifikasi` (
    `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED    NULL COMMENT 'NULL = broadcast ke semua',
    `judul`      VARCHAR(150)    NOT NULL,
    `pesan`      TEXT            NOT NULL,
    `link_url`   VARCHAR(255)    NULL,
    `channel`    ENUM('in_app','whatsapp','email') NOT NULL DEFAULT 'in_app',
    `is_read`    TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_read` (`user_id`, `is_read`),
    INDEX `idx_channel`   (`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: calon_siswa (pendaftaran online)
-- ============================================================
CREATE TABLE `calon_siswa` (
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
    INDEX `idx_status`  (`status`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FOREIGN KEYS
-- ============================================================
ALTER TABLE `siswa`
    ADD CONSTRAINT `fk_siswa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `tutor`
    ADD CONSTRAINT `fk_tutor_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `membership_siswa`
    ADD CONSTRAINT `fk_ms_siswa`   FOREIGN KEY (`siswa_id`)   REFERENCES `siswa`   (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_ms_program` FOREIGN KEY (`program_id`) REFERENCES `program` (`id`) ON DELETE RESTRICT;

ALTER TABLE `jadwal`
    ADD CONSTRAINT `fk_j_program` FOREIGN KEY (`program_id`) REFERENCES `program` (`id`) ON DELETE SET NULL;

ALTER TABLE `jadwal_tutor`
    ADD CONSTRAINT `fk_jt_jadwal` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_jt_tutor`  FOREIGN KEY (`tutor_id`)  REFERENCES `tutor`  (`id`) ON DELETE CASCADE;

ALTER TABLE `absensi_tutor`
    ADD CONSTRAINT `fk_at_jadwal` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_at_tutor`  FOREIGN KEY (`tutor_id`)  REFERENCES `tutor`  (`id`) ON DELETE CASCADE;

ALTER TABLE `attendance_siswa`
    ADD CONSTRAINT `fk_as_siswa`  FOREIGN KEY (`siswa_id`)  REFERENCES `siswa`  (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_as_jadwal` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE;

ALTER TABLE `penilaian_binjas`
    ADD CONSTRAINT `fk_pb_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE;

ALTER TABLE `penilaian_akademik`
    ADD CONSTRAINT `fk_pa_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE;

ALTER TABLE `penilaian_mapel`
    ADD CONSTRAINT `fk_pm_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE;

ALTER TABLE `penilaian_psikologi`
    ADD CONSTRAINT `fk_pp_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE;

ALTER TABLE `pembayaran_siswa`
    ADD CONSTRAINT `fk_pys_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- VERIFIKASI
-- ============================================================
SELECT 
    table_name AS tabel,
    table_rows AS estimasi_baris,
    ROUND((data_length + index_length) / 1024, 1) AS ukuran_kb
FROM information_schema.tables
WHERE table_schema = DATABASE()
ORDER BY table_name;

SELECT 'INSTALL SELESAI. Login dengan admin@perkasa.id / password' AS status;
-- INGAT: Ganti password admin setelah login pertama!

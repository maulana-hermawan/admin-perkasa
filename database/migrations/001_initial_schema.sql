-- ============================================================
-- 001_initial_schema.sql
-- PERKASA MULIA TRAINING CENTER v2.0
-- DDL FRESH INSTALL — Hanya jalankan di database KOSONG
-- Untuk DB existing, SKIP file ini, mulai dari 002_cleanup.sql
-- ============================================================
-- Dibuat  : 2026-05-02
-- Versi   : 2.0.0
-- ROLLBACK: DROP DATABASE dan buat ulang (fresh install saja)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;
SET time_zone = '+07:00';

-- ============================================================
-- USERS (multi-role: admin, tutor, siswa, owner)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`                  INT UNSIGNED       NOT NULL AUTO_INCREMENT,
    `email`               VARCHAR(150)       NOT NULL,
    `password`            VARCHAR(255)       NOT NULL COMMENT 'bcrypt hash via password_hash()',
    `role`                ENUM('admin','tutor','siswa','owner') NOT NULL DEFAULT 'siswa',
    `nama_display`        VARCHAR(150)       NOT NULL DEFAULT '',
    `is_active`           TINYINT(1)         NOT NULL DEFAULT 1,
    `last_login_at`       DATETIME           NULL,
    `failed_login_count`  INT UNSIGNED       NOT NULL DEFAULT 0,
    `locked_until`        DATETIME           NULL,
    `created_at`          DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`          DATETIME           NULL COMMENT 'Soft delete',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email` (`email`),
    INDEX `idx_role_active` (`role`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PROGRAM
-- ============================================================
CREATE TABLE IF NOT EXISTS `program` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `nama_program`    VARCHAR(100)    NOT NULL,
    `kategori_program` ENUM('Jasmani','Akademik','Fasilitas') NOT NULL,
    `deskripsi`       TEXT            NULL,
    `durasi_bulan`    INT UNSIGNED    NOT NULL DEFAULT 6,
    `biaya_bulanan`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `is_active`       TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`      DATETIME        NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_active` (`is_active`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SISWA
-- ============================================================
CREATE TABLE IF NOT EXISTS `siswa` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`           INT UNSIGNED    NULL UNIQUE COMMENT 'NULL = belum punya akun login',
    `nomor_induk`       VARCHAR(20)     NOT NULL UNIQUE COMMENT 'Format: PMTC-YY-NNNN',
    `nama_lengkap`      VARCHAR(150)    NOT NULL,
    `jenis_kelamin`     ENUM('L','P')   NULL,
    `tanggal_lahir`     DATE            NULL,
    `alamat`            TEXT            NULL,
    `asal_sekolah`      VARCHAR(150)    NULL,
    `target_seleksi`    ENUM('Polri','TNI AD','TNI AL','TNI AU','Akpol','Akmil','Bintara','Tamtama','Lainnya') NULL,
    `nomor_wa`          VARCHAR(20)     NOT NULL DEFAULT '',
    `nama_ortu`         VARCHAR(150)    NULL,
    `nomor_wa_ortu`     VARCHAR(20)     NULL,
    `status_siswa`      ENUM('Aktif','Tidak Aktif','Cuti','Lulus') NOT NULL DEFAULT 'Tidak Aktif',
    `keterangan_lulus`  VARCHAR(255)    NULL,
    `foto_path`         VARCHAR(255)    NULL,
    `catatan`           TEXT            NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`        DATETIME        NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_nomor_induk` (`nomor_induk`),
    INDEX `idx_status` (`status_siswa`, `deleted_at`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TUTOR
-- ============================================================
CREATE TABLE IF NOT EXISTS `tutor` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    NOT NULL UNIQUE,
    `nama_lengkap`  VARCHAR(150)    NOT NULL,
    `nomor_wa`      VARCHAR(20)     NULL,
    `spesialisasi`  VARCHAR(100)    NULL,
    `status_aktif`  TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`    DATETIME        NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status_aktif`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- JADWAL
-- ============================================================
CREATE TABLE IF NOT EXISTS `jadwal` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `program_id`    INT UNSIGNED    NOT NULL,
    `nama_kegiatan` ENUM('Jasmani','Renang','Akademik','Psikologi','Tryout') NOT NULL,
    `materi`        VARCHAR(255)    NULL,
    `tanggal`       DATE            NOT NULL,
    `waktu_mulai`   TIME            NOT NULL,
    `waktu_selesai` TIME            NOT NULL,
    `lokasi`        VARCHAR(100)    NULL,
    `created_by`    INT UNSIGNED    NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_program_tanggal` (`program_id`, `tanggal`),
    INDEX `idx_tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- JADWAL_TUTOR (pivot)
-- ============================================================
CREATE TABLE IF NOT EXISTS `jadwal_tutor` (
    `jadwal_id` INT UNSIGNED NOT NULL,
    `tutor_id`  INT UNSIGNED NOT NULL,
    PRIMARY KEY (`jadwal_id`, `tutor_id`),
    INDEX `idx_tutor` (`tutor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MEMBERSHIP SISWA
-- ============================================================
CREATE TABLE IF NOT EXISTS `membership_siswa` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `siswa_id`              INT UNSIGNED    NOT NULL,
    `program_id`            INT UNSIGNED    NOT NULL,
    `tanggal_mulai_aktif`   DATE            NOT NULL,
    `tanggal_selesai_aktif` DATE            NOT NULL,
    `status_membership`     ENUM('Berjalan','Selesai','Cuti') NOT NULL DEFAULT 'Berjalan',
    `created_by`            INT UNSIGNED    NULL,
    `created_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_siswa` (`siswa_id`),
    INDEX `idx_program` (`program_id`),
    INDEX `idx_status` (`status_membership`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ABSENSI TUTOR
-- ============================================================
CREATE TABLE IF NOT EXISTS `absensi_tutor` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `jadwal_id`         INT UNSIGNED    NOT NULL,
    `tutor_id`          INT UNSIGNED    NOT NULL,
    `status_kehadiran`  ENUM('Hadir','Izin','Sakit','Alpa') NOT NULL,
    `keterangan`        VARCHAR(255)    NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_jadwal_tutor` (`jadwal_id`, `tutor_id`),
    INDEX `idx_tutor` (`tutor_id`),
    INDEX `idx_jadwal` (`jadwal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PENILAIAN AKADEMIK
-- ============================================================
CREATE TABLE IF NOT EXISTS `penilaian_akademik` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `siswa_id`              INT UNSIGNED    NOT NULL,
    `tanggal_tes`           DATE            NOT NULL,
    `kategori_tes`          ENUM('SKD','Psikotes','Tryout') NOT NULL DEFAULT 'SKD',
    `nilai_twk`             SMALLINT        NOT NULL DEFAULT 0,
    `nilai_tiu`             SMALLINT        NOT NULL DEFAULT 0,
    `nilai_tkp`             SMALLINT        NOT NULL DEFAULT 0,
    `total_skor`            SMALLINT        NOT NULL DEFAULT 0,
    `passing_grade_status`  ENUM('Lulus','Tidak Lulus','Belum Dinilai') NOT NULL DEFAULT 'Belum Dinilai',
    `sumber`                ENUM('Manual','CAT') NOT NULL DEFAULT 'Manual',
    `sumber_referensi`      VARCHAR(100)    NULL COMMENT 'session_id jika dari CAT',
    `created_by`            INT UNSIGNED    NULL,
    `created_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_siswa_tanggal` (`siswa_id`, `tanggal_tes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PENILAIAN BINJAS
-- ============================================================
CREATE TABLE IF NOT EXISTS `penilaian_binjas` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `siswa_id`              INT UNSIGNED    NOT NULL,
    `tanggal_tes`           DATE            NOT NULL,
    `target_kategori`       ENUM('Polri','TNI AD','TNI AL','TNI AU','Akpol','Akmil','Umum') NOT NULL DEFAULT 'Umum',
    `lari_jarak_meter`      INT             NOT NULL DEFAULT 0,
    `lari_waktu_detik`      DECIMAL(6,2)    NULL COMMENT 'Waktu tempuh lari dalam detik',
    `skor_lari`             TINYINT UNSIGNED NULL,
    `pullup_repetisi`       INT             NOT NULL DEFAULT 0,
    `skor_pullup`           TINYINT UNSIGNED NULL,
    `pushup_repetisi`       INT             NOT NULL DEFAULT 0,
    `skor_pushup`           TINYINT UNSIGNED NULL,
    `situp_repetisi`        INT             NOT NULL DEFAULT 0,
    `skor_situp`            TINYINT UNSIGNED NULL,
    `shuttlerun_detik`      DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `skor_shuttlerun`       TINYINT UNSIGNED NULL,
    `renang_detik`          DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `skor_renang`           TINYINT UNSIGNED NULL,
    `total_skor`            SMALLINT UNSIGNED NULL,
    `predikat`              ENUM('Sangat Baik','Baik','Cukup','Kurang','Sangat Kurang') NULL,
    `created_by`            INT UNSIGNED    NULL,
    `created_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_siswa_tanggal` (`siswa_id`, `tanggal_tes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TRANSAKSI KEUANGAN (unified kategori)
-- ============================================================
CREATE TABLE IF NOT EXISTS `transaksi_keuangan` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `jenis_arus`            ENUM('Pemasukan','Pengeluaran') NOT NULL,
    `kategori`              VARCHAR(50)     NOT NULL COMMENT 'Unified: Bayar Program, Biaya Admin, Belanja Operasional, dll',
    `nominal`               DECIMAL(15,2)   NOT NULL,
    `tanggal_transaksi`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `keterangan_transaksi`  TEXT            NULL,
    `metode_pembayaran`     ENUM('Tunai','Transfer','QRIS','Lainnya') NOT NULL DEFAULT 'Tunai',
    `bukti_path`            VARCHAR(255)    NULL COMMENT 'Path file bukti transfer',
    `siswa_id`              INT UNSIGNED    NULL,
    `membership_id`         INT UNSIGNED    NULL,
    `tutor_id`              INT UNSIGNED    NULL,
    `pembayaran_id`         INT UNSIGNED    NULL COMMENT 'Link ke pembayaran_siswa jika ada',
    `created_by`            INT UNSIGNED    NULL,
    `created_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_jenis_tanggal` (`jenis_arus`, `tanggal_transaksi`),
    INDEX `idx_siswa` (`siswa_id`),
    INDEX `idx_tutor` (`tutor_id`),
    INDEX `idx_membership` (`membership_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PEMBAYARAN SISWA (rekap SPP per siswa)
-- ============================================================
CREATE TABLE IF NOT EXISTS `pembayaran_siswa` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `siswa_id`          INT UNSIGNED    NOT NULL,
    `membership_id`     INT UNSIGNED    NOT NULL,
    `periode_bulan`     DATE            NOT NULL COMMENT 'Tanggal awal bulan: 2026-04-01',
    `nominal_tagihan`   DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `nominal_bayar`     DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    `status_bayar`      ENUM('Lunas','Cicilan','Belum Bayar') NOT NULL DEFAULT 'Belum Bayar',
    `tanggal_bayar`     DATETIME        NULL,
    `metode_pembayaran` ENUM('Tunai','Transfer','QRIS','Lainnya') NULL,
    `keterangan`        VARCHAR(255)    NULL,
    `created_by`        INT UNSIGNED    NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_siswa_membership_periode` (`siswa_id`, `membership_id`, `periode_bulan`),
    INDEX `idx_siswa` (`siswa_id`),
    INDEX `idx_status` (`status_bayar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ATTENDANCE SISWA (kehadiran per jadwal)
-- ============================================================
CREATE TABLE IF NOT EXISTS `attendance_siswa` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `siswa_id`          INT UNSIGNED    NOT NULL,
    `jadwal_id`         INT UNSIGNED    NOT NULL,
    `status_hadir`      ENUM('Hadir','Izin','Sakit','Alpa') NOT NULL DEFAULT 'Alpa',
    `keterangan`        VARCHAR(255)    NULL,
    `dicatat_oleh`      INT UNSIGNED    NULL COMMENT 'tutor_id yang input absensi',
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_siswa_jadwal` (`siswa_id`, `jadwal_id`),
    INDEX `idx_siswa` (`siswa_id`),
    INDEX `idx_jadwal` (`jadwal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- AUDIT LOG
-- ============================================================
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    NULL,
    `action`        VARCHAR(100)    NOT NULL COMMENT 'CREATE_SISWA, UPDATE_TRANSAKSI, LOGIN_FAILED, dll',
    `target_table`  VARCHAR(50)     NOT NULL,
    `target_id`     INT UNSIGNED    NULL,
    `old_values`    JSON            NULL,
    `new_values`    JSON            NULL,
    `ip_address`    VARCHAR(45)     NULL,
    `user_agent`    VARCHAR(255)    NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_date` (`user_id`, `created_at`),
    INDEX `idx_target` (`target_table`, `target_id`),
    INDEX `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- NOTIFIKASI (in-app & queue WhatsApp)
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifikasi` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED    NULL COMMENT 'NULL = broadcast semua',
    `channel`       ENUM('in_app','whatsapp','email') NOT NULL DEFAULT 'in_app',
    `judul`         VARCHAR(200)    NOT NULL,
    `pesan`         TEXT            NOT NULL,
    `link_url`      VARCHAR(255)    NULL,
    `is_read`       TINYINT(1)      NOT NULL DEFAULT 0,
    `sent_at`       DATETIME        NULL,
    `sent_status`   ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_unread` (`user_id`, `is_read`),
    INDEX `idx_channel_status` (`channel`, `sent_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FOREIGN KEYS
-- ============================================================
ALTER TABLE `siswa`
    ADD CONSTRAINT `fk_siswa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `tutor`
    ADD CONSTRAINT `fk_tutor_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `jadwal`
    ADD CONSTRAINT `fk_jadwal_program` FOREIGN KEY (`program_id`) REFERENCES `program` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_jadwal_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `jadwal_tutor`
    ADD CONSTRAINT `fk_jt_jadwal` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_jt_tutor` FOREIGN KEY (`tutor_id`) REFERENCES `tutor` (`id`) ON DELETE CASCADE;

ALTER TABLE `membership_siswa`
    ADD CONSTRAINT `fk_ms_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_ms_program` FOREIGN KEY (`program_id`) REFERENCES `program` (`id`),
    ADD CONSTRAINT `fk_ms_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `absensi_tutor`
    ADD CONSTRAINT `fk_at_jadwal` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_at_tutor` FOREIGN KEY (`tutor_id`) REFERENCES `tutor` (`id`) ON DELETE CASCADE;

ALTER TABLE `penilaian_akademik`
    ADD CONSTRAINT `fk_pa_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_pa_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `penilaian_binjas`
    ADD CONSTRAINT `fk_pb_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_pb_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `transaksi_keuangan`
    ADD CONSTRAINT `fk_tk_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `fk_tk_membership` FOREIGN KEY (`membership_id`) REFERENCES `membership_siswa` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `fk_tk_tutor` FOREIGN KEY (`tutor_id`) REFERENCES `tutor` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `fk_tk_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `pembayaran_siswa`
    ADD CONSTRAINT `fk_ps_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_ps_membership` FOREIGN KEY (`membership_id`) REFERENCES `membership_siswa` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_ps_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `attendance_siswa`
    ADD CONSTRAINT `fk_as_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_as_jadwal` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_as_tutor` FOREIGN KEY (`dicatat_oleh`) REFERENCES `tutor` (`id`) ON DELETE SET NULL;

ALTER TABLE `audit_log`
    ADD CONSTRAINT `fk_al_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `notifikasi`
    ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;
-- ============================================================
-- END OF 001_initial_schema.sql
-- Next: Jalankan 002_cleanup_orphan.sql (untuk DB existing)
-- ============================================================

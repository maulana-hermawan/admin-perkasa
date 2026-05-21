-- ============================================================
-- Migration 012: Program Types, Paket, Multi-Membership, SPP Redesign
-- Jalankan di phpMyAdmin sebelum upload kode baru
-- ============================================================

-- 1. Tambah tipe_program ke tabel program
ALTER TABLE `program`
  ADD COLUMN `tipe_program` ENUM('Program','Fasilitas','Paket') NOT NULL DEFAULT 'Program'
    COMMENT 'Program=reguler/jadwal, Fasilitas=mess dll, Paket=bundel'
    AFTER `nama_program`;

-- Set tipe existing programs
UPDATE `program` SET `tipe_program` = 'Program'
  WHERE `nama_program` IN ('Akademik Intensif','Akademik Reguler','Jasmani Intensif','Jasmani Reguler');

-- Insert programs baru (Kelas Tambahan, Private)
INSERT IGNORE INTO `program` (`nama_program`,`tipe_program`,`deskripsi`,`durasi_bulan`,`biaya_bulanan`,`is_active`) VALUES
('Kelas Tambahan','Program','Sesi tambahan di luar jadwal reguler.',1,300000,1),
('Private','Program','Bimbingan private one-on-one dengan tutor.',1,500000,1),
('Mess / Fasilitas','Fasilitas','Fasilitas penginapan (mess) di lokasi training center.',1,800000,1);

-- Insert paket
INSERT IGNORE INTO `program` (`nama_program`,`tipe_program`,`deskripsi`,`durasi_bulan`,`biaya_bulanan`,`is_active`) VALUES
('Paket Intensif','Paket','Akademik Intensif + Jasmani Intensif. Full program Polri/TNI.',6,2500000,1),
('Paket Reguler','Paket','Akademik Reguler + Jasmani Reguler.',6,1700000,1);

-- 2. Tabel paket_komponen (many-to-many paket → program)
CREATE TABLE IF NOT EXISTS `paket_komponen` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `paket_id`   INT UNSIGNED NOT NULL COMMENT 'FK ke program (tipe_program=Paket)',
    `program_id` INT UNSIGNED NOT NULL COMMENT 'FK ke program (tipe_program=Program)',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_paket_program` (`paket_id`,`program_id`),
    INDEX `idx_paket`   (`paket_id`),
    INDEX `idx_program` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Isi paket_komponen dari data existing
-- Paket Intensif = Akademik Intensif (1) + Jasmani Intensif (3)
INSERT IGNORE INTO `paket_komponen` (`paket_id`,`program_id`)
  SELECT p.id, k.id FROM `program` p, `program` k
  WHERE p.nama_program = 'Paket Intensif'
    AND k.nama_program IN ('Akademik Intensif','Jasmani Intensif');

-- Paket Reguler = Akademik Reguler (2) + Jasmani Reguler (4)
INSERT IGNORE INTO `paket_komponen` (`paket_id`,`program_id`)
  SELECT p.id, k.id FROM `program` p, `program` k
  WHERE p.nama_program = 'Paket Reguler'
    AND k.nama_program IN ('Akademik Reguler','Jasmani Reguler');

-- 3. Membership: tambah biaya_per_bulan custom (NULL = pakai harga program)
ALTER TABLE `membership_siswa`
  ADD COLUMN `biaya_per_bulan` DECIMAL(12,2) NULL
    COMMENT 'Harga custom per bulan untuk siswa ini. NULL = pakai biaya_bulanan program.'
    AFTER `program_id`;

-- 4. Tabel pembayaran_siswa_detail (line items per program per payment)
CREATE TABLE IF NOT EXISTS `pembayaran_siswa_detail` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `pembayaran_id`   INT UNSIGNED    NOT NULL,
    `membership_id`   INT UNSIGNED    NULL,
    `program_id`      INT UNSIGNED    NULL,
    `nama_program`    VARCHAR(100)    NOT NULL,
    `biaya_per_bulan` DECIMAL(12,2)   NOT NULL DEFAULT 0,
    `jumlah_bulan`    INT UNSIGNED    NOT NULL DEFAULT 1,
    `subtotal`        DECIMAL(15,2)   GENERATED ALWAYS AS (`biaya_per_bulan` * `jumlah_bulan`) STORED,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_pembayaran` (`pembayaran_id`),
    INDEX `idx_membership` (`membership_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Ubah pembayaran_siswa: hapus periode_bulan, tambah jumlah_bulan
--    (periode_bulan dijadikan nullable dulu untuk backward compat, lalu drop)
ALTER TABLE `pembayaran_siswa`
  ADD COLUMN `jumlah_bulan`   INT UNSIGNED NOT NULL DEFAULT 1
    COMMENT 'Berapa bulan yang dibayar'
    AFTER `membership_id`,
  ADD COLUMN `keterangan_program` VARCHAR(255) NULL
    COMMENT 'Ringkasan program yang dibayar'
    AFTER `jumlah_bulan`,
  MODIFY COLUMN `periode_bulan` DATE NULL
    COMMENT 'Legacy — tidak dipakai untuk transaksi baru',
  MODIFY COLUMN `membership_id` INT UNSIGNED NULL
    COMMENT 'Legacy — detail ada di pembayaran_siswa_detail';

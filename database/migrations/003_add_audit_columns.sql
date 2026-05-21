-- ============================================================
-- 003_add_audit_columns.sql
-- PERKASA MULIA TRAINING CENTER v2.0
-- ALTER tabel existing: tambah kolom audit, security, & field baru
-- ============================================================
-- Dibuat  : 2026-05-02
-- Prasyarat: 002_cleanup_orphan.sql sudah dijalankan
-- Idempoten: YA — pakai IF NOT EXISTS / cek COLUMN_NAME
-- ROLLBACK : Lihat bagian ROLLBACK di bawah setiap ALTER
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- Helper: Prosedur untuk ADD COLUMN IF NOT EXISTS
-- (MariaDB 10.x belum punya ALTER TABLE ... ADD COLUMN IF NOT EXISTS secara native
--  untuk semua versi, jadi kita pakai stored procedure sementara)

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
        SELECT CONCAT('[003] Added column: ', p_table, '.', p_column) AS migration_log;
    ELSE
        SELECT CONCAT('[003] Skipped (exists): ', p_table, '.', p_column) AS migration_log;
    END IF;
END$$
DELIMITER ;

-- ============================================================
-- TABLE: users — tambah kolom security & profil
-- ============================================================

CALL add_column_if_not_exists('users', 'nama_display',
    '`nama_display` VARCHAR(150) NOT NULL DEFAULT \'\' AFTER `role`');

CALL add_column_if_not_exists('users', 'is_active',
    '`is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `nama_display`');

CALL add_column_if_not_exists('users', 'last_login_at',
    '`last_login_at` DATETIME NULL AFTER `is_active`');

CALL add_column_if_not_exists('users', 'failed_login_count',
    '`failed_login_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `last_login_at`');

CALL add_column_if_not_exists('users', 'locked_until',
    '`locked_until` DATETIME NULL AFTER `failed_login_count`');

CALL add_column_if_not_exists('users', 'updated_at',
    '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');

CALL add_column_if_not_exists('users', 'deleted_at',
    '`deleted_at` DATETIME NULL AFTER `updated_at`');

-- Isi nama_display dari email (untuk data existing)
UPDATE `users`
SET `nama_display` = SUBSTRING_INDEX(`email`, '@', 1)
WHERE `nama_display` = '' OR `nama_display` IS NULL;

-- Update admin utama dengan nama proper
UPDATE `users`
SET `nama_display` = 'Administrator Perkasa'
WHERE `email` = 'admin@bimbelperkasa.id';

-- ROLLBACK:
-- ALTER TABLE users DROP COLUMN nama_display, DROP COLUMN is_active,
-- DROP COLUMN last_login_at, DROP COLUMN failed_login_count,
-- DROP COLUMN locked_until, DROP COLUMN updated_at, DROP COLUMN deleted_at;

-- ============================================================
-- TABLE: siswa — tambah kolom profil lengkap
-- ============================================================

-- Ubah user_id dari NOT NULL ke NULL (siswa mungkin belum punya akun)
-- Dulu: user_id int(11) NOT NULL
-- Sekarang: user_id int(11) NULL (aman karena FK akan SET NULL)
-- Cek dulu apakah perlu diubah:
SET @col_null = (
    SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'siswa' AND COLUMN_NAME = 'user_id'
);
SET @sql_nullable = IF(@col_null = 'NO',
    'ALTER TABLE `siswa` MODIFY COLUMN `user_id` INT(11) NULL',
    'SELECT "[003] siswa.user_id already nullable" AS migration_log'
);
PREPARE stmt FROM @sql_nullable;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CALL add_column_if_not_exists('siswa', 'jenis_kelamin',
    '`jenis_kelamin` ENUM(\'L\',\'P\') NULL AFTER `nama_lengkap`');

CALL add_column_if_not_exists('siswa', 'asal_sekolah',
    '`asal_sekolah` VARCHAR(150) NULL AFTER `nomor_wa_ortu`');

CALL add_column_if_not_exists('siswa', 'target_seleksi',
    '`target_seleksi` ENUM(\'Polri\',\'TNI AD\',\'TNI AL\',\'TNI AU\',\'Akpol\',\'Akmil\',\'Bintara\',\'Tamtama\',\'Lainnya\') NULL AFTER `asal_sekolah`');

CALL add_column_if_not_exists('siswa', 'foto_path',
    '`foto_path` VARCHAR(255) NULL AFTER `keterangan_lulus`');

CALL add_column_if_not_exists('siswa', 'catatan',
    '`catatan` TEXT NULL AFTER `foto_path`');

CALL add_column_if_not_exists('siswa', 'updated_at',
    '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');

CALL add_column_if_not_exists('siswa', 'deleted_at',
    '`deleted_at` DATETIME NULL AFTER `updated_at`');

-- Pastikan nomor_induk nullable (trigger akan mengisi)
-- Kolom existing: varchar(10) DEFAULT NULL — sudah OK, hanya extend ukuran ke 20
SET @col_induk = (
    SELECT CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'siswa' AND COLUMN_NAME = 'nomor_induk'
);
SET @sql_induk = IF(@col_induk < 20,
    'ALTER TABLE `siswa` MODIFY COLUMN `nomor_induk` VARCHAR(20) DEFAULT NULL',
    'SELECT "[003] siswa.nomor_induk sudah VARCHAR(20)" AS migration_log'
);
PREPARE stmt FROM @sql_induk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ROLLBACK:
-- ALTER TABLE siswa DROP COLUMN jenis_kelamin, DROP COLUMN asal_sekolah,
-- DROP COLUMN target_seleksi, DROP COLUMN foto_path, DROP COLUMN catatan,
-- DROP COLUMN updated_at, DROP COLUMN deleted_at;

-- ============================================================
-- TABLE: tutor — tambah kolom audit
-- ============================================================

CALL add_column_if_not_exists('tutor', 'created_at',
    '`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `status_aktif`');

CALL add_column_if_not_exists('tutor', 'updated_at',
    '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');

CALL add_column_if_not_exists('tutor', 'deleted_at',
    '`deleted_at` DATETIME NULL AFTER `updated_at`');

-- ROLLBACK:
-- ALTER TABLE tutor DROP COLUMN created_at, DROP COLUMN updated_at, DROP COLUMN deleted_at;

-- ============================================================
-- TABLE: program — tambah field bisnis & audit
-- ============================================================

CALL add_column_if_not_exists('program', 'deskripsi',
    '`deskripsi` TEXT NULL AFTER `kategori_program`');

CALL add_column_if_not_exists('program', 'durasi_bulan',
    '`durasi_bulan` INT UNSIGNED NOT NULL DEFAULT 6 AFTER `deskripsi`');

CALL add_column_if_not_exists('program', 'biaya_bulanan',
    '`biaya_bulanan` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `durasi_bulan`');

CALL add_column_if_not_exists('program', 'is_active',
    '`is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `biaya_bulanan`');

CALL add_column_if_not_exists('program', 'created_at',
    '`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `is_active`');

CALL add_column_if_not_exists('program', 'updated_at',
    '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');

CALL add_column_if_not_exists('program', 'deleted_at',
    '`deleted_at` DATETIME NULL AFTER `updated_at`');

-- ROLLBACK:
-- ALTER TABLE program DROP COLUMN deskripsi, DROP COLUMN durasi_bulan,
-- DROP COLUMN biaya_bulanan, DROP COLUMN is_active, DROP COLUMN created_at,
-- DROP COLUMN updated_at, DROP COLUMN deleted_at;

-- ============================================================
-- TABLE: jadwal — tambah audit & created_by
-- ============================================================

CALL add_column_if_not_exists('jadwal', 'created_by',
    '`created_by` INT(11) NULL AFTER `lokasi`');

CALL add_column_if_not_exists('jadwal', 'created_at',
    '`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `created_by`');

CALL add_column_if_not_exists('jadwal', 'updated_at',
    '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');

-- ROLLBACK:
-- ALTER TABLE jadwal DROP COLUMN created_by, DROP COLUMN created_at, DROP COLUMN updated_at;

-- ============================================================
-- TABLE: membership_siswa — tambah audit & created_by
-- ============================================================

CALL add_column_if_not_exists('membership_siswa', 'created_by',
    '`created_by` INT(11) NULL AFTER `status_membership`');

CALL add_column_if_not_exists('membership_siswa', 'created_at',
    '`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `created_by`');

CALL add_column_if_not_exists('membership_siswa', 'updated_at',
    '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');

-- ROLLBACK:
-- ALTER TABLE membership_siswa DROP COLUMN created_by, DROP COLUMN created_at, DROP COLUMN updated_at;

-- ============================================================
-- TABLE: absensi_tutor — tambah keterangan & audit
-- ============================================================

CALL add_column_if_not_exists('absensi_tutor', 'keterangan',
    '`keterangan` VARCHAR(255) NULL AFTER `status_kehadiran`');

CALL add_column_if_not_exists('absensi_tutor', 'created_at',
    '`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `keterangan`');

CALL add_column_if_not_exists('absensi_tutor', 'updated_at',
    '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');

-- ROLLBACK:
-- ALTER TABLE absensi_tutor DROP COLUMN keterangan, DROP COLUMN created_at, DROP COLUMN updated_at;

-- ============================================================
-- TABLE: penilaian_akademik — tambah kolom analitik
-- ============================================================

CALL add_column_if_not_exists('penilaian_akademik', 'passing_grade_status',
    '`passing_grade_status` ENUM(\'Lulus\',\'Tidak Lulus\',\'Belum Dinilai\') NOT NULL DEFAULT \'Belum Dinilai\' AFTER `total_skor`');

CALL add_column_if_not_exists('penilaian_akademik', 'sumber',
    '`sumber` ENUM(\'Manual\',\'CAT\') NOT NULL DEFAULT \'Manual\' AFTER `passing_grade_status`');

CALL add_column_if_not_exists('penilaian_akademik', 'sumber_referensi',
    '`sumber_referensi` VARCHAR(100) NULL AFTER `sumber`');

CALL add_column_if_not_exists('penilaian_akademik', 'created_by',
    '`created_by` INT(11) NULL AFTER `sumber_referensi`');

CALL add_column_if_not_exists('penilaian_akademik', 'updated_at',
    '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_by`');

CALL add_column_if_not_exists('penilaian_akademik', 'created_at',
    '`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `updated_at`');

-- Set passing_grade_status untuk data existing berdasarkan nilai SKD 2024
-- TWK>=65, TIU>=80, TKP>=166 (ambang batas SKD CPNS — sesuaikan jika berbeda untuk Polri/TNI)
UPDATE `penilaian_akademik`
SET `passing_grade_status` = CASE
    WHEN `kategori_tes` = 'SKD'
         AND `nilai_twk` >= 65
         AND `nilai_tiu` >= 80
         AND `nilai_tkp` >= 166
    THEN 'Lulus'
    WHEN `kategori_tes` = 'SKD'
    THEN 'Tidak Lulus'
    ELSE 'Belum Dinilai'
END
WHERE `passing_grade_status` = 'Belum Dinilai';

-- Tambah enum 'Tryout' ke kategori_tes jika belum ada
-- (MariaDB: MODIFY COLUMN aman jika nilai existing sudah ada di enum baru)
ALTER TABLE `penilaian_akademik`
MODIFY COLUMN `kategori_tes`
    ENUM('SKD','Psikotes','Tryout') NOT NULL DEFAULT 'SKD';

-- ROLLBACK:
-- ALTER TABLE penilaian_akademik DROP COLUMN passing_grade_status, DROP COLUMN sumber,
-- DROP COLUMN sumber_referensi, DROP COLUMN created_by, DROP COLUMN updated_at;
-- ALTER TABLE penilaian_akademik MODIFY COLUMN kategori_tes ENUM('SKD','Psikotes') NOT NULL;

-- ============================================================
-- TABLE: penilaian_binjas — tambah kolom skoring lengkap
-- ============================================================

CALL add_column_if_not_exists('penilaian_binjas', 'target_kategori',
    '`target_kategori` ENUM(\'Polri\',\'TNI AD\',\'TNI AL\',\'TNI AU\',\'Akpol\',\'Akmil\',\'Umum\') NOT NULL DEFAULT \'Umum\' AFTER `tanggal_tes`');

CALL add_column_if_not_exists('penilaian_binjas', 'lari_waktu_detik',
    '`lari_waktu_detik` DECIMAL(6,2) NULL AFTER `lari_jarak_meter`');

CALL add_column_if_not_exists('penilaian_binjas', 'skor_lari',
    '`skor_lari` TINYINT UNSIGNED NULL AFTER `lari_waktu_detik`');

CALL add_column_if_not_exists('penilaian_binjas', 'skor_pullup',
    '`skor_pullup` TINYINT UNSIGNED NULL AFTER `pullup_repetisi`');

CALL add_column_if_not_exists('penilaian_binjas', 'skor_pushup',
    '`skor_pushup` TINYINT UNSIGNED NULL AFTER `pushup_repetisi`');

CALL add_column_if_not_exists('penilaian_binjas', 'skor_situp',
    '`skor_situp` TINYINT UNSIGNED NULL AFTER `situp_repetisi`');

CALL add_column_if_not_exists('penilaian_binjas', 'skor_shuttlerun',
    '`skor_shuttlerun` TINYINT UNSIGNED NULL AFTER `shuttlerun_detik`');

CALL add_column_if_not_exists('penilaian_binjas', 'skor_renang',
    '`skor_renang` TINYINT UNSIGNED NULL AFTER `renang_detik`');

CALL add_column_if_not_exists('penilaian_binjas', 'total_skor',
    '`total_skor` SMALLINT UNSIGNED NULL AFTER `skor_renang`');

CALL add_column_if_not_exists('penilaian_binjas', 'predikat',
    '`predikat` ENUM(\'Sangat Baik\',\'Baik\',\'Cukup\',\'Kurang\',\'Sangat Kurang\') NULL AFTER `total_skor`');

CALL add_column_if_not_exists('penilaian_binjas', 'created_by',
    '`created_by` INT(11) NULL AFTER `predikat`');

CALL add_column_if_not_exists('penilaian_binjas', 'created_at',
    '`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `created_by`');

CALL add_column_if_not_exists('penilaian_binjas', 'updated_at',
    '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`');

-- ROLLBACK:
-- ALTER TABLE penilaian_binjas
-- DROP COLUMN target_kategori, DROP COLUMN lari_waktu_detik, DROP COLUMN skor_lari,
-- DROP COLUMN skor_pullup, DROP COLUMN skor_pushup, DROP COLUMN skor_situp,
-- DROP COLUMN skor_shuttlerun, DROP COLUMN skor_renang, DROP COLUMN total_skor,
-- DROP COLUMN predikat, DROP COLUMN created_by, DROP COLUMN created_at, DROP COLUMN updated_at;

-- ============================================================
-- TABLE: transaksi_keuangan — tambah field baru (unified kategori, bukti, metode)
-- Kolom lama kategori_pemasukan & kategori_pengeluaran DIPERTAHANKAN dulu
-- Migration data ke kolom `kategori` unified ada di 007_unify_transaksi.sql
-- ============================================================

CALL add_column_if_not_exists('transaksi_keuangan', 'metode_pembayaran',
    '`metode_pembayaran` ENUM(\'Tunai\',\'Transfer\',\'QRIS\',\'Lainnya\') NOT NULL DEFAULT \'Tunai\' AFTER `keterangan_transaksi`');

CALL add_column_if_not_exists('transaksi_keuangan', 'bukti_path',
    '`bukti_path` VARCHAR(255) NULL AFTER `metode_pembayaran`');

CALL add_column_if_not_exists('transaksi_keuangan', 'pembayaran_id',
    '`pembayaran_id` INT(11) NULL AFTER `tutor_id`');

CALL add_column_if_not_exists('transaksi_keuangan', 'created_by',
    '`created_by` INT(11) NULL AFTER `pembayaran_id`');

CALL add_column_if_not_exists('transaksi_keuangan', 'updated_at',
    '`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `tanggal_transaksi`');

-- ROLLBACK:
-- ALTER TABLE transaksi_keuangan DROP COLUMN metode_pembayaran, DROP COLUMN bukti_path,
-- DROP COLUMN pembayaran_id, DROP COLUMN created_by, DROP COLUMN updated_at;

-- ============================================================
-- Cleanup: hapus stored procedure temporary
-- ============================================================
DROP PROCEDURE IF EXISTS `add_column_if_not_exists`;

SET FOREIGN_KEY_CHECKS = 1;

SELECT '[003] Migration selesai: Semua kolom audit & field baru berhasil ditambahkan.' AS final_status;

-- ============================================================
-- END OF 003_add_audit_columns.sql
-- Next: Jalankan 004_add_indexes.sql
-- ============================================================

-- Migration 013: Tambah kolom deleted_at ke tabel jadwal (soft delete)
-- Jalankan sekali. Aman dijalankan ulang (IF NOT EXISTS check).

ALTER TABLE `jadwal`
    ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL DEFAULT NULL AFTER `updated_at`;

-- Index untuk performa query WHERE deleted_at IS NULL
CREATE INDEX IF NOT EXISTS `idx_jadwal_deleted`
    ON `jadwal` (`deleted_at`, `tanggal`);

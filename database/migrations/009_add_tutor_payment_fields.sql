-- ============================================================
-- 009_add_tutor_payment_fields.sql
-- Phase 2 Completion — Tambah kolom penggajian di tabel tutor
-- Jalankan SETELAH 008_nilai_mapel_psikologi.sql
-- Safe to run multiple times (IF NOT EXISTS check via procedure)
-- ============================================================

-- Tambah kolom tarif_per_sesi
ALTER TABLE `tutor`
    ADD COLUMN IF NOT EXISTS `tarif_per_sesi`   INT UNSIGNED    NULL     COMMENT 'Tarif per sesi mengajar (Rupiah)',
    ADD COLUMN IF NOT EXISTS `bank_nama`        VARCHAR(50)     NULL     COMMENT 'Nama bank (BRI, BCA, Mandiri, dst)',
    ADD COLUMN IF NOT EXISTS `bank_rekening`    VARCHAR(30)     NULL     COMMENT 'Nomor rekening bank',
    ADD COLUMN IF NOT EXISTS `bank_atas_nama`   VARCHAR(100)    NULL     COMMENT 'Nama pemilik rekening';

-- Verifikasi
SELECT 'Migration 009 applied: tutor payment fields added.' AS status;

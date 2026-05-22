-- Migration 014: Tambah data ortu/wali ke calon_siswa (pendaftaran online)
-- Agar informasi dari form pendaftaran publik bisa diteruskan ke data siswa.
-- Aman dijalankan ulang.

ALTER TABLE `calon_siswa`
    ADD COLUMN IF NOT EXISTS `nama_ortu`     VARCHAR(150) NULL AFTER `nomor_wa`,
    ADD COLUMN IF NOT EXISTS `nomor_wa_ortu` VARCHAR(20)  NULL AFTER `nama_ortu`;

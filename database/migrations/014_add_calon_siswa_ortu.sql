-- 014_add_calon_siswa_ortu.sql
-- Tambah kolom data orang tua/wali ke calon_siswa (form pendaftaran online)

ALTER TABLE `calon_siswa`
    ADD COLUMN `nama_ortu`     VARCHAR(150) NULL AFTER `asal_sekolah`,
    ADD COLUMN `nomor_wa_ortu` VARCHAR(20)  NULL AFTER `nama_ortu`;

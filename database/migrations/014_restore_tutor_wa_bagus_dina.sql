-- 014_restore_tutor_wa_bagus_dina.sql
-- Kembalikan nomor WhatsApp Coach Bagus & Miss Dina (sesuai dump produksi)

UPDATE `tutor` SET `nomor_wa` = '08465341213'
    WHERE `id` = 8  AND `nama_lengkap` = 'COACH BAGUS';

UPDATE `tutor` SET `nomor_wa` = '08781802744'
    WHERE `id` = 18 AND `nama_lengkap` = 'MISS DINA';

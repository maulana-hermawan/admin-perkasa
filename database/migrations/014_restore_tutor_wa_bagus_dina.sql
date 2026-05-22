-- 014_restore_tutor_wa_bagus_dina.sql
-- Kembalikan nomor WhatsApp Coach Bagus & Miss Dina (format 0xxxx, konsisten dgn baris lain)

UPDATE `tutor` SET `nomor_wa` = '087777538280'
    WHERE `id` = 8  AND `nama_lengkap` = 'COACH BAGUS';

UPDATE `tutor` SET `nomor_wa` = '081235647133'
    WHERE `id` = 18 AND `nama_lengkap` = 'MISS DINA';

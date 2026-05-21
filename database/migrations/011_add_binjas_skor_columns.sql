-- ============================================================
-- Migration 011: Tambah kolom skor per item ke penilaian_binjas
-- Menyimpan skor konversi per item, nilai samapta A/B, nilai AB, nilai gabungan
-- Dijalankan: 2026-05 (Manual via phpMyAdmin)
-- ============================================================

ALTER TABLE `penilaian_binjas`
  -- Skor konversi per item (hasil lookup tabel scoring)
  ADD COLUMN `skor_lari`        INT          NULL     COMMENT 'Skor konversi lari 12 menit'           AFTER `shuttlerun_detik`,
  ADD COLUMN `skor_pullup`      INT          NULL     COMMENT 'Skor konversi pull-up / chinning'       AFTER `skor_lari`,
  ADD COLUMN `skor_situp`       INT          NULL     COMMENT 'Skor konversi sit-up'                   AFTER `skor_pullup`,
  ADD COLUMN `skor_pushup`      INT          NULL     COMMENT 'Skor konversi push-up'                  AFTER `skor_situp`,
  ADD COLUMN `skor_shuttle`     INT          NULL     COMMENT 'Skor konversi shuttle run'              AFTER `skor_pushup`,
  ADD COLUMN `skor_lunges`      INT          NULL     COMMENT 'Skor konversi lunges (opsional)'        AFTER `skor_shuttle`,
  ADD COLUMN `skor_renang`      INT          NULL     COMMENT 'Skor konversi renang (opsional)'        AFTER `skor_lunges`,

  -- Nilai kelompok
  ADD COLUMN `nilai_samapta_a`  DECIMAL(6,2) NULL     COMMENT 'Samapta A = skor_lari'                 AFTER `skor_renang`,
  ADD COLUMN `nilai_samapta_b`  DECIMAL(6,2) NULL     COMMENT 'Samapta B = rata-rata skor B items'    AFTER `nilai_samapta_a`,
  ADD COLUMN `nilai_ab`         DECIMAL(6,2) NULL     COMMENT 'Nilai gabungan AB = (A + B) / 2'       AFTER `nilai_samapta_b`,
  ADD COLUMN `nilai_gabungan`   DECIMAL(6,2) NULL     COMMENT 'Nilai akhir = AB*0.8 + Renang*0.2 (jika ada renang), NULL jika tidak ada renang' AFTER `nilai_ab`;

-- Untuk data lama: isi nilai_ab dari skor_akhir (skor_akhir sudah = round(nilai_ab))
UPDATE `penilaian_binjas`
  SET `nilai_ab` = `skor_akhir`
  WHERE `nilai_ab` IS NULL AND `skor_akhir` IS NOT NULL;

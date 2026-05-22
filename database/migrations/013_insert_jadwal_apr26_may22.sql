-- ============================================================
-- Migration 013: Insert jadwal + jadwal_tutor dari WhatsApp
-- Periode: 26 April - 22 Mei 2026
-- Sumber: Grup "KELAS INTENSIF" & "BP angkatan 3"
--
-- program_id mapping:
--   Akademik Intensif=1, Akademik Reguler=2,
--   Jasmani Intensif=3, Jasmani Reguler=4
--
-- Tutor ID mapping:
--   1=Marjendi, 2=Ida Bagus, 3=Mada, 4=Azalia, 5=Lia,
--   6=Hatta, 7=Fuad, 8=Bagus, 10=Maulana, 11=Bayu,
--   12=Fajar, 13=Kasril, 14=Alan, 15=Yunira,
--   16=Bu Ajeng, 17=Bu Alisti, 18=Miss Dina
-- ============================================================

-- 26 April 2026 (Minggu) - Renang [Intensif] - coach tidak disebutkan di chat
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Renang', 'Kicking dada & bebas 25M x6, Arm Stroke dada & bebas 25x6, Breathing', '2026-04-26', '07:00:00', '09:00:00', 'Kolam Renang Milla Kencana (GOR Pajajaran)');

-- 27 April 2026 (Senin) - Matematika [Intensif] - Bu Ajeng
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (1, 'Matematika', 'Matematika - Pembahasan materi lanjutan & pengambilan nilai', '2026-04-27', '13:00:00', '15:00:00', 'Kelas Madrasah Lantai 2, Masjid Baiturrohman');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 16);

-- 27 April 2026 (Senin) - Jasmani [Intensif] - Coach Ida Bagus
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani', '2026-04-27', '16:00:00', '18:00:00', 'Lapangan Mako Resimen I Paspelopor');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 2);

-- 28 April 2026 (Selasa) - Jasmani [Intensif] - Coach Ida Bagus
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani', '2026-04-28', '16:00:00', '18:00:00', 'Lapangan Makosat, Resimen I Paspelopor');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 2);

-- 29 April 2026 (Rabu) - English [Intensif] - Miss Dina
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (1, 'English', 'English', '2026-04-29', '13:00:00', '15:00:00', 'Kelas Basecamp Perkasa, Pasirlaja');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 18);

-- 29 April 2026 (Rabu) - Jasmani [Intensif] - Coach Hatta
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani', '2026-04-29', '16:00:00', '18:00:00', 'Basecamp Perkasa, Pasirlaja');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 6);

-- 29 April 2026 (Rabu) - Jasmani [BP Angkatan 3] - Coach Lia
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (4, 'Jasmani (BP Angkatan 3)', 'Jasmani', '2026-04-29', '16:00:00', '18:00:00', 'Lapangan Mako Resimen I Paspelopor');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 5);

-- 30 April 2026 (Kamis) - Bahasa Indonesia [Intensif] - Bu Alisti
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (1, 'Bahasa Indonesia', 'Memahami kesalahan penulisan EYD, Menganalisis kesalahan kata', '2026-04-30', '13:00:00', '15:00:00', 'Kelas Madrasah Lantai 2, Masjid Baiturrahman');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 17);

-- 30 April 2026 (Kamis) - Jasmani [Intensif] - Coach Kasril (DIBATALKAN hujan)
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani (DIBATALKAN - hujan)', '2026-04-30', '16:00:00', '18:00:00', 'Lapangan Makosat, Resimen I');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 13);

-- 30 April 2026 (Kamis) - Jasmani [BP Angkatan 3] - Coach Marjendi (DIBATALKAN hujan)
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (4, 'Jasmani (BP Angkatan 3)', 'Jasmani (DIBATALKAN - hujan)', '2026-04-30', '16:00:00', '18:00:00', 'Lapangan Mako Resimen I Paspelopor');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 1);

-- 1 Mei 2026 (Sabtu) - Jasmani [Intensif] - Coach Bagus & Coach Hatta
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani', '2026-05-01', '07:00:00', '09:00:00', 'Lapangan Makosat, Resimen I');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 8), (@jid, 6);

-- 1 Mei 2026 (Sabtu) - WK [Intensif] - Coach Bayu
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (1, 'WK', 'Wawasan Kebangsaan', '2026-05-01', '13:00:00', '15:00:00', 'Kelas Basecamp Perkasa, Pasirlaja');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 11);

-- 1 Mei 2026 (Sabtu) - Jasmani [BP Angkatan 3] - Coach Bagus & Coach Marjendi
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (4, 'Jasmani (BP Angkatan 3)', 'Jasmani', '2026-05-01', '07:00:00', '09:00:00', 'Lapangan Mako Resimen I Paspelopor');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 8), (@jid, 1);

-- 1 Mei 2026 (Sabtu) - Matematika [BP Angkatan 3] - Bu Ajeng
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (2, 'Matematika (BP Angkatan 3)', 'Matematika', '2026-05-01', '10:00:00', '12:00:00', 'Kelas Basecamp Perkasa, Pasirlaja');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 16);

-- 3 Mei 2026 (Minggu) - Renang [Intensif] - Coach Azalia & Coach Mada
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Renang', 'Renang', '2026-05-03', '07:00:00', '09:00:00', 'Kolam Renang Mila, Gor Padjajaran');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 4), (@jid, 3);

-- 3 Mei 2026 (Minggu) - Psikologi [Intensif] - Coach Yunira
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (1, 'Psikologi', 'Psikologi', '2026-05-03', '13:00:00', '15:00:00', 'Kelas Basecamp Perkasa, Pasirlaja');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 15);

-- 3 Mei 2026 (Minggu) - Renang [BP Angkatan 3] - Coach Azalia & Coach Mada
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (4, 'Renang (BP Angkatan 3)', 'Renang', '2026-05-03', '07:00:00', '09:00:00', 'Kolam Renang Mila, Gor Padjajaran');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 4), (@jid, 3);

-- 3 Mei 2026 (Minggu) - Bahasa Indonesia [BP Angkatan 3] - Bu Alisti
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (2, 'Bahasa Indonesia (BP Angkatan 3)', 'Bahasa Indonesia', '2026-05-03', '11:00:00', '13:00:00', 'Kelas Basecamp Perkasa, Pasirlaja');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 17);

-- 4 Mei 2026 (Senin) - Matematika [Intensif] - Bu Ajeng
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (1, 'Matematika', 'Matematika', '2026-05-04', '13:00:00', '15:00:00', 'Kelas Madrasah Lantai 2, Masjid Baiturrohman');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 16);

-- 4 Mei 2026 (Senin) - Jasmani [Intensif] - Coach Ida Bagus
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani', '2026-05-04', '16:00:00', '18:00:00', 'Lapangan Mako Resimen I Paspelopor');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 2);

-- 5 Mei 2026 (Selasa) - Jasmani [Intensif] - Coach Ida Bagus
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani', '2026-05-05', '16:00:00', '18:00:00', 'Lapangan Mako Resimen I Paspelopor');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 2);

-- 6 Mei 2026 (Rabu) - English [Intensif] - Miss Dina
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (1, 'English', 'English', '2026-05-06', '13:00:00', '15:00:00', 'Kelas Basecamp Perkasa, Pasirlaja');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 18);

-- 6 Mei 2026 (Rabu) - Jasmani [Intensif] - Coach Bagus (Samapta B)
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani - Samapta B', '2026-05-06', '16:00:00', '18:00:00', 'Basecamp Perkasa');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 8);

-- 6 Mei 2026 (Rabu) - Jasmani [BP Angkatan 3] - Coach Alan & Coach Lia
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (4, 'Jasmani (BP Angkatan 3)', 'Jasmani', '2026-05-06', '15:30:00', '17:30:00', 'Lapangan Mako Resimen I Paspelopor');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 14), (@jid, 5);

-- 7 Mei 2026 (Kamis) - Bahasa Indonesia [Intensif] - Bu Alisti (Ralat lokasi: Madrasah)
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (1, 'Bahasa Indonesia', 'Memahami & menganalisis kesalahan penulisan EYD', '2026-05-07', '13:00:00', '15:00:00', 'Kelas Madrasah Lantai 2, Masjid Baiturrahman');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 17);

-- 7 Mei 2026 (Kamis) - Jasmani [Intensif] - Coach Marjendi (Ralat lokasi: Makosat)
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani', '2026-05-07', '16:00:00', '18:00:00', 'Lapangan Makosat, Resimen I');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 1);

-- 7 Mei 2026 (Kamis) - Jasmani [BP Angkatan 3] - Coach Marjendi
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (4, 'Jasmani (BP Angkatan 3)', 'Jasmani', '2026-05-07', '16:00:00', '18:00:00', 'Lapangan Mako Resimen I Paspelopor');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 1);

-- 9 Mei 2026 (Sabtu) - Jasmani [Intensif] - Coach Ida Bagus
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani', '2026-05-09', '07:00:00', '09:00:00', 'Lapangan Mako Resimen I');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 2);

-- 9 Mei 2026 (Sabtu) - PU/WK [Intensif] - Coach Maulana
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (1, 'PU/WK', 'Pengetahuan Umum / Wawasan Kebangsaan', '2026-05-09', '13:00:00', '15:00:00', 'Kelas Basecamp Perkasa, Pasirlaja');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 10);

-- 9 Mei 2026 (Sabtu) - Jasmani [BP Angkatan 3] - Coach Bagus
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (4, 'Jasmani (BP Angkatan 3)', 'Jasmani', '2026-05-09', '07:00:00', '09:00:00', 'Lapangan Mako Resimen I');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 8);

-- 9 Mei 2026 (Sabtu) - Bahasa Inggris [BP Angkatan 3] - Miss Dina
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (2, 'Bahasa Inggris (BP Angkatan 3)', 'Bahasa Inggris', '2026-05-09', '11:00:00', '13:00:00', 'Kelas Basecamp Perkasa, Pasirlaja');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 18);

-- 10 Mei 2026 (Minggu) - Renang [Intensif] - Coach Azalia & Coach Mada
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Renang', 'Renang', '2026-05-10', '07:00:00', '09:00:00', 'Kolam Renang Mila, Gor Padjajaran');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 4), (@jid, 3);

-- 10 Mei 2026 (Minggu) - Psikologi [Intensif] - Coach Yunira
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (1, 'Psikologi', 'Psikologi', '2026-05-10', '13:00:00', '15:00:00', 'Kelas Basecamp Perkasa, Pasirlaja');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 15);

-- 10 Mei 2026 (Minggu) - Renang [BP Angkatan 3] - Coach Azalia & Coach Mada
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (4, 'Renang (BP Angkatan 3)', 'Renang', '2026-05-10', '07:00:00', '09:00:00', 'Kolam Renang Mila, Gor Padjajaran');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 4), (@jid, 3);

-- 10 Mei 2026 (Minggu) - Bahasa Indonesia [BP Angkatan 3] - Bu Alisti
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (2, 'Bahasa Indonesia (BP Angkatan 3)', 'Bahasa Indonesia', '2026-05-10', '11:00:00', '13:00:00', 'Kelas Basecamp Perkasa, Pasirlaja');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 17);

-- 11 Mei 2026 (Senin) - Jasmani [Intensif] - Coach Ida Bagus
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani', '2026-05-11', '16:00:00', '18:00:00', 'Lapangan Mako Resimen I Paspelopor');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 2);

-- 12 Mei 2026 (Selasa) - Jasmani [Intensif] - Coach Ida Bagus
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani', '2026-05-12', '16:00:00', '18:00:00', 'Lapangan Mako Resimen I Paspelopor');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 2);

-- 13 Mei 2026 (Rabu) - English [Intensif] - Miss Dina
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (1, 'English', 'English', '2026-05-13', '13:00:00', '15:00:00', 'Kelas Basecamp Perkasa, Pasirlaja');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 18);

-- 13 Mei 2026 (Rabu) - Jasmani [Intensif] - Coach Lia
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani', '2026-05-13', '16:00:00', '18:00:00', 'BC Perkasa');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 5);

-- 13 Mei 2026 (Rabu) - Jasmani [BP Angkatan 3] - Coach Bagus
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (4, 'Jasmani (BP Angkatan 3)', 'Jasmani', '2026-05-13', '16:00:00', '18:00:00', 'Lapangan Mako Resimen I Paspelopor');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 8);

-- 14 Mei 2026 (Kamis) - Bahasa Indonesia [Intensif] - Bu Alisti
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (1, 'Bahasa Indonesia', 'Memahami & menganalisis soal penalaran umum (ponens, tollens, silogisme)', '2026-05-14', '13:00:00', '15:00:00', 'Kelas Madrasah Lantai 2, Masjid Baiturrahman');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 17);

-- 14 Mei 2026 (Kamis) - Jasmani [Intensif] - Coach Fuad & Coach Bagus
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani', '2026-05-14', '16:00:00', '18:00:00', 'Lapangan Makosat, Resimen I');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 7), (@jid, 8);

-- 14 Mei 2026 (Kamis) - Jasmani [BP Angkatan 3] - Coach Fuad & Coach Bagus
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (4, 'Jasmani (BP Angkatan 3)', 'Jasmani', '2026-05-14', '16:00:00', '18:00:00', 'Lapangan Makosat, Resimen I');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 7), (@jid, 8);

-- 15 Mei 2026 (Jumat) - Jasmani [BP Angkatan 3] - Coach Fuad & Coach Bagus
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (4, 'Jasmani (BP Angkatan 3)', 'Jasmani', '2026-05-15', '15:30:00', '17:30:00', 'Lapangan Makosat, Resimen I');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 7), (@jid, 8);

-- 16 Mei 2026 (Sabtu) - Jasmani [Intensif] - Coach Fuad & Coach Hatta
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani', '2026-05-16', '06:00:00', '08:00:00', 'Lapangan Makosat, Resimen I');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 7), (@jid, 6);

-- 16 Mei 2026 (Sabtu) - TKP [Intensif] - Coach Maulana
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (1, 'TKP', 'TKP - Jejaring Kerja', '2026-05-16', '13:00:00', '15:00:00', 'Kelas Basecamp Perkasa, Pasirlaja');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 10);

-- 16 Mei 2026 (Sabtu) - Jasmani [BP Angkatan 3] - Coach Fuad & Coach Hatta
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (4, 'Jasmani (BP Angkatan 3)', 'Jasmani', '2026-05-16', '06:00:00', '08:00:00', 'Lapangan Makosat, Resimen I');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 7), (@jid, 6);

-- 16 Mei 2026 (Sabtu) - Psikologi [BP Angkatan 3] - Coach Bayu
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (2, 'Psikologi (BP Angkatan 3)', 'Psikologi', '2026-05-16', '11:00:00', '13:00:00', 'Kelas Basecamp Perkasa, Pasirlaja');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 11);

-- 17 Mei 2026 (Minggu) - Renang [Intensif] - Coach Azalia & Coach Mada
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Renang', 'Kicking bebas 15m x4 set, bebas normal 15x4, gaya dada 50x4', '2026-05-17', '07:00:00', '09:00:00', 'Kolam Renang Milla Kencana (GOR Pajajaran)');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 4), (@jid, 3);

-- 17 Mei 2026 (Minggu) - Psikologi [Intensif] - Coach Yunira
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (1, 'Psikologi', 'Psikologi', '2026-05-17', '13:00:00', '15:00:00', 'Kelas Basecamp Perkasa, Pasirlaja');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 15);

-- 17 Mei 2026 (Minggu) - Renang [BP Angkatan 3] - Coach Azalia & Coach Mada
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (4, 'Renang (BP Angkatan 3)', 'Renang', '2026-05-17', '07:00:00', '09:00:00', 'Kolam Renang Mila, Gor Padjajaran');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 4), (@jid, 3);

-- 17 Mei 2026 (Minggu) - TKP [BP Angkatan 3] - Coach Maulana
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (2, 'TKP (BP Angkatan 3)', 'TKP', '2026-05-17', '11:00:00', '13:00:00', 'Kelas Basecamp Perkasa, Pasirlaja');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 10);

-- 18 Mei 2026 (Senin) - Matematika [Intensif] - Bu Ajeng
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (1, 'Matematika', 'Kaidah Pencacahan (aturan penjumlahan, perkalian, permutasi, kombinasi)', '2026-05-18', '13:00:00', '15:00:00', 'Kelas Madrasah Lantai 2, Masjid Baiturrohman');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 16);

-- 18 Mei 2026 (Senin) - Jasmani [Intensif] - Coach Ida Bagus
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani', '2026-05-18', '16:00:00', '18:00:00', 'Lapangan Mako Resimen I Paspelopor');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 2);

-- 19 Mei 2026 (Selasa) - Jasmani [Intensif] - Coach Ida Bagus
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani', '2026-05-19', '16:00:00', '18:00:00', 'Lapangan Mako Resimen I Paspelopor');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 2);

-- 20 Mei 2026 (Rabu) - Jasmani pagi [Intensif] - Coach Fajar
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Lari ketahanan 30 menit, Samapta B dan penguatan', '2026-05-20', '06:00:00', '08:00:00', 'Basecamp Perkasa, Pasirlaja');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 12);

-- 20 Mei 2026 (Rabu) - English [Intensif] - Miss Dina
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (1, 'English', 'English', '2026-05-20', '13:00:00', '15:00:00', 'Kelas Madrasah Lantai 2, Masjid Baiturrahman');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 18);

-- 20 Mei 2026 (Rabu) - Jasmani sore [Intensif] - Coach Lia
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani', '2026-05-20', '16:00:00', '18:00:00', 'Lapangan Makosat, Resimen I');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 5);

-- 20 Mei 2026 (Rabu) - Jasmani [BP Angkatan 3] - Coach Lia
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (4, 'Jasmani (BP Angkatan 3)', 'Jasmani', '2026-05-20', '16:00:00', '18:00:00', 'Lapangan Makosat, Resimen I');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 5);

-- 21 Mei 2026 (Kamis) - Jasmani pagi [Intensif] - Coach Fajar
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani', '2026-05-21', '06:00:00', '08:00:00', 'Basecamp Perkasa, Pasirlaja');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 12);

-- 21 Mei 2026 (Kamis) - Bahasa Indonesia [Intensif] - Bu Alisti
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (1, 'Bahasa Indonesia', 'Memahami & menganalisis soal penalaran umum (ponens, tollens, silogisme)', '2026-05-21', '13:00:00', '15:00:00', 'Kelas Madrasah Lantai 2, Masjid Baiturrahman');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 17);

-- 21 Mei 2026 (Kamis) - Jasmani sore [Intensif] - Coach Kasril
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani', '2026-05-21', '16:00:00', '18:00:00', 'Lapangan Makosat, Resimen I');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 13);

-- 21 Mei 2026 (Kamis) - Jasmani [BP Angkatan 3] - Coach Kasril
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (4, 'Jasmani (BP Angkatan 3)', 'Jasmani', '2026-05-21', '16:00:00', '18:00:00', 'Lapangan Makosat, Resimen I');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 13);

-- 22 Mei 2026 (Sabtu) - Jasmani [Intensif] - Coach Kasril & Coach Hatta
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (3, 'Jasmani', 'Jasmani', '2026-05-22', '07:00:00', '09:00:00', 'Lapangan Makosat, Resimen I');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 13), (@jid, 6);

-- 22 Mei 2026 (Sabtu) - Jasmani [BP Angkatan 3] - Coach Kasril & Coach Hatta
INSERT INTO jadwal (program_id, nama_kegiatan, materi, tanggal, waktu_mulai, waktu_selesai, lokasi)
VALUES (4, 'Jasmani (BP Angkatan 3)', 'Jasmani', '2026-05-22', '07:00:00', '09:00:00', 'Lapangan Makosat, Resimen I');
SET @jid = LAST_INSERT_ID();
INSERT INTO jadwal_tutor (jadwal_id, tutor_id) VALUES (@jid, 13), (@jid, 6);

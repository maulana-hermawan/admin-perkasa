<?php
/**
 * app/modules/laporan/controller.php
 * Modul Laporan — export CSV/Excel + printable HTML
 * (Shared hosting compatible — no Composer required)
 */

// ── Rapor PDF (standalone printable page) ───────────────────
if ($action === 'rapor') {
    $siswa_id  = get_int('id');
    if (!$siswa_id) { flash('warning', 'ID siswa tidak valid.'); redirect('index.php?page=siswa'); }

    $tgl_dari   = get('tgl_dari')   ?: null;
    $tgl_sampai = get('tgl_sampai') ?: null;
    if ($tgl_dari   && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_dari))   $tgl_dari   = null;
    if ($tgl_sampai && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_sampai)) $tgl_sampai = null;
    if ($tgl_dari && $tgl_sampai && $tgl_dari > $tgl_sampai) [$tgl_dari, $tgl_sampai] = [$tgl_sampai, $tgl_dari];

    $where_tgl  = '';
    $bind_types = 'i';
    $bind_vals  = [$siswa_id];
    if ($tgl_dari && $tgl_sampai) {
        $where_tgl  = ' AND DATE(tanggal_tes) BETWEEN ? AND ?';
        $bind_types = 'iss';
        $bind_vals  = [$siswa_id, $tgl_dari, $tgl_sampai];
    }

    $siswa = db_fetch(
        "SELECT s.*, m.tanggal_mulai_aktif, m.tanggal_selesai_aktif, m.status_membership,
                p.nama_program, p.biaya_bulanan
         FROM siswa s
         LEFT JOIN (SELECT siswa_id, MAX(id) mid FROM membership_siswa GROUP BY siswa_id) mm ON s.id=mm.siswa_id
         LEFT JOIN membership_siswa m ON mm.mid=m.id
         LEFT JOIN program p ON m.program_id=p.id
         WHERE s.id=? AND s.deleted_at IS NULL",
        "i", [$siswa_id]
    );
    if (!$siswa) { flash('warning', 'Siswa tidak ditemukan.'); redirect('index.php?page=siswa'); }

    $nilai_binjas    = db_fetch_all("SELECT * FROM penilaian_binjas WHERE siswa_id=?{$where_tgl} ORDER BY tanggal_tes DESC", $bind_types, $bind_vals);
    $nilai_mapel     = db_fetch_all("SELECT * FROM penilaian_mapel WHERE siswa_id=?{$where_tgl} ORDER BY tanggal_tes DESC", $bind_types, $bind_vals);
    $nilai_akademik  = db_fetch_all("SELECT * FROM penilaian_akademik WHERE siswa_id=?{$where_tgl} ORDER BY tanggal_tes DESC", $bind_types, $bind_vals);
    $nilai_psikologi = db_fetch_all("SELECT * FROM penilaian_psikologi WHERE siswa_id=?{$where_tgl} ORDER BY tanggal_tes DESC", $bind_types, $bind_vals);

    require __DIR__ . '/rapor.view.php';
    exit();
}

// ── EXPORT handlers (download langsung) ─────────────────────
if (in_array($action, ['export_siswa','export_transaksi','export_gaji','export_nilai'])) {
    // Auth sudah dicek di index.php (check_auth). CSRF tidak diperlukan untuk GET download.
    _laporan_export($action);
    exit();
}

function _laporan_export(string $type): void
{
    $bulan = get('bulan') ?: date('Y-m');
    [$fy, $fm] = explode('-', $bulan . '-01');

    switch ($type) {

        case 'export_siswa':
            $rows = db_fetch_all(
                "SELECT s.nomor_induk,s.nama_lengkap,u.email,s.nomor_wa,s.nama_ortu,
                        s.tanggal_lahir,s.alamat,s.status_siswa,s.target_seleksi,
                        s.asal_sekolah,s.jenis_kelamin,s.created_at
                 FROM siswa s LEFT JOIN users u ON s.user_id=u.id
                 WHERE s.deleted_at IS NULL ORDER BY s.nama_lengkap"
            );
            _output_csv('data_siswa_' . date('Ymd'), [
                'Nomor Induk','Nama Lengkap','Email','WhatsApp','Nama Orang Tua',
                'Tanggal Lahir','Alamat','Status','Target Seleksi','Asal Sekolah',
                'Jenis Kelamin','Tanggal Daftar'
            ], array_map(fn($r) => array_values($r), $rows));
            break;

        case 'export_transaksi':
            $rows = db_fetch_all(
                "SELECT DATE_FORMAT(tanggal_transaksi,'%d/%m/%Y %H:%i') AS tgl,
                        jenis_arus,kategori,keterangan_transaksi,
                        FORMAT(nominal,0) AS nominal,
                        CASE WHEN jenis_arus='Pemasukan' THEN FORMAT(nominal,0) ELSE '' END AS masuk,
                        CASE WHEN jenis_arus='Pengeluaran' THEN FORMAT(nominal,0) ELSE '' END AS keluar
                 FROM transaksi_keuangan
                 WHERE deleted_at IS NULL
                 AND (? = '' OR DATE_FORMAT(tanggal_transaksi,'%Y-%m') = ?)
                 ORDER BY tanggal_transaksi DESC",
                "ss", [$bulan, $bulan]
            );
            _output_csv('transaksi_keuangan_' . $bulan, [
                'Tanggal','Jenis','Kategori','Keterangan','Nominal','Pemasukan','Pengeluaran'
            ], array_map(fn($r) => array_values($r), $rows));
            break;

        case 'export_gaji':
            $rows = db_fetch_all(
                "SELECT t.nama_lengkap, t.spesialisasi,
                        COUNT(DISTINCT jt.jadwal_id) AS jumlah_sesi,
                        COALESCE(t.tarif_per_sesi,0) AS tarif,
                        COUNT(DISTINCT jt.jadwal_id) * COALESCE(t.tarif_per_sesi,0) AS total_gaji,
                        t.bank_nama, t.bank_rekening, t.bank_atas_nama
                 FROM tutor t
                 LEFT JOIN jadwal_tutor jt ON jt.tutor_id=t.id
                 LEFT JOIN jadwal j ON jt.jadwal_id=j.id
                           AND YEAR(j.tanggal)=? AND MONTH(j.tanggal)=?
                           AND j.tanggal<=CURDATE()
                 WHERE t.deleted_at IS NULL AND t.status_aktif=1
                 GROUP BY t.id
                 ORDER BY total_gaji DESC",
                "ii", [(int)$fy, (int)$fm]
            );
            _output_csv('rekap_gaji_' . $bulan, [
                'Nama Tutor','Spesialisasi','Jumlah Sesi','Tarif/Sesi','Total Gaji',
                'Bank','No. Rekening','Atas Nama'
            ], array_map(fn($r) => array_values($r), $rows));
            break;

        case 'export_nilai':
            $rows = db_fetch_all(
                "SELECT s.nomor_induk, s.nama_lengkap,
                        MAX(CASE WHEN pb.id IS NOT NULL THEN pb.skor_akhir END) AS skor_jasmani_terakhir,
                        MAX(CASE WHEN pb.id IS NOT NULL THEN pb.predikat END) AS predikat_jasmani,
                        MAX(pa.total_skor) AS skor_skd_tertinggi,
                        MAX(pm.rata_psikologi) AS rata_psikologi
                 FROM siswa s
                 LEFT JOIN penilaian_binjas pb ON pb.siswa_id=s.id
                 LEFT JOIN penilaian_akademik pa ON pa.siswa_id=s.id AND pa.kategori_tes='SKD'
                 LEFT JOIN penilaian_psikologi pm ON pm.siswa_id=s.id
                 WHERE s.deleted_at IS NULL
                 GROUP BY s.id
                 ORDER BY s.nama_lengkap"
            );
            _output_csv('rekap_nilai_siswa_' . date('Ymd'), [
                'Nomor Induk','Nama','Skor Jasmani Terakhir','Predikat Jasmani',
                'Skor SKD Tertinggi','Rata Psikologi'
            ], array_map(fn($r) => array_values($r), $rows));
            break;
    }
}

function _output_csv(string $filename, array $headers, array $rows): void
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Cache-Control: no-cache, no-store');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel
    fputcsv($out, $headers);
    foreach ($rows as $row) fputcsv($out, $row);
    fclose($out);
}

// ── Default: tampilkan halaman laporan ───────────────────────
return [
    'title'       => 'Laporan & Ekspor',
    'view'        => __DIR__ . '/view.php',
    'breadcrumbs' => [],
    'bulan'       => get('bulan', date('Y-m')),
];

<?php
/**
 * app/modules/tutor/controller.php
 * Modul Tutor — CRUD lengkap + Rekap Gaji + Rekap Tutor
 */

// ── Rekap Tutor (filter: tutor, program, tanggal, jenis kegiatan) ──
if ($action === 'rekap_tutor') {

    // Filter params
    $f_tutor    = get_int('f_tutor',   0);
    $f_program  = get_int('f_program', 0);
    $f_kegiatan = get('f_kegiatan',    '');
    $f_dari     = get('f_dari',  date('Y-m-01'));
    $f_sampai   = get('f_sampai', date('Y-m-t'));

    // Validasi tanggal
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_dari))   $f_dari   = date('Y-m-01');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_sampai)) $f_sampai = date('Y-m-t');
    if ($f_dari > $f_sampai) [$f_dari, $f_sampai] = [$f_sampai, $f_dari];

    // Export CSV
    if ($action === 'export_rekap_tutor') {
        // handled below
    }

    // Build WHERE
    $where  = "WHERE j.tanggal BETWEEN ? AND ? AND t.deleted_at IS NULL";
    $types  = "ss";
    $params = [$f_dari, $f_sampai];

    if ($f_tutor > 0)    { $where .= " AND t.id = ?";              $types .= "i"; $params[] = $f_tutor;    }
    if ($f_program > 0)  { $where .= " AND j.program_id = ?";      $types .= "i"; $params[] = $f_program;  }
    if ($f_kegiatan)     { $where .= " AND j.nama_kegiatan = ?";   $types .= "s"; $params[] = $f_kegiatan; }

    // Detail semua sesi
    $detail_sesi = db_fetch_all(
        "SELECT
            j.id          AS jadwal_id,
            j.tanggal,
            j.nama_kegiatan,
            j.materi,
            j.waktu_mulai,
            j.waktu_selesai,
            j.lokasi,
            p.nama_program,
            t.id          AS tutor_id,
            t.nama_lengkap AS nama_tutor,
            t.spesialisasi,
            t.tarif_per_sesi
         FROM jadwal j
         LEFT JOIN program p     ON j.program_id  = p.id
         JOIN  jadwal_tutor jt   ON jt.jadwal_id  = j.id
         JOIN  tutor t           ON jt.tutor_id   = t.id
         $where
         ORDER BY j.tanggal DESC, j.waktu_mulai ASC, t.nama_lengkap ASC",
        $types, $params
    );

    // Rekap per tutor
    $rekap_per_tutor = db_fetch_all(
        "SELECT
            t.id, t.nama_lengkap, t.spesialisasi, t.tarif_per_sesi,
            t.bank_nama, t.bank_rekening, t.bank_atas_nama, t.nomor_wa,
            COUNT(DISTINCT jt.jadwal_id)                                AS jumlah_sesi,
            GROUP_CONCAT(DISTINCT j.nama_kegiatan ORDER BY j.nama_kegiatan SEPARATOR ', ') AS jenis_kegiatan,
            GROUP_CONCAT(DISTINCT COALESCE(p.nama_program,'—') ORDER BY p.nama_program SEPARATOR ', ') AS program_list,
            COUNT(DISTINCT j.nama_kegiatan)                             AS variasi_kegiatan
         FROM tutor t
         JOIN jadwal_tutor jt ON jt.tutor_id  = t.id
         JOIN jadwal j        ON jt.jadwal_id = j.id
         LEFT JOIN program p  ON j.program_id = p.id
         $where
         GROUP BY t.id
         ORDER BY jumlah_sesi DESC, t.nama_lengkap ASC",
        $types, $params
    );

    // Summary stats
    $total_sesi     = count(array_unique(array_column($detail_sesi, 'jadwal_id')));
    $total_tutor    = count($rekap_per_tutor);
    $total_honor    = array_reduce($rekap_per_tutor, function ($c, $r) {
        return $c + (int)$r['jumlah_sesi'] * (float)($r['tarif_per_sesi'] ?? 0);
    }, 0.0);

    // Breakdown per jenis kegiatan
    $breakdown_kegiatan = db_fetch_all(
        "SELECT j.nama_kegiatan,
                COUNT(DISTINCT j.id) AS jumlah_sesi,
                COUNT(DISTINCT jt.tutor_id) AS jumlah_tutor
         FROM jadwal j
         JOIN jadwal_tutor jt ON jt.jadwal_id = j.id
         JOIN tutor t         ON jt.tutor_id  = t.id
         $where
         GROUP BY j.nama_kegiatan
         ORDER BY jumlah_sesi DESC",
        $types, $params
    );

    // Dropdown data untuk filter
    $daftar_tutor_filter   = db_fetch_all("SELECT id, nama_lengkap FROM tutor WHERE status_aktif=1 AND deleted_at IS NULL ORDER BY nama_lengkap");
    $daftar_program_filter = db_fetch_all("SELECT id, nama_program FROM program WHERE is_active=1 ORDER BY nama_program");
    $daftar_kegiatan       = ['Jasmani', 'Renang', 'Akademik', 'Psikologi', 'Tryout'];

    return [
        'title'                => 'Rekap Tutor',
        'view'                 => __DIR__ . '/rekap_tutor.view.php',
        'breadcrumbs'          => [['label'=>'Tutor','url'=>'index.php?page=tutor'],['label'=>'Rekap Tutor']],
        'detail_sesi'          => $detail_sesi,
        'rekap_per_tutor'      => $rekap_per_tutor,
        'breakdown_kegiatan'   => $breakdown_kegiatan,
        'total_sesi'           => $total_sesi,
        'total_tutor'          => $total_tutor,
        'total_honor'          => $total_honor,
        'daftar_tutor_filter'  => $daftar_tutor_filter,
        'daftar_program_filter'=> $daftar_program_filter,
        'daftar_kegiatan'      => $daftar_kegiatan,
        'f_tutor'              => $f_tutor,
        'f_program'            => $f_program,
        'f_kegiatan'           => $f_kegiatan,
        'f_dari'               => $f_dari,
        'f_sampai'             => $f_sampai,
    ];
}

// ── Export CSV Rekap Tutor ────────────────────────────────────
if ($action === 'export_rekap_tutor') {
    $f_tutor    = get_int('f_tutor',   0);
    $f_program  = get_int('f_program', 0);
    $f_kegiatan = get('f_kegiatan',    '');
    $f_dari     = get('f_dari',  date('Y-m-01'));
    $f_sampai   = get('f_sampai', date('Y-m-t'));
    if ($f_dari > $f_sampai) [$f_dari, $f_sampai] = [$f_sampai, $f_dari];

    $where  = "WHERE j.tanggal BETWEEN ? AND ? AND t.deleted_at IS NULL";
    $types  = "ss";
    $params = [$f_dari, $f_sampai];
    if ($f_tutor > 0)   { $where .= " AND t.id = ?";            $types .= "i"; $params[] = $f_tutor;    }
    if ($f_program > 0) { $where .= " AND j.program_id = ?";    $types .= "i"; $params[] = $f_program;  }
    if ($f_kegiatan)    { $where .= " AND j.nama_kegiatan = ?"; $types .= "s"; $params[] = $f_kegiatan; }

    $rows = db_fetch_all(
        "SELECT
            j.tanggal,
            j.nama_kegiatan,
            COALESCE(p.nama_program,'—') AS program,
            COALESCE(j.materi,'—')       AS materi,
            CONCAT(SUBSTR(j.waktu_mulai,1,5),' – ',SUBSTR(j.waktu_selesai,1,5)) AS waktu,
            j.lokasi,
            t.nama_lengkap               AS tutor,
            COALESCE(t.spesialisasi,'—') AS spesialisasi,
            COALESCE(t.tarif_per_sesi,0) AS tarif
         FROM jadwal j
         LEFT JOIN program p     ON j.program_id  = p.id
         JOIN  jadwal_tutor jt   ON jt.jadwal_id  = j.id
         JOIN  tutor t           ON jt.tutor_id   = t.id
         $where
         ORDER BY j.tanggal DESC, j.waktu_mulai, t.nama_lengkap",
        $types, $params
    );

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="rekap_tutor_' . $f_dari . '_sd_' . $f_sampai . '.csv"');
    header('Cache-Control: no-cache');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
    fputcsv($out, ['Tanggal','Kegiatan','Program','Materi','Waktu','Lokasi','Nama Tutor','Spesialisasi','Tarif/Sesi']);
    foreach ($rows as $r) fputcsv($out, array_values($r));
    fclose($out);
    exit;
}

// ── Rekap Gaji (GET) ─────────────────────────────────────────
if ($action === 'rekap_gaji') {
    $gaji_bulan = get('bulan') ?: date('Y-m');
    [$gy, $gm]  = explode('-', $gaji_bulan . '-01');

    $rekap_gaji = db_fetch_all(
        "SELECT t.*,
                COUNT(DISTINCT jt.jadwal_id) AS jumlah_sesi
         FROM tutor t
         LEFT JOIN jadwal_tutor jt ON jt.tutor_id=t.id
         LEFT JOIN jadwal j ON jt.jadwal_id=j.id
                              AND YEAR(j.tanggal)=? AND MONTH(j.tanggal)=?
                              AND j.tanggal <= CURDATE()
         WHERE t.deleted_at IS NULL AND t.status_aktif=1
         GROUP BY t.id
         HAVING jumlah_sesi > 0
         ORDER BY jumlah_sesi DESC",
        "ii", [(int)$gy, (int)$gm]
    );

    $total_gaji = array_reduce($rekap_gaji, function($c, $r) {
        return $c + (float)$r['jumlah_sesi'] * (float)($r['tarif_per_sesi'] ?? 0);
    }, 0.0);

    return [
        'title'       => 'Rekap Gaji — ' . date('F Y', strtotime($gaji_bulan . '-01')),
        'view'        => __DIR__ . '/rekap_gaji.view.php',
        'breadcrumbs' => [['label'=>'Tutor','url'=>'index.php?page=tutor'],['label'=>'Rekap Gaji']],
        'rekap_gaji'  => $rekap_gaji,
        'total_gaji'  => $total_gaji,
        'gaji_bulan'  => $gaji_bulan,
    ];
}

// ── Bayar Gaji (GET — aksi cepat) ───────────────────────────
if ($action === 'bayar_gaji') {
    csrf_check_get();  // validasi token di GET param
    $tutor_id   = get_int('tutor_id');
    $gaji_bulan = get('bulan') ?: date('Y-m');
    [$gy, $gm]  = explode('-', $gaji_bulan . '-01');

    $tutor = db_fetch("SELECT * FROM tutor WHERE id=? AND deleted_at IS NULL","i",[$tutor_id]);
    if (!$tutor) { set_flash('danger','Tutor tidak ditemukan.'); redirect('index.php?page=tutor&action=rekap_gaji&bulan='.$gaji_bulan); }

    $jumlah_sesi = (int)db_value(
        "SELECT COUNT(DISTINCT jt.jadwal_id) FROM jadwal_tutor jt JOIN jadwal j ON jt.jadwal_id=j.id WHERE jt.tutor_id=? AND YEAR(j.tanggal)=? AND MONTH(j.tanggal)=? AND j.tanggal<=CURDATE()",
        "iii", [$tutor_id,(int)$gy,(int)$gm]
    );
    $total = $jumlah_sesi * (float)($tutor['tarif_per_sesi'] ?? 0);

    if ($total > 0) {
        db_execute(
            "INSERT INTO transaksi_keuangan (jenis_arus,kategori,nominal,keterangan_transaksi,tanggal_transaksi,created_by)
             VALUES ('Pengeluaran','Gaji Tutor',?,?,NOW(),?)",
            "dsi",
            [$total,
             'Gaji ' . e($tutor['nama_lengkap']) . ' — ' . date('F Y', strtotime($gaji_bulan . '-01')) . " ($jumlah_sesi sesi)",
             auth_id()]
        );
        // WA notif
        if ($tutor['nomor_wa']) {
            send_wa($tutor['nomor_wa'], "Halo *{$tutor['nama_lengkap']}*, gaji Anda bulan *" . date('F Y', strtotime($gaji_bulan.'-01')) . "* sebesar *" . format_rupiah($total) . "* telah diproses. Terima kasih! 🙏 _Perkasa Mulia TC_");
        }
        log_action('BAYAR_GAJI','tutor',$tutor_id,null,['bulan'=>$gaji_bulan,'total'=>$total,'sesi'=>$jumlah_sesi]);
        set_flash('success', 'Gaji ' . e($tutor['nama_lengkap']) . ' (' . format_rupiah($total) . ') dicatat ke Buku Kas.');
    } else {
        set_flash('warning', 'Tidak ada gaji yang bisa dihitung (tarif/sesi = 0 atau tidak ada sesi).');
    }
    redirect('index.php?page=tutor&action=rekap_gaji&bulan=' . $gaji_bulan);
}

// ── Delete (GET — aksi cepat via pkConfirm) ─────────────────
if ($action === 'delete') {
    csrf_check_get();  // validasi token di GET param
    $id = get_int('id');
    $tutor = db_fetch("SELECT * FROM tutor WHERE id=? AND deleted_at IS NULL", "i", [$id]);
    if ($tutor) {
        db_execute("UPDATE tutor SET deleted_at=NOW() WHERE id=?", "i", [$id]);
        db_execute("UPDATE users SET deleted_at=NOW(), is_active=0 WHERE id=?", "i", [$tutor['user_id']]);
        log_action('DELETE', 'tutor', $id, $tutor, null);
        set_flash('success', 'Tutor berhasil dihapus.');
    } else {
        set_flash('danger', 'Tutor tidak ditemukan.');
    }
    redirect('index.php?page=tutor');
}

// ── Handle POST actions ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if ($action === 'store') {
        // Validasi
        $nama       = trim(post('nama_lengkap', ''));
        $email      = trim(post('email', ''));
        $nomor_wa   = preg_replace('/\D/', '', post('nomor_wa', ''));
        $spesialis  = trim(post('spesialisasi', ''));
        $tarif      = (int) str_replace(['Rp', '.', ' ', ','], '', post('tarif_per_sesi', '0'));
        $bank_nama  = trim(post('bank_nama', ''));
        $bank_rek   = trim(post('bank_rekening', ''));
        $bank_atas  = trim(post('bank_atas_nama', ''));
        $status     = post('status_aktif', '1') === '1' ? 1 : 0;
        $password   = trim(post('password', ''));

        $errs = [];
        if (!$nama)  $errs[] = 'Nama lengkap wajib diisi.';
        if (!$email) $errs[] = 'Email wajib diisi.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errs[] = 'Format email tidak valid.';
        if (!$password || strlen($password) < 6) $errs[] = 'Password minimal 6 karakter.';

        if (!empty($errs)) {
            set_flash('danger', implode(' ', $errs));
            redirect('index.php?page=tutor&action=create');
        }

        // Cek email duplikat
        $exist = db_value("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL", "s", [$email]);
        if ($exist) {
            set_flash('danger', 'Email sudah terdaftar di sistem.');
            redirect('index.php?page=tutor&action=create');
        }

        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $conn = db_conn();
            $conn->begin_transaction();

            // Insert ke tabel users
            db_execute(
                "INSERT INTO users (email, password, role, nama_display, is_active) VALUES (?,?,?,?,?)",
                "ssssi", [$email, $hash, 'tutor', $nama, $status]
            );
            $user_id = db_conn()->insert_id;

            // Insert ke tabel tutor
            db_execute(
                "INSERT INTO tutor (user_id, nama_lengkap, nomor_wa, spesialisasi, tarif_per_sesi, bank_nama, bank_rekening, bank_atas_nama, status_aktif)
                 VALUES (?,?,?,?,?,?,?,?,?)",
                "isssdsssi",
                [$user_id, $nama, $nomor_wa ?: null, $spesialis ?: null,
                 $tarif ?: 0, $bank_nama ?: null, $bank_rek ?: null, $bank_atas ?: null, $status]
            );
            $tutor_id = db_conn()->insert_id;

            $conn->commit();
            log_action('CREATE', 'tutor', $tutor_id, null, ['nama_lengkap' => $nama]);
            set_flash('success', "Tutor <strong>" . e($nama) . "</strong> berhasil ditambahkan.");
        } catch (Throwable $ex) {
            db_conn()->rollback();
            set_flash('danger', 'Gagal menyimpan: ' . e($ex->getMessage()));
        }
        redirect('index.php?page=tutor');
    }

    if ($action === 'update') {
        $id        = get_int('id');
        $nama      = trim(post('nama_lengkap', ''));
        $nomor_wa  = preg_replace('/\D/', '', post('nomor_wa', ''));
        $spesialis = trim(post('spesialisasi', ''));
        $tarif     = (int) str_replace(['Rp', '.', ' ', ','], '', post('tarif_per_sesi', '0'));
        $bank_nama = trim(post('bank_nama', ''));
        $bank_rek  = trim(post('bank_rekening', ''));
        $bank_atas = trim(post('bank_atas_nama', ''));
        $status    = post('status_aktif', '1') === '1' ? 1 : 0;
        $password  = trim(post('password', ''));

        if (!$id || !$nama) {
            set_flash('danger', 'Data tidak valid.');
            redirect("index.php?page=tutor&action=edit&id=$id");
        }

        $tutor_old = db_fetch("SELECT * FROM tutor WHERE id=? AND deleted_at IS NULL", "i", [$id]);
        if (!$tutor_old) {
            set_flash('danger', 'Tutor tidak ditemukan.');
            redirect('index.php?page=tutor');
        }

        try {
            db_execute(
                "UPDATE tutor SET nama_lengkap=?, nomor_wa=?, spesialisasi=?, tarif_per_sesi=?,
                 bank_nama=?, bank_rekening=?, bank_atas_nama=?, status_aktif=? WHERE id=?",
                "ssssisssi",
                [$nama, $nomor_wa ?: null, $spesialis ?: null, $tarif ?: null,
                 $bank_nama ?: null, $bank_rek ?: null, $bank_atas ?: null, $status, $id]
            );

            // Update nama di users juga
            db_execute(
                "UPDATE users SET nama_display=?, is_active=? WHERE id=?",
                "sii", [$nama, $status, $tutor_old['user_id']]
            );

            // Ganti password jika diisi
            if ($password && strlen($password) >= 6) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                db_execute("UPDATE users SET password=? WHERE id=?", "si", [$hash, $tutor_old['user_id']]);
            }

            log_action('UPDATE', 'tutor', $id, $tutor_old, ['nama_lengkap' => $nama]);
            set_flash('success', "Data tutor <strong>" . e($nama) . "</strong> berhasil diperbarui.");
        } catch (Throwable $ex) {
            set_flash('danger', 'Gagal update: ' . e($ex->getMessage()));
        }
        redirect('index.php?page=tutor');
    }

}

// ── GET: List ────────────────────────────────────────────────
$q_search    = get('q', '');
$f_status    = get('status', '');

$sql  = "SELECT t.*, u.email FROM tutor t LEFT JOIN users u ON t.user_id = u.id WHERE t.deleted_at IS NULL";
$types = '';
$params = [];

if ($q_search) {
    $sql   .= " AND (t.nama_lengkap LIKE ? OR t.spesialisasi LIKE ? OR u.email LIKE ?)";
    $types .= 'sss';
    $like   = '%' . $q_search . '%';
    $params = array_merge($params, [$like, $like, $like]);
}
if ($f_status !== '') {
    $sql   .= " AND t.status_aktif = ?";
    $types .= 'i';
    $params[] = (int)$f_status;
}
$sql .= " ORDER BY t.status_aktif DESC, t.nama_lengkap ASC";

$daftar_tutor = $types
    ? db_fetch_all($sql, $types, $params)
    : db_fetch_all($sql);

// Hitung statistik
$stat_aktif = 0;
$stat_non   = 0;
foreach ($daftar_tutor as $t) {
    if ($t['status_aktif']) $stat_aktif++; else $stat_non++;
}

// ── GET: Create / Edit ───────────────────────────────────────
if ($action === 'create') {
    return [
        'title'      => 'Tambah Tutor',
        'view'       => __DIR__ . '/form.view.php',
        'breadcrumbs'=> [['label'=>'Tutor','url'=>'index.php?page=tutor'],['label'=>'Tambah']],
        'tutor_edit' => null,
    ];
}

if ($action === 'edit') {
    $id = get_int('id');
    $tutor_edit = db_fetch(
        "SELECT t.*, u.email FROM tutor t LEFT JOIN users u ON t.user_id=u.id WHERE t.id=? AND t.deleted_at IS NULL",
        "i", [$id]
    );
    if (!$tutor_edit) {
        set_flash('danger', 'Tutor tidak ditemukan.');
        redirect('index.php?page=tutor');
    }
    return [
        'title'       => 'Edit Tutor — ' . e($tutor_edit['nama_lengkap']),
        'view'        => __DIR__ . '/form.view.php',
        'breadcrumbs' => [['label'=>'Tutor','url'=>'index.php?page=tutor'],['label'=>'Edit']],
        'tutor_edit'  => $tutor_edit,
    ];
}

// Default: list
return [
    'title'        => 'Manajemen Tutor',
    'view'         => __DIR__ . '/list.view.php',
    'breadcrumbs'  => [],
    'daftar_tutor' => $daftar_tutor,
    'stat_aktif'   => $stat_aktif,
    'stat_non'     => $stat_non,
    'q_search'     => $q_search,
    'f_status'     => $f_status,
];

<?php
/**
 * app/modules/pembayaran/controller.php
 * Pembayaran SPP — multi-program, jumlah bulan, auto-extend membership, auto-kas
 */
declare(strict_types=1);
check_auth();

// ── Stat header ───────────────────────────────────────────────
$stat = db_fetch(
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status_bayar='Lunas'       THEN 1 ELSE 0 END) AS lunas,
        SUM(CASE WHEN status_bayar='Belum Bayar' THEN 1 ELSE 0 END) AS belum,
        SUM(CASE WHEN status_bayar='Cicilan'     THEN 1 ELSE 0 END) AS cicilan,
        COALESCE(SUM(nominal_bayar),0)   AS total_masuk,
        COALESCE(SUM(nominal_tagihan),0) AS total_tagihan
     FROM pembayaran_siswa WHERE deleted_at IS NULL"
) ?? [];

// ── AJAX: get_memberships ─────────────────────────────────────
if ($action === 'get_memberships') {
    header('Content-Type: application/json; charset=utf-8');
    $siswa_id = get_int('siswa_id');

    // Schema-safe: columns added in migration-012
    static $_gm_tipe  = null;
    static $_gm_biaya = null;
    if ($_gm_tipe  === null) $_gm_tipe  = (bool)db_value("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='program' AND COLUMN_NAME='tipe_program'");
    if ($_gm_biaya === null) $_gm_biaya = (bool)db_value("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='membership_siswa' AND COLUMN_NAME='biaya_per_bulan'");

    $tipe_sel  = $_gm_tipe  ? "p.tipe_program"                                            : "'Program' AS tipe_program";
    $biaya_sel = $_gm_biaya ? "COALESCE(m.biaya_per_bulan, p.biaya_bulanan) AS biaya_per_bulan" : "p.biaya_bulanan AS biaya_per_bulan";
    $order_by  = $_gm_tipe  ? "p.tipe_program, p.nama_program"                            : "p.nama_program";

    $memberships = db_fetch_all(
        "SELECT m.id, m.program_id, m.tanggal_mulai_aktif, m.tanggal_selesai_aktif, m.status_membership,
                {$biaya_sel},
                p.biaya_bulanan AS biaya_default, p.nama_program, {$tipe_sel}
         FROM membership_siswa m
         JOIN program p ON m.program_id = p.id
         WHERE m.siswa_id=? AND m.status_membership IN ('Aktif','Berjalan')
         ORDER BY {$order_by}",
        "i", [$siswa_id]
    );
    echo json_encode(['ok'=>true,'memberships'=>$memberships]);
    exit;
}

// ── POST handlers ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    /* ── STORE ── */
    if ($action === 'store') {
        $siswa_id     = (int)post('siswa_id','0');
        $jumlah_bulan = max(1,(int)post('jumlah_bulan','1'));
        $bayar        = (float)str_replace(['.','Rp',' '],'',post('nominal_bayar','0'));
        $metode       = post('metode_pembayaran','Tunai');
        $ket          = trim(post('keterangan',''));
        $tgl_bayar    = post('tanggal_bayar') ?: date('Y-m-d H:i:s');
        $membership_ids = array_map('intval', $_POST['membership_ids'] ?? []);
        $biaya_list     = $_POST['biaya_per_bulan'] ?? [];

        if (!$siswa_id || empty($membership_ids)) {
            flash('danger','Pilih siswa dan minimal 1 program.');
            redirect('index.php?page=pembayaran&action=create');
        }

        $tagihan_total = 0;
        $detail_items  = [];
        $nama_programs = [];

        // Schema check once
        static $_store_biaya_col = null;
        if ($_store_biaya_col === null) {
            $_store_biaya_col = (bool)db_value(
                "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='membership_siswa' AND COLUMN_NAME='biaya_per_bulan'"
            );
        }
        $biaya_coalesce = $_store_biaya_col
            ? "COALESCE(m.biaya_per_bulan,p.biaya_bulanan) AS harga_acuan"
            : "p.biaya_bulanan AS harga_acuan";

        foreach ($membership_ids as $mid) {
            $m = db_fetch(
                "SELECT m.*, p.nama_program, p.biaya_bulanan,
                        {$biaya_coalesce}
                 FROM membership_siswa m JOIN program p ON m.program_id=p.id
                 WHERE m.id=? AND m.siswa_id=?",
                "ii", [$mid,$siswa_id]
            );
            if (!$m) continue;

            $harga = isset($biaya_list[$mid])
                ? (float)str_replace(['.','Rp',' '],'', $biaya_list[$mid])
                : (float)$m['harga_acuan'];

            $tagihan_total  += $harga * $jumlah_bulan;
            $detail_items[]  = [
                'membership_id'   => $mid,
                'program_id'      => (int)$m['program_id'],
                'nama_program'    => $m['nama_program'],
                'biaya_per_bulan' => $harga,
                'jumlah_bulan'    => $jumlah_bulan,
            ];
            $nama_programs[] = $m['nama_program'];

            if ($_store_biaya_col && abs($harga - (float)$m['harga_acuan']) > 0.01) {
                db_query("UPDATE membership_siswa SET biaya_per_bulan=? WHERE id=?","di",[$harga,$mid]);
            }
        }

        $status_bayar = match(true) {
            $bayar <= 0              => 'Belum Bayar',
            $bayar >= $tagihan_total => 'Lunas',
            default                  => 'Cicilan',
        };
        $ket_program = implode(' + ', $nama_programs)." ({$jumlah_bulan} bln)";

        db_begin();
        try {
            // Cek apakah kolom migration-012 sudah ada
            static $_pay_has_jumlah = null;
            if ($_pay_has_jumlah === null) {
                $_pay_has_jumlah = (bool)db_value(
                    "SELECT COUNT(*) FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pembayaran_siswa'
                     AND COLUMN_NAME='jumlah_bulan'"
                );
            }

            if ($_pay_has_jumlah) {
                $pay_id = db_insert(
                    "INSERT INTO pembayaran_siswa
                     (siswa_id,jumlah_bulan,keterangan_program,nominal_tagihan,nominal_bayar,
                      status_bayar,tanggal_bayar,metode_pembayaran,keterangan,created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?)",
                    "iisddssssi",
                    [$siswa_id,$jumlah_bulan,$ket_program,$tagihan_total,$bayar,
                     $status_bayar,$tgl_bayar,$metode,$ket,auth_id()]
                );
            } else {
                // Fallback: schema lama (tanpa jumlah_bulan, keterangan_program)
                $periode = date('Y-m-01');
                $pay_id = db_insert(
                    "INSERT INTO pembayaran_siswa
                     (siswa_id,membership_id,periode_bulan,nominal_tagihan,nominal_bayar,
                      status_bayar,tanggal_bayar,metode_pembayaran,keterangan,created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?)",
                    "iisddssss" . "i",
                    [$siswa_id,
                     $detail_items[0]['membership_id'] ?? null,
                     $periode, $tagihan_total, $bayar,
                     $status_bayar, $tgl_bayar, $metode,
                     $ket_program.($ket?' — '.$ket:''), auth_id()]
                );
            }

            // Cek tabel pembayaran_siswa_detail ada
            static $_detail_tbl = null;
            if ($_detail_tbl === null) {
                $_detail_tbl = (bool)db_value(
                    "SELECT COUNT(*) FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pembayaran_siswa_detail'"
                );
            }

            foreach ($detail_items as $d) {
                if ($_detail_tbl) {
                    db_insert(
                        "INSERT INTO pembayaran_siswa_detail
                         (pembayaran_id,membership_id,program_id,nama_program,biaya_per_bulan,jumlah_bulan)
                         VALUES (?,?,?,?,?,?)",
                        "iiisdi",
                        [$pay_id,$d['membership_id'],$d['program_id'],
                         $d['nama_program'],$d['biaya_per_bulan'],$d['jumlah_bulan']]
                    );
                }
                // Auto-extend 31 hari × jumlah_bulan
                $hari = 31 * $jumlah_bulan;
                db_query(
                    "UPDATE membership_siswa
                     SET tanggal_selesai_aktif=DATE_ADD(GREATEST(tanggal_selesai_aktif,CURDATE()),INTERVAL ? DAY),
                         status_membership='Berjalan'
                     WHERE id=?",
                    "ii", [$hari,$d['membership_id']]
                );
            }

            if ($bayar > 0) {
                $siswa = db_fetch("SELECT nama_lengkap FROM siswa WHERE id=?","i",[$siswa_id]);
                db_insert(
                    "INSERT INTO transaksi_keuangan
                     (jenis_arus,kategori,kategori_pemasukan,nominal,keterangan_transaksi,tanggal_transaksi,created_by)
                     VALUES ('Pemasukan','Bayar Program','Bayar Program',?,?,?,?)",
                    "dssi",
                    [$bayar,
                     'SPP '.($siswa['nama_lengkap']??'').' — '.$ket_program,
                     $tgl_bayar, auth_id()]
                );
            }

            db_commit();
            log_action('CREATE_PEMBAYARAN','pembayaran_siswa',$pay_id);
            flash('success',"Pembayaran dicatat. Tagihan: <strong>".format_rupiah($tagihan_total)."</strong>. Membership diperpanjang {$jumlah_bulan}×31 hari.");
        } catch (Throwable $ex) {
            db_rollback();
            flash('danger','Gagal: '.e($ex->getMessage()));
        }
        redirect('index.php?page=pembayaran');
    }

    /* ── UPDATE ── */
    if ($action === 'update') {
        $id      = get_int('id');
        $bayar   = (float)str_replace(['.','Rp',' '],'',post('nominal_bayar','0'));
        $metode  = post('metode_pembayaran','Tunai');
        $ket     = trim(post('keterangan',''));
        $tgl_bayar = post('tanggal_bayar') ?: null;
        $pay = db_fetch("SELECT * FROM pembayaran_siswa WHERE id=?","i",[$id]);
        if (!$pay) { flash('danger','Tidak ditemukan.'); redirect('index.php?page=pembayaran'); }

        $status_bayar = match(true) {
            $bayar <= 0                              => 'Belum Bayar',
            $bayar >= (float)$pay['nominal_tagihan'] => 'Lunas',
            default                                  => 'Cicilan',
        };
        $selisih = $bayar - (float)$pay['nominal_bayar'];

        db_query(
            "UPDATE pembayaran_siswa
             SET nominal_bayar=?,status_bayar=?,tanggal_bayar=?,metode_pembayaran=?,keterangan=?
             WHERE id=?",
            "dssssi", [$bayar,$status_bayar,$tgl_bayar,$metode,$ket,$id]
        );

        if ($selisih > 0.01) {
            $siswa = db_fetch("SELECT nama_lengkap FROM siswa WHERE id=?","i",[(int)$pay['siswa_id']]);
            db_insert(
                "INSERT INTO transaksi_keuangan
                 (jenis_arus,kategori,kategori_pemasukan,nominal,keterangan_transaksi,tanggal_transaksi,created_by)
                 VALUES ('Pemasukan','Bayar Program','Bayar Program',?,?,?,?)",
                "dssi",
                [$selisih,
                 'Pelunasan SPP '.($siswa['nama_lengkap']??'').' — '.($pay['keterangan_program']??''),
                 $tgl_bayar??date('Y-m-d H:i:s'), auth_id()]
            );
        }
        log_action('UPDATE_PEMBAYARAN','pembayaran_siswa',$id);
        flash('success','Pembayaran diperbarui.');
        redirect('index.php?page=pembayaran');
    }

    /* ── DELETE ── */
    if ($action === 'delete') {
        $id = get_int('id');
        db_query("UPDATE pembayaran_siswa SET deleted_at=NOW() WHERE id=?","i",[$id]);
        log_action('DELETE_PEMBAYARAN','pembayaran_siswa',$id);
        flash('success','Pembayaran dihapus.');
        redirect('index.php?page=pembayaran');
    }
}

// ── GET: CREATE ───────────────────────────────────────────────
if ($action === 'create') {
    $daftar_siswa = db_fetch_all(
        "SELECT id,nama_lengkap,nomor_induk FROM siswa
         WHERE deleted_at IS NULL AND status_siswa='Aktif' ORDER BY nama_lengkap"
    );
    return [
        'title'        => 'Catat Pembayaran SPP',
        'view'         => __DIR__.'/form.view.php',
        'breadcrumbs'  => [['label'=>'Pembayaran','url'=>'index.php?page=pembayaran'],['label'=>'Catat']],
        'daftar_siswa' => $daftar_siswa,
        'edit_data'    => null,
        'edit_detail'  => [],
        'stat'         => $stat,
    ];
}

// ── GET: EDIT ─────────────────────────────────────────────────
if ($action === 'edit') {
    $id   = get_int('id');
    $edit = db_fetch(
        "SELECT ps.*,s.nama_lengkap,s.nomor_induk FROM pembayaran_siswa ps
         JOIN siswa s ON ps.siswa_id=s.id WHERE ps.id=?","i",[$id]
    );
    if (!$edit) { flash('danger','Tidak ditemukan.'); redirect('index.php?page=pembayaran'); }
    $edit_detail = db_fetch_all("SELECT * FROM pembayaran_siswa_detail WHERE pembayaran_id=?","i",[$id]);
    return [
        'title'       => 'Edit Pembayaran',
        'view'        => __DIR__.'/form.view.php',
        'breadcrumbs' => [['label'=>'Pembayaran','url'=>'index.php?page=pembayaran'],['label'=>'Edit']],
        'daftar_siswa'=> [],
        'edit_data'   => $edit,
        'edit_detail' => $edit_detail,
        'stat'        => $stat,
    ];
}

// ── GET: KWITANSI CETAK ───────────────────────────────────────
if ($action === 'kwitansi') {
    $id  = get_int('id');
    $pay = db_fetch(
        "SELECT ps.*, s.nama_lengkap, s.nomor_induk, s.nomor_wa, s.alamat
         FROM pembayaran_siswa ps
         JOIN siswa s ON ps.siswa_id = s.id
         WHERE ps.id = ? AND ps.deleted_at IS NULL",
        "i", [$id]
    );
    if (!$pay) {
        set_flash('danger','Data pembayaran tidak ditemukan.');
        redirect('index.php?page=pembayaran');
    }

    // Line items (migration-012 table)
    $kwit_items = [];
    static $_kwit_tbl = null;
    if ($_kwit_tbl === null) {
        $_kwit_tbl = (bool)db_value(
            "SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pembayaran_siswa_detail'"
        );
    }
    if ($_kwit_tbl) {
        $kwit_items = db_fetch_all(
            "SELECT psd.*, p.nama_program AS prog_nama
             FROM pembayaran_siswa_detail psd
             LEFT JOIN program p ON psd.program_id = p.id
             WHERE psd.pembayaran_id = ?
             ORDER BY psd.id",
            "i", [$id]
        );
    }

    return [
        'title'       => 'Kwitansi #' . $id,
        'view'        => __DIR__ . '/kwitansi.view.php',
        'breadcrumbs' => [['label'=>'Pembayaran','url'=>'index.php?page=pembayaran'],['label'=>'Kwitansi']],
        'pay'         => $pay,
        'kwit_items'  => $kwit_items,
        'stat'        => $stat,
    ];
}

// ── GET: LIST ─────────────────────────────────────────────────
$f_siswa  = get('siswa_id');
$f_status = get('status');
$f_q      = get('q');

$sql  = "SELECT ps.*,s.nama_lengkap,s.nomor_induk,s.nomor_wa
         FROM pembayaran_siswa ps JOIN siswa s ON ps.siswa_id=s.id
         WHERE ps.deleted_at IS NULL";
$types=''; $params=[];
if ($f_siswa)  { $sql.=" AND ps.siswa_id=?";    $types.='i'; $params[]=(int)$f_siswa; }
if ($f_status) { $sql.=" AND ps.status_bayar=?"; $types.='s'; $params[]=$f_status; }
if ($f_q)      { $sql.=" AND s.nama_lengkap LIKE ?"; $types.='s'; $params[]='%'.$f_q.'%'; }
$sql .= " ORDER BY ps.created_at DESC LIMIT 200";
$daftar = $types ? db_fetch_all($sql,$types,$params) : db_fetch_all($sql);

$pay_ids = array_column($daftar,'id');
$detail_map = [];
if ($pay_ids) {
    $tbl_exists = (bool)db_value(
        "SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pembayaran_siswa_detail'"
    );
    if ($tbl_exists) {
        $ph = implode(',',array_fill(0,count($pay_ids),'?'));
        $details = db_fetch_all("SELECT * FROM pembayaran_siswa_detail WHERE pembayaran_id IN ($ph)",
                                 str_repeat('i',count($pay_ids)), $pay_ids);
        foreach ($details as $d) $detail_map[$d['pembayaran_id']][] = $d;
    }
}

return [
    'title'      => 'Pembayaran Siswa',
    'view'       => __DIR__.'/list.view.php',
    'breadcrumbs'=> [],
    'daftar'     => $daftar,
    'detail_map' => $detail_map,
    'stat'       => $stat,
    'f_siswa'    => $f_siswa,
    'f_status'   => $f_status,
    'f_q'        => $f_q,
];

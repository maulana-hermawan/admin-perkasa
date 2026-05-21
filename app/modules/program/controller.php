<?php
/**
 * app/modules/program/controller.php
 * Manajemen Program — CRUD program, fasilitas, paket
 */
declare(strict_types=1);
check_auth();

// ── POST handlers ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    /* ── STORE ── */
    if ($action === 'store') {
        $nama  = trim(post('nama_program',''));
        $tipe  = post('tipe_program','Program');
        $deskr = trim(post('deskripsi',''));
        $biaya = (float)str_replace(['.','Rp',' '],'', post('biaya_bulanan','0'));
        $aktif = (int)(post('is_active','1') === '1');

        if (!$nama) { flash('danger','Nama program wajib diisi.'); redirect('index.php?page=program&action=create'); }
        if (!in_array($tipe,['Program','Fasilitas','Paket'],true)) $tipe = 'Program';

        $has_tipe = (bool)db_value(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='program' AND COLUMN_NAME='tipe_program'"
        );

        if ($has_tipe) {
            $new_id = db_insert(
                "INSERT INTO program (nama_program,tipe_program,deskripsi,biaya_bulanan,is_active) VALUES (?,?,?,?,?)",
                "sssdi", [$nama,$tipe,$deskr,$biaya,$aktif]
            );
        } else {
            // Fallback: schema lama tanpa tipe_program
            $new_id = db_insert(
                "INSERT INTO program (nama_program,deskripsi,biaya_bulanan,is_active) VALUES (?,?,?,?)",
                "ssdi", [$nama,$deskr,$biaya,$aktif]
            );
        }

        // Simpan komponen paket
        $has_paket = (bool)db_value(
            "SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='paket_komponen'"
        );
        if ($tipe === 'Paket' && $has_paket) {
            foreach ($_POST['komponen'] ?? [] as $kid) {
                $kid = (int)$kid;
                if ($kid > 0) db_query("INSERT IGNORE INTO paket_komponen (paket_id,program_id) VALUES (?,?)","ii",[$new_id,$kid]);
            }
        }

        log_action('CREATE_PROGRAM','program',$new_id,null,['nama'=>$nama,'tipe'=>$tipe]);
        flash('success',"Program <strong>".e($nama)."</strong> berhasil ditambahkan.");
        redirect('index.php?page=program');
    }

    /* ── UPDATE ── */
    if ($action === 'update') {
        $id    = get_int('id');
        $nama  = trim(post('nama_program',''));
        $tipe  = post('tipe_program','Program');
        $deskr = trim(post('deskripsi',''));
        $biaya = (float)str_replace(['.','Rp',' '],'', post('biaya_bulanan','0'));
        $aktif = (int)(post('is_active','1') === '1');

        if (!$nama) { flash('danger','Nama program wajib diisi.'); redirect("index.php?page=program&action=edit&id=$id"); }
        if (!in_array($tipe,['Program','Fasilitas','Paket'],true)) $tipe = 'Program';

        $old = db_fetch("SELECT * FROM program WHERE id=?","i",[$id]);

        $_has_tipe_u = (bool)db_value("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='program' AND COLUMN_NAME='tipe_program'");
        if ($_has_tipe_u) {
            db_query("UPDATE program SET nama_program=?,tipe_program=?,deskripsi=?,biaya_bulanan=?,is_active=? WHERE id=?",
                     "sssdii",[$nama,$tipe,$deskr,$biaya,$aktif,$id]);
        } else {
            db_query("UPDATE program SET nama_program=?,deskripsi=?,biaya_bulanan=?,is_active=? WHERE id=?",
                     "ssdii",[$nama,$deskr,$biaya,$aktif,$id]);
        }

        // Update komponen paket
        $_has_paket_u = (bool)db_value("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='paket_komponen'");
        if ($tipe === 'Paket' && $_has_paket_u) {
            db_query("DELETE FROM paket_komponen WHERE paket_id=?","i",[$id]);
            foreach (($_POST['komponen'] ?? []) as $kid) {
                $kid = (int)$kid;
                if ($kid > 0) db_query("INSERT IGNORE INTO paket_komponen (paket_id,program_id) VALUES (?,?)",
                                        "ii",[$id,$kid]);
            }
        }

        log_action('UPDATE_PROGRAM','program',$id,$old,['nama'=>$nama,'tipe'=>$tipe]);
        flash('success',"Program berhasil diperbarui.");
        redirect('index.php?page=program');
    }

    /* ── DELETE ── */
    if ($action === 'delete') {
        $id = get_int('id');
        $old = db_fetch("SELECT * FROM program WHERE id=?","i",[$id]);
        db_query("UPDATE program SET is_active=0,deleted_at=NOW() WHERE id=?","i",[$id]);
        log_action('DELETE_PROGRAM','program',$id,$old,null);
        flash('success','Program dinonaktifkan.');
        redirect('index.php?page=program');
    }
}

// ── GET: LIST ────────────────────────────────────────────────
if ($action === 'index') {
    $tipe_filter = get('tipe','');

    // Cek kolom tipe_program (migration 012)
    $has_tipe = (bool)db_value(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='program'
         AND COLUMN_NAME='tipe_program'"
    );
    $has_paket = (bool)db_value(
        "SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='paket_komponen'"
    );

    $komponen_sel = $has_paket
        ? "(SELECT GROUP_CONCAT(pr.nama_program SEPARATOR ', ') FROM paket_komponen pk JOIN program pr ON pk.program_id=pr.id WHERE pk.paket_id=p.id) AS komponen_paket"
        : "NULL AS komponen_paket";

    $tipe_sel = $has_tipe ? "p.tipe_program" : "'Program' AS tipe_program";
    $order    = $has_tipe ? "ORDER BY FIELD(p.tipe_program,'Program','Fasilitas','Paket'), p.nama_program" : "ORDER BY p.nama_program";

    $sql = "SELECT p.*, $tipe_sel, $komponen_sel,
                   (SELECT COUNT(*) FROM membership_siswa m WHERE m.program_id=p.id AND m.status_membership IN ('Aktif','Berjalan')) AS jml_member
            FROM program p
            WHERE p.deleted_at IS NULL";
    $types=''; $params=[];
    if ($tipe_filter && $has_tipe) { $sql .= " AND p.tipe_program=?"; $types='s'; $params[]=$tipe_filter; }
    $sql .= " $order";

    $daftar = $types ? db_fetch_all($sql,$types,$params) : db_fetch_all($sql);

    return [
        'title'       => 'Manajemen Program',
        'view'        => __DIR__.'/list.view.php',
        'breadcrumbs' => [['label'=>'Program']],
        'daftar'      => $daftar,
        'tipe_filter' => $tipe_filter,
        'has_tipe'    => $has_tipe,
    ];
}

// ── GET: CREATE ──────────────────────────────────────────────
if ($action === 'create') {
    $_has_tipe_c = (bool)db_value("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='program' AND COLUMN_NAME='tipe_program'");
    $where_tipe  = $_has_tipe_c ? "AND tipe_program='Program'" : '';
    $daftar_program = db_fetch_all(
        "SELECT id,nama_program FROM program WHERE is_active=1 AND deleted_at IS NULL $where_tipe ORDER BY nama_program"
    );
    return [
        'title'          => 'Tambah Program',
        'view'           => __DIR__.'/form.view.php',
        'breadcrumbs'    => [['label'=>'Program','url'=>'index.php?page=program'],['label'=>'Tambah']],
        'item'           => null,
        'komponen_ids'   => [],
        'daftar_program' => $daftar_program,
    ];
}

// ── GET: EDIT ────────────────────────────────────────────────
if ($action === 'edit') {
    $id   = get_int('id');
    $item = db_fetch("SELECT * FROM program WHERE id=? AND deleted_at IS NULL","i",[$id]);
    if (!$item) { flash('warning','Program tidak ditemukan.'); redirect('index.php?page=program'); }

    // If tipe_program col doesn't exist, fill default
    if (!array_key_exists('tipe_program', $item)) $item['tipe_program'] = 'Program';

    $_has_tipe_e = (bool)db_value("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='program' AND COLUMN_NAME='tipe_program'");
    $where_tipe  = $_has_tipe_e ? "AND tipe_program='Program'" : '';

    $komponen_ids = [];
    $has_paket_tbl = (bool)db_value("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='paket_komponen'");
    if ($has_paket_tbl) {
        $komponen_ids = array_column(
            db_fetch_all("SELECT program_id FROM paket_komponen WHERE paket_id=?","i",[$id]),
            'program_id'
        );
    }
    $daftar_program = db_fetch_all(
        "SELECT id,nama_program FROM program WHERE is_active=1 AND deleted_at IS NULL $where_tipe AND id!=? ORDER BY nama_program",
        "i",[$id]
    );
    return [
        'title'          => 'Edit Program',
        'view'           => __DIR__.'/form.view.php',
        'breadcrumbs'    => [['label'=>'Program','url'=>'index.php?page=program'],['label'=>'Edit']],
        'item'           => $item,
        'komponen_ids'   => $komponen_ids,
        'daftar_program' => $daftar_program,
    ];
}

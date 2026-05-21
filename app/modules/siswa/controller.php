<?php
/**
 * app/modules/siswa/controller.php
 * Module 6.5 — Modul Siswa Upgrade (Phase 2, Task #15)
 *
 * Baru vs M6.3:
 *  - Pagination (10/25/50/100 per halaman)
 *  - Sort per kolom (whitelist + direction)
 *  - Bulk actions: soft-delete massal, ubah status massal
 *  - Auto-generate nomor_induk format PMTC-YY-NNNN
 *  - Lazy load tab detail via action=tab_data
 *  - Reset password siswa
 */

declare(strict_types=1);

// ── Helper: generate nomor_induk PMTC-YYNNNN ──────────────────
function generate_nomor_induk(): string
{
    $yy   = date('y');    // 2 digit tahun: 26
    $last = db_value(
        "SELECT nomor_induk FROM siswa
         WHERE nomor_induk LIKE ?
         ORDER BY id DESC LIMIT 1",
        "s", ["PMTC-{$yy}%"]
    );

    if ($last) {
        // Format: PMTC-260001 → ambil 4 digit terakhir
        $seq = ((int)substr($last, -4)) + 1;
    } else {
        $seq = 1;
    }

    return sprintf('PMTC-%s%04d', $yy, $seq);
}

// ── Kolom yang boleh di-sort (whitelist) ────────────────────────
const SORT_ALLOWED = [
    'nama'     => 's.nama_lengkap',
    'nis'      => 's.nomor_induk',
    'status'   => 's.status_siswa',
    'program'  => 'p.nama_program',
    'daftar'   => 's.created_at',
    'expired'  => 'm.tanggal_selesai_aktif',
];

$action   = get('action', 'index');
$siswa_id = get_int('id');

// ══════════════════════════════════════════════════════════════════
// BULK ACTIONS
// ══════════════════════════════════════════════════════════════════
if ($action === 'bulk' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $bulk_action = post('bulk_action');
    $ids_raw     = $_POST['bulk_ids'] ?? [];

    if (!is_array($ids_raw) || empty($ids_raw)) {
        flash('warning', 'Tidak ada siswa yang dipilih.');
        redirect('index.php?page=siswa');
    }

    // Sanitasi: hanya integer
    $ids = array_filter(array_map('intval', $ids_raw));
    if (empty($ids)) { flash('warning', 'ID tidak valid.'); redirect('index.php?page=siswa'); }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types_i      = str_repeat('i', count($ids));

    switch ($bulk_action) {
        case 'delete':
            db_query(
                "UPDATE siswa SET deleted_at = NOW() WHERE id IN ({$placeholders}) AND deleted_at IS NULL",
                $types_i, $ids
            );
            $n = db_affected();
            log_action('BULK_DELETE_SISWA', 'siswa', null, null, ['ids' => $ids]);
            flash('success', "{$n} siswa berhasil dihapus (soft delete).");
            break;

        case 'aktifkan':
            db_query(
                "UPDATE siswa SET status_siswa = 'Aktif' WHERE id IN ({$placeholders}) AND deleted_at IS NULL",
                $types_i, $ids
            );
            flash('success', count($ids) . " siswa diubah ke status Aktif.");
            break;

        case 'nonaktifkan':
            db_query(
                "UPDATE siswa SET status_siswa = 'Tidak Aktif' WHERE id IN ({$placeholders}) AND deleted_at IS NULL",
                $types_i, $ids
            );
            flash('success', count($ids) . " siswa diubah ke Tidak Aktif.");
            break;

        default:
            flash('warning', 'Aksi tidak dikenali.');
    }

    redirect('index.php?page=siswa');
}

// ══════════════════════════════════════════════════════════════════
// DELETE (soft)
// ══════════════════════════════════════════════════════════════════
if ($action === 'delete' && $siswa_id) {
    csrf_check();
    $lama = db_fetch("SELECT nama_lengkap FROM siswa WHERE id=? AND deleted_at IS NULL", "i", [$siswa_id]);
    if ($lama) {
        db_query("UPDATE siswa SET deleted_at=NOW() WHERE id=?", "i", [$siswa_id]);
        log_action('DELETE_SISWA', 'siswa', $siswa_id, $lama);
        flash('success', "Siswa \"{$lama['nama_lengkap']}\" dihapus. <a href='index.php?page=siswa&action=trash' class='alert-link'>Lihat Trash →</a>");
    }
    redirect('index.php?page=siswa');
}

// ── RESTORE dari trash ──────────────────────────────────────
if ($action === 'restore' && $siswa_id) {
    csrf_check_get();
    $s = db_fetch("SELECT nama_lengkap FROM siswa WHERE id=? AND deleted_at IS NOT NULL", "i", [$siswa_id]);
    if ($s) {
        db_execute("UPDATE siswa SET deleted_at=NULL WHERE id=?", "i", [$siswa_id]);
        db_execute("UPDATE users u JOIN siswa s ON u.id=s.user_id SET u.deleted_at=NULL, u.is_active=1 WHERE s.id=?", "i", [$siswa_id]);
        log_action('RESTORE_SISWA', 'siswa', $siswa_id);
        flash('success', "Siswa \"{$s['nama_lengkap']}\" berhasil dipulihkan.");
    }
    redirect('index.php?page=siswa&action=trash');
}

// ── TRASH BIN view ──────────────────────────────────────────
if ($action === 'trash') {
    $trash_list = db_fetch_all(
        "SELECT s.*, u.email FROM siswa s LEFT JOIN users u ON s.user_id=u.id
         WHERE s.deleted_at IS NOT NULL AND s.deleted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         ORDER BY s.deleted_at DESC"
    );
    return [
        'title'       => 'Trash — Siswa Terhapus',
        'view'        => __DIR__ . '/trash.view.php',
        'breadcrumbs' => [['label'=>'Data Siswa','url'=>'index.php?page=siswa'],['label'=>'Trash']],
        'trash_list'  => $trash_list,
    ];
}

// ══════════════════════════════════════════════════════════════════
// RESET PASSWORD SISWA
// ══════════════════════════════════════════════════════════════════
if ($action === 'reset_password' && $siswa_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $siswa = db_fetch("SELECT s.*, u.id AS uid FROM siswa s JOIN users u ON s.user_id=u.id WHERE s.id=? AND s.deleted_at IS NULL", "i", [$siswa_id]);

    if (!$siswa || !$siswa['uid']) {
        flash('danger', 'Akun login siswa tidak ditemukan.');
        redirect("index.php?page=siswa&action=detail&id={$siswa_id}");
    }

    // Generate password sementara: Perkasa + 4 random digit
    $temp_pass = 'Perkasa@' . rand(1000, 9999);
    $hash      = password_hash($temp_pass, PASSWORD_BCRYPT, ['cost' => 12]);

    db_query("UPDATE users SET password=? WHERE id=?", "si", [$hash, $siswa['uid']]);
    log_action('RESET_PASSWORD_SISWA', 'users', $siswa['uid']);

    flash('success', "Password direset. Sementara: <code>{$temp_pass}</code> — kirim ke siswa via WA.");
    redirect("index.php?page=siswa&action=detail&id={$siswa_id}");
}

// ══════════════════════════════════════════════════════════════════
// STORE (tambah siswa baru)
// ══════════════════════════════════════════════════════════════════
if ($action === 'store') {
    csrf_check();
    $v = validate($_POST);
    $v->required('nama', 'Nama Lengkap')->max('nama', 150);
    $v->required('wa',   'Nomor WA')->phone('wa', 'Nomor WA');
    if ($v->fails()) { flash('danger', $v->first()); redirect('index.php?page=siswa&action=create'); }

    try {
        db_begin();

        $nomor_induk = generate_nomor_induk();
        $email_siswa = preg_replace('/[^0-9]/', '', post('wa')) . '@perkasa.internal';
        $temp_hash   = password_hash('Perkasa@' . rand(1000, 9999), PASSWORD_BCRYPT, ['cost' => 12]);
        $jk          = post('jenis_kelamin') ?: null;
        $target      = post('target_seleksi') ?: null;

        $u_id = db_insert(
            "INSERT INTO users (email, password, role, nama_display, is_active) VALUES (?, ?, 'siswa', ?, 1)",
            "sss", [$email_siswa, $temp_hash, post('nama')]
        );

        $s_id = db_insert(
            "INSERT INTO siswa (user_id, nomor_induk, nama_lengkap, jenis_kelamin, nomor_wa, nama_ortu,
             nomor_wa_ortu, tanggal_lahir, asal_sekolah, target_seleksi, alamat, status_siswa)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Aktif')",
            "issssssssss",
            [$u_id, $nomor_induk, post('nama'), $jk, post('wa'), post('ortu', null),
             post('wa_ortu', null), post('tgl_lahir') ?: null,
             post('asal_sekolah', null), $target, post('alamat', null)]
        );

        if (post_int('program_id') && post('tgl_mulai') && post('tgl_selesai')) {
            db_insert(
                "INSERT INTO membership_siswa (siswa_id, program_id, tanggal_mulai_aktif, tanggal_selesai_aktif, status_membership, created_by)
                 VALUES (?, ?, ?, ?, 'Berjalan', ?)",
                "iissi", [$s_id, post_int('program_id'), post('tgl_mulai'), post('tgl_selesai'), auth_id()]
            );
        }

        db_commit();
        log_action('CREATE_SISWA', 'siswa', $s_id, null, ['nomor_induk' => $nomor_induk, 'nama' => post('nama')]);
        flash('success', "Siswa berhasil didaftarkan dengan NIS: <strong>{$nomor_induk}</strong>");

    } catch (Throwable $e) {
        db_rollback();
        error_log("[SISWA] Create failed: " . $e->getMessage());
        flash('danger', 'Gagal mendaftarkan siswa. Nomor WA atau email mungkin sudah terdaftar.');
    }

    redirect('index.php?page=siswa');
}

// ══════════════════════════════════════════════════════════════════
// UPDATE
// ══════════════════════════════════════════════════════════════════
if ($action === 'update' && $siswa_id) {
    csrf_check();
    $v = validate($_POST);
    $v->required('nama', 'Nama')->max('nama', 150);
    $v->required('wa',   'Nomor WA')->phone('wa', 'Nomor WA');
    if ($v->fails()) { flash('danger', $v->first()); redirect("index.php?page=siswa&action=edit&id={$siswa_id}"); }

    $lama = db_fetch("SELECT * FROM siswa WHERE id=? AND deleted_at IS NULL", "i", [$siswa_id]);
    if (!$lama) { flash('danger', 'Data tidak ditemukan.'); redirect('index.php?page=siswa'); }

    db_query(
        "UPDATE siswa SET nama_lengkap=?, jenis_kelamin=?, nomor_wa=?, nama_ortu=?, nomor_wa_ortu=?,
         tanggal_lahir=?, asal_sekolah=?, target_seleksi=?, alamat=?, status_siswa=?, keterangan_lulus=?, catatan=?
         WHERE id=?",
        "ssssssssssssi",
        [post('nama'), post('jenis_kelamin') ?: null, post('wa'), post('ortu', null), post('wa_ortu', null),
         post('tgl_lahir') ?: null, post('asal_sekolah', null), post('target_seleksi') ?: null,
         post('alamat', null), post('status', 'Aktif'), post('ket_lulus', null), post('catatan', null), $siswa_id]
    );

    // Membership
    if (post_int('program_id') && post('tgl_mulai') && post('tgl_selesai')) {
        $existing = db_fetch("SELECT id FROM membership_siswa WHERE siswa_id=? ORDER BY id DESC LIMIT 1", "i", [$siswa_id]);
        if ($existing) {
            db_query("UPDATE membership_siswa SET program_id=?,tanggal_mulai_aktif=?,tanggal_selesai_aktif=? WHERE id=?",
                "issi", [post_int('program_id'), post('tgl_mulai'), post('tgl_selesai'), $existing['id']]);
        } else {
            db_insert("INSERT INTO membership_siswa (siswa_id,program_id,tanggal_mulai_aktif,tanggal_selesai_aktif,status_membership,created_by) VALUES (?,?,?,?,'Berjalan',?)",
                "iissi", [$siswa_id, post_int('program_id'), post('tgl_mulai'), post('tgl_selesai'), auth_id()]);
        }
    }

    log_action('UPDATE_SISWA', 'siswa', $siswa_id, $lama);
    flash('success', 'Data siswa berhasil diperbarui.');
    redirect("index.php?page=siswa&action=detail&id={$siswa_id}");
}

// ══════════════════════════════════════════════════════════════════
// LAZY LOAD TAB DATA (AJAX endpoint)
// ══════════════════════════════════════════════════════════════════
if ($action === 'tab_data' && $siswa_id) {
    check_auth();
    $tab = get('tab', 'profil');

    header('Content-Type: application/json; charset=utf-8');

    $data = [];

    switch ($tab) {
        case 'membership':
            // Schema-safe: tipe_program added in migration-012, biaya_per_bulan in migration-012
            static $_tipe_col    = null;
            static $_biaya_col   = null;
            if ($_tipe_col  === null) $_tipe_col  = (bool)db_value("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='program' AND COLUMN_NAME='tipe_program'");
            if ($_biaya_col === null) $_biaya_col = (bool)db_value("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='membership_siswa' AND COLUMN_NAME='biaya_per_bulan'");

            $tipe_sel  = $_tipe_col  ? "p.tipe_program,"                                                  : "'Program' AS tipe_program,";
            $biaya_sel = $_biaya_col ? "m.biaya_per_bulan, COALESCE(m.biaya_per_bulan, p.biaya_bulanan) AS biaya_aktif," : "NULL AS biaya_per_bulan, p.biaya_bulanan AS biaya_aktif,";
            $order_by  = $_tipe_col  ? "p.tipe_program, m.id DESC"                                        : "m.id DESC";

            $data = db_fetch_all(
                "SELECT m.*, p.nama_program, {$tipe_sel}
                        p.biaya_bulanan AS biaya_default, {$biaya_sel}
                        '' AS _dummy
                 FROM membership_siswa m
                 LEFT JOIN program p ON m.program_id = p.id
                 WHERE m.siswa_id = ? ORDER BY {$order_by}",
                "i", [$siswa_id]
            );
            break;

        case 'nilai':
            $akademik = db_fetch_all(
                "SELECT tanggal_tes, nilai_twk, nilai_tiu, nilai_tkp, total_skor, kategori_tes
                 FROM penilaian_akademik WHERE siswa_id=? ORDER BY tanggal_tes DESC LIMIT 12",
                "i", [$siswa_id]
            );
            // Cek kolom migration-011 sekali
            static $_binjas_has_skor = null;
            if ($_binjas_has_skor === null) {
                $_binjas_has_skor = (bool)db_value(
                    "SELECT COUNT(*) FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='penilaian_binjas'
                     AND COLUMN_NAME='skor_lari'"
                );
            }
            $skor_cols = $_binjas_has_skor
                ? ',skor_lari,skor_pullup,skor_situp,skor_pushup,skor_shuttle,skor_lunges,skor_renang,nilai_samapta_a,nilai_samapta_b,nilai_ab,nilai_gabungan'
                : '';
            $binjas = db_fetch_all(
                "SELECT tanggal_tes, institusi, gender,
                        lari_jarak_meter, pullup_repetisi, pushup_repetisi, situp_repetisi,
                        shuttlerun_detik, lunges_repetisi, renang_detik
                        $skor_cols, skor_akhir, predikat
                 FROM penilaian_binjas WHERE siswa_id=? ORDER BY tanggal_tes DESC LIMIT 12",
                "i", [$siswa_id]
            );
            $mapel = db_fetch_all(
                "SELECT tanggal_tes, bahasa_indonesia, bahasa_inggris, matematika, pengetahuan_umum, wawasan_kebangsaan
                 FROM penilaian_mapel WHERE siswa_id=? ORDER BY tanggal_tes DESC LIMIT 12",
                "i", [$siswa_id]
            );
            $psikologi = db_fetch_all(
                "SELECT tanggal_tes, kecerdasan, kecermatan, kepribadian, rata_psikologi, status_psikologi
                 FROM penilaian_psikologi WHERE siswa_id=? ORDER BY tanggal_tes DESC LIMIT 12",
                "i", [$siswa_id]
            );
            $data = [
                'akademik'  => $akademik,
                'binjas'    => $binjas,
                'mapel'     => $mapel,
                'psikologi' => $psikologi,
            ];
            break;

        case 'kehadiran':
            $rows = db_fetch_all(
                "SELECT a.*, j.nama_kegiatan, j.tanggal, j.waktu_mulai, j.materi
                 FROM attendance_siswa a JOIN jadwal j ON a.jadwal_id = j.id
                 WHERE a.siswa_id = ? ORDER BY j.tanggal DESC LIMIT 30",
                "i", [$siswa_id]
            );
            $hadir = count(array_filter($rows, fn($r) => $r['status_hadir'] === 'Hadir'));
            $total = count($rows);
            $data  = ['rows' => $rows, 'hadir' => $hadir, 'total' => $total,
                      'pct' => $total > 0 ? round($hadir / $total * 100) : 0];
            break;

        case 'pembayaran':
            // v2.1: membership_id kini nullable (pembayaran multi-program).
            // LEFT JOIN agar pembayaran tanpa membership tetap tampil; nama program
            // fallback ke keterangan_program bila kolom tsb ada (migration 012).
            $_has_ket = (bool)db_value(
                "SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='pembayaran_siswa'
                 AND COLUMN_NAME='keterangan_program'"
            );
            $prog_sel = $_has_ket
                ? "COALESCE(p.nama_program, ps.keterangan_program) AS nama_program"
                : "p.nama_program";
            $data = db_fetch_all(
                "SELECT ps.*, m.tanggal_mulai_aktif, {$prog_sel}
                 FROM pembayaran_siswa ps
                 LEFT JOIN membership_siswa m ON ps.membership_id = m.id
                 LEFT JOIN program p ON m.program_id = p.id
                 WHERE ps.siswa_id = ? AND ps.deleted_at IS NULL
                 ORDER BY COALESCE(ps.tanggal_bayar, ps.created_at) DESC LIMIT 24",
                "i", [$siswa_id]
            );
            break;

        default:
            $data = [];
    }

    echo json_encode(['ok' => true, 'tab' => $tab, 'data' => $data]);
    exit;
}

// ══════════════════════════════════════════════════════════════════
// CREATE / EDIT form data
// ══════════════════════════════════════════════════════════════════
$daftar_program = db_fetch_all("SELECT id, nama_program, biaya_bulanan FROM program WHERE is_active=1 OR is_active IS NULL ORDER BY nama_program");

if ($action === 'create') {
    return ['title' => 'Daftar Siswa Baru', 'view' => __DIR__ . '/form.view.php',
            'breadcrumbs' => [['label' => 'Keanggotaan', 'url' => 'index.php?page=siswa'], ['label' => 'Daftar Baru']],
            'siswa' => null, 'daftar_program' => $daftar_program];
}

if ($action === 'edit' && $siswa_id) {
    $siswa = db_fetch(
        "SELECT s.*, m.program_id, m.tanggal_mulai_aktif, m.tanggal_selesai_aktif
         FROM siswa s
         LEFT JOIN (SELECT siswa_id, MAX(id) mid FROM membership_siswa GROUP BY siswa_id) mm ON s.id=mm.siswa_id
         LEFT JOIN membership_siswa m ON mm.mid=m.id
         WHERE s.id=? AND s.deleted_at IS NULL",
        "i", [$siswa_id]
    );
    if (!$siswa) { flash('danger', 'Siswa tidak ditemukan.'); redirect('index.php?page=siswa'); }
    return ['title' => 'Edit: ' . $siswa['nama_lengkap'], 'view' => __DIR__ . '/form.view.php',
            'breadcrumbs' => [['label' => 'Keanggotaan', 'url' => 'index.php?page=siswa'],
                              ['label' => $siswa['nama_lengkap'], 'url' => "index.php?page=siswa&action=detail&id={$siswa_id}"],
                              ['label' => 'Edit']],
            'siswa' => $siswa, 'daftar_program' => $daftar_program];
}

// ══════════════════════════════════════════════════════════════════
// DETAIL
// ══════════════════════════════════════════════════════════════════
if ($action === 'detail' && $siswa_id) {
    $siswa = db_fetch(
        "SELECT s.*, p.nama_program, m.id AS member_id,
                m.tanggal_mulai_aktif, m.tanggal_selesai_aktif, m.status_membership
         FROM siswa s
         LEFT JOIN (SELECT siswa_id, MAX(id) mid FROM membership_siswa GROUP BY siswa_id) mm ON s.id=mm.siswa_id
         LEFT JOIN membership_siswa m ON mm.mid=m.id
         LEFT JOIN program p ON m.program_id=p.id
         WHERE s.id=? AND s.deleted_at IS NULL",
        "i", [$siswa_id]
    );
    if (!$siswa) { flash('danger', 'Siswa tidak ditemukan.'); redirect('index.php?page=siswa'); }

    // Profil saja dimuat langsung (tidak lazy) — tab lain via AJAX
    return [
        'title'          => $siswa['nama_lengkap'],
        'view'           => __DIR__ . '/detail.view.php',
        'breadcrumbs'    => [['label' => 'Keanggotaan', 'url' => 'index.php?page=siswa'],
                             ['label' => $siswa['nama_lengkap']]],
        'siswa'          => $siswa,
        'daftar_program' => $daftar_program,
    ];
}

// ══════════════════════════════════════════════════════════════════
// INDEX — LIST DENGAN PAGINATION, SORT, FILTER
// ══════════════════════════════════════════════════════════════════
$q          = get('q');
$f_status   = get('status');
$f_program  = get_int('program_id');
$f_target   = get('target');
$per_page   = in_array((int)get('per_page', '25'), [10, 25, 50, 100], true) ? (int)get('per_page', '25') : 25;
$halaman    = max(1, get_int('halaman', 1));

// Sort
$sort_col   = get('sort', 'daftar');
$sort_dir   = strtoupper(get('dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
if (!array_key_exists($sort_col, SORT_ALLOWED)) { $sort_col = 'daftar'; }
$order_sql  = SORT_ALLOWED[$sort_col] . ' ' . $sort_dir;

// Build WHERE
$sql_w  = "WHERE s.deleted_at IS NULL";
$types  = '';
$params = [];

if ($q) {
    $sql_w  .= " AND (s.nama_lengkap LIKE ? OR s.nomor_induk LIKE ? OR s.nomor_wa LIKE ? OR s.nama_ortu LIKE ?)";
    $types  .= 'ssss';
    $params  = array_merge($params, ["%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%"]);
}
if ($f_status && in_array($f_status, ['Aktif', 'Cuti', 'Lulus', 'Tidak Aktif'], true)) {
    $sql_w .= " AND s.status_siswa = ?";
    $types .= 's'; $params[] = $f_status;
}
if ($f_program > 0) {
    $sql_w .= " AND m.program_id = ?";
    $types .= 'i'; $params[] = $f_program;
}
if ($f_target && in_array($f_target, ['Polri','TNI AD','TNI AL','TNI AU','Akpol','Akmil','Bintara','Tamtama','Lainnya'], true)) {
    $sql_w .= " AND s.target_seleksi = ?";
    $types .= 's'; $params[] = $f_target;
}

$sql_base = "FROM siswa s
             LEFT JOIN (SELECT siswa_id, MAX(id) mid FROM membership_siswa GROUP BY siswa_id) mm ON s.id=mm.siswa_id
             LEFT JOIN membership_siswa m ON mm.mid=m.id
             LEFT JOIN program p ON m.program_id=p.id
             {$sql_w}";

$total   = (int)(db_value("SELECT COUNT(DISTINCT s.id) {$sql_base}", $types, $params) ?? 0);
$offset  = ($halaman - 1) * $per_page;
$last_pg = (int)ceil($total / max(1, $per_page));

$daftar_siswa = db_fetch_all(
    "SELECT s.*, p.nama_program, m.tanggal_selesai_aktif, m.status_membership
     {$sql_base}
     ORDER BY {$order_sql}
     LIMIT {$per_page} OFFSET {$offset}",
    $types, $params
);

// Summary stats untuk header
$stat_aktif     = (int)db_value("SELECT COUNT(*) FROM siswa WHERE status_siswa='Aktif'    AND deleted_at IS NULL");
$stat_lulus     = (int)db_value("SELECT COUNT(*) FROM siswa WHERE status_siswa='Lulus'    AND deleted_at IS NULL");
$stat_cuti      = (int)db_value("SELECT COUNT(*) FROM siswa WHERE status_siswa='Cuti'     AND deleted_at IS NULL");
$stat_nonaktif  = (int)db_value("SELECT COUNT(*) FROM siswa WHERE status_siswa='Tidak Aktif' AND deleted_at IS NULL");

return [
    'title'          => 'Data Keanggotaan',
    'view'           => __DIR__ . '/list.view.php',
    'breadcrumbs'    => [['label' => 'Keanggotaan']],
    'daftar_siswa'   => $daftar_siswa,
    'daftar_program' => $daftar_program,
    'total'          => $total,
    'per_page'       => $per_page,
    'halaman'        => $halaman,
    'last_pg'        => $last_pg,
    'offset'         => $offset,
    'q'              => $q,
    'f_status'       => $f_status,
    'f_program'      => $f_program,
    'f_target'       => $f_target,
    'sort_col'       => $sort_col,
    'sort_dir'       => $sort_dir,
    'stat_aktif'     => $stat_aktif,
    'stat_lulus'     => $stat_lulus,
    'stat_cuti'      => $stat_cuti,
    'stat_nonaktif'  => $stat_nonaktif,
];

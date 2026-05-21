<?php
/** app/modules/jadwal/controller.php */
declare(strict_types=1);

$action = get('action', 'index');

// ── AJAX: Ambil data 1 jadwal (untuk modal edit) ─────────────
if ($action === 'get_json') {
    header('Content-Type: application/json');
    $jid  = get_int('id');
    $data = db_fetch(
        "SELECT j.*, GROUP_CONCAT(jt.tutor_id ORDER BY jt.tutor_id) AS tutor_ids
         FROM jadwal j
         LEFT JOIN jadwal_tutor jt ON j.id = jt.jadwal_id
         WHERE j.id = ?
         GROUP BY j.id",
        "i", [$jid]
    );
    if (!$data) { echo json_encode(['ok'=>false,'msg'=>'Jadwal tidak ditemukan']); exit; }
    $data['tutor_ids'] = $data['tutor_ids'] ? array_map('intval', explode(',', $data['tutor_ids'])) : [];
    echo json_encode(['ok'=>true,'data'=>$data]);
    exit;
}

// ── DELETE ────────────────────────────────────────────────────
if ($action === 'delete') {
    csrf_check();
    $jid  = get_int('id');
    $lama = db_fetch("SELECT nama_kegiatan,tanggal FROM jadwal WHERE id=?","i",[$jid]);
    if ($lama) {
        db_query("DELETE FROM jadwal_tutor WHERE jadwal_id=?","i",[$jid]);
        db_query("DELETE FROM jadwal WHERE id=?","i",[$jid]);
        log_action('DELETE_JADWAL','jadwal',$jid,$lama);
        flash('success','Jadwal dihapus.');
    }
    redirect('index.php?page=jadwal&bulan='.get('bulan',date('n')).'&tahun='.get('tahun',date('Y')));
}

// ── STORE (tambah baru) ──────────────────────────────────────
if ($action === 'store') {
    csrf_check();
    $v = validate($_POST);
    $v->required('kegiatan','Jenis Kegiatan');
    $v->required('tanggal','Tanggal')->date('tanggal');
    $v->required('waktu_mulai','Waktu Mulai');
    $v->required('waktu_selesai','Waktu Selesai');
    if ($v->fails()) { flash('danger',$v->first()); redirect('index.php?page=jadwal'); }

    $jid = db_insert(
        "INSERT INTO jadwal (program_id,nama_kegiatan,materi,tanggal,waktu_mulai,waktu_selesai,lokasi)
         VALUES (NULLIF(?,0),?,NULLIF(?,''),?,?,?,?)",
        "issssss",
        [post_int('program_id'), post('kegiatan'), post('materi'),
         post('tanggal'), post('waktu_mulai'), post('waktu_selesai'),
         post('lokasi') ?: 'Perkasa Mulia Training Center']
    );
    foreach ((array)($_POST['tutor_id'] ?? []) as $tid) {
        if (is_numeric($tid)) db_query("INSERT IGNORE INTO jadwal_tutor (jadwal_id,tutor_id) VALUES (?,?)","ii",[$jid,(int)$tid]);
    }
    log_action('CREATE_JADWAL','jadwal',$jid);
    flash('success','Jadwal berhasil ditambahkan.');
    redirect('index.php?page=jadwal&bulan='.date('n',strtotime(post('tanggal'))).'&tahun='.date('Y',strtotime(post('tanggal'))));
}

// ── UPDATE (edit jadwal) ─────────────────────────────────────
if ($action === 'update') {
    csrf_check();
    $jid = post_int('jadwal_id');
    $v = validate($_POST);
    $v->required('kegiatan','Jenis Kegiatan');
    $v->required('tanggal','Tanggal')->date('tanggal');
    $v->required('waktu_mulai','Waktu Mulai');
    $v->required('waktu_selesai','Waktu Selesai');
    if ($v->fails()) { flash('danger',$v->first()); redirect('index.php?page=jadwal'); }

    $lama = db_fetch("SELECT * FROM jadwal WHERE id=?","i",[$jid]);
    db_query(
        "UPDATE jadwal SET program_id=NULLIF(?,0),nama_kegiatan=?,materi=NULLIF(?,''),tanggal=?,waktu_mulai=?,waktu_selesai=?,lokasi=? WHERE id=?",
        "issssssi",
        [post_int('program_id'), post('kegiatan'), post('materi'),
         post('tanggal'), post('waktu_mulai'), post('waktu_selesai'),
         post('lokasi') ?: 'Perkasa Mulia Training Center', $jid]
    );
    db_query("DELETE FROM jadwal_tutor WHERE jadwal_id=?","i",[$jid]);
    foreach ((array)($_POST['tutor_id'] ?? []) as $tid) {
        if (is_numeric($tid)) db_query("INSERT IGNORE INTO jadwal_tutor (jadwal_id,tutor_id) VALUES (?,?)","ii",[$jid,(int)$tid]);
    }
    log_action('UPDATE_JADWAL','jadwal',$jid,$lama);
    flash('success','Jadwal berhasil diperbarui.');
    redirect('index.php?page=jadwal&bulan='.date('n',strtotime(post('tanggal'))).'&tahun='.date('Y',strtotime(post('tanggal'))));
}

// ── Shared data ───────────────────────────────────────────────
$daftar_program = db_fetch_all("SELECT id,nama_program FROM program WHERE is_active=1 ORDER BY nama_program");
if (empty($daftar_program)) {
    $daftar_program = db_fetch_all("SELECT id,nama_program FROM program ORDER BY nama_program");
}
$daftar_tutor = db_fetch_all("SELECT id,nama_lengkap,spesialisasi FROM tutor WHERE status_aktif=1 AND deleted_at IS NULL ORDER BY nama_lengkap");

$bulan = max(1, min(12, get_int('bulan', (int)date('n'))));
$tahun = max(2020, min(2030, get_int('tahun', (int)date('Y'))));
$sub_v = get('sub','calendar');

$jadwal_bulan = db_fetch_all(
    "SELECT j.*, p.nama_program,
            (SELECT GROUP_CONCAT(t.nama_lengkap ORDER BY t.nama_lengkap SEPARATOR ', ')
             FROM jadwal_tutor jt JOIN tutor t ON jt.tutor_id=t.id
             WHERE jt.jadwal_id=j.id) AS nama_tutor
     FROM jadwal j
     LEFT JOIN program p ON j.program_id=p.id
     WHERE MONTH(j.tanggal)=? AND YEAR(j.tanggal)=?
     ORDER BY j.tanggal, j.waktu_mulai",
    "ii", [$bulan, $tahun]
);

$jadwal_by_date = [];
foreach ($jadwal_bulan as $j) $jadwal_by_date[$j['tanggal']][] = $j;

$nama_bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli',
               'Agustus','September','Oktober','November','Desember'];
$prev = $bulan===1  ? ['b'=>12,'t'=>$tahun-1] : ['b'=>$bulan-1,'t'=>$tahun];
$next = $bulan===12 ? ['b'=>1, 't'=>$tahun+1] : ['b'=>$bulan+1,'t'=>$tahun];

return [
    'title'          => 'Jadwal & Tutor',
    'view'           => __DIR__.'/calendar.view.php',
    'breadcrumbs'    => [['label'=>'Jadwal']],
    'jadwal_bulan'   => $jadwal_bulan,
    'jadwal_by_date' => $jadwal_by_date,
    'bulan'          => $bulan,
    'tahun'          => $tahun,
    'nama_bulan'     => $nama_bulan,
    'prev'           => $prev,
    'next'           => $next,
    'daftar_program' => $daftar_program,
    'daftar_tutor'   => $daftar_tutor,
    'sub_v'          => $sub_v,
];

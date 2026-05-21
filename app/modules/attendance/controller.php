<?php
/**
 * app/modules/attendance/controller.php
 * Modul Attendance Siswa — mark kehadiran per jadwal
 */

// ── POST: Simpan attendance ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if ($action === 'store_bulk') {
        $jadwal_id = get_int('jadwal_id');
        $hadir     = $_POST['hadir']     ?? [];  // siswa_id => status
        $ket       = $_POST['keterangan']?? [];  // siswa_id => keterangan

        if (!$jadwal_id) { set_flash('danger','Jadwal tidak valid.'); redirect('index.php?page=attendance'); }

        $count = 0;
        foreach ($hadir as $siswa_id => $status) {
            $siswa_id = (int)$siswa_id;
            if (!$siswa_id) continue;
            $valid_status = in_array($status,['Hadir','Izin','Sakit','Alpa']) ? $status : 'Alpa';
            $keterangan   = trim($ket[$siswa_id] ?? '');
            $dicatat_oleh = auth_id();

            // INSERT ... ON DUPLICATE KEY UPDATE
            db_execute(
                "INSERT INTO attendance_siswa (siswa_id,jadwal_id,status_hadir,keterangan,dicatat_oleh)
                 VALUES (?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE status_hadir=VALUES(status_hadir),keterangan=VALUES(keterangan),dicatat_oleh=VALUES(dicatat_oleh)",
                "iissi",
                [$siswa_id,$jadwal_id,$valid_status,$keterangan,$dicatat_oleh]
            );
            $count++;
        }

        log_action('BULK_ATTENDANCE','attendance_siswa',$jadwal_id,null,['jumlah'=>$count]);
        set_flash('success',"Kehadiran $count siswa berhasil disimpan.");
        redirect("index.php?page=attendance&action=form&jadwal_id=$jadwal_id");
    }
}

// ── GET: FORM per jadwal ─────────────────────────────────────
if ($action === 'form') {
    $jadwal_id = get_int('jadwal_id');
    if (!$jadwal_id) { set_flash('danger','Pilih jadwal dulu.'); redirect('index.php?page=attendance'); }

    $jadwal = db_fetch(
        "SELECT j.*,p.nama_program FROM jadwal j LEFT JOIN program p ON j.program_id=p.id WHERE j.id=?",
        "i",[$jadwal_id]
    );
    if (!$jadwal) { set_flash('danger','Jadwal tidak ditemukan.'); redirect('index.php?page=attendance'); }

    // Ambil semua siswa aktif
    $daftar_siswa = db_fetch_all(
        "SELECT s.id,s.nama_lengkap,s.nomor_induk,
                a.status_hadir, a.keterangan AS att_ket
         FROM siswa s
         LEFT JOIN attendance_siswa a ON a.siswa_id=s.id AND a.jadwal_id=?
         WHERE s.deleted_at IS NULL AND s.status_siswa='Aktif'
         ORDER BY s.nama_lengkap",
        "i",[$jadwal_id]
    );

    $stats = ['Hadir'=>0,'Izin'=>0,'Sakit'=>0,'Alpa'=>0,'Belum'=>0];
    foreach ($daftar_siswa as $s) {
        $st = $s['status_hadir'] ?? 'Belum';
        if (isset($stats[$st])) $stats[$st]++; else $stats['Belum']++;
    }

    return [
        'title'        => 'Absensi — ' . e($jadwal['nama_kegiatan'] ?? '') . ' ' . format_tanggal($jadwal['tanggal'],'d M Y'),
        'view'         => __DIR__.'/form.view.php',
        'breadcrumbs'  => [['label'=>'Attendance','url'=>'index.php?page=attendance'],['label'=>'Form Absensi']],
        'jadwal'       => $jadwal,
        'daftar_siswa' => $daftar_siswa,
        'stats'        => $stats,
    ];
}

// ── GET: LIST jadwal (pilih jadwal untuk absen) ──────────────
$f_bulan = get('bulan') ?: date('Y-m');
[$y,$m]  = explode('-', $f_bulan . '-01');

$jadwal_list = db_fetch_all(
    "SELECT j.*,p.nama_program,
            COUNT(DISTINCT jt.tutor_id) AS jumlah_tutor,
            COUNT(DISTINCT a.siswa_id)  AS sudah_absen
     FROM jadwal j
     LEFT JOIN program p ON j.program_id=p.id
     LEFT JOIN jadwal_tutor jt ON jt.jadwal_id=j.id
     LEFT JOIN attendance_siswa a ON a.jadwal_id=j.id
     WHERE YEAR(j.tanggal)=? AND MONTH(j.tanggal)=?
     GROUP BY j.id
     ORDER BY j.tanggal DESC,j.waktu_mulai ASC",
    "ii",[(int)$y,(int)$m]
);

$total_siswa_aktif = (int)db_value(
    "SELECT COUNT(*) FROM siswa WHERE deleted_at IS NULL AND status_siswa='Aktif'"
) ?? 0;

return [
    'title'             => 'Attendance Siswa',
    'view'              => __DIR__.'/list.view.php',
    'breadcrumbs'       => [],
    'jadwal_list'       => $jadwal_list,
    'f_bulan'           => $f_bulan,
    'total_siswa_aktif' => $total_siswa_aktif,
];

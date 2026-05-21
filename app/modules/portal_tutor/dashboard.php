<?php
/**
 * app/modules/portal_tutor/dashboard.php
 */
$tutor_id = (int)$tutor['id'];
$today    = date('Y-m-d');
$bulan    = date('Y-m');

// Jadwal hari ini
$jadwal_hari_ini = db_fetch_all(
    "SELECT j.*,p.nama_program FROM jadwal j
     LEFT JOIN jadwal_tutor jt ON jt.jadwal_id=j.id
     LEFT JOIN program p ON j.program_id=p.id
     WHERE jt.tutor_id=? AND j.tanggal=?
     ORDER BY j.waktu_mulai",
    "is", [$tutor_id, $today]
);

// Jadwal upcoming 7 hari
$jadwal_upcoming = db_fetch_all(
    "SELECT j.*,p.nama_program FROM jadwal j
     LEFT JOIN jadwal_tutor jt ON jt.jadwal_id=j.id
     LEFT JOIN program p ON j.program_id=p.id
     WHERE jt.tutor_id=? AND j.tanggal > ? AND j.tanggal <= DATE_ADD(?,INTERVAL 7 DAY)
     ORDER BY j.tanggal,j.waktu_mulai LIMIT 5",
    "iss", [$tutor_id, $today, $today]
);

// Sesi bulan ini
$sesi_bulan = (int)db_value(
    "SELECT COUNT(DISTINCT jt.jadwal_id) FROM jadwal_tutor jt
     JOIN jadwal j ON jt.jadwal_id=j.id
     WHERE jt.tutor_id=? AND DATE_FORMAT(j.tanggal,'%Y-%m')=?",
    "is", [$tutor_id, $bulan]
) ?? 0;

$gaji_bulan = $sesi_bulan * (float)($tutor['tarif_per_sesi'] ?? 0);

return [
    'title'           => 'Dashboard',
    'view'            => __DIR__ . '/dashboard.view.php',
    'jadwal_hari_ini' => $jadwal_hari_ini,
    'jadwal_upcoming' => $jadwal_upcoming,
    'sesi_bulan'      => $sesi_bulan,
    'gaji_bulan'      => $gaji_bulan,
];

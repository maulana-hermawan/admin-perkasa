<?php
/** app/modules/portal_siswa/jadwal.php */
$sid     = (int)$siswa['id'];
$f_bulan = get('bulan') ?: date('Y-m');
[$fy,$fm] = explode('-', $f_bulan.'-01');

$jadwal_list = db_fetch_all(
    "SELECT j.*,p.nama_program,
            a.status_hadir
     FROM jadwal j
     LEFT JOIN program p ON j.program_id=p.id
     LEFT JOIN attendance_siswa a ON a.jadwal_id=j.id AND a.siswa_id=?
     WHERE YEAR(j.tanggal)=? AND MONTH(j.tanggal)=?
     ORDER BY j.tanggal,j.waktu_mulai",
    "iii",[$sid,(int)$fy,(int)$fm]
);

$hadir_count = count(array_filter($jadwal_list, fn($j) => $j['status_hadir'] === 'Hadir'));
$total_count = count($jadwal_list);

return [
    'title'       => 'Jadwal',
    'view'        => __DIR__ . '/jadwal.view.php',
    'jadwal_list' => $jadwal_list,
    'f_bulan'     => $f_bulan,
    'hadir_count' => $hadir_count,
    'total_count' => $total_count,
];

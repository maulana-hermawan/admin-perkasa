<?php
/**
 * app/modules/portal_tutor/jadwal.php
 * Portal Tutor — Jadwal saya
 */
$tutor_id = (int)$tutor['id'];
$f_bulan  = get('bulan') ?: date('Y-m');
[$fy,$fm] = explode('-',$f_bulan.'-01');

$jadwal_list = db_fetch_all(
    "SELECT j.*,p.nama_program FROM jadwal j
     LEFT JOIN jadwal_tutor jt ON jt.jadwal_id=j.id
     LEFT JOIN program p ON j.program_id=p.id
     WHERE jt.tutor_id=? AND YEAR(j.tanggal)=? AND MONTH(j.tanggal)=?
     ORDER BY j.tanggal,j.waktu_mulai",
    "iii",[$tutor_id,(int)$fy,(int)$fm]
);

return ['title'=>'Jadwal Saya','view'=>__DIR__.'/jadwal.view.php','jadwal_list'=>$jadwal_list,'f_bulan'=>$f_bulan];

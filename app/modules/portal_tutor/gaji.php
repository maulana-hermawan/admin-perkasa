<?php
/** app/modules/portal_tutor/gaji.php */
$tutor_id = (int)$tutor['id'];
$f_bulan  = get('bulan') ?: date('Y-m');
[$gy,$gm] = explode('-',$f_bulan.'-01');

$sesi_list = db_fetch_all(
    "SELECT j.tanggal,j.nama_kegiatan,j.waktu_mulai,j.waktu_selesai,p.nama_program
     FROM jadwal_tutor jt JOIN jadwal j ON jt.jadwal_id=j.id
     LEFT JOIN program p ON j.program_id=p.id
     WHERE jt.tutor_id=? AND YEAR(j.tanggal)=? AND MONTH(j.tanggal)=? AND j.tanggal<=CURDATE()
     ORDER BY j.tanggal",
    "iii",[$tutor_id,(int)$gy,(int)$gm]
);

$jumlah_sesi = count($sesi_list);
$tarif       = (float)($tutor['tarif_per_sesi'] ?? 0);
$total_gaji  = $jumlah_sesi * $tarif;

return ['title'=>'Slip Gaji','view'=>__DIR__.'/gaji.view.php',
        'sesi_list'=>$sesi_list,'jumlah_sesi'=>$jumlah_sesi,'tarif'=>$tarif,'total_gaji'=>$total_gaji,'f_bulan'=>$f_bulan];

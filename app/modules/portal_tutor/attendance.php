<?php
/**
 * app/modules/portal_tutor/attendance.php
 * Portal Tutor — Isi absensi siswa
 */
$tutor_id  = (int)$tutor['id'];
$jadwal_id = get_int('jadwal_id');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && get('action') === 'store_att') {
    csrf_check();
    $jid   = get_int_post('jadwal_id');
    $hadir = $_POST['hadir']     ?? [];
    $ket   = $_POST['keterangan']?? [];
    foreach($hadir as $sid => $status) {
        $sid  = (int)$sid;
        $stat = in_array($status,['Hadir','Izin','Sakit','Alpa'])?$status:'Alpa';
        db_execute(
            "INSERT INTO attendance_siswa (siswa_id,jadwal_id,status_hadir,keterangan,dicatat_oleh) VALUES(?,?,?,?,?)
             ON DUPLICATE KEY UPDATE status_hadir=VALUES(status_hadir),keterangan=VALUES(keterangan),dicatat_oleh=VALUES(dicatat_oleh)",
            "iissi",[$sid,$jid,$stat,trim($ket[$sid]??''),$tutor_id]
        );
    }
    set_flash('success','Absensi berhasil disimpan!');
    redirect("portal-tutor.php?page=attendance&jadwal_id=$jid".(isset($_GET['tutor_id'])?'&tutor_id='.(int)$_GET['tutor_id']:''));
}

if (!$jadwal_id) {
    $jadwal_list = db_fetch_all(
        "SELECT j.*,p.nama_program,COUNT(DISTINCT a.siswa_id) AS sudah FROM jadwal j
         LEFT JOIN jadwal_tutor jt ON jt.jadwal_id=j.id LEFT JOIN program p ON j.program_id=p.id
         LEFT JOIN attendance_siswa a ON a.jadwal_id=j.id
         WHERE jt.tutor_id=? AND j.tanggal <= CURDATE() AND j.tanggal >= DATE_SUB(CURDATE(),INTERVAL 14 DAY)
         GROUP BY j.id ORDER BY j.tanggal DESC,j.waktu_mulai DESC",
        "i",[$tutor_id]
    );
    return ['title'=>'Pilih Jadwal','view'=>__DIR__.'/att_list.view.php','jadwal_list'=>$jadwal_list];
}

$jadwal = db_fetch("SELECT j.*,p.nama_program FROM jadwal j LEFT JOIN program p ON j.program_id=p.id WHERE j.id=?","i",[$jadwal_id]);
if(!$jadwal){set_flash('danger','Jadwal tidak ditemukan.');redirect('portal-tutor.php?page=attendance');}

$daftar_siswa = db_fetch_all(
    "SELECT s.id,s.nama_lengkap,s.nomor_induk,a.status_hadir,a.keterangan AS att_ket
     FROM siswa s LEFT JOIN attendance_siswa a ON a.siswa_id=s.id AND a.jadwal_id=?
     WHERE s.deleted_at IS NULL AND s.status_siswa='Aktif' ORDER BY s.nama_lengkap",
    "i",[$jadwal_id]
);

return ['title'=>'Isi Absensi','view'=>__DIR__.'/att_form.view.php','jadwal'=>$jadwal,'daftar_siswa'=>$daftar_siswa];

function get_int_post(string $k):int{return (int)($_POST[$k]??0);}

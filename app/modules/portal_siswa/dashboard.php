<?php
/**
 * app/modules/portal_siswa/dashboard.php
 */
$sid = (int)$siswa['id'];

$membership = db_fetch(
    "SELECT m.*,p.nama_program FROM membership_siswa m JOIN program p ON m.program_id=p.id
     WHERE m.siswa_id=? AND m.status_membership='Aktif' ORDER BY m.tanggal_selesai_aktif DESC LIMIT 1",
    "i", [$sid]
);

$jadwal_mendatang = db_fetch_all(
    "SELECT j.*,p.nama_program FROM jadwal j
     LEFT JOIN program p ON j.program_id=p.id
     LEFT JOIN membership_siswa ms ON ms.program_id=j.program_id AND ms.siswa_id=?
     WHERE j.tanggal >= CURDATE()
     ORDER BY j.tanggal,j.waktu_mulai LIMIT 3",
    "i", [$sid]
);

$nilai_terakhir = db_fetch(
    "SELECT pb.* FROM penilaian_binjas pb WHERE pb.siswa_id=? ORDER BY pb.tanggal_tes DESC LIMIT 1",
    "i", [$sid]
);

$tagihan_belum = (int)db_value(
    "SELECT COUNT(*) FROM pembayaran_siswa WHERE siswa_id=? AND status_bayar != 'Lunas'",
    "i", [$sid]
) ?? 0;

$sisa_hari = null;
if ($membership && $membership['tanggal_selesai_aktif']) {
    $sisa_hari = (int)ceil((strtotime($membership['tanggal_selesai_aktif']) - time()) / 86400);
}

return [
    'title'            => 'Beranda',
    'view'             => __DIR__ . '/dashboard.view.php',
    'membership'       => $membership,
    'jadwal_mendatang' => $jadwal_mendatang,
    'nilai_terakhir'   => $nilai_terakhir,
    'tagihan_belum'    => $tagihan_belum,
    'sisa_hari'        => $sisa_hari,
];

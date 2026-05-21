<?php
/** app/modules/portal_siswa/tagihan.php */
$sid = (int)$siswa['id'];

$tagihan_list = db_fetch_all(
    "SELECT ps.*, m.id AS mid FROM pembayaran_siswa ps
     LEFT JOIN membership_siswa m ON ps.membership_id=m.id
     WHERE ps.siswa_id=? AND ps.deleted_at IS NULL
     ORDER BY ps.periode_bulan DESC LIMIT 24",
    "i", [$sid]
);

$total_tagihan = array_sum(array_column($tagihan_list, 'nominal_tagihan'));
$total_bayar   = array_sum(array_column($tagihan_list, 'nominal_bayar'));
$total_sisa    = $total_tagihan - $total_bayar;

return [
    'title'         => 'Tagihan',
    'view'          => __DIR__ . '/tagihan.view.php',
    'tagihan_list'  => $tagihan_list,
    'total_tagihan' => $total_tagihan,
    'total_bayar'   => $total_bayar,
    'total_sisa'    => $total_sisa,
];

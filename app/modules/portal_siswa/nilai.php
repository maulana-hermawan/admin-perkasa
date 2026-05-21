<?php
/** app/modules/portal_siswa/nilai.php */
$sid = (int)$siswa['id'];

$nilai_jasmani = db_fetch_all(
    "SELECT tanggal_tes, skor_akhir AS total_skor, predikat FROM penilaian_binjas WHERE siswa_id=? ORDER BY tanggal_tes DESC LIMIT 10",
    "i", [$sid]
);
$nilai_skd = db_fetch_all(
    "SELECT tanggal_tes, nilai_twk, nilai_tiu, nilai_tkp, total_skor FROM penilaian_akademik WHERE siswa_id=? AND kategori_tes='SKD' ORDER BY tanggal_tes DESC LIMIT 10",
    "i", [$sid]
);
$nilai_psikologi = db_fetch_all(
    "SELECT tanggal_tes, kecerdasan, kecermatan, kepribadian, rata_psikologi, status_psikologi FROM penilaian_psikologi WHERE siswa_id=? ORDER BY tanggal_tes DESC LIMIT 5",
    "i", [$sid]
);

// Chart data: trend jasmani 6 sesi terakhir
$chart_labels  = [];
$chart_jasmani = [];
foreach (array_reverse(array_slice($nilai_jasmani, 0, 6)) as $n) {
    $chart_labels[]  = date('d M', strtotime($n['tanggal_tes']));
    $chart_jasmani[] = (float)($n['total_skor'] ?? 0);
}

$chart_skd = [];
$chart_skd_labels = [];
foreach (array_reverse(array_slice($nilai_skd, 0, 6)) as $n) {
    $chart_skd_labels[] = date('d M', strtotime($n['tanggal_tes']));
    $chart_skd[]        = (float)($n['total_skor'] ?? 0);
}

$nilai_mapel = db_fetch_all(
    "SELECT tanggal_tes, bahasa_indonesia AS b_indonesia, bahasa_inggris AS b_inggris,
            matematika, pengetahuan_umum AS pu, wawasan_kebangsaan AS wk, rata_mapel
     FROM penilaian_mapel WHERE siswa_id=? ORDER BY tanggal_tes DESC LIMIT 6",
    "i", [$sid]
);

// Ambil mapel terbaru untuk summary
$latest_mapel = !empty($nilai_mapel) ? $nilai_mapel[0] : null;

// Chart mapel rata-rata
$chart_mapel = [];
$chart_mapel_labels = [];
foreach (array_reverse(array_slice($nilai_mapel, 0, 6)) as $n) {
    $chart_mapel_labels[] = date('d M', strtotime($n['tanggal_tes']));
    $chart_mapel[]        = (float)($n['rata_mapel'] ?? 0);
}

return [
    'title'             => 'Nilai Saya',
    'view'              => __DIR__ . '/nilai.view.php',
    'nilai_jasmani'     => $nilai_jasmani,
    'nilai_skd'         => $nilai_skd,
    'nilai_psikologi'   => $nilai_psikologi,
    'nilai_mapel'       => $nilai_mapel,
    'latest_mapel'      => $latest_mapel,
    'chart_labels'      => $chart_labels,
    'chart_jasmani'     => $chart_jasmani,
    'chart_skd_labels'  => $chart_skd_labels,
    'chart_skd'         => $chart_skd,
    'chart_mapel_labels'=> $chart_mapel_labels,
    'chart_mapel'       => $chart_mapel,
];

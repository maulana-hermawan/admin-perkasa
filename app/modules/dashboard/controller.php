<?php
/**
 * app/modules/dashboard/controller.php
 * Module 6.4 — Dashboard Upgrade (Phase 2, Task #11)
 * Phase 4: Cache 5 menit untuk query berat
 */

declare(strict_types=1);

require_once __DIR__ . '/../../core/cache.php';

// ── Helper: hitung date range dari preset ───────────────────────
function dashboard_date_range(string $periode): array
{
    $today = date('Y-m-d');
    return match ($periode) {
        'hari_ini'    => [$today, $today],
        'minggu_ini'  => [
            date('Y-m-d', strtotime('monday this week')),
            date('Y-m-d', strtotime('sunday this week')),
        ],
        'bulan_ini'   => [
            date('Y-m-01'),
            date('Y-m-t'),
        ],
        '30_hari'     => [
            date('Y-m-d', strtotime('-29 days')),
            $today,
        ],
        'tahun_ini'   => [
            date('Y-01-01'),
            date('Y-12-31'),
        ],
        default       => [$today, $today], // Fallback; custom ditangani di luar
    };
}

// ── Helper: hitung periode sebelumnya (untuk delta) ─────────────
function dashboard_prev_range(string $dari, string $sampai): array
{
    $durasi = (int)(new DateTime($dari))->diff(new DateTime($sampai))->days + 1;
    $prev_s = date('Y-m-d', strtotime($dari) - $durasi * 86400);
    $prev_e = date('Y-m-d', strtotime($dari) - 86400);
    return [$prev_s, $prev_e];
}

// ── Helper: hitung delta % ───────────────────────────────────────
function dashboard_delta(float $sekarang, float $sebelum): array
{
    if ($sebelum == 0) {
        return ['pct' => null, 'up' => true];
    }
    $pct = (($sekarang - $sebelum) / $sebelum) * 100;
    return ['pct' => round($pct, 1), 'up' => $pct >= 0];
}

// ══════════════════════════════════════════════════════════════════
// AMBIL PARAMETER DATE RANGE
// ══════════════════════════════════════════════════════════════════
$periode   = get('periode', 'bulan_ini');
$PRESETS   = ['hari_ini','minggu_ini','bulan_ini','30_hari','tahun_ini','custom'];
if (!in_array($periode, $PRESETS, true)) $periode = 'bulan_ini';

if ($periode === 'custom') {
    $tgl_dari   = get('tgl_dari')   ?: date('Y-m-01');
    $tgl_sampai = get('tgl_sampai') ?: date('Y-m-t');
    // Validasi format tanggal
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_dari))   $tgl_dari   = date('Y-m-01');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_sampai)) $tgl_sampai = date('Y-m-t');
    if ($tgl_dari > $tgl_sampai) [$tgl_dari, $tgl_sampai] = [$tgl_sampai, $tgl_dari];
} else {
    [$tgl_dari, $tgl_sampai] = dashboard_date_range($periode);
}

[$prev_dari, $prev_sampai] = dashboard_prev_range($tgl_dari, $tgl_sampai);

// Label periode untuk display
$label_presets = [
    'hari_ini'   => 'Hari Ini',
    'minggu_ini' => 'Minggu Ini',
    'bulan_ini'  => 'Bulan Ini',
    '30_hari'    => '30 Hari Terakhir',
    'tahun_ini'  => 'Tahun Ini',
    'custom'     => format_tanggal($tgl_dari, 'd M Y') . ' – ' . format_tanggal($tgl_sampai, 'd M Y'),
];
$label_periode = $label_presets[$periode] ?? 'Bulan Ini';

// ══════════════════════════════════════════════════════════════════
// STAT CARDS DENGAN DELTA
// ══════════════════════════════════════════════════════════════════

// 1. Siswa Aktif (snapshot saat ini — tidak pakai range)
$siswa_aktif_now  = (int)db_value("SELECT COUNT(*) FROM siswa WHERE status_siswa='Aktif' AND deleted_at IS NULL");
$siswa_total      = (int)db_value("SELECT COUNT(*) FROM siswa WHERE deleted_at IS NULL");

// Siswa baru daftar dalam periode ini vs sebelumnya
$siswa_baru_now   = (int)(db_value(
    "SELECT COUNT(*) FROM siswa WHERE deleted_at IS NULL AND DATE(created_at) BETWEEN ? AND ?",
    "ss", [$tgl_dari, $tgl_sampai]
) ?? 0);
$siswa_baru_prev  = (int)(db_value(
    "SELECT COUNT(*) FROM siswa WHERE deleted_at IS NULL AND DATE(created_at) BETWEEN ? AND ?",
    "ss", [$prev_dari, $prev_sampai]
) ?? 0);
$delta_siswa = dashboard_delta($siswa_baru_now, $siswa_baru_prev);

// 2. Pemasukan periode ini vs sebelumnya
$masuk_now  = (float)(db_value(
    "SELECT COALESCE(SUM(nominal),0) FROM transaksi_keuangan WHERE jenis_arus='Pemasukan' AND DATE(tanggal_transaksi) BETWEEN ? AND ?",
    "ss", [$tgl_dari, $tgl_sampai]
) ?? 0);
$masuk_prev = (float)(db_value(
    "SELECT COALESCE(SUM(nominal),0) FROM transaksi_keuangan WHERE jenis_arus='Pemasukan' AND DATE(tanggal_transaksi) BETWEEN ? AND ?",
    "ss", [$prev_dari, $prev_sampai]
) ?? 0);
$delta_masuk = dashboard_delta($masuk_now, $masuk_prev);

// 3. Pengeluaran periode ini vs sebelumnya
$keluar_now  = (float)(db_value(
    "SELECT COALESCE(SUM(nominal),0) FROM transaksi_keuangan WHERE jenis_arus='Pengeluaran' AND DATE(tanggal_transaksi) BETWEEN ? AND ?",
    "ss", [$tgl_dari, $tgl_sampai]
) ?? 0);
$keluar_prev = (float)(db_value(
    "SELECT COALESCE(SUM(nominal),0) FROM transaksi_keuangan WHERE jenis_arus='Pengeluaran' AND DATE(tanggal_transaksi) BETWEEN ? AND ?",
    "ss", [$prev_dari, $prev_sampai]
) ?? 0);
$delta_keluar = dashboard_delta($keluar_now, $keluar_prev);

// 4. Saldo total keseluruhan (bukan per periode)
$tot_masuk  = (float)(db_value("SELECT COALESCE(SUM(nominal),0) FROM transaksi_keuangan WHERE jenis_arus='Pemasukan'") ?? 0);
$tot_keluar = (float)(db_value("SELECT COALESCE(SUM(nominal),0) FROM transaksi_keuangan WHERE jenis_arus='Pengeluaran'") ?? 0);
$saldo_kas  = $tot_masuk - $tot_keluar;

// Saldo sebelum periode ini (untuk delta saldo)
$masuk_sblm_periode  = (float)(db_value(
    "SELECT COALESCE(SUM(nominal),0) FROM transaksi_keuangan WHERE jenis_arus='Pemasukan' AND DATE(tanggal_transaksi) < ?",
    "s", [$tgl_dari]
) ?? 0);
$keluar_sblm_periode = (float)(db_value(
    "SELECT COALESCE(SUM(nominal),0) FROM transaksi_keuangan WHERE jenis_arus='Pengeluaran' AND DATE(tanggal_transaksi) < ?",
    "s", [$tgl_dari]
) ?? 0);
$saldo_prev   = $masuk_sblm_periode - $keluar_sblm_periode;
$delta_saldo  = dashboard_delta($saldo_kas, $saldo_prev);

// ══════════════════════════════════════════════════════════════════
// TREND CHART 6 BULAN — di-cache 15 menit (data tidak berubah detik ke detik)
// ══════════════════════════════════════════════════════════════════
$chart_data = cache_remember('dashboard_trend_chart', function() {
    $labels = $masuk = $keluar = $saldo_kum_arr = [];
    $saldo_kum = 0.0;
    for ($i = 5; $i >= 0; $i--) {
        $bln_str   = date('Y-m', strtotime("-{$i} months"));
        $bln_label = date('M Y', strtotime("-{$i} months"));
        $m = (float)(db_value("SELECT COALESCE(SUM(nominal),0) FROM transaksi_keuangan WHERE jenis_arus='Pemasukan' AND DATE_FORMAT(tanggal_transaksi,'%Y-%m')=?","s",[$bln_str]) ?? 0);
        $k = (float)(db_value("SELECT COALESCE(SUM(nominal),0) FROM transaksi_keuangan WHERE jenis_arus='Pengeluaran' AND DATE_FORMAT(tanggal_transaksi,'%Y-%m')=?","s",[$bln_str]) ?? 0);
        $saldo_kum += ($m - $k);
        $labels[] = $bln_label; $masuk[] = $m; $keluar[] = $k; $saldo_kum_arr[] = $saldo_kum;
    }
    return compact('labels','masuk','keluar','saldo_kum_arr');
}, 900); // 15 menit

$chart_labels           = $chart_data['labels'];
$chart_masuk            = $chart_data['masuk'];
$chart_keluar           = $chart_data['keluar'];
$chart_saldo_kumulatif  = $chart_data['saldo_kum_arr'];

// ══════════════════════════════════════════════════════════════════
// MEMBERSHIP EXPIRING — cache 10 menit
// ══════════════════════════════════════════════════════════════════
$membership_expiring = cache_remember('dashboard_membership_expiring', fn() => db_fetch_all(
    "SELECT s.id AS siswa_id, s.nama_lengkap, s.nomor_wa,
            m.tanggal_selesai_aktif, p.nama_program,
            DATEDIFF(m.tanggal_selesai_aktif, CURDATE()) AS sisa_hari
     FROM membership_siswa m
     JOIN siswa s ON m.siswa_id = s.id
     JOIN program p ON m.program_id = p.id
     WHERE m.status_membership = 'Berjalan'
       AND m.tanggal_selesai_aktif BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                                       AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
       AND s.deleted_at IS NULL
     ORDER BY m.tanggal_selesai_aktif ASC
     LIMIT 10"
), 600); // 10 menit

$expired_count = count(array_filter($membership_expiring, fn($m) => (int)$m['sisa_hari'] < 0));

// ══════════════════════════════════════════════════════════════════
// JADWAL 5 HARI MENDATANG + JUMLAH TUTOR PER SESI
// ══════════════════════════════════════════════════════════════════
$jadwal_mendatang = db_fetch_all(
    "SELECT j.*,
            p.nama_program,
            COUNT(jt.tutor_id) AS jumlah_tutor
     FROM jadwal j
     LEFT JOIN program p ON j.program_id = p.id
     LEFT JOIN jadwal_tutor jt ON j.id = jt.jadwal_id
     WHERE j.tanggal BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 5 DAY)
     GROUP BY j.id
     ORDER BY j.tanggal ASC, j.waktu_mulai ASC
     LIMIT 8"
);

// ══════════════════════════════════════════════════════════════════
// TOP 5 SISWA BERDASARKAN SKOR SKD (periode terpilih)
// ══════════════════════════════════════════════════════════════════
$top_siswa = db_fetch_all(
    "SELECT s.id, s.nama_lengkap, s.nomor_induk,
            MAX(pa.total_skor) AS skor_tertinggi,
            COUNT(pa.id)       AS jumlah_tryout,
            AVG(pa.total_skor) AS rata_rata
     FROM penilaian_akademik pa
     JOIN siswa s ON pa.siswa_id = s.id
     WHERE s.deleted_at IS NULL
       AND pa.kategori_tes = 'SKD'
       AND DATE(pa.tanggal_tes) BETWEEN ? AND ?
     GROUP BY pa.siswa_id
     ORDER BY skor_tertinggi DESC
     LIMIT 5",
    "ss", [$tgl_dari, $tgl_sampai]
);

// Jika tidak ada data di periode terpilih, ambil all-time
if (empty($top_siswa)) {
    $top_siswa = db_fetch_all(
        "SELECT s.id, s.nama_lengkap, s.nomor_induk,
                MAX(pa.total_skor) AS skor_tertinggi,
                COUNT(pa.id)       AS jumlah_tryout,
                AVG(pa.total_skor) AS rata_rata
         FROM penilaian_akademik pa
         JOIN siswa s ON pa.siswa_id = s.id
         WHERE s.deleted_at IS NULL AND pa.kategori_tes = 'SKD'
         GROUP BY pa.siswa_id
         ORDER BY skor_tertinggi DESC LIMIT 5"
    );
}

// ══════════════════════════════════════════════════════════════════
// DATA UNTUK QUICK ACTION MODALS
// ══════════════════════════════════════════════════════════════════
$qa_programs = db_fetch_all("SELECT id, nama_program FROM program WHERE is_active=1 OR is_active IS NULL ORDER BY nama_program");
$qa_tutors   = db_fetch_all("SELECT id, nama_lengkap FROM tutor WHERE status_aktif=1 ORDER BY nama_lengkap");
$qa_siswa    = db_fetch_all("SELECT id, nama_lengkap, nomor_induk FROM siswa WHERE status_siswa='Aktif' AND deleted_at IS NULL ORDER BY nama_lengkap LIMIT 100");

// ══════════════════════════════════════════════════════════════════
// ANALITIK LANJUTAN — Cohort Lulus + Conversion Rate + Top Program
// ══════════════════════════════════════════════════════════════════

// Konversi rate: Aktif → Lulus per tahun — cache 1 jam
$cohort_lulus = cache_remember('db_cohort_lulus', fn() => db_fetch_all(
    "SELECT YEAR(created_at) AS tahun,
            COUNT(*) AS total_masuk,
            SUM(CASE WHEN status_siswa='Lulus' THEN 1 ELSE 0 END) AS lulus,
            ROUND(SUM(CASE WHEN status_siswa='Lulus' THEN 1 ELSE 0 END)*100/COUNT(*),1) AS pct_lulus
     FROM siswa WHERE deleted_at IS NULL AND YEAR(created_at) >= YEAR(NOW())-3
     GROUP BY YEAR(created_at) ORDER BY tahun DESC"
), 3600); // 1 jam

// Program paling populer — cache 30 menit
$top_program = cache_remember('db_top_program', fn() => db_fetch_all(
    "SELECT p.nama_program, COUNT(m.id) AS jumlah_siswa,
            SUM(CASE WHEN m.status_membership='Aktif' THEN 1 ELSE 0 END) AS aktif
     FROM program p
     LEFT JOIN membership_siswa m ON m.program_id=p.id
     GROUP BY p.id ORDER BY jumlah_siswa DESC LIMIT 5"
), 1800);

// Absensi rate bulan ini
$att_bulan = db_fetch_all(
    "SELECT DATE_FORMAT(j.tanggal,'%d %b') AS tgl_label,
            COUNT(DISTINCT j.id) AS jumlah_sesi,
            COUNT(DISTINCT CASE WHEN a.status_hadir='Hadir' THEN a.siswa_id END) AS total_hadir,
            COUNT(DISTINCT s.id) AS total_siswa
     FROM jadwal j
     CROSS JOIN siswa s ON s.status_siswa='Aktif' AND s.deleted_at IS NULL
     LEFT JOIN attendance_siswa a ON a.jadwal_id=j.id AND a.siswa_id=s.id
     WHERE DATE_FORMAT(j.tanggal,'%Y-%m')=?
     GROUP BY j.id ORDER BY j.tanggal DESC LIMIT 8",
    "s", [date('Y-m')]
);

// Rekap tagihan bulan ini
$tagihan_stats = db_fetch(
    "SELECT
        SUM(nominal_tagihan) AS total_tagihan,
        SUM(nominal_bayar)   AS total_bayar,
        COUNT(*) AS total_tagihan_count,
        SUM(CASE WHEN status_bayar='Lunas' THEN 1 ELSE 0 END) AS lunas_count
     FROM pembayaran_siswa
     WHERE deleted_at IS NULL AND DATE_FORMAT(periode_bulan,'%Y-%m')=?",
    "s", [date('Y-m')]
) ?? [];

// ══════════════════════════════════════════════════════════════════
// RETURN VIEW DATA
// ══════════════════════════════════════════════════════════════════
return [
    'title'       => 'Dashboard',
    'view'        => __DIR__ . '/view.php',
    'breadcrumbs' => [],

    // Filter
    'periode'       => $periode,
    'tgl_dari'      => $tgl_dari,
    'tgl_sampai'    => $tgl_sampai,
    'label_periode' => $label_periode,

    // Stat cards
    'siswa_aktif'   => $siswa_aktif_now,
    'siswa_total'   => $siswa_total,
    'siswa_baru'    => $siswa_baru_now,
    'delta_siswa'   => $delta_siswa,
    'masuk_now'     => $masuk_now,
    'delta_masuk'   => $delta_masuk,
    'keluar_now'    => $keluar_now,
    'delta_keluar'  => $delta_keluar,
    'saldo_kas'     => $saldo_kas,
    'delta_saldo'   => $delta_saldo,

    // Chart
    'chart_labels'           => $chart_labels,
    'chart_masuk'            => $chart_masuk,
    'chart_keluar'           => $chart_keluar,
    'chart_saldo_kumulatif'  => $chart_saldo_kumulatif,

    // Widgets
    'membership_expiring'    => $membership_expiring,
    'expired_count'          => $expired_count,
    'jadwal_mendatang'       => $jadwal_mendatang,
    'top_siswa'              => $top_siswa,

    // Quick actions
    'qa_programs'  => $qa_programs,
    'qa_tutors'    => $qa_tutors,
    'qa_siswa'     => $qa_siswa,

    // Analitik lanjutan
    'cohort_lulus'   => $cohort_lulus,
    'top_program'    => $top_program,
    'att_bulan'      => $att_bulan,
    'tagihan_stats'  => $tagihan_stats,
];

<?php
/**
 * app/modules/laporan/rapor.view.php
 * Halaman rapor nilai siswa — printable / save as PDF via browser
 * Variabel: $siswa, $nilai_binjas, $nilai_mapel, $nilai_akademik, $nilai_psikologi,
 *           $tgl_dari, $tgl_sampai, $siswa_id
 */

$app_url   = rtrim(APP_URL, '/');
$nama      = e($siswa['nama_lengkap']);
$nis       = e($siswa['nomor_induk'] ?? '—');
$program   = e($siswa['nama_program'] ?? '—');
$status    = e($siswa['status_siswa'] ?? '—');
$target    = e($siswa['target_seleksi'] ?? '—');
$periode   = ($tgl_dari && $tgl_sampai)
    ? e(date('d/m/Y', strtotime($tgl_dari))) . ' – ' . e(date('d/m/Y', strtotime($tgl_sampai)))
    : 'Semua periode';
$cetak_tgl = date('d/m/Y H:i');

// Best scores
$best_skor_jasmani  = !empty($nilai_binjas)  ? max(array_column($nilai_binjas,  'skor_akhir'))  : null;
$best_skor_skd      = !empty($nilai_akademik) ? max(array_column($nilai_akademik, 'total_skor')) : null;
$best_rata_psiko    = !empty($nilai_psikologi) ? max(array_column($nilai_psikologi, 'rata_psikologi')) : null;

function r_tgl(string $str): string {
    if (!$str) return '—';
    $d = date_create($str);
    return $d ? date_format($d, 'd/m/Y') : $str;
}
function r_num($v, string $suffix = ''): string {
    return ($v !== null && $v !== '') ? number_format((float)$v, 0, ',', '.') . $suffix : '—';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Rapor Nilai — <?= $nama ?></title>
<style>
/* ── Base ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; color: #1a1a2e; background: #f4f6fb; }
@page { size: A4; margin: 14mm 12mm; }

/* ── Print button area (hidden on print) ── */
.no-print { background: #001233; padding: 10px 20px; display: flex; align-items: center; gap: 12px; }
.no-print a { color: rgba(255,255,255,.7); font-size: 12px; text-decoration: none; }
.no-print a:hover { color: #fff; }
.btn-filter { background: rgba(255,255,255,.15); color: #fff; border: none; padding: 6px 14px; border-radius: 6px; font-size: 12px; cursor: pointer; }
.btn-print  { background: #0d6efd; color: #fff; border: none; padding: 6px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; }
.btn-print:hover { background: #0a58ca; }

/* Filter form */
.filter-bar { background: #fff; border-bottom: 2px solid #e9ecef; padding: 12px 20px; display: flex; align-items: flex-end; gap: 14px; flex-wrap: wrap; }
.filter-bar label { font-size: 11px; font-weight: 600; color: #6c757d; display: block; margin-bottom: 3px; }
.filter-bar input[type=date] { border: 1px solid #dee2e6; border-radius: 6px; padding: 5px 8px; font-size: 12px; }
.filter-bar button { background: #0d6efd; color: #fff; border: none; padding: 6px 16px; border-radius: 6px; font-size: 12px; cursor: pointer; }

/* ── Page content ── */
.page { max-width: 860px; margin: 16px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 16px rgba(0,0,0,.1); overflow: hidden; }

/* ── Header ── */
.rapor-header { background: #001233; color: #fff; padding: 20px 24px 16px; display: flex; align-items: flex-start; gap: 16px; }
.rapor-logo { width: 52px; height: 52px; flex-shrink: 0; }
.rapor-logo img { width: 100%; height: 100%; object-fit: contain; filter: brightness(0) invert(1); }
.rapor-title { flex-grow: 1; }
.rapor-title h1 { font-size: 18px; font-weight: 700; letter-spacing: .5px; margin-bottom: 2px; }
.rapor-title p  { font-size: 11px; opacity: .7; }
.rapor-meta    { text-align: right; font-size: 11px; opacity: .7; }

/* ── Student card ── */
.siswa-card { background: #f0f4ff; border-bottom: 2px solid #dce4f7; padding: 14px 24px; display: flex; gap: 28px; flex-wrap: wrap; }
.siswa-card .field { min-width: 130px; }
.siswa-card .field .label { font-size: 10px; font-weight: 700; color: #6c757d; text-transform: uppercase; letter-spacing: .5px; }
.siswa-card .field .value { font-size: 13px; font-weight: 600; color: #001233; margin-top: 2px; }

/* ── Summary strip ── */
.summary-strip { display: flex; background: #001233; }
.summary-strip .item { flex: 1; padding: 12px 16px; border-right: 1px solid rgba(255,255,255,.12); }
.summary-strip .item:last-child { border-right: none; }
.summary-strip .item .s-label { font-size: 10px; color: rgba(255,255,255,.55); text-transform: uppercase; letter-spacing: .5px; }
.summary-strip .item .s-value { font-size: 20px; font-weight: 700; color: #fff; margin-top: 2px; }
.summary-strip .item .s-sub   { font-size: 10px; color: rgba(255,255,255,.5); margin-top: 1px; }

/* ── Sections ── */
.section { padding: 18px 24px; border-bottom: 1px solid #e9ecef; }
.section-title { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: #001233; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
.section-title::before { content: ''; width: 4px; height: 16px; background: #0d6efd; border-radius: 2px; display: inline-block; }

/* ── Tables ── */
table { width: 100%; border-collapse: collapse; font-size: 11.5px; }
thead th { background: #f4f6fb; color: #6c757d; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; padding: 7px 10px; text-align: left; border-bottom: 2px solid #dee2e6; white-space: nowrap; }
tbody td { padding: 6px 10px; border-bottom: 1px solid #f0f2f5; }
tbody tr:last-child td { border-bottom: none; }
.text-center { text-align: center; }
.text-right  { text-align: right; }
.fw-bold     { font-weight: 700; }
.text-muted  { color: #6c757d; }

/* Badges */
.badge { display: inline-block; padding: 2px 7px; border-radius: 4px; font-size: 10px; font-weight: 700; }
.badge-success { background: #d1e7dd; color: #0a3622; }
.badge-danger  { background: #f8d7da; color: #58151c; }
.badge-warning { background: #fff3cd; color: #664d03; }
.badge-info    { background: #cff4fc; color: #055160; }
.badge-primary { background: #cfe2ff; color: #052c65; }

/* Score colors */
.score-good   { color: #198754; font-weight: 700; }
.score-bad    { color: #dc3545; font-weight: 700; }

/* ── No data ── */
.no-data { text-align: center; color: #adb5bd; padding: 24px 0; font-size: 12px; }

/* ── Footer ── */
.rapor-footer { background: #f8f9fa; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; font-size: 10px; color: #adb5bd; border-top: 1px solid #e9ecef; }

/* ── Print overrides ── */
@media print {
    body { background: #fff; }
    .no-print, .filter-bar { display: none !important; }
    .page { margin: 0; border-radius: 0; box-shadow: none; }
    tbody tr { page-break-inside: avoid; }
    .section { page-break-inside: avoid; }
}
</style>
</head>
<body>

<!-- ── Toolbar (hidden on print) ── -->
<div class="no-print">
    <a href="index.php?page=siswa&action=detail&id=<?= (int)$siswa_id ?>">← Kembali ke Profil Anggota</a>
    <div style="margin-left:auto;display:flex;gap:8px;align-items:center;">
        <button class="btn-filter" onclick="document.getElementById('filterBar').classList.toggle('hidden')" style="background:rgba(255,255,255,.2);">
            &#9881; Filter Periode
        </button>
        <button class="btn-print" onclick="window.print()">&#128438; Cetak / Simpan PDF</button>
    </div>
</div>

<!-- Filter periode -->
<div class="filter-bar" id="filterBar">
    <form method="GET" style="display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;width:100%;">
        <input type="hidden" name="page" value="laporan">
        <input type="hidden" name="action" value="rapor">
        <input type="hidden" name="id" value="<?= (int)$siswa_id ?>">
        <div>
            <label>Dari Tanggal</label>
            <input type="date" name="tgl_dari" value="<?= e($tgl_dari ?? '') ?>">
        </div>
        <div>
            <label>Sampai Tanggal</label>
            <input type="date" name="tgl_sampai" value="<?= e($tgl_sampai ?? '') ?>">
        </div>
        <div>
            <label>&nbsp;</label>
            <button type="submit">Terapkan Filter</button>
        </div>
        <?php if ($tgl_dari || $tgl_sampai): ?>
        <div>
            <label>&nbsp;</label>
            <a href="index.php?page=laporan&action=rapor&id=<?= (int)$siswa_id ?>" style="font-size:11px;color:#6c757d;">Hapus filter</a>
        </div>
        <?php endif; ?>
    </form>
</div>

<!-- ── Rapor page ── -->
<div class="page">

    <!-- Header -->
    <div class="rapor-header">
        <div class="rapor-logo">
            <img src="<?= $app_url ?>/assets/logo.svg" alt="Logo" onerror="this.style.display='none'">
        </div>
        <div class="rapor-title">
            <h1>Perkasa Mulia Training Center</h1>
            <p>Laporan Perkembangan Nilai Anggota</p>
        </div>
        <div class="rapor-meta">
            <div>Dicetak: <?= $cetak_tgl ?></div>
            <div>Periode: <?= $periode ?></div>
        </div>
    </div>

    <!-- Siswa info -->
    <div class="siswa-card">
        <div class="field">
            <div class="label">Nomor Induk</div>
            <div class="value"><?= $nis ?></div>
        </div>
        <div class="field" style="flex:2;">
            <div class="label">Nama Lengkap</div>
            <div class="value"><?= $nama ?></div>
        </div>
        <div class="field">
            <div class="label">Program</div>
            <div class="value"><?= $program ?></div>
        </div>
        <div class="field">
            <div class="label">Status</div>
            <div class="value"><?= $status ?></div>
        </div>
        <div class="field">
            <div class="label">Target Seleksi</div>
            <div class="value"><?= $target ?></div>
        </div>
    </div>

    <!-- Summary strip -->
    <div class="summary-strip">
        <div class="item">
            <div class="s-label">Sesi Jasmani</div>
            <div class="s-value"><?= count($nilai_binjas) ?></div>
            <div class="s-sub">Skor terbaik: <?= $best_skor_jasmani !== null ? $best_skor_jasmani : '—' ?></div>
        </div>
        <div class="item">
            <div class="s-label">Sesi Mapel</div>
            <div class="s-value"><?= count($nilai_mapel) ?></div>
            <div class="s-sub">Tes mapel tercatat</div>
        </div>
        <div class="item">
            <div class="s-label">Sesi SKD</div>
            <div class="s-value"><?= count($nilai_akademik) ?></div>
            <div class="s-sub">Skor terbaik: <?= $best_skor_skd !== null ? $best_skor_skd : '—' ?></div>
        </div>
        <div class="item">
            <div class="s-label">Sesi Psikologi</div>
            <div class="s-value"><?= count($nilai_psikologi) ?></div>
            <div class="s-sub">Rata terbaik: <?= $best_rata_psiko !== null ? number_format((float)$best_rata_psiko, 1, ',', '.') : '—' ?></div>
        </div>
    </div>

    <!-- ── JASMANI ── -->
    <div class="section">
        <div class="section-title">Nilai Jasmani (Binjas)</div>
        <?php if (empty($nilai_binjas)): ?>
        <div class="no-data">Belum ada data jasmani pada periode ini.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th class="text-center">Lari (m)</th>
                    <th class="text-center">Pull-up</th>
                    <th class="text-center">Push-up</th>
                    <th class="text-center">Sit-up</th>
                    <th class="text-center">Skor Akhir</th>
                    <th class="text-center">Predikat</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($nilai_binjas as $b): ?>
                <tr>
                    <td class="text-muted"><?= r_tgl($b['tanggal_tes']) ?></td>
                    <td class="text-center"><?= e($b['lari_jarak_meter'] ?? '—') ?></td>
                    <td class="text-center"><?= e($b['pullup_repetisi']  ?? '—') ?></td>
                    <td class="text-center"><?= e($b['pushup_repetisi'] ?? '—') ?></td>
                    <td class="text-center"><?= e($b['situp_repetisi']  ?? '—') ?></td>
                    <td class="text-center fw-bold"><?= e($b['skor_akhir'] ?? '—') ?></td>
                    <td class="text-center">
                        <?php $pr = $b['predikat'] ?? ''; ?>
                        <span class="badge <?= $pr === 'Baik Sekali' ? 'badge-success' : ($pr === 'Baik' ? 'badge-info' : ($pr === 'Cukup' ? 'badge-warning' : 'badge-danger')) ?>">
                            <?= e($pr ?: '—') ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- ── MAPEL ── -->
    <div class="section">
        <div class="section-title">Nilai Mata Pelajaran</div>
        <?php if (empty($nilai_mapel)): ?>
        <div class="no-data">Belum ada data nilai mapel pada periode ini.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th class="text-center">B. Indonesia</th>
                    <th class="text-center">B. Inggris</th>
                    <th class="text-center">Matematika</th>
                    <th class="text-center">Pengetahuan Umum</th>
                    <th class="text-center">Wawasan Kebangsaan</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($nilai_mapel as $m):
                $cols = ['bahasa_indonesia','bahasa_inggris','matematika','pengetahuan_umum','wawasan_kebangsaan'];
            ?>
                <tr>
                    <td class="text-muted"><?= r_tgl($m['tanggal_tes']) ?></td>
                    <?php foreach ($cols as $col):
                        $v = isset($m[$col]) && $m[$col] !== '' ? (int)$m[$col] : null;
                    ?>
                    <td class="text-center <?= $v !== null ? ($v >= 61 ? 'score-good' : 'score-bad') : '' ?>">
                        <?= $v !== null ? $v : '—' ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div style="margin-top:6px;font-size:10px;color:#adb5bd;">* Hijau = ≥ 61 (lulus), Merah = &lt; 61</div>
        <?php endif; ?>
    </div>

    <!-- ── SKD ── -->
    <div class="section">
        <div class="section-title">Nilai Akademik (SKD)</div>
        <?php if (empty($nilai_akademik)): ?>
        <div class="no-data">Belum ada data nilai SKD pada periode ini.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th class="text-center">TWK</th>
                    <th class="text-center">TIU</th>
                    <th class="text-center">TKP</th>
                    <th class="text-center">Total Skor</th>
                    <th class="text-center">Hasil</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($nilai_akademik as $a):
                $total  = (int)($a['total_skor'] ?? 0);
                $twk    = (int)($a['nilai_twk']  ?? 0);
                $tiu    = (int)($a['nilai_tiu']  ?? 0);
                $tkp    = (int)($a['nilai_tkp']  ?? 0);
                $lulus  = $twk >= 65 && $tiu >= 80 && $tkp >= 166;
            ?>
                <tr>
                    <td class="text-muted"><?= r_tgl($a['tanggal_tes']) ?></td>
                    <td><?= e($a['kategori_tes'] ?? 'SKD') ?></td>
                    <td class="text-center <?= $twk >= 65 ? 'score-good' : 'score-bad' ?>"><?= $twk ?></td>
                    <td class="text-center <?= $tiu >= 80 ? 'score-good' : 'score-bad' ?>"><?= $tiu ?></td>
                    <td class="text-center <?= $tkp >= 166 ? 'score-good' : 'score-bad' ?>"><?= $tkp ?></td>
                    <td class="text-center fw-bold"><?= $total ?></td>
                    <td class="text-center">
                        <span class="badge <?= $lulus ? 'badge-success' : 'badge-danger' ?>">
                            <?= $lulus ? 'Lulus' : 'Tidak Lulus' ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div style="margin-top:6px;font-size:10px;color:#adb5bd;">* TWK ≥ 65 · TIU ≥ 80 · TKP ≥ 166</div>
        <?php endif; ?>
    </div>

    <!-- ── PSIKOLOGI ── -->
    <div class="section">
        <div class="section-title">Nilai Psikologi</div>
        <?php if (empty($nilai_psikologi)): ?>
        <div class="no-data">Belum ada data nilai psikologi pada periode ini.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th class="text-center">Kecerdasan</th>
                    <th class="text-center">Kecermatan</th>
                    <th class="text-center">Kepribadian</th>
                    <th class="text-center">Rata-rata</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($nilai_psikologi as $ps):
                $rata   = isset($ps['rata_psikologi']) ? round((float)$ps['rata_psikologi'], 1) : null;
                $status = $ps['status_psikologi'] ?? null;
            ?>
                <tr>
                    <td class="text-muted"><?= r_tgl($ps['tanggal_tes']) ?></td>
                    <td class="text-center"><?= e($ps['kecerdasan']  ?? '—') ?></td>
                    <td class="text-center"><?= e($ps['kecermatan']  ?? '—') ?></td>
                    <td class="text-center"><?= e($ps['kepribadian'] ?? '—') ?></td>
                    <td class="text-center fw-bold"><?= $rata !== null ? number_format($rata, 1, ',', '.') : '—' ?></td>
                    <td class="text-center">
                        <?php if ($status): ?>
                        <span class="badge <?= $status === 'Lulus' ? 'badge-success' : 'badge-danger' ?>">
                            <?= e($status) ?>
                        </span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <div class="rapor-footer">
        <span>Perkasa Mulia Training Center &copy; <?= date('Y') ?></span>
        <span>Dicetak oleh: <?= e(auth_name()) ?> · <?= $cetak_tgl ?></span>
    </div>
</div>

</body>
</html>

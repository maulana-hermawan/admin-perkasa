<?php
/**
 * app/modules/nilai/rekap_nilai.view.php
 * Rekap Nilai — rangkuman lintas kelompok, filter: nama/NIS, program, kelompok, tanggal
 */

// Warna per kelompok
$KFG = [
    'jasmani'   => ['label'=>'Jasmani',   'icon'=>'bi-lightning-charge-fill', 'color'=>'#198754','bg'=>'#d1e7dd','badge'=>'success',  'passing'=>41],
    'mapel'     => ['label'=>'Mapel',     'icon'=>'bi-book-fill',             'color'=>'#0d6efd','bg'=>'#cfe2ff','badge'=>'primary',  'passing'=>61],
    'skd'       => ['label'=>'SKD',       'icon'=>'bi-clipboard2-check-fill', 'color'=>'#0dcaf0','bg'=>'#cff4fc','badge'=>'info',     'passing'=>61],
    'psikologi' => ['label'=>'Psikologi', 'icon'=>'bi-brain',                 'color'=>'#6f42c1','bg'=>'#e2d9f3','badge'=>'purple',   'passing'=>61],
];

function skor_bar(float $skor, float $max, string $color): string {
    $pct = $max > 0 ? min(100, round($skor / $max * 100)) : 0;
    return "<div class=\"progress\" style=\"height:5px;border-radius:3px;background:#e9ecef;\">
        <div class=\"progress-bar\" style=\"width:{$pct}%;background:{$color};\"></div>
    </div>";
}

// ── jas_cell: tampilkan nilai mentah → skor konversi (HARUS di luar foreach) ──
if (!function_exists('jas_cell')) {
    function jas_cell($raw, $skor, $unit = '') {
        $r = ($raw !== null && (float)$raw > 0);
        $s = ($skor !== null && (int)$skor > 0);
        if (!$r && !$s) return '<span class="text-muted" style="font-size:.7rem;">—</span>';
        $rawFmt  = $r
            ? number_format((float)$raw, (fmod((float)$raw, 1) !== 0.0) ? 1 : 0) . ($unit ? ' '.$unit : '')
            : '—';
        $skorFmt = $s
            ? '<span style="color:#198754;font-weight:700;">'.(int)$skor.'</span>'
            : '<span style="color:#aaa;">?</span>';
        return "<span style='font-size:.75rem;color:#555;'>{$rawFmt}</span>"
             . "<br><small style='font-size:.67rem;'>→{$skorFmt}</small>";
    }
}

function skor_badge(float $skor, float $passing, string $color, string $bg): string {
    $lulus = $skor >= $passing;
    $c = $lulus ? $color : '#dc3545';
    $b = $lulus ? $bg    : '#ffe0e3';
    return "<span class=\"fw-bold\" style=\"color:{$c};background:{$b};border:1px solid {$c};border-radius:5px;padding:.15rem .45rem;font-size:.82rem;\">
        {$skor}
    </span>";
}
?>

<style>
/* ── Filter Panel ── */
.rn-filter { background:#fff; border:1px solid #e3e7ef; border-radius:12px; padding:1.2rem 1.25rem 1rem; margin-bottom:1.25rem; }
.rn-filter label { font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#666; margin-bottom:.3rem; }

/* ── Kelompok Checkboxes ── */
.kel-check-grid { display:flex; flex-wrap:wrap; gap:.5rem; }
.kel-check-item { display:flex; align-items:center; gap:.45rem; padding:.4rem .85rem; border-radius:8px;
                  border:2px solid #e3e7ef; cursor:pointer; transition:all .15s; user-select:none;
                  font-size:.82rem; font-weight:600; background:#fff; }
.kel-check-item.checked-jasmani   { background:#d1e7dd; border-color:#198754; color:#0a5c36; }
.kel-check-item.checked-mapel     { background:#cfe2ff; border-color:#0d6efd; color:#084298; }
.kel-check-item.checked-skd       { background:#cff4fc; border-color:#0dcaf0; color:#055160; }
.kel-check-item.checked-psikologi { background:#e2d9f3; border-color:#6f42c1; color:#3d1a78; }
.kel-check-item input { display:none; }

/* ── Stat Cards ── */
.rn-stat { background:#fff; border:1px solid #e3e7ef; border-radius:10px; padding:.9rem 1.1rem; }
.rn-stat .val { font-size:1.5rem; font-weight:800; line-height:1; }
.rn-stat .sub { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#888; margin-top:.15rem; }
.rn-stat .icon { width:40px; height:40px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }

/* ── Rekap Table ── */
.rn-table { font-size:.81rem; }
.rn-table th { font-size:.68rem; text-transform:uppercase; letter-spacing:.05em; background:#f5f7fb; font-weight:700; white-space:nowrap; }
.rn-table td { vertical-align:middle; padding:.55rem .6rem; }
.rn-table tr:hover td { background:#f9fafc; }
.group-header { background:linear-gradient(135deg,var(--gh-c1),var(--gh-c2)); color:#fff; }
.group-header th { color:#fff !important; font-size:.7rem; padding:.4rem .6rem; }
.sub-score { font-size:.68rem; color:#888; }
.badge-purple { background:#e2d9f3; color:#3d1a78; border:1px solid #6f42c1; font-size:.65rem; }
.no-data-cell { color:#ccc; font-size:.75rem; text-align:center; }
.rank-badge { display:inline-flex; align-items:center; justify-content:center;
              width:22px; height:22px; border-radius:50%; font-size:.68rem; font-weight:800; }
.rank-1 { background:#ffd700; color:#7a5700; }
.rank-2 { background:#c0c0c0; color:#4a4a4a; }
.rank-3 { background:#cd7f32; color:#5a3000; }
.rank-n { background:#e9ecef; color:#666; }

@media print {
    .rn-filter, .print-hide { display:none !important; }
    .rn-table th, .rn-table td { font-size:9pt; padding:.3rem .4rem; }
    body { font-size:10pt; }
}
</style>

<!-- ── Header ──────────────────────────────────────────────── -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0">
            <i class="bi bi-bar-chart-steps me-2 text-primary"></i>Rekap Nilai Siswa
        </h4>
        <small class="text-muted">Rangkuman nilai terbaik semua kelompok dalam periode yang dipilih.</small>
    </div>
    <div class="d-flex gap-2 print-hide">
        <a href="index.php?page=nilai" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-list-ul me-1"></i>Input Nilai
        </a>
    </div>
</div>

<!-- ── Filter Panel ─────────────────────────────────────────── -->
<div class="rn-filter print-hide">
    <form method="GET" id="formRekap">
        <input type="hidden" name="page"   value="nilai">
        <input type="hidden" name="action" value="rekap_nilai">
        <div class="row g-2">

            <!-- Nama / NIS -->
            <div class="col-sm-6 col-lg-3">
                <label>Nama / NIS Siswa</label>
                <input type="text" name="f_nama" class="form-control form-control-sm"
                       placeholder="Cari nama atau nomor induk…"
                       value="<?= e($f_nama) ?>">
            </div>

            <!-- Program -->
            <div class="col-sm-6 col-lg-3">
                <label>Program</label>
                <select name="f_program" class="form-select form-select-sm">
                    <option value="0">— Semua Program —</option>
                    <?php foreach ($daftar_program_filter as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= $f_program===(int)$p['id']?'selected':'' ?>>
                        <?= e($p['nama_program']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dari -->
            <div class="col-6 col-sm-3 col-lg-2">
                <label>Dari Tanggal</label>
                <input type="date" name="f_dari" class="form-control form-control-sm"
                       value="<?= e($f_dari) ?>">
            </div>

            <!-- Sampai -->
            <div class="col-6 col-sm-3 col-lg-2">
                <label>Sampai Tanggal</label>
                <input type="date" name="f_sampai" class="form-control form-control-sm"
                       value="<?= e($f_sampai) ?>">
            </div>
        </div>

        <!-- Kelompok Nilai -->
        <div class="mt-2 mb-1">
            <label>Kelompok Nilai <small class="text-muted fw-normal normal-case">(pilih 1 atau lebih)</small></label>
        </div>
        <div class="kel-check-grid mb-3">
            <?php foreach ($KFG as $k => $cfg): ?>
            <label class="kel-check-item <?= in_array($k,$f_kelompok)?'checked-'.$k:'' ?>"
                   onclick="toggleKel(this,'<?= $k ?>')">
                <input type="checkbox" name="f_kelompok[]" value="<?= $k ?>"
                       <?= in_array($k,$f_kelompok)?'checked':'' ?>>
                <i class="bi <?= $cfg['icon'] ?>"></i>
                <?= $cfg['label'] ?>
            </label>
            <?php endforeach; ?>
        </div>

        <!-- Aksi -->
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <button type="submit" class="btn btn-primary btn-sm px-3">
                <i class="bi bi-funnel-fill me-1"></i>Terapkan Filter
            </button>
            <a href="index.php?page=nilai&action=rekap_nilai" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-circle me-1"></i>Reset
            </a>
            <a id="btnExport" href="#" class="btn btn-outline-success btn-sm ms-auto"
               onclick="return updateExportLink(this)">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
            </a>
            <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
    </form>

    <!-- Periode info -->
    <div class="mt-2 pt-2 border-top">
        <small class="text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Periode: <strong><?= format_tanggal($f_dari,'d M Y') ?></strong>
            s/d <strong><?= format_tanggal($f_sampai,'d M Y') ?></strong>
            &nbsp;·&nbsp; Kelompok: <strong><?= implode(', ', array_map(fn($k) => $KFG[$k]['label'], $f_kelompok)) ?></strong>
            <?php if ($f_program): ?>&nbsp;·&nbsp; Program: <strong><?= e(array_column($daftar_program_filter,'nama_program','id')[$f_program] ?? '?') ?></strong><?php endif; ?>
            <?php if ($f_nama): ?>&nbsp;·&nbsp; Cari: <strong>"<?= e($f_nama) ?>"</strong><?php endif; ?>
        </small>
    </div>
</div>

<?php if (empty($rekap_data)): ?>
<!-- Empty State -->
<div class="pk-card text-center py-5">
    <i class="bi bi-bar-chart d-block mb-3 text-muted" style="font-size:3rem;opacity:.25;"></i>
    <h6 class="text-muted mb-1">Tidak ada data nilai</h6>
    <small class="text-muted">Ubah filter atau rentang tanggal untuk melihat rekap.</small>
</div>
<?php else: ?>

<!-- ── Stat Cards ─────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="rn-stat d-flex justify-content-between align-items-center gap-2">
            <div>
                <div class="val text-dark"><?= $stat_siswa ?></div>
                <div class="sub">Siswa Dinilai</div>
            </div>
            <div class="icon bg-dark bg-opacity-10 text-dark">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
    </div>
    <?php if (in_array('jasmani',$f_kelompok) && $stat_avg_jasmani !== null): ?>
    <div class="col-6 col-md-3">
        <div class="rn-stat d-flex justify-content-between align-items-center gap-2">
            <div>
                <div class="val" style="color:#198754;"><?= $stat_avg_jasmani ?></div>
                <div class="sub">Rata Jasmani</div>
            </div>
            <div class="icon" style="background:#d1e7dd;color:#198754;">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if (in_array('mapel',$f_kelompok) && $stat_avg_mapel !== null): ?>
    <div class="col-6 col-md-3">
        <div class="rn-stat d-flex justify-content-between align-items-center gap-2">
            <div>
                <div class="val text-primary"><?= $stat_avg_mapel ?></div>
                <div class="sub">Rata Mapel</div>
            </div>
            <div class="icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-book-fill"></i>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if (in_array('skd',$f_kelompok) && $stat_avg_skd !== null): ?>
    <div class="col-6 col-md-3">
        <div class="rn-stat d-flex justify-content-between align-items-center gap-2">
            <div>
                <div class="val text-info"><?= $stat_avg_skd ?></div>
                <div class="sub">Rata SKD</div>
            </div>
            <div class="icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-clipboard2-check-fill"></i>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if (in_array('psikologi',$f_kelompok) && $stat_avg_psiko !== null): ?>
    <div class="col-6 col-md-3">
        <div class="rn-stat d-flex justify-content-between align-items-center gap-2">
            <div>
                <div class="val" style="color:#6f42c1;"><?= $stat_avg_psiko ?></div>
                <div class="sub">Rata Psikologi</div>
            </div>
            <div class="icon" style="background:#e2d9f3;color:#6f42c1;">
                <i class="bi bi-brain"></i>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ── Tabel Rekap ─────────────────────────────────────────── -->
<div class="pk-card overflow-hidden">
    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom print-hide">
        <div class="fw-bold small">
            <i class="bi bi-table me-1 text-primary"></i>
            TABEL REKAP — <?= count($rekap_data) ?> siswa
        </div>
        <div class="d-flex align-items-center gap-2">
            <input type="text" id="quickSearch" class="form-control form-control-sm"
                   placeholder="Cari di tabel…" style="width:180px;"
                   oninput="quickSearchTable(this.value)">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table rn-table mb-0 align-middle" id="rekapTable">
            <thead>
                <!-- Row 1: group headers -->
                <tr>
                    <th class="ps-3" rowspan="2" style="min-width:30px;">#</th>
                    <th rowspan="2" style="min-width:180px;">Siswa</th>
                    <th rowspan="2" class="d-none d-md-table-cell">Program</th>

                    <?php if (in_array('jasmani',$f_kelompok)): ?>
                    <th colspan="10" class="text-center group-header"
                        style="--gh-c1:#198754;--gh-c2:#0a5c36;">
                        <i class="bi bi-lightning-charge-fill me-1"></i>Jasmani
                    </th>
                    <?php endif; ?>

                    <?php if (in_array('mapel',$f_kelompok)): ?>
                    <th colspan="<?= 2 + 5 ?>" class="text-center group-header"
                        style="--gh-c1:#0d6efd;--gh-c2:#084298;">
                        <i class="bi bi-book-fill me-1"></i>Mapel
                    </th>
                    <?php endif; ?>

                    <?php if (in_array('skd',$f_kelompok)): ?>
                    <th colspan="5" class="text-center group-header"
                        style="--gh-c1:#0dcaf0;--gh-c2:#055160;">
                        <i class="bi bi-clipboard2-check-fill me-1"></i>SKD
                    </th>
                    <?php endif; ?>

                    <?php if (in_array('psikologi',$f_kelompok)): ?>
                    <th colspan="5" class="text-center group-header"
                        style="--gh-c1:#6f42c1;--gh-c2:#3d1a78;">
                        <i class="bi bi-brain me-1"></i>Psikologi
                    </th>
                    <?php endif; ?>
                </tr>
                <!-- Row 2: sub-column headers -->
                <tr>
                    <?php if (in_array('jasmani',$f_kelompok)): ?>
                    <th class="text-center" style="background:#e8f5e9;color:#198754;">Skor</th>
                    <th class="text-center" style="background:#e8f5e9;color:#198754;">Nilai</th>
                    <th class="text-center" style="background:#e8f5e9;color:#198754;white-space:nowrap;">Lari<br><small style="font-weight:400;">(m→skor)</small></th>
                    <th class="text-center" style="background:#e8f5e9;color:#198754;white-space:nowrap;">Pull<br><small style="font-weight:400;">(reps→skor)</small></th>
                    <th class="text-center" style="background:#e8f5e9;color:#198754;white-space:nowrap;">Sit<br><small style="font-weight:400;">(reps→skor)</small></th>
                    <th class="text-center" style="background:#e8f5e9;color:#198754;white-space:nowrap;">Push<br><small style="font-weight:400;">(reps→skor)</small></th>
                    <th class="text-center" style="background:#e8f5e9;color:#198754;white-space:nowrap;">Shuttle<br><small style="font-weight:400;">(s→skor)</small></th>
                    <th class="text-center" style="background:#e8f5e9;color:#198754;white-space:nowrap;">Lunges<br><small style="font-weight:400;">(reps→skor)</small></th>
                    <th class="text-center" style="background:#e8f5e9;color:#198754;white-space:nowrap;">Renang<br><small style="font-weight:400;">(s→skor)</small></th>
                    <th class="text-center" style="background:#e8f5e9;color:#198754;white-space:nowrap;">Smt A/B/Gab</th>
                    <?php endif; ?>

                    <?php if (in_array('mapel',$f_kelompok)): ?>
                    <th class="text-center" style="background:#e3f0ff;color:#0d6efd;">Rata</th>
                    <th class="text-center" style="background:#e3f0ff;color:#0d6efd;">B.Ind</th>
                    <th class="text-center" style="background:#e3f0ff;color:#0d6efd;">B.Ing</th>
                    <th class="text-center" style="background:#e3f0ff;color:#0d6efd;">Mat</th>
                    <th class="text-center" style="background:#e3f0ff;color:#0d6efd;">PU</th>
                    <th class="text-center" style="background:#e3f0ff;color:#0d6efd;">WK</th>
                    <th class="text-center" style="background:#e3f0ff;color:#0d6efd;">Tes</th>
                    <?php endif; ?>

                    <?php if (in_array('skd',$f_kelompok)): ?>
                    <th class="text-center" style="background:#e0f7fa;color:#055160;">Total</th>
                    <th class="text-center" style="background:#e0f7fa;color:#055160;">TWK</th>
                    <th class="text-center" style="background:#e0f7fa;color:#055160;">TIU</th>
                    <th class="text-center" style="background:#e0f7fa;color:#055160;">TKP</th>
                    <th class="text-center" style="background:#e0f7fa;color:#055160;">Tes</th>
                    <?php endif; ?>

                    <?php if (in_array('psikologi',$f_kelompok)): ?>
                    <th class="text-center" style="background:#ede7f6;color:#6f42c1;">Rata</th>
                    <th class="text-center" style="background:#ede7f6;color:#6f42c1;">Kec.</th>
                    <th class="text-center" style="background:#ede7f6;color:#6f42c1;">Kcm.</th>
                    <th class="text-center" style="background:#ede7f6;color:#6f42c1;">Kpr.</th>
                    <th class="text-center" style="background:#ede7f6;color:#6f42c1;">Status</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rekap_data as $i => $s):
                $rank = $i + 1;
                $rankClass = match($rank) { 1=>'rank-1', 2=>'rank-2', 3=>'rank-3', default=>'rank-n' };
                $j = $s['jasmani'];
                $m = $s['mapel'];
                $k = $s['skd'];
                $p = $s['psikologi'];
            ?>
            <tr class="rekap-row">
                <!-- Rank -->
                <td class="ps-3 text-center">
                    <span class="rank-badge <?= $rankClass ?>"><?= $rank ?></span>
                </td>
                <!-- Siswa -->
                <td>
                    <a href="index.php?page=siswa&action=detail&id=<?= (int)$s['id'] ?>"
                       class="fw-bold text-dark text-decoration-none">
                        <?= e($s['nama_lengkap']) ?>
                    </a>
                    <div class="text-muted" style="font-size:.68rem;">
                        <?= e($s['nomor_induk']) ?> ·
                        <?= $s['jenis_kelamin'] === 'L' ? '♂' : ($s['jenis_kelamin'] === 'P' ? '♀' : '') ?>
                        <span class="ms-1 badge <?= $s['status_siswa']==='Aktif'?'bg-success':'bg-secondary' ?> bg-opacity-75" style="font-size:.6rem;">
                            <?= e($s['status_siswa']) ?>
                        </span>
                    </div>
                </td>
                <!-- Program -->
                <td class="d-none d-md-table-cell text-muted" style="font-size:.75rem;">
                    <?= e($s['nama_program']) ?>
                </td>

                <!-- ── JASMANI ── -->
                <?php if (in_array('jasmani',$f_kelompok)): ?>
                <?php if ($j): ?>
                <?php
                $pred = $j['predikat_terbaik'] ?? '—';
                $predColor = match($pred) {
                    'Baik Sekali' => ['#198754','#d1e7dd'],
                    'Baik'        => ['#0d6efd','#cfe2ff'],
                    'Cukup'       => ['#fd7e14','#fff3cd'],
                    default       => ['#6c757d','#e9ecef'],
                };
                $samapta_a   = $j['best_samapta_a']   ?? null;
                $samapta_b   = $j['best_samapta_b']   ?? null;
                $nilai_ab    = $j['best_nilai_ab']     ?? $j['skor_terbaik'] ?? null;
                $nilai_gab   = $j['best_nilai_gabungan'] ?? null;
                ?>
                <!-- Skor Akhir -->
                <td class="text-center">
                    <?= skor_badge((float)$j['skor_terbaik'], 41, '#198754', '#d1e7dd') ?>
                    <div class="mt-1"><?= skor_bar((float)$j['skor_terbaik'], 100, '#198754') ?></div>
                    <div class="sub-score mt-1">rata <?= round((float)$j['skor_rata'],1) ?> · <?= (int)$j['jumlah_tes'] ?>×</div>
                </td>
                <!-- Nilai / Predikat -->
                <td class="text-center">
                    <span class="badge" style="background:<?= $predColor[1] ?>;color:<?= $predColor[0] ?>;border:1px solid <?= $predColor[0] ?>;font-size:.63rem;">
                        <?= e($pred) ?>
                    </span>
                </td>
                <!-- Lari: mentah→skor -->
                <td class="text-center"><?= jas_cell($j['best_lari'] ?? null, $j['best_skor_lari'] ?? null, 'm') ?></td>
                <!-- Pullup -->
                <td class="text-center"><?= jas_cell($j['best_pullup'] ?? null, $j['best_skor_pullup'] ?? null) ?></td>
                <!-- Situp -->
                <td class="text-center"><?= jas_cell($j['best_situp'] ?? null, $j['best_skor_situp'] ?? null) ?></td>
                <!-- Pushup -->
                <td class="text-center"><?= jas_cell($j['best_pushup'] ?? null, $j['best_skor_pushup'] ?? null) ?></td>
                <!-- Shuttle Run -->
                <td class="text-center"><?= jas_cell($j['best_shuttle'] ?? null, $j['best_skor_shuttle'] ?? null, 's') ?></td>
                <!-- Lunges -->
                <td class="text-center"><?= jas_cell($j['best_lunges'] ?? null, $j['best_skor_lunges'] ?? null) ?></td>
                <!-- Renang -->
                <td class="text-center"><?= jas_cell($j['best_renang'] ?? null, $j['best_skor_renang'] ?? null, 's') ?></td>
                <!-- Samapta A / B / Gabungan -->
                <td class="text-center" style="font-size:.72rem;line-height:1.5;">
                    <?php if ($samapta_a !== null): ?>
                    <div><span class="text-muted">A:</span> <strong style="color:#198754;"><?= round((float)$samapta_a,1) ?></strong></div>
                    <?php endif; ?>
                    <?php if ($samapta_b !== null): ?>
                    <div><span class="text-muted">B:</span> <strong style="color:#0d6efd;"><?= round((float)$samapta_b,1) ?></strong></div>
                    <?php endif; ?>
                    <?php if ($nilai_ab !== null): ?>
                    <div><span class="text-muted">AB:</span> <strong><?= round((float)$nilai_ab,1) ?></strong></div>
                    <?php endif; ?>
                    <?php if ($nilai_gab !== null): ?>
                    <div><span class="badge bg-success" style="font-size:.6rem;">Gab: <?= round((float)$nilai_gab,1) ?></span></div>
                    <?php endif; ?>
                </td>
                <?php else: ?>
                <td colspan="10" class="no-data-cell">—</td>
                <?php endif; ?>
                <?php endif; ?>

                <!-- ── MAPEL ── -->
                <?php if (in_array('mapel',$f_kelompok)): ?>
                <?php if ($m): ?>
                <td class="text-center">
                    <?= skor_badge((float)$m['skor_terbaik'], 61, '#0d6efd', '#cfe2ff') ?>
                    <div class="mt-1"><?= skor_bar((float)$m['skor_terbaik'], 100, '#0d6efd') ?></div>
                    <div class="sub-score mt-1">rata <?= round((float)$m['skor_rata'],1) ?></div>
                </td>
                <?php foreach (['best_bi','best_bing','best_mat','best_pu','best_wk'] as $mf): $mv = (float)($m[$mf]??0); ?>
                <td class="text-center" style="font-size:.78rem;">
                    <span style="color:<?= $mv>=61?'#0d6efd':'#dc3545'; ?>;font-weight:<?= $mv>0?'600':'400' ?>;">
                        <?= $mv > 0 ? number_format($mv,1) : '—' ?>
                    </span>
                </td>
                <?php endforeach; ?>
                <td class="text-center text-muted"><?= (int)$m['jumlah_tes'] ?>×</td>
                <?php else: ?>
                <td colspan="7" class="no-data-cell">—</td>
                <?php endif; ?>
                <?php endif; ?>

                <!-- ── SKD ── -->
                <?php if (in_array('skd',$f_kelompok)): ?>
                <?php if ($k): ?>
                <td class="text-center">
                    <?= skor_badge((float)$k['skor_terbaik'], 61, '#055160', '#cff4fc') ?>
                    <div class="mt-1"><?= skor_bar((float)$k['skor_terbaik'], 550, '#0dcaf0') ?></div>
                    <div class="sub-score mt-1">rata <?= round((float)$k['skor_rata'],1) ?></div>
                </td>
                <td class="text-center" style="font-size:.78rem;">
                    <span style="color:<?= (float)($k['best_twk']??0)>=65?'#055160':'#dc3545'; ?>;">
                        <?= number_format((float)($k['best_twk']??0),0) ?>
                        <div class="sub-score">min 65</div>
                    </span>
                </td>
                <td class="text-center" style="font-size:.78rem;">
                    <span style="color:<?= (float)($k['best_tiu']??0)>=80?'#055160':'#dc3545'; ?>;">
                        <?= number_format((float)($k['best_tiu']??0),0) ?>
                        <div class="sub-score">min 80</div>
                    </span>
                </td>
                <td class="text-center" style="font-size:.78rem;">
                    <span style="color:<?= (float)($k['best_tkp']??0)>=166?'#055160':'#dc3545'; ?>;">
                        <?= number_format((float)($k['best_tkp']??0),0) ?>
                        <div class="sub-score">min 166</div>
                    </span>
                </td>
                <td class="text-center text-muted"><?= (int)$k['jumlah_tes'] ?>×</td>
                <?php else: ?>
                <td colspan="5" class="no-data-cell">—</td>
                <?php endif; ?>
                <?php endif; ?>

                <!-- ── PSIKOLOGI ── -->
                <?php if (in_array('psikologi',$f_kelompok)): ?>
                <?php if ($p): ?>
                <td class="text-center">
                    <?= skor_badge((float)$p['skor_terbaik'], 61, '#6f42c1', '#e2d9f3') ?>
                    <div class="mt-1"><?= skor_bar((float)$p['skor_terbaik'], 100, '#6f42c1') ?></div>
                    <div class="sub-score mt-1">rata <?= round((float)$p['skor_rata'],1) ?></div>
                </td>
                <?php foreach (['best_kec','best_kcm','best_kpr'] as $pf): $pv = (float)($p[$pf]??0); ?>
                <td class="text-center" style="font-size:.78rem;">
                    <span style="color:<?= $pv>=61?'#6f42c1':'#dc3545'; ?>;font-weight:<?= $pv>0?'600':'400' ?>;">
                        <?= $pv > 0 ? number_format($pv,1) : '—' ?>
                    </span>
                </td>
                <?php endforeach; ?>
                <td class="text-center">
                    <?php $lulus_p = (int)($p['ada_lulus'] ?? 0); ?>
                    <span class="badge <?= $lulus_p ? 'bg-success' : 'bg-danger' ?>" style="font-size:.62rem;">
                        <?= $lulus_p ? 'Lulus' : 'Tidak Lulus' ?>
                    </span>
                </td>
                <?php else: ?>
                <td colspan="5" class="no-data-cell">—</td>
                <?php endif; ?>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<script>
// Toggle kelompok checkbox styling
function toggleKel(label, k) {
    const cb = label.querySelector('input');
    cb.checked = !cb.checked;
    label.classList.toggle('checked-' + k, cb.checked);
}

// Quick search in table
function quickSearchTable(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#rekapTable tbody tr.rekap-row').forEach(tr => {
        const text = tr.textContent.toLowerCase();
        tr.style.display = !q || text.includes(q) ? '' : 'none';
    });
}

// Build export link from current form values
function updateExportLink(a) {
    const f     = document.getElementById('formRekap');
    const data  = new FormData(f);
    const params = new URLSearchParams();
    params.set('page',   'nilai');
    params.set('action', 'export_rekap_nilai');
    for (const [k, v] of data.entries()) {
        if (k !== 'page' && k !== 'action') {
            params.append(k, v);
        }
    }
    a.href = 'index.php?' + params.toString();
    return true;
}

// Init export link on load
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('btnExport');
    if (btn) updateExportLink(btn);
});
</script>

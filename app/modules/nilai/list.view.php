<?php
/**
 * app/modules/nilai/list.view.php — Rekap 4 Kelompok Nilai
 * Tab: Jasmani | Mapel | SKD | Psikologi
 */

$tab_cfg = [
    'jasmani'   => ['icon'=>'bi-lightning-charge-fill','label'=>'Jasmani',       'color'=>'success',  'unit'=>'Skor'],
    'mapel'     => ['icon'=>'bi-book-fill',            'label'=>'Mapel',         'color'=>'primary',  'unit'=>'Rata-rata'],
    'skd'       => ['icon'=>'bi-clipboard2-check-fill','label'=>'SKD',           'color'=>'info',     'unit'=>'Total'],
    'psikologi' => ['icon'=>'bi-brain',                'label'=>'Psikologi',     'color'=>'purple',   'unit'=>'Rata-rata'],
];
$cfg      = $tab_cfg[$kelompok];
$base_params = array_filter(['page'=>'nilai','kelompok'=>$kelompok,'filter_tgl'=>$f_tgl,'filter_siswa'=>$f_nama,'per_page'=>$per_page!==25?$per_page:null]);

function predikat_badge_nilai(string $p): string {
    $m=['Baik Sekali'=>'bg-success','Baik'=>'bg-success bg-opacity-75','Cukup'=>'bg-warning text-dark','Kurang'=>'bg-orange','Kurang Sekali'=>'bg-danger'];
    $c=$m[$p]??'bg-secondary';
    return '<span class="badge '.$c.'" style="font-size:.7rem;">'.e($p).'</span>';
}
function status_badge(string $s): string {
    $ok=in_array($s,['Lulus']);
    return '<span class="badge '.($ok?'bg-success':'bg-danger').'" style="font-size:.68rem;">'.(($ok?'✓ ':'✗ ').e($s)).'</span>';
}
?>

<style>
.text-purple{color:#534AB7!important;}
.bg-orange{background:#fd7e14!important;}
.skor-mini-bar{width:50px;height:5px;background:#e9ecef;border-radius:3px;display:inline-block;vertical-align:middle;}
.skor-mini-fill{height:100%;border-radius:3px;}
</style>

<!-- Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Rekap Nilai Tryout</h4>
        <small class="text-muted">4 kelompok: Jasmani · Mapel · SKD · Psikologi — standar kelulusan min 61.</small>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?page=nilai&action=rekap_nilai" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-bar-chart-steps me-1"></i><span class="d-none d-sm-inline">Rangkuman</span>
        </a>
        <a href="index.php?page=nilai&action=import&kelompok=<?= e($kelompok) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-upload me-1"></i><span class="d-none d-sm-inline">Import Excel</span>
        </a>
        <a href="index.php?page=nilai&action=create&kelompok=<?= e($kelompok) ?>" class="btn btn-<?= $cfg['color'] === 'purple' ? 'primary' : $cfg['color'] ?>" id="btnNewEntry">
            <i class="bi <?= $cfg['icon'] ?> me-1"></i>Input <?= $cfg['label'] ?>
        </a>
    </div>
</div>

<!-- Summary Cards 4 Kelompok -->
<div class="row g-3 mb-4">
    <?php foreach ($tab_cfg as $k => $c): ?>
    <div class="col-6 col-md-3">
        <a href="?page=nilai&kelompok=<?= $k ?>" class="text-decoration-none">
            <div class="pk-card pk-stat-card h-100 <?= $kelompok===$k ? 'border border-2 border-'.$c['color'] : '' ?>">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="pk-stat-icon bg-<?= $c['color']==='purple'?'primary':$c['color'] ?> bg-opacity-10 text-<?= $c['color']==='purple'?'primary':$c['color'] ?>">
                        <i class="bi <?= $c['icon'] ?>"></i>
                    </div>
                    <?php if ($kelompok===$k): ?><span class="badge bg-<?= $c['color']==='purple'?'primary':$c['color'] ?>" style="font-size:.65rem;">Aktif</span><?php endif; ?>
                </div>
                <div class="mt-2">
                    <div class="fw-bold" style="font-size:1.2rem; line-height:1;"><?= number_format($stats[$k]) ?></div>
                    <div class="text-muted" style="font-size:.7rem;">Tes <?= $c['label'] ?></div>
                    <div class="text-muted" style="font-size:.68rem;">Rata-rata: <strong><?= $avg[$k] ?></strong></div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tab Navigator -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <?php foreach ($tab_cfg as $k => $c): ?>
    <a href="?page=nilai&kelompok=<?= $k ?>&per_page=<?= $per_page ?>"
       class="btn btn-sm <?= $kelompok===$k ? "btn-{$c['color']}" : 'btn-outline-secondary' ?> d-flex align-items-center gap-1">
        <i class="bi <?= $c['icon'] ?>"></i>
        <span><?= $c['label'] ?></span>
        <span class="badge bg-white text-dark ms-1 fw-bold" style="font-size:.65rem;"><?= number_format($stats[$k]) ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Filter -->
<div class="pk-card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="nilai">
        <input type="hidden" name="kelompok" value="<?= e($kelompok) ?>">
        <div class="col-md-2"><label class="form-label small fw-bold mb-1">Tanggal</label>
            <input type="date" name="filter_tgl" class="form-control form-control-sm" value="<?= e($f_tgl??'') ?>">
        </div>
        <div class="col-md-4"><label class="form-label small fw-bold mb-1">Cari Siswa</label>
            <input type="text" name="filter_siswa" class="form-control form-control-sm" value="<?= e($f_nama??'') ?>" placeholder="Nama atau NIS…">
        </div>
        <div class="col-auto d-flex gap-1">
            <button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
            <a href="?page=nilai&kelompok=<?= e($kelompok) ?>" class="btn btn-outline-secondary btn-sm" title="Reset"><i class="bi bi-x-lg"></i></a>
        </div>
    </form>
</div>

<!-- Tabel per Kelompok -->
<div class="pk-card overflow-hidden">
    <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            <?= $total > 0
                ? "Menampilkan <strong>".($offset+1)."–".min($offset+$per_page,$total)."</strong> dari <strong>".number_format($total)."</strong>"
                : "Tidak ada data" ?>
        </div>
        <select class="form-select form-select-sm" style="width:auto;"
                onchange="location.href='?<?= http_build_query(array_merge($base_params,['per_page'=>'__n__','halaman'=>1])) ?>'.replace('__n__',this.value)">
            <?php foreach([10,25,50,100] as $n): ?><option value="<?= $n ?>" <?= $per_page===$n?'selected':''?>><?= $n ?>/halaman</option><?php endforeach; ?>
        </select>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-dark">
                <tr>
                    <th class="ps-3">Tanggal</th>
                    <th>Peserta</th>
                    <?php if ($kelompok === 'jasmani'): ?>
                    <th class="text-center">Lari (m)</th>
                    <th class="text-center">Pull Up</th>
                    <th class="text-center">Sit Up</th>
                    <th class="text-center">Push Up</th>
                    <th class="text-center">Shuttle</th>
                    <th class="text-center">Lunges</th>
                    <th class="text-center">Renang</th>
                    <th class="text-center fw-bold" style="color:#198754;">Skor</th>
                    <th class="text-center">Nilai/Predikat</th>
                    <?php elseif ($kelompok === 'mapel'): ?>
                    <th class="text-center">B.Ind</th>
                    <th class="text-center">B.Ing</th>
                    <th class="text-center">Mat</th>
                    <th class="text-center">PU</th>
                    <th class="text-center">WK</th>
                    <th class="text-center fw-bold">Rata-rata</th>
                    <?php elseif ($kelompok === 'skd'): ?>
                    <th class="text-center">TWK</th>
                    <th class="text-center">TIU</th>
                    <th class="text-center">TKP</th>
                    <th class="text-center fw-bold">Total</th>
                    <th class="text-center">Status</th>
                    <?php else: /* psikologi */ ?>
                    <th class="text-center">Kecerdasan</th>
                    <th class="text-center">Kecermatan</th>
                    <th class="text-center">Kepribadian</th>
                    <th class="text-center fw-bold">Rata-rata</th>
                    <th class="text-center">Status</th>
                    <?php endif; ?>
                    <th class="text-center pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_nilai)): ?>
                <tr><td colspan="12">
                    <div class="py-5 text-center text-muted">
                        <i class="bi <?= $cfg['icon'] ?> d-block mb-2" style="font-size:2.5rem;opacity:.3;"></i>
                        <p class="mb-2 fw-bold">Belum ada data nilai <?= e($cfg['label']) ?></p>
                        <a href="?page=nilai&action=create&kelompok=<?= e($kelompok) ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil-square me-1"></i>Input Nilai Sekarang
                        </a>
                    </div>
                </td></tr>
                <?php else: foreach ($daftar_nilai as $n): ?>
                <tr>
                    <td class="ps-3 text-muted"><?= format_tanggal($n['tanggal_tes']) ?></td>
                    <td>
                        <a href="index.php?page=siswa&action=detail&id=<?= (int)$n['siswa_id'] ?>" class="fw-bold text-dark text-decoration-none d-block"><?= e($n['nama_lengkap']) ?></a>
                        <span class="text-muted" style="font-size:.68rem;"><?= e($n['nomor_induk']??'') ?></span>
                    </td>

                    <?php if ($kelompok === 'jasmani'): ?>
                    <?php
                    // Helper: tampil nilai mentah + skor konversi (jika ada)
                    $jc = function($raw, $skor, $unit='') {
                        $hasRaw  = $raw !== null && (float)$raw > 0;
                        $hasSkor = $skor !== null && (int)$skor > 0;
                        if (!$hasRaw) return '<span class="text-muted">—</span>';
                        $rawFmt  = number_format((float)$raw, fmod((float)$raw,1)!==0.0?1:0) . ($unit?' '.$unit:'');
                        $skorStr = $hasSkor
                            ? '<div style="font-size:.65rem;color:#198754;font-weight:700;">→'.(int)$skor.'</div>'
                            : '';
                        return '<div class="fw-bold">'.$rawFmt.'</div>'.$skorStr;
                    };
                    $skor  = (float)($n['total_skor'] ?? 0);
                    $lulus = $skor >= 41;
                    $pred  = $n['predikat'] ?? null;
                    $predColors = [
                        'Baik Sekali' => ['bg'=>'#d1e7dd','text'=>'#0a5c36'],
                        'Baik'        => ['bg'=>'#cfe2ff','text'=>'#084298'],
                        'Cukup'       => ['bg'=>'#fff3cd','text'=>'#664d03'],
                    ];
                    $pc = $predColors[$pred] ?? ['bg'=>'#e9ecef','text'=>'#555'];
                    ?>
                    <td class="text-center"><?= $jc($n['lari_jarak_meter'],   $n['skor_lari']    ?? null, 'm') ?></td>
                    <td class="text-center"><?= $jc($n['pullup_repetisi'],     $n['skor_pullup']  ?? null, '×') ?></td>
                    <td class="text-center"><?= $jc($n['situp_repetisi'],      $n['skor_situp']   ?? null, '×') ?></td>
                    <td class="text-center"><?= $jc($n['pushup_repetisi'],     $n['skor_pushup']  ?? null, '×') ?></td>
                    <td class="text-center"><?= $jc($n['shuttlerun_detik'],    $n['skor_shuttle'] ?? null, 's') ?></td>
                    <td class="text-center"><?= $jc($n['lunges_repetisi'],     $n['skor_lunges']  ?? null, '×') ?></td>
                    <td class="text-center"><?= $jc($n['renang_detik'],        $n['skor_renang']  ?? null, 's') ?></td>
                    <!-- Skor akhir -->
                    <td class="text-center">
                        <div class="fw-bold" style="font-size:1.1rem;color:<?= $lulus?'#198754':'#dc3545' ?>;">
                            <?= $skor > 0 ? number_format($skor,1) : '—' ?>
                        </div>
                        <?php if (!empty($n['nilai_ab']) || !empty($n['nilai_gabungan'])): ?>
                        <div style="font-size:.65rem;color:#888;">
                            <?php if (!empty($n['nilai_ab'])): ?>AB:<?= round((float)$n['nilai_ab'],1) ?><?php endif; ?>
                            <?php if (!empty($n['nilai_gabungan'])): ?>&nbsp;Gab:<?= round((float)$n['nilai_gabungan'],1) ?><?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <!-- Nilai / Predikat -->
                    <td class="text-center">
                        <?php if ($pred): ?>
                        <span class="badge fw-bold" style="background:<?= $pc['bg'] ?>;color:<?= $pc['text'] ?>;border:1px solid <?= $pc['text'] ?>;font-size:.68rem;">
                            <?= e($pred) ?>
                        </span>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        <?php if (!empty($n['nilai_samapta_a']) || !empty($n['nilai_samapta_b'])): ?>
                        <div style="font-size:.62rem;color:#aaa;margin-top:.15rem;">
                            <?php if (!empty($n['nilai_samapta_a'])): ?>A:<?= round((float)$n['nilai_samapta_a'],1) ?><?php endif; ?>
                            <?php if (!empty($n['nilai_samapta_b'])): ?>&nbsp;B:<?= round((float)$n['nilai_samapta_b'],1) ?><?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </td>

                    <?php elseif ($kelompok === 'mapel'): ?>
                    <?php
                    foreach(['b_indonesia','b_inggris','matematika','pu','wk'] as $mf):
                        $v=(float)($n[$mf]??0);$ok=$v>=61;
                    ?>
                    <td class="text-center">
                        <div class="fw-bold <?= $ok&&$v>0?'text-success':'text-danger' ?>"><?= $v>0?number_format($v,1):'—' ?></div>
                    </td>
                    <?php endforeach; ?>
                    <td class="text-center fw-bold text-primary" style="font-size:1.05rem;"><?= number_format((float)$n['rata_mapel'],1) ?></td>

                    <?php elseif ($kelompok === 'skd'): ?>
                    <?php
                    $skd_pg=['nilai_twk'=>65,'nilai_tiu'=>80,'nilai_tkp'=>166];
                    foreach($skd_pg as $sf=>$pg_min):
                        $v=(int)$n[$sf];$ok=$v>=$pg_min;$max=['nilai_twk'=>150,'nilai_tiu'=>175,'nilai_tkp'=>225][$sf];$pct=$max>0?round($v/$max*100):0;
                    ?>
                    <td class="text-center">
                        <div class="fw-bold <?= $ok?'text-success':'text-danger' ?>"><?= $v ?></div>
                        <div class="skor-mini-bar mt-1"><div class="skor-mini-fill" style="width:<?= $pct ?>%;background:<?= $ok?'#198754':'#dc3545' ?>;"></div></div>
                    </td>
                    <?php endforeach; ?>
                    <td class="text-center fw-bold text-primary" style="font-size:1.1rem;"><?= (int)$n['total_skor'] ?></td>
                    <td class="text-center"><?php $pg_total=(int)$n['total_skor']; echo status_badge($pg_total>=61?'Lulus':'Tidak Lulus'); ?></td>

                    <?php else: /* psikologi */ ?>
                    <?php foreach(['kecerdasan','kecermatan','kepribadian'] as $pf): $v=(float)$n[$pf];$ok=$v>=61; ?>
                    <td class="text-center">
                        <div class="fw-bold <?= $ok&&$v>0?'text-purple':'text-danger' ?>"><?= $v>0?number_format($v,1):'—' ?></div>
                    </td>
                    <?php endforeach; ?>
                    <td class="text-center fw-bold text-purple" style="font-size:1.05rem;"><?= number_format((float)$n['rata_psikologi'],1) ?></td>
                    <td class="text-center"><?= status_badge($n['status_psikologi']??'—') ?></td>
                    <?php endif; ?>

                    <td class="text-center pe-3">
                        <button class="btn btn-xs btn-outline-danger" style="padding:2px 7px;font-size:.72rem;"
                                onclick="pkConfirm('Hapus data nilai ini?', () => location.href='index.php?page=nilai&action=delete&id=<?= (int)$n['id'] ?>&kelompok=<?= e($kelompok) ?>&_csrf_token=<?= e(csrf_token()) ?>')">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($last_pg > 1): ?>
    <div class="px-3 py-2 border-top d-flex justify-content-between align-items-center">
        <small class="text-muted">Halaman <?= $halaman ?> / <?= $last_pg ?></small>
        <nav><ul class="pagination pagination-sm mb-0 gap-1">
            <li class="page-item <?= $halaman<=1?'disabled':'' ?>"><a class="page-link rounded-2" href="?<?= http_build_query(array_merge($base_params,['halaman'=>$halaman-1])) ?>">&laquo;</a></li>
            <?php $sp=max(1,$halaman-2);$ep=min($last_pg,$halaman+2);
            for($p=$sp;$p<=$ep;$p++): ?>
            <li class="page-item <?= $p===$halaman?'active':'' ?>"><a class="page-link rounded-2" href="?<?= http_build_query(array_merge($base_params,['halaman'=>$p])) ?>"><?= $p ?></a></li>
            <?php endfor; ?>
            <li class="page-item <?= $halaman>=$last_pg?'disabled':'' ?>"><a class="page-link rounded-2" href="?<?= http_build_query(array_merge($base_params,['halaman'=>$halaman+1])) ?>">&raquo;</a></li>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

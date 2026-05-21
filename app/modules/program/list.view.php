<?php /** app/modules/program/list.view.php */ ?>

<style>
.badge-tipe-Program   { background:#cfe2ff;color:#084298;border:1px solid #084298; }
.badge-tipe-Fasilitas { background:#fff3cd;color:#664d03;border:1px solid #b8860b; }
.badge-tipe-Paket     { background:#e2d9f3;color:#3d1a78;border:1px solid #6f42c1; }
.prog-icon { width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0; }
</style>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-collection-fill me-2 text-primary"></i>Manajemen Program</h4>
        <small class="text-muted">Kelola program reguler, fasilitas, dan paket bundel.</small>
    </div>
    <a href="index.php?page=program&action=create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah Program
    </a>
</div>

<!-- Filter tipe -->
<div class="pk-card p-3 mb-4">
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <span class="text-muted small fw-bold me-1">Filter:</span>
        <?php foreach ([''=>'Semua','Program'=>'Program','Fasilitas'=>'Fasilitas','Paket'=>'Paket'] as $v => $l): ?>
        <a href="index.php?page=program<?= $v ? '&tipe='.$v : '' ?>"
           class="btn btn-sm <?= $tipe_filter===$v ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <?= $l ?>
        </a>
        <?php endforeach; ?>
        <span class="ms-auto text-muted small"><?= count($daftar) ?> program</span>
    </div>
</div>

<?php if (empty($daftar)): ?>
<div class="pk-card text-center py-5">
    <i class="bi bi-collection d-block mb-2 text-muted" style="font-size:2.5rem;opacity:.3;"></i>
    <p class="text-muted mb-0">Belum ada program.</p>
</div>
<?php else: ?>

<?php
// Group by tipe
$grouped = [];
foreach ($daftar as $p) $grouped[$p['tipe_program']][] = $p;
$tipe_order = ['Program','Fasilitas','Paket'];
$tipe_cfg = [
    'Program'   => ['icon'=>'bi-calendar-check-fill','color'=>'#0d6efd','bg'=>'#cfe2ff','label'=>'Program Reguler'],
    'Fasilitas' => ['icon'=>'bi-house-fill','color'=>'#b8860b','bg'=>'#fff3cd','label'=>'Fasilitas'],
    'Paket'     => ['icon'=>'bi-box-seam-fill','color'=>'#6f42c1','bg'=>'#e2d9f3','label'=>'Paket Bundel'],
];
?>

<?php foreach ($tipe_order as $tipe):
    if (empty($grouped[$tipe])) continue;
    $cfg = $tipe_cfg[$tipe];
?>
<div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
        <div class="prog-icon" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>;">
            <i class="bi <?= $cfg['icon'] ?>"></i>
        </div>
        <h6 class="fw-bold mb-0" style="color:<?= $cfg['color'] ?>;"><?= $cfg['label'] ?></h6>
        <span class="badge rounded-pill" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>;border:1px solid <?= $cfg['color'] ?>;">
            <?= count($grouped[$tipe]) ?>
        </span>
    </div>

    <div class="pk-card overflow-hidden">
        <table class="table pk-table mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Nama Program</th>
                    <th>Harga Acuan / Bulan</th>
                    <th class="d-none d-md-table-cell">Deskripsi</th>
                    <?php if ($tipe === 'Paket'): ?><th>Komponen</th><?php endif; ?>
                    <th>Member Aktif</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($grouped[$tipe] as $p): ?>
            <tr>
                <td class="ps-3 fw-bold"><?= e($p['nama_program']) ?></td>
                <td>
                    <span class="fw-bold text-success"><?= format_rupiah((float)$p['biaya_bulanan']) ?></span>
                    <div class="text-muted" style="font-size:.7rem;">acuan — harga bisa diedit per siswa</div>
                </td>
                <td class="d-none d-md-table-cell text-muted small"><?= e($p['deskripsi']??'—') ?></td>
                <?php if ($tipe === 'Paket'): ?>
                <td>
                    <?php foreach (explode(', ', $p['komponen_paket']??'') as $k): if(!trim($k)) continue; ?>
                    <span class="badge bg-light text-dark border small d-inline-block me-1 mb-1"><?= e(trim($k)) ?></span>
                    <?php endforeach; ?>
                </td>
                <?php endif; ?>
                <td>
                    <span class="badge bg-primary bg-opacity-10 text-primary"><?= (int)$p['jml_member'] ?> siswa</span>
                </td>
                <td>
                    <span class="badge <?= $p['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                        <?= $p['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                </td>
                <td class="text-end pe-3">
                    <a href="index.php?page=program&action=edit&id=<?= (int)$p['id'] ?>"
                       class="btn btn-xs btn-outline-primary me-1">Edit</a>
                    <?php if (!$p['jml_member']): ?>
                    <a href="index.php?page=program&action=delete&id=<?= (int)$p['id'] ?>"
                       class="btn btn-xs btn-outline-danger"
                       onclick="return confirm('Nonaktifkan program ini?')">Hapus</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

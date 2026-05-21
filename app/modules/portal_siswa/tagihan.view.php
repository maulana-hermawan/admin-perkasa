<?php /** portal_siswa/tagihan.view.php */ ?>

<h5 class="fw-bold mb-3">Tagihan SPP</h5>

<!-- Summary -->
<div class="row g-2 mb-3">
    <div class="col-4 text-center pk-mobile-card py-3">
        <div class="text-muted" style="font-size:.62rem;font-weight:700;text-transform:uppercase;">Total Tagihan</div>
        <div class="fw-bold" style="font-size:.9rem;"><?= format_rupiah($total_tagihan) ?></div>
    </div>
    <div class="col-4 text-center pk-mobile-card py-3">
        <div class="text-muted" style="font-size:.62rem;font-weight:700;text-transform:uppercase;">Dibayar</div>
        <div class="fw-bold text-success" style="font-size:.9rem;"><?= format_rupiah($total_bayar) ?></div>
    </div>
    <div class="col-4 text-center pk-mobile-card py-3">
        <div class="text-muted" style="font-size:.62rem;font-weight:700;text-transform:uppercase;">Sisa</div>
        <div class="fw-bold <?= $total_sisa > 0 ? 'text-danger' : 'text-success' ?>" style="font-size:.9rem;"><?= format_rupiah($total_sisa) ?></div>
    </div>
</div>

<?php if (empty($tagihan_list)): ?>
<div class="pk-mobile-card text-center py-4">
    <i class="bi bi-receipt fs-2 d-block mb-2 opacity-25 text-muted"></i>
    <p class="text-muted small mb-0">Belum ada data tagihan.</p>
</div>
<?php else: foreach ($tagihan_list as $t):
    $sisa = (float)$t['nominal_tagihan'] - (float)$t['nominal_bayar'];
    $badge = match($t['status_bayar']) {
        'Lunas'   => ['bg-success','Lunas'],
        'Cicilan' => ['bg-warning text-dark','Cicilan'],
        default   => ['bg-danger','Belum'],
    };
?>
<div class="pk-mobile-card mb-2">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <div class="fw-bold small"><?= date('F Y', strtotime($t['periode_bulan'])) ?></div>
            <div class="text-muted" style="font-size:.7rem;">Tagihan: <?= format_rupiah((float)$t['nominal_tagihan']) ?></div>
        </div>
        <span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span>
    </div>
    <?php if ($t['nominal_bayar'] > 0): ?>
    <div class="mt-2 d-flex justify-content-between small">
        <span class="text-muted">Dibayar <?= $t['tanggal_bayar'] ? date('d M Y',strtotime($t['tanggal_bayar'])) : '' ?></span>
        <span class="text-success fw-bold"><?= format_rupiah((float)$t['nominal_bayar']) ?></span>
    </div>
    <?php endif; ?>
    <?php if ($sisa > 0): ?>
    <div class="d-flex justify-content-between small mt-1">
        <span class="text-muted">Sisa</span>
        <span class="text-danger fw-bold"><?= format_rupiah($sisa) ?></span>
    </div>
    <?php endif; ?>
    <?php if ($t['metode_pembayaran']): ?>
    <div class="text-muted mt-1" style="font-size:.65rem;">via <?= e($t['metode_pembayaran']) ?></div>
    <?php endif; ?>
</div>
<?php endforeach; endif; ?>

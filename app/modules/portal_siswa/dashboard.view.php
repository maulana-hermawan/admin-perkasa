<?php /** portal_siswa/dashboard.view.php */ ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Halo, <?= e(explode(' ', $siswa['nama_lengkap'])[0]) ?> 👋</h5>
    <span class="text-muted small"><?= date('d M Y') ?></span>
</div>

<!-- Membership Card -->
<?php if ($membership): ?>
<div class="pk-mobile-card mb-3" style="background: linear-gradient(135deg, #198754 0%, #0f5c38 100%); color: #fff; border: none;">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <div style="font-size:.68rem; opacity:.8; font-weight:700; text-transform:uppercase;">Program Aktif</div>
            <div class="fw-bold" style="font-size:1.1rem;"><?= e($membership['nama_program']) ?></div>
        </div>
        <span class="badge bg-white text-success" style="font-size:.7rem;">Aktif</span>
    </div>
    <?php if ($sisa_hari !== null): ?>
    <div class="mt-3 d-flex justify-content-between align-items-end">
        <div style="font-size:.8rem; opacity:.85;">
            Berakhir: <strong><?= e(date('d M Y', strtotime($membership['tanggal_selesai_aktif']))) ?></strong>
        </div>
        <div class="text-end">
            <div style="font-size:1.6rem; font-weight:700; line-height:1;"><?= max(0,$sisa_hari) ?></div>
            <div style="font-size:.65rem; opacity:.8;">hari lagi</div>
        </div>
    </div>
    <?php if ($sisa_hari <= 7 && $sisa_hari > 0): ?>
    <div class="alert alert-warning py-1 px-2 mt-2 mb-0" style="font-size:.72rem;">
        ⚠️ Membership hampir berakhir. Segera hubungi admin untuk perpanjangan.
    </div>
    <?php elseif ($sisa_hari <= 0): ?>
    <div class="alert alert-danger py-1 px-2 mt-2 mb-0" style="font-size:.72rem;">
        ❌ Membership sudah berakhir. Hubungi admin sekarang.
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="pk-mobile-card mb-3 text-center py-3">
    <i class="bi bi-exclamation-circle-fill text-warning d-block fs-2 mb-2"></i>
    <p class="text-muted small mb-1">Tidak ada program aktif.</p>
    <small class="text-muted">Hubungi admin untuk pendaftaran program.</small>
</div>
<?php endif; ?>

<!-- Quick stats -->
<div class="row g-2 mb-3">
    <?php if ($tagihan_belum > 0): ?>
    <div class="col-12">
        <a href="portal-siswa.php?page=tagihan" class="pk-mobile-card d-flex gap-3 align-items-center text-decoration-none text-dark p-3">
            <i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i>
            <div class="flex-grow-1">
                <div class="fw-bold small">Ada <?= $tagihan_belum ?> tagihan belum lunas</div>
                <div class="text-muted" style="font-size:.72rem;">Klik untuk lihat detail</div>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Jadwal Mendatang -->
<div class="pk-mobile-card mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold mb-0"><i class="bi bi-calendar-event me-2 text-primary"></i>Jadwal Mendatang</h6>
        <a href="portal-siswa.php?page=jadwal" class="small text-primary text-decoration-none">Semua →</a>
    </div>
    <?php if (empty($jadwal_mendatang)): ?>
    <div class="text-muted text-center py-3 small">
        <i class="bi bi-calendar-check d-block fs-2 mb-1 opacity-25"></i>Tidak ada jadwal mendatang.
    </div>
    <?php else: foreach ($jadwal_mendatang as $j): ?>
    <div class="d-flex gap-3 border-bottom py-2 align-items-start">
        <div class="text-center rounded" style="min-width:40px; background:var(--color-background-secondary); padding:4px;">
            <div class="fw-bold" style="font-size:.85rem;"><?= date('d',strtotime($j['tanggal'])) ?></div>
            <div class="text-muted" style="font-size:.62rem;"><?= date('M',strtotime($j['tanggal'])) ?></div>
        </div>
        <div>
            <div class="fw-bold small"><?= e($j['nama_kegiatan']??'') ?></div>
            <div class="text-muted" style="font-size:.7rem;"><?= substr($j['waktu_mulai'],0,5) ?>–<?= substr($j['waktu_selesai'],0,5) ?> · <?= e($j['nama_program']??'') ?></div>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<!-- Nilai terakhir -->
<?php if ($nilai_terakhir): ?>
<div class="pk-mobile-card">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-fill me-2 text-success"></i>Nilai Terakhir (Jasmani)</h6>
        <a href="portal-siswa.php?page=nilai" class="small text-primary text-decoration-none">Detail →</a>
    </div>
    <div class="d-flex gap-3 flex-wrap">
        <?php
        $items = ['lari_jarak_meter'=>'Lari (m)','pullup_repetisi'=>'Pull Up','situp_repetisi'=>'Sit Up','pushup_repetisi'=>'Push Up','shuttlerun_detik'=>'Shuttle (dtk)'];
        foreach ($items as $col => $lbl):
            if ($nilai_terakhir[$col] ?? false):
        ?>
        <div class="text-center">
            <div class="fw-bold"><?= (float)$nilai_terakhir[$col] ?></div>
            <div class="text-muted" style="font-size:.65rem;"><?= $lbl ?></div>
        </div>
        <?php endif; endforeach; ?>
    </div>
    <div class="text-muted mt-1" style="font-size:.68rem;">
        <?= format_tanggal($nilai_terakhir['tanggal_tes'],'d M Y') ?>
    </div>
</div>
<?php endif; ?>

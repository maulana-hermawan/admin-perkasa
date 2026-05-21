<?php /** portal_tutor/dashboard.view.php */ ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Selamat Datang 👋</h5>
    <span class="text-muted small"><?= date('d M Y') ?></span>
</div>

<!-- Stat mini -->
<div class="row g-2 mb-3">
    <div class="col-6">
        <div class="pk-mobile-card text-center">
            <div class="text-muted" style="font-size:.65rem;font-weight:700;text-transform:uppercase;">Sesi Bulan Ini</div>
            <div class="fw-bold" style="font-size:1.8rem;"><?= $sesi_bulan ?></div>
        </div>
    </div>
    <div class="col-6">
        <div class="pk-mobile-card text-center">
            <div class="text-muted" style="font-size:.65rem;font-weight:700;text-transform:uppercase;">Estimasi Gaji</div>
            <div class="fw-bold text-success" style="font-size:1rem;"><?= $gaji_bulan > 0 ? format_rupiah($gaji_bulan) : '—' ?></div>
        </div>
    </div>
</div>

<!-- Jadwal Hari Ini -->
<div class="pk-mobile-card mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold mb-0"><i class="bi bi-calendar-day me-2 text-primary"></i>Jadwal Hari Ini</h6>
        <a href="portal-tutor.php?page=jadwal" class="small text-primary text-decoration-none">Lihat semua →</a>
    </div>
    <?php if (empty($jadwal_hari_ini)): ?>
    <div class="text-muted text-center py-3" style="font-size:.85rem;">
        <i class="bi bi-calendar-check d-block fs-2 mb-2 opacity-25"></i>
        Tidak ada jadwal hari ini.
    </div>
    <?php else: foreach ($jadwal_hari_ini as $j): ?>
    <div class="d-flex gap-3 align-items-start border-bottom py-2">
        <div class="text-center" style="min-width:42px;">
            <div class="fw-bold text-primary" style="font-size:.85rem;"><?= substr($j['waktu_mulai'],0,5) ?></div>
            <div class="text-muted" style="font-size:.68rem;"><?= substr($j['waktu_selesai'],0,5) ?></div>
        </div>
        <div class="flex-grow-1">
            <div class="fw-bold small"><?= e($j['nama_kegiatan']??'') ?></div>
            <div class="text-muted" style="font-size:.72rem;"><?= e($j['nama_program']??'') ?> <?= $j['lokasi']?'· '.e($j['lokasi']):'' ?></div>
        </div>
        <a href="portal-tutor.php?page=attendance&jadwal_id=<?= (int)$j['id'] ?>"
           class="btn btn-sm btn-primary" style="font-size:.72rem;">
            Absen
        </a>
    </div>
    <?php endforeach; endif; ?>
</div>

<!-- Jadwal Mendatang -->
<?php if (!empty($jadwal_upcoming)): ?>
<div class="pk-mobile-card">
    <h6 class="fw-bold mb-2"><i class="bi bi-calendar-week me-2 text-secondary"></i>7 Hari Ke Depan</h6>
    <?php foreach ($jadwal_upcoming as $j): ?>
    <div class="d-flex gap-2 align-items-center border-bottom py-2">
        <div class="text-center" style="min-width:42px;">
            <div class="fw-bold" style="font-size:.8rem;"><?= date('d',strtotime($j['tanggal'])) ?></div>
            <div class="text-muted" style="font-size:.65rem;"><?= date('M',strtotime($j['tanggal'])) ?></div>
        </div>
        <div class="flex-grow-1">
            <div class="small fw-bold"><?= e($j['nama_kegiatan']??'') ?></div>
            <div class="text-muted" style="font-size:.7rem;"><?= substr($j['waktu_mulai'],0,5) ?> · <?= e($j['nama_program']??'') ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

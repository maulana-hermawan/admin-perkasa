<?php /** portal_siswa/jadwal.view.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Jadwal</h5>
    <?php if ($total_count > 0): ?>
    <span class="badge bg-success"><?= $hadir_count ?>/<?= $total_count ?> hadir</span>
    <?php endif; ?>
</div>

<form method="GET" class="d-flex gap-2 mb-3">
    <input type="hidden" name="page" value="jadwal">
    <input type="month" name="bulan" class="form-control form-control-sm flex-grow-1" value="<?= e($f_bulan) ?>">
    <button class="btn btn-success btn-sm px-3">Tampilkan</button>
</form>

<?php if (empty($jadwal_list)): ?>
<div class="pk-mobile-card text-center py-4">
    <i class="bi bi-calendar-x fs-2 d-block mb-2 opacity-25 text-muted"></i>
    <p class="text-muted small mb-0">Tidak ada jadwal di <?= e(date('F Y',strtotime($f_bulan.'-01'))) ?>.</p>
</div>
<?php else:
    $by_date = [];
    foreach ($jadwal_list as $j) $by_date[$j['tanggal']][] = $j;
    foreach ($by_date as $tgl => $sesi):
?>
<div class="mb-2">
    <div class="text-muted fw-bold mb-1" style="font-size:.68rem;text-transform:uppercase;">
        <?= date('l, d M Y', strtotime($tgl)) ?>
    </div>
    <?php foreach ($sesi as $j):
        $status_color = match($j['status_hadir'] ?? '') {
            'Hadir'  => 'success',
            'Izin'   => 'info',
            'Sakit'  => 'warning',
            'Alpa'   => 'danger',
            default  => 'secondary',
        };
    ?>
    <div class="pk-mobile-card d-flex gap-3 align-items-start p-3 mb-1">
        <div class="text-center" style="min-width:40px;">
            <div class="fw-bold text-success small"><?= substr($j['waktu_mulai'],0,5) ?></div>
            <div class="text-muted" style="font-size:.62rem;"><?= substr($j['waktu_selesai'],0,5) ?></div>
        </div>
        <div class="flex-grow-1">
            <div class="fw-bold small"><?= e($j['nama_kegiatan']??'') ?></div>
            <div class="text-muted" style="font-size:.7rem;"><?= e($j['nama_program']??'') ?><?= $j['lokasi']?' · '.e($j['lokasi']):'' ?></div>
        </div>
        <?php if ($j['status_hadir']): ?>
        <span class="badge bg-<?= $status_color ?>" style="font-size:.65rem;"><?= $j['status_hadir'] ?></span>
        <?php elseif ($tgl >= date('Y-m-d')): ?>
        <span class="badge bg-light text-muted border" style="font-size:.65rem;">Belum</span>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; endif; ?>

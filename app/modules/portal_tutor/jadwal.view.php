<?php /** portal_tutor/jadwal.view.php */ ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Jadwal Saya</h5>
</div>

<form method="GET" class="d-flex gap-2 mb-3">
    <input type="hidden" name="page" value="jadwal">
    <?php if(isset($_GET['tutor_id'])): ?><input type="hidden" name="tutor_id" value="<?= (int)$_GET['tutor_id'] ?>"><?php endif; ?>
    <input type="month" name="bulan" class="form-control form-control-sm flex-grow-1" value="<?= e($f_bulan) ?>">
    <button class="btn btn-primary btn-sm px-3">Tampilkan</button>
</form>

<?php if (empty($jadwal_list)): ?>
<div class="pk-mobile-card text-center py-4">
    <i class="bi bi-calendar-x fs-2 d-block mb-2 text-muted opacity-25"></i>
    <p class="text-muted small mb-0">Tidak ada jadwal di <?= e(date('F Y',strtotime($f_bulan.'-01'))) ?>.</p>
</div>
<?php else:
    $by_date = [];
    foreach($jadwal_list as $j) $by_date[$j['tanggal']][] = $j;
    foreach($by_date as $tgl => $sesi):
?>
<div class="mb-3">
    <div class="text-muted fw-bold mb-1" style="font-size:.7rem;text-transform:uppercase;">
        <?= date('l, d M Y', strtotime($tgl)) ?>
    </div>
    <?php foreach($sesi as $j): ?>
    <div class="pk-mobile-card d-flex gap-3 align-items-start p-3">
        <div class="text-center" style="min-width:44px;">
            <div class="fw-bold text-primary small"><?= substr($j['waktu_mulai'],0,5) ?></div>
            <div class="text-muted" style="font-size:.65rem;"><?= substr($j['waktu_selesai'],0,5) ?></div>
        </div>
        <div class="flex-grow-1">
            <div class="fw-bold small"><?= e($j['nama_kegiatan']??'') ?></div>
            <div class="text-muted" style="font-size:.72rem;"><?= e($j['nama_program']??'') ?></div>
            <?php if($j['materi']): ?><div class="text-muted" style="font-size:.7rem;"><?= e($j['materi']) ?></div><?php endif; ?>
            <?php if($j['lokasi']): ?><div style="font-size:.68rem;"><i class="bi bi-geo-alt text-muted"></i> <?= e($j['lokasi']) ?></div><?php endif; ?>
        </div>
        <?php if($tgl <= date('Y-m-d')): ?>
        <a href="portal-tutor.php?page=attendance&jadwal_id=<?= (int)$j['id'] ?><?= isset($_GET['tutor_id'])?'&tutor_id='.(int)$_GET['tutor_id']:'' ?>"
           class="btn btn-sm btn-outline-primary">Absen</a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; endif; ?>

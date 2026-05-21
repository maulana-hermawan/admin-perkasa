<?php /** portal_tutor/att_list.view.php */ ?>
<h5 class="fw-bold mb-3">Isi Absensi</h5>
<p class="text-muted small">Pilih jadwal yang akan diisi absensi (14 hari terakhir):</p>
<?php if(empty($jadwal_list)): ?>
<div class="pk-mobile-card text-center py-4"><i class="bi bi-calendar-x fs-2 d-block mb-2 opacity-25 text-muted"></i><p class="text-muted small mb-0">Tidak ada jadwal yang perlu diisi.</p></div>
<?php else: foreach($jadwal_list as $j): ?>
<a href="portal-tutor.php?page=attendance&jadwal_id=<?= (int)$j['id'] ?><?= isset($_GET['tutor_id'])?'&tutor_id='.(int)$_GET['tutor_id']:'' ?>"
   class="pk-mobile-card d-flex gap-3 align-items-center text-decoration-none text-dark mb-2 p-3">
    <div class="text-center" style="min-width:44px;">
        <div class="fw-bold"><?= date('d',strtotime($j['tanggal'])) ?></div>
        <div class="text-muted" style="font-size:.65rem;"><?= date('M',strtotime($j['tanggal'])) ?></div>
    </div>
    <div class="flex-grow-1">
        <div class="fw-bold small"><?= e($j['nama_kegiatan']??'') ?></div>
        <div class="text-muted" style="font-size:.7rem;"><?= substr($j['waktu_mulai'],0,5) ?> · <?= e($j['nama_program']??'') ?></div>
    </div>
    <?php if((int)$j['sudah']>0): ?>
    <span class="badge bg-success"><?= (int)$j['sudah'] ?> dicatat</span>
    <?php else: ?><span class="badge bg-warning text-dark">Belum</span><?php endif; ?>
    <i class="bi bi-chevron-right text-muted"></i>
</a>
<?php endforeach; endif; ?>

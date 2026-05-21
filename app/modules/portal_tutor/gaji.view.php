<?php /** portal_tutor/gaji.view.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Slip Gaji</h5>
</div>
<form method="GET" class="d-flex gap-2 mb-3">
    <input type="hidden" name="page" value="gaji">
    <?php if(isset($_GET['tutor_id'])): ?><input type="hidden" name="tutor_id" value="<?= (int)$_GET['tutor_id'] ?>"><?php endif; ?>
    <input type="month" name="bulan" class="form-control form-control-sm flex-grow-1" value="<?= e($f_bulan) ?>">
    <button class="btn btn-primary btn-sm px-3">Tampilkan</button>
</form>

<div class="pk-mobile-card mb-3">
    <div class="d-flex justify-content-between mb-3">
        <div><div class="text-muted" style="font-size:.68rem;text-transform:uppercase;font-weight:700;">Periode</div>
        <div class="fw-bold"><?= date('F Y',strtotime($f_bulan.'-01')) ?></div></div>
        <div class="text-end"><div class="text-muted" style="font-size:.68rem;text-transform:uppercase;font-weight:700;">Tarif/Sesi</div>
        <div class="fw-bold text-primary"><?= $tarif>0?format_rupiah($tarif):'Belum diset' ?></div></div>
    </div>
    <div class="row g-2 text-center mb-3">
        <div class="col-6 bg-light rounded p-2">
            <div class="text-muted" style="font-size:.68rem;">TOTAL SESI</div>
            <div class="fw-bold fs-4"><?= $jumlah_sesi ?></div>
        </div>
        <div class="col-6 bg-success bg-opacity-10 rounded p-2">
            <div class="text-muted" style="font-size:.68rem;">ESTIMASI GAJI</div>
            <div class="fw-bold text-success" style="font-size:1.1rem;"><?= $total_gaji>0?format_rupiah($total_gaji):'—' ?></div>
        </div>
    </div>
    <?php if($tarif<=0): ?><div class="alert alert-warning py-2 small mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Tarif per sesi belum diset. Hubungi admin.</div><?php endif; ?>
</div>

<?php if(!empty($sesi_list)): ?>
<div class="pk-mobile-card">
    <h6 class="fw-bold mb-2">Detail Sesi</h6>
    <?php foreach($sesi_list as $s): ?>
    <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">
        <div>
            <div class="fw-bold"><?= format_tanggal($s['tanggal'],'d M Y') ?></div>
            <div class="text-muted" style="font-size:.7rem;"><?= e($s['nama_kegiatan']??'') ?> · <?= substr($s['waktu_mulai'],0,5) ?>–<?= substr($s['waktu_selesai'],0,5) ?></div>
        </div>
        <div class="text-end text-success fw-bold"><?= $tarif>0?format_rupiah($tarif):'—' ?></div>
    </div>
    <?php endforeach; ?>
    <?php if($total_gaji>0): ?>
    <div class="d-flex justify-content-between fw-bold pt-2">
        <span>TOTAL</span><span class="text-danger"><?= format_rupiah($total_gaji) ?></span>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

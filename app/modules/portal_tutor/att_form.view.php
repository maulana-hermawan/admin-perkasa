<?php /** portal_tutor/att_form.view.php */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold mb-0">Absensi</h5>
        <small class="text-muted"><?= e($jadwal['nama_kegiatan']??'') ?> · <?= format_tanggal($jadwal['tanggal'],'d M Y') ?></small>
    </div>
    <a href="portal-tutor.php?page=attendance<?= isset($_GET['tutor_id'])?'&tutor_id='.(int)$_GET['tutor_id']:'' ?>" class="btn btn-sm btn-outline-secondary">← Kembali</a>
</div>

<!-- Quick mark -->
<div class="d-flex gap-2 mb-3 flex-wrap">
    <span class="text-muted small align-self-center">Tandai semua:</span>
    <?php foreach(['Hadir'=>'success','Izin'=>'info','Sakit'=>'warning','Alpa'=>'danger'] as $st=>$cl): ?>
    <button type="button" class="btn btn-sm btn-<?= $cl ?>" onclick="markAll('<?= $st ?>')"><?= $st ?></button>
    <?php endforeach; ?>
</div>

<form method="POST" action="portal-tutor.php?page=attendance&action=store_att<?= isset($_GET['tutor_id'])?'&tutor_id='.(int)$_GET['tutor_id']:'' ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="jadwal_id" value="<?= (int)$jadwal['id'] ?>">

    <?php foreach($daftar_siswa as $s):
        $cur = $s['status_hadir'] ?? 'Hadir';
        $colors = ['Hadir'=>'success','Izin'=>'info','Sakit'=>'warning','Alpa'=>'danger'];
    ?>
    <div class="pk-mobile-card p-3 mb-2" id="card-<?= (int)$s['id'] ?>">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <div class="fw-bold small"><?= e($s['nama_lengkap']) ?></div>
                <div class="text-muted" style="font-size:.7rem;"><?= e($s['nomor_induk']??'') ?></div>
            </div>
            <input type="hidden" name="hadir[<?= (int)$s['id'] ?>]" id="inp-<?= (int)$s['id'] ?>" value="<?= e($cur) ?>">
        </div>
        <div class="d-flex gap-1 flex-wrap" id="grp-<?= (int)$s['id'] ?>">
            <?php foreach($colors as $st=>$cl):
                $active = $cur===$st?'':'outline-';
            ?>
            <button type="button"
                    class="btn btn-sm btn-<?= $active ?><?= $cl ?> att-btn flex-fill"
                    data-siswa="<?= (int)$s['id'] ?>"
                    data-status="<?= $st ?>"
                    onclick="setStatus(<?= (int)$s['id'] ?>,'<?= $st ?>')">
                <?= $st ?>
            </button>
            <?php endforeach; ?>
        </div>
        <input type="text" name="keterangan[<?= (int)$s['id'] ?>]"
               class="form-control form-control-sm mt-2 keterangan-input"
               placeholder="Keterangan (jika Izin/Sakit/Alpa)"
               value="<?= e($s['att_ket']??'') ?>"
               <?= in_array($cur,['Hadir'])&&!$s['att_ket']?'style="display:none;"':'' ?>>
    </div>
    <?php endforeach; ?>

    <div class="d-grid mt-3 mb-4">
        <button type="submit" class="btn btn-primary btn-lg fw-bold">
            <i class="bi bi-save-fill me-2"></i>Simpan Absensi
        </button>
    </div>
</form>

<script>
const BTN_C={Hadir:'success',Izin:'info',Sakit:'warning',Alpa:'danger'};
function setStatus(id,status){
    document.getElementById('inp-'+id).value=status;
    document.getElementById('grp-'+id).querySelectorAll('.att-btn').forEach(b=>{
        const s=b.dataset.status,c=BTN_C[s];
        b.className=`btn btn-sm btn-${s===status?'':'outline-'}${c} att-btn flex-fill`;
    });
    const card=document.getElementById('card-'+id);
    card.style.background=status==='Hadir'?'rgba(25,135,84,.06)':status==='Alpa'?'rgba(220,53,69,.06)':status==='Izin'?'rgba(13,202,240,.06)':'rgba(255,193,7,.06)';
    // Show/hide keterangan
    const ket=card.querySelector('.keterangan-input');
    if(ket) ket.style.display=(status==='Hadir')?'none':'';
}
function markAll(status){
    document.querySelectorAll('[id^="inp-"]').forEach(inp=>{setStatus(parseInt(inp.id.replace('inp-','')),status);});
}
</script>

<?php /** app/modules/attendance/form.view.php */ ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Form Absensi</h4>
        <small class="text-muted">
            <?= e($jadwal['nama_kegiatan']??'') ?> —
            <?= format_tanggal($jadwal['tanggal'],'d M Y') ?>
            <?= substr($jadwal['waktu_mulai'],0,5) ?>–<?= substr($jadwal['waktu_selesai'],0,5) ?>
            <?php if($jadwal['lokasi']): ?> · <?= e($jadwal['lokasi']) ?><?php endif; ?>
        </small>
    </div>
    <a href="index.php?page=attendance" class="btn btn-outline-secondary btn-sm">← Kembali</a>
</div>

<!-- Stat chips -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <?php
    $chip_colors = ['Hadir'=>'success','Izin'=>'info','Sakit'=>'warning','Alpa'=>'danger','Belum'=>'secondary'];
    foreach ($stats as $st => $cnt): if ($cnt === 0) continue; ?>
    <span class="badge bg-<?= $chip_colors[$st]??'secondary' ?> fs-6 px-3">
        <?= $st ?>: <?= $cnt ?>
    </span>
    <?php endforeach; ?>
    <span class="badge bg-light text-dark border fs-6 px-3">Total: <?= count($daftar_siswa) ?></span>
</div>

<!-- Quick mark all -->
<div class="pk-card p-3 mb-3 d-flex flex-wrap gap-2 align-items-center">
    <span class="small fw-bold text-muted me-2">Tandai semua:</span>
    <?php foreach(['Hadir','Izin','Sakit','Alpa'] as $st): ?>
    <button type="button" class="btn btn-sm btn-outline-secondary"
            onclick="markAll('<?= $st ?>')">Semua <?= $st ?></button>
    <?php endforeach; ?>
</div>

<form method="POST" action="index.php?page=attendance&action=store_bulk&jadwal_id=<?= (int)$jadwal['id'] ?>">
    <?= csrf_field() ?>

    <div class="pk-card overflow-hidden">
        <div class="table-responsive">
            <table class="table pk-table align-middle mb-0 small">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3" style="width:40px;">#</th>
                        <th>Siswa</th>
                        <th style="min-width:240px;">Status Kehadiran</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftar_siswa)): ?>
                    <tr><td colspan="4">
                        <div class="pk-empty-state"><i class="bi bi-people"></i>
                        <small>Belum ada siswa aktif terdaftar.</small></div>
                    </td></tr>
                    <?php else: foreach ($daftar_siswa as $idx => $s):
                        $current_status = $s['status_hadir'] ?? 'Hadir';
                    ?>
                    <tr id="row-<?= (int)$s['id'] ?>">
                        <td class="ps-3 text-muted"><?= $idx+1 ?></td>
                        <td>
                            <div class="fw-bold"><?= e($s['nama_lengkap']) ?></div>
                            <div class="text-muted" style="font-size:.7rem;"><?= e($s['nomor_induk']??'') ?></div>
                        </td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap" id="btnGroup-<?= (int)$s['id'] ?>">
                                <?php
                                $btn_cfg = [
                                    'Hadir' => 'success',
                                    'Izin'  => 'info',
                                    'Sakit' => 'warning',
                                    'Alpa'  => 'danger',
                                ];
                                foreach ($btn_cfg as $st => $col):
                                    $active = $current_status === $st ? '' : 'outline-';
                                ?>
                                <button type="button"
                                        class="btn btn-sm btn-<?= $active ?><?= $col ?> att-btn"
                                        data-siswa="<?= (int)$s['id'] ?>"
                                        data-status="<?= $st ?>"
                                        onclick="setStatus(<?= (int)$s['id'] ?>,'<?= $st ?>')">
                                    <?= $st ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="hadir[<?= (int)$s['id'] ?>]"
                                   id="inp-<?= (int)$s['id'] ?>"
                                   value="<?= e($current_status) ?>">
                        </td>
                        <td>
                            <input type="text" name="keterangan[<?= (int)$s['id'] ?>]"
                                   class="form-control form-control-sm"
                                   placeholder="Alasan / catatan (opsional)"
                                   value="<?= e($s['att_ket']??'') ?>"
                                   style="min-width:160px;">
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save-fill me-2"></i>Simpan Absensi
        </button>
        <a href="index.php?page=attendance" class="btn btn-outline-secondary">Kembali</a>
    </div>
</form>

<script>
const BTN_COLORS = {Hadir:'success',Izin:'info',Sakit:'warning',Alpa:'danger'};

function setStatus(siswaId, status) {
    document.getElementById('inp-'+siswaId).value = status;
    const group = document.getElementById('btnGroup-'+siswaId);
    group.querySelectorAll('.att-btn').forEach(btn => {
        const s = btn.dataset.status;
        const c = BTN_COLORS[s];
        btn.className = `btn btn-sm btn-${s===status ? '' : 'outline-'}${c} att-btn`;
    });
    // Visual row highlight
    const row = document.getElementById('row-'+siswaId);
    row.style.background = status==='Hadir'?'rgba(25,135,84,.06)':
                           status==='Alpa'?'rgba(220,53,69,.06)':
                           status==='Izin'?'rgba(13,202,240,.06)':'rgba(255,193,7,.06)';
}

function markAll(status) {
    document.querySelectorAll('[id^="inp-"]').forEach(inp => {
        const id = inp.id.replace('inp-','');
        setStatus(parseInt(id), status);
    });
}
</script>

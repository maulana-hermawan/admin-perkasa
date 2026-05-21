<?php
/** app/modules/program/form.view.php */
$is_edit  = isset($item) && !empty($item) && !empty($item['id']);
$i        = ($is_edit ? $item : null) ?? [];
$form_act = $is_edit
    ? 'index.php?page=program&action=update&id='.(int)($i['id']??0)
    : 'index.php?page=program&action=store';
$daftar_program  = $daftar_program ?? [];
$komponen_ids    = $komponen_ids   ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><?= $is_edit ? 'Edit Program' : 'Tambah Program' ?></h4>
    <a href="index.php?page=program" class="btn btn-outline-secondary btn-sm">← Kembali</a>
</div>

<form method="POST" action="<?= $form_act ?>" id="formProgram">
<?= csrf_field() ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="pk-card p-4">
            <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-info-circle me-2"></i>Detail Program</h6>

            <div class="mb-3">
                <label class="form-label fw-bold small">Tipe Program <span class="text-danger">*</span></label>
                <div class="d-flex gap-2 flex-wrap" id="tipeGroup">
                    <?php foreach (['Program'=>['bi-calendar-check-fill','primary'],'Fasilitas'=>['bi-house-fill','warning'],'Paket'=>['bi-box-seam-fill','purple']] as $tv => [$ico,$col]): ?>
                    <label class="d-flex align-items-center gap-2 px-3 py-2 rounded border cursor-pointer tipe-opt"
                           style="cursor:pointer;" data-tipe="<?= $tv ?>">
                        <input type="radio" name="tipe_program" value="<?= $tv ?>" class="d-none"
                               <?= ($i['tipe_program']??'Program')===$tv ? 'checked' : '' ?>>
                        <i class="bi <?= $ico ?>"></i>
                        <span class="fw-bold small"><?= $tv ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small">Nama Program <span class="text-danger">*</span></label>
                <input type="text" name="nama_program" class="form-control" required
                       value="<?= e($i['nama_program']??'') ?>" placeholder="cth: Akademik Intensif">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small">Deskripsi</label>
                <textarea name="deskripsi" class="form-control" rows="3"
                          placeholder="Opsional — deskripsi singkat program..."><?= e($i['deskripsi']??'') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small">Harga Acuan / Bulan</label>
                <div class="input-group">
                    <span class="input-group-text bg-light">Rp</span>
                    <input type="number" name="biaya_bulanan" class="form-control" min="0" step="50000"
                           value="<?= (int)($i['biaya_bulanan']??0) ?>" placeholder="0">
                </div>
                <small class="text-muted">Harga ini hanya acuan. Harga aktual dapat diedit per siswa saat mendaftar.</small>
            </div>

            <!-- Komponen paket (tampil hanya jika tipe=Paket) -->
            <div id="sectionKomponen" class="mb-3" style="display:none;">
                <label class="form-label fw-bold small">Program dalam Paket <span class="text-danger">*</span></label>
                <div class="border rounded p-3" style="max-height:220px;overflow-y:auto;">
                    <?php foreach ($daftar_program as $dp): ?>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="komponen[]"
                               value="<?= (int)$dp['id'] ?>" id="komp_<?= (int)$dp['id'] ?>"
                               <?= in_array((int)$dp['id'], $komponen_ids) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="komp_<?= (int)$dp['id'] ?>">
                            <?= e($dp['nama_program']) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <small class="text-muted">Pilih 2 atau lebih program yang menjadi komponen paket ini.</small>
            </div>

            <div class="d-flex align-items-center gap-3 mt-4 pt-3 border-top">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="chkAktif"
                           <?= ($i['is_active']??1) ? 'checked' : '' ?>>
                    <label class="form-check-label small fw-bold" for="chkAktif">Program Aktif</label>
                </div>
                <div class="ms-auto d-flex gap-2">
                    <a href="index.php?page=program" class="btn btn-outline-secondary btn-sm">Batal</a>
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-check-lg me-1"></i><?= $is_edit ? 'Simpan' : 'Tambah' ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="pk-card p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-lightbulb me-2 text-warning"></i>Panduan Tipe Program</h6>
            <div class="small text-muted">
                <div class="mb-3">
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 mb-1">Program</span>
                    <p class="mb-0">Program latihan reguler yang terhubung ke jadwal: Akademik Intensif, Jasmani Reguler, Kelas Tambahan, Private, dsb.</p>
                </div>
                <div class="mb-3">
                    <span class="badge mb-1" style="background:#fff3cd;color:#664d03;border:1px solid #b8860b;">Fasilitas</span>
                    <p class="mb-0">Layanan pendukung seperti mess/penginapan. Tidak terhubung ke jadwal sesi.</p>
                </div>
                <div>
                    <span class="badge mb-1" style="background:#e2d9f3;color:#3d1a78;border:1px solid #6f42c1;">Paket</span>
                    <p class="mb-0">Bundel 2+ program dengan harga spesial. Pilih program-program komponen yang termasuk dalam paket.</p>
                </div>
            </div>
        </div>
    </div>
</div>
</form>

<style>
.tipe-opt { transition:.15s; border-color:#dee2e6 !important; }
.tipe-opt.active-opt { background:#cfe2ff; border-color:#0d6efd !important; color:#084298; }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const opts = document.querySelectorAll('.tipe-opt');
    const sec  = document.getElementById('sectionKomponen');

    function updateTipe() {
        opts.forEach(o => {
            const radio = o.querySelector('input[type=radio]');
            o.classList.toggle('active-opt', radio.checked);
            if (radio.checked) sec.style.display = radio.value === 'Paket' ? '' : 'none';
        });
    }

    opts.forEach(o => {
        o.addEventListener('click', () => {
            o.querySelector('input[type=radio]').checked = true;
            updateTipe();
        });
    });
    updateTipe();
});
</script>

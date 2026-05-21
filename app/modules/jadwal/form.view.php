<?php /** Form tambah jadwal */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Tambah Jadwal</h4>
    <a href="index.php?page=jadwal" class="btn btn-outline-secondary btn-sm">← Kembali</a>
</div>
<form method="POST" action="index.php?page=jadwal&action=store">
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-md-7">
            <div class="pk-card p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Program <span class="text-danger">*</span></label>
                        <select name="program_id" class="form-select" required>
                            <option value="">— Pilih —</option>
                            <?php foreach ($daftar_program as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['nama_program']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Jenis Kegiatan</label>
                        <select name="kegiatan" class="form-select">
                            <option value="">— Pilih —</option>
                            <?php foreach (['Jasmani','Renang','Akademik','Psikologi','Tryout'] as $k): ?><option value="<?= $k ?>"><?= $k ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small">Materi / Topik</label>
                        <input type="text" name="materi" class="form-control" placeholder="Opsional — Lari 12 menit, TIU, dst">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Mulai <span class="text-danger">*</span></label>
                        <input type="time" name="waktu_mulai" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Selesai <span class="text-danger">*</span></label>
                        <input type="time" name="waktu_selesai" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small">Lokasi</label>
                        <input type="text" name="lokasi" class="form-control" placeholder="Perkasa Mulia Training Center">
                    </div>
                    <div class="col-12 d-flex gap-2 pt-2">
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-2"></i>Simpan Jadwal</button>
                        <a href="index.php?page=jadwal" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="pk-card p-4">
                <h6 class="fw-bold mb-3">Tutor / Coach</h6>
                <?php if (empty($daftar_tutor)): ?>
                <p class="text-muted small">Belum ada tutor aktif.</p>
                <?php else: foreach ($daftar_tutor as $t): ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="tutor_id[]"
                           value="<?= (int)$t['id'] ?>" id="t_<?= (int)$t['id'] ?>">
                    <label class="form-check-label small" for="t_<?= (int)$t['id'] ?>">
                        <?= e($t['nama_lengkap']) ?>
                        <?php if ($t['spesialisasi']): ?><span class="text-muted">(<?= e($t['spesialisasi']) ?>)</span><?php endif; ?>
                    </label>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</form>

<?php
/** app/modules/tutor/form.view.php */
$is_edit   = !empty($tutor_edit);
$t         = $tutor_edit ?? [];
$form_action = $is_edit
    ? "index.php?page=tutor&action=update&id=" . (int)($t['id'] ?? 0)
    : "index.php?page=tutor&action=store";
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><?= $is_edit ? 'Edit Tutor' : 'Tambah Tutor Baru' ?></h4>
        <small class="text-muted"><?= $is_edit ? 'Perbarui data ' . e($t['nama_lengkap']) : 'Isi data tutor dan akun login yang akan dibuat.' ?></small>
    </div>
    <a href="index.php?page=tutor" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<form method="POST" action="<?= $form_action ?>" id="tutorForm" novalidate>
    <?= csrf_field() ?>
    <div class="row g-4">

        <!-- Kolom Kiri: Data Pribadi -->
        <div class="col-lg-7">
            <div class="pk-card p-4 mb-3">
                <h6 class="fw-bold mb-3 text-primary">
                    <i class="bi bi-person-badge-fill me-2"></i>Data Pribadi
                </h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-bold small">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama_lengkap" class="form-control" required
                               value="<?= e($t['nama_lengkap'] ?? '') ?>"
                               placeholder="Nama lengkap sesuai KTP">
                        <div class="invalid-feedback">Nama lengkap wajib diisi.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Nomor WhatsApp</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-success"><i class="bi bi-whatsapp"></i></span>
                            <input type="tel" name="nomor_wa" class="form-control"
                                   value="<?= e($t['nomor_wa'] ?? '') ?>"
                                   placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="form-text text-muted" style="font-size:.68rem;">Format: 08xxxxxxxxxx atau +628xxxxxxxx</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Spesialisasi</label>
                        <input type="text" name="spesialisasi" class="form-control"
                               value="<?= e($t['spesialisasi'] ?? '') ?>"
                               placeholder="mis: Akademik, Jasmani, Psikologi...">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small">Status</label>
                        <div class="d-flex gap-3 mt-1">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status_aktif" id="status1" value="1"
                                       <?= ($t['status_aktif'] ?? 1) == 1 ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold text-success small" for="status1">Aktif</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="status_aktif" id="status0" value="0"
                                       <?= ($t['status_aktif'] ?? 1) == 0 ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold text-secondary small" for="status0">Non-Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Penggajian -->
            <div class="pk-card p-4 mb-3">
                <h6 class="fw-bold mb-3 text-warning">
                    <i class="bi bi-cash-coin me-2"></i>Informasi Penggajian
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Tarif per Sesi</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted small">Rp</span>
                            <input type="number" name="tarif_per_sesi" class="form-control"
                                   value="<?= e($t['tarif_per_sesi'] ?? '') ?>"
                                   min="0" step="5000" placeholder="0">
                        </div>
                        <div class="form-text text-muted" style="font-size:.68rem;">Akan dipakai hitung gaji otomatis</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Nama Bank</label>
                        <input type="text" name="bank_nama" class="form-control"
                               value="<?= e($t['bank_nama'] ?? '') ?>"
                               placeholder="BRI, BCA, Mandiri, dst">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Nomor Rekening</label>
                        <input type="text" name="bank_rekening" class="form-control"
                               value="<?= e($t['bank_rekening'] ?? '') ?>"
                               placeholder="Nomor rekening bank">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Atas Nama</label>
                        <input type="text" name="bank_atas_nama" class="form-control"
                               value="<?= e($t['bank_atas_nama'] ?? '') ?>"
                               placeholder="Sama seperti di buku tabungan">
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Akun Login -->
        <div class="col-lg-5">
            <div class="pk-card p-4 mb-3">
                <h6 class="fw-bold mb-3 text-info">
                    <i class="bi bi-key-fill me-2"></i>Akun Login Portal Tutor
                </h6>
                <?php if ($is_edit): ?>
                <div class="alert alert-info py-2 small mb-3">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    Email login tidak bisa diubah. Kosongkan password untuk mempertahankan password lama.
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Email Login</label>
                    <input type="email" class="form-control bg-light" value="<?= e($t['email'] ?? '') ?>" disabled>
                </div>
                <?php else: ?>
                <div class="alert alert-success py-2 small mb-3">
                    <i class="bi bi-shield-check me-1"></i>
                    Email dan password ini akan dipakai tutor untuk login ke portal.
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Email Login <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required
                           placeholder="email@perkasa.id">
                    <div class="invalid-feedback">Email wajib diisi dan harus valid.</div>
                </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label fw-bold small">
                        Password <?= $is_edit ? '' : '<span class="text-danger">*</span>' ?>
                    </label>
                    <div class="input-group">
                        <input type="password" name="password" id="pwdInput" class="form-control"
                               <?= !$is_edit ? 'required minlength="6"' : '' ?>
                               placeholder="<?= $is_edit ? 'Kosongkan jika tidak ingin ganti' : 'Min. 6 karakter' ?>">
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                onclick="const i=document.getElementById('pwdInput'); i.type=i.type==='password'?'text':'password';">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                    </div>
                    <?php if (!$is_edit): ?>
                    <div class="invalid-feedback">Password wajib diisi minimal 6 karakter.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Preview Card -->
            <div class="pk-card p-4" style="background: var(--color-background-secondary);">
                <div class="text-muted fw-bold mb-3" style="font-size:.65rem;text-transform:uppercase;letter-spacing:.06em;">
                    RINGKASAN
                </div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="pk-topbar__avatar" style="width:48px;height:48px;font-size:1.2rem;" id="previewAvatar">
                        <?= strtoupper(substr($t['nama_lengkap'] ?? 'T', 0, 1)) ?>
                    </div>
                    <div>
                        <div class="fw-bold" id="previewNama"><?= e($t['nama_lengkap'] ?? 'Nama Tutor') ?></div>
                        <div class="text-muted small" id="previewSpesialis"><?= e($t['spesialisasi'] ?? 'Spesialisasi') ?></div>
                    </div>
                </div>
                <div class="d-flex flex-column gap-1 small">
                    <div class="d-flex gap-2">
                        <i class="bi bi-whatsapp text-success"></i>
                        <span id="previewWA" class="text-muted"><?= e($t['nomor_wa'] ?? '—') ?></span>
                    </div>
                    <div class="d-flex gap-2">
                        <i class="bi bi-cash-coin text-warning"></i>
                        <span id="previewTarif" class="text-muted">
                            <?= !empty($t['tarif_per_sesi']) ? format_rupiah((float)$t['tarif_per_sesi']) . '/sesi' : '—' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Submit -->
    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary px-4" id="submitBtn">
            <i class="bi bi-save-fill me-2"></i><?= $is_edit ? 'Simpan Perubahan' : 'Tambah Tutor' ?>
        </button>
        <a href="index.php?page=tutor" class="btn btn-outline-secondary">Batal</a>
    </div>
</form>

<script>
// Live preview
const nm  = document.querySelector('[name="nama_lengkap"]');
const sp  = document.querySelector('[name="spesialisasi"]');
const wa  = document.querySelector('[name="nomor_wa"]');
const tar = document.querySelector('[name="tarif_per_sesi"]');

function updatePreview() {
    const nama = nm?.value || 'Nama Tutor';
    document.getElementById('previewAvatar').textContent = nama.charAt(0).toUpperCase();
    document.getElementById('previewNama').textContent   = nama;
    document.getElementById('previewSpesialis').textContent = sp?.value || 'Spesialisasi';
    document.getElementById('previewWA').textContent  = wa?.value  || '—';
    const t = parseInt(tar?.value) || 0;
    document.getElementById('previewTarif').textContent = t > 0
        ? 'Rp ' + t.toLocaleString('id-ID') + '/sesi' : '—';
}

[nm, sp, wa, tar].forEach(el => el?.addEventListener('input', updatePreview));

// Form validation dengan scroll to first error
document.getElementById('tutorForm').addEventListener('submit', function(e) {
    if (!this.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('was-validated');
        // Scroll ke field pertama yang invalid
        const firstInvalid = this.querySelector(':invalid');
        if (firstInvalid) {
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalid.focus();
        }
        return;
    }
    // Disable button untuk cegah double submit
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
});
</script>

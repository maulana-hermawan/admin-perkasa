<?php
/**
 * app/modules/siswa/form.view.php — Module 6.5
 *
 * CREATE : Multi-step wizard 3 langkah sesuai blueprint Section 7.1
 *   Step 1 → Informasi Pribadi
 *   Step 2 → Program & Membership
 *   Step 3 → Konfirmasi & Review
 *
 * EDIT   : Single-page form lengkap (data sudah ada, tidak perlu wizard)
 *
 * Fitur:
 *  - Label di atas field, 12px bold
 *  - Placeholder = contoh, bukan pengganti label
 *  - Required field tanda * merah
 *  - Error inline di bawah field + scroll ke error pertama
 *  - Submit button disabled + spinner saat loading
 *  - Mobile: tombol Next/Back sticky di bawah
 *  - TomSelect pada dropdown Program (search-select)
 *  - Preview NIS otomatis saat CREATE
 */

$is_edit   = !empty($siswa['id']);
$s         = $siswa ?? [];   // shorthand
$s_id      = $is_edit ? (int)$s['id'] : 0;
$form_action = $is_edit
    ? "index.php?page=siswa&action=update&id={$s_id}"
    : "index.php?page=siswa&action=store";
?>

<!-- TomSelect (search-select dropdown) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js" defer></script>

<style>
/* ── Step indicator ── */
.pk-steps { display:flex; align-items:center; gap:0; margin-bottom:2rem; }
.pk-step  { display:flex; flex-direction:column; align-items:center; flex:1; position:relative; }
.pk-step:not(:last-child)::after {
    content:''; position:absolute; top:16px; left:50%; width:100%; height:2px;
    background:var(--color-border-tertiary); z-index:0; transition:background .3s;
}
.pk-step.done::after  { background:#639922; }
.pk-step.active::after { background:var(--color-border-tertiary); }
.pk-step-dot {
    width:32px; height:32px; border-radius:50%; display:flex; align-items:center;
    justify-content:center; font-size:.75rem; font-weight:500;
    border:2px solid var(--color-border-tertiary);
    background:var(--color-background-primary); z-index:1; transition:all .3s;
}
.pk-step.done   .pk-step-dot { background:#639922; border-color:#639922; color:#fff; }
.pk-step.active .pk-step-dot { background:#0d6efd; border-color:#0d6efd; color:#fff; }
.pk-step-label { font-size:.68rem; margin-top:4px; color:var(--color-text-secondary); text-align:center; font-weight:500; }
.pk-step.active .pk-step-label { color:#0d6efd; }

/* ── Step pane ── */
.step-pane { display:none; }
.step-pane.active { display:block; }

/* ── Field ── */
.pk-field label { font-size:.8rem; font-weight:600; display:block; margin-bottom:4px; }
.pk-field label .req { color:#dc3545; margin-left:2px; }
.pk-field .helper { font-size:.7rem; color:var(--color-text-secondary); margin-top:3px; }
.pk-field .invalid-msg {
    font-size:.72rem; color:#dc3545; display:none; margin-top:3px;
}
.pk-field .invalid-msg.show { display:flex; align-items:center; gap:4px; }
.pk-field .form-control.is-invalid,
.pk-field .form-select.is-invalid { border-color:#dc3545; }
.pk-field .form-control.is-valid,
.pk-field .form-select.is-valid   { border-color:#198754; }

/* ── Konfirmasi preview ── */
.confirm-grid { display:grid; grid-template-columns:auto 1fr; gap:4px 16px; font-size:.85rem; }
.confirm-label { color:var(--color-text-secondary); font-weight:500; text-align:right; }
.confirm-value { font-weight:500; }

/* ── Mobile sticky footer ── */
@media (max-width:639px) {
    .pk-form-footer { position:sticky; bottom:0; background:var(--color-background-primary); border-top:0.5px solid var(--color-border-tertiary); padding:12px; margin:0 -1rem; z-index:10; }
}
</style>

<!-- ── Header ────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">
            <?= $is_edit ? 'Edit Profil Siswa' : 'Pendaftaran Siswa Baru' ?>
        </h4>
        <small class="text-muted">
            <?= $is_edit
                ? "NIS: <strong>" . e($s['nomor_induk'] ?? '—') . "</strong>"
                : "NIS akan digenerate otomatis (format PMTC-YY-NNNN)" ?>
        </small>
    </div>
    <a href="<?= $is_edit ? "index.php?page=siswa&action=detail&id={$s_id}" : 'index.php?page=siswa' ?>"
       class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>
        <?= $is_edit ? 'Kembali ke Detail' : 'Kembali' ?>
    </a>
</div>

<?php if (!$is_edit): ?>
<!-- ══ MULTI-STEP WIZARD (CREATE) ══════════════════════════════ -->

<!-- Step Indicator -->
<div class="pk-steps mb-4" id="stepIndicator">
    <div class="pk-step active" id="ind-step1">
        <div class="pk-step-dot" id="dot1">1</div>
        <span class="pk-step-label">Informasi Pribadi</span>
    </div>
    <div class="pk-step" id="ind-step2">
        <div class="pk-step-dot" id="dot2">2</div>
        <span class="pk-step-label">Membership</span>
    </div>
    <div class="pk-step" id="ind-step3">
        <div class="pk-step-dot" id="dot3">3</div>
        <span class="pk-step-label">Konfirmasi</span>
    </div>
</div>

<form method="POST" action="<?= $form_action ?>" id="siswaForm" novalidate>
    <?= csrf_field() ?>

    <!-- ── STEP 1: INFORMASI PRIBADI ───────────────────────── -->
    <div class="step-pane active" id="step1">
        <div class="pk-card p-4 mb-4">
            <h6 class="fw-bold mb-4 d-flex align-items-center gap-2 text-primary">
                <i class="bi bi-person-badge-fill"></i> Informasi Pribadi
            </h6>
            <div class="row g-3">

                <!-- Nama -->
                <div class="col-12 pk-field" id="f-nama">
                    <label>Nama Lengkap <span class="req">*</span></label>
                    <input type="text" name="nama" id="inp-nama" class="form-control"
                           placeholder="Sesuai KTP/KK" maxlength="150" autocomplete="name">
                    <div class="invalid-msg" id="err-nama"><i class="bi bi-exclamation-circle"></i><span></span></div>
                    <div class="helper">Nama lengkap tanpa singkatan, sesuai dokumen resmi.</div>
                </div>

                <!-- Jenis Kelamin -->
                <div class="col-md-4 pk-field" id="f-jk">
                    <label>Jenis Kelamin <span class="req">*</span></label>
                    <div class="d-flex gap-3 mt-1">
                        <?php foreach (['L' => 'Laki-laki', 'P' => 'Perempuan'] as $val => $lbl): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="jenis_kelamin"
                                   id="jk_<?= $val ?>" value="<?= $val ?>">
                            <label class="form-check-label small" for="jk_<?= $val ?>"><?= $lbl ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="invalid-msg" id="err-jk"><i class="bi bi-exclamation-circle"></i><span></span></div>
                </div>

                <!-- Tanggal Lahir -->
                <div class="col-md-4 pk-field" id="f-tgl-lahir">
                    <label>Tanggal Lahir</label>
                    <input type="date" name="tgl_lahir" id="inp-tgllahir" class="form-control"
                           max="<?= date('Y-m-d') ?>">
                    <div class="helper" id="usia-helper"></div>
                </div>

                <!-- Target Seleksi -->
                <div class="col-md-4 pk-field" id="f-target">
                    <label>Target Seleksi <span class="req">*</span></label>
                    <select name="target_seleksi" id="inp-target" class="form-select">
                        <option value="">— Pilih target —</option>
                        <?php foreach (['Polri','TNI AD','TNI AL','TNI AU','Akpol','Akmil','Bintara','Tamtama','Lainnya'] as $t): ?>
                        <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-msg" id="err-target"><i class="bi bi-exclamation-circle"></i><span></span></div>
                </div>

                <!-- Nomor WA Siswa -->
                <div class="col-md-6 pk-field" id="f-wa">
                    <label>Nomor WhatsApp Siswa <span class="req">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-whatsapp text-success"></i></span>
                        <input type="tel" name="wa" id="inp-wa" class="form-control"
                               placeholder="0812xxxx" autocomplete="tel">
                    </div>
                    <div class="invalid-msg" id="err-wa"><i class="bi bi-exclamation-circle"></i><span></span></div>
                    <div class="helper">Format: 08xx atau +628xx. Akan dipakai sebagai akun login.</div>
                </div>

                <!-- Nama Ortu -->
                <div class="col-md-6 pk-field">
                    <label>Nama Orang Tua/Wali</label>
                    <input type="text" name="ortu" class="form-control"
                           placeholder="Contoh: Bapak Suharto" maxlength="150">
                </div>

                <!-- WA Ortu -->
                <div class="col-md-6 pk-field">
                    <label>Nomor WA Orang Tua/Wali</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-whatsapp text-success"></i></span>
                        <input type="tel" name="wa_ortu" class="form-control" placeholder="0812xxxx">
                    </div>
                    <div class="helper">Untuk notifikasi dan komunikasi darurat.</div>
                </div>

                <!-- Asal Sekolah -->
                <div class="col-md-6 pk-field">
                    <label>Asal Sekolah</label>
                    <input type="text" name="asal_sekolah" class="form-control"
                           placeholder="Contoh: SMAN 1 Bogor" maxlength="150">
                </div>

                <!-- Alamat -->
                <div class="col-12 pk-field">
                    <label>Alamat Lengkap</label>
                    <textarea name="alamat" class="form-control" rows="2"
                              placeholder="Jl. Merdeka No. 10, RT 01/RW 02, Bogor Utara"></textarea>
                </div>
            </div>
        </div>

        <div class="pk-form-footer d-flex justify-content-between align-items-center gap-2">
            <a href="index.php?page=siswa" class="btn btn-outline-secondary">Batal</a>
            <button type="button" class="btn btn-primary px-4" onclick="goStep(2)">
                Lanjut: Membership <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>
    </div>

    <!-- ── STEP 2: MEMBERSHIP ───────────────────────────────── -->
    <div class="step-pane" id="step2">
        <div class="pk-card p-4 mb-4">
            <h6 class="fw-bold mb-4 d-flex align-items-center gap-2 text-success">
                <i class="bi bi-journal-bookmark-fill"></i> Program & Membership
            </h6>

            <!-- Info: Tanpa program tetap bisa daftar -->
            <div class="alert alert-info py-2 small mb-3">
                <i class="bi bi-info-circle-fill me-1"></i>
                Program bisa diisi nanti. Siswa tetap bisa didaftarkan tanpa program aktif.
            </div>

            <div class="row g-3">
                <!-- Program dengan TomSelect -->
                <div class="col-12 pk-field">
                    <label>Program Bimbel</label>
                    <select name="program_id" id="inp-program" class="form-select">
                        <option value="">— Belum ada program —</option>
                        <?php foreach ($daftar_program as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"
                                data-biaya="<?= (int)$p['biaya_bulanan'] ?>">
                            <?= e($p['nama_program']) ?>
                            <?php if ($p['biaya_bulanan'] > 0): ?>
                            (<?= format_rupiah((float)$p['biaya_bulanan'], false) ?>/bln)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="helper">Pilih program sesuai target seleksi siswa.</div>
                </div>

                <!-- Tanggal mulai & selesai -->
                <div class="col-md-5 pk-field" id="f-tgl-mulai">
                    <label>Tanggal Mulai</label>
                    <input type="date" name="tgl_mulai" id="inp-mulai" class="form-control"
                           value="<?= date('Y-m-d') ?>">
                    <div class="invalid-msg" id="err-tglmulai"><i class="bi bi-exclamation-circle"></i><span></span></div>
                </div>
                <div class="col-md-5 pk-field" id="f-tgl-selesai">
                    <label>Berlaku Sampai</label>
                    <input type="date" name="tgl_selesai" id="inp-selesai" class="form-control">
                    <div class="invalid-msg" id="err-tglselesai"><i class="bi bi-exclamation-circle"></i><span></span></div>
                </div>
                <div class="col-md-2 pk-field d-flex align-items-end">
                    <div>
                        <div id="durasi-info" class="badge bg-info bg-opacity-15 text-info w-100 py-2" style="font-size:.75rem;">—</div>
                    </div>
                </div>

                <!-- Info biaya -->
                <div class="col-12" id="biaya-preview" style="display:none;">
                    <div class="pk-card p-3 bg-light border-0">
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">Biaya/bulan:</span>
                            <span class="fw-bold" id="biaya-bulanan-txt">—</span>
                        </div>
                        <div class="d-flex justify-content-between small mt-1">
                            <span class="text-muted">Estimasi total:</span>
                            <span class="fw-bold text-success" id="biaya-total-txt">—</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pk-form-footer d-flex justify-content-between gap-2">
            <button type="button" class="btn btn-outline-secondary" onclick="goStep(1)">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </button>
            <button type="button" class="btn btn-primary px-4" onclick="goStep(3)">
                Lanjut: Konfirmasi <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>
    </div>

    <!-- ── STEP 3: KONFIRMASI ───────────────────────────────── -->
    <div class="step-pane" id="step3">
        <div class="pk-card p-4 mb-4">
            <h6 class="fw-bold mb-4 d-flex align-items-center gap-2 text-primary">
                <i class="bi bi-clipboard2-check-fill"></i> Konfirmasi Data Pendaftaran
            </h6>

            <!-- NIS preview -->
            <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-4"
                 style="background:linear-gradient(135deg,#001233,#0d4c92);color:#fff;">
                <i class="bi bi-person-badge-fill fs-3 opacity-75"></i>
                <div>
                    <div style="font-size:.7rem;opacity:.7;font-weight:500;letter-spacing:.05em;">NOMOR INDUK AKAN DIGENERATE</div>
                    <div class="fw-bold font-monospace" style="font-size:1.1rem;">PMTC-<?= date('y') ?>-####</div>
                    <div style="font-size:.7rem;opacity:.6;">Urutan otomatis saat disimpan</div>
                </div>
            </div>

            <!-- Review grid -->
            <div class="confirm-grid mb-4" id="confirmGrid">
                <!-- Diisi oleh JS -->
            </div>

            <div class="alert alert-warning py-2 small mb-0">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                Periksa kembali data di atas sebelum mendaftar. Data dapat diubah setelah pendaftaran melalui halaman Edit.
            </div>
        </div>

        <div class="pk-form-footer d-flex justify-content-between gap-2">
            <button type="button" class="btn btn-outline-secondary" onclick="goStep(2)">
                <i class="bi bi-arrow-left me-1"></i> Ubah Data
            </button>
            <button type="submit" id="btnSubmitCreate" class="btn btn-success px-4 fw-bold">
                <span id="btnSubmitText"><i class="bi bi-person-check-fill me-2"></i>Daftarkan Siswa</span>
                <span id="btnSubmitSpinner" class="d-none">
                    <span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...
                </span>
            </button>
        </div>
    </div>

</form>

<?php else: ?>
<!-- ══ SINGLE PAGE EDIT FORM ═══════════════════════════════════ -->

<form method="POST" action="<?= $form_action ?>" id="siswaForm" novalidate>
    <?= csrf_field() ?>

    <div class="row g-4">
        <!-- Kolom kiri: Data diri -->
        <div class="col-lg-7">

            <!-- Identitas -->
            <div class="pk-card p-4 mb-3">
                <h6 class="fw-bold mb-3 text-primary d-flex align-items-center gap-2">
                    <i class="bi bi-person-badge-fill"></i>Identitas Siswa
                </h6>
                <div class="row g-3">

                    <div class="col-12 pk-field" id="f-nama">
                        <label>Nama Lengkap <span class="req">*</span></label>
                        <input type="text" name="nama" id="inp-nama" class="form-control"
                               value="<?= e($s['nama_lengkap'] ?? '') ?>" required maxlength="150">
                        <div class="invalid-msg" id="err-nama"><i class="bi bi-exclamation-circle"></i><span></span></div>
                    </div>

                    <div class="col-md-4 pk-field" id="f-jk">
                        <label>Jenis Kelamin <span class="req">*</span></label>
                        <div class="d-flex gap-3 mt-1">
                            <?php foreach (['L' => 'Laki-laki', 'P' => 'Perempuan'] as $val => $lbl): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="jenis_kelamin"
                                       id="jk_<?= $val ?>" value="<?= $val ?>"
                                       <?= ($s['jenis_kelamin'] ?? '') === $val ? 'checked' : '' ?>>
                                <label class="form-check-label small" for="jk_<?= $val ?>"><?= $lbl ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="invalid-msg" id="err-jk"><i class="bi bi-exclamation-circle"></i><span></span></div>
                    </div>

                    <div class="col-md-4 pk-field">
                        <label>Tanggal Lahir</label>
                        <input type="date" name="tgl_lahir" id="inp-tgllahir" class="form-control"
                               value="<?= e($s['tanggal_lahir'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
                        <div class="helper" id="usia-helper">
                            <?php if (!empty($s['tanggal_lahir'])): ?>
                            Umur: <strong><?= hitung_umur($s['tanggal_lahir']) ?> tahun</strong>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-4 pk-field" id="f-target">
                        <label>Target Seleksi <span class="req">*</span></label>
                        <select name="target_seleksi" id="inp-target" class="form-select">
                            <option value="">— Pilih —</option>
                            <?php foreach (['Polri','TNI AD','TNI AL','TNI AU','Akpol','Akmil','Bintara','Tamtama','Lainnya'] as $t): ?>
                            <option value="<?= $t ?>" <?= ($s['target_seleksi']??'')===$t?'selected':''?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-msg" id="err-target"><i class="bi bi-exclamation-circle"></i><span></span></div>
                    </div>

                    <div class="col-md-6 pk-field" id="f-wa">
                        <label>Nomor WA Siswa <span class="req">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-whatsapp text-success"></i></span>
                            <input type="tel" name="wa" id="inp-wa" class="form-control"
                                   value="<?= e($s['nomor_wa'] ?? '') ?>">
                        </div>
                        <div class="invalid-msg" id="err-wa"><i class="bi bi-exclamation-circle"></i><span></span></div>
                    </div>

                    <div class="col-md-6 pk-field">
                        <label>Asal Sekolah</label>
                        <input type="text" name="asal_sekolah" class="form-control"
                               value="<?= e($s['asal_sekolah'] ?? '') ?>" maxlength="150">
                    </div>

                    <div class="col-12 pk-field">
                        <label>Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2"><?= e($s['alamat'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Ortu -->
            <div class="pk-card p-4 mb-3">
                <h6 class="fw-bold mb-3 text-muted d-flex align-items-center gap-2">
                    <i class="bi bi-people-fill"></i>Data Orang Tua/Wali
                </h6>
                <div class="row g-3">
                    <div class="col-md-6 pk-field">
                        <label>Nama Orang Tua/Wali</label>
                        <input type="text" name="ortu" class="form-control"
                               value="<?= e($s['nama_ortu'] ?? '') ?>" maxlength="150">
                    </div>
                    <div class="col-md-6 pk-field">
                        <label>Nomor WA Orang Tua/Wali</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-whatsapp text-success"></i></span>
                            <input type="tel" name="wa_ortu" class="form-control"
                                   value="<?= e($s['nomor_wa_ortu'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status (edit only) -->
            <div class="pk-card p-4">
                <h6 class="fw-bold mb-3 text-warning d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check-fill"></i>Status Keanggotaan
                </h6>
                <div class="row g-3">
                    <div class="col-md-6 pk-field">
                        <label>Status Siswa</label>
                        <select name="status" id="inp-status" class="form-select" onchange="toggleKetLulus()">
                            <?php foreach (['Aktif','Cuti','Lulus','Tidak Aktif'] as $st): ?>
                            <option value="<?= $st ?>" <?= ($s['status_siswa']??'Aktif')===$st?'selected':''?>><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 pk-field" id="div-ket-lulus"
                         style="<?= ($s['status_siswa']??'')!=='Lulus'?'display:none':'' ?>">
                        <label>Keterangan Lulus</label>
                        <input type="text" name="ket_lulus" class="form-control"
                               value="<?= e($s['keterangan_lulus'] ?? '') ?>"
                               placeholder="Contoh: Akpol 2026">
                        <div class="helper">Catat keberhasilan siswa.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom kanan: Program + Aksi -->
        <div class="col-lg-5">

            <!-- Program -->
            <div class="pk-card p-4 mb-3">
                <h6 class="fw-bold mb-3 text-success d-flex align-items-center gap-2">
                    <i class="bi bi-journal-bookmark-fill"></i>Program Bimbel
                </h6>
                <div class="row g-3">
                    <div class="col-12 pk-field">
                        <label>Program</label>
                        <select name="program_id" id="inp-program" class="form-select">
                            <option value="">— Belum ada program —</option>
                            <?php foreach ($daftar_program as $p): ?>
                            <option value="<?= (int)$p['id'] ?>"
                                    data-biaya="<?= (int)$p['biaya_bulanan'] ?>"
                                    <?= ($s['program_id']??0)==(int)$p['id']?'selected':''?>>
                                <?= e($p['nama_program']) ?>
                                <?php if ($p['biaya_bulanan'] > 0): ?>
                                (<?= format_rupiah((float)$p['biaya_bulanan'], false) ?>/bln)
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 pk-field">
                        <label>Tanggal Mulai</label>
                        <input type="date" name="tgl_mulai" id="inp-mulai" class="form-control"
                               value="<?= e($s['tanggal_mulai_aktif'] ?? '') ?>">
                    </div>
                    <div class="col-6 pk-field">
                        <label>Berlaku Sampai</label>
                        <input type="date" name="tgl_selesai" id="inp-selesai" class="form-control"
                               value="<?= e($s['tanggal_selesai_aktif'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <div id="durasi-info" class="text-muted" style="font-size:.75rem;"></div>
                    </div>
                </div>
            </div>

            <!-- Aksi -->
            <div class="pk-card p-4">
                <div class="d-grid gap-2">
                    <button type="submit" id="btnSubmitEdit" class="btn btn-primary fw-bold">
                        <span id="btnSubmitText"><i class="bi bi-save-fill me-2"></i>Simpan Perubahan</span>
                        <span id="btnSubmitSpinner" class="d-none">
                            <span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...
                        </span>
                    </button>
                    <a href="index.php?page=siswa&action=detail&id=<?= $s_id ?>"
                       class="btn btn-outline-secondary">Batal</a>
                </div>
                <div class="mt-3 pt-3 border-top">
                    <div class="text-muted small fw-bold mb-2">Info</div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>NIS</span><span class="fw-bold font-monospace"><?= e($s['nomor_induk'] ?? '—') ?></span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mt-1">
                        <span>Daftar Sejak</span><span><?= format_tanggal($s['created_at'] ?? '') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scroll to error message -->
    <div id="formErrorSummary" class="alert alert-danger py-2 small mt-3 d-none">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        <span id="formErrorText">Ada kesalahan pada form. Silakan periksa field yang ditandai merah.</span>
    </div>
</form>

<script>
function toggleKetLulus() {
    const v = document.getElementById('inp-status')?.value;
    const d = document.getElementById('div-ket-lulus');
    if (d) d.style.display = v === 'Lulus' ? '' : 'none';
}
</script>

<?php endif; // end edit mode ?>

<!-- ══ SHARED SCRIPTS ══════════════════════════════════════════ -->
<script>
(function() {
const IS_EDIT  = <?= $is_edit ? 'true' : 'false' ?>;
let currentStep = 1;

// ── TomSelect init ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const progSel = document.getElementById('inp-program');
    if (progSel && window.TomSelect) {
        new TomSelect(progSel, {
            create: false,
            placeholder: '— Pilih atau ketik nama program —',
            allowEmptyOption: true,
            maxOptions: 20,
        });
    }
});

// ── Hitung umur saat tanggal lahir berubah ─────────────────────
const tglLahirEl = document.getElementById('inp-tgllahir');
if (tglLahirEl) {
    tglLahirEl.addEventListener('change', () => {
        const d   = new Date(tglLahirEl.value);
        const now = new Date();
        if (isNaN(d.getTime())) return;
        const umur = Math.floor((now - d) / (365.25 * 24 * 3600000));
        const el   = document.getElementById('usia-helper');
        if (el) el.innerHTML = `Umur: <strong>${umur} tahun</strong>`;
    });
}

// ── Hitung durasi & estimasi biaya ────────────────────────────
function hitungDurasi() {
    const mulai   = document.getElementById('inp-mulai')?.value;
    const selesai = document.getElementById('inp-selesai')?.value;
    const durEl   = document.getElementById('durasi-info');
    const biayaEl = document.getElementById('biaya-preview');
    const biayaTxt= document.getElementById('biaya-bulanan-txt');
    const totalTxt= document.getElementById('biaya-total-txt');
    const progSel = document.getElementById('inp-program');

    if (!mulai || !selesai || !durEl) return;

    const d1 = new Date(mulai), d2 = new Date(selesai);
    if (isNaN(d1) || isNaN(d2) || d2 <= d1) { durEl.textContent = ''; return; }

    const bulan = Math.round((d2 - d1) / (30.44 * 86400000));
    durEl.textContent = bulan > 0 ? `${bulan} bulan` : '';

    if (biayaEl && progSel) {
        const opt    = progSel.options[progSel.selectedIndex];
        const biaya  = parseInt(opt?.dataset?.biaya || '0');
        if (biaya > 0 && bulan > 0) {
            biayaEl.style.display = '';
            biayaTxt.textContent  = 'Rp ' + biaya.toLocaleString('id-ID');
            totalTxt.textContent  = 'Rp ' + (biaya * bulan).toLocaleString('id-ID');
        } else {
            biayaEl.style.display = 'none';
        }
    }
}

['inp-mulai','inp-selesai','inp-program'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', hitungDurasi);
});
hitungDurasi();

// ── Validasi field ─────────────────────────────────────────────
const rules = {
    'inp-nama'   : { required: true, min: 2, label: 'Nama Lengkap' },
    'inp-wa'     : { required: true, phone: true, label: 'Nomor WA' },
    'inp-target' : { required: true, label: 'Target Seleksi' },
};

function showError(fieldId, errId, msg) {
    const el = document.getElementById(fieldId);
    const er = document.getElementById(errId);
    el?.classList.add('is-invalid');
    el?.classList.remove('is-valid');
    if (er) { er.querySelector('span').textContent = msg; er.classList.add('show'); }
    return false;
}

function clearError(fieldId, errId) {
    const el = document.getElementById(fieldId);
    const er = document.getElementById(errId);
    el?.classList.remove('is-invalid');
    el?.classList.add('is-valid');
    if (er) { er.classList.remove('show'); }
}

function validateField(id) {
    const rule = rules[id];
    if (!rule) return true;

    const el  = document.getElementById(id);
    const val = el?.value?.trim() ?? '';
    const err = 'err-' + id.replace('inp-', '');

    if (rule.required && !val) {
        return showError(id, err, `${rule.label} wajib diisi.`);
    }
    if (rule.min && val.length < rule.min) {
        return showError(id, err, `${rule.label} minimal ${rule.min} karakter.`);
    }
    if (rule.phone && val) {
        const digits = val.replace(/[^0-9]/g, '');
        if (digits.length < 9 || digits.length > 15) {
            return showError(id, err, 'Nomor HP tidak valid (9-15 digit).');
        }
    }
    if (id === 'inp-target' && !val) {
        return showError(id, err, 'Target Seleksi wajib dipilih.');
    }

    clearError(id, err);
    return true;
}

// Validate jenis kelamin
function validateJK() {
    const checked = document.querySelector('input[name="jenis_kelamin"]:checked');
    const er      = document.getElementById('err-jk');
    if (!checked) {
        if (er) { er.querySelector('span').textContent = 'Jenis kelamin wajib dipilih.'; er.classList.add('show'); }
        return false;
    }
    if (er) er.classList.remove('show');
    return true;
}

// Live validation on blur
Object.keys(rules).forEach(id => {
    document.getElementById(id)?.addEventListener('blur', () => validateField(id));
});

// ── Step navigation (create only) ─────────────────────────────
window.goStep = function(target) {
    // Validasi step saat ini sebelum lanjut maju
    if (target > currentStep) {
        if (currentStep === 1) {
            const ok1 = validateField('inp-nama');
            const ok2 = validateJK();
            const ok3 = validateField('inp-wa');
            const ok4 = validateField('inp-target');
            if (!ok1 || !ok2 || !ok3 || !ok4) {
                scrollToFirstError();
                return;
            }
        }
        if (currentStep === 2) {
            const mulai   = document.getElementById('inp-mulai')?.value;
            const selesai = document.getElementById('inp-selesai')?.value;
            const prog    = document.getElementById('inp-program')?.value;
            const er1     = document.getElementById('err-tglmulai');
            const er2     = document.getElementById('err-tglselesai');

            // Jika program dipilih, tanggal wajib
            if (prog && mulai && selesai && new Date(selesai) <= new Date(mulai)) {
                if (er2) { er2.querySelector('span').textContent = 'Tanggal selesai harus setelah tanggal mulai.'; er2.classList.add('show'); }
                return;
            }
            if (er1) er1.classList.remove('show');
            if (er2) er2.classList.remove('show');
        }

        if (target === 3) buildConfirmGrid();
    }

    // Hide current, show target
    document.getElementById(`step${currentStep}`)?.classList.remove('active');
    document.getElementById(`ind-step${currentStep}`)?.classList.remove('active');

    if (target > currentStep) {
        document.getElementById(`ind-step${currentStep}`)?.classList.add('done');
    } else {
        document.getElementById(`ind-step${currentStep}`)?.classList.remove('done');
    }

    currentStep = target;
    document.getElementById(`step${currentStep}`)?.classList.add('active');
    document.getElementById(`ind-step${currentStep}`)?.classList.add('active');

    // Update step dots
    [1,2,3].forEach(n => {
        const dot = document.getElementById(`dot${n}`);
        if (!dot) return;
        if (n < currentStep) {
            dot.innerHTML = '<i class="bi bi-check-lg" style="font-size:.75rem;"></i>';
        } else {
            dot.textContent = n;
        }
    });

    window.scrollTo({ top: 0, behavior: 'smooth' });
};

// ── Build konfirmasi grid ─────────────────────────────────────
function buildConfirmGrid() {
    const grid = document.getElementById('confirmGrid');
    if (!grid) return;

    const get = id => document.getElementById(id)?.value?.trim() || '—';
    const getRadio = name => document.querySelector(`input[name="${name}"]:checked`)?.value;
    const getLabel = (sel, val) => document.querySelector(`${sel} option[value="${val}"]`)?.textContent || val || '—';

    const baru_tgl = get('inp-tgllahir');
    const umurStr  = baru_tgl && baru_tgl !== '—'
        ? `(${Math.floor((new Date() - new Date(baru_tgl)) / (365.25 * 24 * 3600000))} th)` : '';

    const jk     = getRadio('jenis_kelamin');
    const jkLbl  = jk === 'L' ? 'Laki-laki' : jk === 'P' ? 'Perempuan' : '—';
    const progId = get('inp-program') !== '—' ? get('inp-program') : '';
    const progLbl= progId ? getLabel('#inp-program', progId) : '—';

    const rows = [
        ['Nama Lengkap', get('inp-nama')],
        ['Jenis Kelamin', jkLbl],
        ['Tanggal Lahir', baru_tgl || '—', umurStr ? ` ${umurStr}` : ''],
        ['Target Seleksi', get('inp-target')],
        ['Nomor WA Siswa', get('inp-wa')],
        ['Nama Ortu', document.querySelector('[name="ortu"]')?.value || '—'],
        ['Asal Sekolah', document.querySelector('[name="asal_sekolah"]')?.value || '—'],
        ['Program', progLbl],
        ['Mulai Aktif', get('inp-mulai') || '—'],
        ['Berlaku Sampai', get('inp-selesai') || '—'],
    ];

    grid.innerHTML = rows.map(([lbl, val, extra]) =>
        `<div class="confirm-label">${lbl}</div>
         <div class="confirm-value">${val}${extra||''}</div>`
    ).join('');
}

// ── Submit loading state ────────────────────────────────────────
const form = document.getElementById('siswaForm');
form?.addEventListener('submit', function(e) {
    // Final validation
    if (!IS_EDIT && currentStep !== 3) { e.preventDefault(); return; }

    const ok1 = validateField('inp-nama');
    const ok2 = validateJK();
    const ok3 = validateField('inp-wa');
    const ok4 = validateField('inp-target');

    if (!ok1 || !ok2 || !ok3 || !ok4) {
        e.preventDefault();
        scrollToFirstError();
        const summary = document.getElementById('formErrorSummary');
        if (summary) summary.classList.remove('d-none');
        return;
    }

    // Loading state
    const btnTxt = document.getElementById('btnSubmitText');
    const btnSpin= document.getElementById('btnSubmitSpinner');
    const btn    = document.getElementById(IS_EDIT ? 'btnSubmitEdit' : 'btnSubmitCreate');

    if (btnTxt && btnSpin && btn) {
        btnTxt.classList.add('d-none');
        btnSpin.classList.remove('d-none');
        btn.disabled = true;
    }
});

// ── Scroll ke error pertama ─────────────────────────────────────
function scrollToFirstError() {
    const firstErr = document.querySelector('.is-invalid, .invalid-msg.show');
    if (firstErr) {
        firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
        const input = firstErr.closest('.pk-field')?.querySelector('input,select,textarea');
        input?.focus();
    }
}

})();
</script>

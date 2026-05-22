<?php /** app/modules/settings/view.php */ ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-0">Pengaturan Akun</h4>
    <small class="text-muted">Kelola profil, keamanan, dan preferensi akun Anda.</small></div>
</div>

<div class="row g-4">

    <!-- Profil -->
    <div class="col-lg-6">
        <div class="pk-card p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-person-circle me-2 text-primary"></i>Profil</h6>
            <form method="POST" action="index.php?page=settings&action=update_profile">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Nama Tampilan</label>
                    <input type="text" name="nama_display" class="form-control"
                           value="<?= e($current_user['nama_display']??'') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Email</label>
                    <input class="form-control bg-light" value="<?= e($current_user['email']??'') ?>" disabled>
                    <div class="form-text">Email tidak bisa diubah. Hubungi owner jika perlu.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Role</label>
                    <input class="form-control bg-light" value="<?= ucfirst(e($current_user['role']??'')) ?>" disabled>
                </div>
                <button class="btn btn-primary btn-sm">Simpan Profil</button>
            </form>
        </div>
    </div>

    <!-- Ganti Password -->
    <div class="col-lg-6">
        <div class="pk-card p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-key-fill me-2 text-warning"></i>Ganti Password</h6>
            <form method="POST" action="index.php?page=settings&action=update_password" novalidate id="pwdForm">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Password Lama <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" name="password_lama" id="pwdOld" class="form-control" required>
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                onclick="toggle('pwdOld')"><i class="bi bi-eye-fill"></i></button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Password Baru <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" name="password_baru" id="pwdNew" class="form-control"
                               required minlength="8" oninput="checkStrength(this.value)">
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                onclick="toggle('pwdNew')"><i class="bi bi-eye-fill"></i></button>
                    </div>
                    <div id="pwdStrength" class="mt-1" style="font-size:.72rem;"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Konfirmasi Password <span class="text-danger">*</span></label>
                    <input type="password" name="password_konfirmasi" id="pwdConf" class="form-control" required minlength="8">
                </div>
                <button class="btn btn-warning btn-sm">Ganti Password</button>
            </form>
        </div>
    </div>

    <!-- 2FA -->
    <div class="col-lg-6">
        <div class="pk-card p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-shield-lock-fill me-2 text-success"></i>Two-Factor Authentication (2FA)</h6>
                <?php if ($current_user['totp_enabled']): ?>
                <span class="badge bg-success">Aktif</span>
                <?php else: ?>
                <span class="badge bg-secondary">Tidak Aktif</span>
                <?php endif; ?>
            </div>

            <?php if (!$current_user['totp_enabled']): ?>
            <p class="text-muted small mb-3">
                Aktifkan 2FA untuk keamanan tambahan. Setiap login akan meminta kode dari aplikasi
                <strong>Google Authenticator</strong> atau <strong>Authy</strong>.
            </p>
            <a href="index.php?page=settings&action=setup_2fa" class="btn btn-success btn-sm">
                <i class="bi bi-shield-plus me-2"></i>Aktifkan 2FA
            </a>
            <?php else: ?>
            <p class="text-muted small mb-3">
                2FA aktif. Setiap login membutuhkan kode dari aplikasi authenticator Anda.
            </p>
            <div class="alert alert-warning py-2 small mb-3">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                Menonaktifkan 2FA akan mengurangi keamanan akun.
            </div>
            <form method="POST" action="index.php?page=settings&action=disable_2fa">
                <?= csrf_field() ?>
                <div class="input-group mb-2">
                    <input type="text" name="totp_code_disable" class="form-control form-control-sm"
                           placeholder="Kode 6 digit dari authenticator" maxlength="6" pattern="\d{6}" required
                           inputmode="numeric" autocomplete="one-time-code">
                    <button class="btn btn-danger btn-sm">Nonaktifkan</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Login History -->
    <div class="col-lg-6">
        <div class="pk-card p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2 text-info"></i>Riwayat Aktivitas</h6>
            <?php if (empty($login_history)): ?>
            <p class="text-muted small">Belum ada riwayat aktivitas.</p>
            <?php else: ?>
            <div style="max-height:260px; overflow-y:auto;">
                <?php foreach ($login_history as $h):
                    $icon = match($h['action']) {
                        'LOGIN'           => ['bi-check-circle-fill','success'],
                        'LOGIN_FAILED'    => ['bi-x-circle-fill','danger'],
                        'LOGIN_LOCKED'    => ['bi-lock-fill','danger'],
                        'ENABLE_2FA'      => ['bi-shield-check-fill','success'],
                        'DISABLE_2FA'     => ['bi-shield-x-fill','warning'],
                        'CHANGE_PASSWORD' => ['bi-key-fill','warning'],
                        default           => ['bi-info-circle-fill','secondary'],
                    };
                    $labels = [
                        'LOGIN'=>'Login berhasil','LOGIN_FAILED'=>'Login gagal',
                        'LOGIN_LOCKED'=>'Akun dikunci','ENABLE_2FA'=>'2FA diaktifkan',
                        'DISABLE_2FA'=>'2FA dinonaktifkan','CHANGE_PASSWORD'=>'Password diganti',
                    ];
                ?>
                <div class="d-flex gap-2 align-items-start border-bottom py-2 small">
                    <i class="bi <?= $icon[0] ?> text-<?= $icon[1] ?> mt-1"></i>
                    <div class="flex-grow-1">
                        <div class="fw-bold"><?= $labels[$h['action']] ?? e($h['action']) ?></div>
                        <div class="text-muted" style="font-size:.68rem;"><?= e(date('d M Y H:i',strtotime($h['created_at']))) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Link ke Audit Log (admin/owner) -->
<?php if (auth_is(['admin','owner'])): ?>
<div class="mt-4">
    <div class="pk-card p-3 d-flex align-items-center justify-content-between">
        <div>
            <div class="fw-bold"><i class="bi bi-clock-history me-2 text-secondary"></i>Audit Log</div>
            <div class="text-muted small mt-1">Lihat riwayat semua aktivitas sistem — siapa mengubah apa dan kapan.</div>
        </div>
        <a href="index.php?page=settings&action=audit_log" class="btn btn-outline-secondary btn-sm">
            Buka Log <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
</div>
<?php endif; ?>

<script>
function toggle(id) {
    const el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}
function checkStrength(pw) {
    const el = document.getElementById('pwdStrength');
    const checks = [pw.length >= 8, /[A-Z]/.test(pw), /[0-9]/.test(pw), /[^A-Za-z0-9]/.test(pw)];
    const score  = checks.filter(Boolean).length;
    const labels = ['','Lemah','Cukup','Kuat','Sangat Kuat'];
    const colors = ['','text-danger','text-warning','text-primary','text-success'];
    el.className = 'mt-1 fw-bold ' + (colors[score]||'');
    el.textContent = score > 0 ? 'Kekuatan: ' + (labels[score]||'') : '';
}
</script>

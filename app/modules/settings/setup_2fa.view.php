<?php /** app/modules/settings/setup_2fa.view.php */ ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-0"><i class="bi bi-shield-plus me-2 text-success"></i>Setup Two-Factor Auth</h4>
    <small class="text-muted">Ikuti langkah-langkah di bawah untuk mengaktifkan 2FA.</small></div>
    <a href="index.php?page=settings" class="btn btn-outline-secondary btn-sm">← Batal</a>
</div>

<div class="row g-4 justify-content-center">
    <div class="col-lg-7">

        <!-- Step 1 -->
        <div class="pk-card p-4 mb-3">
            <h6 class="fw-bold mb-3">
                <span class="badge bg-primary me-2">1</span>
                Download Aplikasi Authenticator
            </h6>
            <p class="text-muted small mb-2">Gunakan salah satu aplikasi berikut di smartphone Anda:</p>
            <div class="d-flex gap-3 flex-wrap">
                <span class="badge bg-light text-dark border px-3 py-2">
                    <i class="bi bi-google me-1"></i> Google Authenticator
                </span>
                <span class="badge bg-light text-dark border px-3 py-2">
                    <i class="bi bi-phone-fill me-1"></i> Authy
                </span>
                <span class="badge bg-light text-dark border px-3 py-2">
                    <i class="bi bi-microsoft me-1"></i> Microsoft Authenticator
                </span>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="pk-card p-4 mb-3">
            <h6 class="fw-bold mb-3">
                <span class="badge bg-primary me-2">2</span>
                Scan QR Code
            </h6>
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <img src="<?= e($qr_url) ?>" alt="QR Code 2FA" width="180" height="180"
                         class="rounded border" style="image-rendering: pixelated;">
                </div>
                <div class="col">
                    <p class="text-muted small mb-2">Buka aplikasi authenticator, pilih <strong>"Tambah akun"</strong>, lalu scan QR code di samping.</p>
                    <details class="small">
                        <summary class="text-primary" style="cursor:pointer;">Tidak bisa scan? Input manual</summary>
                        <div class="mt-2 p-2 rounded" style="background:var(--color-background-secondary);">
                            <div class="text-muted" style="font-size:.7rem;">Secret key:</div>
                            <code class="fw-bold" style="word-break:break-all; font-size:.8rem;"><?= e($totp_secret) ?></code>
                        </div>
                    </details>
                </div>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="pk-card p-4">
            <h6 class="fw-bold mb-3">
                <span class="badge bg-primary me-2">3</span>
                Verifikasi Kode
            </h6>
            <p class="text-muted small mb-3">Masukkan kode 6 digit yang muncul di aplikasi authenticator Anda untuk memverifikasi setup.</p>

            <form method="POST" action="index.php?page=settings&action=enable_2fa">
                <?= csrf_field() ?>
                <input type="hidden" name="totp_secret" value="<?= e($totp_secret) ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold small">Kode Verifikasi <span class="text-danger">*</span></label>
                    <input type="text" name="totp_code" class="form-control"
                           placeholder="000000" maxlength="6" pattern="\d{6}" required
                           inputmode="numeric" autocomplete="one-time-code"
                           style="font-size:1.5rem; letter-spacing:.3em; text-align:center; max-width:200px;">
                    <div class="form-text">Kode berubah setiap 30 detik.</div>
                </div>

                <div class="alert alert-warning py-2 small mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>Simpan secret key di tempat aman!</strong> Jika HP hilang dan Anda tidak punya secret key,
                    akun tidak bisa dibuka. Secret: <code><?= e($totp_secret) ?></code>
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="bi bi-shield-check me-2"></i>Aktifkan 2FA
                </button>
            </form>
        </div>

    </div>
</div>

<script>
// Auto-format input: hapus spasi, auto-submit ketika 6 digit
document.querySelector('[name="totp_code"]').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g,'').slice(0,6);
    if (this.value.length === 6) this.closest('form').submit();
});
</script>

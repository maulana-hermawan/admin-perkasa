<?php
/**
 * login-2fa.php — Halaman verifikasi TOTP (step 2 login)
 * Hanya bisa diakses setelah login berhasil AND user punya 2FA aktif.
 * Session: _2fa_pending = user_id
 */

declare(strict_types=1);

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/core/db.php';
require_once __DIR__ . '/app/core/helpers.php';
require_once __DIR__ . '/app/core/auth.php';
require_once __DIR__ . '/app/lib/TOTP.php';


// ── Global Exception & Error Handler ─────────────────────────
set_exception_handler(function(Throwable $e) {
    $debug = defined('APP_DEBUG') && APP_DEBUG;
    error_log('[Perkasa] Uncaught: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (headers_sent()) { echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>'; exit(); }
    http_response_code(500);
    $msg = $debug ? htmlspecialchars($e->getMessage() . ' [' . basename($e->getFile()) . ':' . $e->getLine() . ']')
                  : 'Terjadi kesalahan sistem. Silakan refresh atau hubungi admin.';
    echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'>"
       . "<title>Error</title><style>body{font-family:sans-serif;background:#f0f4f8;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:1rem}"
       . ".b{background:#fff;border-radius:12px;padding:2rem;max-width:500px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,.1);text-align:center}"
       . "h2{color:#c0392b;margin-bottom:.75rem}p{color:#555;font-size:.9rem}code{background:#f1f3f4;padding:.2rem .5rem;border-radius:4px;font-size:.78rem;word-break:break-all;display:block;margin-top:.5rem;text-align:left}"
       . ".btn{display:inline-block;margin:.75rem .25rem 0;padding:.5rem 1rem;background:#001233;color:#fff;border-radius:6px;text-decoration:none;font-size:.85rem}</style>"
       . "</head><body><div class='b'><h2>⚠ Terjadi Kesalahan</h2><p>" . $msg . "</p>"
       . "<a href='javascript:history.back()' class='btn'>← Kembali</a> <a href='index.php' class='btn'>🏠 Dashboard</a></div></body></html>";
    exit();
});

session_bootstrap();

// Harus ada session _2fa_pending
$pending_uid = $_SESSION['_2fa_pending'] ?? null;
if (!$pending_uid) {
    redirect('login.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $code = preg_replace('/\D/', '', post('totp_code', ''));
    $user = db_fetch("SELECT id, totp_secret, email, nama_display, role FROM users WHERE id=? AND totp_enabled=1","i",[$pending_uid]);

    if (!$user) {
        // 2FA tidak aktif atau user tidak ada — clear pending, redirect
        unset($_SESSION['_2fa_pending']);
        redirect('login.php');
    }

    if (TOTP::verify($user['totp_secret'], $code)) {
        // Verifikasi berhasil — selesaikan login
        unset($_SESSION['_2fa_pending']);
        session_regenerate_id(true);

        $_SESSION['user_id']    = (int)$user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name']  = $user['nama_display'] ?: explode('@', $user['email'])[0];
        $_SESSION['role']       = $user['role'];
        $_SESSION['_login_at']  = time();
        $_SESSION['_2fa_ok']    = true;

        db_execute("UPDATE users SET failed_login_count=0, locked_until=NULL, last_login_at=NOW() WHERE id=?","i",[$user['id']]);
        log_action('LOGIN_2FA_OK','users',(int)$user['id']);

        $intended = $_SESSION['_intended'] ?? 'index.php';
        unset($_SESSION['_intended']);
        redirect(str_starts_with($intended,'http') ? 'index.php' : $intended);
    } else {
        $error = 'Kode tidak valid atau sudah kedaluwarsa. Kode berubah setiap 30 detik.';
        log_action('LOGIN_2FA_FAILED','users',(int)$pending_uid);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi 2FA | Perkasa Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; min-height: 100vh; display:flex; align-items:center; justify-content:center; }
        .card-2fa { width:100%; max-width:400px; border-radius:16px; box-shadow: 0 8px 32px rgba(0,0,0,.12); }
        .otp-input { font-size:2rem; letter-spacing:.4em; text-align:center; }
    </style>
</head>
<body>
<div class="card card-2fa p-4 bg-white">
    <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 mb-3"
             style="width:64px;height:64px;">
            <i class="bi bi-shield-lock-fill text-success" style="font-size:1.8rem;"></i>
        </div>
        <h5 class="fw-bold mb-1">Verifikasi 2FA</h5>
        <p class="text-muted small mb-0">Masukkan kode 6 digit dari aplikasi authenticator Anda.</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger py-2 small mb-3">
        <i class="bi bi-x-circle-fill me-1"></i><?= e($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST">
        <?= csrf_field() ?>
        <div class="mb-3">
            <input type="text" name="totp_code" id="totpInput"
                   class="form-control otp-input <?= $error ? 'is-invalid' : '' ?>"
                   maxlength="6" pattern="\d{6}" required
                   inputmode="numeric" autocomplete="one-time-code"
                   placeholder="000000" autofocus>
        </div>
        <button type="submit" class="btn btn-success w-100 fw-bold">
            <i class="bi bi-arrow-right-circle me-2"></i>Verifikasi
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="login.php" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Login
        </a>
    </div>

    <div class="alert alert-light border mt-3 py-2 small text-muted">
        <i class="bi bi-info-circle me-1"></i>
        Kode tersedia di Google Authenticator, Authy, atau Microsoft Authenticator.
        Kode berubah setiap <span id="countdown">30</span> detik.
    </div>
</div>

<script>
// Auto-submit pada 6 digit
const inp = document.getElementById('totpInput');
inp.addEventListener('input', function() {
    this.value = this.value.replace(/\D/g,'').slice(0,6);
    if (this.value.length === 6) this.closest('form').submit();
});

// Countdown timer
(function() {
    function tick() {
        const sisa = 30 - (Math.floor(Date.now()/1000) % 30);
        document.getElementById('countdown').textContent = sisa;
    }
    tick();
    setInterval(tick, 1000);
})();
</script>
</body>
</html>

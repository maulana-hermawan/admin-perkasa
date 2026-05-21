<?php
/**
 * login.php — Perkasa Mulia Training Center v2.0
 * Halaman login (menggantikan auth.php lama).
 * Secure: CSRF, rate limit, session regenerate, password_hash.
 */

declare(strict_types=1);

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/core/db.php';
require_once __DIR__ . '/app/core/helpers.php';
require_once __DIR__ . '/app/core/auth.php';


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

// Jika sudah login, langsung redirect ke portal sesuai role
if (!empty($_SESSION['user_id'])) {
    $role_now = $_SESSION['role'] ?? '';
    $home = match ($role_now) {
        'siswa' => 'portal-siswa.php',
        'tutor' => 'portal-tutor.php',
        default => 'index.php',
    };
    redirect($home);
}

$error  = '';
$email_val = '';

// ── Handle POST login ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email    = post('email');
    $password = post('password');
    $email_val = $email;

    $result = auth_attempt($email, $password);

    if ($result['ok']) {
        $user = $result['user'];

        // ── 2FA check: jika user punya 2FA aktif, redirect ke verifikasi ──
        if (!empty($user['totp_enabled'])) {
            // Simpan session sementara untuk 2FA
            session_regenerate_id(true);
            $_SESSION['_2fa_pending'] = (int)$user['id'];
            // auth_attempt sudah set session penuh — kita hapus dulu, baru set setelah 2FA lulus
            unset($_SESSION['user_id'], $_SESSION['user_email'], $_SESSION['user_name'], $_SESSION['role'], $_SESSION['_login_at']);
            session_write_close();
            redirect('login-2fa.php');
        }

        unset($_SESSION['_intended']); // bersihkan intended URL lama

        // Selalu redirect ke halaman default sesuai role (paling aman)
        $home = match ($user['role']) {
            'siswa' => 'portal-siswa.php',
            'tutor' => 'portal-tutor.php',
            default => 'index.php?page=dashboard',
        };

        // Simpan session sebelum redirect agar tidak hilang
        session_write_close();
        redirect($home);
    }

    $error = $result['msg'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Perkasa Mulia Training Center</title>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/logo.svg">
    <link rel="apple-touch-icon" href="assets/logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #001233 0%, #0a2a5e 60%, #0d6efd22 100%);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .login-card {
            width: 380px; max-width: 95vw;
            border: none; border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,.4);
        }
        .login-brand {
            background: linear-gradient(135deg, #001233, #0a2a5e);
            border-radius: 20px 20px 0 0; padding: 28px 24px 20px;
            text-align: center;
        }
        .login-logo {
            width: 72px; height: 72px;
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: 14px;
            filter: drop-shadow(0 2px 8px rgba(0,0,0,.3));
        }
        .login-logo img {
            width: 100%; height: 100%;
            object-fit: contain;
        }
        /* Fallback jika SVG tidak load */
        .login-logo-fallback {
            width: 72px; height: 72px; background: rgba(255,255,255,.12);
            border-radius: 18px; display: inline-flex; align-items: center;
            justify-content: center; font-size: 32px; margin-bottom: 14px;
        }
        .form-control:focus { border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,.15); }
        .btn-login {
            background: #001233; border: none; color: #fff;
            font-weight: 700; letter-spacing: .05em; padding: 12px;
            border-radius: 10px; transition: all .2s;
        }
        .btn-login:hover { background: #0d6efd; transform: translateY(-1px); }
        .pw-toggle { cursor: pointer; }
    </style>
</head>
<body>
    <div class="login-card card">
        <!-- Brand -->
        <div class="login-brand">
            <div class="login-logo">
                <img src="<?= rtrim(APP_URL,'/') ?>/assets/logo.svg"
                     alt="Logo Perkasa Mulia Training Center"
                     onerror="this.parentElement.innerHTML='<div class=\'login-logo-fallback\'>🏋️</div>'">
            </div>
            <h5 class="text-white fw-bold mb-0" style="letter-spacing:.04em;">
                PERKASA MULIA
            </h5>
            <div class="text-white fw-semibold mb-1" style="font-size:.78rem;letter-spacing:.08em;opacity:.9;">
                TRAINING CENTER
            </div>
            <small class="text-white opacity-60" style="font-size:.7rem;">
                Bimbel Persiapan Polri &amp; TNI — Bogor
            </small>
        </div>

        <!-- Form -->
        <div class="card-body p-4">
            <h6 class="fw-bold text-dark mb-4 text-center">Masuk ke Sistem</h6>

            <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3 rounded-3">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                <?= e($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off" novalidate>
                <?= csrf_field() ?>

                <!-- Email -->
                <div class="mb-3">
                    <label class="form-label fw-bold small">Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-envelope text-muted"></i>
                        </span>
                        <input type="email" name="email" class="form-control border-start-0"
                               placeholder="admin@bimbelperkasa.id"
                               value="<?= e($email_val) ?>"
                               required autocomplete="email" autofocus>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label class="form-label fw-bold small">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="bi bi-lock text-muted"></i>
                        </span>
                        <input type="password" name="password" id="pwInput"
                               class="form-control border-start-0 border-end-0"
                               placeholder="••••••••" required autocomplete="current-password">
                        <span class="input-group-text bg-light border-start-0 pw-toggle"
                              onclick="togglePw()" title="Tampilkan/Sembunyikan">
                            <i class="bi bi-eye" id="pwEyeIcon"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn btn-login w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>MASUK
                </button>
            </form>
        </div>

        <div class="card-footer bg-transparent border-0 text-center pb-4">
            <small class="text-muted">
                Lupa password? Hubungi <strong>Admin</strong>
            </small>
        </div>
    </div>

    <script>
    function togglePw() {
        const inp  = document.getElementById('pwInput');
        const icon = document.getElementById('pwEyeIcon');
        if (inp.type === 'password') {
            inp.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            inp.type = 'password';
            icon.className = 'bi bi-eye';
        }
    }
    </script>
</body>
</html>

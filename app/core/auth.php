<?php
/**
 * app/core/auth.php
 * Fungsi autentikasi: login, logout, check session, rate limiting.
 * Semua auth logic wajib melalui fungsi di file ini.
 */

declare(strict_types=1);

// Dependency: db.php, helpers.php harus sudah di-require

// ============================================================
// CONSTANTS
// ============================================================
define('AUTH_MAX_ATTEMPTS', 5);    // Maksimal percobaan login gagal
define('AUTH_LOCKOUT_MIN',  5);    // Menit lockout setelah max attempts

// ============================================================
// LOGIN
// ============================================================

/**
 * Coba login dengan email & password.
 *
 * @return array ['ok' => bool, 'msg' => string, 'user' => array|null]
 *
 * @example
 *   $result = auth_attempt($email, $password);
 *   if ($result['ok']) redirect('index.php');
 *   else $error = $result['msg'];
 */
function auth_attempt(string $email, string $password): array
{
    $email = strtolower(trim($email));

    // Validasi format email dasar
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'msg' => 'Format email tidak valid.', 'user' => null];
    }

    // Ambil user dari DB
    $user = db_fetch(
        "SELECT id, email, password, role, nama_display, is_active,
                failed_login_count, locked_until, totp_enabled
         FROM users
         WHERE email = ? AND deleted_at IS NULL
         LIMIT 1",
        "s", [$email]
    );

    if (!$user) {
        // Tunda respons (timing attack mitigation)
        usleep(random_int(100000, 300000));
        return ['ok' => false, 'msg' => 'Email atau password salah.', 'user' => null];
    }

    // Cek apakah akun aktif
    if (!(bool)$user['is_active']) {
        return ['ok' => false, 'msg' => 'Akun Anda dinonaktifkan. Hubungi administrator.', 'user' => null];
    }

    // Cek lockout
    if (!empty($user['locked_until'])) {
        $locked_until_ts = strtotime($user['locked_until']);
        if ($locked_until_ts > time()) {
            $sisa_menit = (int)ceil(($locked_until_ts - time()) / 60);
            return [
                'ok'   => false,
                'msg'  => "Akun terkunci karena terlalu banyak percobaan gagal. Coba lagi dalam {$sisa_menit} menit.",
                'user' => null,
            ];
        }
        // Lockout sudah berakhir, reset
        db_query(
            "UPDATE users SET failed_login_count = 0, locked_until = NULL WHERE id = ?",
            "i", [$user['id']]
        );
        $user['failed_login_count'] = 0;
        $user['locked_until']       = null;
    }

    // Verifikasi password
    $password_valid = password_verify($password, $user['password']);

    // Fallback untuk masa transisi: cek apakah masih plaintext (sebelum migration)
    if (!$password_valid && !str_starts_with($user['password'], '$2')) {
        $password_valid = hash_equals($user['password'], $password);
        if ($password_valid) {
            // Hash otomatis password lama saat pertama login berhasil
            $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            db_query("UPDATE users SET password = ? WHERE id = ?", "si", [$hashed, $user['id']]);
        }
    }

    if (!$password_valid) {
        // Increment failed counter
        $new_count = (int)$user['failed_login_count'] + 1;
        $lock_sql  = '';
        $lock_params = [];

        if ($new_count >= AUTH_MAX_ATTEMPTS) {
            $locked_until = date('Y-m-d H:i:s', time() + AUTH_LOCKOUT_MIN * 60);
            db_query(
                "UPDATE users SET failed_login_count = ?, locked_until = ? WHERE id = ?",
                "isi", [$new_count, $locked_until, $user['id']]
            );
            log_action('LOGIN_LOCKED', 'users', (int)$user['id']);
            return [
                'ok'  => false,
                'msg' => "Akun dikunci selama " . AUTH_LOCKOUT_MIN . " menit karena terlalu banyak percobaan gagal.",
                'user'=> null,
            ];
        }

        db_query(
            "UPDATE users SET failed_login_count = ? WHERE id = ?",
            "ii", [$new_count, $user['id']]
        );
        $sisa = AUTH_MAX_ATTEMPTS - $new_count;
        log_action('LOGIN_FAILED', 'users', (int)$user['id']);

        return [
            'ok'  => false,
            'msg' => "Email atau password salah. Sisa percobaan: {$sisa}.",
            'user'=> null,
        ];
    }

    // === LOGIN BERHASIL ===

    // Regenerate session ID (cegah session fixation)
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    // Set session
    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name']  = $user['nama_display'] ?: explode('@', $user['email'])[0];
    $_SESSION['role']       = $user['role'];
    $_SESSION['_login_at']  = time();

    // Reset failed counter & update last_login
    db_query(
        "UPDATE users SET failed_login_count = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?",
        "i", [$user['id']]
    );

    log_action('LOGIN', 'users', (int)$user['id']);

    return ['ok' => true, 'msg' => 'Login berhasil.', 'user' => $user];
}

// ============================================================
// LOGOUT
// ============================================================

/**
 * Logout: catat audit, destroy session.
 */
function auth_logout(): void
{
    $user_id = $_SESSION['user_id'] ?? null;

    if ($user_id) {
        log_action('LOGOUT', 'users', (int)$user_id);
    }

    // Hapus semua data session
    $_SESSION = [];

    // Hapus session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    session_destroy();
}

// ============================================================
// CHECK AUTH & ROLE
// ============================================================

/**
 * Pastikan user sudah login. Redirect ke login.php jika belum.
 */
function check_auth(): void
{
    if (empty($_SESSION['user_id'])) {
        // Simpan URL yang dituju untuk redirect setelah login
        $_SESSION['_intended'] = $_SERVER['REQUEST_URI'] ?? 'index.php';
        redirect('login.php');
    }

    // Session timeout check
    $login_at = $_SESSION['_login_at'] ?? 0;
    if (time() - $login_at > SESSION_LIFETIME) {
        flash('warning', 'Sesi Anda habis. Silakan login kembali.');
        auth_logout();
        redirect('login.php');
    }

    // Perbarui waktu aktif
    $_SESSION['_login_at'] = time();
}

/**
 * Pastikan user punya role tertentu.
 * @param string|array $allowed  Role yang diizinkan, misal 'admin' atau ['admin','owner']
 */
function check_role(string|array $allowed): void
{
    check_auth();
    $allowed = (array)$allowed;
    $role    = $_SESSION['role'] ?? '';

    if (!in_array($role, $allowed, true)) {
        http_response_code(403);
        include __DIR__ . '/../layouts/403.php';
        exit;
    }
}

// ============================================================
// HELPER SESSION GETTERS
// ============================================================

/** Return user_id sesi saat ini, atau null */
function auth_id(): ?int
{
    return !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/** Return role sesi saat ini */
function auth_role(): string
{
    return $_SESSION['role'] ?? '';
}

/** Return nama display user sesi saat ini */
function auth_name(): string
{
    return $_SESSION['user_name'] ?? 'User';
}

/** Return email user sesi saat ini */
function auth_email(): string
{
    return $_SESSION['user_email'] ?? '';
}

/** Apakah user punya role tertentu? */
function auth_is(string|array $roles): bool
{
    $roles = (array)$roles;
    return in_array(auth_role(), $roles, true);
}

/**
 * Fetch data lengkap user yang sedang login dari DB.
 * Pakai dengan hemat (hanya saat butuh data yang belum ada di session).
 */
function current_user(): ?array
{
    $id = auth_id();
    if (!$id) return null;

    return db_fetch(
        "SELECT id, email, role, nama_display, is_active, last_login_at, created_at
         FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1",
        "i", [$id]
    );
}

// ============================================================
// SESSION BOOTSTRAP
// ============================================================

/**
 * Inisialisasi session dengan konfigurasi aman.
 * Panggil di awal setiap request, sebelum fungsi auth lain.
 */
function session_bootstrap(): void
{
    if (session_status() !== PHP_SESSION_NONE) return; // Sudah aktif

    session_name(SESSION_NAME);

    session_set_cookie_params([
        'lifetime' => 0,                  // Session cookie (hilang saat browser ditutup)
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']), // HTTPS only jika production
        'httponly' => true,               // Tidak bisa diakses JavaScript
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', (string)SESSION_LIFETIME);

    session_start();
}

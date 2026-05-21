<?php
/**
 * app/modules/settings/controller.php
 * Pengaturan akun: profil, ganti password, 2FA TOTP
 */

declare(strict_types=1);

require_once __DIR__ . '/../../lib/TOTP.php';

$user_id = auth_id();

// ── POST handlers ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // Ganti password
    if ($action === 'update_password') {
        $old  = post('password_lama');
        $new  = post('password_baru');
        $conf = post('password_konfirmasi');

        $user = db_fetch("SELECT id, password FROM users WHERE id=?","i",[$user_id]);
        if (!password_verify($old, $user['password'])) {
            set_flash('danger','Password lama salah.');
        } elseif (strlen($new) < 8) {
            set_flash('danger','Password baru minimal 8 karakter.');
        } elseif ($new !== $conf) {
            set_flash('danger','Konfirmasi password tidak cocok.');
        } else {
            db_execute("UPDATE users SET password=? WHERE id=?","si",[password_hash($new, PASSWORD_DEFAULT),$user_id]);
            log_action('CHANGE_PASSWORD','users',$user_id);
            set_flash('success','Password berhasil diganti.');
        }
        redirect('index.php?page=settings');
    }

    // Update profil
    if ($action === 'update_profile') {
        $nama = trim(post('nama_display',''));
        if ($nama) {
            db_execute("UPDATE users SET nama_display=? WHERE id=?","si",[$nama,$user_id]);
            $_SESSION['user_name'] = $nama;
            log_action('UPDATE_PROFILE','users',$user_id);
            set_flash('success','Profil diperbarui.');
        }
        redirect('index.php?page=settings');
    }

    // Enable 2FA — verifikasi kode pertama
    if ($action === 'enable_2fa') {
        $secret = post('totp_secret');
        $code   = post('totp_code');
        if (!$secret || !TOTP::verify($secret, $code)) {
            set_flash('danger','Kode TOTP tidak valid. Coba scan ulang QR code dan masukkan kode dari aplikasi authenticator.');
            redirect('index.php?page=settings&action=setup_2fa');
        }
        db_execute("UPDATE users SET totp_secret=?, totp_enabled=1 WHERE id=?","si",[$secret,$user_id]);
        log_action('ENABLE_2FA','users',$user_id);
        set_flash('success','2FA berhasil diaktifkan! Akun Anda sekarang lebih aman.');
        redirect('index.php?page=settings');
    }

    // Disable 2FA
    if ($action === 'disable_2fa') {
        $code = post('totp_code_disable');
        $user = db_fetch("SELECT totp_secret FROM users WHERE id=?","i",[$user_id]);
        if (!TOTP::verify($user['totp_secret'] ?? '', $code)) {
            set_flash('danger','Kode TOTP salah. 2FA tidak dinonaktifkan.');
        } else {
            db_execute("UPDATE users SET totp_secret=NULL, totp_enabled=0 WHERE id=?","i",[$user_id]);
            log_action('DISABLE_2FA','users',$user_id);
            set_flash('warning','2FA dinonaktifkan. Akun kurang terlindungi.');
        }
        redirect('index.php?page=settings');
    }
}

// ── GET: setup 2FA — generate secret baru ─────────────────────
if ($action === 'setup_2fa') {
    $totp_secret = TOTP::generateSecret();
    $_SESSION['_pending_totp'] = $totp_secret; // simpan sementara
    $qr_url = TOTP::getQRUrl($totp_secret, auth_email());
    $otp_uri = TOTP::getUri($totp_secret, auth_email());

    return [
        'title'       => 'Setup 2FA',
        'view'        => __DIR__ . '/setup_2fa.view.php',
        'breadcrumbs' => [['label'=>'Pengaturan','url'=>'index.php?page=settings'],['label'=>'Setup 2FA']],
        'totp_secret' => $totp_secret,
        'qr_url'      => $qr_url,
        'otp_uri'     => $otp_uri,
    ];
}

// ── GET: main settings page ────────────────────────────────────
$current_user = db_fetch(
    "SELECT id, email, nama_display, role, totp_enabled, last_login_at FROM users WHERE id=?",
    "i", [$user_id]
);

$login_history = db_fetch_all(
    "SELECT action, created_at, ip_address FROM audit_log WHERE user_id=? AND action IN ('LOGIN','LOGIN_FAILED','LOGIN_LOCKED','ENABLE_2FA','DISABLE_2FA','CHANGE_PASSWORD') ORDER BY created_at DESC LIMIT 10",
    "i", [$user_id]
);

return [
    'title'         => 'Pengaturan Akun',
    'view'          => __DIR__ . '/view.php',
    'breadcrumbs'   => [['label'=>'Pengaturan']],
    'current_user'  => $current_user,
    'login_history' => $login_history,
];

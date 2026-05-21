<?php
/**
 * portal-tutor.php — Entry point Portal Tutor (mobile-first)
 * Route: /admin/portal-tutor.php?page=xxx
 */

declare(strict_types=1);

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/app.php';
require_once __DIR__ . '/app/core/db.php';
require_once __DIR__ . '/app/core/helpers.php';
require_once __DIR__ . '/app/core/auth.php';
require_once __DIR__ . '/app/core/validator.php';


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
check_auth();
check_role(['tutor', 'admin', 'owner']);

// Pastikan ada tutor record
$tutor_data = db_fetch(
    "SELECT t.* FROM tutor t WHERE t.user_id=? AND t.deleted_at IS NULL",
    "i", [auth_id()]
);

// Admin/owner bisa akses semua tutor, pilih tutor_id via GET
if (!$tutor_data && in_array(auth_role(), ['admin','owner'])) {
    $preview_id = (int)($_GET['tutor_id'] ?? 0);
    if ($preview_id) {
        $tutor_data = db_fetch("SELECT * FROM tutor WHERE id=? AND deleted_at IS NULL","i",[$preview_id]);
    }
    if (!$tutor_data) {
        // Tampilkan pilihan tutor
        $all_tutors = db_fetch_all("SELECT id,nama_lengkap FROM tutor WHERE deleted_at IS NULL AND status_aktif=1 ORDER BY nama_lengkap");
        require __DIR__ . '/app/layouts/tutor.php';
        exit();
    }
}

if (!$tutor_data) {
    die('<div style="text-align:center;padding:2rem;">Akun tutor tidak ditemukan. Hubungi admin.</div>');
}

$ALLOWED_PAGES = ['dashboard', 'jadwal', 'attendance', 'nilai', 'gaji'];
$page   = get('page', 'dashboard');
$action = get('action', 'index');

if (!in_array($page, $ALLOWED_PAGES, true)) $page = 'dashboard';

$module_map = [
    'dashboard'  => __DIR__ . '/app/modules/portal_tutor/dashboard.php',
    'jadwal'     => __DIR__ . '/app/modules/portal_tutor/jadwal.php',
    'attendance' => __DIR__ . '/app/modules/portal_tutor/attendance.php',
    'nilai'      => __DIR__ . '/app/modules/portal_tutor/nilai.php',
    'gaji'       => __DIR__ . '/app/modules/portal_tutor/gaji.php',
];

$view_data = ['title' => 'Portal Tutor', 'view' => null, 'tutor' => $tutor_data];

if (isset($module_map[$page]) && is_file($module_map[$page])) {
    $module_result = require $module_map[$page];
    if (is_array($module_result)) {
        $view_data = array_merge($view_data, $module_result);
    }
}

require __DIR__ . '/app/layouts/tutor.php';

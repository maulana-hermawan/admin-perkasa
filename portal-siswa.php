<?php
/**
 * portal-siswa.php — Entry point Portal Siswa (mobile-first)
 */
declare(strict_types=1);

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/app.php';
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
check_auth();
check_role(['siswa', 'admin', 'owner']);

// Ambil data siswa dari session user_id
$siswa_data = db_fetch(
    "SELECT s.*, u.email FROM siswa s JOIN users u ON s.user_id=u.id WHERE s.user_id=? AND s.deleted_at IS NULL",
    "i", [auth_id()]
);

// Admin/owner bisa preview siswa tertentu
if (!$siswa_data && in_array(auth_role(), ['admin','owner'])) {
    $preview_id = (int)($_GET['siswa_id'] ?? 0);
    if ($preview_id) {
        $siswa_data = db_fetch(
            "SELECT s.*, u.email FROM siswa s JOIN users u ON s.user_id=u.id WHERE s.id=? AND s.deleted_at IS NULL",
            "i", [$preview_id]
        );
    }
    if (!$siswa_data) {
        $all_siswa = db_fetch_all(
            "SELECT id, nama_lengkap, nomor_induk FROM siswa WHERE deleted_at IS NULL AND status_siswa='Aktif' ORDER BY nama_lengkap LIMIT 50"
        );
        $view_data = ['title'=>'Preview Portal Siswa','view'=>null,'siswa'=>['nama_lengkap'=>'Preview','nomor_induk'=>''],'all_siswa'=>$all_siswa];
        require __DIR__ . '/app/layouts/siswa.php';
        exit();
    }
}

if (!$siswa_data) {
    die('<div style="text-align:center;padding:2rem;font-family:sans-serif;">Data siswa tidak ditemukan. Hubungi admin.</div>');
}

$ALLOWED_PAGES = ['dashboard','jadwal','nilai','tagihan','profil'];
$page   = get('page', 'dashboard');
if (!in_array($page, $ALLOWED_PAGES, true)) $page = 'dashboard';

$modules = [
    'dashboard' => __DIR__ . '/app/modules/portal_siswa/dashboard.php',
    'jadwal'    => __DIR__ . '/app/modules/portal_siswa/jadwal.php',
    'nilai'     => __DIR__ . '/app/modules/portal_siswa/nilai.php',
    'tagihan'   => __DIR__ . '/app/modules/portal_siswa/tagihan.php',
    'profil'    => __DIR__ . '/app/modules/portal_siswa/profil.php',
];

$view_data = ['title'=>'Portal Siswa','view'=>null,'siswa'=>$siswa_data];

if (isset($modules[$page]) && is_file($modules[$page])) {
    $result = require $modules[$page];
    if (is_array($result)) $view_data = array_merge($view_data, $result);
}

require __DIR__ . '/app/layouts/siswa.php';

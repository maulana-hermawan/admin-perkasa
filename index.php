<?php
/**
 * index.php — Front Controller
 * Perkasa Mulia Training Center v2.0
 * Module 6.3 — Refactor Routing & Layout
 *
 * Pattern: GET ?page=xxx&action=yyy
 *   - page    : module (dashboard|siswa|keuangan|jadwal|nilai)
 *   - action  : index|create|store|edit|update|delete|detail
 */

declare(strict_types=1);

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/app.php';
require_once __DIR__ . '/app/core/db.php';
require_once __DIR__ . '/app/core/helpers.php';
require_once __DIR__ . '/app/core/auth.php';
require_once __DIR__ . '/app/core/validator.php';
require_once __DIR__ . '/app/core/cache.php';


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
check_role(['admin', 'owner']);

// ── Routing ──────────────────────────────────────────────────
$ALLOWED_PAGES = ['dashboard', 'siswa', 'keuangan', 'jadwal', 'nilai', 'tutor',
                  'pembayaran', 'attendance', 'laporan', 'settings', 'pendaftaran', 'program'];

$page   = get('page', 'dashboard');
$action = get('action', 'index');

if (!in_array($page, $ALLOWED_PAGES, true)) {
    $page = 'dashboard';
}

// Whitelist action untuk cegah path traversal
$ALLOWED_ACTIONS = ['index', 'create', 'store', 'edit', 'update', 'delete', 'detail',
                    'store_jasmani', 'store_mapel', 'store_skd', 'store_psikologi',
                    'bulk', 'tab_data', 'reset_password',
                    'rekap_gaji', 'bayar_gaji', 'rekap_tutor', 'export_rekap_tutor', 'store_bulk', 'form',
                    'import', 'template', 'process_import',
                    'trash', 'restore',
                    'rekap_nilai', 'export_rekap_nilai',
                    'export_siswa', 'export_transaksi', 'export_gaji', 'export_nilai', 'rapor',
                    'setup_2fa', 'enable_2fa', 'disable_2fa',
                    'update_password', 'update_profile',
                    'terima', 'tolak', 'proses',
                    // AJAX actions
                    'hitung_binjas', 'rekap_siswa', 'get_json', 'get_memberships'];
if (!in_array($action, $ALLOWED_ACTIONS, true)) {
    $action = 'index';
}

// ── Load Controller ───────────────────────────────────────────
$controller_path = __DIR__ . "/app/modules/{$page}/controller.php";

if (!is_file($controller_path)) {
    http_response_code(404);
    $view_data = ['title' => '404 — Halaman Tidak Ditemukan', 'view' => null];
} else {
    // Controller HARUS return array $view_data berisi:
    //   'title' => string
    //   'view'  => string (path ke view file)
    //   + data lain sesuai kebutuhan module
    $view_data = require $controller_path;
}

// ── Render Layout ─────────────────────────────────────────────
require __DIR__ . '/app/layouts/admin.php';

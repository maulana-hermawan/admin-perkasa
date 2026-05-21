<?php
/**
 * api.php — Clean entry point for /admin/api/v1/ requests.
 * Usage: GET /admin/api.php?v=1&path=siswa
 *
 * Or with .htaccess RewriteRule (see README).
 */

$_GET['_path'] = $_GET['path'] ?? '';
require __DIR__ . '/app/api/v1/_router.php';

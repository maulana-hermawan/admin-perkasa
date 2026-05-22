<?php
/**
 * app/config/database.php
 * Load konfigurasi dari .env, setup koneksi DB.
 */

declare(strict_types=1);

// ── Tentukan ROOT_PATH ────────────────────────────────────────
// app/config/database.php → app/config → app → ROOT
define('ROOT_PATH', dirname(__DIR__, 2));

// ── env() helper ─────────────────────────────────────────────
function env(string $key, mixed $default = null): mixed
{
    $val = $_ENV[$key] ?? getenv($key);
    if ($val === false || $val === null) return $default;
    if ($val === 'true')  return true;
    if ($val === 'false') return false;
    if ($val === 'null')  return null;
    return $val;
}

// ── Load .env ────────────────────────────────────────────────
$env_path = ROOT_PATH . '/.env';

if (!is_file($env_path)) {
    _show_setup_page($env_path);
}

// Parse .env
$lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;

    $eq_pos = strpos($line, '=');
    if ($eq_pos === false) continue;

    $key   = trim(substr($line, 0, $eq_pos));
    $value = trim(substr($line, $eq_pos + 1));

    // Strip inline comments (spasi + #)
    if (($pos = strpos($value, ' #')) !== false) {
        $value = trim(substr($value, 0, $pos));
    }
    // Strip surrounding quotes
    if (strlen($value) >= 2 &&
        (($value[0] === '"' && $value[-1] === '"') ||
         ($value[0] === "'" && $value[-1] === "'"))) {
        $value = substr($value, 1, -1);
    }

    if (!array_key_exists($key, $_ENV)) {
        $_ENV[$key]  = $value;
        putenv("{$key}={$value}");
    }
}

// ── Define constants ─────────────────────────────────────────
define('APP_NAME',  env('APP_NAME', 'Perkasa Mulia Training Center'));
define('APP_URL',   rtrim((string)env('APP_URL', ''), '/'));
define('APP_ENV',   env('APP_ENV', 'production'));
define('APP_DEBUG', (bool)env('APP_DEBUG', false));
define('APP_TZ',    env('APP_TIMEZONE', 'Asia/Jakarta'));

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER', ''));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', ''));
define('DB_PORT', (int)env('DB_PORT', 3306));

define('SESSION_NAME',     env('SESSION_NAME', 'pk_sess'));
define('SESSION_LIFETIME', (int)env('SESSION_LIFETIME', 7200));
define('CSRF_LENGTH',      (int)env('CSRF_TOKEN_LENGTH', 32));

define('STORAGE_PATH', ROOT_PATH . '/storage');
define('LOG_PATH',     STORAGE_PATH . '/logs');
define('UPLOAD_PATH',  STORAGE_PATH . '/uploads');

date_default_timezone_set(APP_TZ);

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    if (is_dir(LOG_PATH)) {
        ini_set('error_log', LOG_PATH . '/php_errors.log');
    }
}

// ── Validasi DB credentials tidak kosong ─────────────────────
if (!DB_USER || !DB_NAME) {
    _show_setup_page($env_path, 'db_empty');
}

// ── Halaman setup jika .env belum ada atau DB kosong ────────
function _show_setup_page(string $env_path, string $issue = 'no_env'): never
{
    $root = ROOT_PATH;

    // Deteksi base URL otomatis
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script   = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    $base_url = "$scheme://$host$script";

    // Konten .env yang harus dibuat
    $env_content = <<<ENV
# Salin isi ini ke file: .env (di folder admin/)
# ================================================

# Database Hostinger — isi sesuai panel Anda
DB_HOST=localhost
DB_USER=NAMA_USER_DATABASE_ANDA
DB_PASS=PASSWORD_DATABASE_ANDA
DB_NAME=NAMA_DATABASE_ANDA
DB_PORT=3306

# Aplikasi
APP_NAME=Perkasa Mulia Training Center
APP_URL=$base_url
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Jakarta

# Session
SESSION_NAME=pk_sess
SESSION_LIFETIME=7200
CSRF_TOKEN_LENGTH=32

# WhatsApp Fonnte (opsional - isi nanti)
FONNTE_TOKEN=
FONNTE_SENDER=
ADMIN_WA_1=6287777538280
ADMIN_WA_1_NAME=Coach Bagus
ADMIN_WA_2=6281235647133
ADMIN_WA_2_NAME=Miss Dina

# CAT Webhook (opsional - isi nanti)
CAT_WEBHOOK_SECRET=perkasa-cat-secret-2026

# API JWT (ganti dengan string acak)
API_JWT_SECRET=perkasa-mulia-api-secret-ganti-ini-2026
API_KEY=
ENV;

    $title    = $issue === 'db_empty' ? 'Database belum dikonfigurasi' : 'File .env belum dibuat';
    $icon     = $issue === 'db_empty' ? '⚙️' : '📋';
    $step_no  = $issue === 'db_empty' ? '2' : '1';
    $active1  = $step_no === '1' ? 'active' : '';
    $active2  = $step_no === '2' ? 'active' : '';

    http_response_code(503);
    echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Setup Diperlukan — Perkasa Admin</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f0f4f8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem}
.card{background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.10);max-width:680px;width:100%;overflow:hidden}
.header{background:#001233;color:#fff;padding:1.5rem 2rem}
.header h1{font-size:1.2rem;font-weight:700;margin-bottom:.25rem}
.header p{opacity:.7;font-size:.85rem}
.body{padding:1.5rem 2rem}
.alert{border-radius:8px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.88rem}
.alert-danger{background:#fff0f0;border:1px solid #f5c6cb;color:#721c24}
.alert-info{background:#e8f4fd;border:1px solid #b8daff;color:#0c5460}
.steps{counter-reset:step;list-style:none;padding:0}
.steps li{display:flex;gap:.75rem;margin-bottom:1rem;padding:.75rem;background:#f8f9fa;border-radius:8px;font-size:.88rem}
.steps li:before{counter-increment:step;content:counter(step);background:#001233;color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;flex-shrink:0;margin-top:1px}
.steps li.active{background:#e8f4fd;border:1px solid #b8daff}
pre{background:#1e1e2e;color:#cdd6f4;border-radius:8px;padding:1rem;overflow-x:auto;font-size:.78rem;line-height:1.6;margin:.5rem 0 1rem}
.label{font-size:.75rem;font-weight:700;color:#666;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.25rem}
.path{font-family:monospace;background:#f1f3f4;padding:.2rem .5rem;border-radius:4px;font-size:.85rem;word-break:break-all}
.btn{display:inline-block;padding:.5rem 1.2rem;background:#001233;color:#fff;border-radius:6px;text-decoration:none;font-size:.85rem;margin-top:.5rem}
.btn:hover{background:#1a3a6b}
</style>
</head>
<body>
<div class="card">
  <div class="header">
    <h1>{$icon} Setup Diperlukan — Perkasa Admin v2.0</h1>
    <p>Langkah konfigurasi awal sebelum aplikasi bisa digunakan</p>
  </div>
  <div class="body">

    <div class="alert alert-danger">
      <strong>⚠ {$title}</strong><br>
      Aplikasi tidak bisa berjalan karena konfigurasi database belum diisi.
    </div>

    <ol class="steps">
      <li class="{$active1}">
        <div>
          <strong>Buat file <code>.env</code> di folder admin</strong><br>
          <div class="label" style="margin-top:.5rem">Path yang dibutuhkan:</div>
          <div class="path">{$env_path}</div>
          <div style="margin-top:.5rem;font-size:.82rem;color:#666">
            Cara: Login File Manager Hostinger → navigasi ke folder <code>admin/</code> → klik "New File" → nama: <code>.env</code>
          </div>
        </div>
      </li>
      <li class="{$active2}">
        <div>
          <strong>Isi konten <code>.env</code> dengan data berikut</strong><br>
          <div style="margin-top:.5rem;font-size:.82rem;color:#666">Ganti <code>NAMA_USER_DATABASE_ANDA</code>, <code>PASSWORD_DATABASE_ANDA</code>, <code>NAMA_DATABASE_ANDA</code> sesuai panel Hostinger Anda:</div>
          <pre>{$env_content}</pre>
          <div style="font-size:.82rem;color:#666">
            💡 Tip: Cari credentials DB di Hostinger Panel → Databases → MySQL → klik nama database
          </div>
        </div>
      </li>
      <li>
        <div>
          <strong>Import database</strong><br>
          Login phpMyAdmin → pilih database Anda → Import → pilih file <code>database/install.sql</code>
        </div>
      </li>
      <li>
        <div>
          <strong>Refresh halaman ini</strong><br>
          Setelah <code>.env</code> dibuat dan database diimport, refresh browser ini.
        </div>
      </li>
    </ol>

    <div class="alert alert-info" style="margin-top:1rem">
      <strong>ℹ Informasi Database Hostinger:</strong><br>
      DB_HOST biasanya: <code>localhost</code><br>
      DB_USER, DB_PASS, DB_NAME: lihat di panel Hostinger → <em>Databases → MySQL Databases</em>
    </div>

    <a href="javascript:location.reload()" class="btn">🔄 Refresh Setelah Setup</a>

  </div>
</div>
</body>
</html>
HTML;
    exit();
}

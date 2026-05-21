<?php
/**
 * app/core/db.php
 * Wrapper MySQLi dengan prepared statement otomatis.
 * Semua query DB di aplikasi wajib melalui fungsi di file ini.
 *
 * @example
 *   $siswa = db_fetch("SELECT * FROM siswa WHERE id = ?", "i", [$id]);
 *   $all   = db_fetch_all("SELECT * FROM siswa WHERE status_siswa = ?", "s", ["Aktif"]);
 *   db_query("UPDATE siswa SET nama_lengkap = ? WHERE id = ?", "si", [$nama, $id]);
 *   $id    = db_insert("INSERT INTO siswa (nama_lengkap) VALUES (?)", "s", [$nama]);
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

// ============================================================
// KONEKSI (singleton)
// ============================================================
function db_connect(): mysqli
{
    static $conn = null;

    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

        if (!$conn) {
            $err = mysqli_connect_error();
            error_log("[DB] Connection failed: {$err}");
            _db_error_page($err);
        }

        mysqli_set_charset($conn, 'utf8mb4');
        mysqli_query($conn, "SET time_zone = '+07:00'");
    }

    return $conn;
}

function _db_error_page(string $err): never
{
    http_response_code(503);

    // Sembunyikan detail di production, tampilkan di debug
    $debug   = defined('APP_DEBUG') && APP_DEBUG;
    $db_host = defined('DB_HOST') ? DB_HOST : '?';
    $db_user = defined('DB_USER') ? DB_USER : '?';
    $db_name = defined('DB_NAME') ? DB_NAME : '?';
    $db_port = defined('DB_PORT') ? DB_PORT : 3306;

    // Deteksi kemungkinan penyebab
    $hints = [];
    if (!$db_user || $db_user === 'NAMA_USER_DATABASE_ANDA') {
        $hints[] = 'DB_USER di file .env belum diisi';
    }
    if (!$db_name || $db_name === 'NAMA_DATABASE_ANDA') {
        $hints[] = 'DB_NAME di file .env belum diisi';
    }
    if (str_contains($err, 'Access denied')) {
        $hints[] = 'DB_PASS salah — periksa password database di panel Hostinger';
    }
    if (str_contains($err, 'Unknown database')) {
        $hints[] = "DB_NAME ({$db_name}) tidak ditemukan — pastikan database sudah dibuat di Hostinger";
    }
    if (str_contains($err, "Can't connect") || str_contains($err, 'refused')) {
        $hints[] = "DB_HOST ({$db_host}) tidak bisa diakses — biasanya 'localhost' di Hostinger";
    }
    if (empty($hints)) {
        $hints[] = 'Periksa DB_HOST, DB_USER, DB_PASS, DB_NAME di file .env';
    }

    $hints_html = implode('</li><li>', array_map('htmlspecialchars', $hints));
    $err_html   = htmlspecialchars($err);
    $env_path   = defined('ROOT_PATH') ? ROOT_PATH . '/.env' : '(root)/.env';

    echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Koneksi Database Gagal — Perkasa Admin</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f0f4f8;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem}
.card{background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.10);max-width:640px;width:100%;overflow:hidden}
.header{background:#c0392b;color:#fff;padding:1.2rem 1.8rem}
.header h1{font-size:1.1rem;font-weight:700}
.header p{opacity:.8;font-size:.82rem;margin-top:.2rem}
.body{padding:1.5rem 1.8rem}
.section{margin-bottom:1.2rem}
.label{font-size:.72rem;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.35rem}
.hint-list{background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:.75rem 1rem}
.hint-list li{font-size:.88rem;color:#5d4037;padding:.2rem 0}
.info-grid{display:grid;grid-template-columns:auto 1fr;gap:.3rem .75rem;font-size:.84rem;background:#f8f9fa;border-radius:8px;padding:.75rem 1rem}
.info-grid .k{font-weight:700;color:#444}
.info-grid .v{font-family:monospace;color:#0070f3;word-break:break-all}
.err-box{background:#1e1e2e;color:#f38ba8;border-radius:8px;padding:.75rem 1rem;font-family:monospace;font-size:.8rem;word-break:break-all}
.steps{list-style:decimal;padding-left:1.2rem}
.steps li{font-size:.87rem;color:#333;padding:.2rem 0}
.btn{display:inline-block;margin-top:1rem;padding:.5rem 1.2rem;background:#c0392b;color:#fff;border-radius:6px;text-decoration:none;font-size:.85rem}
pre{background:#1e1e2e;color:#a6e3a1;border-radius:8px;padding:.75rem 1rem;font-size:.78rem;overflow-x:auto;margin-top:.5rem}
</style>
</head>
<body>
<div class="card">
  <div class="header">
    <h1>🔴 Koneksi Database Gagal</h1>
    <p>Aplikasi tidak bisa terhubung ke MySQL. Ikuti langkah di bawah.</p>
  </div>
  <div class="body">

    <div class="section">
      <div class="label">Kemungkinan Penyebab</div>
      <div class="hint-list"><ul><li>{$hints_html}</li></ul></div>
    </div>

    <div class="section">
      <div class="label">Konfigurasi Saat Ini (dari .env)</div>
      <div class="info-grid">
        <span class="k">DB_HOST</span><span class="v">{$db_host}</span>
        <span class="k">DB_USER</span><span class="v">{$db_user}</span>
        <span class="k">DB_PASS</span><span class="v">••••••••</span>
        <span class="k">DB_NAME</span><span class="v">{$db_name}</span>
        <span class="k">DB_PORT</span><span class="v">{$db_port}</span>
        <span class="k">File .env</span><span class="v">{$env_path}</span>
      </div>
    </div>

    <div class="section">
      <div class="label">Error MySQL</div>
      <div class="err-box">{$err_html}</div>
    </div>

    <div class="section">
      <div class="label">Cara Memperbaiki</div>
      <ol class="steps">
        <li>Login ke <strong>Hostinger Panel</strong> → <em>Databases → MySQL Databases</em></li>
        <li>Catat <strong>DB Username</strong>, <strong>DB Password</strong>, <strong>DB Name</strong> yang benar</li>
        <li>Buka <strong>File Manager</strong> → folder <code>admin/</code> → edit file <code>.env</code></li>
        <li>Update nilai <code>DB_USER</code>, <code>DB_PASS</code>, <code>DB_NAME</code> sesuai langkah 2</li>
        <li>Jika database belum dibuat: phpMyAdmin → Import → pilih <code>database/install.sql</code></li>
        <li>Refresh halaman ini</li>
      </ol>
      <pre>DB_HOST=localhost
DB_USER=u741010203_admin        ← ganti dengan user DB Anda
DB_PASS=PASSWORD_ANDA_DISINI    ← ganti dengan password DB Anda
DB_NAME=u741010203_adminperkasa ← ganti dengan nama DB Anda</pre>
    </div>

    <a href="javascript:location.reload()" class="btn">🔄 Coba Lagi</a>

  </div>
</div>
</body>
</html>
HTML;
    exit();
}

// ============================================================
// CORE: Execute prepared statement, return statement
// ============================================================
function db_stmt(string $sql, string $types = '', array $params = []): mysqli_stmt
{
    $conn = db_connect();
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        $err = mysqli_error($conn);
        error_log("[DB] Prepare failed: {$err} | SQL: {$sql}");
        throw new RuntimeException("Database query gagal. Hubungi administrator.");
    }

    if ($types !== '' && count($params) > 0) {
        if (!mysqli_stmt_bind_param($stmt, $types, ...$params)) {
            $err = mysqli_stmt_error($stmt);
            error_log("[DB] Bind failed: {$err} | SQL: {$sql}");
            throw new RuntimeException("Database query gagal (bind). Hubungi administrator.");
        }
    }

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        error_log("[DB] Execute failed: {$err} | SQL: {$sql}");
        throw new RuntimeException("Database query gagal (execute). Hubungi administrator.");
    }

    return $stmt;
}

// ============================================================
// QUERY: Eksekusi tanpa return data (INSERT/UPDATE/DELETE)
// ============================================================
function db_query(string $sql, string $types = '', array $params = []): void
{
    $stmt = db_stmt($sql, $types, $params);
    mysqli_stmt_close($stmt);
}

// ============================================================
// INSERT: Eksekusi INSERT, return last insert ID
// ============================================================
function db_insert(string $sql, string $types = '', array $params = []): int
{
    $stmt = db_stmt($sql, $types, $params);
    $id   = (int)mysqli_insert_id(db_connect());
    mysqli_stmt_close($stmt);
    return $id;
}

// ============================================================
// FETCH ONE: Return satu baris sebagai associative array atau null
// ============================================================
function db_fetch(string $sql, string $types = '', array $params = []): ?array
{
    $stmt = db_stmt($sql, $types, $params);
    $result = mysqli_stmt_get_result($stmt);
    $row    = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

// ============================================================
// FETCH ALL: Return semua baris sebagai array of arrays
// ============================================================
function db_fetch_all(string $sql, string $types = '', array $params = []): array
{
    $stmt   = db_stmt($sql, $types, $params);
    $result = mysqli_stmt_get_result($stmt);
    $rows   = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);
    return $rows;
}

// ============================================================
// FETCH COLUMN: Return satu nilai dari satu kolom, satu baris
// ============================================================
function db_value(string $sql, string $types = '', array $params = []): mixed
{
    $row = db_fetch($sql, $types, $params);
    return $row ? reset($row) : null;
}

// ============================================================
// AFFECTED ROWS: Berapa baris yang terpengaruh dari UPDATE/DELETE
// ============================================================
function db_affected(): int
{
    return (int)mysqli_affected_rows(db_connect());
}

// ============================================================
// TRANSACTION HELPERS
// ============================================================
function db_begin(): void
{
    db_connect()->begin_transaction();
}

function db_commit(): void
{
    db_connect()->commit();
}

function db_rollback(): void
{
    db_connect()->rollback();
}

// ============================================================
// PAGINATE: Fetch dengan LIMIT/OFFSET, return [rows, total]
// ============================================================
function db_paginate(
    string $sql_data,
    string $sql_count,
    string $types,
    array  $params,
    int    $page  = 1,
    int    $per_page = 25
): array {
    $page     = max(1, $page);
    $offset   = ($page - 1) * $per_page;
    $total    = (int)db_value($sql_count, $types, $params);

    // Append LIMIT/OFFSET ke query data
    $rows     = db_fetch_all(
        $sql_data . " LIMIT {$per_page} OFFSET {$offset}",
        $types,
        $params
    );

    return [
        'rows'       => $rows,
        'total'      => $total,
        'per_page'   => $per_page,
        'page'       => $page,
        'last_page'  => (int)ceil($total / max(1, $per_page)),
    ];
}

// ============================================================
// ALIAS HELPERS — backward-compat untuk kode modul baru
// ============================================================

/**
 * db_execute() — alias db_query() (untuk modul yang ditulis dengan nama ini)
 * Eksekusi query tanpa return value (INSERT/UPDATE/DELETE).
 */
function db_execute(string $sql, string $types = '', array $params = []): void
{
    db_query($sql, $types, $params);
}

/**
 * db_conn() — kembalikan koneksi MySQLi aktif
 * Digunakan untuk begin_transaction(), insert_id, dll.
 */
function db_conn(): mysqli
{
    return db_connect();
}


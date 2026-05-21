#!/usr/bin/env php
<?php
/**
 * database/scripts/backup.php
 * Backup MySQL database ke storage/backups/
 * Jalankan via: php backup.php
 * Cron (setiap hari jam 02:00 WIB): 0 2 * * * /usr/bin/php /path/to/admin/database/scripts/backup.php
 *
 * Retention: simpan 30 backup terakhir, hapus yang lebih lama.
 */

declare(strict_types=1);

// Cari root project (2 level dari script ini)
$root = dirname(__DIR__, 2);

require_once $root . '/app/config/database.php';

// ── Config ────────────────────────────────────────────────────
$backup_dir = $root . '/storage/backups';
$retention  = 30;  // Jumlah backup yang disimpan
$timestamp  = date('Y-m-d_H-i-s');
$filename   = "backup_{$timestamp}.sql";
$filepath   = $backup_dir . '/' . $filename;
$gz_path    = $filepath . '.gz';

// ── Pastikan direktori ada ────────────────────────────────────
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0750, true);
}

// ── Ambil credentials dari env ────────────────────────────────
$host = env('DB_HOST', 'localhost');
$user = env('DB_USER', '');
$pass = env('DB_PASS', '');
$name = env('DB_NAME', '');
$port = env('DB_PORT', '3306');

if (!$user || !$name) {
    echo "[BACKUP ERROR] Database credentials tidak ditemukan di .env\n";
    exit(1);
}

// ── Coba mysqldump ────────────────────────────────────────────
$pass_arg = $pass ? '-p' . escapeshellarg($pass) : '';
$cmd = sprintf(
    'mysqldump --host=%s --port=%s --user=%s %s --single-transaction --routines --triggers --add-drop-table %s 2>&1',
    escapeshellarg($host),
    escapeshellarg($port),
    escapeshellarg($user),
    $pass_arg,
    escapeshellarg($name)
);

$output    = '';
$exit_code = 0;

ob_start();
passthru($cmd . ' > ' . escapeshellarg($filepath), $exit_code);
ob_end_clean();

if ($exit_code !== 0 || !is_file($filepath) || filesize($filepath) < 100) {
    // Fallback: PHP-based export (lebih lambat tapi tidak butuh mysqldump CLI)
    echo "[BACKUP] mysqldump tidak tersedia atau gagal — pakai PHP fallback...\n";
    _php_backup($filepath, $host, $user, $pass, $name, (int)$port);
}

// ── Kompres hasil ─────────────────────────────────────────────
if (is_file($filepath) && function_exists('gzencode')) {
    $content = file_get_contents($filepath);
    file_put_contents($gz_path, gzencode($content, 9));
    unlink($filepath);
    $final_file = basename($gz_path);
    $final_size = round(filesize($gz_path) / 1024, 1);
} else {
    $final_file = $filename;
    $final_size = round(filesize($filepath) / 1024, 1);
}

echo "[BACKUP OK] $final_file ({$final_size} KB)\n";

// ── Hapus backup lama (retention) ────────────────────────────
$backups = glob($backup_dir . '/backup_*.sql*') ?: [];
usort($backups, fn($a,$b) => filemtime($a) - filemtime($b));

$deleted = 0;
while (count($backups) > $retention) {
    $old = array_shift($backups);
    if (@unlink($old)) {
        $deleted++;
        echo "[BACKUP] Hapus lama: " . basename($old) . "\n";
    }
}

if ($deleted > 0) echo "[BACKUP] $deleted backup lama dihapus.\n";
echo "[BACKUP] Total backup tersimpan: " . count($backups) . "\n";

// ── PHP fallback export ───────────────────────────────────────
function _php_backup(string $filepath, string $host, string $user, string $pass, string $db, int $port): void
{
    $conn = new mysqli($host, $user, $pass, $db, $port);
    if ($conn->connect_error) {
        echo "[BACKUP ERROR] Koneksi gagal: " . $conn->connect_error . "\n";
        exit(1);
    }
    $conn->set_charset('utf8mb4');

    $fh = fopen($filepath, 'w');
    fwrite($fh, "-- Perkasa Admin Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n-- Database: $db\n\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

    $tables = $conn->query("SHOW TABLES");
    while ($row = $tables->fetch_row()) {
        $table = $row[0];

        // CREATE TABLE
        $create = $conn->query("SHOW CREATE TABLE `$table`")->fetch_row();
        fwrite($fh, "DROP TABLE IF EXISTS `$table`;\n" . $create[1] . ";\n\n");

        // INSERT rows in chunks
        $result = $conn->query("SELECT * FROM `$table`");
        if ($result->num_rows === 0) continue;

        $chunk = [];
        while ($r = $result->fetch_row()) {
            $vals = array_map(fn($v) => $v === null ? 'NULL' : "'" . $conn->real_escape_string($v) . "'", $r);
            $chunk[] = '(' . implode(',', $vals) . ')';
            if (count($chunk) >= 500) {
                fwrite($fh, "INSERT INTO `$table` VALUES " . implode(",\n", $chunk) . ";\n");
                $chunk = [];
            }
        }
        if ($chunk) fwrite($fh, "INSERT INTO `$table` VALUES " . implode(",\n", $chunk) . ";\n");
        fwrite($fh, "\n");
    }

    fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fh);
    $conn->close();
    echo "[BACKUP PHP-fallback] Export selesai: " . basename($filepath) . "\n";
}

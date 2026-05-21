<?php
/**
 * migrate_passwords.php
 * PERKASA MULIA TRAINING CENTER v2.0 — Phase 1, Task 1
 *
 * Tujuan : Hash semua password plaintext yang ada di tabel `users`
 *          menggunakan password_hash() (bcrypt, cost=12)
 *
 * Jalankan SEKALI saja via CLI:
 *   php migrate_passwords.php
 *
 * Atau via browser (hanya di localhost / jangan expose ke publik!):
 *   http://localhost/migrate_passwords.php?secret=GANTI_SECRET_INI
 *
 * Setelah selesai: HAPUS atau pindahkan file ini ke luar webroot!
 *
 * ⚠️  BACKUP DATABASE SEBELUM MENJALANKAN SCRIPT INI!
 */

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

// ============================================================
// KONFIGURASI — Sesuaikan dengan .env atau config.php Anda
// ============================================================
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'u741010203_admin');    // Ganti sesuai DB user Anda
define('DB_PASS', 'GANTI_PASSWORD_DB');   // Ganti sesuai password DB Anda
define('DB_NAME', 'u741010203_adminperkasa');
define('DB_PORT', 3306);

// Secret untuk akses via browser (abaikan jika jalankan via CLI)
define('BROWSER_SECRET', 'GANTI_SECRET_UNIK_INI');

// Bcrypt cost factor — 12 adalah standar aman (naik jika server kuat)
define('BCRYPT_COST', 12);

// Password sementara untuk user placeholder tutor (hasil cleanup 002)
// Mereka harus ganti password saat pertama login
define('PLACEHOLDER_TEMP_PASSWORD', 'Perkasa@2026!');

// Apakah mode dry-run? (true = hanya tampilkan, tidak update DB)
define('DRY_RUN', false);

// ============================================================
// SECURITY CHECK
// ============================================================
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    // Browser access — wajib pakai secret param
    $provided = $_GET['secret'] ?? '';
    if (!hash_equals(BROWSER_SECRET, $provided)) {
        http_response_code(403);
        die('403 Forbidden — Jalankan via CLI atau sertakan ?secret=xxx yang benar.');
    }
    echo '<pre>';
}

log_msg('=== PERKASA v2.0 — Password Migration Script ===');
log_msg('PHP Version : ' . PHP_VERSION);
log_msg('Bcrypt Cost : ' . BCRYPT_COST);
log_msg('Dry Run     : ' . (DRY_RUN ? 'YES (tidak ada perubahan DB)' : 'NO (akan update DB)'));
log_msg('Waktu mulai : ' . date('Y-m-d H:i:s'));
log_msg('');

// ============================================================
// KONEKSI DATABASE
// ============================================================
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if (!$conn) {
    log_msg('ERROR: Gagal koneksi DB — ' . mysqli_connect_error(), 'ERROR');
    exit(1);
}

mysqli_set_charset($conn, 'utf8mb4');
log_msg('Koneksi DB berhasil: ' . DB_NAME . '@' . DB_HOST);

// ============================================================
// STEP 1: Audit kondisi password sebelum migrasi
// ============================================================
log_msg('');
log_msg('--- STEP 1: Audit Password Sebelum Migrasi ---');

$result = mysqli_query($conn,
    "SELECT id, email, role,
            CASE
                WHEN password LIKE '\$2y\$%' THEN 'BCRYPT'
                WHEN password LIKE '\$2a\$%' THEN 'BCRYPT_OLD'
                WHEN password LIKE '\$argon%' THEN 'ARGON2'
                WHEN password = 'NEEDS_RESET' THEN 'PLACEHOLDER'
                ELSE 'PLAINTEXT'
            END AS pw_type,
            LENGTH(password) AS pw_length
     FROM users
     ORDER BY role, id"
);

$stats = ['BCRYPT' => 0, 'BCRYPT_OLD' => 0, 'ARGON2' => 0, 'PLAINTEXT' => 0, 'PLACEHOLDER' => 0];
$users_to_migrate = [];
$all_users = [];

while ($row = mysqli_fetch_assoc($result)) {
    $stats[$row['pw_type']] = ($stats[$row['pw_type']] ?? 0) + 1;
    $all_users[] = $row;
    if (in_array($row['pw_type'], ['PLAINTEXT', 'PLACEHOLDER'])) {
        $users_to_migrate[] = $row;
    }
}

log_msg('Status password saat ini:');
foreach ($stats as $type => $count) {
    if ($count > 0) {
        log_msg("  {$type}: {$count} user");
    }
}
log_msg('');
log_msg('Users yang perlu diproses: ' . count($users_to_migrate));

if (empty($users_to_migrate)) {
    log_msg('Tidak ada password yang perlu di-hash. Script selesai.');
    mysqli_close($conn);
    exit(0);
}

// ============================================================
// STEP 2: Hash password & update DB
// ============================================================
log_msg('');
log_msg('--- STEP 2: Proses Hashing ---');

if (DRY_RUN) {
    log_msg('[DRY RUN] Tidak ada perubahan — berikut yang akan diproses:');
}

$success_count = 0;
$error_count   = 0;
$migration_report = [];

foreach ($users_to_migrate as $user) {
    $user_id  = (int)$user['id'];
    $email    = $user['email'];
    $role     = $user['role'];
    $pw_type  = $user['pw_type'];

    // Tentukan password baru
    if ($pw_type === 'PLACEHOLDER') {
        // User placeholder tutor — set ke temp password, tandai harus ganti
        $plain_password = PLACEHOLDER_TEMP_PASSWORD;
        $needs_reset    = true;
        $action_note    = "Placeholder → temp password (harus ganti saat login)";
    } else {
        // PLAINTEXT — hash password yang ada (teks aslinya)
        // Ambil nilai plaintext dari DB
        $stmt_get = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt_get, 'i', $user_id);
        mysqli_stmt_execute($stmt_get);
        mysqli_stmt_bind_result($stmt_get, $current_plain);
        mysqli_stmt_fetch($stmt_get);
        mysqli_stmt_close($stmt_get);

        $plain_password = $current_plain;
        $needs_reset    = false;
        $action_note    = "Plaintext → bcrypt hash";
    }

    // Buat hash baru
    $hashed = password_hash($plain_password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

    if ($hashed === false) {
        log_msg("  ERROR: Gagal hash untuk user #{$user_id} ({$email})", 'ERROR');
        $error_count++;
        continue;
    }

    // Verifikasi hash berhasil
    if (!password_verify($plain_password, $hashed)) {
        log_msg("  ERROR: Verifikasi hash gagal untuk user #{$user_id} ({$email})", 'ERROR');
        $error_count++;
        continue;
    }

    if (DRY_RUN) {
        log_msg("  [DRY] #{$user_id} [{$role}] {$email} — {$action_note}");
        $success_count++;
        continue;
    }

    // Update DB
    $stmt_update = mysqli_prepare($conn,
        "UPDATE users SET password = ?, failed_login_count = 0, locked_until = NULL WHERE id = ?"
    );
    mysqli_stmt_bind_param($stmt_update, 'si', $hashed, $user_id);
    $updated = mysqli_stmt_execute($stmt_update);
    mysqli_stmt_close($stmt_update);

    if (!$updated) {
        log_msg("  ERROR: Gagal update DB untuk user #{$user_id} — " . mysqli_error($conn), 'ERROR');
        $error_count++;
        continue;
    }

    $success_count++;
    $migration_report[] = [
        'id'         => $user_id,
        'email'      => $email,
        'role'       => $role,
        'action'     => $action_note,
        'needs_reset'=> $needs_reset ? 'YA' : 'Tidak',
    ];

    log_msg("  ✓ #{$user_id} [{$role}] {$email} — {$action_note}");
}

// ============================================================
// STEP 3: Buat user owner pertama (jika belum ada)
// ============================================================
if (!DRY_RUN) {
    log_msg('');
    log_msg('--- STEP 3: Cek User Owner ---');

    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM users WHERE role = 'owner'");
    $row_owner = mysqli_fetch_assoc($r);

    if ($row_owner['cnt'] == 0) {
        log_msg("  Belum ada user owner — membuat user owner default...");

        $owner_email  = 'owner@bimbelperkasa.id';
        $owner_pass   = 'Perkasa@Owner2026!';  // Ganti SEGERA setelah login pertama!
        $owner_hash   = password_hash($owner_pass, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $owner_nama   = 'Owner Perkasa Mulia';

        $stmt_owner = mysqli_prepare($conn,
            "INSERT IGNORE INTO users (email, password, role, nama_display, is_active)
             VALUES (?, ?, 'owner', ?, 1)"
        );
        mysqli_stmt_bind_param($stmt_owner, 'sss', $owner_email, $owner_hash, $owner_nama);
        mysqli_stmt_execute($stmt_owner);
        mysqli_stmt_close($stmt_owner);

        if (mysqli_affected_rows($conn) > 0) {
            log_msg("  ✓ User owner dibuat: {$owner_email}");
            log_msg("  ⚠️  Password sementara: {$owner_pass}");
            log_msg("  ⚠️  GANTI PASSWORD INI SEGERA setelah login pertama!");
        } else {
            log_msg("  INFO: User owner sudah ada atau gagal dibuat.");
        }
    } else {
        log_msg("  ✓ User owner sudah ada ({$row_owner['cnt']} akun)");
    }
}

// ============================================================
// STEP 4: Verifikasi akhir
// ============================================================
log_msg('');
log_msg('--- STEP 4: Verifikasi Akhir ---');

$r_final = mysqli_query($conn,
    "SELECT
         SUM(CASE WHEN password LIKE '\$2y\$%' OR password LIKE '\$2a\$%' THEN 1 ELSE 0 END) AS hashed,
         SUM(CASE WHEN password NOT LIKE '\$2%' AND password != 'NEEDS_RESET' THEN 1 ELSE 0 END) AS plaintext,
         SUM(CASE WHEN password = 'NEEDS_RESET' THEN 1 ELSE 0 END) AS placeholder,
         COUNT(*) AS total
     FROM users"
);
$final = mysqli_fetch_assoc($r_final);

log_msg("  Total users   : {$final['total']}");
log_msg("  Sudah di-hash : {$final['hashed']}");
log_msg("  Masih plaintext: {$final['plaintext']}");
log_msg("  Placeholder   : {$final['placeholder']}");

if ((int)$final['plaintext'] > 0) {
    log_msg("  ⚠️  MASIH ADA PASSWORD PLAINTEXT! Cek log error di atas.", 'WARN');
} else {
    log_msg("  ✅ Semua password sudah di-hash dengan aman.");
}

// ============================================================
// STEP 5: Tulis laporan migrasi ke file log
// ============================================================
if (!DRY_RUN && !empty($migration_report)) {
    $log_dir  = __DIR__ . '/storage/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0750, true);
    }

    $log_file = $log_dir . '/password_migration_' . date('Ymd_His') . '.log';
    $log_content = "PERKASA v2.0 — Password Migration Report\n";
    $log_content .= "Tanggal: " . date('Y-m-d H:i:s') . "\n";
    $log_content .= str_repeat('=', 60) . "\n\n";
    $log_content .= sprintf("%-5s %-40s %-8s %-45s %-12s\n",
        'ID', 'Email', 'Role', 'Action', 'Needs Reset');
    $log_content .= str_repeat('-', 115) . "\n";

    foreach ($migration_report as $r) {
        $log_content .= sprintf("%-5d %-40s %-8s %-45s %-12s\n",
            $r['id'], $r['email'], $r['role'], $r['action'], $r['needs_reset']);
    }

    $log_content .= "\nSummary: {$success_count} success, {$error_count} error\n";

    file_put_contents($log_file, $log_content);
    log_msg("  📄 Laporan disimpan: {$log_file}");
}

// ============================================================
// RINGKASAN AKHIR
// ============================================================
log_msg('');
log_msg('=== RINGKASAN ===');
log_msg("Berhasil  : {$success_count}");
log_msg("Gagal     : {$error_count}");
log_msg("Waktu akhir: " . date('Y-m-d H:i:s'));
log_msg('');
log_msg('⚠️  LANGKAH SELANJUTNYA:');
log_msg('  1. Verifikasi login admin berfungsi di aplikasi');
log_msg('  2. Kirim informasi password sementara ke tutor via WA');
log_msg('  3. HAPUS file migrate_passwords.php dari server setelah selesai!');
log_msg('  4. Pastikan .htaccess memblokir akses ke /storage/logs/');

mysqli_close($conn);

if (!$is_cli) {
    echo '</pre>';
}

exit($error_count > 0 ? 1 : 0);

// ============================================================
// HELPER
// ============================================================
function log_msg(string $msg, string $level = 'INFO'): void
{
    $is_cli = (php_sapi_name() === 'cli');
    $prefix = ($level !== 'INFO') ? "[{$level}] " : '';
    $line   = $prefix . $msg;

    if ($is_cli) {
        echo $line . "\n";
    } else {
        // Browser: escape HTML
        echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . "\n";
    }
}

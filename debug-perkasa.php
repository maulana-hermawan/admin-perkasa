<?php
/**
 * debug-perkasa.php — Script diagnostik sementara
 * LANGKAH:
 *   1. Upload file ini ke folder admin-baru/ di server
 *   2. Buka: https://bimbelperkasa.id/admin-baru/debug-perkasa.php
 *   3. Baca laporan, fix masalah
 *   4. HAPUS file ini setelah selesai (jangan dibiarkan di server!)
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = __DIR__;
$ok   = '✅';
$warn = '⚠️';
$fail = '❌';

ob_start();

echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'>
<meta name='viewport' content='width=device-width,initial-scale=1'>
<title>Perkasa Debug</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:1rem;font-size:14px}
h2{color:#38bdf8;margin:1.5rem 0 .5rem;font-size:1rem;border-bottom:1px solid #334155;padding-bottom:.3rem}
.ok{color:#4ade80}.warn{color:#fbbf24}.fail{color:#f87171}
.block{background:#1e293b;border-radius:6px;padding:.75rem 1rem;margin:.3rem 0;word-break:break-all}
.label{color:#94a3b8;margin-right:.5rem}
pre{white-space:pre-wrap;word-break:break-all}
</style>
</head><body>";

// ── PHP VERSION ────────────────────────────────────────────────
echo "<h2>1. PHP Version</h2>";
$php = PHP_VERSION;
$major = (int)PHP_MAJOR_VERSION;
$minor = (int)PHP_MINOR_VERSION;
$status = ($major >= 8 && $minor >= 1) ? $ok : (($major >= 8) ? $warn : $fail);
echo "<div class='block'>$status PHP $php — ";
if ($major < 8) echo "<span class='fail'>TERLALU LAMA. Butuh minimal PHP 8.0, idealnya 8.1+. Ganti versi PHP di Hostinger Panel → PHP Configuration.</span>";
elseif ($minor < 1) echo "<span class='warn'>PHP 8.0 — OK, tapi beberapa fitur butuh 8.1+. Upgrade ke 8.1 disarankan.</span>";
else echo "<span class='ok'>OK — PHP 8.1+ siap</span>";
echo "</div>";

// ── PHP EXTENSIONS ────────────────────────────────────────────
echo "<h2>2. PHP Extensions</h2>";
$needed = ['mysqli','curl','mbstring','json','openssl','zip','fileinfo'];
foreach ($needed as $ext) {
    $loaded = extension_loaded($ext);
    echo "<div class='block'>" . ($loaded ? $ok : $fail) . " <span class='label'>ext-$ext</span>";
    if (!$loaded) echo "<span class='fail'>TIDAK ADA — aktifkan di Hostinger PHP settings</span>";
    echo "</div>";
}

// ── FILE PERMISSIONS ─────────────────────────────────────────
echo "<h2>3. File & Folder Permissions</h2>";
$checks = [
    '.env'                 => ['exists' => true,  'readable' => true],
    'storage/logs'         => ['exists' => true,  'writable' => true],
    'storage/uploads'      => ['exists' => true,  'writable' => true],
    'storage/cache'        => ['exists' => true,  'writable' => true],
    'storage/exports'      => ['exists' => true,  'writable' => true],
    'storage/backups'      => ['exists' => true,  'writable' => true],
    'app/core/db.php'      => ['exists' => true,  'readable' => true],
    'app/core/helpers.php' => ['exists' => true,  'readable' => true],
    'app/core/auth.php'    => ['exists' => true,  'readable' => true],
];
foreach ($checks as $path => $req) {
    $full = $root . '/' . $path;
    $exists = file_exists($full);
    if (!$exists) {
        echo "<div class='block'>$fail <span class='label'>$path</span><span class='fail'>TIDAK DITEMUKAN</span></div>";
        continue;
    }
    if (!empty($req['readable']) && !is_readable($full)) {
        echo "<div class='block'>$warn <span class='label'>$path</span><span class='warn'>TIDAK BISA DIBACA (chmod 644/755?)</span></div>";
        continue;
    }
    if (!empty($req['writable']) && !is_writable($full)) {
        echo "<div class='block'>$warn <span class='label'>$path</span><span class='warn'>TIDAK BISA DITULIS — jalankan: chmod 755 $path</span></div>";
        continue;
    }
    echo "<div class='block'>$ok <span class='label'>$path</span></div>";
}

// ── .ENV CONTENT (tanpa password) ───────────────────────────
echo "<h2>4. .env Variables (password disembunyikan)</h2>";
$env_path = $root . '/.env';
if (!file_exists($env_path)) {
    echo "<div class='block fail'>❌ FILE .env TIDAK ADA di: $env_path</div>";
} else {
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        // Sembunyikan nilai sensitif
        if (preg_match('/^(DB_PASS|API_JWT_SECRET|CAT_WEBHOOK_SECRET|FONNTE_TOKEN)/i', $line)) {
            $key = explode('=', $line)[0];
            $val = strlen(explode('=', $line, 2)[1] ?? '') > 0 ? '***HIDDEN*** (ada isinya)' : '(KOSONG!)';
            $icon = str_contains($val, 'KOSONG') ? $warn : $ok;
            echo "<div class='block'>$icon <span class='label'>$key</span>$val</div>";
        } else {
            echo "<div class='block'>$ok <pre>" . htmlspecialchars($line) . "</pre></div>";
        }
    }
}

// ── DATABASE CONNECTION ───────────────────────────────────────
echo "<h2>5. Koneksi Database</h2>";
if (!file_exists($env_path)) {
    echo "<div class='block fail'>$fail Skip — .env tidak ada</div>";
} else {
    // Parse .env
    $env = [];
    foreach (file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $eq = strpos($line, '=');
        if ($eq === false) continue;
        $k = trim(substr($line, 0, $eq));
        $v = trim(substr($line, $eq + 1));
        if (strlen($v) >= 2 && (($v[0] === '"' && $v[-1] === '"') || ($v[0] === "'" && $v[-1] === "'"))) {
            $v = substr($v, 1, -1);
        }
        $env[$k] = $v;
    }

    $host = $env['DB_HOST'] ?? 'localhost';
    $user = $env['DB_USER'] ?? '';
    $pass = $env['DB_PASS'] ?? '';
    $name = $env['DB_NAME'] ?? '';
    $port = (int)($env['DB_PORT'] ?? 3306);

    echo "<div class='block'><span class='label'>Host:</span>$host | <span class='label'>User:</span>$user | <span class='label'>DB:</span>$name | <span class='label'>Port:</span>$port</div>";

    if (!$user || !$name) {
        echo "<div class='block fail'>$fail DB_USER atau DB_NAME kosong di .env!</div>";
    } elseif (!$pass || $pass === 'ISI_PASSWORD_DATABASE_ANDA_DISINI') {
        echo "<div class='block fail'>$fail DB_PASS belum diisi di .env! Isi dulu dengan password database Hostinger.</div>";
    } else {
        mysqli_report(MYSQLI_REPORT_OFF);
        $conn = @mysqli_connect($host, $user, $pass, $name, $port);
        if (!$conn) {
            $err = mysqli_connect_error();
            $errno = mysqli_connect_errno();
            echo "<div class='block fail'>$fail GAGAL KONEK: [$errno] $err</div>";
            echo "<div class='block'>";
            if ($errno == 1045) echo "$fail Access denied — username/password salah. Cek di Hostinger Panel → Databases → MySQL.";
            elseif ($errno == 1049) echo "$fail Database '$name' tidak ditemukan. Pastikan DB sudah dibuat dan nama benar.";
            elseif ($errno == 2002) echo "$fail Tidak bisa konek ke host '$host'. Pastikan DB_HOST benar (biasanya 'localhost').";
            else echo "$warn Error tidak dikenal — screenshot & hubungi developer.";
            echo "</div>";
        } else {
            echo "<div class='block ok'>$ok Koneksi database BERHASIL!</div>";

            // Cek tabel-tabel penting
            echo "<h2>6. Cek Tabel Database</h2>";
            $tables_needed = ['users','siswa','tutor','program','membership_siswa',
                              'transaksi_keuangan','jadwal','jadwal_tutor',
                              'penilaian_akademik','penilaian_binjas','audit_log',
                              'attendance_siswa','pembayaran_siswa','calon_siswa',
                              'notifikasi','absensi_tutor','penilaian_mapel'];
            $result = mysqli_query($conn, "SHOW TABLES");
            $existing = [];
            while ($row = mysqli_fetch_row($result)) $existing[] = $row[0];

            $missing = [];
            foreach ($tables_needed as $t) {
                if (!in_array($t, $existing)) {
                    $missing[] = $t;
                    echo "<div class='block fail'>$fail Tabel <b>$t</b> TIDAK ADA</div>";
                } else {
                    $cnt = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM `$t`"))[0];
                    echo "<div class='block ok'>$ok Tabel <b>$t</b> — $cnt baris</div>";
                }
            }

            if ($missing) {
                echo "<div class='block warn'>$warn " . count($missing) . " tabel hilang. Import file <b>database/install.sql</b> di phpMyAdmin!</div>";
            }

            // Cek user admin
            echo "<h2>7. Cek User Admin</h2>";
            if (in_array('users', $existing)) {
                $users = mysqli_query($conn, "SELECT email, role, is_active, password FROM users LIMIT 10");
                if (mysqli_num_rows($users) == 0) {
                    echo "<div class='block fail'>$fail Tabel users KOSONG. Belum ada akun admin.</div>";
                    echo "<div class='block warn'>Jalankan query ini di phpMyAdmin:<br><pre>INSERT INTO users (email, password, role, nama_display, is_active) VALUES\n('admin@bimbelperkasa.id', '\$2y\$12\$" . password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]) . "', 'admin', 'Admin Perkasa', 1);</pre></div>";
                } else {
                    while ($u = mysqli_fetch_assoc($users)) {
                        $pw_hashed = str_starts_with($u['password'] ?? '', '$2');
                        $active = (bool)$u['is_active'];
                        echo "<div class='block'>" . ($active && $pw_hashed ? $ok : $warn)
                            . " <span class='label'>Email:</span>" . htmlspecialchars($u['email'])
                            . " | <span class='label'>Role:</span>" . htmlspecialchars($u['role'])
                            . " | <span class='label'>Password:</span>" . ($pw_hashed ? "Ter-hash ✓" : "<span class='fail'>PLAINTEXT! Jalankan migrate_passwords.php</span>")
                            . " | <span class='label'>Aktif:</span>" . ($active ? "Ya" : "<span class='fail'>Tidak</span>")
                            . "</div>";
                    }
                }
            }

            mysqli_close($conn);
        }
    }
}

// ── INCLUDE TEST ──────────────────────────────────────────────
echo "<h2>8. Test Load Core Files</h2>";
$core_files = [
    'app/config/database.php',
    'app/core/db.php',
    'app/core/helpers.php',
    'app/core/auth.php',
];
foreach ($core_files as $f) {
    $full = $root . '/' . $f;
    if (!file_exists($full)) {
        echo "<div class='block fail'>$fail $f — FILE TIDAK ADA</div>";
        continue;
    }
    // Cek require tanpa execute (token check)
    $tokens = @token_get_all(file_get_contents($full));
    echo "<div class='block ok'>$ok $f — parseable OK (" . count($tokens) . " tokens)</div>";
}

// ── PHP FEATURES TEST ─────────────────────────────────────────
echo "<h2>9. PHP 8.x Feature Check</h2>";
$features = [
    'str_starts_with()' => function_exists('str_starts_with'),
    'str_ends_with()'   => function_exists('str_ends_with'),
    'match expression'  => PHP_VERSION_ID >= 80000,
    'Union types'       => PHP_VERSION_ID >= 80000,
    'never return type' => PHP_VERSION_ID >= 80100,
    'Fibers'            => PHP_VERSION_ID >= 80100,
    'password_hash()'   => function_exists('password_hash'),
    'random_bytes()'    => function_exists('random_bytes'),
];
foreach ($features as $name => $available) {
    echo "<div class='block'>" . ($available ? $ok : $fail) . " $name</div>";
}

// ── SUMMARY ───────────────────────────────────────────────────
echo "<h2>📋 Ringkasan & Langkah Selanjutnya</h2>";
echo "<div class='block'>Jika semua $ok di atas → aplikasi seharusnya jalan. Jika masih error:<br>
1. Set <b>APP_DEBUG=true</b> di file .env sementara<br>
2. Buka lagi index.php — error detail akan muncul<br>
3. Screenshot error, kirim ke developer<br>
4. Setelah fix, kembalikan <b>APP_DEBUG=false</b></div>";

echo "<div class='block warn'>⚠️ <b>PENTING: Hapus file debug-perkasa.php ini setelah selesai diagnosis!</b></div>";

echo "</body></html>";
echo ob_get_clean();
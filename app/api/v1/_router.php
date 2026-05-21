<?php
/**
 * app/api/v1/_router.php
 * Internal router for /api/v1/ endpoints.
 * Called via: GET|POST /admin/api/v1/{endpoint}
 *
 * Authentication: Bearer JWT in Authorization header.
 * POST /api/v1/token  — Get token (email + password + api_key)
 * GET  /api/v1/siswa  — List siswa aktif
 * GET  /api/v1/jadwal — List jadwal mendatang
 * GET  /api/v1/nilai/{siswa_id} — Nilai per siswa
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

// Bootstrap
$root = dirname(__DIR__, 3);
require_once $root . '/app/config/database.php';
require_once $root . '/app/config/app.php';
require_once $root . '/app/core/db.php';
require_once $root . '/app/core/helpers.php';
require_once $root . '/app/lib/JWT.php';

// ── Rate limit per IP (simple, file-based) ────────────────────
function api_rate_limit(int $max = 60, int $window = 60): void
{
    $ip   = md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $file = sys_get_temp_dir() . "/pk_api_rl_{$ip}.json";
    $now  = time();

    $data = [];
    if (is_file($file)) {
        $data = json_decode(file_get_contents($file), true) ?? [];
    }

    // Clean old hits
    $data = array_filter($data, fn($t) => $t > $now - $window);

    if (count($data) >= $max) {
        http_response_code(429);
        die(json_encode(['error' => 'Rate limit exceeded. Try again later.']));
    }

    $data[] = $now;
    file_put_contents($file, json_encode(array_values($data)));
}

api_rate_limit(60, 60);

// ── JWT verification middleware ────────────────────────────────
function api_auth(): array
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer (.+)$/', $header, $m)) {
        http_response_code(401);
        die(json_encode(['error' => 'Authorization header missing or invalid.']));
    }

    $secret = env('API_JWT_SECRET', 'perkasa-api-secret-change-me-2026');
    try {
        return JWT::decode($m[1], $secret);
    } catch (RuntimeException $e) {
        http_response_code(401);
        die(json_encode(['error' => 'Token invalid: ' . $e->getMessage()]));
    }
}

function api_ok(mixed $data, array $meta = []): never
{
    echo json_encode(array_merge(['ok' => true], $meta, ['data' => $data]), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

function api_error(string $msg, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit();
}

// ── Routing ───────────────────────────────────────────────────
$path   = trim($_GET['_path'] ?? '', '/');
$method = $_SERVER['REQUEST_METHOD'];

match (true) {
    // POST /api/v1/token
    $path === 'token' && $method === 'POST' => (function() {
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $email   = strtolower(trim($body['email'] ?? ''));
        $pwd     = $body['password'] ?? '';
        $api_key = $body['api_key'] ?? '';

        // Simple API key guard (set in .env)
        $expected_key = env('API_KEY', '');
        if ($expected_key && !hash_equals($expected_key, $api_key)) {
            api_error('Invalid API key', 401);
        }

        $user = db_fetch("SELECT id,email,password,role,is_active FROM users WHERE email=? AND deleted_at IS NULL","s",[$email]);
        if (!$user || !password_verify($pwd, $user['password']) || !(bool)$user['is_active']) {
            api_error('Invalid credentials', 401);
        }
        if (!in_array($user['role'], ['admin','owner'])) {
            api_error('Insufficient permissions', 403);
        }

        $secret = env('API_JWT_SECRET', 'perkasa-api-secret-change-me-2026');
        $token  = JWT::encode(['uid' => $user['id'], 'role' => $user['role']], $secret, 86400);

        api_ok(['token' => $token, 'expires_in' => 86400, 'token_type' => 'Bearer']);
    })(),

    // GET /api/v1/siswa
    $path === 'siswa' && $method === 'GET' => (function() {
        api_auth();
        $status = $_GET['status'] ?? 'Aktif';
        $limit  = min((int)($_GET['limit'] ?? 50), 200);
        $offset = max((int)($_GET['offset'] ?? 0), 0);

        $rows = db_fetch_all(
            "SELECT id, nomor_induk, nama_lengkap, nomor_wa, status_siswa, target_seleksi,
                    jenis_kelamin, created_at
             FROM siswa WHERE deleted_at IS NULL AND status_siswa=?
             ORDER BY nama_lengkap LIMIT ? OFFSET ?",
            "sii", [$status, $limit, $offset]
        );
        $total = (int)db_value("SELECT COUNT(*) FROM siswa WHERE deleted_at IS NULL AND status_siswa=?","s",[$status]);

        api_ok($rows, ['total' => $total, 'limit' => $limit, 'offset' => $offset]);
    })(),

    // GET /api/v1/jadwal
    $path === 'jadwal' && $method === 'GET' => (function() {
        api_auth();
        $dari   = $_GET['dari']   ?? date('Y-m-d');
        $sampai = $_GET['sampai'] ?? date('Y-m-d', strtotime('+30 days'));

        $rows = db_fetch_all(
            "SELECT j.id, j.nama_kegiatan, j.tanggal, j.waktu_mulai, j.waktu_selesai,
                    j.lokasi, j.materi, p.nama_program
             FROM jadwal j LEFT JOIN program p ON j.program_id=p.id
             WHERE j.tanggal BETWEEN ? AND ?
             ORDER BY j.tanggal, j.waktu_mulai",
            "ss", [$dari, $sampai]
        );

        api_ok($rows, ['dari' => $dari, 'sampai' => $sampai]);
    })(),

    // GET /api/v1/nilai/{siswa_id}
    preg_match('#^nilai/(\d+)$#', $path, $nm) => (function() use ($nm) {
        api_auth();
        $siswa_id = (int)$nm[1];

        $siswa = db_fetch("SELECT id, nama_lengkap, nomor_induk FROM siswa WHERE id=? AND deleted_at IS NULL","i",[$siswa_id]);
        if (!$siswa) api_error('Siswa tidak ditemukan', 404);

        $jasmani  = db_fetch_all("SELECT tanggal_tes, skor_akhir, predikat FROM penilaian_binjas WHERE siswa_id=? ORDER BY tanggal_tes DESC LIMIT 5","i",[$siswa_id]);
        $skd      = db_fetch_all("SELECT tanggal_tes, nilai_twk, nilai_tiu, nilai_tkp, total_skor FROM penilaian_akademik WHERE siswa_id=? AND kategori_tes='SKD' ORDER BY tanggal_tes DESC LIMIT 5","i",[$siswa_id]);
        $psikologi= db_fetch_all("SELECT tanggal_tes, rata_psikologi, status_psikologi FROM penilaian_psikologi WHERE siswa_id=? ORDER BY tanggal_tes DESC LIMIT 3","i",[$siswa_id]);

        api_ok(['siswa' => $siswa, 'jasmani' => $jasmani, 'skd' => $skd, 'psikologi' => $psikologi]);
    })(),

    default => api_error('Endpoint not found', 404),
};

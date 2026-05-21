<?php
/**
 * app/core/helpers.php
 * Fungsi utilitas global yang dipakai di seluruh aplikasi.
 * Wajib di-require di awal setiap request (via bootstrap index.php).
 */

declare(strict_types=1);

// ============================================================
// XSS PROTECTION
// ============================================================

/**
 * Escape output — selalu pakai ini untuk output data dari DB/user ke HTML.
 * @example echo e($nama_siswa);
 */
function e(mixed $val): string
{
    return htmlspecialchars((string)($val ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Output escaped string langsung (shortcut echo e())
 */
function ee(mixed $val): void
{
    echo e($val);
}

// ============================================================
// CSRF PROTECTION
// ============================================================

/**
 * Generate dan simpan CSRF token di session.
 * Token di-regenerate setiap REQUEST (strict mode).
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(CSRF_LENGTH));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Return hidden input field dengan CSRF token.
 * Pakai di setiap <form method="POST">
 * @example echo csrf_field();
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Validasi CSRF token dari POST request.
 * Panggil di setiap handler POST sebelum proses data.
 * Redirect ke login jika gagal.
 */
function csrf_check(): void
{
    $submitted = $_POST['_csrf_token'] ?? '';
    $stored    = $_SESSION['_csrf_token'] ?? '';

    if (!$submitted || !$stored || !hash_equals($stored, $submitted)) {
        error_log("[CSRF] Token mismatch — IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        unset($_SESSION['_csrf_token']);
        http_response_code(403);
        die('Sesi tidak valid. <a href="index.php">Kembali</a>');
    }
    unset($_SESSION['_csrf_token']);
}

/** CSRF check untuk aksi GET (link langsung dengan _csrf_token di query string) */
function csrf_check_get(): void
{
    $submitted = $_GET['_csrf_token'] ?? '';
    $stored    = $_SESSION['_csrf_token'] ?? '';
    if (!$submitted || !$stored || !hash_equals($stored, $submitted)) {
        http_response_code(403);
        die('Sesi tidak valid. <a href="index.php">Kembali</a>');
    }
    unset($_SESSION['_csrf_token']);
}

// ============================================================
// FLASH MESSAGES
// ============================================================

/**
 * Set flash message untuk ditampilkan di request berikutnya.
 * @param string $type  'success' | 'danger' | 'warning' | 'info'
 * @param string $msg   Pesan yang ditampilkan
 */
function flash(string $type, string $msg): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
}

/**
 * Ambil semua flash messages (dan hapus dari session).
 * Return array of ['type'=>..., 'msg'=>...]
 */
function flash_get(): array
{
    $msgs = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $msgs;
}

/**
 * Render flash messages sebagai Bootstrap alerts.
 * Pakai di layout/header.php setelah sidebar.
 */
function flash_render(): void
{
    $msgs = flash_get();
    if (empty($msgs)) return;

    foreach ($msgs as $m) {
        $type = in_array($m['type'], ['success','danger','warning','info']) ? $m['type'] : 'info';
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show mx-0 mb-3 rounded-3 shadow-sm" role="alert">';
        echo '<i class="bi bi-' . match($type) {
            'success' => 'check-circle-fill',
            'danger'  => 'exclamation-triangle-fill',
            'warning' => 'exclamation-circle-fill',
            default   => 'info-circle-fill',
        } . ' me-2"></i>';
        echo $m['msg']; // Flash dari server (bukan user input) — boleh render HTML seperti <strong>
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

// ============================================================
// REDIRECT
// ============================================================

/**
 * Redirect ke URL (internal) dan exit.
 * Whitelist: hanya URL relatif yang diizinkan.
 */
function redirect(string $url = 'index.php'): never
{
    // Tolak absolute URL / open redirect
    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '//')) {
        $url = 'index.php';
    }
    header('Location: ' . $url);
    exit;
}

// ============================================================
// AUDIT LOG
// ============================================================

/**
 * Catat aksi penting ke tabel audit_log.
 * Gunakan di setiap CRUD dan event security.
 *
 * @example log_action('CREATE_SISWA', 'siswa', $new_id, null, $new_data);
 * @example log_action('LOGIN', 'users', $user_id);
 */
function log_action(
    string  $action,
    string  $target_table,
    ?int    $target_id    = null,
    ?array  $old_values   = null,
    ?array  $new_values   = null
): void {
    try {
        $user_id    = $_SESSION['user_id'] ?? null;
        $ip         = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        $old_json   = $old_values ? json_encode($old_values, JSON_UNESCAPED_UNICODE) : null;
        $new_json   = $new_values ? json_encode($new_values, JSON_UNESCAPED_UNICODE) : null;

        // Cek apakah tabel audit_log sudah ada
        static $table_exists = null;
        if ($table_exists === null) {
            $r = db_fetch(
                "SELECT 1 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_log'
                 LIMIT 1"
            );
            $table_exists = ($r !== null);
        }

        if (!$table_exists) return; // Tabel belum ada (sebelum migration)

        db_query(
            "INSERT INTO audit_log
             (user_id, action, target_table, target_id, old_values, new_values, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            "ississss",
            [$user_id, $action, $target_table, $target_id, $old_json, $new_json, $ip, $user_agent]
        );
    } catch (Throwable $e) {
        // Log error tapi jangan hentikan aplikasi hanya karena audit gagal
        error_log("[AUDIT] Failed to log action {$action}: " . $e->getMessage());
    }
}

// ============================================================
// FORMAT HELPERS
// ============================================================

/**
 * Format angka sebagai Rupiah.
 * @example format_rupiah(1500000) → "Rp 1.500.000"
 */
function format_rupiah(float|int $angka, bool $with_prefix = true): string
{
    $formatted = 'Rp ' . number_format((float)$angka, 0, ',', '.');
    return $with_prefix ? $formatted : number_format((float)$angka, 0, ',', '.');
}

/**
 * Format tanggal Indonesia.
 * @example format_tanggal('2026-05-02') → "02 Mei 2026"
 */
function format_tanggal(string $date, string $format = 'd M Y'): string
{
    if (empty($date) || $date === '0000-00-00') return '-';
    $ts = strtotime($date);
    if ($ts === false) return '-';
    $bulan = [
        1  => 'Jan', 2  => 'Feb', 3  => 'Mar', 4  => 'Apr',
        5  => 'Mei', 6  => 'Jun', 7  => 'Jul', 8  => 'Agu',
        9  => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];
    if ($format === 'd M Y') {
        return date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    }
    if ($format === 'd/m/Y') return date('d/m/Y', $ts);
    return date($format, $ts);
}

/**
 * Hitung umur dari tanggal lahir.
 * @example hitung_umur('2005-04-12') → 21
 */
function hitung_umur(string $tgl_lahir): int
{
    if (empty($tgl_lahir)) return 0;
    return (int)date_diff(date_create($tgl_lahir), date_create('today'))->y;
}

/**
 * Singkat teks panjang.
 * @example truncate('Ini teks panjang sekali', 10) → "Ini teks p..."
 */
function truncate(string $text, int $length = 50): string
{
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '...';
}

/**
 * Generate badge HTML untuk status siswa.
 */
function badge_status_siswa(string $status): string
{
    $map = [
        'Aktif'      => 'success',
        'Lulus'      => 'primary',
        'Cuti'       => 'warning',
        'Tidak Aktif'=> 'secondary',
    ];
    $class = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $class . '">' . e($status) . '</span>';
}

/**
 * Sanitasi nomor WA ke format 62xxxx
 */
function sanitize_wa(string $nomor): string
{
    $nomor = preg_replace('/[^0-9]/', '', $nomor);
    if (str_starts_with($nomor, '0')) {
        $nomor = '62' . substr($nomor, 1);
    }
    if (!str_starts_with($nomor, '62')) {
        $nomor = '62' . $nomor;
    }
    return $nomor;
}

/**
 * Buat link WhatsApp
 */
function wa_link(string $nomor, string $pesan = ''): string
{
    $nomor = sanitize_wa($nomor);
    $url   = 'https://wa.me/' . $nomor;
    if ($pesan) $url .= '?text=' . urlencode($pesan);
    return $url;
}

// ============================================================
// INPUT HELPERS
// ============================================================

/**
 * Ambil nilai POST dengan aman, trim whitespace.
 * @param string $key   Nama field
 * @param mixed  $default  Default jika tidak ada
 */
function post(string $key, mixed $default = ''): mixed
{
    $val = $_POST[$key] ?? $default;
    if (is_string($val)) $val = trim($val);
    return ($val === '') ? $default : $val;
}

/**
 * Ambil nilai GET dengan aman.
 */
function get(string $key, mixed $default = ''): mixed
{
    $val = $_GET[$key] ?? $default;
    if (is_string($val)) $val = trim($val);
    return ($val === '') ? $default : $val;
}

/**
 * Ambil integer dari POST/GET.
 */
function post_int(string $key, int $default = 0): int
{
    return (int)($_POST[$key] ?? $default);
}

function get_int(string $key, int $default = 0): int
{
    return (int)($_GET[$key] ?? $default);
}

// ============================================================
// PAGINATION RENDER
// ============================================================

/**
 * Render Bootstrap pagination links.
 * @param array $pag  Result dari db_paginate()
 * @param array $extra_params  GET params tambahan (filter, dll)
 */
function pagination_links(array $pag, array $extra_params = []): string
{
    if ($pag['last_page'] <= 1) return '';

    $current = $pag['page'];
    $last    = $pag['last_page'];
    $params  = array_merge($extra_params, ['page' => '%d']);
    $base    = '?' . http_build_query($params);

    $html = '<nav><ul class="pagination pagination-sm justify-content-center mb-0">';

    // Prev
    $html .= '<li class="page-item ' . ($current <= 1 ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . sprintf($base, $current - 1) . '">&laquo;</a></li>';

    // Pages (show up to 5 around current)
    $start = max(1, $current - 2);
    $end   = min($last, $current + 2);

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($base, 1) . '">1</a></li>';
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $current ? ' active' : '';
        $html  .= '<li class="page-item' . $active . '"><a class="page-link" href="' . sprintf($base, $i) . '">' . $i . '</a></li>';
    }

    if ($end < $last) {
        if ($end < $last - 1) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($base, $last) . '">' . $last . '</a></li>';
    }

    // Next
    $html .= '<li class="page-item ' . ($current >= $last ? 'disabled' : '') . '">';
    $html .= '<a class="page-link" href="' . sprintf($base, $current + 1) . '">&raquo;</a></li>';
    $html .= '</ul></nav>';

    return $html;
}

// ============================================================
// NOTIFIKASI — WhatsApp via Fonnte
// ============================================================

/**
 * Kirim pesan WhatsApp via Fonnte API.
 * Mengembalikan true jika berhasil, false jika gagal atau token belum dikonfigurasi.
 *
 * @param string $nomor  Nomor tujuan (format: 08xxx atau 628xxx)
 * @param string $pesan  Isi pesan (maks ~4096 karakter)
 */
function send_wa(string $nomor, string $pesan): bool
{
    if (!defined('FONNTE_TOKEN') || !FONNTE_TOKEN) return false;

    // Normalisasi nomor: 08xxx → 628xxx
    $nomor = preg_replace('/\D/', '', $nomor);
    if (str_starts_with($nomor, '0')) {
        $nomor = '62' . substr($nomor, 1);
    }
    if (!$nomor) return false;

    try {
        $ch = curl_init('https://api.fonnte.com/send');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => ['Authorization: ' . FONNTE_TOKEN],
            CURLOPT_POSTFIELDS     => http_build_query([
                'target'  => $nomor,
                'message' => $pesan,
            ]),
        ]);
        $res = curl_exec($ch);
        $err = curl_errno($ch);
        curl_close($ch);

        if ($err) return false;
        $json = json_decode($res, true);
        return isset($json['status']) && $json['status'] === true;
    } catch (Throwable) {
        return false;
    }
}

/**
 * Template pesan WA untuk berbagai event.
 */
function wa_template(string $tipe, array $data): string
{
    return match($tipe) {
        'membership_expired' =>
            "Halo *{$data['nama']}*,\n\nMembership Anda di *Perkasa Mulia Training Center* untuk program *{$data['program']}* akan berakhir pada *{$data['tanggal']}*.\n\nSegera hubungi admin untuk perpanjangan. Terima kasih! 🏋️",
        'pembayaran_konfirmasi' =>
            "Halo *{$data['nama']}*,\n\nPembayaran SPP Anda sebesar *{$data['nominal']}* untuk periode *{$data['periode']}* telah kami terima pada *{$data['tanggal']}*.\n\nTerima kasih atas kepercayaan Anda! 🙏\n\n*Perkasa Mulia Training Center*",
        'jadwal_reminder' =>
            "Reminder: *{$data['nama']}*, besok ada sesi latihan:\n\n📅 *{$data['tanggal']}*\n⏰ *{$data['waktu']}*\n📍 *{$data['lokasi']}*\n\nSampai jumpa! 💪",
        'nilai_psikologi' =>
            "Halo *{$data['nama']}*,\n\nHasil psikotes Anda telah masuk:\n\n🧠 Kecerdasan: *{$data['kecerdasan']}*\n👁️ Kecermatan: *{$data['kecermatan']}*\n❤️ Kepribadian: *{$data['kepribadian']}*\n\nStatus: *{$data['status']}*\n\n_Perkasa Mulia Training Center_",
        default => $data['pesan'] ?? '',
    };
}

/**
 * set_flash() — alias flash() untuk konsistensi kode modul yang lebih baru.
 */
function set_flash(string $type, string $msg): void
{
    flash($type, $msg);
}

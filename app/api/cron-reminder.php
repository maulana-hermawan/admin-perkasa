<?php
/**
 * app/api/cron-reminder.php
 * Endpoint untuk pengiriman WA reminder otomatis.
 *
 * Dipanggil oleh cron job eksternal (mis. cron-job.org) sekali sehari.
 * Method: POST JSON  {"secret": "..."}
 *   atau  GET        ?secret=...
 *
 * Tipe reminder yang dikirim:
 *   1. Membership hampir habis (3 hari & 7 hari lagi)
 *   2. Pembayaran belum lunas > 14 hari sejak dibuat
 *
 * Security: validasi shared secret.
 * Rate limit bawaan: 1 eksekusi per hari per siswa (cek audit_log).
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/core/db.php';
require_once __DIR__ . '/../../app/core/helpers.php';

// ── Auth secret ───────────────────────────────────────────────
$expected = defined('CAT_WEBHOOK_SECRET') && CAT_WEBHOOK_SECRET
    ? CAT_WEBHOOK_SECRET
    : 'perkasa-cat-secret-2026';

$incoming = $_GET['secret']
    ?? (json_decode(file_get_contents('php://input'), true)['secret'] ?? '');

if (!hash_equals($expected, $incoming)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}

$today      = date('Y-m-d');
$sent_count = 0;
$skip_count = 0;
$results    = [];

// ── 1. Reminder membership hampir habis ──────────────────────
// Kirim WA 7 hari sebelum & 3 hari sebelum. Masing-masing 1× per siswa per tanggal.
$expiry_days = [7, 3];

foreach ($expiry_days as $days_ahead) {
    $target_date = date('Y-m-d', strtotime("+{$days_ahead} days"));

    $expiring = db_fetch_all(
        "SELECT m.id AS membership_id, m.tanggal_selesai_aktif,
                s.id AS siswa_id, s.nama_lengkap, s.nomor_wa,
                p.nama_program
         FROM membership_siswa m
         JOIN siswa s ON m.siswa_id = s.id
         JOIN program p ON m.program_id = p.id
         WHERE m.tanggal_selesai_aktif = ?
           AND m.status_membership IN ('Aktif','Berjalan')
           AND m.deleted_at IS NULL
           AND s.deleted_at IS NULL
           AND s.nomor_wa IS NOT NULL
           AND s.nomor_wa != ''",
        "s", [$target_date]
    );

    foreach ($expiring as $row) {
        // Cek apakah sudah dikirim hari ini (audit_log)
        $already = db_value(
            "SELECT id FROM audit_log
             WHERE action='CRON_REMINDER_MEMBERSHIP'
               AND target_id=?
               AND DATE(created_at)=?
             LIMIT 1",
            "is", [(int)$row['membership_id'], $today]
        );
        if ($already) { $skip_count++; continue; }

        $pesan = wa_template('membership_expired', [
            'nama'     => $row['nama_lengkap'],
            'program'  => $row['nama_program'],
            'tanggal'  => date('d M Y', strtotime($row['tanggal_selesai_aktif'])),
        ]);

        $ok = send_wa($row['nomor_wa'], $pesan);

        log_action('CRON_REMINDER_MEMBERSHIP', 'membership_siswa', (int)$row['membership_id'], null, [
            'siswa'        => $row['nama_lengkap'],
            'program'      => $row['nama_program'],
            'expire_date'  => $row['tanggal_selesai_aktif'],
            'days_ahead'   => $days_ahead,
            'wa_sent'      => $ok,
        ]);

        if ($ok) $sent_count++;
        $results[] = [
            'type'    => 'membership',
            'siswa'   => $row['nama_lengkap'],
            'program' => $row['nama_program'],
            'expiry'  => $row['tanggal_selesai_aktif'],
            'days'    => $days_ahead,
            'sent'    => $ok,
        ];
    }
}

// ── 2. Reminder pembayaran belum lunas > 14 hari ─────────────
$cutoff = date('Y-m-d', strtotime('-14 days'));

$overdue = db_fetch_all(
    "SELECT ps.id AS pay_id, ps.nominal_tagihan, ps.nominal_bayar,
            ps.tanggal_bayar, ps.created_at,
            s.id AS siswa_id, s.nama_lengkap, s.nomor_wa
     FROM pembayaran_siswa ps
     JOIN siswa s ON ps.siswa_id = s.id
     WHERE ps.status_bayar IN ('Belum Bayar','Cicilan')
       AND ps.deleted_at IS NULL
       AND s.deleted_at IS NULL
       AND s.nomor_wa IS NOT NULL AND s.nomor_wa != ''
       AND DATE(ps.created_at) <= ?",
    "s", [$cutoff]
);

foreach ($overdue as $row) {
    // Kirim maksimal 1× per 7 hari per pembayaran
    $already = db_value(
        "SELECT id FROM audit_log
         WHERE action='CRON_REMINDER_PAYMENT'
           AND target_id=?
           AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         LIMIT 1",
        "i", [(int)$row['pay_id']]
    );
    if ($already) { $skip_count++; continue; }

    $sisa   = (float)$row['nominal_tagihan'] - (float)$row['nominal_bayar'];
    $pesan  = "Halo *{$row['nama_lengkap']}*,\n\n"
            . "Kami ingatkan bahwa masih ada tagihan SPP yang belum dilunasi "
            . "sebesar *" . format_rupiah($sisa) . "*.\n\n"
            . "Mohon segera menyelesaikan pembayaran. "
            . "Hubungi admin jika ada pertanyaan.\n\n"
            . "_Perkasa Mulia Training Center_";

    $ok = send_wa($row['nomor_wa'], $pesan);

    log_action('CRON_REMINDER_PAYMENT', 'pembayaran_siswa', (int)$row['pay_id'], null, [
        'siswa'   => $row['nama_lengkap'],
        'sisa'    => $sisa,
        'wa_sent' => $ok,
    ]);

    if ($ok) $sent_count++;
    $results[] = [
        'type'  => 'payment',
        'siswa' => $row['nama_lengkap'],
        'sisa'  => $sisa,
        'sent'  => $ok,
    ];
}

http_response_code(200);
echo json_encode([
    'ok'         => true,
    'date'       => $today,
    'sent'       => $sent_count,
    'skipped'    => $skip_count,
    'total'      => count($results),
    'detail'     => $results,
]);

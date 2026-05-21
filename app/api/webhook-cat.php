<?php
/**
 * app/api/webhook-cat.php
 * Receiver webhook dari Psikotes CAT.
 * CAT kirim hasil ujian siswa → admin panel insert ke penilaian_psikologi.
 *
 * Security: validasi 'secret' token (shared secret antara CAT dan admin).
 * Method: POST JSON
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/core/db.php';
require_once __DIR__ . '/../../app/core/helpers.php';

// Hanya POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Method not allowed']);
    exit();
}

// Parse JSON
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Invalid JSON']);
    exit();
}

// ── Validasi secret token ─────────────────────────────────────
$expected_secret = defined('CAT_WEBHOOK_SECRET') && CAT_WEBHOOK_SECRET
    ? CAT_WEBHOOK_SECRET
    : 'perkasa-cat-secret-2026';

$incoming_secret = $data['secret'] ?? '';
if (!hash_equals($expected_secret, $incoming_secret)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit();
}

// ── Validasi field ────────────────────────────────────────────
$nama_peserta     = trim($data['nama_peserta']    ?? '');
$skor_kecerdasan  = max(0, min(100, (float)($data['skor_kecerdasan']  ?? 0)));
$skor_kecermatan  = max(0, min(100, (float)($data['skor_kecermatan']  ?? 0)));
$skor_kepribadian = max(0, min(100, (float)($data['skor_kepribadian'] ?? 0)));
$nilai_akhir      = max(0, min(100, (float)($data['nilai_akhir']      ?? 0)));
$status_cat       = $data['status']       ?? 'TIDAK MEMENUHI SYARAT';
$waktu_ujian      = $data['waktu_ujian']  ?? date('Y-m-d H:i:s');
$cat_id           = (int)($data['cat_id'] ?? 0);
$tanggal_tes      = date('Y-m-d', strtotime($waktu_ujian));

if (!$nama_peserta) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'nama_peserta wajib']);
    exit();
}

// ── Cari siswa berdasarkan nama ───────────────────────────────
// Cari EXACT match dulu, lalu LIKE
$siswa = db_fetch(
    "SELECT id, nama_lengkap, nomor_wa FROM siswa
     WHERE nama_lengkap = ? AND deleted_at IS NULL LIMIT 1",
    "s", [$nama_peserta]
);

if (!$siswa) {
    // Coba partial match (hanya jika unik)
    $candidates = db_fetch_all(
        "SELECT id, nama_lengkap, nomor_wa FROM siswa
         WHERE nama_lengkap LIKE ? AND deleted_at IS NULL LIMIT 2",
        "s", ['%' . $nama_peserta . '%']
    );
    $siswa = (count($candidates) === 1) ? $candidates[0] : null;
}

// ── Insert ke penilaian_psikologi ─────────────────────────────
$siswa_id    = $siswa ? (int)$siswa['id'] : null;
$status_lkp  = ($skor_kecerdasan >= 61 && $skor_kecermatan >= 61 && $skor_kepribadian >= 61)
               ? 'Lulus' : 'Tidak Lulus';

if ($siswa_id) {
    // Cek duplikat: siswa + tanggal yang sama
    $dup = db_value(
        "SELECT id FROM penilaian_psikologi WHERE siswa_id=? AND tanggal_tes=? AND sumber='CAT' LIMIT 1",
        "is", [$siswa_id, $tanggal_tes]
    );

    if ($dup) {
        // Update — data lebih baru dari CAT menggantikan yang lama
        db_execute(
            "UPDATE penilaian_psikologi
             SET kecerdasan=?,kecermatan=?,kepribadian=?,sumber='CAT',sumber_referensi=?,updated_at=NOW()
             WHERE id=?",
            "dddsi",
            [$skor_kecerdasan, $skor_kecermatan, $skor_kepribadian,
             'cat_id:' . $cat_id, $dup]
        );
        $action = 'UPDATED';
        $rec_id = $dup;
    } else {
        db_execute(
            "INSERT INTO penilaian_psikologi
             (siswa_id,tanggal_tes,kecerdasan,kecermatan,kepribadian,sumber,sumber_referensi,created_by)
             VALUES (?,?,?,?,?,'CAT',?,NULL)",
            "isddds",
            [$siswa_id, $tanggal_tes, $skor_kecerdasan,
             $skor_kecermatan, $skor_kepribadian, 'cat_id:' . $cat_id]
        );
        $rec_id = db_conn()->insert_id;
        $action = 'CREATED';

        // Kirim WA notif jika siswa punya nomor
        if (!empty($siswa['nomor_wa'])) {
            $pesan = wa_template('nilai_psikologi', [
                'nama'        => $siswa['nama_lengkap'],
                'kecerdasan'  => number_format($skor_kecerdasan, 1),
                'kecermatan'  => number_format($skor_kecermatan, 1),
                'kepribadian' => number_format($skor_kepribadian, 1),
                'status'      => $status_lkp,
            ]);
            send_wa($siswa['nomor_wa'], $pesan);
        }
    }

    // Audit log
    log_action('WEBHOOK_CAT', 'penilaian_psikologi', $rec_id, null, [
        'nama_peserta' => $nama_peserta,
        'siswa_id'     => $siswa_id,
        'kecerdasan'   => $skor_kecerdasan,
        'kecermatan'   => $skor_kecermatan,
        'kepribadian'  => $skor_kepribadian,
        'cat_id'       => $cat_id,
    ]);

    http_response_code(200);
    echo json_encode([
        'ok'       => true,
        'action'   => $action,
        'siswa_id' => $siswa_id,
        'rec_id'   => $rec_id,
        'msg'      => "Nilai psikologi siswa '{$siswa['nama_lengkap']}' berhasil disimpan.",
    ]);
} else {
    // Siswa tidak ditemukan — simpan ke queue / log manual
    log_action('WEBHOOK_CAT_UNMATCHED', 'penilaian_psikologi', 0, null, [
        'nama_peserta'    => $nama_peserta,
        'skor_kecerdasan' => $skor_kecerdasan,
        'skor_kecermatan' => $skor_kecermatan,
        'skor_kepribadian'=> $skor_kepribadian,
        'nilai_akhir'     => $nilai_akhir,
        'cat_id'          => $cat_id,
        'note'            => 'Siswa tidak ditemukan di database — perlu match manual',
    ]);

    http_response_code(202); // Accepted but not fully processed
    echo json_encode([
        'ok'     => false,
        'action' => 'UNMATCHED',
        'msg'    => "Siswa '$nama_peserta' tidak ditemukan. Data dicatat di audit_log untuk review manual.",
    ]);
}

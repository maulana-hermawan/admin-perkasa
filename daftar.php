<?php
/**
 * daftar.php — Form pendaftaran siswa online (publik, tanpa login)
 * Submit → masuk tabel calon_siswa dengan status 'Baru' → admin verifikasi
 */

declare(strict_types=1);

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/app.php';
require_once __DIR__ . '/app/core/db.php';
require_once __DIR__ . '/app/core/helpers.php';
require_once __DIR__ . '/app/core/auth.php';

// Mulai session — gunakan session_bootstrap jika ada, fallback ke session_start
if (session_status() === PHP_SESSION_NONE) {
    if (function_exists('session_bootstrap')) {
        session_bootstrap();
    } else {
        session_start();
    }
}

$success = false;
$error   = '';
$nomor_pendaftaran = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simple honeypot (anti-spam)
    if (!empty($_POST['website'])) { sleep(2); exit(); }

    $nama     = trim(post('nama_lengkap',''));
    $wa       = preg_replace('/\D/','',post('nomor_wa',''));
    $email    = strtolower(trim(post('email','')));
    $lahir    = post('tanggal_lahir','') ?: null;
    $sekolah  = trim(post('asal_sekolah',''));
    $ortu     = trim(post('nama_ortu',''));
    $wa_ortu  = preg_replace('/\D/','',post('nomor_wa_ortu',''));
    $target   = post('target_seleksi','');
    $gender   = post('jenis_kelamin','');
    $alamat   = trim(post('alamat',''));
    $catatan  = trim(post('catatan',''));

    if (!$nama || strlen($nama) < 3) { $error = 'Nama lengkap minimal 3 karakter.'; }
    elseif (!$wa || strlen($wa) < 9)  { $error = 'Nomor WhatsApp tidak valid.'; }
    else {
        // Cek duplikat (nomor WA sama hari ini)
        $dup = db_value(
            "SELECT id FROM calon_siswa WHERE nomor_wa=? AND DATE(created_at)=CURDATE()",
            "s", [$wa]
        );
        if ($dup) {
            $error = 'Nomor WhatsApp ini sudah terdaftar hari ini. Admin akan menghubungi Anda segera.';
        } else {
            try {
                // Schema-safe: kolom ortu mungkin belum dimigrasi di server
                static $_has_ortu = null;
                if ($_has_ortu === null) {
                    $_has_ortu = (bool)db_value(
                        "SELECT COUNT(*) FROM information_schema.COLUMNS
                         WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='calon_siswa' AND COLUMN_NAME='nama_ortu'"
                    );
                }
                if ($_has_ortu) {
                    db_execute(
                        "INSERT INTO calon_siswa (nama_lengkap,nomor_wa,email,tanggal_lahir,asal_sekolah,nama_ortu,nomor_wa_ortu,target_seleksi,jenis_kelamin,alamat,catatan_pendaftar)
                         VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                        "sssssssssss",
                        [$nama, $wa, $email ?: null, $lahir, $sekolah ?: null,
                         $ortu ?: null, $wa_ortu ?: null,
                         $target ?: null, $gender ?: null, $alamat ?: null, $catatan ?: null]
                    );
                } else {
                    db_execute(
                        "INSERT INTO calon_siswa (nama_lengkap,nomor_wa,email,tanggal_lahir,asal_sekolah,target_seleksi,jenis_kelamin,alamat,catatan_pendaftar)
                         VALUES (?,?,?,?,?,?,?,?,?)",
                        "sssssssss",
                        [$nama, $wa, $email ?: null, $lahir, $sekolah ?: null,
                         $target ?: null, $gender ?: null, $alamat ?: null, $catatan ?: null]
                    );
                }
                $new_id = db_conn()->insert_id;
                $nomor_pendaftaran = 'PDR-' . date('Ymd') . '-' . str_pad((string)$new_id, 4, '0', STR_PAD_LEFT);
                $success = true;

                // Notif WA ke admin (bisa >1 nomor, pisahkan dengan koma)
                $admin_wa = env('ADMIN_WA_NUMBER','087777538280,081235647133');
                foreach (array_filter(array_map('trim', explode(',', $admin_wa))) as $no) {
                    send_wa($no, "📋 Pendaftar baru!\n\n*Nama:* $nama\n*WA:* $wa\n*Target:* " . ($target ?: '-') . "\n\nCek di panel admin → Pendaftaran.");
                }
            } catch (Throwable $ex) {
                $error = 'Terjadi kesalahan sistem. Coba lagi atau hubungi admin langsung via WhatsApp.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Online — Perkasa Mulia Training Center</title>
    <link rel="icon" type="image/svg+xml" href="assets/logo.svg">
    <link rel="apple-touch-icon" href="assets/logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { background: linear-gradient(135deg,#001233 0%,#0d3b66 100%); min-height: 100vh; padding: 2rem 1rem; }
        .reg-card { max-width:580px; margin:0 auto; background:#fff; border-radius:16px; overflow:hidden; }
        .reg-header { background:#001233; color:#fff; padding:1.5rem 2rem; }
    </style>
</head>
<body>
<div class="reg-card shadow-lg">
    <div class="reg-header">
        <div class="d-flex align-items-center gap-3 mb-2">
            <img src="assets/logo.svg"
                 alt="Logo PMTC"
                 style="width:40px;height:40px;object-fit:contain;filter:brightness(0) invert(1);"
                 onerror="this.outerHTML='<span style=\'font-size:1.8rem;\'>🏋️</span>'">
            <h4 class="fw-bold mb-0">Perkasa Mulia Training Center</h4>
        </div>
        <p class="mb-0 opacity-75 small">Formulir Pendaftaran Siswa Baru</p>
        <p class="mb-0 opacity-60" style="font-size:.72rem;">Persiapan Tes Polri & TNI — Bogor</p>
    </div>

    <div class="p-4">
        <?php if ($success): ?>
        <div class="text-center py-4">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 mb-3"
                 style="width:72px;height:72px;font-size:2.5rem;">✅</div>
            <h5 class="fw-bold">Pendaftaran Diterima!</h5>
            <p class="text-muted small mb-3">Nomor pendaftaran Anda:</p>
            <div class="alert alert-success fw-bold fs-5"><?= e($nomor_pendaftaran) ?></div>
            <p class="text-muted small">Tim kami akan menghubungi Anda via <strong>WhatsApp</strong> dalam waktu 1×24 jam untuk konfirmasi dan informasi selanjutnya.</p>
            <a href="daftar.php" class="btn btn-outline-success btn-sm mt-2">Daftar Lagi</a>
        </div>

        <?php else: ?>

        <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle-fill me-1"></i><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate id="regForm">
            <!-- Honeypot -->
            <input type="text" name="website" style="display:none;" tabindex="-1" autocomplete="off">

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-bold small">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama_lengkap" class="form-control" required
                           value="<?= e(post('nama_lengkap','')) ?>" placeholder="Sesuai KTP">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Nomor WhatsApp <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-whatsapp text-success"></i></span>
                        <input type="tel" name="nomor_wa" class="form-control" required
                               value="<?= e(post('nomor_wa','')) ?>" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="form-text">Admin akan menghubungi via nomor ini.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= e(post('email','')) ?>" placeholder="Opsional">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" class="form-control"
                           value="<?= e(post('tanggal_lahir','')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="form-select">
                        <option value="">— Pilih —</option>
                        <option value="L" <?= post('jenis_kelamin')==='L'?'selected':'' ?>>Laki-laki</option>
                        <option value="P" <?= post('jenis_kelamin')==='P'?'selected':'' ?>>Perempuan</option>
                    </select>
                </div>
                <div class="col-12" x-data="{
                    seleksi: '<?= e(post('_target_base','')) ?>',
                    pangkat: '<?= e(post('_target_pangkat','')) ?>',
                    lainnya: '<?= e(post('_target_lainnya','')) ?>',
                    needPangkat() { return ['POLRI','TNI AD','TNI AL','TNI AU'].includes(this.seleksi); },
                    combined() {
                        if (this.seleksi === 'LAINNYA') return this.lainnya;
                        if (this.needPangkat() && this.pangkat) return this.pangkat + ' ' + this.seleksi;
                        return this.seleksi;
                    }
                }">
                    <input type="hidden" name="target_seleksi" :value="combined()">
                    <input type="hidden" name="_target_base" :value="seleksi">
                    <input type="hidden" name="_target_pangkat" :value="pangkat">
                    <input type="hidden" name="_target_lainnya" :value="lainnya">
                    <label class="form-label fw-bold small">Target Seleksi</label>
                    <select x-model="seleksi" class="form-select" @change="pangkat=''; lainnya='';">
                        <option value="">— Pilih (opsional) —</option>
                        <?php foreach(['POLRI','TNI AD','TNI AL','TNI AU','IPDN','STIN','STIS','STMKG','POLTEK SSN','POLTEKIP','POLTEKIM','CPNS','LAINNYA'] as $t): ?>
                        <option value="<?= $t ?>"><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div x-show="needPangkat()" x-cloak class="mt-2">
                        <label class="form-label small">Pangkat / Jenjang</label>
                        <select x-model="pangkat" class="form-select">
                            <option value="">— Pilih Pangkat —</option>
                            <option value="TAMTAMA">TAMTAMA</option>
                            <option value="BINTARA">BINTARA</option>
                            <option value="AKADEMI / PERWIRA">AKADEMI / PERWIRA</option>
                        </select>
                    </div>
                    <div x-show="seleksi === 'LAINNYA'" x-cloak class="mt-2">
                        <label class="form-label small">Sebutkan target seleksi</label>
                        <input type="text" x-model="lainnya" class="form-control" placeholder="Ketik target seleksi Anda">
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold small">Asal Sekolah / Instansi</label>
                    <input type="text" name="asal_sekolah" class="form-control"
                           value="<?= e(post('asal_sekolah','')) ?>" placeholder="SMA/SMK/PTN...">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Nama Orang Tua / Wali</label>
                    <input type="text" name="nama_ortu" class="form-control"
                           value="<?= e(post('nama_ortu','')) ?>" placeholder="Nama orang tua / wali">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Nomor WA Orang Tua / Wali</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-whatsapp text-success"></i></span>
                        <input type="tel" name="nomor_wa_ortu" class="form-control"
                               value="<?= e(post('nomor_wa_ortu','')) ?>" placeholder="08xxxxxxxxxx">
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold small">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="2"
                              placeholder="Kota/Kecamatan minimal"><?= e(post('alamat','')) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold small">Pesan / Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2"
                              placeholder="Pertanyaan atau informasi tambahan (opsional)"><?= e(post('catatan','')) ?></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success w-100 fw-bold py-2" id="submitBtn">
                        <i class="bi bi-send-fill me-2"></i>Kirim Pendaftaran
                    </button>
                </div>
                <div class="col-12">
                    <p class="text-muted text-center" style="font-size:.72rem;">
                        Data Anda akan digunakan hanya untuk keperluan pendaftaran bimbel Perkasa.<br>
                        Butuh info lebih lanjut? Chat via WA:<br>
                        <a href="https://wa.me/6287777538280">087777538280</a> (Coach Bagus)
                        &nbsp;·&nbsp;
                        <a href="https://wa.me/6281235647133">081235647133</a> (Miss Dina)
                    </p>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('regForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengirim...';
});
</script>
</body>
</html>

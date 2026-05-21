<?php
/**
 * setup.php — Onboarding wizard untuk instalasi baru
 * Setelah wizard selesai, file storage/setup.done dibuat
 * dan halaman ini tidak bisa diakses lagi.
 */

declare(strict_types=1);

// Jika sudah selesai, redirect ke login
if (file_exists(__DIR__ . '/storage/setup.done')) {
    header('Location: login.php'); exit();
}

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/core/db.php';
require_once __DIR__ . '/app/core/helpers.php';

session_bootstrap();

$step  = (int)($_GET['step'] ?? 1);
$error = '';
$success = '';

// ── Step handlers ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($step === 1) {
        // Tes koneksi DB sudah berhasil (kalau bisa sampai sini)
        redirect('setup.php?step=2');
    }

    if ($step === 2) {
        // Buat admin account
        $nama  = trim($_POST['nama'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pwd   = $_POST['password'] ?? '';
        $conf  = $_POST['confirm']  ?? '';

        if (!$nama || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Nama dan email wajib diisi dengan format yang valid.';
        } elseif (strlen($pwd) < 8) {
            $error = 'Password minimal 8 karakter.';
        } elseif ($pwd !== $conf) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            $existing = db_value("SELECT id FROM users WHERE role='admin' LIMIT 1");
            if ($existing) {
                $error = 'Admin sudah ada. Hapus akun admin lama terlebih dahulu atau lewati langkah ini.';
            } else {
                $hash = password_hash($pwd, PASSWORD_DEFAULT);
                db_execute(
                    "INSERT INTO users (email, password, role, nama_display, is_active) VALUES (?,?,?,?,1)",
                    "ssss", [$email, $hash, 'admin', $nama]
                );
                $_SESSION['setup_admin_id'] = db_conn()->insert_id;
                redirect('setup.php?step=3');
            }
        }
    }

    if ($step === 3) {
        // Buat program pertama
        $nama_program = trim($_POST['nama_program'] ?? '');
        $kategori     = $_POST['kategori'] ?? 'Akademik';
        $durasi       = (int)($_POST['durasi'] ?? 6);

        if ($nama_program) {
            $existing_p = db_value("SELECT id FROM program LIMIT 1");
            if (!$existing_p) {
                db_execute(
                    "INSERT INTO program (nama_program, kategori_program, durasi_bulan) VALUES (?,?,?)",
                    "ssi", [$nama_program, $kategori, $durasi]
                );
            }
        }
        redirect('setup.php?step=4');
    }

    if ($step === 4) {
        // Selesai — buat flag file
        file_put_contents(__DIR__ . '/storage/setup.done', date('Y-m-d H:i:s') . ' — Setup completed');
        redirect('login.php');
    }
}

// ── Cek koneksi DB di step 1 ──────────────────────────────────
$db_ok    = false;
$db_error = '';
if ($step === 1) {
    try {
        $db_ok = (bool)db_value("SELECT 1");
    } catch (Throwable $e) {
        $db_ok    = false;
        $db_error = $e->getMessage();
    }
}

$steps = [1 => 'Koneksi DB', 2 => 'Akun Admin', 3 => 'Program Pertama', 4 => 'Selesai'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup Perkasa Admin v2.0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f4f8; min-height: 100vh; display:flex; align-items:center; justify-content:center; padding:2rem 0; }
        .wizard-card { width:100%; max-width:560px; border-radius:16px; box-shadow:0 8px 40px rgba(0,0,0,.12); }
        .step-badge { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.8rem; font-weight:700; }
        .step-done  { background:#198754; color:#fff; }
        .step-active{ background:#0d6efd; color:#fff; }
        .step-todo  { background:#e9ecef; color:#6c757d; }
    </style>
</head>
<body>
<div class="wizard-card bg-white p-0 overflow-hidden">
    <!-- Header -->
    <div style="background:#001233; color:#fff; padding:1.5rem;">
        <div class="fw-bold" style="font-size:1.1rem;">🏋️ Perkasa Mulia Training Center</div>
        <div style="opacity:.7; font-size:.85rem;">Setup Wizard v2.0</div>
    </div>

    <!-- Step indicators -->
    <div class="d-flex border-bottom px-4 py-3 gap-3 overflow-x-auto">
        <?php foreach ($steps as $n => $label): ?>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <div class="step-badge <?= $n < $step ? 'step-done' : ($n === $step ? 'step-active' : 'step-todo') ?>">
                <?= $n < $step ? '✓' : $n ?>
            </div>
            <span class="small <?= $n === $step ? 'fw-bold' : 'text-muted' ?>"><?= $label ?></span>
        </div>
        <?php if ($n < count($steps)): ?><div class="text-muted">—</div><?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="p-4">
        <?php if ($error): ?>
        <div class="alert alert-danger py-2 small mb-3"><i class="bi bi-exclamation-triangle-fill me-1"></i><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
        <!-- Step 1: DB Check -->
        <h5 class="fw-bold mb-3">Langkah 1 — Cek Koneksi Database</h5>
        <?php if ($db_ok): ?>
        <div class="alert alert-success small mb-3">
            <i class="bi bi-check-circle-fill me-2"></i>
            Koneksi ke MySQL berhasil! Database siap digunakan.
        </div>
        <p class="text-muted small">Pastikan Anda sudah menjalankan semua migration SQL di folder
        <code>database/migrations/</code> secara berurutan (001 sampai 010) sebelum melanjutkan.</p>
        <form method="POST">
            <button class="btn btn-primary">Lanjut ke Langkah 2 →</button>
        </form>
        <?php else: ?>
        <div class="alert alert-danger small mb-3">
            <i class="bi bi-x-circle-fill me-2"></i>
            Koneksi database gagal.<br>
            <?php if ($db_error): ?>
            <code style="font-size:.75rem;"><?= htmlspecialchars($db_error) ?></code><br>
            <?php endif; ?>
            Periksa file <code>.env</code> — pastikan DB_HOST, DB_USER, DB_PASS, DB_NAME sudah benar.
        </div>
        <a href="setup.php?step=1" class="btn btn-outline-danger btn-sm">Coba Lagi</a>
        <?php endif; ?>

        <?php elseif ($step === 2): ?>
        <!-- Step 2: Admin Account -->
        <h5 class="fw-bold mb-3">Langkah 2 — Buat Akun Admin</h5>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold small">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" name="nama" class="form-control" required placeholder="Nama admin">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">Email Login <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" required placeholder="admin@perkasa.id">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">Password <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" required minlength="8" placeholder="Min. 8 karakter">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold small">Konfirmasi Password <span class="text-danger">*</span></label>
                <input type="password" name="confirm" class="form-control" required minlength="8">
            </div>
            <div class="d-flex gap-2">
                <a href="setup.php?step=1" class="btn btn-outline-secondary btn-sm">← Kembali</a>
                <button class="btn btn-primary">Buat Akun & Lanjut →</button>
            </div>
        </form>

        <?php elseif ($step === 3): ?>
        <!-- Step 3: First Program -->
        <h5 class="fw-bold mb-3">Langkah 3 — Program Pertama (Opsional)</h5>
        <p class="text-muted small mb-3">Buat program latihan pertama. Bisa dilewati dan ditambahkan nanti dari menu admin.</p>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold small">Nama Program</label>
                <input type="text" name="nama_program" class="form-control" placeholder="mis: Persiapan Polri TNI 6 Bulan">
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Kategori</label>
                    <select name="kategori" class="form-select">
                        <option value="Akademik">Akademik</option>
                        <option value="Jasmani">Jasmani</option>
                        <option value="Fasilitas">Fasilitas</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold small">Durasi (bulan)</label>
                    <input type="number" name="durasi" class="form-control" value="6" min="1" max="24">
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="setup.php?step=2" class="btn btn-outline-secondary btn-sm">← Kembali</a>
                <button class="btn btn-primary">Lanjut →</button>
                <a href="setup.php?step=4" class="btn btn-outline-secondary btn-sm">Lewati</a>
            </div>
        </form>

        <?php elseif ($step === 4): ?>
        <!-- Step 4: Done -->
        <div class="text-center py-3">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 mb-3"
                 style="width:72px;height:72px;font-size:2.5rem;">🎉</div>
            <h5 class="fw-bold">Setup Selesai!</h5>
            <p class="text-muted small mb-4">
                Perkasa Admin v2.0 siap digunakan. Login dengan akun admin yang baru dibuat.
            </p>
            <div class="alert alert-warning text-start small mb-4">
                <strong>Langkah selanjutnya:</strong>
                <ol class="mb-0 mt-1">
                    <li>Login ke panel admin</li>
                    <li>Isi data siswa pertama di menu <strong>Data Keanggotaan</strong></li>
                    <li>Tambahkan tutor di menu <strong>Manajemen Tutor</strong></li>
                    <li>Buat jadwal latihan pertama</li>
                    <li>Aktifkan 2FA di <strong>Pengaturan Akun</strong> untuk keamanan ekstra</li>
                </ol>
            </div>
            <form method="POST">
                <button class="btn btn-success fw-bold px-4">
                    <i class="bi bi-arrow-right-circle me-2"></i>Selesai & Login
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

<?php
/**
 * app/layouts/403.php — Halaman Akses Ditolak
 * Role-aware: siswa → portal-siswa.php, tutor → portal-tutor.php, guest → login.php
 */

// Tentukan link tombol kembali berdasarkan role session
$role = $_SESSION['role'] ?? '';
if ($role === 'siswa') {
    $back_url   = 'portal-siswa.php';
    $back_label = '← Portal Siswa';
} elseif ($role === 'tutor') {
    $back_url   = 'portal-tutor.php';
    $back_label = '← Portal Tutor';
} elseif (in_array($role, ['admin', 'owner'], true)) {
    $back_url   = 'index.php';
    $back_label = '← Dashboard Admin';
} else {
    // Belum login atau session kosong
    $back_url   = 'login.php';
    $back_label = '← Login';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 Akses Ditolak — Perkasa Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
<div class="text-center p-4">
    <div class="display-1 fw-bold text-danger">403</div>
    <h4 class="mb-2">Akses Ditolak</h4>
    <p class="text-muted mb-4">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
    <div class="d-flex gap-2 justify-content-center flex-wrap">
        <a href="<?= htmlspecialchars($back_url) ?>" class="btn btn-primary">
            <?= htmlspecialchars($back_label) ?>
        </a>
        <a href="logout.php" class="btn btn-outline-secondary">
            🚪 Logout
        </a>
    </div>
</div>
</body>
</html>

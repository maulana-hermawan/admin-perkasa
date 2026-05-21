<?php
/**
 * app/layouts/siswa.php
 * Layout Portal Siswa — mobile-first, bottom navigation
 */
$page_title = $view_data['title'] ?? 'Portal Siswa';
$view_file  = $view_data['view']  ?? null;
$siswa      = $view_data['siswa'] ?? null;
$page       = get('page', 'dashboard');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($page_title) ?> | Perkasa Siswa</title>
    <meta name="theme-color" content="#198754">
    <link rel="icon" type="image/svg+xml" href="<?= rtrim(APP_URL,'/') ?>/assets/logo.svg">
    <link rel="apple-touch-icon" href="<?= rtrim(APP_URL,'/') ?>/assets/logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
    <style>
        body { background: var(--color-background-tertiary); padding-bottom: 80px; }
        .ps-header { background: #198754; color: #fff; padding: 1rem; display: flex; align-items: center; gap: .75rem; position: sticky; top: 0; z-index: 100; }
        .ps-header__avatar { width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,.2); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .9rem; color: #fff; flex-shrink: 0; }
        .ps-bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: var(--color-background-primary); border-top: 0.5px solid var(--color-border-tertiary); display: flex; z-index: 200; padding-bottom: env(safe-area-inset-bottom); }
        .ps-nav-item { flex: 1; display: flex; flex-direction: column; align-items: center; padding: .6rem .25rem; gap: 3px; text-decoration: none; color: var(--color-text-secondary); font-size: .65rem; transition: color .15s; -webkit-tap-highlight-color: transparent; }
        .ps-nav-item i { font-size: 1.3rem; }
        .ps-nav-item.active { color: #198754; }
        .ps-content { padding: 1rem; }
        .pk-mobile-card { background: var(--color-background-primary); border-radius: var(--border-radius-lg); border: 0.5px solid var(--color-border-tertiary); padding: 1rem; margin-bottom: .75rem; }
        @media(min-width:640px) { .ps-content { max-width: 600px; margin: 0 auto; } }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>
</head>
<body>

<div class="ps-header">
    <!-- Logo kecil -->
    <img src="<?= rtrim(APP_URL,'/') ?>/assets/logo.svg"
         alt="Logo PMTC"
         style="width:28px;height:28px;object-fit:contain;filter:brightness(0) invert(1);flex-shrink:0;"
         onerror="this.style.display='none'">
    <div class="ps-header__avatar">
        <?= strtoupper(substr($siswa['nama_lengkap'] ?? 'S', 0, 1)) ?>
    </div>
    <div class="flex-grow-1 lh-1">
        <div class="fw-bold" style="font-size:.9rem;"><?= e($siswa['nama_lengkap'] ?? 'Siswa') ?></div>
        <div style="font-size:.72rem; opacity:.7;"><?= e($siswa['nomor_induk'] ?? 'Portal Siswa') ?></div>
    </div>
    <a href="logout.php" class="text-white text-decoration-none" style="opacity:.7;" title="Keluar">
        <i class="bi bi-power"></i>
    </a>
</div>

<div class="px-3 pt-2">
    <?php require __DIR__ . '/_partials/flash.php'; ?>
</div>

<div class="ps-content">
    <?php if ($view_file && is_file($view_file)):
        extract($view_data);
        include $view_file;
    endif; ?>
</div>

<nav class="ps-bottom-nav">
    <?php
    $nav = [
        ['page'=>'dashboard','icon'=>'bi-grid-1x2-fill',      'label'=>'Beranda'],
        ['page'=>'jadwal',   'icon'=>'bi-calendar-event-fill', 'label'=>'Jadwal'],
        ['page'=>'nilai',    'icon'=>'bi-bar-chart-fill',      'label'=>'Nilai'],
        ['page'=>'tagihan',  'icon'=>'bi-receipt',             'label'=>'Tagihan'],
        ['page'=>'profil',   'icon'=>'bi-person-circle',       'label'=>'Profil'],
    ];
    foreach ($nav as $n):
        $active = ($page === $n['page']) ? 'active' : '';
    ?>
    <a href="portal-siswa.php?page=<?= $n['page'] ?>" class="ps-nav-item <?= $active ?>">
        <i class="bi <?= $n['icon'] ?>"></i>
        <span><?= $n['label'] ?></span>
    </a>
    <?php endforeach; ?>
</nav>

<div id="pkToastContainer" class="position-fixed bottom-0 end-0 p-3" style="z-index:9999; padding-bottom:90px !important;"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
function pkToast(msg, type='success', d=4500) {
    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${type} border-0 mb-2 show`;
    el.innerHTML = `<div class="d-flex"><div class="toast-body">${msg}</div><button class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.toast').remove()"></button></div>`;
    document.getElementById('pkToastContainer').appendChild(el);
    setTimeout(() => el.remove(), d);
}
</script>
</body>
</html>

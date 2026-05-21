<?php
/**
 * app/layouts/tutor.php
 * Layout Portal Tutor — mobile-first, bottom navigation
 */

$page_title = $view_data['title'] ?? 'Portal Tutor';
$view_file  = $view_data['view']  ?? null;
$tutor      = $view_data['tutor'] ?? null;
$page       = get('page','dashboard');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($page_title) ?> | Perkasa Tutor</title>
    <meta name="theme-color" content="#001233">
    <link rel="icon" type="image/svg+xml" href="<?= rtrim(APP_URL,'/') ?>/assets/logo.svg">
    <link rel="apple-touch-icon" href="<?= rtrim(APP_URL,'/') ?>/assets/logo.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
    <style>
        body { background: var(--color-background-tertiary); padding-bottom: 80px; }
        .pt-header { background: var(--pk-navy-900,#001233); color: #fff; padding: 1rem; display: flex; align-items: center; gap: .75rem; position: sticky; top: 0; z-index: 100; }
        .pt-header__avatar { width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .9rem; color: #fff; flex-shrink: 0; }
        .pt-bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: var(--color-background-primary); border-top: 0.5px solid var(--color-border-tertiary); display: flex; z-index: 200; padding-bottom: env(safe-area-inset-bottom); }
        .pt-nav-item { flex: 1; display: flex; flex-direction: column; align-items: center; padding: .6rem .25rem; gap: 3px; text-decoration: none; color: var(--color-text-secondary); font-size: .65rem; transition: color .15s; -webkit-tap-highlight-color: transparent; }
        .pt-nav-item i { font-size: 1.3rem; }
        .pt-nav-item.active { color: var(--pk-navy-900, #001233); }
        .pt-nav-item.active i { font-weight: 900; }
        .pt-content { padding: 1rem; }
        .pk-mobile-card { background: var(--color-background-primary); border-radius: var(--border-radius-lg); border: 0.5px solid var(--color-border-tertiary); padding: 1rem; margin-bottom: .75rem; }
        @media(min-width:640px) { .pt-content { max-width: 600px; margin: 0 auto; } }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>
</head>
<body>

<!-- Header -->
<div class="pt-header">
    <!-- Logo kecil -->
    <img src="<?= rtrim(APP_URL,'/') ?>/assets/logo.svg"
         alt="Logo PMTC"
         style="width:28px;height:28px;object-fit:contain;filter:brightness(0) invert(1);flex-shrink:0;"
         onerror="this.style.display='none'">
    <div class="pt-header__avatar">
        <?= strtoupper(substr($tutor['nama_lengkap'] ?? 'T', 0, 1)) ?>
    </div>
    <div class="flex-grow-1 lh-1">
        <div class="fw-bold" style="font-size:.9rem;"><?= e($tutor['nama_lengkap'] ?? 'Tutor') ?></div>
        <div style="font-size:.72rem; opacity:.7;"><?= e($tutor['spesialisasi'] ?? 'Tutor Perkasa') ?></div>
    </div>
    <a href="logout.php" class="text-white text-decoration-none" style="opacity:.7;" title="Keluar">
        <i class="bi bi-power"></i>
    </a>
</div>

<!-- Flash -->
<div class="px-3 pt-2">
    <?php require __DIR__ . '/_partials/flash.php'; ?>
</div>

<!-- Page Content -->
<div class="pt-content">
    <?php if ($view_file && is_file($view_file)): ?>
        <?php extract($view_data); include $view_file; ?>
    <?php elseif (isset($all_tutors)): ?>
    <!-- Admin preview: pilih tutor -->
    <div class="pk-mobile-card">
        <h6 class="fw-bold mb-3">Preview Portal Tutor</h6>
        <p class="text-muted small">Pilih tutor untuk pratinjau portal:</p>
        <div class="d-flex flex-column gap-2">
            <?php foreach ($all_tutors as $t): ?>
            <a href="?tutor_id=<?= (int)$t['id'] ?>" class="btn btn-outline-primary btn-sm text-start">
                <?= e($t['nama_lengkap']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Bottom Navigation -->
<nav class="pt-bottom-nav">
    <?php
    $nav_items = [
        ['page'=>'dashboard',  'icon'=>'bi-grid-1x2-fill',      'label'=>'Dashboard'],
        ['page'=>'jadwal',     'icon'=>'bi-calendar-event-fill', 'label'=>'Jadwal'],
        ['page'=>'attendance', 'icon'=>'bi-check2-square',       'label'=>'Absensi'],
        ['page'=>'nilai',      'icon'=>'bi-bar-chart-fill',      'label'=>'Nilai'],
        ['page'=>'gaji',       'icon'=>'bi-cash-coin',           'label'=>'Gaji'],
    ];
    foreach ($nav_items as $nav):
        $active = ($page === $nav['page']) ? 'active' : '';
    ?>
    <a href="portal-tutor.php?page=<?= $nav['page'] ?><?= isset($_GET['tutor_id'])?'&tutor_id='.(int)$_GET['tutor_id']:'' ?>"
       class="pt-nav-item <?= $active ?>">
        <i class="bi <?= $nav['icon'] ?>"></i>
        <span><?= $nav['label'] ?></span>
    </a>
    <?php endforeach; ?>
</nav>

<div id="pkToastContainer" class="position-fixed bottom-0 end-0 p-3" style="z-index:9999; padding-bottom:90px !important;"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
function pkToast(msg, type='success', d=4500) {
    const id='t'+Date.now();
    const el=document.createElement('div');
    el.className=`toast align-items-center text-bg-${type} border-0 mb-2 show`;
    el.innerHTML=`<div class="d-flex"><div class="toast-body">${msg}</div><button class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.toast').remove()"></button></div>`;
    document.getElementById('pkToastContainer').appendChild(el);
    setTimeout(()=>el.remove(),d);
}
</script>
</body>
</html>

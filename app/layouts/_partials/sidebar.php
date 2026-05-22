<?php
/**
 * app/layouts/_partials/sidebar.php
 * Sidebar navigasi — collapsible di mobile via Alpine.js
 * Variabel yang tersedia: $page (string, nama module aktif)
 */
$current_page = $page ?? get('page', 'dashboard');

$nav_items = [
    ['page' => 'dashboard', 'icon' => 'bi-grid-1x2-fill',      'label' => 'Dashboard'],
    ['page' => 'siswa',     'icon' => 'bi-people-fill',         'label' => 'Data Keanggotaan'],
    ['page' => 'program',   'icon' => 'bi-collection-fill',     'label' => 'Manajemen Program'],
    ['page' => 'keuangan',  'icon' => 'bi-wallet2',             'label' => 'Buku Kas & Arus'],
    ['page' => 'jadwal',    'icon' => 'bi-calendar-event-fill', 'label' => 'Jadwal & Tutor'],
    ['page' => 'nilai',      'icon' => 'bi-bar-chart-fill',      'label' => 'Rekap Nilai'],
    ['page' => 'tutor',      'icon' => 'bi-person-video3',        'label' => 'Manajemen Tutor'],
    ['page' => 'pembayaran', 'icon' => 'bi-cash-coin',            'label' => 'Pembayaran SPP'],
    ['page' => 'attendance', 'icon' => 'bi-calendar-check-fill',  'label' => 'Absensi Siswa'],
    ['page' => 'laporan',    'icon' => 'bi-file-earmark-arrow-down-fill', 'label' => 'Laporan & Ekspor'],
    ['page' => 'pendaftaran','icon' => 'bi-person-plus-fill',             'label' => 'Antrian Pendaftaran'],
    ['page' => 'settings',   'icon' => 'bi-gear-fill',                   'label' => 'Pengaturan Akun'],
];
?>
<!-- Overlay untuk mobile — tampil saat sidebar terbuka di layar kecil -->
<div class="position-fixed top-0 start-0 w-100 h-100 d-lg-none"
     x-show="sidebarOpen"
     @click="sidebarOpen = false"
     x-cloak
     style="background:rgba(0,0,0,.45);z-index:1039;backdrop-filter:blur(2px);"></div>

<aside class="pk-sidebar"
       :class="{ 'pk-sidebar--open': sidebarOpen, 'pk-sidebar--closed': !sidebarOpen }"
       style="z-index:1040;">

    <!-- ── Brand ── -->
    <div class="pk-sidebar__brand d-flex align-items-center justify-content-between p-3 border-bottom border-white border-opacity-10">
        <div class="d-flex align-items-center gap-2 overflow-hidden flex-grow-1" style="min-width:0;">
            <!-- Logo — selalu tampil -->
            <div style="width:32px;height:32px;flex-shrink:0;">
                <img src="<?= rtrim(APP_URL,'/') ?>/assets/logo.svg"
                     alt="Logo"
                     style="width:100%;height:100%;object-fit:contain;filter:brightness(0) invert(1);"
                     onerror="this.outerHTML='<span style=\'font-size:1.3rem;\'>🏋️</span>'">
            </div>
            <!-- Teks brand — hanya saat sidebar terbuka -->
            <div class="overflow-hidden" style="white-space:nowrap;" x-show="sidebarOpen" x-cloak>
                <div class="fw-bold text-white lh-1" style="font-size:.88rem;letter-spacing:.04em;">PERKASA MULIA</div>
                <div class="text-white opacity-50" style="font-size:.6rem;letter-spacing:.06em;">TRAINING CENTER</div>
            </div>
        </div>
        <!-- Toggle desktop -->
        <button @click="toggleSidebar()"
                class="btn btn-link text-white opacity-50 p-0 d-none d-lg-flex flex-shrink-0"
                title="Perkecil/Perbesar sidebar">
            <i class="bi" :class="sidebarOpen ? 'bi-layout-sidebar-reverse' : 'bi-layout-sidebar'"></i>
        </button>
        <!-- Tutup di mobile -->
        <button @click="sidebarOpen = false"
                class="btn btn-link text-white opacity-50 p-0 d-lg-none flex-shrink-0"
                title="Tutup menu">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- ── Navigation ── -->
    <nav class="pk-sidebar__nav flex-grow-1 py-2">
        <?php foreach ($nav_items as $item):
            $is_active = ($current_page === $item['page']);
        ?>
        <a href="index.php?page=<?= $item['page'] ?>"
           class="pk-sidebar__link <?= $is_active ? 'pk-sidebar__link--active' : '' ?>"
           title="<?= e($item['label']) ?>">
            <i class="bi <?= $item['icon'] ?> pk-sidebar__icon"></i>
            <span class="pk-sidebar__label" x-show="sidebarOpen" x-cloak><?= e($item['label']) ?></span>
            <?php if ($is_active): ?>
            <span class="pk-sidebar__active-dot" x-show="!sidebarOpen" x-cloak></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>

        <?php if (auth_is(['owner'])): ?>
        <div class="pk-sidebar__divider" x-show="sidebarOpen" x-cloak></div>
        <div class="pk-sidebar__section-label px-3 mb-1" x-show="sidebarOpen" x-cloak>
            <small class="text-white opacity-30 text-uppercase fw-bold" style="font-size:.6rem;">Owner</small>
        </div>
        <a href="index.php?page=laporan"
           class="pk-sidebar__link <?= $current_page==='laporan'?'pk-sidebar__link--active':'' ?>"
           title="Laporan">
            <i class="bi bi-file-earmark-bar-graph-fill pk-sidebar__icon"></i>
            <span class="pk-sidebar__label" x-show="sidebarOpen" x-cloak>Laporan</span>
        </a>
        <?php endif; ?>
    </nav>

    <!-- ── User Profile ── -->
    <div class="pk-sidebar__user border-top border-white border-opacity-10 p-3">
        <!-- Avatar selalu tampil; nama/opsi tersembunyi via CSS saat collapsed -->
        <div class="d-flex align-items-center gap-2">
            <div class="pk-sidebar__avatar flex-shrink-0">
                <?= strtoupper(substr(auth_name(), 0, 1)) ?>
            </div>
            <div class="pk-sidebar__brandtext overflow-hidden flex-grow-1" style="min-width:0;" x-show="sidebarOpen" x-cloak>
                <div class="text-white fw-bold small text-truncate"><?= e(auth_name()) ?></div>
                <div class="text-white opacity-50" style="font-size:.7rem;"><?= ucfirst(auth_role()) ?></div>
            </div>
            <div class="dropdown pk-sidebar__brandtext flex-shrink-0" x-show="sidebarOpen" x-cloak>
                <button class="btn btn-link text-white opacity-50 p-0"
                        data-bs-toggle="dropdown" aria-expanded="false" title="Opsi akun">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li>
                        <a class="dropdown-item small" href="index.php?page=settings">
                            <i class="bi bi-person-circle me-2 text-muted"></i>Pengaturan Akun
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item small text-danger"
                           href="logout.php"
                           onclick="event.preventDefault(); pkConfirm('Yakin ingin keluar dari sistem?', () => window.location.href='logout.php', {title:'Keluar', btnLabel:'Ya, Keluar', btnClass:'btn-warning'});">
                            <i class="bi bi-power me-2"></i>Keluar
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</aside>

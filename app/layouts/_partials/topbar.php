<?php
/**
 * app/layouts/_partials/topbar.php
 * Topbar: hamburger toggle, breadcrumb kiri, search global + notif bell + user dropdown kanan
 */

// Ambil notifikasi yang belum dibaca (in-app)
$notif_count = 0;
$notif_list  = [];
try {
    $notif_count = (int)(db_value(
        "SELECT COUNT(*) FROM notifikasi
         WHERE (user_id = ? OR user_id IS NULL)
           AND is_read = 0 AND channel = 'in_app'",
        "i", [auth_id() ?? 0]
    ) ?? 0);

    $notif_list = db_fetch_all(
        "SELECT id, judul, pesan, link_url, created_at
         FROM notifikasi
         WHERE (user_id = ? OR user_id IS NULL)
           AND is_read = 0 AND channel = 'in_app'
         ORDER BY created_at DESC LIMIT 5",
        "i", [auth_id() ?? 0]
    );
} catch (Throwable $e) {
    // Tabel notifikasi mungkin belum ada (sebelum migration Phase 1)
}
?>
<header class="pk-topbar d-flex align-items-center gap-3">

    <!-- Hamburger toggle (kiri) -->
    <button @click="toggleSidebar()"
            class="btn btn-light btn-sm border-0 bg-transparent"
            title="Toggle sidebar">
        <i class="bi bi-list fs-5 text-muted"></i>
    </button>

    <!-- Page title (mobile) -->
    <div class="d-flex align-items-center gap-2 d-lg-none" style="min-width:0;flex:1;">
        <span class="fw-bold text-dark small text-truncate" style="max-width:180px;">
            <?= e($page_title) ?>
        </span>
    </div>

    <!-- ── Search Global ─────────────────────────────── -->
    <div class="pk-topbar__search d-none d-md-flex align-items-center">
        <form method="GET" action="index.php" class="position-relative">
            <input type="hidden" name="page" value="<?= e(get('page','siswa')) ?>">
            <i class="bi bi-search position-absolute top-50 translate-middle-y text-muted"
               style="left:10px; pointer-events:none;"></i>
            <input type="search"
                   id="pkGlobalSearch"
                   name="q"
                   value="<?= e(get('q')) ?>"
                   class="form-control form-control-sm pk-search-input"
                   placeholder="Cari siswa, transaksi… (tekan /)"
                   autocomplete="off"
                   style="padding-left:2rem; width:260px;">
        </form>
    </div>

    <!-- Spacer -->
    <div class="flex-grow-1"></div>

    <!-- ── Jam ── -->
    <span id="pkClock" class="text-muted d-none d-md-inline-block"
          style="font-size:.8rem; min-width:40px;">
        <?= date('H:i') ?>
    </span>

    <!-- ── Notifikasi Bell ──────────────────────────── -->
    <div class="dropdown">
        <button class="btn btn-light btn-sm border-0 bg-transparent position-relative"
                data-bs-toggle="dropdown"
                data-bs-auto-close="outside"
                aria-expanded="false"
                title="Notifikasi">
            <i class="bi bi-bell-fill text-muted fs-6"></i>
            <?php if ($notif_count > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                  style="font-size:.6rem; padding:2px 5px;">
                <?= $notif_count > 9 ? '9+' : $notif_count ?>
            </span>
            <?php endif; ?>
        </button>

        <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-0"
             style="width:320px; border-radius:12px; overflow:hidden;">
            <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center bg-light">
                <span class="fw-bold small">Notifikasi</span>
                <?php if ($notif_count > 0): ?>
                <a href="#" class="text-primary small text-decoration-none"
                   onclick="markAllRead(); return false;">Tandai semua dibaca</a>
                <?php endif; ?>
            </div>

            <div style="max-height:320px; overflow-y:auto;">
                <?php if (empty($notif_list)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-bell-slash fs-2 d-block mb-2 opacity-25"></i>
                    <small>Tidak ada notifikasi baru</small>
                </div>
                <?php else: foreach ($notif_list as $n): ?>
                <a href="<?= $n['link_url'] ? e($n['link_url']) : '#' ?>"
                   class="dropdown-item border-bottom py-2 px-3"
                   onclick="markRead(<?= (int)$n['id'] ?>)">
                    <div class="fw-bold small mb-0"><?= e($n['judul']) ?></div>
                    <div class="text-muted" style="font-size:.72rem; white-space:normal;">
                        <?= e(truncate($n['pesan'], 80)) ?>
                    </div>
                    <div class="text-muted mt-1" style="font-size:.68rem;">
                        <?= format_tanggal($n['created_at'], 'd M Y') ?>
                    </div>
                </a>
                <?php endforeach; endif; ?>
            </div>

            <div class="px-3 py-2 bg-light text-center border-top">
                <a href="index.php?page=notifikasi" class="text-primary small text-decoration-none">
                    Lihat semua notifikasi →
                </a>
            </div>
        </div>
    </div>

    <!-- ── User Dropdown ──────────────────────────────── -->
    <div class="dropdown">
        <button class="btn btn-sm border-0 bg-transparent d-flex align-items-center gap-2 p-1"
                data-bs-toggle="dropdown" aria-expanded="false">
            <div class="pk-topbar__avatar">
                <?= strtoupper(substr(auth_name(), 0, 1)) ?>
            </div>
            <div class="d-none d-lg-block text-start lh-1">
                <div class="fw-bold small text-dark"><?= e(auth_name()) ?></div>
                <div class="text-muted" style="font-size:.68rem;"><?= ucfirst(auth_role()) ?></div>
            </div>
            <i class="bi bi-chevron-down text-muted d-none d-lg-block" style="font-size:.7rem;"></i>
        </button>

        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 p-1" style="min-width:200px;">
            <li class="px-3 py-2 border-bottom mb-1">
                <div class="fw-bold small"><?= e(auth_name()) ?></div>
                <div class="text-muted" style="font-size:.72rem;"><?= e(auth_email()) ?></div>
            </li>
            <li>
                <a class="dropdown-item rounded-2 small py-2" href="index.php?page=profil">
                    <i class="bi bi-person-circle me-2 text-primary"></i>Lihat Profil
                </a>
            </li>
            <li>
                <a class="dropdown-item rounded-2 small py-2" href="index.php?page=ganti-password">
                    <i class="bi bi-key-fill me-2 text-warning"></i>Ganti Password
                </a>
            </li>
            <li>
                <button class="dropdown-item rounded-2 small py-2" onclick="toggleDark()">
                    <i class="bi bi-moon-fill me-2 text-secondary"></i>
                    <span id="darkToggleLabel">Mode Gelap</span>
                </button>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <li>
                <a class="dropdown-item rounded-2 small py-2 text-danger"
                   href="logout.php"
                   onclick="event.preventDefault(); pkConfirm('Yakin ingin keluar dari sistem?', () => window.location.href='logout.php', {title:'Keluar', btnLabel:'Ya, Keluar', btnClass:'btn-warning'});">
                    <i class="bi bi-power me-2"></i>Keluar
                </a>
            </li>
        </ul>
    </div>
</header>

<script>
function markRead(id) {
    fetch('api/notif-read.php?id=' + id, { method: 'POST' }).catch(() => {});
}
function markAllRead() {
    fetch('api/notif-read.php?all=1', { method: 'POST' })
        .then(() => location.reload())
        .catch(() => {});
}
function toggleDark() {
    // Delegate ke Alpine pkApp
    const el = document.querySelector('[x-data]');
    if (el && el._x_dataStack) {
        const app = el._x_dataStack[0];
        if (app.toggleDark) app.toggleDark();
    }
    const lbl = document.getElementById('darkToggleLabel');
    if (lbl) lbl.textContent = lbl.textContent === 'Mode Gelap' ? 'Mode Terang' : 'Mode Gelap';
}
</script>

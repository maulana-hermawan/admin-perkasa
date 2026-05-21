<?php
/**
 * app/layouts/admin.php
 * Master layout admin — di-require oleh front controller.
 * Semua data tersedia via $view_data array dari controller.
 */
if (!isset($view_data) || !is_array($view_data)) {
    die('Layout error: $view_data tidak tersedia.');
}

$page_title = $view_data['title'] ?? 'Admin';
$view_file  = $view_data['view'] ?? null;

// Breadcrumb: array of ['label'=>..., 'url'=>...], item terakhir tanpa url
$breadcrumbs = $view_data['breadcrumbs'] ?? [];
?>
<!DOCTYPE html>
<html lang="id" x-data="pkApp()" :class="{ 'dark': darkMode }" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= e($page_title) ?> | Perkasa Mulia TC</title>
    <meta name="theme-color" content="#001233">
    <meta name="application-name" content="Perkasa Mulia Training Center">

    <!-- Favicon dari logo.svg -->
    <link rel="icon" type="image/svg+xml" href="<?= rtrim(APP_URL,'/') ?>/assets/logo.svg">
    <link rel="apple-touch-icon" href="<?= rtrim(APP_URL,'/') ?>/assets/logo.svg">

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Tom Select (search-select dropdown) -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <!-- App CSS (design tokens) — path pakai APP_URL agar jalan di subfolder -->
    <link href="<?= rtrim(APP_URL, '/') ?>/assets/css/app.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <!-- Tom Select JS -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js" defer></script>
</head>
<body class="bg-pk-page" style="min-height:100vh;">

<div class="app-shell d-flex" style="min-height:100vh;">

    <!-- ── SIDEBAR ─────────────────────────────────────── -->
    <?php require __DIR__ . '/_partials/sidebar.php'; ?>

    <!-- ── MAIN CONTENT ────────────────────────────────── -->
    <div class="pk-main flex-grow-1 d-flex flex-column" :class="{ 'sidebar-collapsed': !sidebarOpen }">

        <!-- Topbar -->
        <?php require __DIR__ . '/_partials/topbar.php'; ?>

        <!-- Flash Messages -->
        <div class="pk-flash-wrapper px-3 pt-3">
            <?php require __DIR__ . '/_partials/flash.php'; ?>
        </div>

        <!-- Page Content -->
        <main class="pk-content flex-grow-1 p-3 p-md-4">

            <!-- Breadcrumb -->
            <?php if (!empty($breadcrumbs)): ?>
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb breadcrumb-sm mb-0">
                    <li class="breadcrumb-item">
                        <a href="index.php?page=dashboard" class="text-decoration-none">
                            <i class="bi bi-house-fill"></i>
                        </a>
                    </li>
                    <?php foreach ($breadcrumbs as $idx => $bc):
                        $is_last = ($idx === count($breadcrumbs) - 1);
                    ?>
                    <li class="breadcrumb-item <?= $is_last ? 'active' : '' ?>">
                        <?php if (!$is_last && !empty($bc['url'])): ?>
                            <a href="<?= e($bc['url']) ?>" class="text-decoration-none"><?= e($bc['label']) ?></a>
                        <?php else: ?>
                            <?= e($bc['label']) ?>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
            <?php endif; ?>

            <!-- View Content -->
            <?php
            if ($view_file && is_file($view_file)) {
                // Expose $view_data sebagai variables langsung untuk view
                extract($view_data, EXTR_SKIP);
                require $view_file;
            } elseif ($view_file === null) {
                echo '<div class="text-center py-5">
                    <i class="bi bi-map fs-1 text-muted d-block mb-3"></i>
                    <h4>404 — Halaman Tidak Ditemukan</h4>
                    <a href="index.php" class="btn btn-primary mt-3">← Kembali ke Dashboard</a>
                </div>';
            } else {
                echo '<div class="alert alert-danger">View file tidak ditemukan: ' . e($view_file) . '</div>';
            }
            ?>
        </main>

        <!-- Footer -->
        <footer class="text-center py-3 border-top d-flex align-items-center justify-content-center gap-2" style="font-size:.72rem;color:#aaa;">
            <img src="<?= rtrim(APP_URL,'/') ?>/assets/logo.svg"
                 alt="Logo" style="width:16px;height:16px;object-fit:contain;opacity:.5;"
                 onerror="this.style.display='none'">
            <span>Perkasa Mulia Training Center &copy; <?= date('Y') ?> — Sistem Manajemen v2.0</span>
        </footer>
    </div>
</div>

<!-- Global Confirm Modal -->
<div class="modal fade" id="pkConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-body text-center p-4">
                <div class="mb-3">
                    <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size:2.5rem;"></i>
                </div>
                <p class="fw-bold mb-1" id="pkConfirmTitle">Konfirmasi</p>
                <p class="text-muted small mb-0" id="pkConfirmMsg">Yakin ingin melanjutkan?</p>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0 pb-3 gap-2">
                <button class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Batal</button>
                <button id="pkConfirmBtn" class="btn btn-danger btn-sm px-4">Ya, Hapus</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="pkToastContainer" class="position-fixed bottom-0 end-0 p-3" style="z-index:9999; min-width:300px;"></div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ── Alpine.js Global App State ──────────────────────────────
function pkApp() {
    return {
        sidebarOpen: window.innerWidth >= 992,
        darkMode: localStorage.getItem('pk_dark') === '1',
        notifCount: 0,
        searchQuery: '',

        toggleSidebar() { this.sidebarOpen = !this.sidebarOpen; },
        toggleDark() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('pk_dark', this.darkMode ? '1' : '0');
        },

        init() {
            // Close sidebar on mobile when clicking outside
            this.$watch('sidebarOpen', val => {
                if (window.innerWidth < 992) {
                    document.body.style.overflow = val ? 'hidden' : '';
                }
            });
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 992 && !this.sidebarOpen) {
                    this.sidebarOpen = true;
                }
            });
        }
    };
}

// ── Toast Helper ──────────────────────────────────────────────
function pkToast(msg, type = 'success', duration = 4500) {
    const icons = {
        success: 'check-circle-fill',
        danger:  'exclamation-triangle-fill',
        warning: 'exclamation-circle-fill',
        info:    'info-circle-fill',
    };
    const id  = 'toast_' + Date.now();
    const el  = document.createElement('div');
    el.id     = id;
    el.className = `toast align-items-center text-bg-${type} border-0 mb-2 show`;
    el.setAttribute('role', 'alert');
    el.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-${icons[type]||icons.info} me-2"></i>${msg}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto"
                    onclick="this.closest('.toast').remove()"></button>
        </div>`;
    document.getElementById('pkToastContainer').appendChild(el);
    setTimeout(() => el.remove(), duration);
}

// ── Confirm Modal Helper ──────────────────────────────────────
function pkConfirm(message, onConfirm, { title = 'Konfirmasi Hapus', btnLabel = 'Ya, Hapus', btnClass = 'btn-danger' } = {}) {
    const modal = document.getElementById('pkConfirmModal');
    document.getElementById('pkConfirmTitle').textContent = title;
    document.getElementById('pkConfirmMsg').textContent   = message;
    const btn = document.getElementById('pkConfirmBtn');
    btn.className   = 'btn btn-sm px-4 ' + btnClass;
    btn.textContent = btnLabel;
    const m = new bootstrap.Modal(modal);
    btn.onclick = () => { m.hide(); onConfirm(); };
    m.show();
}

// ── Keyboard Shortcuts ────────────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
    if (e.key === '/' && !e.ctrlKey) {
        e.preventDefault();
        const s = document.getElementById('pkGlobalSearch');
        if (s) s.focus();
    }
    if (e.key === 'n' && !e.ctrlKey && !e.metaKey && !e.shiftKey) {
        e.preventDefault();
        // Coba klik tombol "Tambah" pertama yang ditemukan
        const btnNew = document.getElementById('btnNewEntry')
            || document.querySelector('a[href*="action=create"]')
            || document.querySelector('.btn-success[href*="create"]');
        if (btnNew) btnNew.click();
        // Broadcast event ke module jika ada handler kustom
        document.dispatchEvent(new CustomEvent('pkNewEntry'));
    }
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.show').forEach(m =>
            bootstrap.Modal.getInstance(m)?.hide()
        );
    }
});

// ── Form Validation Global ─────────────────────────────────────
/**
 * pkValidateForm(formEl) — Validasi + scroll ke field error pertama
 * Gunakan di form: form.addEventListener('submit', pkFormSubmitHandler)
 * atau panggil manual: pkValidateForm(form)
 */
function pkValidateForm(form) {
    form.classList.remove('was-validated');
    const invalids = form.querySelectorAll(':invalid');
    if (invalids.length === 0) return true;
    form.classList.add('was-validated');
    const first = invalids[0];
    first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(() => first.focus(), 300);
    return false;
}

// Auto-apply ke semua form dengan class pk-validate atau data-validate
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form.pk-validate, form[data-validate]').forEach(form => {
        form.setAttribute('novalidate', '');
        form.addEventListener('submit', function(e) {
            if (!pkValidateForm(this)) {
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            // Disable submit button
            const btn = this.querySelector('[type="submit"]');
            if (btn) {
                btn.disabled = true;
                const orig = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
                // Re-enable after 8 detik sebagai fallback
                setTimeout(() => { btn.disabled = false; btn.innerHTML = orig; }, 8000);
            }
        });
    });

    // Auto-init Tom Select untuk select dengan class ts-search
    if (typeof TomSelect !== 'undefined') {
        document.querySelectorAll('select.ts-search').forEach(el => {
            if (el.tomselect) return; // sudah diinit
            new TomSelect(el, {
                allowEmptyOption: true,
                maxOptions: 200,
                searchField: ['text'],
                render: {
                    no_results: () => '<div class="no-results px-3 py-2 text-muted small">Siswa tidak ditemukan</div>'
                }
            });
        });
    }
});

// ── Live Clock ────────────────────────────────────────────────
setInterval(() => {
    const el = document.getElementById('pkClock');
    if (el) el.textContent = new Date().toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
}, 1000);
</script>
</body>
</html>
<?php
/**
 * app/layouts/_partials/flash.php
 * Render flash messages dari session sebagai Bootstrap alerts.
 * Auto-dismiss setelah 6 detik via JS.
 */
$flash_messages = flash_get();
if (empty($flash_messages)) return;

$icons = [
    'success' => 'check-circle-fill',
    'danger'  => 'exclamation-triangle-fill',
    'warning' => 'exclamation-circle-fill',
    'info'    => 'info-circle-fill',
];
?>
<div id="pkFlashContainer">
    <?php foreach ($flash_messages as $idx => $m):
        $type = in_array($m['type'], ['success','danger','warning','info']) ? $m['type'] : 'info';
        $icon = $icons[$type] ?? 'info-circle-fill';
        $id   = 'flash_' . $idx;
    ?>
    <div id="<?= $id ?>"
         class="alert alert-<?= $type ?> alert-dismissible fade show d-flex align-items-start gap-2 shadow-sm rounded-3 mb-2"
         role="alert">
        <i class="bi bi-<?= $icon ?> flex-shrink-0 mt-1"></i>
        <div class="flex-grow-1 small"><?= $m['msg'] /* HTML diizinkan — konten dari server, bukan input user */ ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
    <script>
        setTimeout(() => {
            const el = document.getElementById('<?= $id ?>');
            if (el) { el.classList.remove('show'); setTimeout(() => el.remove(), 300); }
        }, 6000);
    </script>
    <?php endforeach; ?>
</div>

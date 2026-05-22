<?php
/**
 * app/modules/settings/audit_log.view.php
 * Viewer riwayat aktivitas (audit_log)
 */
$total_pages = (int)ceil($total / $per_page);

// Label warna per kategori action
function audit_badge_class(string $action): string {
    if (str_starts_with($action, 'CREATE') || $action === 'TERIMA_PENDAFTAR')  return 'bg-success';
    if (str_starts_with($action, 'UPDATE') || str_starts_with($action, 'CHANGE')) return 'bg-primary';
    if (str_starts_with($action, 'DELETE'))      return 'bg-danger';
    if ($action === 'LOGIN')                      return 'bg-info text-dark';
    if ($action === 'LOGIN_FAILED')               return 'bg-warning text-dark';
    if (str_starts_with($action, 'BULK'))         return 'bg-secondary';
    if (str_starts_with($action, 'WEBHOOK'))      return 'bg-warning text-dark';
    return 'bg-secondary';
}
?>
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-muted"></i>Audit Log</h4>
        <div class="text-muted small mt-1">Riwayat semua aktivitas sistem — <?= number_format($total) ?> entri total</div>
    </div>
    <a href="index.php?page=settings" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<!-- Filter -->
<form method="GET" action="index.php" class="pk-card p-3 mb-4">
    <input type="hidden" name="page"   value="settings">
    <input type="hidden" name="action" value="audit_log">
    <div class="row g-2 align-items-end">
        <div class="col-sm-3">
            <label class="form-label form-label-sm text-muted fw-bold mb-1">Cari Aksi</label>
            <input type="text" name="f_action" class="form-control form-control-sm"
                   placeholder="CREATE, UPDATE, DELETE..." value="<?= e($f_action) ?>">
        </div>
        <div class="col-sm-3">
            <label class="form-label form-label-sm text-muted fw-bold mb-1">Tabel</label>
            <select name="f_table" class="form-select form-select-sm">
                <option value="">— Semua Tabel —</option>
                <?php foreach ($dist_tables as $dt): ?>
                <option value="<?= e($dt['target_table']) ?>" <?= $f_table===$dt['target_table']?'selected':'' ?>>
                    <?= e($dt['target_table']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-2">
            <label class="form-label form-label-sm text-muted fw-bold mb-1">Tanggal</label>
            <input type="date" name="f_date" class="form-control form-control-sm"
                   value="<?= e($f_date) ?>">
        </div>
        <div class="col-sm-2">
            <label class="form-label form-label-sm text-muted fw-bold mb-1">User</label>
            <input type="text" name="f_user" class="form-control form-control-sm"
                   placeholder="Email/nama..." value="<?= e($f_user) ?>">
        </div>
        <div class="col-sm-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                <i class="bi bi-funnel-fill me-1"></i>Filter
            </button>
            <?php if ($f_action || $f_table || $f_date || $f_user): ?>
            <a href="index.php?page=settings&action=audit_log" class="btn btn-outline-secondary btn-sm" title="Reset">
                <i class="bi bi-x-lg"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- Tabel -->
<div class="pk-card overflow-hidden">
    <?php if (empty($logs)): ?>
    <div class="pk-empty-state py-5">
        <i class="bi bi-clock-history"></i>
        <div class="fw-bold">Tidak ada data</div>
        <div class="small text-muted mt-1">Coba ubah filter atau tunggu hingga ada aktivitas.</div>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table pk-table mb-0" style="font-size:.8rem;">
            <thead>
                <tr>
                    <th style="width:160px;">Waktu</th>
                    <th style="width:160px;">Pengguna</th>
                    <th style="width:200px;">Aksi</th>
                    <th>Tabel / ID</th>
                    <th style="width:110px;">IP</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="text-muted" style="white-space:nowrap;">
                        <?= format_tanggal($log['created_at'], 'd M Y H:i:s') ?>
                    </td>
                    <td>
                        <?php if ($log['nama_display']): ?>
                        <div class="fw-bold"><?= e($log['nama_display']) ?></div>
                        <div class="text-muted" style="font-size:.72rem;"><?= e($log['email'] ?? '') ?></div>
                        <?php else: ?>
                        <span class="text-muted fst-italic small">System / API</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= audit_badge_class($log['action']) ?>"
                              style="font-size:.68rem;letter-spacing:.02em;">
                            <?= e($log['action']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border me-1" style="font-size:.68rem;">
                            <?= e($log['target_table'] ?? '—') ?>
                        </span>
                        <?php if ($log['target_id']): ?>
                        <span class="text-muted">#<?= (int)$log['target_id'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted" style="font-size:.72rem;"><?= e($log['ip_address'] ?? '—') ?></td>
                    <td class="text-center">
                        <?php if ($log['old_values'] || $log['new_values']): ?>
                        <button type="button" class="btn btn-xs btn-outline-secondary"
                                onclick="showLogDetail(<?= htmlspecialchars(json_encode([
                                    'action' => $log['action'],
                                    'table'  => $log['target_table'],
                                    'id'     => $log['target_id'],
                                    'old'    => $log['old_values'] ? json_decode($log['old_values'], true) : null,
                                    'new'    => $log['new_values'] ? json_decode($log['new_values'], true) : null,
                                ]), ENT_QUOTES) ?>)">
                            <i class="bi bi-eye"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="px-3 py-2 border-top d-flex align-items-center justify-content-between">
        <small class="text-muted">
            Menampilkan <?= number_format(($p-1)*$per_page+1) ?>–<?= number_format(min($p*$per_page,$total)) ?>
            dari <?= number_format($total) ?> entri
        </small>
        <nav>
            <ul class="pagination pagination-sm mb-0 gap-1">
                <?php
                $base = "index.php?page=settings&action=audit_log&f_action=".urlencode($f_action)."&f_table=".urlencode($f_table)."&f_date=".urlencode($f_date)."&f_user=".urlencode($f_user)."&p=";
                for ($pg = max(1,$p-2); $pg <= min($total_pages,$p+2); $pg++): ?>
                <li class="page-item <?= $pg===$p?'active':'' ?>">
                    <a class="page-link" href="<?= $base.$pg ?>"><?= $pg ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal detail perubahan data -->
<div class="modal fade" id="logDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header">
                <h6 class="modal-title fw-bold" id="logDetailTitle">Detail Perubahan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0" id="logDetailBody"></div>
            </div>
        </div>
    </div>
</div>

<script>
function showLogDetail(data) {
    document.getElementById('logDetailTitle').textContent =
        data.action + ' — ' + data.table + (data.id ? ' #' + data.id : '');

    const renderCol = (title, obj, colorClass) => {
        if (!obj) return '';
        const rows = Object.entries(obj).map(([k, v]) =>
            `<tr><td class="text-muted small" style="width:40%;word-break:break-all;padding:4px 8px;">${k}</td>
             <td class="small" style="word-break:break-all;padding:4px 8px;">${v === null ? '<em class="text-muted">null</em>' : String(v).substring(0,200)}</td></tr>`
        ).join('');
        return `<div class="col-md-6 border-end">
            <div class="px-3 py-2 border-bottom fw-bold small ${colorClass} bg-opacity-10">${title}</div>
            <table class="table table-sm mb-0">${rows}</table>
        </div>`;
    };

    const oldHtml = renderCol('Sebelum', data.old, 'text-danger');
    const newHtml = renderCol('Sesudah', data.new, 'text-success');

    document.getElementById('logDetailBody').innerHTML =
        (oldHtml || newHtml)
            ? (oldHtml + newHtml)
            : '<div class="p-4 text-muted text-center w-100">Tidak ada data detail.</div>';

    new bootstrap.Modal(document.getElementById('logDetailModal')).show();
}
</script>

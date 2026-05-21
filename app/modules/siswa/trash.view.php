<?php /** app/modules/siswa/trash.view.php */ ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-trash2-fill text-danger me-2"></i>Trash — Siswa Terhapus</h4>
        <small class="text-muted">Data terhapus dalam 30 hari terakhir. Bisa dipulihkan sebelum otomatis dihapus permanen.</small>
    </div>
    <a href="index.php?page=siswa" class="btn btn-outline-secondary btn-sm">← Kembali</a>
</div>

<div class="pk-card overflow-hidden">
    <div class="table-responsive">
        <table class="table pk-table pk-table-mobile align-middle mb-0 small">
            <thead class="table-dark">
                <tr>
                    <th class="ps-3">Siswa</th>
                    <th class="d-none d-md-table-cell">Program Terakhir</th>
                    <th>Dihapus</th>
                    <th class="text-center pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($trash_list)): ?>
                <tr><td colspan="4">
                    <div class="pk-empty-state">
                        <i class="bi bi-trash2"></i>
                        <small>Trash kosong — tidak ada siswa yang dihapus dalam 30 hari terakhir.</small>
                    </div>
                </td></tr>
                <?php else: foreach ($trash_list as $s):
                    $deleted  = new DateTime($s['deleted_at']);
                    $deadline = clone $deleted; $deadline->modify('+30 days');
                    $sisa     = (int)ceil(($deadline->getTimestamp() - time()) / 86400);
                ?>
                <tr>
                    <td class="ps-3" data-label="Siswa">
                        <div class="fw-bold"><?= e($s['nama_lengkap']) ?></div>
                        <div class="text-muted" style="font-size:.72rem;"><?= e($s['nomor_induk']??'—') ?> · <?= e($s['email']??'') ?></div>
                    </td>
                    <td class="d-none d-md-table-cell text-muted" data-label="Program">—</td>
                    <td data-label="Dihapus">
                        <div class="small"><?= e(date('d M Y H:i', strtotime($s['deleted_at']))) ?></div>
                        <div class="<?= $sisa <= 5 ? 'text-danger' : 'text-muted' ?>" style="font-size:.68rem;">
                            <?= $sisa > 0 ? "Dihapus permanen dalam $sisa hari" : 'Segera dihapus permanen' ?>
                        </div>
                    </td>
                    <td class="text-center pe-3" data-label="">
                        <a href="index.php?page=siswa&action=restore&id=<?= (int)$s['id'] ?>&_csrf_token=<?= e(csrf_token()) ?>"
                           class="btn btn-sm btn-outline-success"
                           onclick="return confirm('Pulihkan siswa <?= e(addslashes($s['nama_lengkap'])) ?>?')">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                        </a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($trash_list)): ?>
<div class="alert alert-warning mt-3 small">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Data di trash akan <strong>dihapus permanen otomatis setelah 30 hari</strong> sejak tanggal penghapusan.
    Pulihkan sebelum batas waktu.
</div>
<?php endif; ?>

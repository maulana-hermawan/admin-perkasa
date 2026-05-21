<?php /** app/modules/tutor/list.view.php */ ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Manajemen Tutor</h4>
        <small class="text-muted">Kelola data tutor, spesialisasi, dan informasi penggajian.</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="index.php?page=tutor&action=rekap_tutor" class="btn btn-outline-primary">
            <i class="bi bi-person-video3 me-1"></i>Rekap Tutor
        </a>
        <a href="index.php?page=tutor&action=rekap_gaji" class="btn btn-outline-danger">
            <i class="bi bi-cash-stack me-1"></i>Rekap Gaji
        </a>
        <a href="index.php?page=tutor&action=create" class="btn btn-success" id="btnNewEntry">
            <i class="bi bi-plus-circle me-1"></i>Tambah Tutor
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="pk-card pk-stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="text-muted fw-bold" style="font-size:.68rem;">TOTAL TUTOR</div>
                <div class="fw-bold" style="font-size:1.5rem;"><?= count($daftar_tutor) ?></div>
            </div>
            <div class="pk-stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-person-badge-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pk-card pk-stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="text-muted fw-bold" style="font-size:.68rem;">AKTIF</div>
                <div class="fw-bold text-success" style="font-size:1.5rem;"><?= $stat_aktif ?></div>
            </div>
            <div class="pk-stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pk-card pk-stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="text-muted fw-bold" style="font-size:.68rem;">NON-AKTIF</div>
                <div class="fw-bold text-secondary" style="font-size:1.5rem;"><?= $stat_non ?></div>
            </div>
            <div class="pk-stat-icon bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-pause-circle-fill"></i></div>
        </div>
    </div>
</div>

<!-- Filter & Search -->
<div class="pk-card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="tutor">
        <div class="col-md-5">
            <label class="form-label small fw-bold mb-1">Cari Tutor</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="q" class="form-control" value="<?= e($q_search) ?>"
                       placeholder="Nama, spesialisasi, email...">
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Semua Status</option>
                <option value="1" <?= $f_status==='1'?'selected':'' ?>>Aktif</option>
                <option value="0" <?= $f_status==='0'?'selected':'' ?>>Non-Aktif</option>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
            <a href="?page=tutor" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
        </div>
    </form>
</div>

<!-- Tabel Tutor -->
<div class="pk-card overflow-hidden">
    <div class="table-responsive">
        <table class="table pk-table pk-table-mobile align-middle mb-0 small">
            <thead class="table-dark">
                <tr>
                    <th class="ps-3" style="width:44px;">#</th>
                    <th>Nama Tutor</th>
                    <th class="d-none d-md-table-cell">Kontak</th>
                    <th class="d-none d-lg-table-cell">Spesialisasi</th>
                    <th class="d-none d-lg-table-cell">Tarif/Sesi</th>
                    <th>Status</th>
                    <th class="text-center pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar_tutor)): ?>
                <tr>
                    <td colspan="7">
                        <div class="pk-empty-state">
                            <i class="bi bi-person-x"></i>
                            <small>Belum ada data tutor<?= $q_search ? ' yang cocok dengan pencarian' : '' ?>.</small>
                            <?php if (!$q_search): ?>
                            <a href="index.php?page=tutor&action=create" class="btn btn-success btn-sm mt-2">
                                <i class="bi bi-plus-circle me-1"></i> Tambah Tutor Pertama
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php else: foreach ($daftar_tutor as $idx => $t): ?>
                <tr>
                    <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                    <td data-label="Tutor">
                        <div class="d-flex align-items-center gap-2">
                            <div class="pk-topbar__avatar" style="width:32px;height:32px;font-size:.8rem;flex-shrink:0;">
                                <?= strtoupper(substr($t['nama_lengkap'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-bold"><?= e($t['nama_lengkap']) ?></div>
                                <div class="text-muted" style="font-size:.72rem;"><?= e($t['email'] ?? '—') ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="d-none d-md-table-cell" data-label="Kontak">
                        <?php if ($t['nomor_wa']): ?>
                        <a href="https://wa.me/62<?= ltrim($t['nomor_wa'], '0') ?>"
                           target="_blank" class="text-success text-decoration-none small">
                            <i class="bi bi-whatsapp me-1"></i><?= e($t['nomor_wa']) ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="d-none d-lg-table-cell" data-label="Spesialisasi">
                        <?php if ($t['spesialisasi']): ?>
                        <span class="badge bg-light text-dark border"><?= e($t['spesialisasi']) ?></span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="d-none d-lg-table-cell" data-label="Tarif/Sesi">
                        <?php if (!empty($t['tarif_per_sesi']) && $t['tarif_per_sesi'] > 0): ?>
                        <span class="fw-bold text-success"><?= format_rupiah((float)$t['tarif_per_sesi']) ?></span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Status">
                        <span class="badge <?= $t['status_aktif'] ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $t['status_aktif'] ? 'Aktif' : 'Non-Aktif' ?>
                        </span>
                    </td>
                    <td class="text-center pe-3" data-label="">
                        <a href="index.php?page=tutor&action=edit&id=<?= (int)$t['id'] ?>"
                           class="btn btn-sm btn-outline-primary" title="Edit">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                        <?php if ($t['nomor_wa']): ?>
                        <a href="https://wa.me/62<?= ltrim($t['nomor_wa'], '0') ?>?text=Halo+<?= urlencode($t['nama_lengkap']) ?>%2C+salam+dari+Perkasa+Mulia+Training+Center."
                           target="_blank" class="btn btn-sm btn-outline-success" title="WA">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-danger" title="Hapus"
                                onclick="pkConfirm(
                                    'Hapus tutor <?= e(addslashes($t['nama_lengkap'])) ?>? Akun login juga akan dinonaktifkan.',
                                    () => location.href='index.php?page=tutor&action=delete&id=<?= (int)$t['id'] ?>&_csrf_token=<?= e(csrf_token()) ?>',
                                    {title:'Hapus Tutor', btnLabel:'Ya, Hapus', btnClass:'btn-danger'}
                                )">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($daftar_tutor)): ?>
    <div class="px-3 py-2 border-top text-muted" style="font-size:.75rem;">
        Menampilkan <?= count($daftar_tutor) ?> tutor
        <?= $q_search ? ' · hasil pencarian "<strong>' . e($q_search) . '</strong>"' : '' ?>
    </div>
    <?php endif; ?>
</div>

<script>
// 'n' shortcut sudah handle di admin.php global, arahkan ke btnNewEntry
document.addEventListener('pkNewEntry', () => {
    window.location.href = 'index.php?page=tutor&action=create';
});
</script>

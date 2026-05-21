<?php /** app/modules/pendaftaran/list.view.php */ ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Antrian Pendaftaran Online</h4>
        <small class="text-muted">Verifikasi calon siswa dari form publik. Terima = buat akun siswa otomatis.</small>
    </div>
    <a href="daftar.php" target="_blank" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-box-arrow-up-right me-1"></i>Lihat Form Publik
    </a>
</div>

<!-- Status chips -->
<div class="d-flex flex-wrap gap-2 mb-3">
    <?php
    $status_opts = [''=>'Semua','Baru'=>'Baru','Diproses'=>'Diproses','Diterima'=>'Diterima','Ditolak'=>'Ditolak'];
    $badge_map = ['Baru'=>'primary','Diproses'=>'warning','Diterima'=>'success','Ditolak'=>'danger'];
    foreach ($status_opts as $sv => $sl):
        $cnt = $sv === '' ? array_sum($counts) : ($counts[$sv] ?? 0);
    ?>
    <a href="?page=pendaftaran&status=<?= e($sv) ?>"
       class="btn btn-sm <?= $f_status===$sv?'btn-'.($badge_map[$sv]??'secondary'):'btn-outline-secondary' ?>">
        <?= $sl ?> <span class="badge bg-white text-dark ms-1"><?= $cnt ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Search -->
<div class="pk-card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="pendaftaran">
        <input type="hidden" name="status" value="<?= e($f_status) ?>">
        <div class="col-md-4">
            <input type="text" name="q" class="form-control form-control-sm" value="<?= e($f_q) ?>"
                   placeholder="Cari nama atau nomor WA...">
        </div>
        <div class="col-auto">
            <button class="btn btn-primary btn-sm">Cari</button>
            <a href="?page=pendaftaran" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
        </div>
    </form>
</div>

<!-- List -->
<div class="pk-card overflow-hidden">
    <div class="table-responsive">
        <table class="table pk-table pk-table-mobile align-middle mb-0 small">
            <thead class="table-dark">
                <tr>
                    <th class="ps-3">Nama</th>
                    <th class="d-none d-md-table-cell">Kontak</th>
                    <th class="d-none d-md-table-cell">Target</th>
                    <th>Status</th>
                    <th class="d-none d-md-table-cell">Daftar</th>
                    <th class="text-center pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar)): ?>
                <tr><td colspan="6">
                    <div class="pk-empty-state"><i class="bi bi-inbox"></i>
                    <small>Tidak ada pendaftaran <?= $f_status ? "dengan status <strong>$f_status</strong>" : '' ?>.</small>
                    </div>
                </td></tr>
                <?php else: foreach ($daftar as $c):
                    $badge = match($c['status']) {
                        'Diterima' => 'bg-success', 'Ditolak' => 'bg-danger',
                        'Diproses' => 'bg-warning text-dark', default => 'bg-primary',
                    };
                ?>
                <tr>
                    <td class="ps-3" data-label="Nama">
                        <div class="fw-bold"><?= e($c['nama_lengkap']) ?></div>
                        <?php if($c['asal_sekolah']): ?><div class="text-muted" style="font-size:.7rem;"><?= e($c['asal_sekolah']) ?></div><?php endif; ?>
                    </td>
                    <td class="d-none d-md-table-cell" data-label="Kontak">
                        <a href="https://wa.me/62<?= ltrim(preg_replace('/\D/','',$c['nomor_wa']),'0') ?>"
                           target="_blank" class="text-success text-decoration-none small">
                            <i class="bi bi-whatsapp me-1"></i><?= e($c['nomor_wa']) ?>
                        </a>
                        <?php if($c['email']): ?><div class="text-muted" style="font-size:.68rem;"><?= e($c['email']) ?></div><?php endif; ?>
                    </td>
                    <td class="d-none d-md-table-cell" data-label="Target">
                        <?= $c['target_seleksi'] ? '<span class="badge bg-light text-dark border">'.e($c['target_seleksi']).'</span>' : '<span class="text-muted">—</span>' ?>
                    </td>
                    <td data-label="Status">
                        <span class="badge <?= $badge ?>"><?= e($c['status']) ?></span>
                    </td>
                    <td class="d-none d-md-table-cell text-muted" data-label="Daftar">
                        <?= e(date('d M Y', strtotime($c['created_at']))) ?>
                    </td>
                    <td class="text-center pe-3" data-label="">
                        <?php if ($c['status'] === 'Baru' || $c['status'] === 'Diproses'): ?>
                        <div class="d-flex gap-1 justify-content-center">
                            <!-- Proses -->
                            <form method="POST" action="index.php?page=pendaftaran&action=proses&id=<?= (int)$c['id'] ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-warning" title="Tandai Diproses">
                                    <i class="bi bi-hourglass-split"></i>
                                </button>
                            </form>
                            <!-- Terima -->
                            <button class="btn btn-sm btn-success"
                                    onclick="showTerimaModal(<?= (int)$c['id'] ?>, '<?= e(addslashes($c['nama_lengkap'])) ?>')"
                                    title="Terima & Buat Akun">
                                <i class="bi bi-check-lg"></i>
                            </button>
                            <!-- Tolak -->
                            <button class="btn btn-sm btn-outline-danger"
                                    onclick="showTolakModal(<?= (int)$c['id'] ?>, '<?= e(addslashes($c['nama_lengkap'])) ?>')"
                                    title="Tolak">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <?php elseif ($c['catatan_admin']): ?>
                        <span class="text-muted small" title="<?= e($c['catatan_admin']) ?>">
                            <i class="bi bi-chat-text me-1"></i>ada catatan
                        </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Terima -->
<div class="modal fade" id="terimaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold text-success"><i class="bi bi-check-circle-fill me-2"></i>Terima Pendaftaran</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="terimaForm">
                <?= csrf_field() ?>
                <div class="modal-body pt-0">
                    <p class="small text-muted mb-3">Terima <strong id="terimaNama"></strong>? Akun siswa akan dibuat otomatis dan login dikirim via WA.</p>
                    <label class="form-label fw-bold small">Catatan (opsional)</label>
                    <textarea name="catatan_admin" class="form-control" rows="2" placeholder="Catatan untuk siswa..."></textarea>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-4">Ya, Terima & Buat Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tolak -->
<div class="modal fade" id="tolakModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold text-danger"><i class="bi bi-x-circle-fill me-2"></i>Tolak Pendaftaran</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="tolakForm">
                <?= csrf_field() ?>
                <div class="modal-body pt-0">
                    <p class="small text-muted mb-3">Tolak pendaftaran <strong id="tolakNama"></strong>?</p>
                    <label class="form-label fw-bold small">Alasan penolakan (akan dikirim via WA)</label>
                    <textarea name="catatan_admin" class="form-control" rows="2" placeholder="mis: Kuota penuh, tidak memenuhi syarat usia..."></textarea>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm">Ya, Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showTerimaModal(id, nama) {
    document.getElementById('terimaNama').textContent = nama;
    document.getElementById('terimaForm').action = `index.php?page=pendaftaran&action=terima&id=${id}`;
    new bootstrap.Modal(document.getElementById('terimaModal')).show();
}
function showTolakModal(id, nama) {
    document.getElementById('tolakNama').textContent = nama;
    document.getElementById('tolakForm').action = `index.php?page=pendaftaran&action=tolak&id=${id}`;
    new bootstrap.Modal(document.getElementById('tolakModal')).show();
}
</script>

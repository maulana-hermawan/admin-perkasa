<?php /** app/modules/tutor/rekap_gaji.view.php */ ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Rekap Gaji Tutor</h4>
        <small class="text-muted">Hitung otomatis: jumlah sesi × tarif per sesi.</small>
    </div>
    <a href="index.php?page=tutor" class="btn btn-outline-secondary btn-sm">← Kembali</a>
</div>

<!-- Filter Bulan -->
<div class="pk-card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="tutor">
        <input type="hidden" name="action" value="rekap_gaji">
        <div class="col-md-3">
            <label class="form-label small fw-bold mb-1">Periode</label>
            <input type="month" name="bulan" class="form-control form-control-sm"
                   value="<?= e($gaji_bulan) ?>">
        </div>
        <div class="col-auto">
            <button class="btn btn-primary btn-sm"><i class="bi bi-calculator me-1"></i>Hitung</button>
        </div>
    </form>
    <div class="mt-2">
        <small class="text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Menghitung berdasarkan jadwal yang sudah berlangsung di bulan ini dan tarif masing-masing tutor.
        </small>
    </div>
</div>

<!-- Rekap -->
<?php if (!empty($rekap_gaji)): ?>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="pk-card pk-stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="text-muted fw-bold" style="font-size:.68rem;">TOTAL GAJI BULAN INI</div>
                <div class="fw-bold text-danger" style="font-size:1.2rem;"><?= format_rupiah($total_gaji) ?></div>
            </div>
            <div class="pk-stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-cash-stack"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="pk-card pk-stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="text-muted fw-bold" style="font-size:.68rem;">TOTAL SESI</div>
                <div class="fw-bold" style="font-size:1.5rem;"><?= array_sum(array_column($rekap_gaji,'jumlah_sesi')) ?></div>
            </div>
            <div class="pk-stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-calendar-check-fill"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="pk-card pk-stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="text-muted fw-bold" style="font-size:.68rem;">TUTOR AKTIF</div>
                <div class="fw-bold" style="font-size:1.5rem;"><?= count($rekap_gaji) ?></div>
            </div>
            <div class="pk-stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-person-badge-fill"></i></div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="pk-card overflow-hidden">
    <div class="table-responsive">
        <table class="table pk-table align-middle mb-0 small">
            <thead class="table-dark">
                <tr>
                    <th class="ps-3">Tutor</th>
                    <th class="text-center">Sesi</th>
                    <th class="text-end">Tarif/Sesi</th>
                    <th class="text-end">Total Gaji</th>
                    <th class="d-none d-md-table-cell">Bank</th>
                    <th class="text-center pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rekap_gaji)): ?>
                <tr><td colspan="6">
                    <div class="pk-empty-state"><i class="bi bi-calculator"></i>
                    <small>Belum ada data jadwal di <?= e(date('F Y',strtotime($gaji_bulan.'-01'))) ?>.</small>
                    </div>
                </td></tr>
                <?php else: foreach ($rekap_gaji as $r):
                    $total = (float)$r['jumlah_sesi'] * (float)($r['tarif_per_sesi'] ?? 0);
                ?>
                <tr>
                    <td class="ps-3">
                        <div class="fw-bold"><?= e($r['nama_lengkap']) ?></div>
                        <div class="text-muted" style="font-size:.7rem;"><?= e($r['spesialisasi'] ?? '—') ?></div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-primary fs-6"><?= (int)$r['jumlah_sesi'] ?></span>
                    </td>
                    <td class="text-end text-muted">
                        <?= $r['tarif_per_sesi'] ? format_rupiah((float)$r['tarif_per_sesi']) : '<span class="text-warning">—</span>' ?>
                    </td>
                    <td class="text-end fw-bold <?= $total > 0 ? 'text-danger' : 'text-muted' ?>">
                        <?= $total > 0 ? format_rupiah($total) : '—' ?>
                        <?php if (!$r['tarif_per_sesi']): ?>
                        <div style="font-size:.68rem;" class="text-warning">set tarif dulu</div>
                        <?php endif; ?>
                    </td>
                    <td class="d-none d-md-table-cell text-muted" style="font-size:.72rem;">
                        <?php if ($r['bank_nama']): ?>
                        <?= e($r['bank_nama']) ?><br>
                        <?= e($r['bank_rekening'] ?? '') ?><br>
                        a.n. <?= e($r['bank_atas_nama'] ?? '') ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="text-center pe-3">
                        <?php if ($total > 0): ?>
                        <button class="btn btn-sm btn-outline-danger"
                                onclick="bayarGaji(<?= (int)$r['id'] ?>, '<?= e(addslashes($r['nama_lengkap'])) ?>', <?= $total ?>, '<?= e(csrf_token()) ?>')">
                            <i class="bi bi-cash-coin me-1"></i>Bayar
                        </button>
                        <?php if ($r['nomor_wa']): ?>
                        <a href="https://wa.me/62<?= ltrim(preg_replace('/\D/','',$r['nomor_wa']),'0') ?>?text=<?= urlencode('Halo '.$r['nama_lengkap'].', gaji Anda bulan '.date('F Y',strtotime($gaji_bulan.'-01')).' sebesar '.format_rupiah($total).' siap ditransfer. Silakan konfirmasi nomor rekening. - Admin Perkasa') ?>"
                           target="_blank" class="btn btn-sm btn-outline-success ms-1">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                        <?php endif; ?>
                        <?php else: ?>
                        <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <?php if (!empty($rekap_gaji) && $total_gaji > 0): ?>
            <tfoot class="table-light">
                <tr>
                    <td colspan="3" class="fw-bold text-end ps-3">TOTAL PENGELUARAN GAJI:</td>
                    <td class="fw-bold text-end text-danger"><?= format_rupiah($total_gaji) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<script>
function bayarGaji(tutorId, tutorNama, total, csrf) {
    pkConfirm(
        `Tandai gaji ${tutorNama} sebesar Rp ${Math.round(total).toLocaleString('id-ID')} sebagai DIBAYAR?\nIni akan mencatat pengeluaran di Buku Kas.`,
        () => {
            location.href = `index.php?page=tutor&action=bayar_gaji&tutor_id=${tutorId}&bulan=<?= e($gaji_bulan) ?>&_csrf_token=${csrf}`;
        },
        {title: 'Konfirmasi Pembayaran Gaji', btnLabel: 'Ya, Bayar', btnClass: 'btn-danger'}
    );
}
</script>

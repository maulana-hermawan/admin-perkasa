<?php /** app/modules/pembayaran/list.view.php */ ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Pembayaran Siswa</h4>
        <small class="text-muted">Rekam pembayaran SPP dan kelola tagihan per siswa.</small>
    </div>
    <a href="index.php?page=pembayaran&action=create" class="btn btn-success" id="btnNewEntry">
        <i class="bi bi-plus-circle me-1"></i> Catat Pembayaran
    </a>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="pk-card pk-stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="text-muted fw-bold" style="font-size:.68rem;">TOTAL MASUK</div>
                <div class="fw-bold text-success" style="font-size:1.1rem;"><?= format_rupiah((float)($stat['total_masuk']??0)) ?></div>
            </div>
            <div class="pk-stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-cash-coin"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pk-card pk-stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="text-muted fw-bold" style="font-size:.68rem;">LUNAS</div>
                <div class="fw-bold text-success" style="font-size:1.5rem;"><?= (int)($stat['lunas']??0) ?></div>
            </div>
            <div class="pk-stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pk-card pk-stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="text-muted fw-bold" style="font-size:.68rem;">CICILAN</div>
                <div class="fw-bold text-warning" style="font-size:1.5rem;"><?= (int)($stat['cicilan']??0) ?></div>
            </div>
            <div class="pk-stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-hourglass-split"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="pk-card pk-stat-card d-flex justify-content-between align-items-center">
            <div>
                <div class="text-muted fw-bold" style="font-size:.68rem;">BELUM BAYAR</div>
                <div class="fw-bold text-danger" style="font-size:1.5rem;"><?= (int)($stat['belum']??0) ?></div>
            </div>
            <div class="pk-stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-exclamation-circle-fill"></i></div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="pk-card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="pembayaran">
        <div class="col-md-3">
            <label class="form-label small fw-bold mb-1">Cari Siswa</label>
            <input type="text" name="q" class="form-control form-control-sm" value="<?= e($f_q??'') ?>" placeholder="Nama siswa...">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Semua</option>
                <?php foreach(['Lunas','Cicilan','Belum Bayar'] as $s): ?>
                <option value="<?= $s ?>" <?= ($f_status??'')===$s?'selected':'' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold mb-1">Periode</label>
            <input type="month" name="bulan" class="form-control form-control-sm" value="<?= e($f_bulan??'') ?>">
        </div>
        <div class="col-auto">
            <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
            <a href="?page=pembayaran" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
        </div>
    </form>
</div>

<!-- Tabel -->
<div class="pk-card overflow-hidden">
    <div class="table-responsive">
        <table class="table pk-table pk-table-mobile align-middle mb-0 small">
            <thead class="table-dark">
                <tr>
                    <th class="ps-3">Siswa</th>
                    <th class="d-none d-md-table-cell">Periode</th>
                    <th class="d-none d-md-table-cell text-end">Tagihan</th>
                    <th class="text-end">Dibayar</th>
                    <th>Status</th>
                    <th class="d-none d-md-table-cell">Metode</th>
                    <th class="text-center pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftar)): ?>
                <tr><td colspan="7">
                    <div class="pk-empty-state"><i class="bi bi-receipt"></i>
                    <small>Belum ada data pembayaran<?= $f_q?' yang cocok':'' ?>.</small>
                    <a href="index.php?page=pembayaran&action=create" class="btn btn-success btn-sm mt-2">+ Catat Pembayaran</a>
                    </div>
                </td></tr>
                <?php else: foreach ($daftar as $p):
                    $sisa = (float)$p['nominal_tagihan'] - (float)$p['nominal_bayar'];
                ?>
                <tr>
                    <td class="ps-3" data-label="Siswa">
                        <div class="fw-bold"><?= e($p['nama_lengkap']) ?></div>
                        <div class="text-muted" style="font-size:.72rem;"><?= e($p['nomor_induk']??'') ?></div>
                    </td>
                    <td class="d-none d-md-table-cell" data-label="Program">
                        <?= e($p['keterangan_program'] ?? ($p['periode_bulan'] ? date('F Y',strtotime($p['periode_bulan'])) : '—')) ?>
                        <?php if (!empty($detail_map[$p['id']])): ?>
                        <div style="font-size:.68rem;color:#888;margin-top:.15rem;">
                            <?php foreach ($detail_map[$p['id']] as $d): ?>
                            <span><?= e($d['nama_program']) ?>: <?= format_rupiah((float)$d['biaya_per_bulan']) ?>/bln</span><br>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="d-none d-md-table-cell text-end" data-label="Tagihan">
                        <?= format_rupiah((float)$p['nominal_tagihan']) ?>
                    </td>
                    <td class="text-end fw-bold" data-label="Dibayar">
                        <span class="text-success"><?= format_rupiah((float)$p['nominal_bayar']) ?></span>
                        <?php if ($sisa > 0): ?>
                        <div class="text-danger" style="font-size:.7rem;">sisa <?= format_rupiah($sisa) ?></div>
                        <?php endif; ?>
                    </td>
                    <td data-label="Status">
                        <?php
                        $badge = match($p['status_bayar']) {
                            'Lunas'      => 'bg-success',
                            'Cicilan'    => 'bg-warning text-dark',
                            default      => 'bg-danger',
                        };
                        ?>
                        <span class="badge <?= $badge ?>"><?= e($p['status_bayar']) ?></span>
                    </td>
                    <td class="d-none d-md-table-cell text-muted" data-label="Metode">
                        <?= e($p['metode_pembayaran'] ?: '—') ?>
                        <?php if ($p['tanggal_bayar']): ?>
                        <div style="font-size:.68rem;"><?= e(date('d M Y',strtotime($p['tanggal_bayar']))) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center pe-3" data-label="">
                        <a href="index.php?page=pembayaran&action=kwitansi&id=<?= (int)$p['id'] ?>"
                           class="btn btn-sm btn-outline-secondary" title="Cetak Kwitansi">
                            <i class="bi bi-receipt"></i></a>
                        <a href="index.php?page=pembayaran&action=edit&id=<?= (int)$p['id'] ?>"
                           class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                        <?php if ($p['nomor_wa']): ?>
                        <a href="https://wa.me/62<?= ltrim(preg_replace('/\D/','',$p['nomor_wa']),'0') ?>?text=<?= urlencode('Konfirmasi pembayaran SPP Anda di Perkasa Mulia TC sudah kami terima. Terima kasih!') ?>"
                           target="_blank" class="btn btn-sm btn-outline-success" title="Konfirmasi WA">
                           <i class="bi bi-whatsapp"></i></a>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-danger"
                                onclick="pkConfirm('Hapus data pembayaran ini?',()=>location.href='index.php?page=pembayaran&action=delete&id=<?= (int)$p['id'] ?>&_csrf_token=<?= e(csrf_token()) ?>')">
                            <i class="bi bi-trash-fill"></i></button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($daftar)): ?>
    <div class="px-3 py-2 border-top text-muted" style="font-size:.75rem;">
        <?= count($daftar) ?> record ditampilkan
    </div>
    <?php endif; ?>
</div>

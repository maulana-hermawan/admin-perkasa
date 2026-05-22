<?php
/**
 * app/modules/pembayaran/kwitansi.view.php
 * Bukti pembayaran / kwitansi — siap cetak.
 * Gunakan tombol Cetak atau Ctrl+P dari browser.
 */
$sisa = (float)$pay['nominal_tagihan'] - (float)$pay['nominal_bayar'];
$nomor_kwit = 'PMTC-' . date('y', strtotime($pay['tanggal_bayar'] ?? $pay['created_at'])) . '-' . str_pad((string)(int)$pay['id'], 5, '0', STR_PAD_LEFT);
?>

<!-- Tombol kontrol (tidak ikut cetak) -->
<div class="no-print d-flex gap-2 mb-3">
    <button class="btn btn-primary btn-sm" onclick="window.print()">
        <i class="bi bi-printer-fill me-1"></i>Cetak / Simpan PDF
    </button>
    <a href="index.php?page=pembayaran" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<!-- Kwitansi -->
<div class="pk-card p-4" style="max-width:680px; margin:0 auto;" id="kwitansi">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
        <div class="d-flex align-items-center gap-3">
            <img src="<?= rtrim(APP_URL,'/') ?>/assets/logo.svg"
                 alt="Logo"
                 style="width:48px;height:48px;object-fit:contain;"
                 onerror="this.outerHTML='<span style=\'font-size:2rem;\'>🏋️</span>'">
            <div>
                <div class="fw-bold" style="font-size:1rem;color:#001233;">Perkasa Mulia Training Center</div>
                <div class="text-muted" style="font-size:.75rem;">Jl. Contoh No. 123, Kota — WA: <?= defined('ADMIN_WA_NUMBER') ? e(ADMIN_WA_NUMBER) : '—' ?></div>
            </div>
        </div>
        <div class="text-end">
            <div class="fw-bold text-uppercase" style="font-size:.7rem;letter-spacing:.08em;color:#6c757d;">Bukti Pembayaran</div>
            <div class="font-monospace fw-bold" style="font-size:.95rem;color:#001233;"><?= e($nomor_kwit) ?></div>
        </div>
    </div>

    <!-- Status badge -->
    <div class="text-center mb-3">
        <?php
        $badge_cls = match($pay['status_bayar'] ?? '') {
            'Lunas'      => 'bg-success',
            'Cicilan'    => 'bg-warning text-dark',
            default      => 'bg-danger',
        };
        ?>
        <span class="badge <?= $badge_cls ?> px-3 py-2" style="font-size:.9rem;letter-spacing:.04em;">
            <?= e(strtoupper($pay['status_bayar'] ?? '—')) ?>
        </span>
    </div>

    <!-- Info Siswa -->
    <div class="row g-3 mb-3">
        <div class="col-sm-6">
            <table class="table table-borderless table-sm mb-0" style="font-size:.82rem;">
                <tr>
                    <td class="text-muted fw-bold" style="width:100px;">Nama</td>
                    <td><?= e($pay['nama_lengkap']) ?></td>
                </tr>
                <tr>
                    <td class="text-muted fw-bold">NIS</td>
                    <td class="font-monospace"><?= e($pay['nomor_induk'] ?? '—') ?></td>
                </tr>
                <?php if ($pay['alamat']): ?>
                <tr>
                    <td class="text-muted fw-bold">Alamat</td>
                    <td><?= e($pay['alamat']) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <div class="col-sm-6">
            <table class="table table-borderless table-sm mb-0" style="font-size:.82rem;">
                <tr>
                    <td class="text-muted fw-bold" style="width:100px;">Tgl Bayar</td>
                    <td><?= format_tanggal($pay['tanggal_bayar'] ?? $pay['created_at'], 'd M Y H:i') ?></td>
                </tr>
                <tr>
                    <td class="text-muted fw-bold">Metode</td>
                    <td><?= e($pay['metode_pembayaran'] ?? 'Tunai') ?></td>
                </tr>
                <?php if (!empty($pay['jumlah_bulan'])): ?>
                <tr>
                    <td class="text-muted fw-bold">Jumlah Bulan</td>
                    <td><?= (int)$pay['jumlah_bulan'] ?> bulan</td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Rincian Program -->
    <table class="table table-sm mb-3" style="font-size:.82rem;">
        <thead class="table-light">
            <tr>
                <th>Program / Keterangan</th>
                <th class="text-end" style="width:120px;">Harga/Bln</th>
                <th class="text-center" style="width:60px;">Bln</th>
                <th class="text-end" style="width:120px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($kwit_items)): ?>
                <?php foreach ($kwit_items as $item): ?>
                <tr>
                    <td><?= e($item['prog_nama'] ?? $item['nama_program'] ?? '—') ?></td>
                    <td class="text-end"><?= format_rupiah((float)($item['biaya_per_bulan'] ?? 0)) ?></td>
                    <td class="text-center"><?= (int)($item['jumlah_bulan'] ?? 1) ?></td>
                    <td class="text-end fw-bold"><?= format_rupiah((float)($item['subtotal'] ?? (($item['biaya_per_bulan'] ?? 0) * ($item['jumlah_bulan'] ?? 1)))) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4"><?= e($pay['keterangan_program'] ?? $pay['keterangan'] ?? 'Pembayaran SPP') ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Ringkasan keuangan -->
    <div class="d-flex flex-column align-items-end gap-1 border-top pt-3 mb-4" style="font-size:.88rem;">
        <div class="d-flex gap-4">
            <span class="text-muted">Total Tagihan</span>
            <span class="fw-bold" style="min-width:140px;text-align:right;"><?= format_rupiah((float)$pay['nominal_tagihan']) ?></span>
        </div>
        <div class="d-flex gap-4">
            <span class="text-muted">Nominal Dibayar</span>
            <span class="fw-bold text-success" style="min-width:140px;text-align:right;"><?= format_rupiah((float)$pay['nominal_bayar']) ?></span>
        </div>
        <?php if ($sisa > 0): ?>
        <div class="d-flex gap-4 border-top pt-1 mt-1">
            <span class="text-danger fw-bold">Sisa Belum Bayar</span>
            <span class="fw-bold text-danger" style="min-width:140px;text-align:right;"><?= format_rupiah($sisa) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($pay['keterangan']): ?>
    <div class="text-muted small mb-4">
        <strong>Keterangan:</strong> <?= e($pay['keterangan']) ?>
    </div>
    <?php endif; ?>

    <!-- Tanda tangan -->
    <div class="row mt-4 pt-2" style="font-size:.82rem;">
        <div class="col-6 text-center">
            <div class="text-muted mb-4">Mengetahui,</div>
            <div class="border-top pt-2 mx-4">
                <div class="fw-bold">Kepala Bidang Keuangan</div>
                <div class="text-muted">Perkasa Mulia TC</div>
            </div>
        </div>
        <div class="col-6 text-center">
            <div class="text-muted mb-4">Diterima oleh,</div>
            <div class="border-top pt-2 mx-4">
                <div class="fw-bold">Siswa / Wali</div>
                <div class="text-muted"><?= e($pay['nama_lengkap']) ?></div>
            </div>
        </div>
    </div>

    <div class="text-center text-muted mt-4 pt-3 border-top" style="font-size:.7rem;">
        Dokumen ini dicetak pada <?= date('d M Y H:i') ?> | Perkasa Mulia Training Center &copy; <?= date('Y') ?>
    </div>
</div>

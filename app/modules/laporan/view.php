<?php /** app/modules/laporan/view.php */ ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Laporan & Ekspor</h4>
        <small class="text-muted">Download data dalam format CSV (bisa dibuka di Excel/Google Sheets).</small>
    </div>
</div>

<!-- Filter Bulan (untuk laporan berbasis bulan) -->
<div class="pk-card p-3 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="laporan">
        <div class="col-md-3">
            <label class="form-label small fw-bold mb-1">Periode</label>
            <input type="month" name="bulan" class="form-control form-control-sm" value="<?= e($bulan) ?>">
        </div>
        <div class="col-auto">
            <button class="btn btn-primary btn-sm">Terapkan</button>
        </div>
    </form>
    <div class="mt-2 text-muted small">
        Periode aktif: <strong><?= e(date('F Y', strtotime($bulan.'-01'))) ?></strong>
        (digunakan untuk laporan transaksi & gaji)
    </div>
</div>

<div class="row g-4">
    <!-- Data Siswa -->
    <div class="col-md-6">
        <div class="pk-card p-4 h-100">
            <div class="d-flex align-items-start gap-3 mb-3">
                <div class="pk-stat-icon bg-primary bg-opacity-10 text-primary" style="flex-shrink:0;">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Data Siswa</h6>
                    <p class="text-muted small mb-0">Semua siswa aktif + non-aktif beserta data lengkap.</p>
                </div>
            </div>
            <a href="index.php?page=laporan&action=export_siswa"
               class="btn btn-primary btn-sm w-100">
                <i class="bi bi-download me-2"></i>Download CSV — Data Siswa
            </a>
        </div>
    </div>

    <!-- Transaksi Keuangan -->
    <div class="col-md-6">
        <div class="pk-card p-4 h-100">
            <div class="d-flex align-items-start gap-3 mb-3">
                <div class="pk-stat-icon bg-success bg-opacity-10 text-success" style="flex-shrink:0;">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Transaksi Keuangan</h6>
                    <p class="text-muted small mb-0">Semua transaksi pemasukan & pengeluaran bulan ini.</p>
                </div>
            </div>
            <a href="index.php?page=laporan&action=export_transaksi&bulan=<?= e($bulan) ?>"
               class="btn btn-success btn-sm w-100">
                <i class="bi bi-download me-2"></i>Download CSV — Transaksi <?= e(date('F Y',strtotime($bulan.'-01'))) ?>
            </a>
        </div>
    </div>

    <!-- Rekap Gaji Tutor -->
    <div class="col-md-6">
        <div class="pk-card p-4 h-100">
            <div class="d-flex align-items-start gap-3 mb-3">
                <div class="pk-stat-icon bg-warning bg-opacity-10 text-warning" style="flex-shrink:0;">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Rekap Gaji Tutor</h6>
                    <p class="text-muted small mb-0">Jumlah sesi & total gaji per tutor bulan ini.</p>
                </div>
            </div>
            <a href="index.php?page=laporan&action=export_gaji&bulan=<?= e($bulan) ?>"
               class="btn btn-warning btn-sm w-100">
                <i class="bi bi-download me-2"></i>Download CSV — Gaji <?= e(date('F Y',strtotime($bulan.'-01'))) ?>
            </a>
        </div>
    </div>

    <!-- Rekap Nilai Siswa -->
    <div class="col-md-6">
        <div class="pk-card p-4 h-100">
            <div class="d-flex align-items-start gap-3 mb-3">
                <div class="pk-stat-icon bg-info bg-opacity-10 text-info" style="flex-shrink:0;">
                    <i class="bi bi-bar-chart-fill"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-1">Rekap Nilai Siswa</h6>
                    <p class="text-muted small mb-0">Ringkasan nilai jasmani, SKD, & psikologi semua siswa.</p>
                </div>
            </div>
            <a href="index.php?page=laporan&action=export_nilai"
               class="btn btn-info text-white btn-sm w-100">
                <i class="bi bi-download me-2"></i>Download CSV — Rekap Nilai Semua Siswa
            </a>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4 small">
    <i class="bi bi-info-circle-fill me-2"></i>
    <strong>Cara buka di Excel:</strong> Buka Excel → File → Import → From Text/CSV → pilih file yang didownload.
    Atau klik kanan file → "Open With" → Microsoft Excel.
</div>

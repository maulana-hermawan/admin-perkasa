<?php
/**
 * app/modules/dashboard/view.php
 * Module 6.4 — Dashboard Upgrade
 * Semua variabel sudah di-extract dari $view_data oleh admin.php
 */

// Helper: render badge delta
function delta_badge(array $d, bool $invert = false): string
{
    if ($d['pct'] === null) return '<span class="badge bg-secondary bg-opacity-20 text-secondary ms-1" style="font-size:.65rem;">—</span>';
    $up    = $invert ? !$d['up'] : $d['up'];
    $color = $up ? 'success' : 'danger';
    $icon  = $d['up'] ? '↑' : '↓';
    $abs   = abs($d['pct']);
    return "<span class=\"badge bg-{$color} bg-opacity-15 text-{$color} ms-1\" style=\"font-size:.65rem;\">{$icon} {$abs}%</span>";
}
?>

<!-- ══ HEADER + DATE RANGE PICKER ══════════════════════════════ -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Dashboard</h4>
        <p class="text-muted mb-0" style="font-size:.85rem;">
            Ringkasan operasional Perkasa Mulia Training Center
        </p>
    </div>

    <!-- Date Range Picker -->
    <div x-data="dateRange()" class="position-relative">
        <button @click="open = !open"
                class="btn btn-white border shadow-sm d-flex align-items-center gap-2"
                style="min-width:200px; font-size:.85rem;">
            <i class="bi bi-calendar3 text-primary"></i>
            <span><?= e($label_periode) ?></span>
            <i class="bi bi-chevron-down text-muted ms-auto" style="font-size:.7rem;"></i>
        </button>

        <!-- Dropdown -->
        <div x-show="open" @click.outside="open = false" x-cloak
             class="position-absolute end-0 mt-1 bg-white border rounded-3 shadow-lg p-0"
             style="z-index:500; min-width:240px;">

            <!-- Presets -->
            <div class="p-2 border-bottom">
                <?php
                $presets = [
                    'hari_ini'   => 'Hari Ini',
                    'minggu_ini' => 'Minggu Ini',
                    'bulan_ini'  => 'Bulan Ini',
                    '30_hari'    => '30 Hari Terakhir',
                    'tahun_ini'  => 'Tahun Ini',
                ];
                foreach ($presets as $key => $label):
                    $active = $periode === $key ? 'bg-primary text-white' : 'text-dark';
                ?>
                <a href="?page=dashboard&periode=<?= $key ?>"
                   class="d-block px-3 py-2 rounded-2 small text-decoration-none mb-1 <?= $active ?>"
                   onclick="open = false">
                    <?= $label ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Custom range -->
            <form method="GET" class="p-3">
                <input type="hidden" name="page" value="dashboard">
                <input type="hidden" name="periode" value="custom">
                <div class="mb-2">
                    <label class="form-label small fw-bold mb-1">Dari</label>
                    <input type="date" name="tgl_dari" class="form-control form-control-sm"
                           value="<?= e($tgl_dari) ?>">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold mb-1">Sampai</label>
                    <input type="date" name="tgl_sampai" class="form-control form-control-sm"
                           value="<?= e($tgl_sampai) ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100">Terapkan</button>
            </form>
        </div>
    </div>
</div>

<!-- ══ STAT CARDS (4 kolom) ════════════════════════════════════ -->
<div class="row g-3 mb-4">

    <!-- Siswa Aktif -->
    <div class="col-6 col-md-3">
        <a href="index.php?page=siswa&status=Aktif" class="text-decoration-none">
            <div class="pk-card pk-stat-card h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="pk-stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <?= delta_badge($delta_siswa) ?>
                </div>
                <div class="mt-3">
                    <div class="fw-bold" style="font-size:1.6rem; line-height:1;"><?= number_format($siswa_aktif) ?></div>
                    <div class="text-muted" style="font-size:.72rem; margin-top:2px;">SISWA AKTIF</div>
                    <div class="text-muted" style="font-size:.68rem;">
                        +<?= $siswa_baru ?> baru <?= e($label_periode) ?>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- Saldo Kas -->
    <div class="col-6 col-md-3">
        <a href="index.php?page=keuangan" class="text-decoration-none">
            <div class="pk-card pk-stat-card h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="pk-stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-safe-fill"></i>
                    </div>
                    <?= delta_badge($delta_saldo) ?>
                </div>
                <div class="mt-3">
                    <div class="fw-bold text-success" style="font-size:1.2rem; line-height:1.2;">
                        <?= format_rupiah($saldo_kas) ?>
                    </div>
                    <div class="text-muted" style="font-size:.72rem; margin-top:2px;">SALDO KAS</div>
                    <div class="text-muted" style="font-size:.68rem;">Total kumulatif</div>
                </div>
            </div>
        </a>
    </div>

    <!-- Pemasukan Periode -->
    <div class="col-6 col-md-3">
        <a href="index.php?page=keuangan&arus=Pemasukan&tgl_mulai=<?= e($tgl_dari) ?>&tgl_selesai=<?= e($tgl_sampai) ?>"
           class="text-decoration-none">
            <div class="pk-card pk-stat-card h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="pk-stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <?= delta_badge($delta_masuk) ?>
                </div>
                <div class="mt-3">
                    <div class="fw-bold text-info" style="font-size:1.2rem; line-height:1.2;">
                        <?= format_rupiah($masuk_now) ?>
                    </div>
                    <div class="text-muted" style="font-size:.72rem; margin-top:2px;">PEMASUKAN</div>
                    <div class="text-muted" style="font-size:.68rem;"><?= e($label_periode) ?></div>
                </div>
            </div>
        </a>
    </div>

    <!-- Pengeluaran Periode -->
    <div class="col-6 col-md-3">
        <a href="index.php?page=keuangan&arus=Pengeluaran&tgl_mulai=<?= e($tgl_dari) ?>&tgl_selesai=<?= e($tgl_sampai) ?>"
           class="text-decoration-none">
            <div class="pk-card pk-stat-card h-100 d-flex flex-column justify-content-between">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="pk-stat-icon bg-danger bg-opacity-10 text-danger">
                        <i class="bi bi-graph-down-arrow"></i>
                    </div>
                    <?= delta_badge($delta_keluar, true) /* invert: naik = merah */ ?>
                </div>
                <div class="mt-3">
                    <div class="fw-bold text-danger" style="font-size:1.2rem; line-height:1.2;">
                        <?= format_rupiah($keluar_now) ?>
                    </div>
                    <div class="text-muted" style="font-size:.72rem; margin-top:2px;">PENGELUARAN</div>
                    <div class="text-muted" style="font-size:.68rem;"><?= e($label_periode) ?></div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- ══ TREND CHART + MEMBERSHIP EXPIRING ══════════════════════ -->
<div class="row g-3 mb-4">

    <!-- Trend Chart -->
    <div class="col-lg-7">
        <div class="pk-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-bar-chart-line-fill text-primary me-2"></i>Trend Arus Kas 6 Bulan
                </h6>
                <!-- Toggle dataset -->
                <div class="btn-group btn-group-sm" role="group" id="chartToggle">
                    <button type="button" class="btn btn-outline-secondary active" data-toggle="both">Keduanya</button>
                    <button type="button" class="btn btn-outline-success"           data-toggle="masuk">Masuk</button>
                    <button type="button" class="btn btn-outline-danger"            data-toggle="keluar">Keluar</button>
                    <button type="button" class="btn btn-outline-primary"           data-toggle="saldo">Saldo</button>
                </div>
            </div>
            <div style="position:relative; height:230px;">
                <canvas id="chartArusKas" role="img"
                        aria-label="Trend arus kas 6 bulan terakhir — pemasukan dan pengeluaran">
                </canvas>
            </div>
        </div>
    </div>

    <!-- Membership Expiring -->
    <div class="col-lg-5">
        <div class="pk-card h-100 d-flex flex-column overflow-hidden">
            <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                    Membership Expiring
                </h6>
                <div class="d-flex gap-1">
                    <?php if ($expired_count > 0): ?>
                    <span class="badge bg-danger"><?= $expired_count ?> expired</span>
                    <?php endif; ?>
                    <span class="badge bg-warning text-dark"><?= count($membership_expiring) - $expired_count ?> akan habis</span>
                </div>
            </div>

            <div class="flex-grow-1 overflow-auto" style="max-height:270px;">
                <?php if (empty($membership_expiring)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-check-circle-fill text-success d-block" style="font-size:2rem; margin-bottom:.5rem;"></i>
                    <small>Tidak ada membership yang akan habis</small>
                </div>
                <?php else: foreach ($membership_expiring as $m):
                    $sisa      = (int)$m['sisa_hari'];
                    $is_exp    = $sisa < 0;
                    $dot_color = $is_exp ? 'danger' : ($sisa <= 3 ? 'danger' : ($sisa <= 5 ? 'warning' : 'info'));
                    $badge_txt = $is_exp ? abs($sisa).' hari lalu' : ($sisa === 0 ? 'Hari ini!' : $sisa.' hari lagi');
                ?>
                <div class="px-3 py-2 border-bottom d-flex align-items-center gap-2">
                    <span class="rounded-circle bg-<?= $dot_color ?> flex-shrink-0"
                          style="width:8px;height:8px;display:inline-block;"></span>
                    <div class="flex-grow-1 min-width-0">
                        <div class="fw-bold small text-truncate"><?= e($m['nama_lengkap']) ?></div>
                        <div class="text-muted" style="font-size:.7rem;"><?= e($m['nama_program']) ?></div>
                    </div>
                    <div class="flex-shrink-0 text-end">
                        <span class="badge bg-<?= $dot_color ?> <?= $dot_color === 'warning' ? 'text-dark' : '' ?>"
                              style="font-size:.65rem;">
                            <?= $badge_txt ?>
                        </span>
                        <?php if ($m['nomor_wa']): ?>
                        <a href="<?= e(wa_link($m['nomor_wa'], "Halo {$m['nama_lengkap']}, membership Perkasa Anda akan habis pada " . format_tanggal($m['tanggal_selesai_aktif']) . ". Segera perpanjang agar tidak terputus.")) ?>"
                           target="_blank"
                           class="btn btn-xs btn-outline-success d-block mt-1"
                           style="font-size:.65rem; padding:1px 6px;">
                            <i class="bi bi-whatsapp"></i> WA
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>

            <div class="px-3 py-2 bg-light border-top text-center">
                <a href="index.php?page=siswa" class="text-primary small text-decoration-none fw-bold">
                    Lihat semua keanggotaan →
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ══ JADWAL MENDATANG + TOP 5 SISWA ═══════════════════════ -->
<div class="row g-3 mb-4">

    <!-- Jadwal Mendatang -->
    <div class="col-lg-7">
        <div class="pk-card h-100 d-flex flex-column overflow-hidden">
            <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-calendar-week text-primary me-2"></i>Jadwal 5 Hari Mendatang
                </h6>
                <a href="index.php?page=jadwal" class="btn btn-outline-primary btn-sm">
                    Lihat Kalender →
                </a>
            </div>

            <?php if (empty($jadwal_mendatang)): ?>
            <div class="flex-grow-1 d-flex align-items-center justify-content-center py-4">
                <div class="text-center text-muted">
                    <i class="bi bi-calendar-x d-block mb-2" style="font-size:2rem; opacity:.4;"></i>
                    <small>Tidak ada jadwal 5 hari ke depan</small>
                </div>
            </div>
            <?php else:
                // Grouping by tanggal
                $grouped_jadwal = [];
                foreach ($jadwal_mendatang as $j) {
                    $grouped_jadwal[$j['tanggal']][] = $j;
                }
                $color_map = [
                    'Jasmani'  => ['bg-success', 'text-success', '#d1e7dd'],
                    'Renang'   => ['bg-info',    'text-info',    '#cff4fc'],
                    'Akademik' => ['bg-primary',  'text-primary', '#cfe2ff'],
                    'Psikologi'=> ['bg-purple',   'text-purple',  '#e2d9f3'],
                    'Tryout'   => ['bg-warning',  'text-warning', '#fff3cd'],
                ];
                foreach ($grouped_jadwal as $tgl => $items):
            ?>
            <div>
                <div class="px-3 py-1 bg-light border-bottom border-top"
                     style="font-size:.72rem; font-weight:600; color:#6c757d;">
                    <?= format_tanggal($tgl) ?> <?= date('l', strtotime($tgl)) === date('l') ? '— Hari Ini' : '' ?>
                </div>
                <?php foreach ($items as $j):
                    $kegiatan = $j['nama_kegiatan'] ?: 'Kelas';
                    [$bg_cls, $txt_cls, $bg_hex] = $color_map[$kegiatan] ?? ['bg-secondary', 'text-secondary', '#e9ecef'];
                ?>
                <div class="px-3 py-2 border-bottom d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-2 flex-shrink-0"
                         style="width:36px;height:36px;background:<?= $bg_hex ?>;">
                        <i class="bi bi-<?= $kegiatan==='Jasmani'?'lightning-charge-fill':($kegiatan==='Akademik'?'book-fill':($kegiatan==='Renang'?'water':($kegiatan==='Tryout'?'clipboard-check-fill':'brain'))) ?> <?= $txt_cls ?>"></i>
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <div class="fw-bold small"><?= e($kegiatan) ?>
                            <?php if ($j['materi']): ?><span class="text-muted fw-normal">— <?= e($j['materi']) ?></span><?php endif; ?>
                        </div>
                        <div class="text-muted" style="font-size:.7rem;">
                            <?= e($j['nama_program'] ?? '—') ?>
                            <?php if ($j['lokasi']): ?> · <?= e($j['lokasi']) ?><?php endif; ?>
                        </div>
                    </div>
                    <div class="text-end flex-shrink-0">
                        <div class="fw-bold small"><?= substr($j['waktu_mulai'],0,5) ?>–<?= substr($j['waktu_selesai'],0,5) ?></div>
                        <?php if ((int)$j['jumlah_tutor'] > 0): ?>
                        <div class="text-muted" style="font-size:.68rem;">
                            <i class="bi bi-person-fill"></i> <?= (int)$j['jumlah_tutor'] ?> tutor
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Top 5 Siswa -->
    <div class="col-lg-5">
        <div class="pk-card h-100 d-flex flex-column overflow-hidden">
            <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-trophy-fill text-warning me-2"></i>
                    Top 5 Skor SKD
                </h6>
                <span class="text-muted small"><?= e($label_periode) ?></span>
            </div>

            <?php if (empty($top_siswa)): ?>
            <div class="flex-grow-1 d-flex align-items-center justify-content-center py-4">
                <div class="text-center text-muted">
                    <i class="bi bi-bar-chart d-block mb-2" style="font-size:2rem; opacity:.4;"></i>
                    <small>Belum ada data nilai SKD</small>
                </div>
            </div>
            <?php else: ?>
            <div class="p-3 flex-grow-1">
                <?php
                $medals = ['🥇','🥈','🥉','4️⃣','5️⃣'];
                $max_skor = (int)($top_siswa[0]['skor_tertinggi'] ?? 500);
                foreach ($top_siswa as $idx => $s):
                    $pct = $max_skor > 0 ? round((int)$s['skor_tertinggi'] / $max_skor * 100) : 0;
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <span style="font-size:1rem; line-height:1;"><?= $medals[$idx] ?? ($idx+1).'' ?></span>
                            <div>
                                <div class="fw-bold small"><?= e($s['nama_lengkap']) ?></div>
                                <div class="text-muted" style="font-size:.68rem;">
                                    <?= e($s['nomor_induk'] ?? '—') ?> · <?= (int)$s['jumlah_tryout'] ?> tryout
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-primary"><?= (int)$s['skor_tertinggi'] ?></div>
                            <div class="text-muted" style="font-size:.68rem;">avg <?= round((float)$s['rata_rata']) ?></div>
                        </div>
                    </div>
                    <div class="progress" style="height:5px; border-radius:10px;">
                        <div class="progress-bar bg-primary" style="width:<?= $pct ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="px-3 py-2 bg-light border-top text-center">
                <a href="index.php?page=nilai&filter_jenis=Akademik" class="text-primary small text-decoration-none fw-bold">
                    Lihat semua ranking →
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ══ QUICK ACTIONS ════════════════════════════════════════════ -->
<div class="pk-card p-3 mb-4">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="text-muted fw-bold small me-1">
            <i class="bi bi-lightning-fill text-warning me-1"></i>Quick Actions:
        </span>
        <button class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                data-bs-toggle="modal" data-bs-target="#qaModalSiswa">
            <i class="bi bi-person-plus-fill"></i> Pendaftaran
        </button>
        <button class="btn btn-sm btn-outline-success d-flex align-items-center gap-1"
                data-bs-toggle="modal" data-bs-target="#qaModalTransaksi">
            <i class="bi bi-plus-circle-fill"></i> Transaksi
        </button>
        <button class="btn btn-sm btn-outline-warning d-flex align-items-center gap-1"
                data-bs-toggle="modal" data-bs-target="#qaModalJadwal">
            <i class="bi bi-calendar-plus-fill"></i> Jadwal
        </button>
        <button class="btn btn-sm btn-outline-info d-flex align-items-center gap-1"
                data-bs-toggle="modal" data-bs-target="#qaModalNilai">
            <i class="bi bi-pencil-square"></i> Input Nilai
        </button>
        <div class="ms-auto text-muted small">
            Tekan <kbd>n</kbd> untuk aksi baru
        </div>
    </div>
</div>

<!-- ══ QUICK ACTION MODALS ══════════════════════════════════ -->

<!-- QA: Siswa Baru -->
<div class="modal fade" id="qaModalSiswa" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="index.php?page=siswa&action=store">
                <?= csrf_field() ?>
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i>Pendaftaran Cepat</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control" required maxlength="150">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Nomor WhatsApp <span class="text-danger">*</span></label>
                            <input type="tel" name="wa" class="form-control" required placeholder="0812xxxx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Nama Orang Tua</label>
                            <input type="text" name="ortu" class="form-control" maxlength="150">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Tanggal Lahir</label>
                            <input type="date" name="tgl_lahir" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Program Bimbel</label>
                            <select name="program_id" class="form-select">
                                <option value="">— Pilih —</option>
                                <?php foreach ($qa_programs as $p): ?>
                                <option value="<?= (int)$p['id'] ?>"><?= e($p['nama_program']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Mulai</label>
                            <input type="date" name="tgl_mulai" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Sampai</label>
                            <input type="date" name="tgl_selesai" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-save me-1"></i>Daftarkan
                    </button>
                    <a href="index.php?page=siswa&action=create" class="btn btn-outline-primary">
                        Form Lengkap →
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- QA: Transaksi -->
<div class="modal fade" id="qaModalTransaksi" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="index.php?page=keuangan&action=store">
                <?= csrf_field() ?>
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-cash-coin me-2"></i>Catat Transaksi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Jenis</label>
                            <select name="jenis" class="form-select" id="qaJenis" onchange="qaToggleKat()">
                                <option value="Pemasukan">Pemasukan (+)</option>
                                <option value="Pengeluaran">Pengeluaran (−)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Nominal <span class="text-danger">*</span></label>
                            <input type="number" name="nominal" class="form-control" required min="0" step="500">
                        </div>
                        <div class="col-md-6" id="qaKatMasuk">
                            <label class="form-label fw-bold small">Kategori</label>
                            <select name="kat_masuk" class="form-select">
                                <?php foreach (['Bayar Program','Biaya Admin','Kelas Tambahan','Private','Lainnya'] as $k): ?>
                                <option><?= $k ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6" id="qaKatKeluar" style="display:none;">
                            <label class="form-label fw-bold small">Kategori</label>
                            <select name="kat_keluar" class="form-select">
                                <?php foreach (['Belanja Operasional','Belanja Modal','Belanja Pegawai'] as $k): ?>
                                <option><?= $k ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Keterangan <span class="text-danger">*</span></label>
                            <input type="text" name="keterangan" class="form-control" required maxlength="500">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-bold small">Tanggal</label>
                            <input type="datetime-local" name="tanggal_transaksi" class="form-control"
                                   value="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4"><i class="bi bi-save me-1"></i>Catat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- QA: Jadwal -->
<div class="modal fade" id="qaModalJadwal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="index.php?page=jadwal&action=store">
                <?= csrf_field() ?>
                <div class="modal-header bg-warning">
                    <h5 class="modal-title fw-bold"><i class="bi bi-calendar-plus-fill me-2"></i>Tambah Jadwal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Program <span class="text-danger">*</span></label>
                            <select name="program_id" class="form-select" required>
                                <option value="">— Pilih —</option>
                                <?php foreach ($qa_programs as $p): ?>
                                <option value="<?= (int)$p['id'] ?>"><?= e($p['nama_program']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Kegiatan</label>
                            <select name="kegiatan" class="form-select">
                                <?php foreach (['Jasmani','Renang','Akademik','Psikologi','Tryout'] as $k): ?>
                                <option><?= $k ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Mulai <span class="text-danger">*</span></label>
                            <input type="time" name="waktu_mulai" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Selesai <span class="text-danger">*</span></label>
                            <input type="time" name="waktu_selesai" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small">Lokasi</label>
                            <input type="text" name="lokasi" class="form-control" placeholder="Perkasa Center">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning px-4 fw-bold">
                        <i class="bi bi-save me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- QA: Input Nilai -->
<div class="modal fade" id="qaModalNilai" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="index.php?page=nilai&action=store">
                <?= csrf_field() ?>
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Input Nilai Cepat</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-bold small">Siswa <span class="text-danger">*</span></label>
                            <select name="siswa_id" class="form-select" required>
                                <option value="">— Pilih —</option>
                                <?php foreach ($qa_siswa as $s): ?>
                                <option value="<?= (int)$s['id'] ?>"><?= e($s['nomor_induk']??'') ?> — <?= e($s['nama_lengkap']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Tanggal Tes</label>
                            <input type="date" name="tanggal_tes" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Jenis</label>
                            <select name="jenis_tes" class="form-select" id="qaJenisTes" onchange="qaToggleNilai()">
                                <option value="Akademik">Akademik</option>
                                <option value="Binjas">Binjas</option>
                            </select>
                        </div>
                        <div class="col-12"><hr class="my-0"></div>
                        <!-- Akademik -->
                        <div id="qaNilaiAka" class="col-12">
                            <div class="row g-2">
                                <div class="col-4"><label class="form-label small fw-bold">TWK</label><input type="number" name="twk" class="form-control" min="0" max="150" placeholder="≥65"></div>
                                <div class="col-4"><label class="form-label small fw-bold">TIU</label><input type="number" name="tiu" class="form-control" min="0" max="175" placeholder="≥80"></div>
                                <div class="col-4"><label class="form-label small fw-bold">TKP</label><input type="number" name="tkp" class="form-control" min="0" max="225" placeholder="≥166"></div>
                            </div>
                        </div>
                        <!-- Binjas -->
                        <div id="qaNilaiBin" class="col-12" style="display:none;">
                            <div class="row g-2">
                                <div class="col-6 col-md-4"><label class="form-label small fw-bold">Lari (m)</label><input type="number" name="lari" class="form-control" min="0"></div>
                                <div class="col-6 col-md-4"><label class="form-label small fw-bold">Pull Up</label><input type="number" name="pull" class="form-control" min="0"></div>
                                <div class="col-6 col-md-4"><label class="form-label small fw-bold">Push Up</label><input type="number" name="push" class="form-control" min="0"></div>
                                <div class="col-6 col-md-4"><label class="form-label small fw-bold">Sit Up</label><input type="number" name="sit" class="form-control" min="0"></div>
                                <div class="col-6 col-md-4"><label class="form-label small fw-bold">Shuttle (dtk)</label><input type="number" name="shuttle" step="0.1" class="form-control" min="0"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info text-white px-4">
                        <i class="bi bi-save me-1"></i>Simpan Nilai
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══ SCRIPTS ══════════════════════════════════════════════════ -->
<script>
// ── Alpine: date range dropdown ─────────────────────────────────
function dateRange() {
    return { open: false };
}

// ── Chart Arus Kas dengan toggle ────────────────────────────────
// Chart.js dimuat dengan `defer` sehingga harus diinisialisasi setelah DOM+scripts siap
const chartData = {
    labels : <?= json_encode($chart_labels) ?>,
    masuk  : <?= json_encode($chart_masuk) ?>,
    keluar : <?= json_encode($chart_keluar) ?>,
    saldo  : <?= json_encode($chart_saldo_kumulatif) ?>,
};

let chart;
document.addEventListener('DOMContentLoaded', () => {
const ctx = document.getElementById('chartArusKas');
if (!ctx || typeof Chart === 'undefined') return;
chart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: chartData.labels,
        datasets: [
            {
                label    : 'Pemasukan',
                data     : chartData.masuk,
                backgroundColor : 'rgba(25,135,84,.75)',
                borderRadius    : 5,
                order           : 2,
            },
            {
                label    : 'Pengeluaran',
                data     : chartData.keluar,
                backgroundColor : 'rgba(220,53,69,.75)',
                borderRadius    : 5,
                order           : 3,
            },
            {
                label    : 'Saldo Kumulatif',
                data     : chartData.saldo,
                type     : 'line',
                borderColor     : '#0d6efd',
                backgroundColor : 'rgba(13,110,253,.08)',
                tension         : 0.35,
                fill            : false,
                pointRadius     : 4,
                borderWidth     : 2,
                order           : 1,
                hidden          : true,
            },
        ],
    },
    options: {
        responsive         : true,
        maintainAspectRatio: false,
        interaction        : { mode: 'index', intersect: false },
        plugins: {
            legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.dataset.label + ': Rp ' +
                           ctx.parsed.y.toLocaleString('id-ID'),
                },
            },
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: v => 'Rp ' + (v / 1e6).toFixed(1) + 'jt',
                    font: { size: 10 },
                },
            },
            x: { ticks: { font: { size: 10 } } },
        },
    },
});

// Toggle dataset
document.getElementById('chartToggle')?.addEventListener('click', e => {
    const btn = e.target.closest('button[data-toggle]');
    if (!btn) return;

    // Reset active
    document.querySelectorAll('#chartToggle button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const mode = btn.dataset.toggle;
    const [ds0, ds1, ds2] = chart.data.datasets;

    if (mode === 'both')   { ds0.hidden = false; ds1.hidden = false; ds2.hidden = true;  }
    if (mode === 'masuk')  { ds0.hidden = false; ds1.hidden = true;  ds2.hidden = true;  }
    if (mode === 'keluar') { ds0.hidden = true;  ds1.hidden = false; ds2.hidden = true;  }
    if (mode === 'saldo')  { ds0.hidden = true;  ds1.hidden = true;  ds2.hidden = false; }
    chart.update();
});

}); // end DOMContentLoaded

// ── Quick Action helpers ────────────────────────────────────────
function qaToggleKat() {
    const j = document.getElementById('qaJenis').value;
    document.getElementById('qaKatMasuk').style.display  = j === 'Pemasukan'   ? '' : 'none';
    document.getElementById('qaKatKeluar').style.display = j === 'Pengeluaran' ? '' : 'none';
}

function qaToggleNilai() {
    const j = document.getElementById('qaJenisTes').value;
    document.getElementById('qaNilaiAka').style.display = j === 'Akademik' ? '' : 'none';
    document.getElementById('qaNilaiBin').style.display = j === 'Binjas'   ? '' : 'none';
}

// ── Keyboard shortcut: n = buka QA ─────────────────────────────
document.addEventListener('keydown', e => {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
    if (e.key === 'n') {
        e.preventDefault();
        new bootstrap.Modal(document.getElementById('qaModalSiswa')).show();
    }
});
</script>

<?php
// ── ANALITIK LANJUTAN — append ke bawah dashboard ──────────────
if (!empty($cohort_lulus) || !empty($top_program) || !empty($tagihan_stats)):
?>
<div class="row g-4 mt-1">

    <?php if (!empty($cohort_lulus)): ?>
    <!-- Cohort Lulus -->
    <div class="col-lg-6">
        <div class="pk-card p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-mortarboard-fill text-success me-2"></i>Cohort Lulus per Tahun</h6>
            <table class="table table-sm small mb-0">
                <thead class="table-light"><tr><th>Tahun</th><th class="text-center">Masuk</th><th class="text-center">Lulus</th><th class="text-end">% Lulus</th></tr></thead>
                <tbody>
                    <?php foreach ($cohort_lulus as $c): ?>
                    <tr>
                        <td class="fw-bold"><?= (int)$c['tahun'] ?></td>
                        <td class="text-center"><?= (int)$c['total_masuk'] ?></td>
                        <td class="text-center text-success fw-bold"><?= (int)$c['lulus'] ?></td>
                        <td class="text-end">
                            <div class="d-flex align-items-center gap-2 justify-content-end">
                                <div class="progress" style="height:6px;width:60px;">
                                    <div class="progress-bar bg-success" style="width:<?= min(100,(float)$c['pct_lulus']) ?>%"></div>
                                </div>
                                <span class="fw-bold text-success"><?= (float)$c['pct_lulus'] ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($top_program)): ?>
    <!-- Top Program -->
    <div class="col-lg-6">
        <div class="pk-card p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-trophy-fill text-warning me-2"></i>Program Terpopuler</h6>
            <?php foreach ($top_program as $idx => $p): ?>
            <div class="d-flex align-items-center gap-3 mb-2">
                <span class="badge bg-light text-dark border fw-bold" style="min-width:24px;"><?= $idx+1 ?></span>
                <div class="flex-grow-1">
                    <div class="small fw-bold"><?= e($p['nama_program']) ?></div>
                    <div class="progress mt-1" style="height:5px;">
                        <?php $maxS = (int)($top_program[0]['jumlah_siswa'] ?? 1); ?>
                        <div class="progress-bar bg-primary" style="width:<?= $maxS > 0 ? round((int)$p['jumlah_siswa']/$maxS*100) : 0 ?>%"></div>
                    </div>
                </div>
                <div class="text-end small">
                    <div class="fw-bold"><?= (int)$p['jumlah_siswa'] ?></div>
                    <div class="text-success" style="font-size:.68rem;"><?= (int)$p['aktif'] ?> aktif</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($tagihan_stats['total_tagihan'])): ?>
    <!-- Tagihan bulan ini -->
    <div class="col-lg-6">
        <div class="pk-card p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-receipt-cutoff text-info me-2"></i>Tagihan SPP Bulan Ini</h6>
            <?php
            $ttag = (float)($tagihan_stats['total_tagihan'] ?? 0);
            $tbay = (float)($tagihan_stats['total_bayar']   ?? 0);
            $pct  = $ttag > 0 ? round($tbay / $ttag * 100) : 0;
            ?>
            <div class="d-flex justify-content-between mb-2 small">
                <span class="text-muted">Total Tagihan</span>
                <span class="fw-bold"><?= format_rupiah($ttag) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2 small">
                <span class="text-muted">Terkumpul</span>
                <span class="fw-bold text-success"><?= format_rupiah($tbay) ?></span>
            </div>
            <div class="progress mb-2" style="height:10px;">
                <div class="progress-bar bg-success" style="width:<?= $pct ?>%;"></div>
            </div>
            <div class="d-flex justify-content-between small">
                <span class="text-muted"><?= (int)($tagihan_stats['lunas_count']??0) ?> / <?= (int)($tagihan_stats['total_tagihan_count']??0) ?> lunas</span>
                <span class="fw-bold text-success"><?= $pct ?>% terkumpul</span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick links analitik -->
    <div class="col-lg-6">
        <div class="pk-card p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-lightning-charge-fill text-primary me-2"></i>Aksi Cepat</h6>
            <div class="d-grid gap-2">
                <a href="index.php?page=tutor&action=rekap_gaji" class="btn btn-outline-danger btn-sm text-start">
                    <i class="bi bi-cash-stack me-2"></i>Rekap Gaji Tutor Bulan Ini
                </a>
                <a href="index.php?page=attendance" class="btn btn-outline-primary btn-sm text-start">
                    <i class="bi bi-calendar-check me-2"></i>Isi Absensi Siswa
                </a>
                <a href="index.php?page=nilai&action=import" class="btn btn-outline-success btn-sm text-start">
                    <i class="bi bi-upload me-2"></i>Import Nilai dari Excel
                </a>
                <a href="index.php?page=laporan" class="btn btn-outline-secondary btn-sm text-start">
                    <i class="bi bi-download me-2"></i>Download Laporan
                </a>
            </div>
        </div>
    </div>

</div>
<?php endif; ?>

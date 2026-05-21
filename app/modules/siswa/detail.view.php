<?php
/**
 * app/modules/siswa/detail.view.php — Module 6.5
 * Detail siswa dengan lazy loading tab via Fetch API
 * Tab data dimuat dari controller action=tab_data&tab=XXX&id=YYY
 */

// Sisa hari membership
$sisa_hari_m = null;
if (!empty($siswa['tanggal_selesai_aktif'])) {
    $diff = (new DateTime())->diff(new DateTime($siswa['tanggal_selesai_aktif']));
    $sisa_hari_m = ($siswa['tanggal_selesai_aktif'] >= date('Y-m-d')) ? $diff->days : -$diff->days;
}

// Link WA dengan template pesan
$wa_link_siswa = wa_link(
    $siswa['nomor_wa'] ?? '',
    "Halo {$siswa['nama_lengkap']}, ini pesan dari Perkasa Mulia Training Center."
);
?>

<!-- ── Header Profil ─────────────────────────────────────────── -->
<div class="pk-card p-4 mb-4">
    <div class="d-flex flex-wrap align-items-start gap-3">

        <!-- Avatar besar -->
        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
             style="width:72px;height:72px;font-size:1.75rem;">
            <?= strtoupper(substr($siswa['nama_lengkap'], 0, 1)) ?>
        </div>

        <!-- Info utama -->
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 font-monospace">
                    <?= e($siswa['nomor_induk'] ?? '—') ?>
                </span>
                <?= badge_status_siswa($siswa['status_siswa']) ?>
                <?php if ($siswa['target_seleksi']): ?>
                <span class="badge bg-warning bg-opacity-15 text-warning border border-warning border-opacity-25">
                    <i class="bi bi-bullseye me-1"></i><?= e($siswa['target_seleksi']) ?>
                </span>
                <?php endif; ?>
            </div>
            <h4 class="fw-bold mb-1"><?= e($siswa['nama_lengkap']) ?></h4>

            <!-- Membership progress bar -->
            <?php if (!empty($siswa['nama_program'])): ?>
            <div class="mb-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-bold"><?= e($siswa['nama_program']) ?></span>
                    <?php if ($sisa_hari_m !== null): ?>
                    <span class="small <?= $sisa_hari_m < 0 ? 'text-danger' : ($sisa_hari_m <= 7 ? 'text-warning' : 'text-muted') ?>">
                        <?= $sisa_hari_m < 0 ? 'Expired '.abs($sisa_hari_m).' hari lalu' : ($sisa_hari_m === 0 ? 'Berakhir hari ini' : 'Sisa '.$sisa_hari_m.' hari') ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php
                $mulai  = $siswa['tanggal_mulai_aktif'] ?? null;
                $selesai= $siswa['tanggal_selesai_aktif'] ?? null;
                if ($mulai && $selesai) {
                    $total_days   = max(1, (new DateTime($mulai))->diff(new DateTime($selesai))->days);
                    $elapsed_days = max(0, (new DateTime($mulai))->diff(new DateTime())->days);
                    $pct          = min(100, round($elapsed_days / $total_days * 100));
                    $bar_class    = $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success');
                ?>
                <div class="progress rounded-pill" style="height:6px;">
                    <div class="progress-bar <?= $bar_class ?>" style="width:<?= $pct ?>%;"></div>
                </div>
                <div class="d-flex justify-content-between mt-1" style="font-size:.68rem; color:#6c757d;">
                    <span><?= format_tanggal($mulai, 'd M Y') ?></span>
                    <span><?= format_tanggal($selesai, 'd M Y') ?></span>
                </div>
                <?php } ?>
            </div>
            <?php endif; ?>

            <!-- Kontak chips -->
            <div class="d-flex flex-wrap gap-2">
                <?php if ($siswa['nomor_wa']): ?>
                <a href="<?= e($wa_link_siswa) ?>" target="_blank"
                   class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 text-decoration-none">
                    <i class="bi bi-whatsapp me-1"></i><?= e($siswa['nomor_wa']) ?>
                </a>
                <?php endif; ?>
                <?php if ($siswa['nomor_wa_ortu']): ?>
                <a href="<?= e(wa_link($siswa['nomor_wa_ortu'])) ?>" target="_blank"
                   class="badge bg-secondary bg-opacity-10 text-secondary border text-decoration-none">
                    <i class="bi bi-person-fill me-1"></i>Ortu: <?= e($siswa['nomor_wa_ortu']) ?>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tombol aksi -->
        <div class="d-flex gap-2 flex-shrink-0 ms-auto">
            <a href="index.php?page=siswa&action=edit&id=<?= (int)$siswa['id'] ?>"
               class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-fill me-1"></i>Edit
            </a>
            <div class="dropdown">
                <button class="btn btn-light btn-sm border" data-bs-toggle="dropdown" title="Aksi lain">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow rounded-3 border-0 p-1">
                    <?php if ($siswa['nomor_wa']): ?>
                    <li>
                        <a class="dropdown-item small rounded-2 py-2"
                           href="<?= e($wa_link_siswa) ?>" target="_blank">
                            <i class="bi bi-whatsapp text-success me-2"></i>Kirim WA Siswa
                        </a>
                    </li>
                    <?php endif; ?>
                    <li>
                        <a class="dropdown-item small rounded-2 py-2"
                           href="index.php?page=laporan&action=rapor&id=<?= (int)$siswa['id'] ?>">
                            <i class="bi bi-file-earmark-pdf text-danger me-2"></i>Cetak Rapor PDF
                        </a>
                    </li>
                    <li>
                        <!-- Reset Password -->
                        <button class="dropdown-item small rounded-2 py-2"
                                onclick="pkConfirm(
                                    'Reset password akun login siswa &quot;<?= e(addslashes($siswa['nama_lengkap'])) ?>&quot;? Password sementara akan ditampilkan.',
                                    () => document.getElementById('formResetPw').submit(),
                                    { title: 'Reset Password', btnLabel: 'Ya, Reset', btnClass: 'btn-warning' }
                                )">
                            <i class="bi bi-key-fill text-warning me-2"></i>Reset Password Login
                        </button>
                        <form id="formResetPw" method="POST"
                              action="index.php?page=siswa&action=reset_password&id=<?= (int)$siswa['id'] ?>" style="display:none;">
                            <?= csrf_field() ?>
                        </form>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <button class="dropdown-item small rounded-2 py-2 text-danger"
                                onclick="pkConfirm(
                                    'Hapus siswa &quot;<?= e(addslashes($siswa['nama_lengkap'])) ?>&quot;?',
                                    () => location.href='index.php?page=siswa&action=delete&id=<?= (int)$siswa['id'] ?>&_csrf_token=<?= e(csrf_token()) ?>'
                                )">
                            <i class="bi bi-trash me-2"></i>Hapus Siswa
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- ── Tab Navigation ────────────────────────────────────────── -->
<div class="border-bottom mb-4">
    <ul class="nav pk-tabs" id="detailTabs" role="tablist">
        <?php
        $tabs = [
            ['profil',     'bi-person-fill',           'Profil'],
            ['membership', 'bi-journal-bookmark-fill', 'Membership'],
            ['pembayaran', 'bi-receipt',               'Pembayaran'],
            ['nilai',      'bi-bar-chart-fill',        'Nilai'],
            ['kehadiran',  'bi-calendar-check-fill',   'Kehadiran'],
            ['catatan',    'bi-chat-square-text-fill', 'Catatan'],
        ];
        foreach ($tabs as $i => [$tab_id, $icon, $label]):
        ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $i === 0 ? 'active' : '' ?>"
                    id="btn-<?= $tab_id ?>"
                    data-bs-toggle="tab"
                    data-bs-target="#pane-<?= $tab_id ?>"
                    data-tab="<?= $tab_id ?>"
                    type="button" role="tab">
                <i class="bi <?= $icon ?> d-none d-sm-inline me-1"></i><?= $label ?>
            </button>
        </li>
        <?php endforeach; ?>
    </ul>
</div>

<!-- ── Tab Panes ─────────────────────────────────────────────── -->
<div class="tab-content">

    <!-- TAB 1: PROFIL (loaded immediately — no lazy) ─────────── -->
    <div class="tab-pane fade show active" id="pane-profil" role="tabpanel">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="pk-card p-4">
                    <h6 class="fw-bold mb-3 text-muted text-uppercase" style="font-size:.68rem; letter-spacing:.08em;">
                        <i class="bi bi-person-badge me-1"></i>Data Pribadi
                    </h6>
                    <?php
                    $rows = [
                        ['Nomor Induk',    $siswa['nomor_induk'] ?? '—'],
                        ['Nama Lengkap',   $siswa['nama_lengkap']],
                        ['Jenis Kelamin',  $siswa['jenis_kelamin'] === 'L' ? 'Laki-laki' : ($siswa['jenis_kelamin'] === 'P' ? 'Perempuan' : '—')],
                        ['Tanggal Lahir',  $siswa['tanggal_lahir'] ? format_tanggal($siswa['tanggal_lahir']) . ' (' . hitung_umur($siswa['tanggal_lahir']) . ' th)' : '—'],
                        ['Asal Sekolah',   $siswa['asal_sekolah'] ?? '—'],
                        ['Target Seleksi', $siswa['target_seleksi'] ?? '—'],
                        ['Alamat',         $siswa['alamat'] ?? '—'],
                    ];
                    foreach ($rows as [$lbl, $val]):
                    ?>
                    <div class="d-flex gap-3 mb-2 small align-items-start">
                        <div class="text-muted fw-bold flex-shrink-0" style="min-width:120px;"><?= e($lbl) ?></div>
                        <div class="text-dark"><?= e($val) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="pk-card p-4">
                    <h6 class="fw-bold mb-3 text-muted text-uppercase" style="font-size:.68rem; letter-spacing:.08em;">
                        <i class="bi bi-people-fill me-1"></i>Kontak & Keluarga
                    </h6>
                    <?php
                    $rows2 = [
                        ['WA Siswa',       $siswa['nomor_wa'] ?: '—'],
                        ['Nama Ortu/Wali', $siswa['nama_ortu'] ?? '—'],
                        ['WA Ortu/Wali',   $siswa['nomor_wa_ortu'] ?? '—'],
                        ['Status',         $siswa['status_siswa']],
                        ['Ket. Lulus',     $siswa['keterangan_lulus'] ?? '—'],
                        ['Daftar Sejak',   format_tanggal($siswa['created_at'])],
                    ];
                    foreach ($rows2 as [$lbl, $val]):
                    ?>
                    <div class="d-flex gap-3 mb-2 small align-items-start">
                        <div class="text-muted fw-bold flex-shrink-0" style="min-width:120px;"><?= e($lbl) ?></div>
                        <div class="text-dark"><?= e($val) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2–5: LAZY LOADED ─────────────────────────────────── -->
    <?php foreach (['membership', 'pembayaran', 'nilai', 'kehadiran'] as $tid): ?>
    <div class="tab-pane fade" id="pane-<?= $tid ?>" role="tabpanel" data-lazy="false">
        <!-- Skeleton placeholder -->
        <div class="pk-card p-4 lazy-skeleton">
            <div class="pk-skeleton mb-3" style="width:180px;"></div>
            <div class="pk-skeleton mb-2" style="width:100%;height:40px;"></div>
            <div class="pk-skeleton mb-2" style="width:100%;height:40px;"></div>
            <div class="pk-skeleton" style="width:80%;height:40px;"></div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- TAB 6: CATATAN (form → submit langsung) ─────────────── -->
    <div class="tab-pane fade" id="pane-catatan" role="tabpanel">
        <div class="pk-card p-4">
            <h6 class="fw-bold mb-3">
                <i class="bi bi-chat-square-text-fill text-muted me-2"></i>Catatan Perkembangan
            </h6>
            <form method="POST" action="index.php?page=siswa&action=update&id=<?= (int)$siswa['id'] ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="nama"   value="<?= e($siswa['nama_lengkap']) ?>">
                <input type="hidden" name="wa"     value="<?= e($siswa['nomor_wa']) ?>">
                <input type="hidden" name="status" value="<?= e($siswa['status_siswa']) ?>">
                <textarea name="catatan" class="form-control mb-3" rows="8"
                          placeholder="Catatan perkembangan, kendala, rekomendasi tutor, dll..."><?= e($siswa['catatan'] ?? '') ?></textarea>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>Simpan Catatan
                </button>
            </form>
        </div>
    </div>
</div>

<style>
/* Skeleton */
@keyframes pk-shimmer { 0%{background-position:-200% 0} 100%{background-position:200% 0} }
.pk-skeleton {
    background: linear-gradient(90deg,#e9ecef 25%,#f8f9fa 50%,#e9ecef 75%);
    background-size: 200% 100%;
    animation: pk-shimmer 1.4s infinite;
    border-radius: 4px; height: 14px;
}
</style>

<script>
(function() {
    const SISWA_ID = <?= (int)$siswa['id'] ?>;
    const loaded   = {};

    // Render functions per tab
    const renderers = {
        membership: renderMembership,
        pembayaran: renderPembayaran,
        nilai:      renderNilai,
        kehadiran:  renderKehadiran,
    };

    // On tab show → lazy load
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', e => {
            const tab  = e.target.dataset.tab;
            const pane = document.getElementById('pane-' + tab);
            if (!pane || loaded[tab]) return;

            const skeleton = pane.querySelector('.lazy-skeleton');
            if (!skeleton) return;

            fetch(`index.php?page=siswa&action=tab_data&id=${SISWA_ID}&tab=${tab}`)
                .then(r => r.json())
                .then(res => {
                    if (!res.ok) throw new Error('Server error');
                    if (renderers[tab]) {
                        skeleton.outerHTML = renderers[tab](res.data);
                    }
                    loaded[tab] = true;
                })
                .catch(() => {
                    if (skeleton) skeleton.innerHTML = '<p class="text-danger small">Gagal memuat data. <a href="javascript:void(0)" onclick="location.reload()">Coba lagi</a></p>';
                });
        });
    });

    // ── Render: Membership ───────────────────────────────────
    function renderMembership(rows) {
        if (!rows || rows.length === 0) return emptyState('journal-x', 'Belum ada riwayat membership.');

        // Separator by tipe
        const tipeLabel = {'Program':'Program Reguler','Fasilitas':'Fasilitas','Paket':'Paket Bundel'};
        const tipeIcon  = {'Program':'bi-calendar-check-fill','Fasilitas':'bi-house-fill','Paket':'bi-box-seam-fill'};
        const tipeColor = {'Program':'#0d6efd','Fasilitas':'#b8860b','Paket':'#6f42c1'};

        // Group by tipe
        const grp = {};
        rows.forEach(m => { const t=m.tipe_program||'Program'; (grp[t]=grp[t]||[]).push(m); });

        let html = `<div class="d-flex justify-content-end mb-2">
            <a href="index.php?page=pembayaran&action=create" class="btn btn-success btn-sm">
                <i class="bi bi-cash-coin me-1"></i>Bayar SPP
            </a>
        </div>`;

        ['Program','Fasilitas','Paket'].forEach(t => {
            if (!grp[t]) return;
            html += `<div class="pk-card overflow-hidden mb-3">
                <div class="px-3 py-2 border-bottom d-flex align-items-center gap-2">
                    <i class="bi ${tipeIcon[t]||'bi-collection'}" style="color:${tipeColor[t]||'#333'};"></i>
                    <h6 class="fw-bold mb-0 small" style="color:${tipeColor[t]||'#333'};">${tipeLabel[t]||t}</h6>
                    <span class="badge ms-auto" style="background:rgba(0,0,0,.06);color:#555;">${grp[t].length} program</span>
                </div>
                <table class="table pk-table small mb-0">
                <thead class="table-light"><tr>
                    <th class="ps-3">Program</th><th>Mulai</th><th>Selesai</th>
                    <th>Status</th><th>Harga/Bln</th><th class="pe-3">Default</th>
                </tr></thead><tbody>`;

            grp[t].forEach(m => {
                const sc    = m.status_membership === 'Berjalan' ? 'bg-success' :
                              m.status_membership === 'Selesai'  ? 'bg-secondary' : 'bg-warning text-dark';
                const biaya = m.biaya_aktif ? 'Rp '+Number(m.biaya_aktif).toLocaleString('id-ID') : '—';
                const def   = m.biaya_default ? 'Rp '+Number(m.biaya_default).toLocaleString('id-ID') : '—';
                const custom= m.biaya_per_bulan && m.biaya_per_bulan != m.biaya_default
                    ? `<span class="badge bg-warning bg-opacity-15 text-warning border" style="font-size:.6rem;">custom</span>`
                    : '';
                html += `<tr>
                    <td class="ps-3 fw-bold">${esc(m.nama_program||'—')}</td>
                    <td class="text-muted">${formatTgl(m.tanggal_mulai_aktif)}</td>
                    <td class="text-muted">${formatTgl(m.tanggal_selesai_aktif)}</td>
                    <td><span class="badge ${sc}">${esc(m.status_membership)}</span></td>
                    <td class="fw-bold text-success">${biaya} ${custom}</td>
                    <td class="pe-3 text-muted" style="font-size:.7rem;">${def}</td>
                </tr>`;
            });
            html += `</tbody></table></div>`;
        });
        return html;
    }

    // ── Render: Pembayaran ───────────────────────────────────
    function renderPembayaran(rows) {
        if (!rows || rows.length === 0) return emptyState('receipt', 'Belum ada data pembayaran.');
        let html = `<div class="pk-card overflow-hidden">
            <table class="table pk-table small mb-0">
            <thead class="table-light"><tr>
                <th class="ps-3">Periode</th><th>Program</th>
                <th class="text-end">Tagihan</th><th class="text-end">Bayar</th>
                <th>Status</th><th>Tgl Bayar</th>
            </tr></thead><tbody>`;

        let totalTagihan = 0, totalBayar = 0;
        rows.forEach(p => {
            totalTagihan += parseFloat(p.nominal_tagihan) || 0;
            totalBayar   += parseFloat(p.nominal_bayar)   || 0;
            const sc = p.status_bayar === 'Lunas' ? 'bg-success' :
                       p.status_bayar === 'Cicilan' ? 'bg-warning text-dark' : 'bg-danger';
            html += `<tr>
                <td class="ps-3 fw-bold">${formatBulan(p.periode_bulan)}</td>
                <td><span class="badge bg-info bg-opacity-15 text-info border border-info border-opacity-25">${esc(p.nama_program || '—')}</span></td>
                <td class="text-end">Rp ${Number(p.nominal_tagihan).toLocaleString('id-ID')}</td>
                <td class="text-end fw-bold">Rp ${Number(p.nominal_bayar).toLocaleString('id-ID')}</td>
                <td><span class="badge ${sc}">${esc(p.status_bayar)}</span></td>
                <td class="text-muted">${p.tanggal_bayar ? formatTgl(p.tanggal_bayar) : '—'}</td>
            </tr>`;
        });

        html += `</tbody><tfoot class="table-light">
            <tr>
                <td colspan="2" class="fw-bold ps-3 text-end small">Total:</td>
                <td class="text-end fw-bold small">Rp ${totalTagihan.toLocaleString('id-ID')}</td>
                <td class="text-end fw-bold small text-success">Rp ${totalBayar.toLocaleString('id-ID')}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot></table></div>`;
        return html;
    }

    // ── Render: Nilai ────────────────────────────────────────
    function renderNilai(data) {
        const hasAkademik  = data.akademik?.length  > 0;
        const hasBinjas    = data.binjas?.length    > 0;
        const hasMapel     = data.mapel?.length     > 0;
        const hasPsikologi = data.psikologi?.length > 0;

        if (!hasAkademik && !hasBinjas && !hasMapel && !hasPsikologi) {
            return emptyState('clipboard-x', 'Belum ada data nilai.');
        }

        const chartId = 'chartNilai_' + Date.now();

        // ── Helper: build chart datasets per type ──────────────
        function getChartDataset(type) {
            let entries = [], label = '', color = '#0d6efd', passing = null;
            if (type === 'skd' && hasAkademik) {
                entries = [...data.akademik].reverse();
                label   = 'Total Skor SKD';
                color   = '#0d6efd';
                passing = 311;
                return {
                    labels : entries.map(n => n.tanggal_tes),
                    values : entries.map(n => parseInt(n.total_skor) || 0),
                    ptColors: entries.map(n => (parseInt(n.total_skor)||0) >= 311 ? '#198754' : '#dc3545'),
                    label, color, passing
                };
            }
            if (type === 'jasmani' && hasBinjas) {
                entries = [...data.binjas].reverse();
                label   = 'Skor Jasmani';
                color   = '#198754';
                passing = 61;
                return {
                    labels : entries.map(n => n.tanggal_tes),
                    values : entries.map(n => parseFloat(n.skor_akhir) || 0),
                    ptColors: entries.map(n => (parseFloat(n.skor_akhir)||0) >= 61 ? '#198754' : '#dc3545'),
                    label, color, passing
                };
            }
            if (type === 'mapel' && hasMapel) {
                entries = [...data.mapel].reverse();
                label   = 'Rata-rata Mapel';
                color   = '#6f42c1';
                passing = 61;
                const vals = entries.map(n => {
                    const nums = [n.bahasa_indonesia, n.bahasa_inggris, n.matematika, n.pengetahuan_umum, n.wawasan_kebangsaan]
                        .map(v => parseFloat(v) || 0).filter(v => v > 0);
                    return nums.length ? Math.round(nums.reduce((a,b) => a+b,0) / nums.length * 10) / 10 : 0;
                });
                return {
                    labels : entries.map(n => n.tanggal_tes),
                    values : vals,
                    ptColors: vals.map(v => v >= 61 ? '#6f42c1' : '#dc3545'),
                    label, color, passing
                };
            }
            if (type === 'psikologi' && hasPsikologi) {
                entries = [...data.psikologi].reverse();
                label   = 'Rata Psikologi';
                color   = '#fd7e14';
                passing = 61;
                return {
                    labels : entries.map(n => n.tanggal_tes),
                    values : entries.map(n => parseFloat(n.rata_psikologi) || 0),
                    ptColors: entries.map(n => (parseFloat(n.rata_psikologi)||0) >= 61 ? '#fd7e14' : '#dc3545'),
                    label, color, passing
                };
            }
            return null;
        }

        // ── Determine default chart type ──────────────────────
        const chartTypes = [
            { key: 'jasmani',   label: 'Jasmani',   icon: 'bi-lightning-charge-fill', color: '#198754', has: hasBinjas },
            { key: 'skd',       label: 'SKD',        icon: 'bi-clipboard2-check-fill', color: '#0d6efd', has: hasAkademik },
            { key: 'mapel',     label: 'Mapel',      icon: 'bi-book-fill',             color: '#6f42c1', has: hasMapel },
            { key: 'psikologi', label: 'Psikologi',  icon: 'bi-brain',                 color: '#fd7e14', has: hasPsikologi },
        ].filter(t => t.has);

        let activeType = chartTypes.length ? chartTypes[0].key : null;
        let chartInstance = null;

        // ── Selector + Chart container ────────────────────────
        let html = `<div class="pk-card p-4 mb-3" id="chartBox_${chartId}">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h6 class="fw-bold mb-0">Trend Skor</h6>
                <div class="btn-group btn-group-sm" id="chartTypeSelector_${chartId}">`;

        chartTypes.forEach((t, i) => {
            html += `<button type="button"
                class="btn ${i===0?'btn-outline-'+colorName(t.color)+' active':'btn-outline-secondary'}"
                data-type="${t.key}"
                style="font-size:.75rem;${i===0?'background:'+t.color+';color:#fff;border-color:'+t.color+';':''}"
                onclick="switchChart_${chartId}(this,'${t.key}')">
                <i class="bi ${t.icon} me-1"></i>${t.label}
            </button>`;
        });

        html += `</div></div>
            <div style="position:relative;height:160px;">
                <canvas id="${chartId}" role="img" aria-label="Trend skor"></canvas>
            </div>
        </div>`;

        html += `<div class="row g-3">`;

        // ── Jasmani table ─────────────────────────────────────
        if (hasBinjas) {
            html += `<div class="col-12"><div class="pk-card overflow-hidden">
                <div class="px-3 py-2 border-bottom d-flex align-items-center gap-2">
                    <span class="badge rounded-pill" style="background:#d1e7dd;color:#198754;font-size:.65rem;">
                        <i class="bi bi-lightning-charge-fill me-1"></i>Jasmani
                    </span>
                    <h6 class="fw-bold mb-0 small">Nilai Jasmani (Samapta)</h6>
                </div>
                <div class="table-responsive">
                <table class="table pk-table small mb-0" style="font-size:.78rem;">
                <thead class="table-light"><tr>
                    <th class="ps-3" style="white-space:nowrap;">Tanggal</th>
                    <th class="text-center" style="white-space:nowrap;">Lari<br><small class="text-muted fw-normal">(m → skor)</small></th>
                    <th class="text-center" style="white-space:nowrap;">Pull-Up<br><small class="text-muted fw-normal">(reps → skor)</small></th>
                    <th class="text-center" style="white-space:nowrap;">Sit-Up<br><small class="text-muted fw-normal">(reps → skor)</small></th>
                    <th class="text-center" style="white-space:nowrap;">Push-Up<br><small class="text-muted fw-normal">(reps → skor)</small></th>
                    <th class="text-center" style="white-space:nowrap;">Shuttle<br><small class="text-muted fw-normal">(s → skor)</small></th>
                    <th class="text-center" style="white-space:nowrap;">Lunges<br><small class="text-muted fw-normal">(reps → skor)</small></th>
                    <th class="text-center" style="white-space:nowrap;">Renang<br><small class="text-muted fw-normal">(s → skor)</small></th>
                    <th class="text-center" style="white-space:nowrap;color:#198754;">Smt A/B/AB</th>
                    <th class="text-center fw-bold" style="color:#198754;white-space:nowrap;">Nilai</th>
                </tr></thead><tbody>`;

            const fmtCell = (raw, skor, suffix='') => {
                const hasRaw  = raw  != null && parseFloat(raw)  > 0;
                const hasSkor = skor != null && parseInt(skor)   > 0;
                if (!hasRaw && !hasSkor) return `<span class="text-muted">—</span>`;
                const rawStr  = hasRaw  ? `<span style="color:#555;">${parseFloat(raw)%1===0?parseInt(raw):parseFloat(raw).toFixed(1)}${suffix}</span>` : `<span class="text-muted">—</span>`;
                const skorStr = hasSkor ? `<strong style="color:#198754;">${parseInt(skor)}</strong>` : `<span style="color:#bbb;">?</span>`;
                return `${rawStr}<br><small style="font-size:.67rem;">→ ${skorStr}</small>`;
            };

            data.binjas.forEach(b => {
                const skor    = parseFloat(b.skor_akhir) || 0;
                const lulus   = skor >= 61;
                const predColor = {
                    'Baik Sekali': ['#198754','#d1e7dd'],
                    'Baik':        ['#0d6efd','#cfe2ff'],
                    'Cukup':       ['#fd7e14','#fff3cd'],
                }[b.predikat] || ['#6c757d','#e9ecef'];

                const smtA   = b.nilai_samapta_a != null ? parseFloat(b.nilai_samapta_a).toFixed(1) : null;
                const smtB   = b.nilai_samapta_b != null ? parseFloat(b.nilai_samapta_b).toFixed(1) : null;
                const nilAB  = b.nilai_ab != null ? parseFloat(b.nilai_ab).toFixed(1) : (skor > 0 ? skor.toFixed(1) : null);
                const nilGab = b.nilai_gabungan != null ? `<span class="badge bg-success" style="font-size:.6rem;">Gab: ${parseFloat(b.nilai_gabungan).toFixed(1)}</span>` : '';

                const smtCell = [
                    smtA  ? `<div style="font-size:.7rem;"><span class="text-muted">A:</span> <strong style="color:#198754;">${smtA}</strong></div>` : '',
                    smtB  ? `<div style="font-size:.7rem;"><span class="text-muted">B:</span> <strong style="color:#0d6efd;">${smtB}</strong></div>` : '',
                    nilAB ? `<div style="font-size:.7rem;"><span class="text-muted">AB:</span> <strong>${nilAB}</strong></div>` : '',
                    nilGab,
                ].join('');

                html += `<tr>
                    <td class="ps-3 text-muted" style="white-space:nowrap;">${formatTgl(b.tanggal_tes)}</td>
                    <td class="text-center">${fmtCell(b.lari_jarak_meter, b.skor_lari, 'm')}</td>
                    <td class="text-center">${fmtCell(b.pullup_repetisi, b.skor_pullup)}</td>
                    <td class="text-center">${fmtCell(b.situp_repetisi, b.skor_situp)}</td>
                    <td class="text-center">${fmtCell(b.pushup_repetisi, b.skor_pushup)}</td>
                    <td class="text-center">${fmtCell(b.shuttlerun_detik, b.skor_shuttle, 's')}</td>
                    <td class="text-center">${fmtCell(b.lunges_repetisi, b.skor_lunges)}</td>
                    <td class="text-center">${fmtCell(b.renang_detik, b.skor_renang, 's')}</td>
                    <td class="text-center" style="line-height:1.6;">${smtCell || '<span class="text-muted">—</span>'}</td>
                    <td class="text-center">
                        <div class="fw-bold" style="color:${lulus?'#198754':'#dc3545'};font-size:.9rem;">${skor > 0 ? skor.toFixed(1) : '—'}</div>
                        ${b.predikat ? `<span class="badge" style="background:${predColor[1]};color:${predColor[0]};border:1px solid ${predColor[0]};font-size:.6rem;">${esc(b.predikat)}</span>` : ''}
                    </td>
                </tr>`;
            });

            html += `</tbody></table></div></div></div>`;
        }

        // ── Akademik (SKD) table ──────────────────────────────
        html += `<div class="col-md-7"><div class="pk-card overflow-hidden">
            <div class="px-3 py-2 border-bottom d-flex align-items-center gap-2">
                <span class="badge rounded-pill" style="background:#cfe2ff;color:#0d6efd;font-size:.65rem;">
                    <i class="bi bi-clipboard2-check-fill me-1"></i>SKD
                </span>
                <h6 class="fw-bold mb-0 small">Nilai Akademik (SKD)</h6>
            </div>
            <table class="table pk-table small mb-0">
            <thead class="table-light"><tr>
                <th class="ps-3">Tanggal</th><th class="text-center">TWK</th>
                <th class="text-center">TIU</th><th class="text-center">TKP</th>
                <th class="text-center fw-bold">Total</th><th>Hasil</th>
            </tr></thead><tbody>`;

        if (!hasAkademik) {
            html += `<tr><td colspan="6" class="text-center text-muted py-3 small">Belum ada data</td></tr>`;
        } else {
            data.akademik.forEach(n => {
                const total  = parseInt(n.total_skor) || 0;
                const lulus  = total >= 311;
                const badgec = lulus ? 'bg-success' : 'bg-danger';
                html += `<tr>
                    <td class="ps-3 text-muted">${formatTgl(n.tanggal_tes)}</td>
                    <td class="text-center">${n.nilai_twk}</td>
                    <td class="text-center">${n.nilai_tiu}</td>
                    <td class="text-center">${n.nilai_tkp}</td>
                    <td class="text-center fw-bold text-primary">${n.total_skor}</td>
                    <td><span class="badge ${badgec}" style="font-size:.65rem;">${lulus?'Lulus':'Tidak Lulus'}</span></td>
                </tr>`;
            });
        }
        html += `</tbody></table></div></div>`;

        // ── Psikologi table ───────────────────────────────────
        html += `<div class="col-md-5"><div class="pk-card overflow-hidden">
            <div class="px-3 py-2 border-bottom d-flex align-items-center gap-2">
                <span class="badge rounded-pill" style="background:#fff3cd;color:#fd7e14;font-size:.65rem;">
                    <i class="bi bi-brain me-1"></i>Psikologi
                </span>
                <h6 class="fw-bold mb-0 small">Nilai Psikologi</h6>
            </div>
            <table class="table pk-table small mb-0">
            <thead class="table-light"><tr>
                <th class="ps-3">Tanggal</th><th class="text-center">Kec.</th>
                <th class="text-center">Kcm.</th><th class="text-center">Kpr.</th>
                <th class="text-center fw-bold">Rata</th><th>Status</th>
            </tr></thead><tbody>`;

        if (!hasPsikologi) {
            html += `<tr><td colspan="6" class="text-center text-muted py-3 small">Belum ada data</td></tr>`;
        } else {
            data.psikologi.forEach(p => {
                const lulus = p.status_psikologi === 'Lulus';
                html += `<tr>
                    <td class="ps-3 text-muted">${formatTgl(p.tanggal_tes)}</td>
                    <td class="text-center">${p.kecerdasan ?? '—'}</td>
                    <td class="text-center">${p.kecermatan ?? '—'}</td>
                    <td class="text-center">${p.kepribadian ?? '—'}</td>
                    <td class="text-center fw-bold" style="color:#fd7e14;">${parseFloat(p.rata_psikologi)?.toFixed(1) ?? '—'}</td>
                    <td><span class="badge ${lulus?'bg-success':'bg-danger'}" style="font-size:.65rem;">${esc(p.status_psikologi ?? '—')}</span></td>
                </tr>`;
            });
        }
        html += `</tbody></table></div></div>`;

        // ── Mapel table ───────────────────────────────────────
        html += `<div class="col-12"><div class="pk-card overflow-hidden">
            <div class="px-3 py-2 border-bottom d-flex align-items-center gap-2">
                <span class="badge rounded-pill" style="background:#e2d9f3;color:#6f42c1;font-size:.65rem;">
                    <i class="bi bi-book-fill me-1"></i>Mapel
                </span>
                <h6 class="fw-bold mb-0 small">Nilai Mata Pelajaran</h6>
            </div>
            <table class="table pk-table small mb-0">
            <thead class="table-light"><tr>
                <th class="ps-3">Tanggal</th><th class="text-center">B.Indo</th>
                <th class="text-center">B.Ing</th><th class="text-center">Mat</th>
                <th class="text-center">PU</th><th class="text-center">WK</th>
            </tr></thead><tbody>`;

        if (!hasMapel) {
            html += `<tr><td colspan="6" class="text-center text-muted py-3 small">Belum ada data</td></tr>`;
        } else {
            data.mapel.forEach(m => {
                const fmt = v => (v != null && parseFloat(v) > 0)
                    ? `<span style="color:#6f42c1;">${parseFloat(v).toFixed(1)}</span>`
                    : '<span class="text-muted">—</span>';
                html += `<tr>
                    <td class="ps-3 text-muted">${formatTgl(m.tanggal_tes)}</td>
                    <td class="text-center">${fmt(m.bahasa_indonesia)}</td>
                    <td class="text-center">${fmt(m.bahasa_inggris)}</td>
                    <td class="text-center">${fmt(m.matematika)}</td>
                    <td class="text-center">${fmt(m.pengetahuan_umum)}</td>
                    <td class="text-center">${fmt(m.wawasan_kebangsaan)}</td>
                </tr>`;
            });
        }
        html += `</tbody></table></div></div>`;

        html += `</div>`; // end row

        // ── Chart init + switch logic ─────────────────────────
        if (activeType) {
            const allDatasets = {};
            chartTypes.forEach(t => {
                allDatasets[t.key] = getChartDataset(t.key);
            });

            setTimeout(() => {
                const canvas = document.getElementById(chartId);
                if (!canvas || typeof Chart === 'undefined') return;

                function buildChart(type) {
                    const ds = allDatasets[type];
                    if (!ds) return;
                    if (chartInstance) chartInstance.destroy();
                    chartInstance = new Chart(canvas, {
                        type: 'line',
                        data: {
                            labels: ds.labels,
                            datasets: [{
                                label: ds.label,
                                data: ds.values,
                                borderColor: ds.color,
                                backgroundColor: ds.color + '18',
                                tension: .3, fill: true,
                                pointBackgroundColor: ds.ptColors,
                                pointRadius: 5,
                            }]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: false, ticks: { font: { size: 10 } } } }
                        }
                    });
                }

                // Attach global switch function
                window[`switchChart_${chartId}`] = function(btn, type) {
                    document.querySelectorAll(`#chartTypeSelector_${chartId} button`).forEach(b => {
                        b.classList.remove('active');
                        b.style.background = '';
                        b.style.color = '';
                        b.className = b.className.replace(/btn-outline-\S+/, 'btn-outline-secondary');
                    });
                    const typeInfo = chartTypes.find(t => t.key === type);
                    if (typeInfo) {
                        btn.style.background    = typeInfo.color;
                        btn.style.color         = '#fff';
                        btn.style.borderColor   = typeInfo.color;
                        btn.classList.add('active');
                    }
                    buildChart(type);
                };

                buildChart(activeType);
            }, 100);
        }

        return html;
    }

    // Helper to convert hex color to Bootstrap color name (approx)
    function colorName(hex) {
        const map = {'#198754':'success','#0d6efd':'primary','#6f42c1':'purple','#fd7e14':'warning','#0dcaf0':'info','#dc3545':'danger'};
        return map[hex] || 'secondary';
    }

    // ── Render: Kehadiran ────────────────────────────────────
    function renderKehadiran(data) {
        if (!data || data.total === 0) return emptyState('calendar-x', 'Belum ada data kehadiran.');

        const badgeColor = data.pct >= 80 ? 'bg-success' : data.pct >= 60 ? 'bg-warning text-dark' : 'bg-danger';
        const barColor   = data.pct >= 80 ? '#198754'  : data.pct >= 60 ? '#f59e0b'  : '#dc3545';

        let html = `<div class="pk-card p-4 mb-3">
            <div class="d-flex align-items-center gap-4">
                <div class="text-center">
                    <div class="fw-bold" style="font-size:2.2rem;line-height:1;">${data.pct}%</div>
                    <span class="badge ${badgeColor} mt-1">Kehadiran</span>
                </div>
                <div class="flex-grow-1">
                    <div class="progress rounded-pill mb-2" style="height:10px;">
                        <div class="progress-bar" style="width:${data.pct}%;background:${barColor};"></div>
                    </div>
                    <div class="text-muted small">
                        ${data.hadir} hadir dari ${data.total} sesi tercatat
                    </div>
                </div>
            </div>
        </div>`;

        html += `<div class="pk-card overflow-hidden"><table class="table pk-table small mb-0">
            <thead class="table-light"><tr>
                <th class="ps-3">Tanggal</th><th>Kegiatan</th><th>Jam</th><th>Status</th>
            </tr></thead><tbody>`;

        data.rows.forEach(k => {
            const sc = k.status_hadir === 'Hadir' ? 'bg-success' :
                       k.status_hadir === 'Izin'  ? 'bg-warning text-dark' :
                       k.status_hadir === 'Sakit' ? 'bg-info text-dark' : 'bg-secondary';
            html += `<tr>
                <td class="ps-3">${formatTgl(k.tanggal)}</td>
                <td>${esc(k.nama_kegiatan || k.materi || '—')}</td>
                <td class="text-muted">${(k.waktu_mulai||'').substring(0,5)}</td>
                <td><span class="badge ${sc}" style="font-size:.65rem;">${esc(k.status_hadir)}</span></td>
            </tr>`;
        });
        return html + '</tbody></table></div>';
    }

    // ── Utils ────────────────────────────────────────────────
    function emptyState(icon, msg) {
        return `<div class="pk-card p-5 text-center text-muted">
            <i class="bi bi-${icon} d-block mb-2" style="font-size:2.5rem;opacity:.3;"></i>
            <p class="small mb-0">${msg}</p>
        </div>`;
    }

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    function formatTgl(str) {
        if (!str) return '—';
        const d = new Date(str);
        if (isNaN(d)) return str;
        const bln = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        return `${String(d.getDate()).padStart(2,'0')} ${bln[d.getMonth()]} ${d.getFullYear()}`;
    }

    function formatBulan(str) {
        if (!str) return '—';
        const d = new Date(str);
        const bln = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        return `${bln[d.getMonth()]} ${d.getFullYear()}`;
    }
})();
</script>

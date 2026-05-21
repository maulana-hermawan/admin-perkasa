<?php
/**
 * app/modules/tutor/rekap_tutor.view.php
 * Rekap Tutor — filter: tutor, program, tanggal, jenis kegiatan
 */

$WARNA_KEGIATAN = [
    'Jasmani'   => ['bg'=>'#d1e7dd','border'=>'#198754','text'=>'#0a5c36','badge'=>'success'],
    'Renang'    => ['bg'=>'#cff4fc','border'=>'#0dcaf0','text'=>'#055160','badge'=>'info'],
    'Akademik'  => ['bg'=>'#cfe2ff','border'=>'#0d6efd','text'=>'#084298','badge'=>'primary'],
    'Psikologi' => ['bg'=>'#e2d9f3','border'=>'#6f42c1','text'=>'#3d1a78','badge'=>'purple'],
    'Tryout'    => ['bg'=>'#fff3cd','border'=>'#fd7e14','text'=>'#6f4800','badge'=>'warning'],
    'default'   => ['bg'=>'#e9ecef','border'=>'#6c757d','text'=>'#343a40','badge'=>'secondary'],
];

function kegiatan_badge(string $k, array $warna): string {
    $w = $warna[$k] ?? $warna['default'];
    return "<span class=\"badge\" style=\"background:{$w['bg']};color:{$w['text']};border:1px solid {$w['border']};font-size:.68rem;\">{$k}</span>";
}
?>

<style>
.rekap-filter-card { background:#fff; border:1px solid #e3e7ef; border-radius:12px; padding:1.25rem; margin-bottom:1.25rem; }
.rekap-stat        { background:#fff; border:1px solid #e3e7ef; border-radius:10px; padding:1rem 1.25rem; }
.rekap-stat .val   { font-size:1.6rem; font-weight:800; line-height:1; margin-bottom:.15rem; }
.rekap-stat .lbl   { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#888; }
.rekap-stat .icon  { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; }
.tutor-card        { background:#fff; border:1px solid #e3e7ef; border-radius:10px; overflow:hidden; transition:box-shadow .2s; }
.tutor-card:hover  { box-shadow:0 4px 16px rgba(0,18,51,.08); }
.tutor-card-head   { background:linear-gradient(135deg,#001233,#0a2a5e); color:#fff; padding:.85rem 1rem; }
.tutor-avatar      { width:38px; height:38px; border-radius:50%; background:rgba(255,255,255,.2); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1rem; flex-shrink:0; }
.sesi-badge        { background:#0d6efd; color:#fff; border-radius:6px; padding:.25rem .6rem; font-size:.78rem; font-weight:700; }
.honor-txt         { font-size:.85rem; color:rgba(255,255,255,.8); }
.tutor-card-body   { padding:.85rem 1rem; }
.detail-table th   { font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; background:#f8f9fb; }
.detail-table td   { font-size:.82rem; vertical-align:middle; }
.badge-purple      { background:#e2d9f3; color:#3d1a78; border:1px solid #6f42c1; }
.print-hide        { }
@media print {
    .print-hide { display:none !important; }
    .rekap-filter-card { display:none !important; }
    body { font-size:12px; }
}
</style>

<!-- ── Header ─────────────────────────────────────────────── -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0">
            <i class="bi bi-person-video3 me-2 text-primary"></i>Rekap Tutor
        </h4>
        <small class="text-muted">
            Rekap kehadiran & sesi mengajar tutor berdasarkan jadwal terealisasi.
        </small>
    </div>
    <div class="d-flex gap-2 print-hide">
        <a href="index.php?page=tutor&action=rekap_gaji" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-cash-stack me-1"></i>Rekap Gaji
        </a>
        <a href="index.php?page=tutor" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<!-- ── Filter ─────────────────────────────────────────────── -->
<div class="rekap-filter-card print-hide">
    <form method="GET" id="formFilter">
        <input type="hidden" name="page"   value="tutor">
        <input type="hidden" name="action" value="rekap_tutor">
        <div class="row g-2 align-items-end">

            <!-- Tutor -->
            <div class="col-sm-6 col-lg-3">
                <label class="form-label small fw-bold mb-1">
                    <i class="bi bi-person-badge me-1 text-primary"></i>Tutor
                </label>
                <select name="f_tutor" class="form-select form-select-sm">
                    <option value="0">— Semua Tutor —</option>
                    <?php foreach ($daftar_tutor_filter as $t): ?>
                    <option value="<?= (int)$t['id'] ?>" <?= $f_tutor===(int)$t['id']?'selected':'' ?>>
                        <?= e($t['nama_lengkap']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Program -->
            <div class="col-sm-6 col-lg-3">
                <label class="form-label small fw-bold mb-1">
                    <i class="bi bi-mortarboard me-1 text-success"></i>Program
                </label>
                <select name="f_program" class="form-select form-select-sm">
                    <option value="0">— Semua Program —</option>
                    <?php foreach ($daftar_program_filter as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= $f_program===(int)$p['id']?'selected':'' ?>>
                        <?= e($p['nama_program']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Jenis Kegiatan -->
            <div class="col-sm-6 col-lg-2">
                <label class="form-label small fw-bold mb-1">
                    <i class="bi bi-tags me-1 text-warning"></i>Jenis Kegiatan
                </label>
                <select name="f_kegiatan" class="form-select form-select-sm">
                    <option value="">— Semua —</option>
                    <?php foreach ($daftar_kegiatan as $k): ?>
                    <option value="<?= e($k) ?>" <?= $f_kegiatan===$k?'selected':'' ?>><?= e($k) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dari -->
            <div class="col-sm-3 col-lg-2">
                <label class="form-label small fw-bold mb-1">
                    <i class="bi bi-calendar-event me-1 text-muted"></i>Dari
                </label>
                <input type="date" name="f_dari" class="form-control form-control-sm"
                       value="<?= e($f_dari) ?>">
            </div>

            <!-- Sampai -->
            <div class="col-sm-3 col-lg-2">
                <label class="form-label small fw-bold mb-1">
                    <i class="bi bi-calendar-event me-1 text-muted"></i>Sampai
                </label>
                <input type="date" name="f_sampai" class="form-control form-control-sm"
                       value="<?= e($f_sampai) ?>">
            </div>
        </div>

        <!-- Tombol -->
        <div class="d-flex gap-2 mt-2 flex-wrap">
            <button type="submit" class="btn btn-primary btn-sm px-3">
                <i class="bi bi-funnel-fill me-1"></i>Terapkan Filter
            </button>
            <a href="index.php?page=tutor&action=rekap_tutor" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-circle me-1"></i>Reset
            </a>
            <a href="<?= 'index.php?page=tutor&action=export_rekap_tutor&f_dari='.e($f_dari).'&f_sampai='.e($f_sampai).'&f_tutor='.$f_tutor.'&f_program='.$f_program.'&f_kegiatan='.e($f_kegiatan) ?>"
               class="btn btn-outline-success btn-sm ms-auto">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
            </a>
            <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
    </form>

    <!-- Info periode aktif -->
    <div class="mt-2 pt-2 border-top">
        <small class="text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Periode: <strong><?= format_tanggal($f_dari,'d M Y') ?></strong>
            s/d <strong><?= format_tanggal($f_sampai,'d M Y') ?></strong>
            <?php if ($f_tutor || $f_program || $f_kegiatan): ?>
            &nbsp;·&nbsp;
            <?php if ($f_tutor): ?>
                Tutor: <strong><?= e(array_column($daftar_tutor_filter,'nama_lengkap','id')[$f_tutor] ?? '?') ?></strong>
            <?php endif; ?>
            <?php if ($f_program): ?>
                Program: <strong><?= e(array_column($daftar_program_filter,'nama_program','id')[$f_program] ?? '?') ?></strong>
            <?php endif; ?>
            <?php if ($f_kegiatan): ?>
                Kegiatan: <strong><?= e($f_kegiatan) ?></strong>
            <?php endif; ?>
            <?php endif; ?>
        </small>
    </div>
</div>

<?php if (empty($rekap_per_tutor)): ?>
<!-- Empty State -->
<div class="pk-card text-center py-5">
    <i class="bi bi-person-x d-block mb-3 text-muted" style="font-size:3rem;opacity:.3;"></i>
    <h6 class="text-muted mb-1">Tidak ada data tutor</h6>
    <small class="text-muted">Coba ubah filter atau rentang tanggal.</small>
</div>
<?php else: ?>

<!-- ── Stat Cards ──────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="rekap-stat d-flex justify-content-between align-items-center gap-2">
            <div>
                <div class="val text-primary"><?= $total_sesi ?></div>
                <div class="lbl">Total Sesi</div>
            </div>
            <div class="icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-calendar-check-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="rekap-stat d-flex justify-content-between align-items-center gap-2">
            <div>
                <div class="val text-success"><?= $total_tutor ?></div>
                <div class="lbl">Tutor Mengajar</div>
            </div>
            <div class="icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-person-badge-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="rekap-stat d-flex justify-content-between align-items-center gap-2">
            <div>
                <div class="val text-warning" style="font-size:1.1rem;">
                    <?= format_rupiah($total_honor) ?>
                </div>
                <div class="lbl">Estimasi Honor</div>
            </div>
            <div class="icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-wallet2"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="rekap-stat d-flex justify-content-between align-items-center gap-2">
            <div>
                <div class="val text-info"><?= count($breakdown_kegiatan) ?></div>
                <div class="lbl">Jenis Kegiatan</div>
            </div>
            <div class="icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-tags-fill"></i>
            </div>
        </div>
    </div>
</div>

<!-- ── Breakdown per Kegiatan ─────────────────────────────── -->
<?php if (!empty($breakdown_kegiatan)): ?>
<div class="pk-card p-3 mb-4">
    <div class="fw-bold small mb-2" style="letter-spacing:.03em;">
        <i class="bi bi-bar-chart-fill me-1 text-primary"></i>BREAKDOWN PER JENIS KEGIATAN
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($breakdown_kegiatan as $bk): ?>
        <?php $w = $WARNA_KEGIATAN[$bk['nama_kegiatan']] ?? $WARNA_KEGIATAN['default']; ?>
        <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-3"
             style="background:<?= $w['bg'] ?>;border:1px solid <?= $w['border'] ?>;">
            <div>
                <div class="fw-bold" style="color:<?= $w['text'] ?>;font-size:.85rem;">
                    <?= e($bk['nama_kegiatan']) ?>
                </div>
                <div style="font-size:.7rem;color:<?= $w['text'] ?>;opacity:.8;">
                    <?= (int)$bk['jumlah_sesi'] ?> sesi &nbsp;·&nbsp;
                    <?= (int)$bk['jumlah_tutor'] ?> tutor
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ── Rekap Per Tutor (Cards) ─────────────────────────────── -->
<div class="fw-bold small mb-2 ps-1" style="letter-spacing:.03em;">
    <i class="bi bi-person-badge-fill me-1 text-primary"></i>REKAP PER TUTOR
</div>
<div class="row g-3 mb-4">
    <?php foreach ($rekap_per_tutor as $r):
        $honor = (int)$r['jumlah_sesi'] * (float)($r['tarif_per_sesi'] ?? 0);
        $inisial = strtoupper(substr($r['nama_lengkap'], 0, 1));
    ?>
    <div class="col-md-6 col-lg-4">
        <div class="tutor-card h-100">
            <!-- Head -->
            <div class="tutor-card-head d-flex align-items-center gap-2">
                <div class="tutor-avatar"><?= $inisial ?></div>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="fw-bold text-truncate"><?= e($r['nama_lengkap']) ?></div>
                    <div class="honor-txt text-truncate"><?= e($r['spesialisasi'] ?: '—') ?></div>
                </div>
                <div class="text-end">
                    <div class="sesi-badge"><?= (int)$r['jumlah_sesi'] ?> sesi</div>
                </div>
            </div>
            <!-- Body -->
            <div class="tutor-card-body">
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <div class="text-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;">Honor Estimasi</div>
                        <div class="fw-bold <?= $honor>0?'text-danger':'text-muted' ?>" style="font-size:.9rem;">
                            <?= $honor > 0 ? format_rupiah($honor) : '—' ?>
                        </div>
                        <?php if (!$r['tarif_per_sesi']): ?>
                        <div class="text-warning" style="font-size:.65rem;">set tarif dulu</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;">Tarif/Sesi</div>
                        <div class="fw-bold" style="font-size:.9rem;">
                            <?= $r['tarif_per_sesi'] ? format_rupiah((float)$r['tarif_per_sesi']) : '—' ?>
                        </div>
                    </div>
                </div>

                <!-- Kegiatan pills -->
                <div class="mb-2">
                    <div class="text-muted mb-1" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;">Kegiatan</div>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach (explode(', ', $r['jenis_kegiatan']) as $k): ?>
                        <?= kegiatan_badge(trim($k), $WARNA_KEGIATAN) ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Program -->
                <?php if ($r['program_list'] && $r['program_list'] !== '—'): ?>
                <div class="mb-2">
                    <div class="text-muted mb-1" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;">Program</div>
                    <div class="small text-dark"><?= e($r['program_list']) ?></div>
                </div>
                <?php endif; ?>

                <!-- Bank info + WA -->
                <div class="d-flex gap-2 mt-2 pt-2 border-top">
                    <?php if ($r['bank_nama']): ?>
                    <div class="text-muted" style="font-size:.68rem;">
                        <i class="bi bi-bank2 me-1"></i>
                        <?= e($r['bank_nama']) ?> · <?= e($r['bank_rekening'] ?? '—') ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($r['nomor_wa']): ?>
                    <a href="https://wa.me/62<?= ltrim(preg_replace('/\D/','',$r['nomor_wa']),'0') ?>"
                       target="_blank" class="ms-auto text-success" style="font-size:.9rem;" title="WhatsApp">
                        <i class="bi bi-whatsapp"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── Detail Sesi (Tabel Lengkap) ────────────────────────── -->
<div class="pk-card overflow-hidden">
    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
        <div class="fw-bold small">
            <i class="bi bi-table me-1 text-primary"></i>
            DETAIL SESI — <?= count($detail_sesi) ?> baris
        </div>
        <div class="d-flex gap-1 print-hide">
            <button class="btn btn-sm btn-outline-secondary" onclick="toggleDetail()"
                    id="btnToggleDetail">
                <i class="bi bi-eye-slash me-1"></i>Sembunyikan Detail
            </button>
        </div>
    </div>

    <div id="detailSesiWrapper">
    <div class="table-responsive">
        <table class="table detail-table align-middle mb-0 small" id="tabelDetail">
            <thead>
                <tr>
                    <th class="ps-3" style="width:100px;">Tanggal</th>
                    <th>Kegiatan</th>
                    <th class="d-none d-md-table-cell">Program</th>
                    <th class="d-none d-lg-table-cell">Materi</th>
                    <th class="text-center d-none d-md-table-cell" style="width:110px;">Waktu</th>
                    <th>Tutor</th>
                    <th class="d-none d-lg-table-cell">Spesialisasi</th>
                    <th class="text-end pe-3 d-none d-md-table-cell">Tarif/Sesi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $prev_jadwal_id = null;
            foreach ($detail_sesi as $d):
                $is_new_jadwal = ($d['jadwal_id'] !== $prev_jadwal_id);
                $prev_jadwal_id = $d['jadwal_id'];
                $w = $WARNA_KEGIATAN[$d['nama_kegiatan']] ?? $WARNA_KEGIATAN['default'];
            ?>
            <tr class="<?= $is_new_jadwal ? 'border-top border-2' : '' ?>"
                style="<?= !$is_new_jadwal ? 'background:#fafbfc;' : '' ?>">
                <td class="ps-3">
                    <?php if ($is_new_jadwal): ?>
                    <div class="fw-bold"><?= date('d M', strtotime($d['tanggal'])) ?></div>
                    <div class="text-muted" style="font-size:.65rem;"><?= date('Y', strtotime($d['tanggal'])) ?></div>
                    <?php else: ?>
                    <div class="text-muted" style="font-size:.65rem;">↳</div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($is_new_jadwal): ?>
                    <?= kegiatan_badge($d['nama_kegiatan'], $WARNA_KEGIATAN) ?>
                    <?php else: ?>
                    <span class="text-muted" style="font-size:.65rem;">—</span>
                    <?php endif; ?>
                </td>
                <td class="d-none d-md-table-cell text-muted">
                    <?= $is_new_jadwal ? e($d['nama_program'] ?? '—') : '' ?>
                </td>
                <td class="d-none d-lg-table-cell text-muted">
                    <?= $is_new_jadwal ? e($d['materi'] ?? '—') : '' ?>
                </td>
                <td class="text-center d-none d-md-table-cell">
                    <?php if ($is_new_jadwal): ?>
                    <span style="font-size:.72rem;">
                        <?= substr($d['waktu_mulai'],0,5) ?>–<?= substr($d['waktu_selesai'],0,5) ?>
                    </span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="fw-bold" style="font-size:.82rem;"><?= e($d['nama_tutor']) ?></div>
                </td>
                <td class="d-none d-lg-table-cell text-muted" style="font-size:.72rem;">
                    <?= e($d['spesialisasi'] ?? '—') ?>
                </td>
                <td class="text-end pe-3 d-none d-md-table-cell text-muted" style="font-size:.72rem;">
                    <?= $d['tarif_per_sesi'] ? format_rupiah((float)$d['tarif_per_sesi']) : '—' ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    </div><!-- #detailSesiWrapper -->
</div>

<?php endif; ?>

<script>
function toggleDetail() {
    const wrap = document.getElementById('detailSesiWrapper');
    const btn  = document.getElementById('btnToggleDetail');
    const hidden = wrap.style.display === 'none';
    wrap.style.display = hidden ? '' : 'none';
    btn.innerHTML = hidden
        ? '<i class="bi bi-eye-slash me-1"></i>Sembunyikan Detail'
        : '<i class="bi bi-eye me-1"></i>Tampilkan Detail';
}

// Auto-submit saat pilihan berubah (opsional)
document.querySelectorAll('#formFilter select').forEach(el => {
    el.addEventListener('change', () => {
        // biarkan user klik tombol filter
    });
});

// Highlight baris tutor yang sama saat hover
document.querySelectorAll('#tabelDetail tbody tr').forEach(tr => {
    tr.addEventListener('mouseenter', function() {
        const tutorCell = this.querySelectorAll('td')[5];
        if (!tutorCell) return;
        const nama = tutorCell.textContent.trim();
        if (!nama) return;
        document.querySelectorAll('#tabelDetail tbody tr').forEach(r => {
            const c = r.querySelectorAll('td')[5];
            if (c && c.textContent.trim() === nama) {
                r.style.background = '#fffbe6';
            }
        });
    });
    tr.addEventListener('mouseleave', function() {
        document.querySelectorAll('#tabelDetail tbody tr').forEach(r => {
            r.style.background = '';
        });
    });
});
</script>

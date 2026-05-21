<?php
/**
 * calendar.view.php — Kalender jadwal interaktif
 * - Klik tanggal kosong → modal Tambah Jadwal (tanggal otomatis terisi)
 * - Klik event jadwal   → modal Edit Jadwal (data lengkap terisi)
 * - Support banyak jadwal per hari
 */

$WARNA = [
    'Jasmani'  => ['bg'=>'#d1e7dd','border'=>'#198754','text'=>'#0a5c36'],
    'Renang'   => ['bg'=>'#cff4fc','border'=>'#0dcaf0','text'=>'#055160'],
    'Akademik' => ['bg'=>'#cfe2ff','border'=>'#0d6efd','text'=>'#084298'],
    'Psikologi'=> ['bg'=>'#e2d9f3','border'=>'#6f42c1','text'=>'#3d1a78'],
    'Tryout'   => ['bg'=>'#fff3cd','border'=>'#fd7e14','text'=>'#6f4800'],
    'default'  => ['bg'=>'#e9ecef','border'=>'#6c757d','text'=>'#343a40'],
];
$KEGIATAN_LIST = ['Jasmani','Renang','Akademik','Psikologi','Tryout'];

// Fungsi label hari Indonesia
function nama_hari_id(string $tanggal): string {
    $map = ['Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
            'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu','Sunday'=>'Minggu'];
    return $map[date('l', strtotime($tanggal))] ?? '';
}
?>

<style>
/* ── Kalender ──────────────────────────────────────────── */
.cal-grid { border: 1px solid #dee2e6; border-radius: 12px; overflow: hidden; }
.cal-head  { display: grid; grid-template-columns: repeat(7,1fr); background: #001233; }
.cal-head-cell { text-align: center; padding: 8px 4px; font-size: .72rem; font-weight: 700;
                 color: rgba(255,255,255,.8); letter-spacing:.04em; text-transform:uppercase; }
.cal-body  { display: grid; grid-template-columns: repeat(7,1fr); }

.cal-cell  {
    min-height: 90px;
    border-right: 1px solid #e9ecef;
    border-bottom: 1px solid #e9ecef;
    padding: 5px;
    cursor: pointer;
    transition: background .12s;
    position: relative;
}
.cal-cell:hover        { background: #f0f4ff; }
.cal-cell.is-today     { background: #fff8e1; }
.cal-cell.is-today:hover { background: #fff0b3; }
.cal-cell.is-empty     { background: #f8f9fa; cursor: default; }
.cal-cell:nth-child(7n){ border-right: none; }

.cal-date-num {
    font-size: .72rem; font-weight: 700; color: #6c757d;
    text-align: right; margin-bottom: 3px; line-height: 1;
}
.cal-cell.is-today .cal-date-num { color: #0d6efd; }
.today-dot {
    display: inline-block; width: 18px; height: 18px;
    border-radius: 50%; background: #0d6efd; color: #fff;
    font-size: .68rem; font-weight: 800;
    text-align: center; line-height: 18px; float: right;
}

/* Event chips */
.cal-event {
    font-size: .63rem; padding: 2px 5px; margin-bottom: 2px;
    border-radius: 4px; border-left: 3px solid;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    cursor: pointer; transition: opacity .1s, transform .1s;
    line-height: 1.4;
}
.cal-event:hover { opacity: .82; transform: translateX(1px); }
.cal-event-time  { opacity: .7; }

.cal-add-hint {
    position: absolute; bottom: 4px; left: 50%; transform: translateX(-50%);
    font-size: .6rem; color: #adb5bd; opacity: 0;
    transition: opacity .15s; white-space: nowrap;
    pointer-events: none;
}
.cal-cell:hover .cal-add-hint { opacity: 1; }

/* Mobile list */
.mob-event-card {
    border-left: 4px solid;
    border-radius: 0 8px 8px 0;
    padding: 8px 12px;
    margin-bottom: 6px;
    cursor: pointer;
    transition: opacity .12s;
}
.mob-event-card:hover { opacity: .85; }

/* Modal form */
.modal-jadwal .modal-content { border-radius: 14px; border: none; }
.modal-jadwal .modal-header  { background: #001233; color: #fff; border-radius: 14px 14px 0 0; }
.modal-jadwal .modal-header .btn-close { filter: invert(1); }
.tutor-check-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px,1fr)); gap: 6px; }
.tutor-check-item {
    border: 1.5px solid #dee2e6; border-radius: 8px; padding: 8px 10px;
    cursor: pointer; transition: all .12s; display: flex; align-items: center; gap: 8px;
    font-size: .83rem;
}
.tutor-check-item:hover             { border-color: #0d6efd; background: #f0f4ff; }
.tutor-check-item.checked           { border-color: #0d6efd; background: #e7f0ff; font-weight: 600; }
.tutor-check-item input[type=checkbox] { accent-color: #0d6efd; width: 15px; height: 15px; }
</style>

<!-- ── Header ─────────────────────────────────────────────── -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0">Jadwal & Tutor</h4>
        <small class="text-muted">Klik tanggal untuk tambah • Klik jadwal untuk edit</small>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?page=tutor&action=rekap_tutor&f_dari=<?= date('Y-m-01') ?>&f_sampai=<?= date('Y-m-t') ?>"
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-person-video3 me-1"></i>Rekap Tutor
        </a>
        <button class="btn btn-primary btn-sm" onclick="openTambah('')">
            <i class="bi bi-plus-lg me-1"></i>Tambah Jadwal
        </button>
    </div>
</div>

<!-- ── Navigasi Bulan ─────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-center gap-3 mb-3">
    <a href="?page=jadwal&bulan=<?= $prev['b'] ?>&tahun=<?= $prev['t'] ?>"
       class="btn btn-outline-secondary btn-sm px-3">
        <i class="bi bi-chevron-left"></i>
    </a>
    <h5 class="fw-bold mb-0" style="min-width:180px; text-align:center;">
        <?= $nama_bulan[$bulan] ?> <?= $tahun ?>
    </h5>
    <a href="?page=jadwal&bulan=<?= $next['b'] ?>&tahun=<?= $next['t'] ?>"
       class="btn btn-outline-secondary btn-sm px-3">
        <i class="bi bi-chevron-right"></i>
    </a>
</div>

<!-- ── Kalender (desktop ≥768px) ────────────────────────── -->
<div class="cal-grid d-none d-md-block mb-4 shadow-sm">
    <!-- Header hari -->
    <div class="cal-head">
        <?php foreach (['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $h): ?>
        <div class="cal-head-cell"><?= $h ?></div>
        <?php endforeach; ?>
    </div>
    <!-- Grid hari -->
    <div class="cal-body">
        <?php
        $first = (int)date('N', mktime(0,0,0,$bulan,1,$tahun)); // 1=Sen .. 7=Min
        $days  = (int)date('t', mktime(0,0,0,$bulan,1,$tahun));
        $today = date('Y-m-d');

        // Sel kosong sebelum hari pertama
        for ($i = 1; $i < $first; $i++):
        ?>
        <div class="cal-cell is-empty"></div>
        <?php endfor; ?>

        <?php for ($d = 1; $d <= $days; $d++):
            $ds       = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
            $is_today = ($ds === $today);
            $events   = $jadwal_by_date[$ds] ?? [];
        ?>
        <div class="cal-cell <?= $is_today ? 'is-today' : '' ?>"
             onclick="openTambah('<?= $ds ?>')"
             title="Tambah jadwal <?= $ds ?>">

            <!-- Nomor tanggal -->
            <div class="cal-date-num">
                <?php if ($is_today): ?>
                    <span class="today-dot"><?= $d ?></span>
                <?php else: ?>
                    <?= $d ?>
                <?php endif; ?>
            </div>

            <!-- Event chips -->
            <?php foreach ($events as $j):
                $w = $WARNA[$j['nama_kegiatan']] ?? $WARNA['default'];
            ?>
            <div class="cal-event"
                 style="background:<?= $w['bg'] ?>;border-left-color:<?= $w['border'] ?>;color:<?= $w['text'] ?>;"
                 onclick="event.stopPropagation(); openEdit(<?= (int)$j['id'] ?>)"
                 title="<?= e($j['nama_kegiatan']) ?> <?= e($j['materi']?'— '.$j['materi']:'') ?> · <?= substr($j['waktu_mulai'],0,5) ?>–<?= substr($j['waktu_selesai'],0,5) ?>">
                <strong><?= e($j['nama_kegiatan'] ?: '—') ?></strong>
                <span class="cal-event-time"> <?= substr($j['waktu_mulai'],0,5) ?></span>
            </div>
            <?php endforeach; ?>

            <span class="cal-add-hint">+ Tambah</span>
        </div>
        <?php endfor; ?>

        <!-- Sel kosong di akhir baris -->
        <?php
        $last_col = (($first - 1 + $days) % 7);
        $rem = $last_col === 0 ? 0 : (7 - $last_col);
        for ($i = 0; $i < $rem; $i++):
        ?>
        <div class="cal-cell is-empty"></div>
        <?php endfor; ?>
    </div>
</div>

<!-- ── Mobile: list view ─────────────────────────────────── -->
<div class="d-md-none mb-4">
    <?php if (empty($jadwal_bulan)): ?>
    <div class="pk-card p-4 text-center">
        <i class="bi bi-calendar-x text-muted" style="font-size:2rem;"></i>
        <p class="text-muted mt-2 mb-0 small">Tidak ada jadwal bulan ini</p>
        <button class="btn btn-primary btn-sm mt-3" onclick="openTambah('')">
            <i class="bi bi-plus-lg me-1"></i>Tambah Jadwal
        </button>
    </div>
    <?php else:
        $grouped = [];
        foreach ($jadwal_bulan as $j) $grouped[$j['tanggal']][] = $j;
        foreach ($grouped as $tgl => $items):
            $w_h = $WARNA[$items[0]['nama_kegiatan']] ?? $WARNA['default'];
    ?>
    <div class="mb-3">
        <div class="fw-bold small text-muted mb-1">
            <?= nama_hari_id($tgl) ?>, <?= format_tanggal($tgl,'d M Y') ?>
        </div>
        <?php foreach ($items as $j):
            $w = $WARNA[$j['nama_kegiatan']] ?? $WARNA['default'];
        ?>
        <div class="mob-event-card"
             style="background:<?= $w['bg'] ?>;border-left-color:<?= $w['border'] ?>;"
             onclick="openEdit(<?= (int)$j['id'] ?>)">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="fw-bold" style="color:<?= $w['text'] ?>;font-size:.88rem;">
                        <?= e($j['nama_kegiatan'] ?: '—') ?>
                        <?php if ($j['materi']): ?>
                        <span class="fw-normal" style="font-size:.78rem;"> — <?= e($j['materi']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($j['nama_tutor']): ?>
                    <div style="font-size:.73rem;color:<?= $w['text'] ?>;opacity:.8;">
                        <i class="bi bi-person-fill me-1"></i><?= e($j['nama_tutor']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div style="font-size:.78rem;color:<?= $w['text'] ?>;opacity:.9;white-space:nowrap;margin-left:8px;">
                    <?= substr($j['waktu_mulai'],0,5) ?>–<?= substr($j['waktu_selesai'],0,5) ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; endif; ?>

    <!-- Tombol tambah mobile -->
    <button class="btn btn-primary w-100 mt-2" onclick="openTambah('')">
        <i class="bi bi-plus-lg me-1"></i>Tambah Jadwal
    </button>
</div>

<!-- ── Tabel detail (semua platform) ───────────────────── -->
<?php if (!empty($jadwal_bulan)): ?>
<div class="pk-card overflow-hidden">
    <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">
            <i class="bi bi-list-ul me-2 text-muted"></i>
            Detail — <?= $nama_bulan[$bulan] ?> <?= $tahun ?>
            <span class="badge bg-secondary ms-2" style="font-size:.7rem;"><?= count($jadwal_bulan) ?></span>
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table pk-table small mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width:120px;">Tanggal</th>
                    <th>Kegiatan</th>
                    <th>Program</th>
                    <th>Waktu</th>
                    <th class="d-none d-lg-table-cell">Tutor</th>
                    <th class="text-center pe-3" style="width:90px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jadwal_bulan as $j):
                    $w = $WARNA[$j['nama_kegiatan']] ?? $WARNA['default'];
                ?>
                <tr>
                    <td class="ps-3">
                        <div class="fw-bold"><?= format_tanggal($j['tanggal'],'d M Y') ?></div>
                        <div class="text-muted" style="font-size:.68rem;"><?= nama_hari_id($j['tanggal']) ?></div>
                    </td>
                    <td>
                        <span class="badge" style="background:<?= $w['bg'] ?>;color:<?= $w['text'] ?>;border:1px solid <?= $w['border'] ?>;font-size:.72rem;">
                            <?= e($j['nama_kegiatan'] ?: '—') ?>
                        </span>
                        <?php if ($j['materi']): ?>
                        <div class="text-muted" style="font-size:.7rem;"><?= e($j['materi']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-light text-dark border" style="font-size:.7rem;"><?= e($j['nama_program'] ?? '—') ?></span></td>
                    <td class="text-muted fw-bold" style="font-size:.8rem; white-space:nowrap;">
                        <?= substr($j['waktu_mulai'],0,5) ?>–<?= substr($j['waktu_selesai'],0,5) ?>
                    </td>
                    <td class="text-muted d-none d-lg-table-cell" style="font-size:.75rem;">
                        <?= $j['nama_tutor'] ? e($j['nama_tutor']) : '<span class="text-muted">—</span>' ?>
                    </td>
                    <td class="text-center pe-3">
                        <div class="d-flex gap-1 justify-content-center">
                            <button class="btn btn-sm btn-outline-secondary" title="Edit"
                                    onclick="openEdit(<?= (int)$j['id'] ?>)">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" title="Hapus"
                                    onclick="hapusJadwal(<?= (int)$j['id'] ?>, '<?= e(addslashes($j['nama_kegiatan'])) ?>', '<?= e(addslashes(format_tanggal($j['tanggal'],'d M Y'))) ?>')">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>


<!-- ════════════════════════════════════════════════════════════
     MODAL TAMBAH JADWAL
     ════════════════════════════════════════════════════════════ -->
<div class="modal fade modal-jadwal" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-plus-circle-fill me-2"></i>Tambah Jadwal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?page=jadwal&action=store" id="formTambah">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <?= _jadwal_form_body($daftar_program, $daftar_tutor, $KEGIATAN_LIST) ?>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-save me-2"></i>Simpan Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     MODAL EDIT JADWAL
     ════════════════════════════════════════════════════════════ -->
<div class="modal fade modal-jadwal" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2a5e;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-square me-2"></i>Edit Jadwal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?page=jadwal&action=update" id="formEdit">
                <?= csrf_field() ?>
                <input type="hidden" name="jadwal_id" id="edit_jadwal_id">
                <div class="modal-body" id="editModalBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2 text-muted small">Memuat data jadwal…</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-danger me-auto" id="btnHapusDariEdit">
                        <i class="bi bi-trash me-1"></i>Hapus
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-save me-2"></i>Perbarui
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Helper: render isi form (shared antara tambah & edit)
function _jadwal_form_body(array $programs, array $tutors, array $kegiatan_list, array $data = []): string {
    $sel_program  = (int)($data['program_id'] ?? 0);
    $sel_kegiatan = $data['nama_kegiatan'] ?? '';
    $val_materi   = e($data['materi'] ?? '');
    $val_tanggal  = e($data['tanggal'] ?? '');
    $val_mulai    = e(substr($data['waktu_mulai'] ?? '',0,5));
    $val_selesai  = e(substr($data['waktu_selesai'] ?? '',0,5));
    $val_lokasi   = e($data['lokasi'] ?? '');

    ob_start(); ?>
    <div class="row g-3">
        <!-- Kegiatan & Program -->
        <div class="col-sm-6">
            <label class="form-label fw-bold small">Jenis Kegiatan <span class="text-danger">*</span></label>
            <select name="kegiatan" class="form-select js-kegiatan" required>
                <option value="">— Pilih kegiatan —</option>
                <?php foreach ($kegiatan_list as $k): ?>
                <option value="<?= $k ?>" <?= $sel_kegiatan===$k?'selected':'' ?>><?= $k ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-bold small">Program</label>
            <select name="program_id" class="form-select">
                <option value="">— Pilih program (opsional) —</option>
                <?php foreach ($programs as $p): ?>
                <option value="<?= (int)$p['id'] ?>" <?= $sel_program===(int)$p['id']?'selected':'' ?>><?= e($p['nama_program']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Materi -->
        <div class="col-12">
            <label class="form-label fw-bold small">Materi / Topik</label>
            <input type="text" name="materi" class="form-control"
                   placeholder="Opsional — Lari 12 menit, latihan soal TWK, dst"
                   value="<?= $val_materi ?>">
        </div>
        <!-- Tanggal & Waktu -->
        <div class="col-sm-4">
            <label class="form-label fw-bold small">Tanggal <span class="text-danger">*</span></label>
            <input type="date" name="tanggal" class="form-control js-tanggal" required value="<?= $val_tanggal ?>">
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-bold small">Mulai <span class="text-danger">*</span></label>
            <input type="time" name="waktu_mulai" class="form-control" required value="<?= $val_mulai ?>">
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-bold small">Selesai <span class="text-danger">*</span></label>
            <input type="time" name="waktu_selesai" class="form-control" required value="<?= $val_selesai ?>">
        </div>
        <!-- Lokasi -->
        <div class="col-12">
            <label class="form-label fw-bold small">Lokasi</label>
            <input type="text" name="lokasi" class="form-control"
                   placeholder="Perkasa Mulia Training Center"
                   value="<?= $val_lokasi ?: '' ?>">
        </div>
        <!-- Tutor -->
        <?php if (!empty($tutors)): ?>
        <div class="col-12">
            <label class="form-label fw-bold small">Tutor / Coach</label>
            <div class="tutor-check-grid">
                <?php
                $sel_tutor_ids = $data['tutor_ids'] ?? [];
                foreach ($tutors as $t):
                    $checked = in_array((int)$t['id'], array_map('intval', (array)$sel_tutor_ids));
                ?>
                <label class="tutor-check-item <?= $checked?'checked':'' ?>"
                       onclick="this.classList.toggle('checked')">
                    <input type="checkbox" name="tutor_id[]"
                           value="<?= (int)$t['id'] ?>" <?= $checked?'checked':'' ?>>
                    <span>
                        <?= e($t['nama_lengkap']) ?>
                        <?php if ($t['spesialisasi']): ?>
                        <br><span class="text-muted" style="font-size:.72rem;"><?= e($t['spesialisasi']) ?></span>
                        <?php endif; ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
?>

<script>
const CSRF_TOKEN   = '<?= csrf_token() ?>';
const BULAN        = <?= $bulan ?>;
const TAHUN        = <?= $tahun ?>;

// ── Buka modal TAMBAH ─────────────────────────────────────
function openTambah(tanggal) {
    const modal = document.getElementById('modalTambah');
    if (tanggal) {
        const tgl = modal.querySelector('.js-tanggal');
        if (tgl) tgl.value = tanggal;
    }
    new bootstrap.Modal(modal).show();
}

// ── Buka modal EDIT (load data via AJAX) ──────────────────
function openEdit(jadwal_id) {
    const modal   = document.getElementById('modalEdit');
    const bodyEl  = document.getElementById('editModalBody');
    const idInput = document.getElementById('edit_jadwal_id');

    idInput.value = jadwal_id;

    // Loading state
    bodyEl.innerHTML = `<div class="text-center py-4">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-2 text-muted small">Memuat data jadwal…</div>
    </div>`;

    const m = new bootstrap.Modal(modal);
    m.show();

    // Fetch data jadwal
    fetch(`index.php?page=jadwal&action=get_json&id=${jadwal_id}`)
        .then(r => r.json())
        .then(res => {
            if (!res.ok) { bodyEl.innerHTML = `<div class="alert alert-danger">${res.msg}</div>`; return; }
            const d = res.data;
            bodyEl.innerHTML = buildFormHTML(d);
            initTutorCheckboxes(bodyEl);
        })
        .catch(() => {
            bodyEl.innerHTML = '<div class="alert alert-danger">Gagal memuat data. Coba lagi.</div>';
        });

    // Tombol hapus di modal edit
    document.getElementById('btnHapusDariEdit').onclick = () => {
        m.hide();
        setTimeout(() => hapusJadwal(jadwal_id, '', ''), 300);
    };
}

// ── Hapus dengan konfirmasi ───────────────────────────────
function hapusJadwal(id, nama, tgl) {
    const label = nama ? `${nama}${tgl?' ('+tgl+')':''}` : `ID #${id}`;
    pkConfirm(
        `Hapus jadwal "${label}"?`,
        () => {
            window.location.href = `index.php?page=jadwal&action=delete&id=${id}&bulan=${BULAN}&tahun=${TAHUN}&_csrf_token=${CSRF_TOKEN}`;
        },
        { title: 'Hapus Jadwal', btnLabel: 'Ya, Hapus' }
    );
}

// ── Build form HTML untuk modal edit ─────────────────────
function buildFormHTML(d) {
    const programs = <?= json_encode($daftar_program) ?>;
    const tutors   = <?= json_encode($daftar_tutor) ?>;
    const kegiatan = <?= json_encode($KEGIATAN_LIST) ?>;

    let progOpts = '<option value="">— Pilih program (opsional) —</option>';
    programs.forEach(p => {
        const sel = parseInt(d.program_id) === parseInt(p.id) ? 'selected' : '';
        progOpts += `<option value="${p.id}" ${sel}>${escHtml(p.nama_program)}</option>`;
    });

    let kegOpts = '<option value="">— Pilih kegiatan —</option>';
    kegiatan.forEach(k => {
        const sel = d.nama_kegiatan === k ? 'selected' : '';
        kegOpts += `<option value="${k}" ${sel}>${k}</option>`;
    });

    const selTutorIds = (d.tutor_ids || []).map(Number);
    let tutorHTML = '';
    if (tutors.length > 0) {
        let cards = tutors.map(t => {
            const checked = selTutorIds.includes(Number(t.id));
            const cls     = checked ? 'tutor-check-item checked' : 'tutor-check-item';
            const chk     = checked ? 'checked' : '';
            const spes    = t.spesialisasi ? `<br><span class="text-muted" style="font-size:.72rem;">${escHtml(t.spesialisasi)}</span>` : '';
            return `<label class="${cls}" onclick="this.classList.toggle('checked')">
                <input type="checkbox" name="tutor_id[]" value="${t.id}" ${chk}>
                <span>${escHtml(t.nama_lengkap)}${spes}</span>
            </label>`;
        }).join('');
        tutorHTML = `<div class="col-12">
            <label class="form-label fw-bold small">Tutor / Coach</label>
            <div class="tutor-check-grid">${cards}</div>
        </div>`;
    }

    const tanggal  = (d.tanggal || '').substring(0,10);
    const mulai    = (d.waktu_mulai || '').substring(0,5);
    const selesai  = (d.waktu_selesai || '').substring(0,5);
    const materi   = escHtml(d.materi || '');
    const lokasi   = escHtml(d.lokasi || '');

    return `<div class="row g-3">
        <div class="col-sm-6">
            <label class="form-label fw-bold small">Jenis Kegiatan <span class="text-danger">*</span></label>
            <select name="kegiatan" class="form-select" required>${kegOpts}</select>
        </div>
        <div class="col-sm-6">
            <label class="form-label fw-bold small">Program</label>
            <select name="program_id" class="form-select">${progOpts}</select>
        </div>
        <div class="col-12">
            <label class="form-label fw-bold small">Materi / Topik</label>
            <input type="text" name="materi" class="form-control"
                   placeholder="Opsional" value="${materi}">
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-bold small">Tanggal <span class="text-danger">*</span></label>
            <input type="date" name="tanggal" class="form-control js-tanggal" required value="${tanggal}">
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-bold small">Mulai <span class="text-danger">*</span></label>
            <input type="time" name="waktu_mulai" class="form-control" required value="${mulai}">
        </div>
        <div class="col-sm-4">
            <label class="form-label fw-bold small">Selesai <span class="text-danger">*</span></label>
            <input type="time" name="waktu_selesai" class="form-control" required value="${selesai}">
        </div>
        <div class="col-12">
            <label class="form-label fw-bold small">Lokasi</label>
            <input type="text" name="lokasi" class="form-control"
                   placeholder="Perkasa Mulia Training Center" value="${lokasi}">
        </div>
        ${tutorHTML}
    </div>`;
}

function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function initTutorCheckboxes(container) {
    // Already handled by inline onclick in buildFormHTML
}

// ── Set tanggal default saat modal tambah dibuka ─────────
document.getElementById('modalTambah').addEventListener('show.bs.modal', function() {
    // Jika tanggal belum diisi, default hari ini
    const tgl = this.querySelector('.js-tanggal');
    if (tgl && !tgl.value) tgl.value = new Date().toISOString().split('T')[0];
});
</script>

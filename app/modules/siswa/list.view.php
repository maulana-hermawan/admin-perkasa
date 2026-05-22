<?php
/**
 * app/modules/siswa/list.view.php — Module 6.5
 * Data table full-featured: search, sort, pagination, bulk, mobile cards
 */

// Helper: sort link dengan indicator
function sort_link(string $col, string $label, string $current_col, string $current_dir, array $extra = []): string
{
    $new_dir = ($current_col === $col && $current_dir === 'ASC') ? 'DESC' : 'ASC';
    $indicator = '';
    if ($current_col === $col) {
        $indicator = ' <i class="bi bi-arrow-' . ($current_dir === 'ASC' ? 'up' : 'down') . '" style="font-size:.7rem;opacity:.8;"></i>';
    } else {
        $indicator = ' <i class="bi bi-arrow-down-up text-muted" style="font-size:.65rem;opacity:.4;"></i>';
    }

    $params = array_merge($extra, ['page' => 'siswa', 'sort' => $col, 'dir' => $new_dir]);
    $url    = '?' . http_build_query($params);
    return '<a href="' . e($url) . '" class="text-decoration-none text-white d-block">'
           . e($label) . $indicator . '</a>';
}

// Build base URL params for pagination links (preserve all filters)
$base_params = array_filter([
    'page'       => 'siswa',
    'q'          => $q,
    'status'     => $f_status,
    'program_id' => $f_program ?: null,
    'target'     => $f_target,
    'sort'       => $sort_col,
    'dir'        => $sort_dir,
    'per_page'   => $per_page !== 25 ? $per_page : null,
]);
?>

<!-- ── Summary Chips ─────────────────────────────────────────── -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Data Keanggotaan</h4>
        <div class="d-flex flex-wrap gap-2 mt-2">
            <?php
            $chips = [
                ['Aktif',      $stat_aktif,    'success', 'status', 'Aktif'],
                ['Lulus',      $stat_lulus,    'primary', 'status', 'Lulus'],
                ['Cuti',       $stat_cuti,     'warning', 'status', 'Cuti'],
                ['Tidak Aktif',$stat_nonaktif, 'secondary','status', 'Tidak Aktif'],
            ];
            foreach ($chips as [$lbl, $cnt, $color, $param, $val]):
                $active  = ($f_status === $val);
                $url_arr = array_merge($base_params, ['halaman' => 1, $param => $active ? '' : $val]);
                unset($url_arr['page']); // already in base
            ?>
            <a href="?<?= http_build_query(array_merge(['page'=>'siswa'], $url_arr)) ?>"
               class="badge rounded-pill text-decoration-none border <?= $active ? "bg-{$color} text-white" : "bg-{$color} bg-opacity-10 text-{$color}" ?>"
               style="font-size:.72rem;">
                <?= e($lbl) ?> <span class="fw-bold"><?= $cnt ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <a href="index.php?page=siswa&action=trash"
           class="btn btn-outline-secondary btn-sm" title="Lihat siswa terhapus">
            <i class="bi bi-trash2-fill me-1"></i>
            <span class="d-none d-sm-inline">Trash</span>
        </a>
        <a href="index.php?page=siswa&action=create"
           class="btn btn-primary d-flex align-items-center gap-2" id="btnNewEntry"
           title="Tekan N untuk daftar baru">
            <i class="bi bi-person-plus-fill"></i>
            <span class="d-none d-sm-inline">Daftar Baru</span>
        </a>
    </div>
</div>

<!-- ── Filter & Search Bar ──────────────────────────────────── -->
<div class="pk-card p-3 mb-3">
    <form method="GET" id="filterForm">
        <input type="hidden" name="page" value="siswa">
        <input type="hidden" name="sort" value="<?= e($sort_col) ?>">
        <input type="hidden" name="dir"  value="<?= e($sort_dir) ?>">
        <input type="hidden" name="per_page" value="<?= $per_page ?>">

        <div class="row g-2">
            <!-- Search -->
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                    <input type="search" name="q" id="searchInput"
                           class="form-control"
                           value="<?= e($q ?? '') ?>"
                           placeholder="Nama, NIS, WA, orang tua… (Tekan /)">
                </div>
            </div>
            <!-- Status -->
            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <?php foreach (['Aktif','Cuti','Lulus','Tidak Aktif'] as $s): ?>
                    <option value="<?= $s ?>" <?= $f_status===$s?'selected':''?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Program -->
            <div class="col-6 col-md-2">
                <select name="program_id" class="form-select form-select-sm">
                    <option value="">Semua Program</option>
                    <?php foreach ($daftar_program as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= $f_program==(int)$p['id']?'selected':''?>>
                        <?= e($p['nama_program']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Target -->
            <div class="col-6 col-md-2">
                <select name="target" class="form-select form-select-sm">
                    <option value="">Semua Target</option>
                    <?php foreach (['Polri','TNI AD','TNI AL','TNI AU','Akpol','Akmil','Bintara','Tamtama','Lainnya'] as $t): ?>
                    <option value="<?= $t ?>" <?= $f_target===$t?'selected':''?>><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="?page=siswa" class="btn btn-outline-secondary btn-sm" title="Reset">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </div>
    </form>
</div>

<!-- ── Bulk Actions Bar ─────────────────────────────────────── -->
<form method="POST" action="index.php?page=siswa&action=bulk" id="bulkForm">
    <?= csrf_field() ?>

<div id="bulkBar" class="alert alert-info d-none d-flex align-items-center gap-2 py-2 mb-3 rounded-3" role="alert">
    <span class="fw-bold" id="bulkCount">0</span> siswa dipilih.
    <div class="ms-auto d-flex gap-2">
        <button type="submit" name="bulk_action" value="aktifkan"
                class="btn btn-sm btn-success"
                onclick="return confirm('Ubah status ke Aktif?')">
            <i class="bi bi-check-circle me-1"></i>Aktifkan
        </button>
        <button type="submit" name="bulk_action" value="nonaktifkan"
                class="btn btn-sm btn-warning"
                onclick="return confirm('Ubah ke Tidak Aktif?')">
            <i class="bi bi-pause-circle me-1"></i>Non-aktifkan
        </button>
        <button type="submit" name="bulk_action" value="delete"
                class="btn btn-sm btn-danger"
                onclick="return confirm('Hapus siswa yang dipilih? Ini tidak bisa langsung dibatalkan.')">
            <i class="bi bi-trash me-1"></i>Hapus
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearBulk()">
            Batal
        </button>
    </div>
</div>

<!-- ── Tabel Desktop ─────────────────────────────────────────── -->
<div class="pk-card overflow-hidden d-none d-md-block">
    <!-- Table info + per-page -->
    <div class="px-3 py-2 border-bottom bg-white d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            Menampilkan <strong><?= $offset + 1 ?>–<?= min($offset + $per_page, $total) ?></strong>
            dari <strong><?= number_format($total) ?></strong> siswa
            <?php if ($q || $f_status || $f_program || $f_target): ?>
            <span class="badge bg-primary ms-1">Filter aktif</span>
            <?php endif; ?>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label class="text-muted small mb-0">Tampilkan:</label>
            <select class="form-select form-select-sm" style="width:auto;"
                    onchange="changePerPage(this.value)">
                <?php foreach ([10, 25, 50, 100] as $n): ?>
                <option value="<?= $n ?>" <?= $per_page===$n?'selected':''?>><?= $n ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small" id="siswaTable">
            <thead class="table-dark">
                <tr>
                    <th class="ps-3" style="width:36px;">
                        <input type="checkbox" class="form-check-input" id="checkAll"
                               onchange="toggleAll(this.checked)">
                    </th>
                    <th><?= sort_link('nis', 'NIS / Nama', $sort_col, $sort_dir, $base_params) ?></th>
                    <th><?= sort_link('program', 'Program', $sort_col, $sort_dir, $base_params) ?></th>
                    <th>Kontak</th>
                    <th><?= sort_link('expired', 'Exp. Membership', $sort_col, $sort_dir, $base_params) ?></th>
                    <th><?= sort_link('status', 'Status', $sort_col, $sort_dir, $base_params) ?></th>
                    <th class="text-center pe-3"><?= sort_link('daftar', 'Daftar', $sort_col, $sort_dir, $base_params) ?></th>
                </tr>
            </thead>
            <tbody id="skeletonTarget">
                <?php if (empty($daftar_siswa)): ?>
                <tr>
                    <td colspan="7">
                        <!-- Empty State -->
                        <div class="py-5 text-center">
                            <svg width="120" height="100" viewBox="0 0 120 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="mb-3 opacity-50">
                                <ellipse cx="60" cy="90" rx="50" ry="8" fill="#e2e8f0"/>
                                <circle cx="60" cy="38" r="28" fill="#e2e8f0"/>
                                <circle cx="60" cy="38" r="18" fill="#cbd5e1"/>
                                <path d="M45 60 Q60 75 75 60" stroke="#94a3b8" stroke-width="2" fill="none" stroke-linecap="round"/>
                                <circle cx="53" cy="34" r="3" fill="#94a3b8"/>
                                <circle cx="67" cy="34" r="3" fill="#94a3b8"/>
                            </svg>
                            <p class="fw-bold text-muted mb-1">
                                <?= ($q || $f_status || $f_program || $f_target) ? 'Tidak ada siswa yang sesuai filter.' : 'Belum ada siswa terdaftar.' ?>
                            </p>
                            <small class="text-muted d-block mb-3">
                                <?= ($q || $f_status || $f_program || $f_target) ? 'Coba ubah kata kunci atau hapus filter.' : 'Mulai dengan mendaftarkan siswa pertama.' ?>
                            </small>
                            <?php if ($q || $f_status || $f_program || $f_target): ?>
                            <a href="?page=siswa" class="btn btn-sm btn-outline-secondary me-2">Hapus Filter</a>
                            <?php endif; ?>
                            <a href="?page=siswa&action=create" class="btn btn-sm btn-primary">
                                <i class="bi bi-person-plus-fill me-1"></i>Daftar Siswa Baru
                            </a>
                        </div>
                    </td>
                </tr>
                <?php else: foreach ($daftar_siswa as $s):
                    $exp       = $s['tanggal_selesai_aktif'];
                    $sisa_hari = $exp ? (int)(new DateTime())->diff(new DateTime($exp))->days * (new DateTime($exp) >= new DateTime() ? 1 : -1) : null;
                    $exp_class = $sisa_hari === null ? '' : ($sisa_hari < 0 ? 'text-danger' : ($sisa_hari <= 7 ? 'text-warning' : 'text-muted'));
                ?>
                <tr class="siswa-row">
                    <td class="ps-3">
                        <input type="checkbox" class="form-check-input row-check" name="bulk_ids[]"
                               value="<?= (int)$s['id'] ?>" onchange="updateBulk()">
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0
                                        <?= $s['jenis_kelamin']==='P' ? 'bg-danger bg-opacity-10 text-danger' : 'bg-primary bg-opacity-10 text-primary' ?>"
                                 style="width:40px;height:40px;font-size:.95rem;">
                                <?= strtoupper(substr($s['nama_lengkap'], 0, 1)) ?>
                            </div>
                            <div style="min-width:0;">
                                <div class="fw-bold text-primary font-monospace" style="font-size:.68rem;letter-spacing:.02em;"><?= e($s['nomor_induk'] ?? '—') ?></div>
                                <div class="fw-semibold">
                                    <a href="index.php?page=siswa&action=detail&id=<?= (int)$s['id'] ?>"
                                       class="text-decoration-none text-dark">
                                        <?= e($s['nama_lengkap']) ?>
                                    </a>
                                </div>
                                <?php if ($s['nama_ortu']): ?>
                                <div class="text-muted" style="font-size:.68rem;">
                                    <i class="bi bi-person-heart me-1"></i><?= e($s['nama_ortu']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($s['nama_program']): ?>
                        <span class="badge bg-info bg-opacity-15 text-info border border-info border-opacity-25">
                            <?= e($s['nama_program']) ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex flex-column gap-1 align-items-start">
                            <?php if ($s['nomor_wa']): ?>
                            <a href="<?= e(wa_link($s['nomor_wa'], 'Halo '.$s['nama_lengkap'].', dari Perkasa Mulia Training Center.')) ?>" target="_blank"
                               class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 text-decoration-none d-inline-flex align-items-center gap-1"
                               style="font-size:.7rem;font-weight:500;" title="Chat WA siswa">
                                <i class="bi bi-whatsapp"></i><?= e($s['nomor_wa']) ?>
                                <span class="opacity-75">· Siswa</span>
                            </a>
                            <?php endif; ?>
                            <?php if (!empty($s['nomor_wa_ortu'])): ?>
                            <a href="<?= e(wa_link($s['nomor_wa_ortu'], 'Halo, kami dari Perkasa Mulia Training Center terkait ananda '.$s['nama_lengkap'].'.')) ?>" target="_blank"
                               class="badge bg-secondary bg-opacity-10 text-secondary border text-decoration-none d-inline-flex align-items-center gap-1"
                               style="font-size:.7rem;font-weight:500;" title="Chat WA ortu/wali">
                                <i class="bi bi-whatsapp"></i><?= e($s['nomor_wa_ortu']) ?>
                                <span class="opacity-75">· Ortu</span>
                            </a>
                            <?php endif; ?>
                            <?php if (!$s['nomor_wa'] && empty($s['nomor_wa_ortu'])): ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($s['target_seleksi']): ?>
                        <div class="text-muted mt-1" style="font-size:.68rem;">
                            <i class="bi bi-bullseye me-1"></i><?= e($s['target_seleksi']) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($exp): ?>
                        <div class="<?= $exp_class ?> fw-bold" style="font-size:.75rem;">
                            <?= format_tanggal($exp, 'd M Y') ?>
                        </div>
                        <?php if ($sisa_hari !== null): ?>
                        <div class="<?= $exp_class ?>" style="font-size:.68rem;">
                            <?= $sisa_hari < 0 ? abs($sisa_hari).' hari lalu' : ($sisa_hari === 0 ? 'Hari ini' : $sisa_hari.' hari lagi') ?>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= badge_status_siswa($s['status_siswa']) ?>
                        <?php if ($s['status_siswa'] === 'Lulus' && !empty($s['keterangan_lulus'])): ?>
                        <div class="text-primary fw-semibold mt-1" style="font-size:.67rem;line-height:1.2;">
                            <i class="bi bi-award-fill me-1"></i><?= e($s['keterangan_lulus']) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center pe-3">
                        <div class="d-flex justify-content-center gap-1">
                            <a href="index.php?page=siswa&action=detail&id=<?= (int)$s['id'] ?>"
                               class="btn btn-xs btn-outline-secondary" title="Detail"
                               style="padding:2px 7px;font-size:.72rem;">
                                <i class="bi bi-eye-fill"></i>
                            </a>
                            <a href="index.php?page=siswa&action=edit&id=<?= (int)$s['id'] ?>"
                               class="btn btn-xs btn-outline-primary" title="Edit"
                               style="padding:2px 7px;font-size:.72rem;">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            <button class="btn btn-xs btn-outline-danger" title="Hapus"
                                    style="padding:2px 7px;font-size:.72rem;"
                                    onclick="pkConfirm('Hapus siswa &quot;<?= e(addslashes($s['nama_lengkap'])) ?>&quot;?', () => location.href='index.php?page=siswa&action=delete&id=<?= (int)$s['id'] ?>&_csrf_token=<?= e(csrf_token()) ?>')">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>
                        <div class="text-muted mt-1" style="font-size:.65rem;">
                            <?= format_tanggal($s['created_at'], 'd M Y') ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($last_pg > 1): ?>
    <div class="px-3 py-2 border-top bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">Halaman <?= $halaman ?> dari <?= $last_pg ?></small>
        <nav>
            <ul class="pagination pagination-sm mb-0 gap-1">
                <!-- Prev -->
                <li class="page-item <?= $halaman <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link rounded-2" href="?<?= http_build_query(array_merge($base_params, ['halaman' => $halaman - 1])) ?>">&laquo;</a>
                </li>
                <?php
                $start_p = max(1, $halaman - 2);
                $end_p   = min($last_pg, $halaman + 2);
                if ($start_p > 1) { echo '<li class="page-item"><a class="page-link rounded-2" href="?'.http_build_query(array_merge($base_params, ['halaman'=>1])).'">1</a></li>'; }
                if ($start_p > 2) { echo '<li class="page-item disabled"><span class="page-link">…</span></li>'; }
                for ($p = $start_p; $p <= $end_p; $p++):
                ?>
                <li class="page-item <?= $p === $halaman ? 'active' : '' ?>">
                    <a class="page-link rounded-2" href="?<?= http_build_query(array_merge($base_params, ['halaman' => $p])) ?>"><?= $p ?></a>
                </li>
                <?php endfor;
                if ($end_p < $last_pg - 1) { echo '<li class="page-item disabled"><span class="page-link">…</span></li>'; }
                if ($end_p < $last_pg) { echo '<li class="page-item"><a class="page-link rounded-2" href="?'.http_build_query(array_merge($base_params, ['halaman'=>$last_pg])).'">'.  $last_pg.'</a></li>'; }
                ?>
                <!-- Next -->
                <li class="page-item <?= $halaman >= $last_pg ? 'disabled' : '' ?>">
                    <a class="page-link rounded-2" href="?<?= http_build_query(array_merge($base_params, ['halaman' => $halaman + 1])) ?>">&raquo;</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- ── Mobile Card View (<640px) ─────────────────────────────── -->
<div class="d-md-none">
    <?php if (empty($daftar_siswa)): ?>
    <div class="pk-card p-4 text-center text-muted">
        <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
        <p class="mb-2">Tidak ada siswa ditemukan.</p>
        <a href="?page=siswa&action=create" class="btn btn-sm btn-primary">Daftar Baru</a>
    </div>
    <?php else: foreach ($daftar_siswa as $s): ?>
    <div class="pk-card mb-2 overflow-hidden">
        <div class="p-3 d-flex gap-3 align-items-start">
            <!-- Avatar -->
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                 style="width:42px;height:42px;font-size:1rem;">
                <?= strtoupper(substr($s['nama_lengkap'], 0, 1)) ?>
            </div>
            <div class="flex-grow-1 min-width-0">
                <!-- NIS + Nama -->
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="text-primary fw-bold" style="font-size:.68rem;"><?= e($s['nomor_induk'] ?? '—') ?></span>
                    <div>
                        <?= badge_status_siswa($s['status_siswa']) ?>
                        <?php if ($s['status_siswa'] === 'Lulus' && !empty($s['keterangan_lulus'])): ?>
                        <div class="text-primary fw-semibold mt-1" style="font-size:.67rem;line-height:1.2;">
                            <i class="bi bi-award-fill me-1"></i><?= e($s['keterangan_lulus']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="index.php?page=siswa&action=detail&id=<?= (int)$s['id'] ?>"
                   class="fw-bold text-dark text-decoration-none d-block"><?= e($s['nama_lengkap']) ?></a>
                <?php if ($s['nama_ortu']): ?>
                <div class="text-muted" style="font-size:.7rem;">
                    <i class="bi bi-person-heart me-1"></i><?= e($s['nama_ortu']) ?>
                </div>
                <?php endif; ?>
                <!-- Meta -->
                <div class="d-flex flex-wrap gap-2 mt-1">
                    <?php if ($s['nama_program']): ?>
                    <span class="badge bg-info bg-opacity-15 text-info border border-info border-opacity-25 small">
                        <?= e($s['nama_program']) ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($s['nomor_wa']): ?>
                    <a href="<?= e(wa_link($s['nomor_wa'], 'Halo '.$s['nama_lengkap'].', dari Perkasa Mulia Training Center.')) ?>" class="badge bg-success bg-opacity-10 text-success text-decoration-none small" target="_blank">
                        <i class="bi bi-whatsapp me-1"></i><?= e($s['nomor_wa']) ?> · Siswa
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($s['nomor_wa_ortu'])): ?>
                    <a href="<?= e(wa_link($s['nomor_wa_ortu'], 'Halo, kami dari Perkasa Mulia Training Center terkait ananda '.$s['nama_lengkap'].'.')) ?>" class="badge bg-secondary bg-opacity-10 text-secondary text-decoration-none small" target="_blank">
                        <i class="bi bi-whatsapp me-1"></i><?= e($s['nomor_wa_ortu']) ?> · Ortu
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Actions -->
            <div class="dropdown flex-shrink-0">
                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li>
                        <a class="dropdown-item small" href="index.php?page=siswa&action=detail&id=<?= (int)$s['id'] ?>">
                            <i class="bi bi-eye me-2 text-muted"></i>Detail
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item small" href="index.php?page=siswa&action=edit&id=<?= (int)$s['id'] ?>">
                            <i class="bi bi-pencil me-2 text-muted"></i>Edit
                        </a>
                    </li>
                    <?php if ($s['nomor_wa']): ?>
                    <li>
                        <a class="dropdown-item small" href="<?= e(wa_link($s['nomor_wa'], 'Halo '.$s['nama_lengkap'].', dari Perkasa Mulia Training Center.')) ?>" target="_blank">
                            <i class="bi bi-whatsapp me-2 text-success"></i>Kirim WA Siswa
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($s['nomor_wa_ortu'])): ?>
                    <li>
                        <a class="dropdown-item small" href="<?= e(wa_link($s['nomor_wa_ortu'], 'Halo, kami dari Perkasa Mulia Training Center terkait ananda '.$s['nama_lengkap'].'.')) ?>" target="_blank">
                            <i class="bi bi-whatsapp me-2 text-secondary"></i>Kirim WA Ortu/Wali
                        </a>
                    </li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button class="dropdown-item small text-danger"
                                onclick="pkConfirm('Hapus siswa <?= e(addslashes($s['nama_lengkap'])) ?>?', () => location.href='index.php?page=siswa&action=delete&id=<?= (int)$s['id'] ?>&_csrf_token=<?= e(csrf_token()) ?>')">
                            <i class="bi bi-trash me-2"></i>Hapus
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>

    <!-- Mobile Pagination -->
    <?php if ($last_pg > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <?php if ($halaman > 1): ?>
        <a href="?<?= http_build_query(array_merge($base_params, ['halaman' => $halaman - 1])) ?>"
           class="btn btn-outline-secondary btn-sm">← Sebelumnya</a>
        <?php else: ?><div></div><?php endif; ?>
        <small class="text-muted"><?= $halaman ?> / <?= $last_pg ?></small>
        <?php if ($halaman < $last_pg): ?>
        <a href="?<?= http_build_query(array_merge($base_params, ['halaman' => $halaman + 1])) ?>"
           class="btn btn-outline-secondary btn-sm">Berikutnya →</a>
        <?php else: ?><div></div><?php endif; ?>
    </div>
    <?php endif; ?>
</div>

</form><!-- /#bulkForm -->

<style>
/* Skeleton animation */
@keyframes pk-shimmer {
    0%   { background-position: -200% 0; }
    100% { background-position:  200% 0; }
}
.pk-skeleton {
    background: linear-gradient(90deg, #e9ecef 25%, #f8f9fa 50%, #e9ecef 75%);
    background-size: 200% 100%;
    animation: pk-shimmer 1.4s infinite;
    border-radius: 4px;
    height: 12px;
    display: inline-block;
}

/* xs button */
.btn-xs { font-size: .72rem; padding: 2px 7px; }

/* Mobile min-width fix */
.min-width-0 { min-width: 0; }
</style>

<script>
// ── Bulk select ──────────────────────────────────────────────────
function updateBulk() {
    const checks = document.querySelectorAll('.row-check:checked');
    const bar    = document.getElementById('bulkBar');
    const cnt    = document.getElementById('bulkCount');
    const all    = document.getElementById('checkAll');

    cnt.textContent = checks.length;
    bar.classList.toggle('d-none',  checks.length === 0);
    bar.classList.toggle('d-flex',  checks.length > 0);

    const total = document.querySelectorAll('.row-check').length;
    all.indeterminate = checks.length > 0 && checks.length < total;
    all.checked       = checks.length === total && total > 0;
}

function toggleAll(checked) {
    document.querySelectorAll('.row-check').forEach(c => c.checked = checked);
    updateBulk();
}

function clearBulk() {
    document.querySelectorAll('.row-check, #checkAll').forEach(c => c.checked = false);
    updateBulk();
}

// ── Per-page change ──────────────────────────────────────────────
function changePerPage(n) {
    const url = new URL(window.location);
    url.searchParams.set('per_page', n);
    url.searchParams.set('halaman', '1');
    window.location = url.toString();
}

// ── Debounced search ─────────────────────────────────────────────
let searchTimer;
document.getElementById('searchInput')?.addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        document.getElementById('filterForm').submit();
    }, 500);
});

// ── Keyboard: / → focus search ──────────────────────────────────
document.addEventListener('keydown', e => {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') return;
    if (e.key === '/') {
        e.preventDefault();
        document.getElementById('searchInput')?.focus();
    }
    if (e.key === 'n') {
        e.preventDefault();
        window.location = 'index.php?page=siswa&action=create';
    }
});
</script>

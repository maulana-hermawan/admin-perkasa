<?php /** app/modules/attendance/list.view.php */ ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Attendance Siswa</h4>
        <small class="text-muted">Pilih jadwal untuk mengisi atau melihat absensi.</small>
    </div>
</div>

<!-- Filter bulan -->
<div class="pk-card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <input type="hidden" name="page" value="attendance">
        <div class="col-md-3">
            <label class="form-label small fw-bold mb-1">Bulan</label>
            <input type="month" name="bulan" class="form-control form-control-sm" value="<?= e($f_bulan) ?>">
        </div>
        <div class="col-auto">
            <button class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Tampilkan</button>
        </div>
    </form>
</div>

<!-- List Jadwal -->
<div class="pk-card overflow-hidden">
    <div class="table-responsive">
        <table class="table pk-table pk-table-mobile align-middle mb-0 small">
            <thead class="table-dark">
                <tr>
                    <th class="ps-3">Tanggal</th>
                    <th>Kegiatan</th>
                    <th class="d-none d-md-table-cell">Program</th>
                    <th class="d-none d-md-table-cell">Waktu</th>
                    <th class="text-center">Absen</th>
                    <th class="text-center pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($jadwal_list)): ?>
                <tr><td colspan="6">
                    <div class="pk-empty-state"><i class="bi bi-calendar-x"></i>
                    <small>Tidak ada jadwal di <?= e(date('F Y',strtotime($f_bulan.'-01'))) ?>.</small>
                    </div>
                </td></tr>
                <?php else: foreach ($jadwal_list as $j): 
                    $pct = $total_siswa_aktif > 0 ? round(($j['sudah_absen']/$total_siswa_aktif)*100) : 0;
                ?>
                <tr>
                    <td class="ps-3 fw-bold" data-label="Tanggal">
                        <?= format_tanggal($j['tanggal'],'d M Y') ?>
                        <div class="text-muted" style="font-size:.68rem;"><?= date('l',strtotime($j['tanggal'])) ?></div>
                    </td>
                    <td data-label="Kegiatan">
                        <div class="fw-bold"><?= e($j['nama_kegiatan']??'—') ?></div>
                        <?php if($j['materi']): ?><div class="text-muted" style="font-size:.7rem;"><?= e(truncate($j['materi'],30)) ?></div><?php endif; ?>
                    </td>
                    <td class="d-none d-md-table-cell" data-label="Program">
                        <span class="badge bg-light text-dark border"><?= e($j['nama_program']??'—') ?></span>
                    </td>
                    <td class="d-none d-md-table-cell text-muted" data-label="Waktu">
                        <?= substr($j['waktu_mulai'],0,5) ?>–<?= substr($j['waktu_selesai'],0,5) ?>
                    </td>
                    <td class="text-center" data-label="Absen">
                        <?php if ($j['sudah_absen'] > 0): ?>
                        <span class="badge bg-success"><?= (int)$j['sudah_absen'] ?> / <?= $total_siswa_aktif ?></span>
                        <div class="progress mt-1" style="height:4px;width:60px;margin:0 auto;">
                            <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                        </div>
                        <?php else: ?>
                        <span class="badge bg-secondary">Belum</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center pe-3" data-label="">
                        <a href="index.php?page=attendance&action=form&jadwal_id=<?= (int)$j['id'] ?>"
                           class="btn btn-sm <?= $j['sudah_absen']>0?'btn-outline-primary':'btn-primary' ?>">
                            <i class="bi bi-<?= $j['sudah_absen']>0?'pencil-fill':'check2-square' ?> me-1"></i>
                            <?= $j['sudah_absen']>0 ? 'Edit' : 'Isi' ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

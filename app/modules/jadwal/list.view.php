<?php /** Jadwal list table — juga dipanggil dari calendar.view.php */ ?>
<div class="pk-card overflow-hidden <?= ($sub_v??'')!=='list' ? '' : 'mt-0' ?>">
    <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Detail Jadwal — <?= ($nama_bulan[$bulan]??'') ?> <?= $tahun ?></h6>
        <?php if (($sub_v??'')==='list'): ?>
        <a href="?page=jadwal&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-calendar3 me-1"></i>Kalender
        </a>
        <?php endif; ?>
    </div>
    <?php if (empty($jadwal_bulan)): ?>
    <div class="pk-empty-state"><i class="bi bi-calendar-x"></i><small>Tidak ada jadwal bulan ini</small></div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table pk-table small mb-0">
            <thead class="table-light">
                <tr><th class="ps-3">Tanggal</th><th>Kegiatan</th><th>Program</th><th>Waktu</th><th>Lokasi</th><th class="text-center pe-3">Aksi</th></tr>
            </thead>
            <tbody>
                <?php foreach ($jadwal_bulan as $j): ?>
                <tr>
                    <td class="ps-3 fw-bold"><?= format_tanggal($j['tanggal'],'d M Y') ?></td>
                    <td><?= e($j['nama_kegiatan']??'—') ?><?php if($j['materi']): ?><div class="text-muted" style="font-size:.7rem;"><?= e($j['materi']) ?></div><?php endif; ?></td>
                    <td><span class="badge bg-light text-dark border"><?= e($j['nama_program']??'—') ?></span></td>
                    <td class="text-muted"><?= substr($j['waktu_mulai'],0,5) ?>–<?= substr($j['waktu_selesai'],0,5) ?></td>
                    <td class="text-muted"><?= e($j['lokasi']??'—') ?></td>
                    <td class="text-center pe-3">
                        <button class="btn btn-sm btn-outline-danger"
                                onclick="pkConfirm('Hapus jadwal <?= e(addslashes(date('d M', strtotime($j['tanggal'])))) ?>?', () => location.href='index.php?page=jadwal&action=delete&id=<?= (int)$j['id'] ?>&_csrf_token=<?= e(csrf_token()) ?>')">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

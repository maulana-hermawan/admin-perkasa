<?php /** portal_siswa/profil.view.php */ ?>

<h5 class="fw-bold mb-3">Profil Saya</h5>

<div class="pk-mobile-card mb-3 text-center py-4">
    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10"
         style="width:72px;height:72px;font-size:2rem;font-weight:700;color:#198754;">
        <?= strtoupper(substr($siswa['nama_lengkap'],0,1)) ?>
    </div>
    <div class="fw-bold fs-5"><?= e($siswa['nama_lengkap']) ?></div>
    <div class="text-muted small"><?= e($siswa['nomor_induk']??'') ?></div>
    <span class="badge <?= ($siswa['status_siswa']??'')==='Aktif'?'bg-success':'bg-secondary' ?> mt-2">
        <?= e($siswa['status_siswa']??'') ?>
    </span>
</div>

<div class="pk-mobile-card mb-3">
    <h6 class="fw-bold mb-3">Informasi Pribadi</h6>
    <?php
    $fields = [
        'nomor_wa'        => ['WhatsApp', 'bi-whatsapp'],
        'nama_ortu'       => ['Nama Orang Tua', 'bi-people-fill'],
        'tanggal_lahir'   => ['Tanggal Lahir', 'bi-calendar-date'],
        'alamat'          => ['Alamat', 'bi-geo-alt-fill'],
        'asal_sekolah'    => ['Asal Sekolah', 'bi-building'],
        'target_seleksi'  => ['Target Seleksi', 'bi-bullseye'],
    ];
    foreach ($fields as $col => [$label, $icon]):
        $val = $siswa[$col] ?? '';
        if (!$val) continue;
        if ($col === 'tanggal_lahir') $val = date('d M Y', strtotime($val));
    ?>
    <div class="d-flex gap-3 align-items-start border-bottom py-2 small">
        <i class="bi <?= $icon ?> text-success mt-1" style="min-width:18px;"></i>
        <div>
            <div class="text-muted" style="font-size:.68rem;"><?= $label ?></div>
            <div class="fw-bold"><?= e($val) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="pk-mobile-card">
    <h6 class="fw-bold mb-2">Akun</h6>
    <div class="d-flex gap-3 align-items-center small border-bottom py-2">
        <i class="bi bi-envelope-fill text-primary" style="min-width:18px;"></i>
        <div>
            <div class="text-muted" style="font-size:.68rem;">Email Login</div>
            <div class="fw-bold"><?= e($siswa['email'] ?? '—') ?></div>
        </div>
    </div>
    <div class="mt-3">
        <a href="logout.php" class="btn btn-outline-danger btn-sm w-100">
            <i class="bi bi-power me-2"></i>Keluar
        </a>
    </div>
</div>

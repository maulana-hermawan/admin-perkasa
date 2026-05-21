<?php /** portal_tutor/nilai.view.php */ ?>
<h5 class="fw-bold mb-3">Input Nilai</h5>
<div class="pk-mobile-card text-center py-4">
    <i class="bi bi-bar-chart-fill d-block fs-2 mb-3 text-primary"></i>
    <p class="fw-bold">Input nilai dilakukan via panel admin.</p>
    <p class="text-muted small mb-3">Pilih kelompok nilai yang ingin diinput:</p>
    <div class="d-grid gap-2">
        <?php foreach(['jasmani'=>['Jasmani / Samapta','bi-lightning-charge-fill','success'],'mapel'=>['Mata Pelajaran','bi-book-fill','primary'],'skd'=>['SKD','bi-clipboard2-check-fill','info'],'psikologi'=>['Psikologi','bi-brain','secondary']] as $k=>[$lbl,$ic,$cl]): ?>
        <a href="index.php?page=nilai&action=create&kelompok=<?= $k ?>"
           class="btn btn-<?= $cl ?> fw-bold">
            <i class="bi <?= $ic ?> me-2"></i><?= $lbl ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

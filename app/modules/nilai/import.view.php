<?php
/**
 * app/modules/nilai/import.view.php
 * Upload Excel untuk bulk import nilai jasmani / SKD / mapel
 */
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Import Nilai dari Excel</h4>
        <small class="text-muted">Upload file .xlsx untuk input massal setelah tryout 50+ siswa.</small>
    </div>
    <a href="index.php?page=nilai" class="btn btn-outline-secondary btn-sm">← Kembali</a>
</div>

<!-- Tab pilih kelompok -->
<div class="d-flex gap-2 mb-4 flex-wrap">
    <?php
    $kelompok_import = get('kelompok','jasmani');
    $tab_opts = [
        'jasmani' => ['Jasmani','bi-lightning-charge-fill','success'],
        'skd'     => ['SKD',    'bi-clipboard2-check-fill','info'],
        'mapel'   => ['Mapel',  'bi-book-fill',            'primary'],
    ];
    foreach ($tab_opts as $k => [$lbl,$ic,$cl]):
    ?>
    <a href="?page=nilai&action=import&kelompok=<?= $k ?>"
       class="btn btn-sm <?= $kelompok_import===$k?"btn-$cl":"btn-outline-secondary" ?>">
        <i class="bi <?= $ic ?> me-1"></i><?= $lbl ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Template download -->
<div class="pk-card p-4 mb-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-download me-2 text-primary"></i>Langkah 1 — Download Template</h6>
    <p class="text-muted small mb-3">Download template Excel, isi data, lalu upload kembali.</p>
    <a href="index.php?page=nilai&action=template&kelompok=<?= e($kelompok_import) ?>"
       class="btn btn-outline-primary btn-sm">
        <i class="bi bi-file-earmark-spreadsheet me-2"></i>Download Template <?= strtoupper($kelompok_import) ?>.csv
    </a>
    <?php
    $cols = match($kelompok_import) {
        'jasmani' => ['nomor_induk_atau_nama','tanggal_tes (YYYY-MM-DD)','institusi (polri/tni)','gender (pria/wanita)','lari (meter)','pullup','situp','pushup','shuttle (detik)'],
        'skd'     => ['nomor_induk_atau_nama','tanggal_tes (YYYY-MM-DD)','twk','tiu','tkp'],
        'mapel'   => ['nomor_induk_atau_nama','tanggal_tes (YYYY-MM-DD)','bahasa_indonesia','bahasa_inggris','matematika','pengetahuan_umum','wawasan_kebangsaan'],
        default   => [],
    };
    ?>
    <div class="mt-3">
        <div class="text-muted fw-bold small mb-1">Kolom yang diperlukan:</div>
        <code class="d-block p-2 rounded" style="background:var(--color-background-secondary);font-size:.75rem;">
            <?= implode(', ', $cols) ?>
        </code>
    </div>
</div>

<!-- Upload form -->
<div class="pk-card p-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-upload me-2 text-success"></i>Langkah 2 — Upload File</h6>
    <form method="POST" action="index.php?page=nilai&action=process_import&kelompok=<?= e($kelompok_import) ?>"
          enctype="multipart/form-data" id="importForm">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label class="form-label fw-bold small">File Excel / CSV <span class="text-danger">*</span></label>
            <input type="file" name="import_file" class="form-control" accept=".xlsx,.xls,.csv" required
                   onchange="previewFileName(this)">
            <div class="form-text text-muted">Format: .xlsx, .xls, atau .csv · Maks 2MB</div>
            <div id="filePreview" class="mt-2 small text-success d-none">
                <i class="bi bi-check-circle-fill me-1"></i><span id="fileName"></span>
            </div>
        </div>
        <div class="mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="skip_header" value="1" id="skipHeader" checked>
                <label class="form-check-label small" for="skipHeader">
                    Baris pertama adalah header (dilewati saat import)
                </label>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success" id="submitBtn">
                <i class="bi bi-upload me-2"></i>Upload & Import
            </button>
        </div>
    </form>
</div>

<?php if (isset($import_result)): ?>
<div class="pk-card p-4 mt-4">
    <h6 class="fw-bold mb-3">Hasil Import</h6>
    <div class="row g-3 mb-3">
        <div class="col-4 text-center">
            <div class="fw-bold text-success fs-3"><?= (int)($import_result['success']??0) ?></div>
            <div class="text-muted small">Berhasil</div>
        </div>
        <div class="col-4 text-center">
            <div class="fw-bold text-warning fs-3"><?= (int)($import_result['skipped']??0) ?></div>
            <div class="text-muted small">Dilewati</div>
        </div>
        <div class="col-4 text-center">
            <div class="fw-bold text-danger fs-3"><?= (int)($import_result['error']??0) ?></div>
            <div class="text-muted small">Error</div>
        </div>
    </div>
    <?php if (!empty($import_result['errors'])): ?>
    <div class="alert alert-warning small">
        <strong>Detail Error:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach (array_slice($import_result['errors'],0,10) as $err): ?>
            <li><?= e($err) ?></li>
            <?php endforeach; ?>
            <?php if (count($import_result['errors']) > 10): ?>
            <li>...dan <?= count($import_result['errors'])-10 ?> error lainnya</li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
function previewFileName(input) {
    if (input.files && input.files[0]) {
        document.getElementById('fileName').textContent = input.files[0].name;
        document.getElementById('filePreview').classList.remove('d-none');
    }
}
document.getElementById('importForm').addEventListener('submit', function(e) {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
});
</script>

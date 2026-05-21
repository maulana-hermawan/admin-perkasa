<?php /** portal_siswa/nilai.view.php */ ?>

<h5 class="fw-bold mb-3">Nilai Saya</h5>

<!-- Trend Jasmani -->
<?php if (!empty($chart_jasmani)): ?>
<div class="pk-mobile-card mb-3">
    <h6 class="fw-bold mb-2"><i class="bi bi-lightning-charge-fill text-success me-2"></i>Trend Jasmani</h6>
    <canvas id="chartJasmani" height="180"></canvas>
</div>
<?php endif; ?>

<!-- Trend SKD -->
<?php if (!empty($chart_skd)): ?>
<div class="pk-mobile-card mb-3">
    <h6 class="fw-bold mb-2"><i class="bi bi-clipboard2-check-fill text-info me-2"></i>Trend SKD</h6>
    <canvas id="chartSKD" height="180"></canvas>
</div>
<?php endif; ?>

<!-- Tabel Jasmani -->
<?php if (!empty($nilai_jasmani)): ?>
<div class="pk-mobile-card mb-3">
    <h6 class="fw-bold mb-2">Riwayat Jasmani</h6>
    <?php foreach ($nilai_jasmani as $n):
        $predikat = $n['predikat'] ?? '';
        $pcolor = match(true) {
            str_contains($predikat,'Baik Sekali') => 'success',
            str_contains($predikat,'Baik')        => 'primary',
            str_contains($predikat,'Cukup')       => 'warning',
            default                               => 'danger',
        };
    ?>
    <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">
        <div>
            <div class="fw-bold"><?= e(date('d M Y',strtotime($n['tanggal_tes']))) ?></div>
        </div>
        <div class="text-end">
            <div class="fw-bold fs-6"><?= number_format((float)($n['total_skor']??$n['skor_akhir']??0),1) ?></div>
            <?php if ($predikat): ?>
            <span class="badge bg-<?= $pcolor ?>" style="font-size:.62rem;"><?= e($predikat) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Tabel SKD -->
<?php if (!empty($nilai_skd)): ?>
<div class="pk-mobile-card mb-3">
    <h6 class="fw-bold mb-2">Riwayat SKD</h6>
    <?php foreach ($nilai_skd as $n): ?>
    <div class="border-bottom py-2 small">
        <div class="d-flex justify-content-between">
            <span class="fw-bold"><?= e(date('d M Y',strtotime($n['tanggal_tes']))) ?></span>
            <span class="fw-bold fs-6"><?= (int)$n['total_skor'] ?></span>
        </div>
        <div class="d-flex gap-3 text-muted mt-1" style="font-size:.68rem;">
            <span>TWK: <strong><?= (int)$n['nilai_twk'] ?></strong><?= $n['nilai_twk']>=65?' ✓':' ✗' ?></span>
            <span>TIU: <strong><?= (int)$n['nilai_tiu'] ?></strong><?= $n['nilai_tiu']>=80?' ✓':' ✗' ?></span>
            <span>TKP: <strong><?= (int)$n['nilai_tkp'] ?></strong><?= $n['nilai_tkp']>=166?' ✓':' ✗' ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Psikologi -->
<?php if (!empty($nilai_psikologi)): ?>
<div class="pk-mobile-card mb-3">
    <h6 class="fw-bold mb-2"><i class="bi bi-brain text-secondary me-2"></i>Riwayat Psikologi</h6>
    <?php foreach ($nilai_psikologi as $n): ?>
    <div class="border-bottom py-2 small">
        <div class="d-flex justify-content-between">
            <span class="fw-bold"><?= e(date('d M Y',strtotime($n['tanggal_tes']))) ?></span>
            <span class="badge <?= $n['status_psikologi']==='Lulus'?'bg-success':'bg-danger' ?>"><?= e($n['status_psikologi']) ?></span>
        </div>
        <div class="d-flex gap-3 text-muted mt-1" style="font-size:.68rem;">
            <span>IQ: <strong><?= number_format((float)$n['kecerdasan'],1) ?></strong></span>
            <span>Cermat: <strong><?= number_format((float)$n['kecermatan'],1) ?></strong></span>
            <span>Pribadi: <strong><?= number_format((float)$n['kepribadian'],1) ?></strong></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($nilai_mapel)): ?>
<div class="pk-card overflow-hidden mb-3">
    <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 small"><i class="bi bi-book-fill text-primary me-2"></i>Mata Pelajaran</h6>
        <?php if (!empty($chart_mapel)): ?>
        <span class="badge bg-primary bg-opacity-10 text-primary">Rata-rata: <?= number_format((float)end($nilai_mapel)['rata_mapel'],1) ?></span>
        <?php endif; ?>
    </div>
    <?php if (!empty($chart_mapel)): ?>
    <div class="p-3" style="height:140px;position:relative;">
        <canvas id="chartMapel" role="img" aria-label="Trend nilai mapel"></canvas>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        new Chart(document.getElementById('chartMapel'), {
            type:'line',
            data:{
                labels:<?= json_encode($chart_mapel_labels) ?>,
                datasets:[{label:'Rata-rata Mapel',data:<?= json_encode($chart_mapel) ?>,borderColor:'#0d6efd',backgroundColor:'rgba(13,110,253,.08)',tension:.3,fill:true,pointRadius:4}]
            },
            options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:false,min:0,max:100,ticks:{font:{size:10}}}}}
        });
    });
    </script>
    <?php endif; ?>
    <div class="p-3">
        <?php
        $mapel_labels_display = ['b_indonesia'=>'B.Indonesia','b_inggris'=>'B.Inggris','matematika'=>'Matematika','pu'=>'PU','wk'=>'WK'];
        if ($latest_mapel):
        ?>
        <div class="text-muted small mb-2">Tes terbaru: <?= format_tanggal($latest_mapel['tanggal_tes']) ?></div>
        <div class="row g-2">
            <?php foreach ($mapel_labels_display as $field => $label): $v=(float)($latest_mapel[$field]??0);$ok=$v>=61; ?>
            <div class="col-6">
                <div class="d-flex justify-content-between align-items-center small">
                    <span class="text-muted"><?= $label ?></span>
                    <span class="fw-bold <?= $ok?'text-success':'text-danger' ?>"><?= $v>0?number_format($v,1):'—' ?></span>
                </div>
                <div class="progress mt-1" style="height:4px;border-radius:2px;">
                    <div class="progress-bar bg-<?= $ok?'success':'danger' ?>" style="width:<?= min(100,$v) ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top small">
            <span class="fw-bold">Rata-rata</span>
            <span class="fw-bold text-primary"><?= number_format((float)($latest_mapel['rata_mapel']??0),1) ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (empty($nilai_jasmani) && empty($nilai_skd) && empty($nilai_psikologi) && empty($nilai_mapel)): ?>
<div class="pk-mobile-card text-center py-4">
    <i class="bi bi-bar-chart fs-2 d-block mb-2 opacity-25 text-muted"></i>
    <p class="text-muted small mb-0">Belum ada data nilai.</p>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    <?php if (!empty($chart_jasmani)): ?>
    new Chart(document.getElementById('chartJasmani'), {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Skor Jasmani',
                data: <?= json_encode($chart_jasmani) ?>,
                borderColor: '#198754', backgroundColor: 'rgba(25,135,84,.1)',
                tension: .35, fill: true, pointRadius: 4
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: false, min: 0, max: 100, ticks: { font: { size: 10 } } },
                x: { ticks: { font: { size: 10 } } }
            }
        }
    });
    <?php endif; ?>
    <?php if (!empty($chart_skd)): ?>
    new Chart(document.getElementById('chartSKD'), {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_skd_labels) ?>,
            datasets: [{
                label: 'Total SKD',
                data: <?= json_encode($chart_skd) ?>,
                borderColor: '#0dcaf0', backgroundColor: 'rgba(13,202,240,.1)',
                tension: .35, fill: true, pointRadius: 4
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: false, ticks: { font: { size: 10 } } },
                x: { ticks: { font: { size: 10 } } }
            }
        }
    });
    <?php endif; ?>
});
</script>

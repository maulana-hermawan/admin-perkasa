<?php
/**
 * app/modules/nilai/form.view.php — Rombakan 4 Kelompok Nilai
 * Tab: Jasmani | Mapel | SKD | Psikologi
 */

$tab_config = [
    'jasmani'   => ['icon'=>'bi-lightning-charge-fill', 'label'=>'Jasmani / Samapta',   'color'=>'success'],
    'mapel'     => ['icon'=>'bi-book-fill',             'label'=>'Mata Pelajaran',       'color'=>'primary'],
    'skd'       => ['icon'=>'bi-clipboard2-check-fill', 'label'=>'SKD',                  'color'=>'info'],
    'psikologi' => ['icon'=>'bi-brain',                 'label'=>'Psikologi',            'color'=>'purple'],
];
$cfg = $tab_config[$kelompok];
?>

<style>
.live-score-badge { display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;font-size:.77rem;font-weight:500;transition:all .2s;min-width:70px; }
.ls-empty  { background:var(--color-background-secondary);color:var(--color-text-secondary); }
.ls-bs     { background:#d1fae5;color:#065f46; }
.ls-b      { background:#d1e7dd;color:#0a5c36; }
.ls-c      { background:#fff3cd;color:#854f0b; }
.ls-k      { background:#ffe0b2;color:#bf360c; }
.ls-ks     { background:#fce4ec;color:#b71c1c; }
.skor-box  { border-radius:10px;padding:.9rem 1.1rem;transition:all .3s;background:var(--color-background-secondary); }
.skor-box.bs{background:linear-gradient(135deg,#d1fae5,#a7f3d0);border-left:4px solid #10b981;}
.skor-box.b {background:linear-gradient(135deg,#d1e7dd,#a3cfbb);border-left:4px solid #198754;}
.skor-box.c {background:linear-gradient(135deg,#fff3cd,#fde68a);border-left:4px solid #ffc107;}
.skor-box.k {background:linear-gradient(135deg,#ffe0b2,#ffcc80);border-left:4px solid #ff9800;}
.skor-box.ks{background:linear-gradient(135deg,#fce4ec,#f8bbd9);border-left:4px solid #e91e63;}
.text-purple{color:#534AB7 !important;}
.mapel-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
@media(max-width:640px){.mapel-row{grid-template-columns:1fr;}}
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Input Nilai — <?= e($cfg['label']) ?></h4>
        <small class="text-muted">Pilih kelompok nilai di bawah sesuai sesi tryout hari ini.</small>
    </div>
    <a href="index.php?page=nilai&kelompok=<?= e($kelompok) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar
    </a>
</div>

<!-- Tab Navigator 4 Kelompok -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <?php foreach ($tab_config as $k => $c): ?>
    <a href="?page=nilai&action=create&kelompok=<?= $k ?>"
       class="btn btn-sm <?= $kelompok===$k ? "btn-{$c['color']}" : 'btn-outline-secondary' ?> d-flex align-items-center gap-1">
        <i class="bi <?= $c['icon'] ?>"></i>
        <span class="d-none d-sm-inline"><?= $c['label'] ?></span>
        <span class="d-sm-none"><?= explode(' ',$c['label'])[0] ?></span>
    </a>
    <?php endforeach; ?>
</div>

<?php if ($kelompok === 'jasmani'): ?>
<!-- ══ FORM JASMANI (Samapta) ══════════════════════════════ -->
<form method="POST" action="index.php?page=nilai&action=store_jasmani" novalidate>
    <?= csrf_field() ?>
    <script>const ST = <?= $tables_json ?? '{}' ?>;</script>

    <div class="row g-4">
        <div class="col-lg-7">
            <!-- Identitas -->
            <div class="pk-card p-4 mb-3">
                <h6 class="fw-bold mb-3 text-success"><i class="bi bi-person-badge me-2"></i>Identitas</h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-bold small">Peserta <span class="text-danger">*</span></label>
                        <select name="siswa_id" class="form-select ts-search" required onchange="autoSetKategori(this)">
                            <option value="">— Pilih siswa aktif —</option>
                            <?php foreach ($daftar_siswa as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"
                                    data-target="<?= e($s['target_seleksi']??'') ?>"
                                    data-gender="<?= e($s['jenis_kelamin']??'') ?>">
                                <?= e($s['nomor_induk']??'') ?> — <?= e($s['nama_lengkap']) ?>
                                <?php if($s['target_seleksi']): ?>(<?= e($s['target_seleksi']) ?>)<?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_tes" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Institusi Target</label>
                        <div class="d-flex gap-3 mt-1">
                            <?php foreach(['polri'=>'Polri','tni'=>'TNI'] as $v=>$l): ?>
                            <div class="form-check"><input class="form-check-input" type="radio" name="institusi" id="inst-<?= $v ?>" value="<?= $v ?>" <?= $v==='polri'?'checked':'' ?> onchange="updateBinjasLive()"><label class="form-check-label small fw-bold" for="inst-<?= $v ?>"><?= $l ?></label></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Jenis Kelamin</label>
                        <div class="d-flex gap-3 mt-1">
                            <?php foreach(['pria'=>'Pria','wanita'=>'Wanita'] as $v=>$l): ?>
                            <div class="form-check"><input class="form-check-input" type="radio" name="gender" id="gen-<?= $v ?>" value="<?= $v ?>" <?= $v==='pria'?'checked':'' ?> onchange="updateBinjasLive()"><label class="form-check-label small" for="gen-<?= $v ?>"><?= $l ?></label></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Samapta A: Lari -->
            <div class="pk-card p-4 mb-3">
                <h6 class="fw-bold mb-3 text-success"><i class="bi bi-signpost-2-fill me-2"></i>Samapta A — Lari 12 Menit</h6>
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-bold small">Jarak Tempuh (meter) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" id="bin-lari" name="lari" class="form-control" min="0" placeholder="mis: 2400" oninput="updateBinjasLive()">
                            <span class="input-group-text bg-light text-muted small">meter</span>
                        </div>
                        <div class="form-text text-muted" style="font-size:.68rem;">Semakin jauh = skor lebih tinggi</div>
                    </div>
                    <div class="col-md-7 d-flex align-items-end">
                        <div id="badge-lari" class="live-score-badge ls-empty ms-2">—</div>
                    </div>
                </div>
            </div>

            <!-- Samapta B -->
            <div class="pk-card p-4 mb-3">
                <h6 class="fw-bold mb-3 text-success"><i class="bi bi-activity me-2"></i>Samapta B — Kekuatan & Kelincahan</h6>
                <div class="alert alert-warning py-2 small mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Samapta B wajib semua diisi. Nilai nol pada salah satu item = <strong>TMS (Tidak Memenuhi Syarat)</strong>.
                </div>
                <div class="row g-3">
                    <?php
                    $samb = [
                        ['bin-pullup','pullup','Pull Up / Chinning','badge-pullup','kali','Wanita: Chinning'],
                        ['bin-situp', 'situp', 'Sit Up (1 menit)',  'badge-situp', 'kali',''],
                        ['bin-pushup','pushup','Push Up (1 menit)', 'badge-pushup','kali',''],
                        ['bin-shuttle','shuttle','Shuttle Run',      'badge-shuttle','detik','Waktu (dtk) — semakin kecil makin baik'],
                    ];
                    foreach ($samb as [$id,$name,$label,$bid,$unit,$helper]):
                    ?>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small"><?= $label ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" id="<?= $id ?>" name="<?= $name ?>" class="form-control"
                                   min="0" step="<?= $name==='shuttle'?'0.1':'1' ?>" placeholder="0"
                                   oninput="updateBinjasLive()">
                            <span class="input-group-text bg-light text-muted small"><?= $unit ?></span>
                        </div>
                        <div id="<?= $bid ?>" class="live-score-badge ls-empty mt-1">—</div>
                        <?php if($helper): ?><div class="form-text text-muted" style="font-size:.68rem;"><?= $helper ?></div><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Opsional -->
            <div class="pk-card p-4 mb-3">
                <h6 class="fw-bold mb-3 text-muted">Opsional</h6>
                <div class="mb-2">
                    <div class="form-check form-switch mb-1"><input class="form-check-input" type="checkbox" id="incl-lunges" onchange="toggleSection('sec-lunges',this.checked);updateBinjasLive()"><label class="form-check-label small fw-bold" for="incl-lunges">Lunges</label></div>
                    <div id="sec-lunges" style="display:none;">
                        <div class="input-group" style="max-width:220px;"><input type="number" id="bin-lunges" name="lunges" class="form-control" min="0" placeholder="0" oninput="updateBinjasLive()"><span class="input-group-text bg-light text-muted small">kali</span></div>
                        <div id="badge-lunges" class="live-score-badge ls-empty mt-1">—</div>
                    </div>
                </div>
                <div>
                    <div class="form-check form-switch mb-1"><input class="form-check-input" type="checkbox" id="incl-renang" onchange="toggleSection('sec-renang',this.checked);updateBinjasLive()"><label class="form-check-label small fw-bold" for="incl-renang">Renang (bobot 20%)</label></div>
                    <div id="sec-renang" style="display:none;">
                        <div class="row g-2" style="max-width:380px;">
                            <div class="col-7"><div class="input-group"><input type="number" id="bin-renang" name="renang" class="form-control" min="0" step="0.1" placeholder="0.0" oninput="updateBinjasLive()"><span class="input-group-text bg-light text-muted small">dtk</span></div></div>
                            <div class="col-5" id="renang-gaya-wrap" style="display:none;"><select id="renang-gaya" name="renang_gaya" class="form-select form-select-sm" onchange="updateBinjasLive()"><option value="dada">Gaya Dada</option><option value="bebas">Gaya Bebas</option></select></div>
                        </div>
                        <div id="badge-renang" class="live-score-badge ls-empty mt-1">—</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kanan: Preview -->
        <div class="col-lg-5">
            <div class="skor-box mb-3" id="skor-total-box">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="text-muted fw-bold" style="font-size:.65rem;text-transform:uppercase;letter-spacing:.06em;">SKOR JASMANI</div>
                        <div class="fw-bold" id="skor-total-num" style="font-size:2.5rem;line-height:1;">—</div>
                        <div class="fw-bold" id="skor-predikat" style="font-size:1rem;">Isi nilai dulu</div>
                    </div>
                    <div class="text-end">
                        <div id="skor-lulus" class="fw-bold" style="font-size:.85rem;"></div>
                        <div class="text-muted mt-1" style="font-size:.7rem;">PG Polri ≥ 61 | TNI ≥ 41</div>
                    </div>
                </div>
            </div>
            <div class="pk-card p-3 mb-3">
                <div class="text-muted fw-bold mb-2" style="font-size:.65rem;text-transform:uppercase;letter-spacing:.06em;">SKOR PER ITEM</div>
                <table class="table table-sm small mb-0">
                    <tbody>
                        <?php foreach(['badge-lari'=>'Lari','badge-pullup'=>'Pull Up','badge-situp'=>'Sit Up','badge-pushup'=>'Push Up','badge-shuttle'=>'Shuttle','badge-lunges'=>'Lunges','badge-renang'=>'Renang'] as $bid=>$blbl): ?>
                        <tr><td class="text-muted py-1"><?= $blbl ?></td><td class="py-1"><span id="tbl-<?= $bid ?>" class="text-muted small">—</span></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-success fw-bold"><i class="bi bi-save-fill me-2"></i>Simpan & Hitung Skor</button>
                <a href="index.php?page=nilai&kelompok=jasmani" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </div>
</form>

<script>
/* Live preview binjas — port dari kalkulator.js */
function _bs(val,table){if(!table||val<=0)return 0;const isTime=table[table.length-1]===true;for(const e of table){if(typeof e==='boolean')continue;const[t,s]=e;if(isTime?val<=t:val>=t)return s;}return 1;}
function _bp(s){if(s>=81)return{l:'Baik Sekali',c:'bs'};if(s>=71)return{l:'Baik',c:'b'};if(s>=61)return{l:'Cukup',c:'c'};if(s>=41)return{l:'Kurang',c:'k'};return{l:'Kurang Sekali',c:'ks'};}
function updateBinjasLive(){
    const inst=document.querySelector('input[name="institusi"]:checked')?.value||'polri';
    const gen=document.querySelector('input[name="gender"]:checked')?.value||'pria';
    const tb=ST[inst]?.[gen];if(!tb)return;
    const pu=gen==='wanita'?'chinning':'pullup';
    const pg=inst==='polri'?61:41;
    const gV=id=>parseFloat(document.getElementById(id)?.value)||0;
    const nL=_bs(gV('bin-lari'),tb.lari),nP=_bs(gV('bin-pullup'),tb[pu]),nS=_bs(gV('bin-situp'),tb.situp),nPu=_bs(gV('bin-pushup'),tb.pushup),nSh=_bs(gV('bin-shuttle'),tb.shuttle);
    const sb=(bid,s,hv)=>{const el=document.getElementById(bid);if(!el)return;if(!hv||s===0){el.className='live-score-badge ls-empty';el.textContent='—';}else{const r=_bp(s);el.className='live-score-badge ls-'+r.c;el.textContent=s+' · '+r.l;}};
    sb('badge-lari',nL,gV('bin-lari')>0);sb('badge-pullup',nP,gV('bin-pullup')>0);sb('badge-situp',nS,gV('bin-situp')>0);sb('badge-pushup',nPu,gV('bin-pushup')>0);sb('badge-shuttle',nSh,gV('bin-shuttle')>0);
    const iL=document.getElementById('incl-lunges')?.checked;let nLg=null;
    if(iL&&gV('bin-lunges')>0&&tb.lunges){nLg=_bs(gV('bin-lunges'),tb.lunges);sb('badge-lunges',nLg,true);}else sb('badge-lunges',0,false);
    const iR=document.getElementById('incl-renang')?.checked;let nR=null;
    if(iR&&gV('bin-renang')>0){let rt;if(inst==='tni'&&tb.renang?.dada){const g=document.getElementById('renang-gaya')?.value||'dada';rt=tb.renang[g];}else rt=tb.renang;nR=_bs(gV('bin-renang'),rt);sb('badge-renang',nR,true);}else sb('badge-renang',0,false);
    const bi=[nP,nS,nPu,nSh];if(iL&&nLg&&nLg>0)bi.push(nLg);
    const anyFilled=gV('bin-lari')>0||gV('bin-pullup')>0;if(!anyFilled)return;
    const avgB=bi.reduce((a,b)=>a+b,0)/bi.length;const nilAB=(nL+avgB)/2;
    const hasZ=nL===0||[nP,nS,nPu,nSh].includes(0);const lulus=nilAB>=pg&&!hasZ;
    const pred=_bp(nilAB);const tot=Math.round(nilAB);
    const box=document.getElementById('skor-total-box');
    if(box){box.className='skor-box '+pred.c;document.getElementById('skor-total-num').textContent=tot;document.getElementById('skor-predikat').textContent=pred.l;const lu=document.getElementById('skor-lulus');lu.textContent=lulus?'✅ Lulus':'❌ TMS';lu.style.color=lulus?'#198754':'#dc3545';}
}
function toggleSection(id,show){const el=document.getElementById(id);if(el)el.style.display=show?'':'none';const isTni=document.querySelector('input[name="institusi"]:checked')?.value==='tni';const inclR=document.getElementById('incl-renang')?.checked;const gw=document.getElementById('renang-gaya-wrap');if(gw)gw.style.display=(isTni&&inclR)?'':'none';}
function autoSetKategori(sel){const opt=sel.options[sel.selectedIndex];const target=opt.dataset.target||'';const gender=opt.dataset.gender||'';const isTni=['TNI AD','TNI AL','TNI AU','Akmil'].includes(target);const inst=isTni?'tni':'polri';const gen=gender==='P'?'wanita':'pria';const ir=document.getElementById('inst-'+inst);const gr=document.getElementById('gen-'+gen);if(ir)ir.checked=true;if(gr)gr.checked=true;updateBinjasLive();}
</script>

<?php elseif ($kelompok === 'mapel'): ?>
<!-- ══ FORM MAPEL ══════════════════════════════════════════ -->
<form method="POST" action="index.php?page=nilai&action=store_mapel" novalidate>
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="pk-card p-4 mb-3">
                <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-person-badge me-2"></i>Identitas</h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-bold small">Peserta <span class="text-danger">*</span></label>
                        <select name="siswa_id" class="form-select ts-search" required>
                            <option value="">— Pilih siswa —</option>
                            <?php foreach ($daftar_siswa as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"><?= e($s['nomor_induk']??'') ?> — <?= e($s['nama_lengkap']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold small">Tanggal Tes <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_tes" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
            </div>
            <div class="pk-card p-4 mb-3">
                <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-book-fill me-2"></i>Nilai Mata Pelajaran</h6>
                <div class="alert alert-info py-2 small mb-3">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    Nilai min passing: <strong>61</strong>. Semua mapel harus ≥ 61 untuk status Lulus.
                </div>
                <div class="mapel-row">
                    <?php foreach ($mapel_labels as $field => $label): ?>
                    <div class="pk-field">
                        <label class="form-label fw-bold small"><?= e($label) ?></label>
                        <div class="input-group">
                            <input type="number" name="<?= $field ?>" id="mpl-<?= $field ?>"
                                   class="form-control" min="0" max="100" step="0.5" placeholder="0"
                                   oninput="updateMapelPreview()">
                            <span class="input-group-text bg-light text-muted small">/ 100</span>
                        </div>
                        <div id="mpl-badge-<?= $field ?>" class="mt-1" style="font-size:.72rem;"></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="col-12 mt-3">
                    <label class="form-label fw-bold small">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan sesi tryout mapel..."></textarea>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="skor-box mb-3" id="mapel-preview-box" style="background:var(--color-background-secondary);">
                <div class="text-muted fw-bold mb-1" style="font-size:.65rem;text-transform:uppercase;letter-spacing:.06em;">RATA-RATA MAPEL</div>
                <div class="fw-bold" id="mapel-rata" style="font-size:2.5rem;line-height:1;">—</div>
                <div id="mapel-status" class="fw-bold mt-1" style="font-size:.9rem;">Isi nilai dulu</div>
            </div>
            <div class="pk-card p-3 mb-3">
                <?php foreach ($mapel_labels as $field => $label): ?>
                <div class="d-flex justify-content-between small py-1 border-bottom">
                    <span class="text-muted"><?= e($label) ?></span>
                    <span id="tbl-mpl-<?= $field ?>" class="text-muted">—</span>
                </div>
                <?php endforeach; ?>
                <div class="d-flex justify-content-between small py-1 fw-bold mt-1">
                    <span>Rata-rata</span>
                    <span id="tbl-mpl-rata" class="text-primary">—</span>
                </div>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-save-fill me-2"></i>Simpan Nilai Mapel</button>
                <a href="index.php?page=nilai&kelompok=mapel" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </div>
</form>
<script>
const MAPEL_FIELDS = <?= json_encode(array_keys($mapel_labels)) ?>;
function updateMapelPreview() {
    let sum=0,cnt=0,allPass=true;
    MAPEL_FIELDS.forEach(f=>{
        const v=parseFloat(document.getElementById('mpl-'+f)?.value)||0;
        const el=document.getElementById('tbl-mpl-'+f);
        const bg=document.getElementById('mpl-badge-'+f);
        if(el)el.textContent=v?v.toFixed(1):'—';
        if(v>0){
            sum+=v;cnt++;
            const ok=v>=61;
            if(!ok)allPass=false;
            if(bg)bg.innerHTML=`<span style="color:${ok?'#198754':'#dc3545'};font-weight:500;">${ok?'✓ Lulus':'✗ < 61'}</span>`;
        }else{if(bg)bg.textContent='';allPass=false;}
    });
    const rata=cnt>0?sum/cnt:0;
    const rataEl=document.getElementById('mapel-rata');
    const statEl=document.getElementById('mapel-status');
    const tblRata=document.getElementById('tbl-mpl-rata');
    const box=document.getElementById('mapel-preview-box');
    if(rataEl)rataEl.textContent=cnt>0?rata.toFixed(1):'—';
    if(tblRata)tblRata.textContent=cnt>0?rata.toFixed(1):'—';
    if(statEl&&box){
        if(cnt===0){statEl.textContent='Isi nilai dulu';box.className='skor-box';box.style.background='var(--color-background-secondary)';}
        else if(allPass&&cnt===MAPEL_FIELDS.length){statEl.textContent='✅ Lulus — semua mapel ≥ 61';box.className='skor-box b';box.style.background='';}
        else{statEl.textContent='❌ Ada mapel < 61';box.className='skor-box ks';box.style.background='';}
    }
}
</script>

<?php elseif ($kelompok === 'skd'): ?>
<!-- ══ FORM SKD ══════════════════════════════════════════ -->
<form method="POST" action="index.php?page=nilai&action=store_skd" novalidate>
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="pk-card p-4 mb-3">
                <h6 class="fw-bold mb-3 text-info"><i class="bi bi-person-badge me-2"></i>Identitas</h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-bold small">Peserta <span class="text-danger">*</span></label>
                        <select name="siswa_id" class="form-select ts-search" required>
                            <option value="">— Pilih siswa —</option>
                            <?php foreach ($daftar_siswa as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['nomor_induk']??'') ?> — <?= e($s['nama_lengkap']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5"><label class="form-label fw-bold small">Tanggal <span class="text-danger">*</span></label><input type="date" name="tanggal_tes" class="form-control" required value="<?= date('Y-m-d') ?>"></div>
                </div>
            </div>
            <div class="pk-card p-4 mb-3">
                <h6 class="fw-bold mb-3 text-info"><i class="bi bi-clipboard2-check-fill me-2"></i>Nilai SKD</h6>
                <div class="alert alert-info py-2 small mb-3">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    Internal bimbel: total ≥ 61 = Lulus. Standar CPNS resmi: TWK≥65, TIU≥80, TKP≥166.
                </div>
                <div class="row g-3">
                    <?php
                    $skd_items = [
                        ['twk','TWK — Tes Wawasan Kebangsaan','0','150','PG CPNS ≥ 65'],
                        ['tiu','TIU — Tes Intelejensi Umum',  '0','175','PG CPNS ≥ 80'],
                        ['tkp','TKP — Karakteristik Pribadi', '0','225','PG CPNS ≥ 166'],
                    ];
                    foreach ($skd_items as [$name,$label,$min,$max,$hint]):
                    ?>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small"><?= $label ?></label>
                        <input type="number" name="<?= $name ?>" id="skd-<?= $name ?>" class="form-control" min="<?= $min ?>" max="<?= $max ?>" placeholder="0" oninput="updateSKD()">
                        <div class="form-text text-muted" style="font-size:.68rem;"><?= $hint ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="skor-box mb-3" id="skd-box" style="background:var(--color-background-secondary);">
                <div class="text-muted fw-bold mb-1" style="font-size:.65rem;text-transform:uppercase;letter-spacing:.06em;">TOTAL SKD</div>
                <div class="fw-bold" id="skd-total" style="font-size:2.5rem;line-height:1;">—</div>
                <div id="skd-status" class="fw-bold mt-1" style="font-size:.9rem;">Isi nilai dulu</div>
            </div>
            <div class="pk-card p-3 mb-3">
                <?php foreach(['twk'=>['TWK','65'],'tiu'=>['TIU','80'],'tkp'=>['TKP','166']] as $k=>[$l,$pg]): ?>
                <div class="d-flex justify-content-between align-items-center small py-1 border-bottom">
                    <span class="text-muted"><?= $l ?></span>
                    <div class="d-flex align-items-center gap-2">
                        <span id="tbl-skd-<?= $k ?>" class="fw-bold">—</span>
                        <span id="pg-skd-<?= $k ?>" class="text-muted" style="font-size:.68rem;">PG CPNS ≥<?= $pg ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-info text-white fw-bold"><i class="bi bi-save-fill me-2"></i>Simpan Nilai SKD</button>
                <a href="index.php?page=nilai&kelompok=skd" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </div>
</form>
<script>
function updateSKD() {
    const twk=parseInt(document.getElementById('skd-twk')?.value)||0;
    const tiu=parseInt(document.getElementById('skd-tiu')?.value)||0;
    const tkp=parseInt(document.getElementById('skd-tkp')?.value)||0;
    const tot=twk+tiu+tkp;
    const lulusInt=tot>=61;const lulusCPNS=(twk>=65&&tiu>=80&&tkp>=166);
    document.getElementById('skd-total').textContent=tot||'—';
    const vals={twk:[twk,65],tiu:[tiu,80],tkp:[tkp,166]};
    Object.entries(vals).forEach(([k,[v,pg]])=>{
        const el=document.getElementById('tbl-skd-'+k);const pg_el=document.getElementById('pg-skd-'+k);
        if(el)el.textContent=v||'—';
        if(pg_el&&v>0)pg_el.style.color=v>=pg?'#198754':'#dc3545';
    });
    const box=document.getElementById('skd-box');const stat=document.getElementById('skd-status');
    if(!twk&&!tiu&&!tkp){if(stat)stat.textContent='Isi nilai dulu';if(box){box.className='skor-box';box.style.background='var(--color-background-secondary)';}return;}
    if(lulusCPNS){if(stat)stat.textContent='✅ Lulus — termasuk standar CPNS';if(box)box.className='skor-box bs';}
    else if(lulusInt){if(stat)stat.textContent='✅ Lulus internal (total ≥ 61)';if(box)box.className='skor-box b';}
    else{if(stat)stat.textContent='❌ Tidak Lulus (total < 61)';if(box)box.className='skor-box ks';}
    if(box)box.style.background='';
}
</script>

<?php else: ?>
<!-- ══ FORM PSIKOLOGI ══════════════════════════════════════ -->
<form method="POST" action="index.php?page=nilai&action=store_psikologi" novalidate>
    <?= csrf_field() ?>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="pk-card p-4 mb-3">
                <h6 class="fw-bold mb-3 text-purple"><i class="bi bi-person-badge me-2"></i>Identitas</h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-bold small">Peserta <span class="text-danger">*</span></label>
                        <select name="siswa_id" class="form-select ts-search" required>
                            <option value="">— Pilih siswa —</option>
                            <?php foreach ($daftar_siswa as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['nomor_induk']??'') ?> — <?= e($s['nama_lengkap']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5"><label class="form-label fw-bold small">Tanggal <span class="text-danger">*</span></label><input type="date" name="tanggal_tes" class="form-control" required value="<?= date('Y-m-d') ?>"></div>
                    <div class="col-md-7">
                        <label class="form-label fw-bold small">Sumber</label>
                        <select name="sumber" class="form-select">
                            <option value="Manual">Manual (input langsung)</option>
                            <option value="CAT">CAT (dari sistem psikotes)</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small">Referensi Session CAT</label>
                        <input type="text" name="sumber_referensi" class="form-control" placeholder="session_id dari psikotes CAT (opsional)">
                    </div>
                </div>
            </div>
            <div class="pk-card p-4 mb-3">
                <h6 class="fw-bold mb-3 text-purple"><i class="bi bi-brain me-2"></i>Nilai Sub-Tes Psikologi</h6>
                <div class="alert alert-info py-2 small mb-3">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    Semua sub-tes ≥ <strong>61</strong> = Lulus. Skala 0–100.
                </div>
                <div class="row g-3">
                    <?php
                    $psi_items = [
                        ['kecerdasan', 'Kecerdasan (IQ)',         'Nilai tes kecerdasan / kemampuan intelektual'],
                        ['kecermatan', 'Kecermatan (Ketelitian)', 'Nilai tes kecermatan/konsentrasi'],
                        ['kepribadian','Kepribadian',             'Nilai tes kepribadian/karakter'],
                    ];
                    foreach ($psi_items as [$name,$label,$helper]):
                    ?>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small"><?= $label ?></label>
                        <div class="input-group">
                            <input type="number" name="<?= $name ?>" id="psi-<?= $name ?>" class="form-control" min="0" max="100" step="0.5" placeholder="0" oninput="updatePsiPreview()">
                            <span class="input-group-text bg-light text-muted small">/ 100</span>
                        </div>
                        <div id="psi-badge-<?= $name ?>" class="mt-1" style="font-size:.72rem;"></div>
                        <div class="form-text text-muted" style="font-size:.68rem;"><?= $helper ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3">
                    <label class="form-label fw-bold small">Catatan</label>
                    <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan psikolog / pengamat..."></textarea>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="skor-box mb-3" id="psi-box" style="background:var(--color-background-secondary);">
                <div class="text-muted fw-bold mb-1" style="font-size:.65rem;text-transform:uppercase;letter-spacing:.06em;">RATA-RATA PSIKOLOGI</div>
                <div class="fw-bold" id="psi-rata" style="font-size:2.5rem;line-height:1;">—</div>
                <div id="psi-status" class="fw-bold mt-1" style="font-size:.9rem;">Isi nilai dulu</div>
            </div>
            <div class="pk-card p-3 mb-3">
                <?php foreach (['kecerdasan'=>'Kecerdasan','kecermatan'=>'Kecermatan','kepribadian'=>'Kepribadian'] as $k=>$l): ?>
                <div class="d-flex justify-content-between small py-1 border-bottom">
                    <span class="text-muted"><?= $l ?></span>
                    <div class="d-flex align-items-center gap-2">
                        <span id="tbl-psi-<?= $k ?>" class="fw-bold">—</span>
                        <span id="pg-psi-<?= $k ?>" class="text-muted" style="font-size:.68rem;">min 61</span>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="d-flex justify-content-between small py-1 fw-bold mt-1"><span>Rata-rata</span><span id="tbl-psi-rata" class="text-purple">—</span></div>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn fw-bold" style="background:#534AB7;color:#fff;"><i class="bi bi-save-fill me-2"></i>Simpan Nilai Psikologi</button>
                <a href="index.php?page=nilai&kelompok=psikologi" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </div>
</form>
<script>
const PSI_FIELDS=['kecerdasan','kecermatan','kepribadian'];
function updatePsiPreview(){
    let sum=0,cnt=0,allPass=true;
    PSI_FIELDS.forEach(f=>{
        const v=parseFloat(document.getElementById('psi-'+f)?.value)||0;
        const el=document.getElementById('tbl-psi-'+f);const bg=document.getElementById('psi-badge-'+f);const pg=document.getElementById('pg-psi-'+f);
        if(el)el.textContent=v?v.toFixed(1):'—';
        if(v>0){sum+=v;cnt++;const ok=v>=61;if(!ok)allPass=false;if(bg)bg.innerHTML=`<span style="color:${ok?'#198754':'#dc3545'};font-weight:500;">${ok?'✓ ≥ 61':'✗ < 61'}</span>`;if(pg)pg.style.color=ok?'#198754':'#dc3545';}else{if(bg)bg.textContent='';allPass=false;}
    });
    const rata=cnt>0?sum/cnt:0;
    document.getElementById('tbl-psi-rata').textContent=cnt>0?rata.toFixed(1):'—';
    document.getElementById('psi-rata').textContent=cnt>0?rata.toFixed(1):'—';
    const box=document.getElementById('psi-box');const stat=document.getElementById('psi-status');
    if(cnt===0){if(stat)stat.textContent='Isi nilai dulu';if(box){box.className='skor-box';box.style.background='var(--color-background-secondary)';}return;}
    if(allPass&&cnt===3){if(stat)stat.textContent='✅ Lulus — semua sub-tes ≥ 61';if(box)box.className='skor-box b';}
    else{if(stat)stat.textContent='❌ Ada sub-tes < 61';if(box)box.className='skor-box ks';}
    if(box)box.style.background='';
}
</script>
<?php endif; ?>

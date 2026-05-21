<?php
/** app/modules/pembayaran/form.view.php */
$is_edit = !empty($edit_data);
$e       = $edit_data ?? [];
?>

<style>
.prog-row { border:1px solid #dee2e6; border-radius:8px; padding:.75rem 1rem; margin-bottom:.5rem; transition:.15s; }
.prog-row.selected { border-color:#0d6efd; background:#f0f5ff; }
.harga-input { max-width:170px; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><?= $is_edit ? 'Edit Pembayaran' : 'Catat Pembayaran SPP' ?></h4>
        <small class="text-muted"><?= $is_edit ? e($e['nama_lengkap']??'') : 'Pilih siswa, program, dan jumlah bulan.' ?></small>
    </div>
    <a href="index.php?page=pembayaran" class="btn btn-outline-secondary btn-sm">← Kembali</a>
</div>

<?php if ($is_edit): ?>
<form method="POST" action="index.php?page=pembayaran&action=update&id=<?= (int)$e['id'] ?>">
<?= csrf_field() ?>
<div class="row g-4"><div class="col-lg-7">
    <div class="pk-card p-4 mb-3">
        <h6 class="fw-bold mb-3 text-primary">Detail Pembayaran</h6>
        <div class="alert alert-light border mb-3">
            <div class="fw-bold"><?= e($e['nama_lengkap']??'') ?> — <?= e($e['keterangan_program']??'') ?></div>
            <div class="text-muted small mt-1">
                <?= (int)($e['jumlah_bulan']??1) ?> bulan · Tagihan: <strong><?= format_rupiah((float)($e['nominal_tagihan']??0)) ?></strong>
            </div>
        </div>
        <?php foreach ($edit_detail as $d): ?>
        <div class="d-flex justify-content-between py-2 border-bottom small">
            <span><?= e($d['nama_program']) ?></span>
            <span class="text-muted"><?= format_rupiah((float)$d['biaya_per_bulan']) ?>/bln × <?= (int)$d['jumlah_bulan'] ?> = <strong><?= format_rupiah((float)($d['biaya_per_bulan']*$d['jumlah_bulan'])) ?></strong></span>
        </div>
        <?php endforeach; ?>
        <div class="row g-3 mt-3">
            <div class="col-md-6">
                <label class="form-label fw-bold small">Jumlah Dibayar <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light">Rp</span>
                    <input type="number" name="nominal_bayar" class="form-control" min="0" step="50000" value="<?= (float)($e['nominal_bayar']??0) ?>">
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small">Tanggal Bayar</label>
                <input type="date" name="tanggal_bayar" class="form-control" value="<?= e(substr($e['tanggal_bayar']??'',0,10)) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small">Metode</label>
                <select name="metode_pembayaran" class="form-select">
                    <?php foreach (['Tunai','Transfer','QRIS','Lainnya'] as $m): ?>
                    <option value="<?= $m ?>" <?= ($e['metode_pembayaran']??'')===$m?'selected':'' ?>><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small">Keterangan</label>
                <input type="text" name="keterangan" class="form-control" value="<?= e($e['keterangan']??'') ?>">
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-2">
        <a href="index.php?page=pembayaran" class="btn btn-outline-secondary">Batal</a>
        <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
    </div>
</div></div>
</form>
<?php else: ?>
<form method="POST" action="index.php?page=pembayaran&action=store" id="payForm">
<?= csrf_field() ?>
<div class="row g-4">
  <div class="col-lg-7">

    <!-- 1. Pilih Siswa -->
    <div class="pk-card p-4 mb-3">
        <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-person-badge me-2"></i>1. Pilih Siswa</h6>
        <select name="siswa_id" id="selSiswa" class="form-select ts-search" required>
            <option value="">— Pilih siswa aktif —</option>
            <?php foreach ($daftar_siswa as $s): ?>
            <option value="<?= (int)$s['id'] ?>"><?= e($s['nomor_induk']??'') ?> — <?= e($s['nama_lengkap']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- 2. Pilih Program -->
    <div class="pk-card p-4 mb-3" id="cardProgram" style="display:none;">
        <h6 class="fw-bold mb-2 text-primary"><i class="bi bi-collection me-2"></i>2. Pilih Program</h6>
        <small class="text-muted d-block mb-3">Centang program yang akan dibayar. Harga dapat disesuaikan per siswa.</small>
        <div id="programList"></div>
        <div class="text-center text-muted py-3 small" id="noMembership" style="display:none;">
            <i class="bi bi-exclamation-circle me-1"></i>Siswa ini belum memiliki membership aktif.
            <a href="index.php?page=siswa" class="d-block mt-1 small">→ Tambah di Data Keanggotaan</a>
        </div>
        <div class="mt-2 d-flex gap-2">
            <button type="button" class="btn btn-xs btn-outline-secondary" onclick="selectAll(true)">Pilih Semua</button>
            <button type="button" class="btn btn-xs btn-outline-secondary" onclick="selectAll(false)">Hapus Pilihan</button>
        </div>
    </div>

    <!-- 3. Jumlah Bulan -->
    <div class="pk-card p-4 mb-3" id="cardBulan" style="display:none;">
        <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-calendar-range me-2"></i>3. Jumlah Bulan</h6>
        <div class="d-flex gap-2 flex-wrap align-items-center" id="bulanBtns">
            <?php foreach ([1,2,3,6,12] as $b): ?>
            <label class="btn btn-sm <?= $b===1 ? 'btn-primary' : 'btn-outline-secondary' ?>" style="min-width:62px;">
                <input type="radio" name="jumlah_bulan" value="<?= $b ?>" class="d-none" <?= $b===1?'checked':'' ?> onchange="updateTotal()">
                <?= $b ?> bln
            </label>
            <?php endforeach; ?>
            <label class="btn btn-sm btn-outline-secondary">
                <input type="radio" name="jumlah_bulan" value="custom" class="d-none" id="rbCustom" onchange="showCustom()">
                Lainnya
            </label>
            <input type="number" id="inpCustom" min="1" max="24" class="form-control form-control-sm"
                   style="width:75px;display:none;" placeholder="bln" oninput="syncCustom()">
        </div>
        <small class="text-muted d-block mt-2">Membership otomatis diperpanjang 31 hari × jumlah bulan.</small>
    </div>

  </div>
  <div class="col-lg-5">

    <!-- Ringkasan & bayar -->
    <div class="pk-card p-4 mb-3" id="cardTotal" style="display:none;">
        <h6 class="fw-bold mb-3"><i class="bi bi-receipt me-2 text-success"></i>Ringkasan & Pembayaran</h6>
        <div id="summaryItems" class="mb-3 small"></div>
        <div class="d-flex justify-content-between fw-bold border-top pt-2 mb-3">
            <span>Total Tagihan</span>
            <span class="text-success" id="lblTotal">Rp 0</span>
        </div>
        <div class="mb-2">
            <label class="form-label fw-bold small">Jumlah Dibayar <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text bg-light">Rp</span>
                <input type="number" name="nominal_bayar" id="inpBayar" class="form-control" min="0" step="50000" placeholder="0" oninput="updateSisa()" required>
            </div>
            <div id="infoSisa" class="mt-1 small"></div>
        </div>
        <div class="d-flex gap-2 mb-3">
            <button type="button" class="btn btn-xs btn-outline-success" onclick="bayarLunas()">Lunas</button>
            <button type="button" class="btn btn-xs btn-outline-secondary" onclick="bayarDP50()">DP 50%</button>
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold small">Tanggal Bayar</label>
            <input type="date" name="tanggal_bayar" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold small">Metode</label>
            <div class="d-flex gap-2 flex-wrap" id="metodeGrp">
                <?php foreach (['Tunai','Transfer','QRIS','Lainnya'] as $m): ?>
                <label class="btn btn-sm <?= $m==='Tunai' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    <input type="radio" name="metode_pembayaran" value="<?= $m ?>" class="d-none" <?= $m==='Tunai'?'checked':'' ?> onchange="styleMetode()">
                    <?= $m ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label fw-bold small">Keterangan (Opsional)</label>
            <input type="text" name="keterangan" class="form-control" placeholder="cth: cicilan bulan pertama">
        </div>
        <button type="submit" class="btn btn-success w-100 fw-bold" id="btnSubmit" disabled>
            <i class="bi bi-check-circle me-1"></i>Simpan Pembayaran
        </button>
    </div>

    <div id="cardTips" class="pk-card p-4 text-center text-muted">
        <i class="bi bi-arrow-left-circle d-block mb-2" style="font-size:2rem;opacity:.25;"></i>
        <small>Pilih siswa untuk memulai</small>
    </div>
  </div>
</div>
</form>

<script>
let memberships = [];
let totalTagihan = 0;

document.getElementById('selSiswa').addEventListener('change', function() {
    const sid = this.value;
    if (!sid) { resetUI(); return; }
    fetch(`index.php?page=pembayaran&action=get_memberships&siswa_id=${sid}`)
        .then(r=>r.json()).then(d => { memberships = d.memberships||[]; renderList(); });
});

function resetUI() {
    ['cardProgram','cardBulan','cardTotal'].forEach(id => document.getElementById(id).style.display='none');
    document.getElementById('cardTips').style.display='';
}

function renderList() {
    const list = document.getElementById('programList');
    const none = document.getElementById('noMembership');
    ['cardProgram','cardBulan'].forEach(id => document.getElementById(id).style.display='');
    document.getElementById('cardTips').style.display='none';

    if (!memberships.length) { list.innerHTML=''; none.style.display=''; return; }
    none.style.display='none';

    const order=['Program','Fasilitas','Paket'];
    const grp={};
    memberships.forEach(m=>{ (grp[m.tipe_program]=grp[m.tipe_program]||[]).push(m); });

    let html='';
    order.forEach(t=>{
        if (!grp[t]) return;
        html+=`<div class="text-uppercase text-muted fw-bold mb-1 mt-3" style="font-size:.65rem;letter-spacing:.08em;">${t}</div>`;
        grp[t].forEach(m=>{
            const d   = daysLeft(m.tanggal_selesai_aktif);
            const bdg = d!==null?(d<14?`<span class="badge bg-danger ms-1">${d}h lagi</span>`:`<span class="badge bg-success bg-opacity-10 text-success ms-1">${d}h lagi</span>`):'';
            const exp = m.tanggal_selesai_aktif ? `s/d ${m.tanggal_selesai_aktif}` : '';
            html+=`<div class="prog-row d-flex align-items-center gap-3" id="row_${m.id}">
                <input type="checkbox" name="membership_ids[]" value="${m.id}" id="chk_${m.id}" class="form-check-input flex-shrink-0" onchange="onCheck()">
                <label for="chk_${m.id}" class="flex-grow-1 mb-0" style="cursor:pointer;">
                    <div class="fw-bold small">${esc(m.nama_program)} ${bdg}</div>
                    <div class="text-muted" style="font-size:.7rem;">${exp}</div>
                </label>
                <div class="input-group input-group-sm harga-input">
                    <span class="input-group-text bg-light px-1 small">Rp</span>
                    <input type="number" name="biaya_per_bulan[${m.id}]" id="h_${m.id}"
                           class="form-control form-control-sm text-end" value="${parseInt(m.biaya_per_bulan)}"
                           min="0" step="50000"
                           title="Acuan: Rp ${parseInt(m.biaya_default).toLocaleString('id-ID')}/bln"
                           oninput="updateTotal()">
                    <span class="input-group-text bg-light px-1 small">/bln</span>
                </div>
            </div>`;
        });
    });
    list.innerHTML = html;
    updateTotal();
}

function daysLeft(s) {
    if (!s) return null;
    return Math.round((new Date(s)-new Date())/86400000);
}
function esc(s) { const t=document.createElement('span'); t.textContent=s; return t.innerHTML; }
function selectAll(v) { document.querySelectorAll('[name="membership_ids[]"]').forEach(c=>{c.checked=v; onCheck();}); }
function onCheck() {
    document.querySelectorAll('[name="membership_ids[]"]').forEach(c=>{
        document.getElementById('row_'+c.value)?.classList.toggle('selected',c.checked);
    });
    updateTotal();
}

function getBulan() {
    const rb=document.querySelector('[name="jumlah_bulan"]:checked');
    if (!rb) return 1;
    if (rb.value==='custom') return parseInt(document.getElementById('inpCustom').value)||1;
    return parseInt(rb.value)||1;
}

function updateTotal() {
    const b=getBulan(); let tot=0; let items='';
    document.querySelectorAll('[name="membership_ids[]"]:checked').forEach(cb=>{
        const mid=cb.value;
        const h=parseFloat(document.getElementById('h_'+mid)?.value)||0;
        const sub=h*b; tot+=sub;
        const nm=document.querySelector(`#row_${mid} label .fw-bold`)?.childNodes[0]?.textContent?.trim()||mid;
        items+=`<div class="d-flex justify-content-between py-1 border-bottom">
            <span>${esc(nm)}</span>
            <span class="text-muted">${fmt(h)}/bln×${b}=<strong>${fmt(sub)}</strong></span>
        </div>`;
    });
    totalTagihan=tot;
    document.getElementById('summaryItems').innerHTML=items||'<div class="text-center text-muted small py-2">Pilih program</div>';
    document.getElementById('lblTotal').textContent='Rp '+fmt(tot);
    const has=document.querySelectorAll('[name="membership_ids[]"]:checked').length>0;
    document.getElementById('cardTotal').style.display=has?'':'none';
    document.getElementById('btnSubmit').disabled=!has||tot<=0;
    styleBulan(); updateSisa();
}

function fmt(n){ return Math.round(n).toLocaleString('id-ID'); }
function updateSisa(){
    const bayar=parseFloat(document.getElementById('inpBayar')?.value)||0;
    const el=document.getElementById('infoSisa'); if(!el)return;
    const sisa=totalTagihan-bayar;
    if(bayar<=0) el.innerHTML='';
    else if(sisa<=0) el.innerHTML='<span class="text-success"><i class="bi bi-check-circle me-1"></i>Lunas</span>';
    else el.innerHTML=`<span class="text-warning">Cicilan — sisa Rp ${fmt(sisa)}</span>`;
}
function bayarLunas(){ const i=document.getElementById('inpBayar'); if(i){i.value=totalTagihan;updateSisa();} }
function bayarDP50(){ const i=document.getElementById('inpBayar'); if(i){i.value=Math.ceil(totalTagihan*.5);updateSisa();} }
function styleBulan(){
    document.querySelectorAll('#bulanBtns label').forEach(l=>{
        const r=l.querySelector('input[type=radio]');
        l.classList.toggle('btn-primary',!!r?.checked);
        l.classList.toggle('btn-outline-secondary',!r?.checked);
    });
}
function styleMetode(){
    document.querySelectorAll('#metodeGrp label').forEach(l=>{
        const r=l.querySelector('input[type=radio]');
        l.classList.toggle('btn-primary',!!r?.checked);
        l.classList.toggle('btn-outline-secondary',!r?.checked);
    });
}
function showCustom(){ document.getElementById('inpCustom').style.display=''; document.getElementById('inpCustom').focus(); updateTotal(); }
function syncCustom(){ document.getElementById('rbCustom').checked=true; updateTotal(); }
document.querySelectorAll('#bulanBtns input[type=radio]').forEach(r=>r.addEventListener('change',updateTotal));
</script>
<?php endif; ?>

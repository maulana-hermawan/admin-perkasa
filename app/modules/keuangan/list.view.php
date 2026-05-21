<?php
/** app/modules/keuangan/list.view.php */
$is_filtered = $f_arus || $f_kategori || $f_dari || $f_sampai;
?>
<style>
.kat-chip{display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .75rem;border-radius:20px;font-size:.78rem;font-weight:600;border:2px solid transparent;cursor:pointer;transition:all .15s;white-space:nowrap;}
.kat-chip.masuk{background:#d1e7dd;color:#0a5c36;border-color:#198754;}
.kat-chip.keluar{background:#ffe0e3;color:#7a1524;border-color:#dc3545;}
.kat-chip.semua{background:#e9ecef;color:#444;border-color:#6c757d;}
.kat-chip.active-masuk{background:#198754;color:#fff;}
.kat-chip.active-keluar{background:#dc3545;color:#fff;}
.kat-chip.active-semua{background:#343a40;color:#fff;border-color:#343a40;}
.kat-sub-chip{display:inline-flex;align-items:center;padding:.22rem .65rem;border-radius:12px;font-size:.75rem;font-weight:500;border:1.5px solid #dee2e6;cursor:pointer;transition:all .13s;background:#fff;color:#555;white-space:nowrap;}
.kat-sub-chip:hover{border-color:#aaa;background:#f8f9fa;}
.kat-sub-chip.active-masuk{background:#d1e7dd;color:#0a5c36;border-color:#198754;font-weight:700;}
.kat-sub-chip.active-keluar{background:#ffe0e3;color:#7a1524;border-color:#dc3545;font-weight:700;}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="fw-bold mb-0">Buku Kas & Arus</h4>
    <small class="text-muted">Catat dan audit semua transaksi keuangan.</small></div>
    <a href="index.php?page=keuangan&action=create" class="btn btn-success">
        <i class="bi bi-plus-circle me-1"></i> Catat Transaksi
    </a>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="pk-card pk-stat-card d-flex justify-content-between align-items-center">
        <div><div class="text-muted fw-bold" style="font-size:.68rem;">TOTAL PEMASUKAN</div><div class="fw-bold text-success" style="font-size:1.2rem;"><?= format_rupiah($tot_in) ?></div></div>
        <div class="pk-stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-graph-up-arrow"></i></div>
    </div></div>
    <div class="col-md-4"><div class="pk-card pk-stat-card d-flex justify-content-between align-items-center">
        <div><div class="text-muted fw-bold" style="font-size:.68rem;">TOTAL PENGELUARAN</div><div class="fw-bold text-danger" style="font-size:1.2rem;"><?= format_rupiah($tot_out) ?></div></div>
        <div class="pk-stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-graph-down-arrow"></i></div>
    </div></div>
    <div class="col-md-4"><div class="pk-card pk-stat-card d-flex justify-content-between align-items-center">
        <div><div class="text-muted fw-bold" style="font-size:.68rem;">SALDO KAS</div><div class="fw-bold <?= $saldo>=0?'text-primary':'text-danger' ?>" style="font-size:1.2rem;"><?= format_rupiah($saldo) ?></div></div>
        <div class="pk-stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-safe-fill"></i></div>
    </div></div>
</div>

<!-- Filter Panel -->
<div class="pk-card p-3 mb-3">

    <!-- Preset cepat -->
    <div class="d-flex flex-wrap gap-2 mb-3">
        <?php
        $today=$today??date('Y-m-d');
        $mon_start=date('Y-m-d',strtotime('monday this week'));
        $mon_end=date('Y-m-d',strtotime('sunday this week'));
        $bulan_1=date('Y-m-01');$bulan_end=date('Y-m-t');
        $hari30=date('Y-m-d',strtotime('-29 days'));
        $presets=['hari_ini'=>['label'=>'Hari Ini','dari'=>$today,'sampai'=>$today],
                  'minggu_ini'=>['label'=>'Minggu Ini','dari'=>$mon_start,'sampai'=>$mon_end],
                  'bulan_ini'=>['label'=>'Bulan Ini','dari'=>$bulan_1,'sampai'=>$bulan_end],
                  'hari30'=>['label'=>'30 Hari Terakhir','dari'=>$hari30,'sampai'=>$today]];
        $active_preset=$_GET['preset']??'';
        foreach($presets as $key=>$p):
        ?>
        <a href="?page=keuangan&preset=<?=$key?>&arus=<?=e($f_arus??'')?>&kategori=<?=e($f_kategori??'')?>"
           class="btn btn-sm <?=$active_preset===$key?'btn-primary':'btn-outline-secondary'?>">
            <?=$p['label']?>
        </a>
        <?php endforeach; ?>
        <?php if($is_filtered): ?>
        <a href="?page=keuangan" class="btn btn-sm btn-outline-danger ms-auto">
            <i class="bi bi-x-circle me-1"></i>Reset Semua
        </a>
        <?php endif; ?>
    </div>

    <!-- Form filter -->
    <form method="GET" id="filterForm">
        <input type="hidden" name="page" value="keuangan">
        <div class="row g-2 align-items-end mb-3">

            <!-- Jenis Arus -->
            <div class="col-sm-6 col-md-3">
                <label class="form-label small fw-bold mb-1">Jenis Arus</label>
                <select name="arus" class="form-select form-select-sm" id="selectArus"
                        onchange="onArusChange(this.value)">
                    <option value="" <?=!$f_arus?'selected':''?>>— Semua —</option>
                    <option value="Pemasukan"  <?=$f_arus==='Pemasukan' ?'selected':''?>>↑ Pemasukan</option>
                    <option value="Pengeluaran"<?=$f_arus==='Pengeluaran'?'selected':''?>>↓ Pengeluaran</option>
                </select>
            </div>

            <!-- Kategori dropdown -->
            <div class="col-sm-6 col-md-3">
                <label class="form-label small fw-bold mb-1">Kategori</label>
                <select name="kategori" class="form-select form-select-sm" id="selectKategori">
                    <option value="">— Semua Kategori —</option>
                    <optgroup label="↑ Pemasukan" id="grpMasuk" <?=$f_arus==='Pengeluaran'?'style="display:none"':''?>>
                        <?php foreach($kat_masuk_list as $kat): ?>
                        <option value="<?=e($kat)?>" data-jenis="Pemasukan" <?=$f_kategori===$kat?'selected':''?>><?=e($kat)?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="↓ Pengeluaran" id="grpKeluar" <?=$f_arus==='Pemasukan'?'style="display:none"':''?>>
                        <?php foreach($kat_keluar_list as $kat): ?>
                        <option value="<?=e($kat)?>" data-jenis="Pengeluaran" <?=$f_kategori===$kat?'selected':''?>><?=e($kat)?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>

            <!-- Tanggal -->
            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold mb-1">Dari</label>
                <input type="date" name="tgl_mulai" id="tgl_mulai" class="form-control form-control-sm" value="<?=e($f_dari??'')?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold mb-1">Sampai</label>
                <input type="date" name="tgl_selesai" id="tgl_selesai" class="form-control form-control-sm" value="<?=e($f_sampai??'')?>">
            </div>
            <div class="col-auto">
                <button class="btn btn-primary btn-sm px-3"><i class="bi bi-funnel-fill me-1"></i>Filter</button>
            </div>
        </div>

        <!-- Chip arus cepat -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
            <span class="text-muted fw-bold" style="font-size:.68rem;letter-spacing:.05em;">ARUS:</span>
            <span class="kat-chip semua <?=!$f_arus&&!$f_kategori?'active-semua':''?>" onclick="setFilter('','')">Semua</span>
            <span class="kat-chip masuk <?=$f_arus==='Pemasukan'&&!$f_kategori?'active-masuk':''?>" onclick="setFilter('Pemasukan','')">
                <i class="bi bi-arrow-up-circle-fill"></i> Pemasukan
            </span>
            <span class="kat-chip keluar <?=$f_arus==='Pengeluaran'&&!$f_kategori?'active-keluar':''?>" onclick="setFilter('Pengeluaran','')">
                <i class="bi bi-arrow-down-circle-fill"></i> Pengeluaran
            </span>
        </div>

        <!-- Chip sub-kategori pemasukan -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-1" id="masukChips"
             style="<?=$f_arus==='Pengeluaran'?'display:none':''?>">
            <span class="text-muted" style="font-size:.68rem;">↑ MASUK:</span>
            <?php foreach($kat_masuk_list as $kat): ?>
            <span class="kat-sub-chip <?=($f_arus==='Pemasukan'&&$f_kategori===$kat)?'active-masuk':''?>"
                  onclick="setFilter('Pemasukan','<?=e(addslashes($kat))?>')">
                <?=e($kat)?>
            </span>
            <?php endforeach; ?>
        </div>

        <!-- Chip sub-kategori pengeluaran -->
        <div class="d-flex flex-wrap gap-2 align-items-center" id="keluarChips"
             style="<?=$f_arus==='Pemasukan'?'display:none':''?>">
            <span class="text-muted" style="font-size:.68rem;">↓ KELUAR:</span>
            <?php foreach($kat_keluar_list as $kat): ?>
            <span class="kat-sub-chip <?=($f_arus==='Pengeluaran'&&$f_kategori===$kat)?'active-keluar':''?>"
                  onclick="setFilter('Pengeluaran','<?=e(addslashes($kat))?>')">
                <?=e($kat)?>
            </span>
            <?php endforeach; ?>
        </div>
    </form>

    <!-- Info filter aktif -->
    <?php if($is_filtered): ?>
    <div class="mt-2 pt-2 border-top">
        <small class="text-muted">
            <i class="bi bi-funnel-fill me-1 text-primary"></i>Filter aktif:
            <?php if($f_arus): ?><span class="badge <?=$f_arus==='Pemasukan'?'bg-success':'bg-danger'?> ms-1"><?=e($f_arus)?></span><?php endif; ?>
            <?php if($f_kategori): ?><span class="badge bg-secondary ms-1"><?=e($f_kategori)?></span><?php endif; ?>
            <?php if($f_dari&&$f_sampai): ?><span class="ms-1"><?=date('d M Y',strtotime($f_dari))?> s/d <?=date('d M Y',strtotime($f_sampai))?></span><?php endif; ?>
            &nbsp;·&nbsp; <strong><?=count($daftar_transaksi)?></strong> transaksi
        </small>
    </div>
    <?php endif; ?>
</div>

<!-- Tabel -->
<div class="pk-card overflow-hidden">
    <div class="table-responsive">
        <table class="table pk-table pk-table-mobile align-middle mb-0 small">
            <thead class="table-dark">
                <tr>
                    <th class="ps-3">Tanggal</th>
                    <th>Keterangan</th>
                    <th>Kategori</th>
                    <th>Jenis</th>
                    <th class="text-end">Nominal</th>
                    <th class="text-center pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($daftar_transaksi)): ?>
                <tr><td colspan="6">
                    <div class="pk-empty-state">
                        <i class="bi bi-receipt"></i>
                        <small>Tidak ada transaksi<?=$is_filtered?' dengan filter ini':''?></small>
                        <?php if($is_filtered): ?><a href="?page=keuangan" class="btn btn-sm btn-outline-secondary mt-2">Reset Filter</a><?php endif; ?>
                    </div>
                </td></tr>
                <?php else: foreach($daftar_transaksi as $k):
                    $kat_tampil = $k['kategori'] ?: ($k['kategori_pemasukan'] ?: ($k['kategori_pengeluaran'] ?: '—'));
                    $is_masuk   = $k['jenis_arus']==='Pemasukan';
                ?>
                <tr>
                    <td class="ps-3 text-muted" data-label="Tanggal">
                        <?=e(date('d M Y',strtotime($k['tanggal_transaksi'])))?>
                        <div style="font-size:.68rem;"><?=e(date('H:i',strtotime($k['tanggal_transaksi'])))?></div>
                    </td>
                    <td class="fw-bold" data-label="Keterangan" style="max-width:220px;">
                        <?=e(truncate($k['keterangan_transaksi']??'',55))?>
                    </td>
                    <td data-label="Kategori">
                        <a href="?page=keuangan&arus=<?=e($k['jenis_arus'])?>&kategori=<?=e($kat_tampil)?>"
                           class="badge text-decoration-none"
                           style="background:<?=$is_masuk?'#d1e7dd':'#ffe0e3'?>;color:<?=$is_masuk?'#0a5c36':'#7a1524'?>;border:1px solid <?=$is_masuk?'#198754':'#dc3545'?>;"
                           title="Klik untuk filter kategori ini">
                            <?=e($kat_tampil)?>
                        </a>
                    </td>
                    <td data-label="Jenis">
                        <span class="badge <?=$is_masuk?'bg-success':'bg-danger'?>">
                            <?=$is_masuk?'↑':'↓'?> <?=e($k['jenis_arus'])?>
                        </span>
                    </td>
                    <td class="fw-bold text-end <?=$is_masuk?'text-success':'text-danger'?>" data-label="Nominal">
                        <?=$is_masuk?'+':'−'?> <?=format_rupiah((float)$k['nominal'])?>
                    </td>
                    <td class="text-center pe-3" data-label="">
                        <a href="index.php?page=keuangan&action=edit&id=<?=(int)$k['id']?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                        <button class="btn btn-sm btn-outline-danger"
                                onclick="pkConfirm('Hapus transaksi ini?',()=>location.href='index.php?page=keuangan&action=delete&id=<?=(int)$k['id']?>&_csrf_token=<?=e(csrf_token())?>')">
                            <i class="bi bi-trash-fill"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <?php if(!empty($daftar_transaksi)&&$is_filtered): ?>
            <tfoot class="table-light">
                <tr>
                    <td colspan="4" class="fw-bold text-end small ps-3">
                        Saldo periode filter (<?=count($daftar_transaksi)?> transaksi):
                    </td>
                    <td class="fw-bold text-end <?=$filter_saldo>=0?'text-success':'text-danger'?>">
                        <?=($filter_saldo>=0?'+':'').format_rupiah($filter_saldo)?>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<script>
const presets=<?=json_encode($presets??[])?>;
(function(){
    const url=new URL(window.location.href),p=url.searchParams.get('preset');
    if(p&&presets[p]){
        const d=document.getElementById('tgl_mulai'),s=document.getElementById('tgl_selesai');
        if(d&&!d.value)d.value=presets[p].dari;
        if(s&&!s.value)s.value=presets[p].sampai;
    }
})();

function onArusChange(arus){
    const gM=document.getElementById('grpMasuk'),gK=document.getElementById('grpKeluar');
    const cM=document.getElementById('masukChips'),cK=document.getElementById('keluarChips');
    const sel=document.getElementById('selectKategori');
    sel.value='';
    if(arus==='Pemasukan'){gM.style.display='';gK.style.display='none';cM.style.display='';cK.style.display='none';}
    else if(arus==='Pengeluaran'){gM.style.display='none';gK.style.display='';cM.style.display='none';cK.style.display='';}
    else{gM.style.display='';gK.style.display='';cM.style.display='';cK.style.display='';}
}

function setFilter(arus,kategori){
    document.getElementById('selectArus').value=arus;
    document.getElementById('selectKategori').value=kategori;
    onArusChange(arus);
    document.getElementById('filterForm').submit();
}
</script>

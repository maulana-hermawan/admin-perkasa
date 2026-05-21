<?php
/** Form tambah/edit transaksi. $transaksi=null untuk baru. */
$is_edit = !empty($transaksi['id']);
$t_id    = $is_edit ? (int)$transaksi['id'] : 0;
$act     = $is_edit ? 'update' : 'store';
$jenis_v = $transaksi['jenis_arus'] ?? 'Pemasukan';
$kat_v   = $transaksi['kategori']   ?? '';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><?= $is_edit ? 'Edit Transaksi' : 'Catat Transaksi Baru' ?></h4>
    <a href="index.php?page=keuangan" class="btn btn-outline-secondary btn-sm">← Kembali</a>
</div>

<div class="row g-4">
    <div class="col-md-7">
        <div class="pk-card p-4">
            <form method="POST" action="index.php?page=keuangan&action=<?= $act ?><?= $is_edit?'&id='.$t_id:'' ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Jenis Arus <span class="text-danger">*</span></label>
                        <select name="jenis" class="form-select" id="selJenis" onchange="toggleKat()" required>
                            <option value="Pemasukan"  <?= $jenis_v==='Pemasukan' ?'selected':'' ?>>Pemasukan (+)</option>
                            <option value="Pengeluaran"<?= $jenis_v==='Pengeluaran'?'selected':'' ?>>Pengeluaran (−)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Nominal (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="nominal" class="form-control" required min="0" step="500"
                               value="<?= (float)($transaksi['nominal'] ?? 0) ?>">
                    </div>
                    <div id="divKatMasuk" class="col-md-6">
                        <label class="form-label fw-bold small">Kategori Pemasukan</label>
                        <select name="kat_masuk" class="form-select">
                            <?php foreach (['Bayar Program','Biaya Admin','Kelas Tambahan','Private','Lainnya'] as $k): ?>
                            <option value="<?= $k ?>" <?= $kat_v===$k?'selected':'' ?>><?= $k ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="divKatKeluar" class="col-md-6" style="display:none;">
                        <label class="form-label fw-bold small">Kategori Pengeluaran</label>
                        <select name="kat_keluar" class="form-select">
                            <?php foreach (['Belanja Operasional','Belanja Modal','Belanja Pegawai'] as $k): ?>
                            <option value="<?= $k ?>" <?= $kat_v===$k?'selected':'' ?>><?= $k ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small">Keterangan <span class="text-danger">*</span></label>
                        <input type="text" name="keterangan" class="form-control" required maxlength="500"
                               value="<?= e($transaksi['keterangan_transaksi'] ?? '') ?>"
                               placeholder="Deskripsi transaksi…">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Tanggal & Waktu</label>
                        <input type="datetime-local" name="tanggal_transaksi" class="form-control"
                               value="<?= $transaksi ? date('Y-m-d\TH:i', strtotime($transaksi['tanggal_transaksi'])) : date('Y-m-d\TH:i') ?>">
                    </div>
                    <div class="col-12 d-flex gap-2 pt-2">
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-save me-2"></i><?= $is_edit ? 'Simpan' : 'Catat' ?>
                        </button>
                        <a href="index.php?page=keuangan" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-5">
        <div class="pk-card p-4">
            <h6 class="fw-bold mb-3 text-muted">Ringkasan Kas</h6>
            <div class="mb-2 d-flex justify-content-between small"><span class="text-muted">Total Pemasukan</span><span class="text-success fw-bold"><?= format_rupiah($tot_in) ?></span></div>
            <div class="mb-2 d-flex justify-content-between small"><span class="text-muted">Total Pengeluaran</span><span class="text-danger fw-bold"><?= format_rupiah($tot_out) ?></span></div>
            <hr>
            <div class="d-flex justify-content-between"><span class="fw-bold">Saldo</span><span class="fw-bold <?= $saldo>=0?'text-primary':'text-danger' ?>"><?= format_rupiah($saldo) ?></span></div>
        </div>
    </div>
</div>

<script>
function toggleKat() {
    const j = document.getElementById('selJenis').value;
    document.getElementById('divKatMasuk').style.display  = j==='Pemasukan'   ? '' : 'none';
    document.getElementById('divKatKeluar').style.display = j==='Pengeluaran' ? '' : 'none';
}
toggleKat();
</script>

<?php
/** app/modules/keuangan/controller.php */
declare(strict_types=1);

$action = get('action', 'index');
$keu_id = get_int('id');

// HAPUS
if ($action === 'delete' && $keu_id) {
    csrf_check();
    $lama = db_fetch("SELECT * FROM transaksi_keuangan WHERE id=?","i",[$keu_id]);
    if ($lama) { db_query("DELETE FROM transaksi_keuangan WHERE id=?","i",[$keu_id]); log_action('DELETE_TRANSAKSI','transaksi_keuangan',$keu_id,$lama); flash('success','Transaksi dihapus.'); }
    redirect('index.php?page=keuangan');
}

// STORE
if ($action === 'store') {
    csrf_check();
    $v = validate($_POST);
    $v->required('jenis')->enum('jenis',['Pemasukan','Pengeluaran']);
    $v->required('nominal','Nominal')->numeric('nominal')->min_val('nominal',1);
    $v->required('keterangan','Keterangan');
    if ($v->fails()) { flash('danger',$v->first()); redirect('index.php?page=keuangan&action=create'); }

    $jenis    = post('jenis');
    $kategori = post($jenis==='Pemasukan'?'kat_masuk':'kat_keluar','Lainnya');
    $k_masuk  = $jenis==='Pemasukan'  ? $kategori : null;
    $k_keluar = $jenis==='Pengeluaran'? $kategori : null;
    $tgl      = post('tanggal_transaksi') ?: date('Y-m-d H:i:s');

    $new_id = db_insert(
        "INSERT INTO transaksi_keuangan (jenis_arus,kategori,kategori_pemasukan,kategori_pengeluaran,nominal,keterangan_transaksi,tanggal_transaksi,created_by) VALUES (?,?,?,?,?,?,?,?)",
        "ssssdssi", [$jenis,$kategori,$k_masuk,$k_keluar,(float)post('nominal'),post('keterangan'),$tgl,auth_id()]
    );
    log_action('CREATE_TRANSAKSI','transaksi_keuangan',$new_id);
    flash('success','Transaksi dicatat.');
    redirect('index.php?page=keuangan');
}

// UPDATE
if ($action === 'update' && $keu_id) {
    csrf_check();
    $jenis    = post('jenis');
    $kategori = post($jenis==='Pemasukan'?'kat_masuk':'kat_keluar','Lainnya');
    $k_masuk  = $jenis==='Pemasukan'  ? $kategori : null;
    $k_keluar = $jenis==='Pengeluaran'? $kategori : null;
    $lama     = db_fetch("SELECT * FROM transaksi_keuangan WHERE id=?","i",[$keu_id]);
    db_query("UPDATE transaksi_keuangan SET jenis_arus=?,kategori=?,kategori_pemasukan=?,kategori_pengeluaran=?,nominal=?,keterangan_transaksi=?,tanggal_transaksi=? WHERE id=?",
        "ssssdssi",[$jenis,$kategori,$k_masuk,$k_keluar,(float)post('nominal'),post('keterangan'),post('tanggal_transaksi') ?: date('Y-m-d H:i:s'),$keu_id]);
    log_action('UPDATE_TRANSAKSI','transaksi_keuangan',$keu_id,$lama);
    flash('success','Transaksi diperbarui.');
    redirect('index.php?page=keuangan');
}

// Shared: summary
$tot_in  = (float)(db_value("SELECT COALESCE(SUM(nominal),0) FROM transaksi_keuangan WHERE jenis_arus='Pemasukan'") ?? 0);
$tot_out = (float)(db_value("SELECT COALESCE(SUM(nominal),0) FROM transaksi_keuangan WHERE jenis_arus='Pengeluaran'") ?? 0);
$saldo   = $tot_in - $tot_out;

// CREATE
if ($action === 'create') {
    return ['title'=>'Catat Transaksi','view'=>__DIR__.'/form.view.php',
            'breadcrumbs'=>[['label'=>'Buku Kas','url'=>'index.php?page=keuangan'],['label'=>'Catat Baru']],
            'transaksi'=>null,'tot_in'=>$tot_in,'tot_out'=>$tot_out,'saldo'=>$saldo];
}

// EDIT
if ($action === 'edit' && $keu_id) {
    $transaksi = db_fetch("SELECT * FROM transaksi_keuangan WHERE id=?","i",[$keu_id]);
    if (!$transaksi) { flash('danger','Tidak ditemukan.'); redirect('index.php?page=keuangan'); }
    return ['title'=>'Edit Transaksi','view'=>__DIR__.'/form.view.php',
            'breadcrumbs'=>[['label'=>'Buku Kas','url'=>'index.php?page=keuangan'],['label'=>'Edit']],
            'transaksi'=>$transaksi,'tot_in'=>$tot_in,'tot_out'=>$tot_out,'saldo'=>$saldo];
}

// INDEX
$f_arus     = get('arus');
$f_kategori = get('kategori');
$f_dari     = get('tgl_mulai');
$f_sampai   = get('tgl_selesai');

// Quick filter preset
$preset = get('preset');
$presets_map = [
    'hari_ini'   => [date('Y-m-d'), date('Y-m-d')],
    'minggu_ini' => [date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week'))],
    'bulan_ini'  => [date('Y-m-01'), date('Y-m-t')],
    'hari30'     => [date('Y-m-d', strtotime('-29 days')), date('Y-m-d')],
];
if ($preset && isset($presets_map[$preset]) && !$f_dari) {
    [$f_dari, $f_sampai] = $presets_map[$preset];
}

$sql_w = "WHERE deleted_at IS NULL"; $types = ''; $params = [];
if ($f_arus && in_array($f_arus,['Pemasukan','Pengeluaran'],true)) { $sql_w.=" AND jenis_arus=?"; $types.='s'; $params[]=$f_arus; }
if ($f_kategori) { $sql_w.=" AND kategori=?"; $types.='s'; $params[]=$f_kategori; }
if ($f_dari && $f_sampai) { $sql_w.=" AND DATE(tanggal_transaksi) BETWEEN ? AND ?"; $types.='ss'; $params[]=$f_dari; $params[]=$f_sampai; }

// Daftar kategori unik dari DB (untuk dropdown filter)
$kat_pemasukan_db = db_fetch_all(
    "SELECT DISTINCT kategori FROM transaksi_keuangan WHERE jenis_arus='Pemasukan' AND deleted_at IS NULL AND kategori IS NOT NULL ORDER BY kategori"
);
$kat_pengeluaran_db = db_fetch_all(
    "SELECT DISTINCT kategori FROM transaksi_keuangan WHERE jenis_arus='Pengeluaran' AND deleted_at IS NULL AND kategori IS NOT NULL ORDER BY kategori"
);

// Gabung dengan kategori default (agar tampil meski belum ada transaksi)
$kat_default_masuk  = ['Bayar Program','Biaya Admin','Kelas Tambahan','Private','Lainnya'];
$kat_default_keluar = ['Belanja Operasional','Belanja Modal','Belanja Pegawai','Gaji Tutor','Lainnya'];
$kat_masuk_list  = array_unique(array_merge($kat_default_masuk,  array_column($kat_pemasukan_db,  'kategori')));
$kat_keluar_list = array_unique(array_merge($kat_default_keluar, array_column($kat_pengeluaran_db, 'kategori')));
sort($kat_masuk_list);
sort($kat_keluar_list);

$daftar_transaksi = db_fetch_all("SELECT * FROM transaksi_keuangan {$sql_w} ORDER BY tanggal_transaksi DESC LIMIT 200",$types,$params);
$filter_saldo = array_reduce($daftar_transaksi, fn($c,$k) => $c + ($k['jenis_arus']==='Pemasukan'?$k['nominal']:-$k['nominal']), 0.0);

return [
    'title'=>'Buku Kas & Arus','view'=>__DIR__.'/list.view.php',
    'breadcrumbs'=>[['label'=>'Buku Kas']],
    'daftar_transaksi'=>$daftar_transaksi,'tot_in'=>$tot_in,'tot_out'=>$tot_out,
    'saldo'=>$saldo,'filter_saldo'=>$filter_saldo,
    'f_arus'=>$f_arus,'f_dari'=>$f_dari,'f_sampai'=>$f_sampai,'f_kategori'=>$f_kategori,
    'kat_masuk_list'=>$kat_masuk_list,'kat_keluar_list'=>$kat_keluar_list,
];

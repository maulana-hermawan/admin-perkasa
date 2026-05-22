<?php
/**
 * app/modules/pendaftaran/controller.php
 * Antrian verifikasi calon siswa dari form publik
 */

// ── POST: update status ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (in_array($action, ['terima','tolak','proses'])) {
        $id      = get_int('id');
        $catatan = trim(post('catatan_admin',''));
        $status  = match($action) {
            'terima' => 'Diterima',
            'tolak'  => 'Ditolak',
            default  => 'Diproses',
        };

        $calon = db_fetch("SELECT * FROM calon_siswa WHERE id=?","i",[$id]);
        if (!$calon) { set_flash('danger','Data tidak ditemukan.'); redirect('index.php?page=pendaftaran'); }

        db_execute(
            "UPDATE calon_siswa SET status=?,catatan_admin=?,diproses_oleh=?,diproses_at=NOW() WHERE id=?",
            "ssii", [$status, $catatan ?: null, auth_id(), $id]
        );

        // Jika diterima: buat user + siswa record
        if ($status === 'Diterima') {
            try {
                $conn = db_conn();
                $conn->begin_transaction();

                // Generate password sementara
                $temp_pwd = substr(str_replace(['=','+','/'],'',base64_encode(random_bytes(12))),0,10);
                $hash     = password_hash($temp_pwd, PASSWORD_DEFAULT);
                $email    = $calon['email'] ?: strtolower(preg_replace('/\s+/','.',trim($calon['nama_lengkap']))) . '@siswa.perkasa.local';

                db_execute("INSERT INTO users (email,password,role,nama_display,is_active) VALUES (?,?,?,?,1)",
                    "ssss", [$email,$hash,'siswa',$calon['nama_lengkap']]);
                $uid = db_conn()->insert_id;

                $nomor_induk = _pendaftaran_generate_nomor_induk();
                db_execute(
                    "INSERT INTO siswa (user_id,nomor_induk,nama_lengkap,nomor_wa,nama_ortu,nomor_wa_ortu,tanggal_lahir,jenis_kelamin,asal_sekolah,target_seleksi,alamat,status_siswa)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,'Aktif')",
                    "isssssssss" . "s",
                    [$uid,$nomor_induk,$calon['nama_lengkap'],$calon['nomor_wa'],
                     $calon['nama_ortu'] ?? null, $calon['nomor_wa_ortu'] ?? null,
                     $calon['tanggal_lahir'],$calon['jenis_kelamin'],
                     $calon['asal_sekolah'],$calon['target_seleksi'],$calon['alamat']]
                );

                $conn->commit();
                log_action('TERIMA_PENDAFTAR','calon_siswa',$id,null,['nomor_induk'=>$nomor_induk]);

                // Kirim WA ke calon siswa
                if ($calon['nomor_wa']) {
                    send_wa($calon['nomor_wa'],
                        "Selamat *{$calon['nama_lengkap']}*! 🎉\n\n" .
                        "Pendaftaran Anda di *Perkasa Mulia Training Center* telah *DITERIMA*.\n\n" .
                        "📋 Nomor Induk: *$nomor_induk*\n" .
                        "📧 Email Login: *$email*\n" .
                        "🔑 Password: *$temp_pwd* (harap ganti setelah login pertama)\n\n" .
                        "Silakan datang ke kantor atau hubungi admin untuk info jadwal dan pembayaran.\n\n" .
                        "_Perkasa Mulia Training Center_"
                    );
                }

                set_flash('success', "Calon siswa <strong>{$calon['nama_lengkap']}</strong> diterima. Nomor induk: <strong>$nomor_induk</strong>. Password sementara: <code>$temp_pwd</code>");
            } catch (Throwable $ex) {
                db_conn()->rollback();
                set_flash('danger','Gagal buat akun siswa: '.e($ex->getMessage()));
            }
        } elseif ($status === 'Ditolak') {
            if ($calon['nomor_wa']) {
                $alasan = $catatan ? "\n\nAlasan: $catatan" : '';
                send_wa($calon['nomor_wa'],
                    "Halo *{$calon['nama_lengkap']}*,\n\nMohon maaf, pendaftaran Anda di Perkasa Mulia Training Center belum bisa kami proses saat ini.$alasan\n\nSilakan menghubungi kami untuk informasi lebih lanjut.\n\n_Perkasa Mulia Training Center_"
                );
            }
            set_flash('info', "Pendaftaran {$calon['nama_lengkap']} ditolak.");
        } else {
            set_flash('info', "Status diubah ke Diproses.");
        }

        redirect('index.php?page=pendaftaran');
    }
}

// ── GET: list ─────────────────────────────────────────────────
$f_status = get('status','Baru');
$f_q      = get('q','');

$sql = "SELECT * FROM calon_siswa WHERE 1=1";
$types=''; $params=[];

if ($f_status) { $sql.=" AND status=?"; $types.='s'; $params[]=$f_status; }
if ($f_q)      { $sql.=" AND (nama_lengkap LIKE ? OR nomor_wa LIKE ?)"; $types.='ss'; $params[]="%$f_q%"; $params[]="%$f_q%"; }
$sql .= " ORDER BY created_at DESC LIMIT 100";

$daftar = $types ? db_fetch_all($sql,$types,$params) : db_fetch_all($sql);

$stat_counts = db_fetch_all("SELECT status, COUNT(*) AS jumlah FROM calon_siswa GROUP BY status");
$counts = [];
foreach ($stat_counts as $s) $counts[$s['status']] = (int)$s['jumlah'];

return [
    'title'       => 'Antrian Pendaftaran',
    'view'        => __DIR__ . '/list.view.php',
    'breadcrumbs' => [['label'=>'Pendaftaran Online']],
    'daftar'      => $daftar,
    'f_status'    => $f_status,
    'f_q'         => $f_q,
    'counts'      => $counts,
];

function _pendaftaran_generate_nomor_induk(): string
{
    $yy   = date('y');    // 2 digit tahun: 26
    $last = db_value(
        "SELECT nomor_induk FROM siswa
         WHERE nomor_induk LIKE ?
         ORDER BY id DESC LIMIT 1",
        "s", ["PMTC-{$yy}%"]
    );
    // Format: PMTC-260001 → ambil 4 digit terakhir
    $seq = $last ? ((int)substr($last, -4)) + 1 : 1;
    return sprintf('PMTC-%s%04d', $yy, $seq);
}

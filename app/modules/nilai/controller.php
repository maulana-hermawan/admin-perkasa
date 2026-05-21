<?php
/**
 * app/modules/nilai/controller.php
 * Perkasa Mulia Training Center v2.0 — Rombakan Modul Nilai
 *
 * 4 Kelompok Nilai:
 *   1. Jasmani  — Binjas/Samapta (penilaian_binjas)
 *   2. Akademik — Mapel: B.Indonesia, B.Inggris, Matematika, PU, WK, Komputer (penilaian_mapel)
 *   3. SKD      — TWK, TIU, TKP (penilaian_akademik)
 *   4. Psikologi— Kecerdasan, Kecermatan, Kepribadian (penilaian_psikologi)
 *
 * Standar kelulusan (semua internal bimbel, min 61):
 *   - Mapel     : tiap mapel ≥ 61
 *   - SKD       : total ≥ 61 (CPNS: TWK≥65, TIU≥80, TKP≥166)
 *   - Psikologi : tiap sub-tes ≥ 61
 *   - Jasmani   : lari skor ≥ 41, samapta B tidak boleh nol
 */

declare(strict_types=1);

require_once __DIR__ . '/../../core/binjas_scoring.php';

// ── Kelompok nilai yang valid ─────────────────────────────────
// Pakai variabel biasa (bukan const) agar tidak konflik jika controller di-require ulang
$NILAI_KELOMPOK     = ['jasmani', 'mapel', 'skd', 'psikologi'];
$NILAI_MAPEL_FIELDS = ['bahasa_indonesia','bahasa_inggris','matematika','pengetahuan_umum','wawasan_kebangsaan'];
$NILAI_MAPEL_LABELS = [
    'bahasa_indonesia' => 'Bahasa Indonesia',
    'bahasa_inggris'   => 'Bahasa Inggris',
    'matematika'       => 'Matematika',
    'pengetahuan_umum' => 'Pengetahuan Umum (PU)',
    'wawasan_kebangsaan'=> 'Wawasan Kebangsaan (WK)',
];

$action   = get('action', 'index');
$kelompok = get('kelompok', 'jasmani'); // tab aktif
if (!in_array($kelompok, $NILAI_KELOMPOK, true)) $kelompok = 'jasmani';

// ══════════════════════════════════════════════════════════════
// REKAP NILAI — rangkuman lintas kelompok per siswa
// ══════════════════════════════════════════════════════════════
if ($action === 'rekap_nilai' || $action === 'export_rekap_nilai') {

    // ── Filter params ──────────────────────────────────────────
    $f_nama     = get('f_nama', '');
    $f_program  = get_int('f_program', 0);
    $f_dari     = get('f_dari',   date('Y-m-01'));
    $f_sampai   = get('f_sampai', date('Y-m-t'));
    $f_kelompok = (array)($_GET['f_kelompok'] ?? ['jasmani','mapel','skd','psikologi']);
    $f_kelompok = array_intersect($f_kelompok, ['jasmani','mapel','skd','psikologi']);
    if (empty($f_kelompok)) $f_kelompok = ['jasmani','mapel','skd','psikologi'];

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_dari))   $f_dari   = date('Y-m-01');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_sampai)) $f_sampai = date('Y-m-t');
    if ($f_dari > $f_sampai) [$f_dari, $f_sampai] = [$f_sampai, $f_dari];

    // ── Ambil daftar siswa sesuai filter ──────────────────────
    $siswa_where  = "WHERE s.deleted_at IS NULL";
    $siswa_types  = '';
    $siswa_params = [];

    if ($f_nama) {
        $siswa_where  .= " AND (s.nama_lengkap LIKE ? OR s.nomor_induk LIKE ?)";
        $siswa_types  .= 'ss';
        $siswa_params[] = "%{$f_nama}%";
        $siswa_params[] = "%{$f_nama}%";
    }
    if ($f_program > 0) {
        $siswa_where  .= " AND EXISTS (SELECT 1 FROM membership_siswa ms WHERE ms.siswa_id=s.id AND ms.program_id=? AND ms.status_membership='Berjalan')";
        $siswa_types  .= 'i';
        $siswa_params[] = $f_program;
    }

    $daftar_siswa_rekap = db_fetch_all(
        "SELECT s.id, s.nomor_induk, s.nama_lengkap, s.jenis_kelamin, s.status_siswa,
                COALESCE(p.nama_program,'—') AS nama_program
         FROM siswa s
         LEFT JOIN membership_siswa ms ON ms.siswa_id = s.id AND ms.status_membership = 'Berjalan'
         LEFT JOIN program p           ON ms.program_id = p.id
         $siswa_where
         GROUP BY s.id
         ORDER BY s.nama_lengkap ASC",
        $siswa_types, $siswa_params
    );

    if (empty($daftar_siswa_rekap)) {
        $rekap_data = [];
    } else {
        $siswa_ids = array_column($daftar_siswa_rekap, 'id');
        $ph        = implode(',', array_fill(0, count($siswa_ids), '?'));
        $ph_types  = str_repeat('i', count($siswa_ids));

        // ── Query per kelompok (dalam rentang tanggal) ────────
        $jasmani_map = $mapel_map = $skd_map = $psiko_map = [];

        if (in_array('jasmani', $f_kelompok)) {
            // Cek apakah kolom migration-011 sudah ada
            static $_has_skor_cols = null;
            if ($_has_skor_cols === null) {
                $_has_skor_cols = (bool)db_value(
                    "SELECT COUNT(*) FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='penilaian_binjas'
                     AND COLUMN_NAME='skor_lari'"
                );
            }

            $extra_cols = $_has_skor_cols
                ? ",MAX(skor_lari) AS best_skor_lari, MAX(skor_pullup) AS best_skor_pullup,
                   MAX(skor_situp) AS best_skor_situp, MAX(skor_pushup) AS best_skor_pushup,
                   MAX(skor_shuttle) AS best_skor_shuttle,
                   MAX(skor_lunges) AS best_skor_lunges, MAX(skor_renang) AS best_skor_renang,
                   MAX(nilai_samapta_a) AS best_samapta_a, MAX(nilai_samapta_b) AS best_samapta_b,
                   MAX(nilai_ab) AS best_nilai_ab, MAX(nilai_gabungan) AS best_nilai_gabungan"
                : '';

            $rows = db_fetch_all(
                "SELECT siswa_id,
                        MAX(skor_akhir)        AS skor_terbaik,
                        AVG(skor_akhir)        AS skor_rata,
                        COUNT(*)               AS jumlah_tes,
                        MAX(predikat)          AS predikat_terbaik,
                        MAX(lari_jarak_meter)  AS best_lari,
                        MAX(pullup_repetisi)   AS best_pullup,
                        MAX(situp_repetisi)    AS best_situp,
                        MAX(pushup_repetisi)   AS best_pushup,
                        MAX(shuttlerun_detik)  AS best_shuttle,
                        MAX(lunges_repetisi)   AS best_lunges,
                        MAX(renang_detik)      AS best_renang
                        $extra_cols
                 FROM penilaian_binjas
                 WHERE siswa_id IN ($ph) AND tanggal_tes BETWEEN ? AND ? AND deleted_at IS NULL
                 GROUP BY siswa_id",
                $ph_types . 'ss',
                array_merge($siswa_ids, [$f_dari, $f_sampai])
            );
            foreach ($rows as $r) $jasmani_map[$r['siswa_id']] = $r;
        }

        if (in_array('mapel', $f_kelompok)) {
            $rows = db_fetch_all(
                "SELECT siswa_id,
                        MAX(rata_mapel)  AS skor_terbaik,
                        AVG(rata_mapel)  AS skor_rata,
                        COUNT(*)         AS jumlah_tes,
                        MAX(bahasa_indonesia)  AS best_bi,
                        MAX(bahasa_inggris)    AS best_bing,
                        MAX(matematika)        AS best_mat,
                        MAX(pengetahuan_umum)  AS best_pu,
                        MAX(wawasan_kebangsaan)AS best_wk
                 FROM penilaian_mapel
                 WHERE siswa_id IN ($ph) AND tanggal_tes BETWEEN ? AND ? AND deleted_at IS NULL
                 GROUP BY siswa_id",
                $ph_types . 'ss',
                array_merge($siswa_ids, [$f_dari, $f_sampai])
            );
            foreach ($rows as $r) $mapel_map[$r['siswa_id']] = $r;
        }

        if (in_array('skd', $f_kelompok)) {
            $rows = db_fetch_all(
                "SELECT siswa_id,
                        MAX(total_skor)  AS skor_terbaik,
                        AVG(total_skor)  AS skor_rata,
                        COUNT(*)         AS jumlah_tes,
                        MAX(nilai_twk)   AS best_twk,
                        MAX(nilai_tiu)   AS best_tiu,
                        MAX(nilai_tkp)   AS best_tkp
                 FROM penilaian_akademik
                 WHERE siswa_id IN ($ph) AND tanggal_tes BETWEEN ? AND ? AND deleted_at IS NULL
                 GROUP BY siswa_id",
                $ph_types . 'ss',
                array_merge($siswa_ids, [$f_dari, $f_sampai])
            );
            foreach ($rows as $r) $skd_map[$r['siswa_id']] = $r;
        }

        if (in_array('psikologi', $f_kelompok)) {
            $rows = db_fetch_all(
                "SELECT siswa_id,
                        MAX(rata_psikologi)  AS skor_terbaik,
                        AVG(rata_psikologi)  AS skor_rata,
                        COUNT(*)             AS jumlah_tes,
                        MAX(kecerdasan)      AS best_kec,
                        MAX(kecermatan)      AS best_kcm,
                        MAX(kepribadian)     AS best_kpr,
                        MAX(status_psikologi='Lulus') AS ada_lulus
                 FROM penilaian_psikologi
                 WHERE siswa_id IN ($ph) AND tanggal_tes BETWEEN ? AND ? AND deleted_at IS NULL
                 GROUP BY siswa_id",
                $ph_types . 'ss',
                array_merge($siswa_ids, [$f_dari, $f_sampai])
            );
            foreach ($rows as $r) $psiko_map[$r['siswa_id']] = $r;
        }

        // Gabung ke dalam data siswa
        foreach ($daftar_siswa_rekap as &$siswa) {
            $sid = $siswa['id'];
            $siswa['jasmani']  = $jasmani_map[$sid] ?? null;
            $siswa['mapel']    = $mapel_map[$sid]   ?? null;
            $siswa['skd']      = $skd_map[$sid]     ?? null;
            $siswa['psikologi']= $psiko_map[$sid]   ?? null;
            $siswa['punya_data'] = ($siswa['jasmani'] || $siswa['mapel'] || $siswa['skd'] || $siswa['psikologi']);
        }
        unset($siswa);

        // Hanya tampilkan siswa yang punya data nilai (kecuali semua filter kelompok dipilih & ada filter nama)
        $rekap_data = array_filter($daftar_siswa_rekap, fn($s) => $s['punya_data']);
        $rekap_data = array_values($rekap_data);
    }

    // ── Stats ringkasan ────────────────────────────────────────
    $stat_siswa_ada_nilai = count($rekap_data);
    $stat_avg_jasmani  = $stat_avg_mapel = $stat_avg_skd = $stat_avg_psiko = null;
    if (!empty($rekap_data)) {
        $j_vals = array_filter(array_map(fn($s) => $s['jasmani']['skor_terbaik'] ?? null, $rekap_data), fn($v) => $v !== null);
        $m_vals = array_filter(array_map(fn($s) => $s['mapel']['skor_terbaik']   ?? null, $rekap_data), fn($v) => $v !== null);
        $k_vals = array_filter(array_map(fn($s) => $s['skd']['skor_terbaik']     ?? null, $rekap_data), fn($v) => $v !== null);
        $p_vals = array_filter(array_map(fn($s) => $s['psikologi']['skor_terbaik']?? null, $rekap_data), fn($v) => $v !== null);
        if ($j_vals) $stat_avg_jasmani = round(array_sum($j_vals)/count($j_vals), 1);
        if ($m_vals) $stat_avg_mapel   = round(array_sum($m_vals)/count($m_vals), 1);
        if ($k_vals) $stat_avg_skd     = round(array_sum($k_vals)/count($k_vals), 1);
        if ($p_vals) $stat_avg_psiko   = round(array_sum($p_vals)/count($p_vals), 1);
    }

    // ── Export CSV ─────────────────────────────────────────────
    if ($action === 'export_rekap_nilai') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="rekap_nilai_' . $f_dari . '_sd_' . $f_sampai . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

        $headers = ['NIS', 'Nama', 'JK', 'Status', 'Program'];
        if (in_array('jasmani',  $f_kelompok)) array_push($headers, 'Skor Jasmani', 'Predikat', 'Tes Jasmani');
        if (in_array('mapel',    $f_kelompok)) array_push($headers, 'Rata Mapel', 'B.Ind', 'B.Ing', 'Mat', 'PU', 'WK', 'Tes Mapel');
        if (in_array('skd',      $f_kelompok)) array_push($headers, 'Total SKD', 'TWK', 'TIU', 'TKP', 'Tes SKD');
        if (in_array('psikologi',$f_kelompok)) array_push($headers, 'Rata Psiko', 'Kecerdasan', 'Kecermatan', 'Kepribadian', 'Status Psiko', 'Tes Psiko');
        fputcsv($out, $headers);

        foreach ($rekap_data as $s) {
            $row = [
                $s['nomor_induk'], $s['nama_lengkap'],
                $s['jenis_kelamin'] ?? '—', $s['status_siswa'], $s['nama_program'],
            ];
            if (in_array('jasmani',  $f_kelompok)) {
                $j = $s['jasmani'];
                array_push($row, $j ? round($j['skor_terbaik'],1) : '—', $j['predikat_terbaik'] ?? '—', $j['jumlah_tes'] ?? 0);
            }
            if (in_array('mapel', $f_kelompok)) {
                $m = $s['mapel'];
                array_push($row, $m ? round($m['skor_terbaik'],1) : '—',
                    $m ? round($m['best_bi'],1)   : '—', $m ? round($m['best_bing'],1) : '—',
                    $m ? round($m['best_mat'],1)  : '—', $m ? round($m['best_pu'],1)   : '—',
                    $m ? round($m['best_wk'],1)   : '—', $m['jumlah_tes'] ?? 0);
            }
            if (in_array('skd', $f_kelompok)) {
                $k = $s['skd'];
                array_push($row, $k ? round($k['skor_terbaik'],1) : '—',
                    $k ? round($k['best_twk'],1) : '—', $k ? round($k['best_tiu'],1) : '—',
                    $k ? round($k['best_tkp'],1) : '—', $k['jumlah_tes'] ?? 0);
            }
            if (in_array('psikologi', $f_kelompok)) {
                $p = $s['psikologi'];
                array_push($row, $p ? round($p['skor_terbaik'],1) : '—',
                    $p ? round($p['best_kec'],1) : '—', $p ? round($p['best_kcm'],1) : '—',
                    $p ? round($p['best_kpr'],1) : '—',
                    $p ? ((int)$p['ada_lulus'] ? 'Lulus' : 'Tidak Lulus') : '—',
                    $p['jumlah_tes'] ?? 0);
            }
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    // ── Dropdown filter ────────────────────────────────────────
    $daftar_program_filter = db_fetch_all("SELECT id, nama_program FROM program WHERE is_active=1 ORDER BY nama_program");

    return [
        'title'                => 'Rekap Nilai Siswa',
        'view'                 => __DIR__ . '/rekap_nilai.view.php',
        'breadcrumbs'          => [['label'=>'Rekap Nilai','url'=>'index.php?page=nilai'],['label'=>'Rangkuman']],
        'rekap_data'           => $rekap_data,
        'f_nama'               => $f_nama,
        'f_program'            => $f_program,
        'f_dari'               => $f_dari,
        'f_sampai'             => $f_sampai,
        'f_kelompok'           => $f_kelompok,
        'daftar_program_filter'=> $daftar_program_filter,
        'stat_siswa'           => $stat_siswa_ada_nilai,
        'stat_avg_jasmani'     => $stat_avg_jasmani,
        'stat_avg_mapel'       => $stat_avg_mapel,
        'stat_avg_skd'         => $stat_avg_skd,
        'stat_avg_psiko'       => $stat_avg_psiko,
    ];
}

// ══════════════════════════════════════════════════════════════
// AJAX: Hitung skor binjas (live preview)
// ══════════════════════════════════════════════════════════════
if ($action === 'hitung_binjas') {
    check_auth();
    header('Content-Type: application/json; charset=utf-8');
    $hasil = binjas_hitung(
        get('institusi', 'polri'), get('gender', 'pria'),
        (float)get('lari', '0'), (float)get('pullup', '0'),
        (float)get('situp', '0'), (float)get('pushup', '0'),
        (float)get('shuttle', '0'),
        get('lunges','') !== '' ? (float)get('lunges') : null,
        get('renang','') !== '' ? (float)get('renang') : null,
        get('renang_gaya', 'dada')
    );
    echo json_encode(['ok' => true, 'hasil' => $hasil]);
    exit;
}

// ══════════════════════════════════════════════════════════════
// DELETE (semua kelompok)
// ══════════════════════════════════════════════════════════════
if ($action === 'delete') {
    csrf_check_get(); // delete nilai dipanggil via GET link dengan token di query string
    $id    = get_int('id');
    $tabel = match ($kelompok) {
        'jasmani'   => 'penilaian_binjas',
        'mapel'     => 'penilaian_mapel',
        'skd'       => 'penilaian_akademik',
        'psikologi' => 'penilaian_psikologi',
    };
    $lama = db_fetch("SELECT * FROM `{$tabel}` WHERE id=?", "i", [$id]);
    if ($lama) {
        db_query("DELETE FROM `{$tabel}` WHERE id=?", "i", [$id]);
        log_action('DELETE_NILAI_' . strtoupper($kelompok), $tabel, $id, $lama);
        flash('success', 'Data nilai berhasil dihapus.');
    }
    redirect("index.php?page=nilai&kelompok={$kelompok}");
}

// ══════════════════════════════════════════════════════════════
// STORE — JASMANI (Binjas/Samapta)
// ══════════════════════════════════════════════════════════════
if ($action === 'store_jasmani') {
    csrf_check();
    $v = validate($_POST);
    $v->required('siswa_id','Siswa');
    $v->required('tanggal_tes','Tanggal')->date('tanggal_tes');
    if ($v->fails()) { flash('danger',$v->first()); redirect("index.php?page=nilai&action=create&kelompok=jasmani"); }

    $s_id      = post_int('siswa_id');
    $tgl       = post('tanggal_tes');
    $institusi = post('institusi','polri');
    $gender    = post('gender','pria');
    $lari      = (float)post('lari','0');
    $pullup    = (float)post('pullup','0');
    $situp     = (float)post('situp','0');
    $pushup    = (float)post('pushup','0');
    $shuttle   = (float)post('shuttle','0');
    $lunges_v  = post('lunges','') !== '' ? (float)post('lunges') : null;
    $renang_v  = post('renang','') !== '' ? (float)post('renang') : null;
    $gaya      = post('renang_gaya','dada');

    $hasil = binjas_hitung($institusi, $gender, $lari, $pullup, $situp, $pushup, $shuttle, $lunges_v, $renang_v, $gaya);

    // Hitung nilai_samapta_b dari nilai_ab dan skor_lari
    // nilai_ab = (skor_lari + avg_b) / 2 → avg_b = nilai_ab * 2 - skor_lari
    $nilai_samapta_a = $hasil['skor_lari'];
    $nilai_samapta_b = round($hasil['nilai_ab'] * 2 - $hasil['skor_lari'], 2);
    $nilai_ab        = $hasil['nilai_ab'];
    $nilai_gabungan  = $hasil['nilai_gabungan']; // null jika tidak ada renang

    $target_map = ['polri'=>'Polri','tni'=>'TNI AD'];
    $target     = $target_map[$institusi] ?? 'Umum';

    $new_id = db_insert(
        "INSERT INTO penilaian_binjas
         (siswa_id, tanggal_tes, institusi, gender,
          lari_jarak_meter, pullup_repetisi, situp_repetisi,
          pushup_repetisi, shuttlerun_detik,
          lunges_repetisi, renang_detik,
          skor_lari, skor_pullup, skor_situp, skor_pushup, skor_shuttle, skor_lunges, skor_renang,
          nilai_samapta_a, nilai_samapta_b, nilai_ab, nilai_gabungan,
          skor_akhir, predikat, created_by)
         VALUES (?,?,?,?, ?,?,?, ?,?, ?,?, ?,?,?,?,?,?,?, ?,?,?,?, ?,?,?)",
        "isssddddddd" . "iiiiiii" . "dddd" . "dsi",
        [$s_id, $tgl, $institusi, $gender,
         $lari, $pullup, $situp,
         $pushup, $shuttle,
         $lunges_v, $renang_v,
         $hasil['skor_lari'], $hasil['skor_pullup'], $hasil['skor_situp'],
         $hasil['skor_pushup'], $hasil['skor_shuttle'],
         $hasil['skor_lunges'], $hasil['skor_renang'],
         $nilai_samapta_a, $nilai_samapta_b, $nilai_ab, $nilai_gabungan,
         $hasil['total_skor'], $hasil['predikat'], auth_id()]
    );
    log_action('CREATE_NILAI_JASMANI','penilaian_binjas',$new_id);
    flash('success', "Nilai jasmani dicatat. Skor: <strong>{$hasil['total_skor']}</strong> — <strong>{$hasil['predikat']}</strong> — " . ($hasil['lulus']?'✅ Lulus':'❌ TMS'));
    redirect('index.php?page=nilai&kelompok=jasmani');
}

// ══════════════════════════════════════════════════════════════
// STORE — MAPEL (Akademik Mata Pelajaran)
// ══════════════════════════════════════════════════════════════
if ($action === 'store_mapel') {
    csrf_check();
    $v = validate($_POST);
    $v->required('siswa_id','Siswa');
    $v->required('tanggal_tes','Tanggal')->date('tanggal_tes');
    if ($v->fails()) { flash('danger',$v->first()); redirect("index.php?page=nilai&action=create&kelompok=mapel"); }

    $s_id = post_int('siswa_id');
    $tgl  = post('tanggal_tes');
    $uid  = auth_id();

    $bi  = (float)post('bahasa_indonesia','0');
    $bing= (float)post('bahasa_inggris','0');
    $mat = (float)post('matematika','0');
    $pu  = (float)post('pengetahuan_umum','0');
    $wk  = (float)post('wawasan_kebangsaan','0');
    $cat = post('catatan',null);

    $new_id = db_insert(
        "INSERT INTO penilaian_mapel (siswa_id, tanggal_tes, bahasa_indonesia, bahasa_inggris, matematika, pengetahuan_umum, wawasan_kebangsaan, catatan, created_by)
         VALUES (?,?,?,?,?,?,?,?,?)",
        "isdddddsi",
        [$s_id,$tgl,$bi,$bing,$mat,$pu,$wk,$cat,$uid]
    );

    // Ambil rata-rata dari generated column
    $rec = db_fetch("SELECT rata_mapel FROM penilaian_mapel WHERE id=?","i",[$new_id]);
    log_action('CREATE_NILAI_MAPEL','penilaian_mapel',$new_id);
    flash('success', "Nilai mapel dicatat. Rata-rata: <strong>{$rec['rata_mapel']}</strong>");
    redirect('index.php?page=nilai&kelompok=mapel');
}

// ══════════════════════════════════════════════════════════════
// STORE — SKD
// ══════════════════════════════════════════════════════════════
if ($action === 'store_skd') {
    csrf_check();
    $v = validate($_POST);
    $v->required('siswa_id','Siswa');
    $v->required('tanggal_tes','Tanggal')->date('tanggal_tes');
    if ($v->fails()) { flash('danger',$v->first()); redirect("index.php?page=nilai&action=create&kelompok=skd"); }

    $s_id  = post_int('siswa_id');
    $tgl   = post('tanggal_tes');
    $twk   = (int)post('twk','0');
    $tiu   = (int)post('tiu','0');
    $tkp   = (int)post('tkp','0');
    $total = $twk + $tiu + $tkp;
    $uid   = auth_id();

    // Passing grade:
    // CPNS resmi: TWK≥65, TIU≥80, TKP≥166
    // Internal bimbel: total ≥ 61 (lebih lenient)
    $lulus_cpns    = ($twk >= 65 && $tiu >= 80 && $tkp >= 166);
    $lulus_internal= ($total >= 61);
    $pg_status     = $lulus_internal ? 'Lulus' : 'Tidak Lulus';

    $new_id = db_insert(
        "INSERT INTO penilaian_akademik (siswa_id,tanggal_tes,kategori_tes,nilai_twk,nilai_tiu,nilai_tkp,catatan,created_by)
         VALUES (?,?,'SKD',?,?,?,NULL,?)",
        "isiiii",[$s_id,$tgl,$twk,$tiu,$tkp,$uid]
    );
    log_action('CREATE_NILAI_SKD','penilaian_akademik',$new_id);
    $lulus_internal= ($twk + $tiu + $tkp >= 61);
    $pg_status     = $lulus_internal ? 'Lulus' : 'Tidak Lulus';
    $cpns_txt = $lulus_cpns ? '(memenuhi standar CPNS)' : '(belum memenuhi standar CPNS: TWK≥65/TIU≥80/TKP≥166)';
    flash('success', "SKD dicatat. Total: <strong>{$total}</strong> — <strong>{$pg_status}</strong> {$cpns_txt}");
    redirect('index.php?page=nilai&kelompok=skd');
}

// ══════════════════════════════════════════════════════════════
// STORE — PSIKOLOGI
// ══════════════════════════════════════════════════════════════
if ($action === 'store_psikologi') {
    csrf_check();
    $v = validate($_POST);
    $v->required('siswa_id','Siswa');
    $v->required('tanggal_tes','Tanggal')->date('tanggal_tes');
    if ($v->fails()) { flash('danger',$v->first()); redirect("index.php?page=nilai&action=create&kelompok=psikologi"); }

    $s_id   = post_int('siswa_id');
    $tgl    = post('tanggal_tes');
    $kec    = (float)post('kecerdasan','0');
    $kcm    = (float)post('kecermatan','0');
    $kpr    = (float)post('kepribadian','0');
    $cat    = post('catatan',null);
    $sumber = post('sumber','Manual');
    $sref   = post('sumber_referensi',null);
    $uid    = auth_id();

    $new_id = db_insert(
        "INSERT INTO penilaian_psikologi (siswa_id,tanggal_tes,kecerdasan,kecermatan,kepribadian,catatan,sumber,sumber_referensi,created_by)
         VALUES (?,?,?,?,?,?,?,?,?)",
        "isdddsssi",[$s_id,$tgl,$kec,$kcm,$kpr,$cat,$sumber,$sref,$uid]
    );
    $rec = db_fetch("SELECT rata_psikologi, status_psikologi FROM penilaian_psikologi WHERE id=?","i",[$new_id]);
    log_action('CREATE_NILAI_PSIKOLOGI','penilaian_psikologi',$new_id);
    flash('success', "Psikologi dicatat. Rata-rata: <strong>{$rec['rata_psikologi']}</strong> — <strong>{$rec['status_psikologi']}</strong>");
    redirect('index.php?page=nilai&kelompok=psikologi');
}

// ══════════════════════════════════════════════════════════════
// IMPORT & TEMPLATE
// ══════════════════════════════════════════════════════════════
if ($action === 'import') {
    return [
        'title'       => 'Import Nilai — ' . ucfirst($kelompok),
        'view'        => __DIR__ . '/import.view.php',
        'breadcrumbs' => [['label'=>'Rekap Nilai','url'=>'index.php?page=nilai'],['label'=>'Import']],
        'kelompok'    => $kelompok,
    ];
}

if ($action === 'template') {
    // Generate CSV template
    $headers = match($kelompok) {
        'jasmani' => ['nomor_induk_atau_nama','tanggal_tes','institusi','gender','lari','pullup','situp','pushup','shuttle'],
        'skd'     => ['nomor_induk_atau_nama','tanggal_tes','twk','tiu','tkp'],
        'mapel'   => ['nomor_induk_atau_nama','tanggal_tes','bahasa_indonesia','bahasa_inggris','matematika','pengetahuan_umum','wawasan_kebangsaan'],
        default   => ['nomor_induk_atau_nama','tanggal_tes'],
    };
    $example = match($kelompok) {
        'jasmani' => ['PMTC-26-0001', date('Y-m-d'), 'polri', 'pria', '2400', '15', '60', '50', '12.5'],
        'skd'     => ['PMTC-26-0001', date('Y-m-d'), '120', '85', '180'],
        'mapel'   => ['PMTC-26-0001', date('Y-m-d'), '80', '75', '70', '85', '90'],
        default   => ['PMTC-26-0001', date('Y-m-d')],
    };
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="template_nilai_' . $kelompok . '_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel
    fputcsv($out, $headers);
    fputcsv($out, $example);
    fclose($out);
    exit();
}

if ($action === 'process_import') {
    csrf_check();
    require_once __DIR__ . '/../../lib/SimpleXLSX.php';

    $file     = $_FILES['import_file'] ?? null;
    $skip_hdr = (bool)($_POST['skip_header'] ?? 1);

    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        flash('danger','Upload gagal. Periksa ukuran file.');
        redirect("index.php?page=nilai&action=import&kelompok=$kelompok");
    }

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $rows = [];

    if ($ext === 'csv') {
        $handle = fopen($file['tmp_name'], 'r');
        while (($row = fgetcsv($handle)) !== false) $rows[] = $row;
        fclose($handle);
    } elseif (in_array($ext, ['xlsx','xls'])) {
        if ($xlsx = SimpleXLSX::parse($file['tmp_name'])) {
            $rows = $xlsx->rows();
        } else {
            flash('danger', 'File Excel tidak bisa dibaca: ' . SimpleXLSX::parseError());
            redirect("index.php?page=nilai&action=import&kelompok=$kelompok");
        }
    } else {
        flash('danger','Format file tidak didukung. Gunakan .xlsx, .xls, atau .csv.');
        redirect("index.php?page=nilai&action=import&kelompok=$kelompok");
    }

    if ($skip_hdr && count($rows) > 0) array_shift($rows);

    $success = 0; $skipped = 0; $errors = [];

    foreach ($rows as $i => $row) {
        $row = array_map('trim', $row);
        if (empty(array_filter($row))) { $skipped++; continue; }

        $identifier = $row[0] ?? '';
        $tgl_tes    = $row[1] ?? date('Y-m-d');

        // Cari siswa by nomor_induk ATAU nama
        $siswa_row = db_fetch("SELECT id FROM siswa WHERE nomor_induk=? AND deleted_at IS NULL","s",[$identifier])
                  ?? db_fetch("SELECT id FROM siswa WHERE nama_lengkap=? AND deleted_at IS NULL","s",[$identifier]);

        if (!$siswa_row) {
            $errors[] = "Baris ".($i+2).": Siswa '$identifier' tidak ditemukan.";
            $skipped++; continue;
        }

        $sid = (int)$siswa_row['id'];

        try {
            if ($kelompok === 'jasmani') {
                $institusi = strtolower($row[2] ?? 'polri');
                $gender    = strtolower($row[3] ?? 'pria');
                $lari      = (float)($row[4] ?? 0);
                $pullup    = (float)($row[5] ?? 0);
                $situp     = (float)($row[6] ?? 0);
                $pushup    = (float)($row[7] ?? 0);
                $shuttle   = (float)($row[8] ?? 0);
                db_execute(
                    "INSERT INTO penilaian_binjas (siswa_id,tanggal_tes,institusi,gender,lari_jarak_meter,pullup_repetisi,situp_repetisi,pushup_repetisi,shuttlerun_detik,created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?)",
                    "isssdddddi",
                    [$sid,$tgl_tes,$institusi,$gender,$lari,$pullup,$situp,$pushup,$shuttle,auth_id()]
                );
            } elseif ($kelompok === 'skd') {
                $twk=(int)($row[2]??0);$tiu=(int)($row[3]??0);$tkp=(int)($row[4]??0);
                db_execute(
                    "INSERT INTO penilaian_akademik (siswa_id,tanggal_tes,nilai_twk,nilai_tiu,nilai_tkp,kategori_tes,created_by) VALUES (?,?,?,?,?,?,?)",
                    "isiiisi",
                    [$sid,$tgl_tes,$twk,$tiu,$tkp,'SKD',auth_id()]
                );
            } elseif ($kelompok === 'mapel') {
                $bi  =(float)($row[2]??0);$bing=(float)($row[3]??0);$mat=(float)($row[4]??0);
                $pu  =(float)($row[5]??0);$wk  =(float)($row[6]??0);
                db_execute(
                    "INSERT INTO penilaian_mapel (siswa_id,tanggal_tes,bahasa_indonesia,bahasa_inggris,matematika,pengetahuan_umum,wawasan_kebangsaan,created_by) VALUES (?,?,?,?,?,?,?,?)",
                    "isdddddi",
                    [$sid,$tgl_tes,$bi,$bing,$mat,$pu,$wk,auth_id()]
                );
            }
            $success++;
        } catch (Throwable $ex) {
            $errors[] = "Baris ".($i+2).": ".e($ex->getMessage());
        }
    }

    log_action('IMPORT_NILAI','penilaian_binjas',0,null,['kelompok'=>$kelompok,'success'=>$success,'error'=>count($errors)]);
    flash($success > 0 ? 'success' : 'warning', "$success data berhasil diimport, $skipped dilewati, ".count($errors)." error.");

    return [
        'title'         => 'Hasil Import Nilai',
        'view'          => __DIR__ . '/import.view.php',
        'breadcrumbs'   => [['label'=>'Rekap Nilai','url'=>'index.php?page=nilai'],['label'=>'Import']],
        'kelompok'      => $kelompok,
        'import_result' => ['success'=>$success,'skipped'=>$skipped,'error'=>count($errors),'errors'=>$errors],
    ];
}

// ══════════════════════════════════════════════════════════════
// CREATE FORM
// ══════════════════════════════════════════════════════════════
if ($action === 'create') {
    $daftar_siswa = db_fetch_all(
        "SELECT id, nama_lengkap, nomor_induk, target_seleksi, jenis_kelamin
         FROM siswa WHERE status_siswa='Aktif' AND deleted_at IS NULL ORDER BY nama_lengkap LIMIT 200"
    );
    return [
        'title'        => 'Input Nilai — ' . ucfirst($kelompok),
        'view'         => __DIR__ . '/form.view.php',
        'breadcrumbs'  => [['label'=>'Rekap Nilai','url'=>'index.php?page=nilai'],['label'=>'Input '.ucfirst($kelompok)]],
        'kelompok'     => $kelompok,
        'daftar_siswa' => $daftar_siswa,
        'tables_json'  => binjas_tables_json(),
        'mapel_labels' => $NILAI_MAPEL_LABELS,
    ];
}

// ══════════════════════════════════════════════════════════════
// REKAP PER SISWA (tab rekap dari detail siswa)
// ══════════════════════════════════════════════════════════════
if ($action === 'rekap_siswa') {
    check_auth();
    $siswa_id = get_int('siswa_id');
    header('Content-Type: application/json; charset=utf-8');

    $siswa = db_fetch("SELECT id, nama_lengkap, nomor_induk FROM siswa WHERE id=? AND deleted_at IS NULL","i",[$siswa_id]);
    if (!$siswa) { echo json_encode(['ok'=>false,'msg'=>'Siswa tidak ditemukan']); exit; }

    $jasmani   = db_fetch_all("SELECT tanggal_tes,skor_akhir AS total_skor,predikat,lari_jarak_meter,pullup_repetisi,situp_repetisi,pushup_repetisi,shuttlerun_detik FROM penilaian_binjas WHERE siswa_id=? ORDER BY tanggal_tes DESC LIMIT 6","i",[$siswa_id]);
    $mapel     = db_fetch_all("SELECT tanggal_tes,bahasa_indonesia AS b_indonesia,bahasa_inggris AS b_inggris,matematika,pengetahuan_umum AS pu,wawasan_kebangsaan AS wk,rata_mapel FROM penilaian_mapel WHERE siswa_id=? ORDER BY tanggal_tes DESC LIMIT 6","i",[$siswa_id]);
    $skd       = db_fetch_all("SELECT tanggal_tes,nilai_twk,nilai_tiu,nilai_tkp,total_skor FROM penilaian_akademik WHERE siswa_id=? ORDER BY tanggal_tes DESC LIMIT 6","i",[$siswa_id]);
    $psikologi = db_fetch_all("SELECT tanggal_tes,kecerdasan,kecermatan,kepribadian,rata_psikologi,status_psikologi FROM penilaian_psikologi WHERE siswa_id=? ORDER BY tanggal_tes DESC LIMIT 6","i",[$siswa_id]);

    // Skor terbaik per kelompok
    $best_jas  = !empty($jasmani)   ? max(array_column($jasmani,   'total_skor'))   : null;
    $best_map  = !empty($mapel)     ? max(array_column($mapel,     'rata_mapel'))   : null;
    $best_skd  = !empty($skd)       ? max(array_column($skd,       'total_skor'))   : null;
    $best_psi  = !empty($psikologi) ? max(array_column($psikologi, 'rata_psikologi')): null;

    echo json_encode([
        'ok'       => true,
        'siswa'    => $siswa,
        'jasmani'  => $jasmani,
        'mapel'    => $mapel,
        'skd'      => $skd,
        'psikologi'=> $psikologi,
        'best'     => ['jasmani'=>$best_jas,'mapel'=>$best_map,'skd'=>$best_skd,'psikologi'=>$best_psi],
    ]);
    exit;
}

// ══════════════════════════════════════════════════════════════
// INDEX — LIST PER KELOMPOK
// ══════════════════════════════════════════════════════════════
$f_tgl     = get('filter_tgl');
$f_nama    = get('filter_siswa');
$per_page  = in_array((int)get('per_page','25'),[10,25,50,100],true) ? (int)get('per_page','25') : 25;
$halaman   = max(1, get_int('halaman',1));
$offset    = ($halaman-1)*$per_page;

$sql_w  = "WHERE 1=1";
$types  = '';
$params = [];
if ($f_tgl)  { $sql_w.=" AND n.tanggal_tes=?";                                                    $types.='s';  $params[]=$f_tgl; }
if ($f_nama) { $sql_w.=" AND (s.nama_lengkap LIKE ? OR s.nomor_induk LIKE ?)";                    $types.='ss'; $params[]="%$f_nama%"; $params[]="%$f_nama%"; }

// Query sesuai kelompok
$_has_skor_cols_list = (bool)db_value(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='penilaian_binjas' AND COLUMN_NAME='skor_lari'"
);
$extra_skor_sel = $_has_skor_cols_list
    ? ",\n                     n.skor_lari, n.skor_pullup, n.skor_situp, n.skor_pushup, n.skor_shuttle,
                     n.skor_lunges, n.skor_renang,
                     n.nilai_samapta_a, n.nilai_samapta_b, n.nilai_ab, n.nilai_gabungan"
    : ",\n                     NULL AS skor_lari, NULL AS skor_pullup, NULL AS skor_situp, NULL AS skor_pushup, NULL AS skor_shuttle,
                     NULL AS skor_lunges, NULL AS skor_renang,
                     NULL AS nilai_samapta_a, NULL AS nilai_samapta_b, NULL AS nilai_ab, NULL AS nilai_gabungan";

$sql_map = [
    'jasmani'   => [
        'tabel'  => 'penilaian_binjas',
        'select' => "n.id, n.tanggal_tes, n.institusi, n.gender,
                     n.lari_jarak_meter,
                     n.pullup_repetisi,
                     n.situp_repetisi,
                     n.pushup_repetisi,
                     n.shuttlerun_detik,
                     n.lunges_repetisi,
                     n.renang_detik,
                     n.skor_akhir AS total_skor, n.predikat{$extra_skor_sel},
                     n.siswa_id, s.nama_lengkap, s.nomor_induk",
    ],
    'mapel'     => [
        'tabel'  => 'penilaian_mapel',
        'select' => "n.id, n.tanggal_tes,
                     n.bahasa_indonesia AS b_indonesia,
                     n.bahasa_inggris AS b_inggris,
                     n.matematika,
                     n.pengetahuan_umum AS pu,
                     n.wawasan_kebangsaan AS wk,
                     n.rata_mapel,
                     n.siswa_id, s.nama_lengkap, s.nomor_induk",
    ],
    'skd'       => [
        'tabel'  => 'penilaian_akademik',
        'select' => "n.id, n.tanggal_tes, n.nilai_twk, n.nilai_tiu, n.nilai_tkp,
                     n.total_skor,
                     n.siswa_id, s.nama_lengkap, s.nomor_induk",
    ],
    'psikologi' => [
        'tabel'  => 'penilaian_psikologi',
        'select' => "n.id, n.tanggal_tes, n.kecerdasan, n.kecermatan, n.kepribadian,
                     n.rata_psikologi, n.status_psikologi,
                     n.siswa_id, s.nama_lengkap, s.nomor_induk",
    ],
];

$m      = $sql_map[$kelompok];
$tabel  = $m['tabel'];
$select = $m['select'];

$total        = (int)(db_value("SELECT COUNT(*) FROM `{$tabel}` n JOIN siswa s ON n.siswa_id=s.id {$sql_w}", $types, $params) ?? 0);
$last_pg      = (int)ceil($total / max(1,$per_page));
$daftar_nilai = db_fetch_all("SELECT {$select} FROM `{$tabel}` n JOIN siswa s ON n.siswa_id=s.id {$sql_w} ORDER BY n.tanggal_tes DESC, n.id DESC LIMIT {$per_page} OFFSET {$offset}", $types, $params);

// Stat per kelompok
$stats = [
    'jasmani'   => (int)db_value("SELECT COUNT(*) FROM penilaian_binjas"),
    'mapel'     => (int)db_value("SELECT COUNT(*) FROM penilaian_mapel"),
    'skd'       => (int)db_value("SELECT COUNT(*) FROM penilaian_akademik WHERE kategori_tes='SKD'"),
    'psikologi' => (int)db_value("SELECT COUNT(*) FROM penilaian_psikologi"),
];
$avg = [
    'jasmani'   => round((float)(db_value("SELECT AVG(skor_akhir) FROM penilaian_binjas") ?? 0), 1),
    'mapel'     => round((float)(db_value("SELECT AVG(rata_mapel) FROM penilaian_mapel") ?? 0), 1),
    'skd'       => round((float)(db_value("SELECT AVG(total_skor) FROM penilaian_akademik WHERE kategori_tes='SKD'") ?? 0), 1),
    'psikologi' => round((float)(db_value("SELECT AVG(rata_psikologi) FROM penilaian_psikologi") ?? 0), 1),
];

return [
    'title'        => 'Rekap Nilai Tryout',
    'view'         => __DIR__ . '/list.view.php',
    'breadcrumbs'  => [['label'=>'Rekap Nilai']],
    'kelompok'     => $kelompok,
    'daftar_nilai' => $daftar_nilai,
    'total'        => $total,
    'per_page'     => $per_page,
    'halaman'      => $halaman,
    'last_pg'      => $last_pg,
    'offset'       => $offset,
    'f_tgl'        => $f_tgl,
    'f_nama'       => $f_nama,
    'stats'        => $stats,
    'avg'          => $avg,
    'mapel_labels' => $NILAI_MAPEL_LABELS,
];

<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['status'=>'error','message'=>'Method not allowed']); exit();
}

require_once '../koneksi.php';

$data = [];
$ct = isset($_SERVER['CONTENT_TYPE']) ? strtolower(trim($_SERVER['CONTENT_TYPE'])) : '';
if (strpos($ct,'application/json') !== false) {
    $dec = json_decode(file_get_contents('php://input'), true);
    if (is_array($dec)) $data = $dec;
} elseif (!empty($_POST)) { $data = $_POST; }
else { $dec = json_decode(file_get_contents('php://input'), true); if (is_array($dec)) $data = $dec; }

if (empty($data['nama']) || !isset($data['nilaiAkhir'])) {
    http_response_code(400); echo json_encode(['status'=>'error','message'=>'Data tidak lengkap.']); exit();
}

$nama            = trim(htmlspecialchars_decode($data['nama']));
$skorKecerdasan  = max(0,min(100,floatval($data['skorKecerdasan']  ?? 0)));
$skorKecermatan  = max(0,min(100,floatval($data['skorKecermatan']  ?? 0)));
$skorKepribadian = max(0,min(100,floatval($data['skorKepribadian'] ?? 0)));
$nilaiAkhir      = max(0,min(100,floatval($data['nilaiAkhir'])));
$status          = in_array($data['status']??'',['MEMENUHI SYARAT','TIDAK MEMENUHI SYARAT'])
                   ? $data['status'] : 'TIDAK MEMENUHI SYARAT';

try {
    $stmt = $pdo->prepare("INSERT INTO hasil_tryout (nama_peserta,skor_kecerdasan,skor_kecermatan,skor_kepribadian,nilai_akhir,status,waktu_ujian) VALUES (:nama,:cerdas,:cermat,:pribadi,:akhir,:status,NOW())");
    $stmt->execute([':nama'=>$nama,':cerdas'=>$skorKecerdasan,':cermat'=>$skorKecermatan,':pribadi'=>$skorKepribadian,':akhir'=>$nilaiAkhir,':status'=>$status]);
    $insertId = $pdo->lastInsertId();

    // Webhook ke admin panel (fire-and-forget)
    _cat_send_webhook([
        'secret'=>'perkasa-cat-secret-2026',
        'nama_peserta'=>$nama,'skor_kecerdasan'=>$skorKecerdasan,
        'skor_kecermatan'=>$skorKecermatan,'skor_kepribadian'=>$skorKepribadian,
        'nilai_akhir'=>$nilaiAkhir,'status'=>$status,
        'waktu_ujian'=>date('Y-m-d H:i:s'),'cat_id'=>$insertId,
    ]);

    echo json_encode(['status'=>'success','message'=>'Data berhasil disimpan.','id'=>$insertId]);
} catch (PDOException $e) {
    http_response_code(500); echo json_encode(['status'=>'error','message'=>'Gagal: '.$e->getMessage()]);
}

function _cat_send_webhook(array $payload): void {
    $scheme = (!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';
    $host   = $_SERVER['HTTP_HOST'] ?? '';
    if (!$host) return;
    // Sesuaikan path admin jika berbeda
    $url = "$scheme://$host/admin/app/api/webhook-cat.php";
    try {
        $ch = curl_init($url);
        curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode($payload)]);
        curl_exec($ch); curl_close($ch);
    } catch (Throwable) {}
}

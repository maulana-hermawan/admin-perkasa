# Konvensi Kode & Panduan Pengembangan

---

## Prinsip Utama

1. **Keamanan dulu** — setiap input user = tidak dipercaya. Selalu validasi, escape, dan prepared statement.
2. **Shared hosting compatible** — tidak ada Composer, Redis, APCu, atau exec(). Solusinya: file cache, SimpleXLSX, dll.
3. **PHP murni** — tidak ada framework. Pahami pola front controller manual di `index.php`.
4. **Bootstrap-first** — semua UI menggunakan Bootstrap 5.3 + kelas kustom `pk-*`.

---

## Konvensi Penamaan

### PHP
```php
// Variabel: snake_case
$siswa_aktif = 0;
$tgl_mulai   = '2026-01-01';

// Fungsi: snake_case dengan prefix konteks
function db_fetch() {}
function format_rupiah() {}
function auth_check() {}
function cache_remember() {}

// Konstanta: UPPER_SNAKE_CASE
define('APP_URL', '...');
define('DB_HOST', '...');

// Kelas: PascalCase
class Validator {}
class JWT {}

// Method: camelCase
$v->hasErrors();
$v->fails();
```

### Database
```sql
-- Tabel: snake_case, plural
CREATE TABLE membership_siswa ...
CREATE TABLE penilaian_binjas ...
CREATE TABLE transaksi_keuangan ...

-- Kolom: snake_case
nama_lengkap, tanggal_tes, skor_akhir, created_at

-- Primary key: selalu `id`
-- Foreign key: {tabel_referensi}_id (contoh: siswa_id, program_id)
-- Timestamp: created_at, updated_at, deleted_at (soft delete)
```

### File
```
app/modules/{modul}/controller.php   ← satu file controller per modul
app/modules/{modul}/list.view.php    ← tampilan daftar
app/modules/{modul}/form.view.php    ← form tambah/edit
app/modules/{modul}/detail.view.php  ← detail (jika ada)
app/modules/{modul}/{action}.view.php ← view khusus untuk action tertentu
```

### URL / Route
```
?page=siswa               → index
?page=siswa&action=create → create form
?page=siswa&action=store  → POST handler (store)
?page=siswa&action=edit&id=5   → edit form
?page=siswa&action=update&id=5 → POST handler (update)
?page=siswa&action=delete&id=5 → hapus (redirect setelah berhasil)
?page=siswa&action=detail&id=5 → halaman detail
```

---

## Pola Wajib — Security

### 1. Setiap POST WAJIB: csrf_check()
```php
if ($action === 'store' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();  // ← WAJIB ada di baris pertama setelah kondisi
    // ... proses POST
}
```

### 2. Setiap Output User Data WAJIB: e()
```php
// ✅ Benar
echo e($siswa['nama_lengkap']);
<td><?= e($r['keterangan']) ?></td>
value="<?= e($item['email']) ?>"

// ❌ Salah — XSS vulnerability
echo $siswa['nama_lengkap'];
<td><?= $r['keterangan'] ?></td>
```

### 3. Setiap Query WAJIB: Prepared Statement
```php
// ✅ Benar
$row = db_fetch("SELECT * FROM siswa WHERE id=?", "i", [$id]);
$rows = db_fetch_all("SELECT * FROM siswa WHERE status_siswa=?", "s", [$status]);

// ❌ Salah — SQL injection
$row = db_fetch("SELECT * FROM siswa WHERE id={$id}");
```

### 4. Integer ID dari URL
```php
// ✅ Benar
$siswa_id = get_int('id');  // otomatis integer, 0 jika tidak valid

// ❌ Salah
$siswa_id = $_GET['id'];    // bisa berisi string apapun
```

---

## Pola Controller Lengkap

```php
<?php
/**
 * app/modules/contoh/controller.php
 */
declare(strict_types=1);

$action    = get('action', 'index');
$record_id = get_int('id');

// ── DELETE ────────────────────────────────────────────────────
if ($action === 'delete' && $record_id) {
    csrf_check_get();  // delete via GET, butuh CSRF check juga
    $old = db_fetch("SELECT * FROM tabel WHERE id=?", "i", [$record_id]);
    if (!$old) { flash('warning', 'Data tidak ditemukan.'); redirect('index.php?page=contoh'); }
    
    db_query("UPDATE tabel SET deleted_at=NOW() WHERE id=?", "i", [$record_id]);
    log_action('DELETE_CONTOH', 'tabel', $record_id, $old, null);
    flash('success', 'Data berhasil dihapus.');
    redirect('index.php?page=contoh');
}

// ── UPDATE ────────────────────────────────────────────────────
if ($action === 'update' && $record_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    $v = validate($_POST)
        ->required('nama', 'Nama')
        ->max('nama', 100, 'Nama');
    
    if ($v->fails()) {
        flash('danger', $v->first());
        redirect("index.php?page=contoh&action=edit&id={$record_id}");
    }
    
    $old = db_fetch("SELECT * FROM tabel WHERE id=?", "i", [$record_id]);
    db_query("UPDATE tabel SET nama=?, updated_at=NOW() WHERE id=?", "si", [post('nama'), $record_id]);
    log_action('UPDATE_CONTOH', 'tabel', $record_id, $old, ['nama' => post('nama')]);
    flash('success', 'Data berhasil diperbarui.');
    redirect('index.php?page=contoh');
}

// ── STORE ─────────────────────────────────────────────────────
if ($action === 'store' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    
    $v = validate($_POST)
        ->required('nama', 'Nama')
        ->max('nama', 100, 'Nama');
    
    if ($v->fails()) {
        flash('danger', $v->first());
        redirect('index.php?page=contoh&action=create');
    }
    
    $new_id = db_insert("INSERT INTO tabel (nama, created_by) VALUES (?,?)", "si", [post('nama'), auth_id()]);
    log_action('CREATE_CONTOH', 'tabel', $new_id, null, ['nama' => post('nama')]);
    flash('success', 'Data berhasil disimpan.');
    redirect('index.php?page=contoh');
}

// ── EDIT ──────────────────────────────────────────────────────
if ($action === 'edit' && $record_id) {
    $item = db_fetch("SELECT * FROM tabel WHERE id=? AND deleted_at IS NULL", "i", [$record_id]);
    if (!$item) { flash('warning', 'Data tidak ditemukan.'); redirect('index.php?page=contoh'); }
    return [
        'title'       => 'Edit Data',
        'view'        => __DIR__ . '/form.view.php',
        'breadcrumbs' => [['label' => 'Contoh', 'url' => 'index.php?page=contoh'], ['label' => 'Edit']],
        'item'        => $item,
        'action_mode' => 'edit',
    ];
}

// ── CREATE ────────────────────────────────────────────────────
if ($action === 'create') {
    return [
        'title'       => 'Tambah Data',
        'view'        => __DIR__ . '/form.view.php',
        'breadcrumbs' => [['label' => 'Contoh', 'url' => 'index.php?page=contoh'], ['label' => 'Tambah']],
        'item'        => null,
        'action_mode' => 'create',
    ];
}

// ── INDEX (default) ───────────────────────────────────────────
$q        = get('q', '');
$page_num = max(1, get_int('page') ?: 1);
$per_page = in_array(get_int('per_page'), [10,25,50,100], true) ? get_int('per_page') : 25;

$where = "WHERE deleted_at IS NULL";
$params_types = "";
$params_vals  = [];

if ($q !== '') {
    $where .= " AND nama LIKE ?";
    $params_types .= "s";
    $params_vals[]  = "%{$q}%";
}

$result = db_paginate(
    "SELECT * FROM tabel {$where} ORDER BY created_at DESC",
    "SELECT COUNT(*) FROM tabel {$where}",
    $params_types, $params_vals, $page_num, $per_page
);

return [
    'title'       => 'Daftar Data',
    'view'        => __DIR__ . '/list.view.php',
    'breadcrumbs' => [],
    'rows'        => $result['rows'],
    'pag'         => $result,
    'q'           => $q,
    'per_page'    => $per_page,
];
```

---

## Pola View — Form (form.view.php)

```php
<?php
// $item = null (create) atau array (edit)
// $action_mode = 'create' | 'edit'
$is_edit = $action_mode === 'edit';
$form_action = $is_edit
    ? "index.php?page=contoh&action=update&id={$item['id']}"
    : "index.php?page=contoh&action=store";
?>

<div class="pk-card p-4" style="max-width:640px;">
    <h5 class="fw-bold mb-4"><?= $is_edit ? 'Edit' : 'Tambah' ?> Data</h5>

    <form method="POST" action="<?= $form_action ?>" class="pk-validate">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label fw-bold small">Nama <span class="text-danger">*</span></label>
            <input type="text" name="nama" class="form-control"
                   value="<?= e($item['nama'] ?? '') ?>" required>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i><?= $is_edit ? 'Perbarui' : 'Simpan' ?>
            </button>
            <a href="index.php?page=contoh" class="btn btn-light border">Batal</a>
        </div>
    </form>
</div>
```

---

## Pola View — List (list.view.php)

```php
<?php // $rows = array, $pag = pagination result, $q = search query ?>

<!-- Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <h4 class="fw-bold mb-0">Daftar Data</h4>
    <a href="index.php?page=contoh&action=create" id="btnNewEntry" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah
    </a>
</div>

<!-- Filter -->
<form method="GET" class="d-flex gap-2 mb-3 flex-wrap">
    <input type="hidden" name="page" value="contoh">
    <input type="text" name="q" class="form-control form-control-sm" style="max-width:280px;"
           placeholder="Cari..." value="<?= e($q) ?>">
    <button type="submit" class="btn btn-outline-primary btn-sm">Cari</button>
    <?php if ($q): ?><a href="index.php?page=contoh" class="btn btn-light btn-sm border">Reset</a><?php endif; ?>
</form>

<!-- Tabel -->
<div class="pk-card overflow-hidden">
    <table class="table pk-table small mb-0">
        <thead class="table-light">
            <tr>
                <th class="ps-3">Nama</th>
                <th>Tanggal</th>
                <th class="pe-3 text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="3" class="text-center text-muted py-4">Tidak ada data.</td></tr>
        <?php else: foreach ($rows as $r): ?>
            <tr>
                <td class="ps-3 fw-bold"><?= e($r['nama']) ?></td>
                <td class="text-muted"><?= format_tanggal($r['created_at']) ?></td>
                <td class="pe-3 text-end">
                    <a href="index.php?page=contoh&action=edit&id=<?= (int)$r['id'] ?>"
                       class="btn btn-xs btn-outline-primary">Edit</a>
                    <button class="btn btn-xs btn-outline-danger"
                            onclick="pkConfirm('Hapus data ini?', () => location.href='index.php?page=contoh&action=delete&id=<?= (int)$r['id'] ?>&_csrf_token=<?= e(csrf_token()) ?>')">
                        Hapus
                    </button>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?= pagination_links($pag, "&page=contoh&q=" . urlencode($q)) ?>
```

---

## Pola AJAX Endpoint

```php
// Di controller, untuk action yang mengembalikan JSON:
if ($action === 'get_json') {
    header('Content-Type: application/json; charset=utf-8');
    $data = db_fetch_all("SELECT id, nama FROM tabel WHERE deleted_at IS NULL");
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
}

// Di view, untuk memanggil endpoint tersebut:
// fetch('index.php?page=contoh&action=get_json')
//     .then(r => r.json())
//     .then(res => { if (res.ok) { /* gunakan res.data */ } });
```

---

## Pola Soft Delete

Semua tabel yang mendukung soft delete memiliki kolom `deleted_at DATETIME DEFAULT NULL`.

```php
// Delete (soft)
db_query("UPDATE tabel SET deleted_at=NOW() WHERE id=?", "i", [$id]);

// Restore
db_query("UPDATE tabel SET deleted_at=NULL WHERE id=?", "i", [$id]);

// Query SELALU sertakan filter ini
"WHERE deleted_at IS NULL"
"AND s.deleted_at IS NULL"
```

---

## Pola Pagination

```php
// Di controller
$result = db_paginate(
    "SELECT * FROM tabel WHERE deleted_at IS NULL ORDER BY created_at DESC",
    "SELECT COUNT(*) FROM tabel WHERE deleted_at IS NULL",
    "",   // bind types (kosong jika tidak ada parameter)
    [],   // bind values
    $page_num,
    $per_page
);
// $result berisi: rows, total, pages, page, per_page, offset

// Di view
<?= pagination_links($pag, "&page=modul&q=" . urlencode($q)) ?>
```

---

## Pola WhatsApp

```php
// Kirim WA setelah aksi penting
if ($siswa['nomor_wa']) {
    $pesan = wa_template('pembayaran_konfirmasi', [
        'nama'    => $siswa['nama_lengkap'],
        'nominal' => format_rupiah($nominal),
        'bulan'   => format_tanggal($periode, 'F Y'),
    ]);
    send_wa($siswa['nomor_wa'], $pesan);
}

// Template yang tersedia:
// 'membership_expired'     — info membership hampir habis
// 'pembayaran_konfirmasi'  — konfirmasi pembayaran diterima
// 'jadwal_reminder'        — reminder jadwal besok
// 'nilai_psikologi'        — hasil psikotes sudah keluar
```

---

## Pola Cache untuk Query Berat

```php
// Gunakan cache untuk query yang:
// 1. Jarang berubah (data historis, agregat bulanan)
// 2. Membutuhkan banyak JOIN atau agregat
// 3. Diakses di setiap page load

$data = cache_remember('unique_cache_key', function() {
    return db_fetch_all("SELECT ... JOIN ... GROUP BY ...");
}, 900); // 15 menit

// Invalidasi cache saat data berubah:
// Contoh: saat ada transaksi baru, invalidasi chart
db_insert("INSERT INTO transaksi_keuangan ...", ...);
cache_delete('dashboard_trend_chart');
```

---

## Hal yang Harus Dihindari (Anti-Pattern)

### ❌ Jangan gunakan nama kolom yang sudah dihapus/berganti:
```php
// ❌ Kolom-kolom ini TIDAK ADA di DB:
"SELECT b_indonesia, pu, wk, komputer FROM penilaian_mapel"
"SELECT total_skor FROM penilaian_binjas"
"SELECT passing_grade_status FROM penilaian_akademik"
"INSERT INTO jadwal (created_by) VALUES (?)"

// ✅ Nama yang benar:
"SELECT bahasa_indonesia, pengetahuan_umum, wawasan_kebangsaan FROM penilaian_mapel"
"SELECT skor_akhir FROM penilaian_binjas"
```

### ❌ Jangan INSERT ke kolom GENERATED:
```php
// ❌ Ini akan error — kolom GENERATED ALWAYS
"INSERT INTO penilaian_akademik (siswa_id, total_skor) VALUES (?,?)"
"INSERT INTO penilaian_psikologi (siswa_id, rata_psikologi, status_psikologi) VALUES (?,?,?)"

// ✅ Benar — biarkan DB menghitung sendiri
"INSERT INTO penilaian_akademik (siswa_id, nilai_twk, nilai_tiu, nilai_tkp) VALUES (?,?,?,?)"
```

### ❌ Jangan hardcode URL:
```php
// ❌
<img src="/admin-baru/assets/logo.svg">
header('Location: /admin-baru/index.php');

// ✅
<img src="<?= rtrim(APP_URL, '/') ?>/assets/logo.svg">
redirect('index.php?page=dashboard');
```

### ❌ Jangan inisialisasi Chart.js di luar DOMContentLoaded:
```javascript
// ❌ Chart.js dimuat defer — ini akan error
const chart = new Chart(ctx, {...});

// ✅ Bungkus dalam DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    const chart = new Chart(ctx, {...});
});
```

### ❌ Jangan lupa whitelist saat tambah action/page baru:
```php
// ❌ Tanpa ini, action baru tidak akan berjalan
// (index.php akan fallback ke 'index')

// ✅ Tambahkan ke ALLOWED_ACTIONS di index.php
$ALLOWED_ACTIONS = [..., 'action_baru'];
```

---

## Anti-Pattern Baru di v2.1

### ❌ Jangan deklarasi PHP function di dalam loop
```php
// ❌ Ini bug rekap nilai v2.0 — "Cannot redeclare function" mulai iterasi #2
foreach ($rekap_data as $s) {
    function jas_cell($raw, $skor) { /* ... */ }   // ← redeclare error!
    echo jas_cell($s['lari'], $s['skor_lari']);
}

// ✅ Pindah ke top file dengan guard
if (!function_exists('jas_cell')) {
    function jas_cell($raw, $skor) { /* ... */ }
}
foreach ($rekap_data as $s) {
    echo jas_cell($s['lari'], $s['skor_lari']);
}
```

### ❌ Jangan pakai escaped quotes `\'` di dalam `<?= ?>` echo
```php
// ❌ Sintaks heredoc — bukan PHP echo. Akan error parser.
<?= $b===1?'btn-primary':\'btn-outline-secondary\' ?>

// ✅ Pakai konkatenasi normal
<?= $b===1 ? 'btn-primary' : 'btn-outline-secondary' ?>
```

### ❌ Jangan type string dengan spasi di `bind_param`
```php
// ❌ "iisddssss i" — spasi merusak binding silent (gak error, gak insert)
db_insert("INSERT INTO ...", "iisddssss i", [...]);

// ✅ Tanpa spasi
db_insert("INSERT INTO ...", "iisddssssi", [...]);

// ✅ Atau pakai konkatenasi jika butuh visual separator
db_insert("INSERT INTO ...", "iisddssss" . "i", [...]);
```

### ❌ Jangan asumsi kolom hasil migration sudah ada
```php
// ❌ Akan error fatal jika migration belum dijalankan di server
$rows = db_fetch_all("SELECT p.tipe_program FROM program p WHERE ...");

// ✅ Schema-safe pattern
static $_has_tipe = null;
if ($_has_tipe === null) {
    $_has_tipe = (bool)db_value(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='program' AND COLUMN_NAME='tipe_program'"
    );
}
$tipe_sel = $_has_tipe ? "p.tipe_program" : "'Program' AS tipe_program";
$rows = db_fetch_all("SELECT {$tipe_sel} FROM program p WHERE ...");
```

### ❌ Jangan hide elemen Alpine pakai CSS class saja
```html
<!-- ❌ Sebelum Alpine init, elemen flash visible — bug double logo -->
<div class="pk-sidebar__brandtext">PERKASA MULIA</div>
<style>
    .pk-sidebar:not(.pk-sidebar--open) .pk-sidebar__brandtext { display: none; }
</style>

<!-- ✅ Pakai x-show + x-cloak untuk hidden by default sebelum Alpine init -->
<div x-show="sidebarOpen" x-cloak>PERKASA MULIA</div>
<style>
    [x-cloak] { display: none !important; }
</style>
```

### ❌ Jangan guard `!empty($item)` saja untuk edit mode
```php
// ❌ Jika $item = [] (array kosong dari controller create), !empty([]) = true
// dan akhirnya $item['id'] tidak ada → href "...&id=0"
$is_edit = !empty($item);

// ✅ Pakai triple guard
$is_edit = isset($item) && !empty($item) && !empty($item['id']);
```

### ❌ Jangan asumsi `session_bootstrap()` ada saat `auth.php` di-require
```php
// ❌ Order load bisa berbeda di server vs lokal
require_once 'app/core/auth.php';
session_bootstrap();   // ← fatal kalau function tidak ter-define

// ✅ Cek session_status() dulu, lalu function_exists()
if (session_status() === PHP_SESSION_NONE) {
    if (function_exists('session_bootstrap')) {
        session_bootstrap();
    } else {
        session_start();
    }
}
```

---

## Kelas CSS yang Tersedia

### Layout
```html
<div class="pk-card">           <!-- Card putih dengan shadow -->
<div class="pk-card p-4">       <!-- Card dengan padding -->
<div class="pk-stat-card">      <!-- Stat card (dashboard) -->
<div class="pk-stat-icon">      <!-- Ikon bulat stat card -->
```

### Tabel
```html
<table class="table pk-table">  <!-- Tabel dengan styling kustom -->
```

### Tombol
```html
<button class="btn btn-xs">     <!-- Tombol ekstra kecil -->
```

### Sidebar (jangan diubah tanpa alasan)
```css
.pk-sidebar, .pk-sidebar__brand, .pk-sidebar__nav
.pk-sidebar__link, .pk-sidebar__link--active
.pk-sidebar__icon, .pk-sidebar__label
.pk-sidebar__user, .pk-sidebar__avatar
```

### Alpine.js Utilities
```html
x-cloak         <!-- Sembunyikan sebelum Alpine init (butuh [x-cloak]{display:none} di CSS) -->
x-show="expr"   <!-- Show/hide berdasarkan kondisi -->
@click="expr"   <!-- Event handler klik -->
:class="expr"   <!-- Dynamic class binding -->
```

---

## Urutan Baca File untuk Memahami Codebase

1. **`CLAUDE.md`** — gambaran besar & konvensi utama
2. **`index.php`** — routing, whitelist, lifecycle
3. **`app/core/db.php`** — fungsi DB
4. **`app/core/helpers.php`** — fungsi umum
5. **`app/core/auth.php`** — autentikasi
6. **`app/layouts/admin.php`** — layout utama + Alpine.js global state
7. **`app/modules/siswa/controller.php`** — contoh controller lengkap
8. **`app/modules/dashboard/controller.php`** — contoh dengan cache + stats
9. **`docs/DATABASE.md`** — schema DB lengkap
10. **`docs/MODULES.md`** — semua action per modul

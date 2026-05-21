# Changelog — Perkasa Mulia Training Center

Format: berbasis [Keep a Changelog](https://keepachangelog.com/), versi mengikuti [SemVer](https://semver.org/).

---

## [2.1.0] — 2026-05-05

### ★ Added
- **Modul Manajemen Program** (`app/modules/program/`) — CRUD untuk Program reguler, Fasilitas (mess), dan Paket bundel
- Tabel **`paket_komponen`** untuk many-to-many paket ↔ program
- Tabel **`pembayaran_siswa_detail`** untuk line items per pembayaran
- Kolom **`program.tipe_program`** ENUM(`Program`, `Fasilitas`, `Paket`)
- Kolom **`membership_siswa.biaya_per_bulan`** — harga custom per siswa (NULL = pakai default)
- Kolom **`pembayaran_siswa.jumlah_bulan`** + **`keterangan_program`**
- AJAX endpoint `?page=pembayaran&action=get_memberships` untuk load membership siswa
- Form Pembayaran SPP redesign — pilih multi-program + jumlah bulan + harga editable per program
- Auto-extend `tanggal_selesai_aktif` membership = +31 hari × jumlah_bulan saat bayar
- Auto-insert ke `transaksi_keuangan` kategori `Bayar Program`
- Logo SVG di portal siswa, portal tutor, dan form daftar publik
- Schema-safe pattern di semua query bergantung kolom v2.1
- `docs/ROADMAP.md` — rencana pengembangan Phase 5-13

### Changed
- **Sidebar** — teks brand & user section kini pakai `x-show + x-cloak` (bukan CSS-based hide) untuk hilangkan flash double logo
- **Form Program** — guard `is_edit` lebih ketat: `isset($item) && !empty($item) && !empty($item['id'])`
- **Pembayaran controller** — pakai `db_begin()/db_commit()/db_rollback()` untuk multi-INSERT
- **Detail siswa** — tab Membership dikelompokkan per `tipe_program` dengan badge custom price
- **NIS format** — `PMTC-YYNNNN` (tanpa dash tengah)
- Sidebar menu order: Dashboard → Data Keanggotaan → **Manajemen Program** → Buku Kas → ...

### Fixed
- **Rekap Nilai jasmani hilang mulai siswa #2** — `function jas_cell()` dideklarasi di dalam `foreach` → "Cannot redeclare". Pindah ke top file dengan `if (!function_exists())` guard
- **Pembayaran error `bind_param`** — type string `"iisddssss i"` dengan spasi → bind silent fail. Diperbaiki ke `"iisddssssi"`
- **Tambah program error `update&id=0`** — `!empty([])` returns true. Guard ditambah `!empty($item['id'])`
- **`daftar.php` fatal error `session_bootstrap()` undefined** — order dependency di server. Cek `session_status()` dulu, baru `function_exists()`
- **Sidebar logo muncul 2×** — CSS-based hide brand text terlihat sebelum Alpine init. Pindah ke `x-show + x-cloak`
- **Escaped quotes `\'` di PHP echo** — sintaks heredoc dipakai di `<?= ?>` → parser error. Pakai konkatenasi normal
- Query `tipe_program` & `paket_komponen` error sebelum migration → semua di-guard schema-safe

### Migration
- `database/migrations/011_add_binjas_skor_columns.sql` — Skor per item binjas + Samapta A/B/AB
- `database/migrations/012_program_paket_spp_redesign.sql` — Schema v2.1

### Documentation
- Updated **CLAUDE.md** ke v2.1 blueprint
- Updated **docs/DATABASE.md** dengan tabel & kolom baru
- Updated **docs/MODULES.md** dengan modul `program/` + redesign `pembayaran/`
- Updated **docs/CONVENTIONS.md** dengan 7 anti-pattern baru
- New **docs/ROADMAP.md** dengan Phase 5-13 plan

---

## [2.0.0] — 2026-05-02

### Added
- 2FA TOTP di module settings
- Tabel `cabang` untuk multi-lokasi (belum dipakai aktif)
- Module `pendaftaran/` untuk antrian calon siswa dari `daftar.php`
- Module `laporan/` dengan Rapor PDF per siswa
- Webhook CAT psikotes (`app/api/webhook-cat.php`)
- Audit log table + `log_action()` di setiap CUD
- Soft delete (`deleted_at`) di siswa, tutor, program, dll
- File-based cache (`app/core/cache.php`)
- Notifikasi in-app (`notifikasi` table) + bell di topbar

### Changed
- Restructure folder: `app/modules/{name}/` (dari flat `pages/`)
- Routing via front controller `index.php` dengan whitelist
- Layout split jadi `admin.php`, `siswa.php`, `tutor.php`
- Portal siswa & tutor dipisah, mobile-first

### Fixed
- Berbagai bug nama kolom (lihat Anti-Pattern di CONVENTIONS.md)
- Race condition pada `session_start()` di multi-handler

---

## [1.x] — Pre-Mei 2026

Versi awal — flat structure, tanpa audit log, tanpa role 'owner'. Tidak didokumentasikan secara formal.

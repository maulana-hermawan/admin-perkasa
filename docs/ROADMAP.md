# Roadmap Pengembangan — Perkasa Mulia Training Center

> Rencana pengembangan setelah v2.1.
> Disusun berdasarkan kondisi sistem saat ini, kebutuhan operasional bimbel, dan realitas hosting (PHP shared, tanpa Composer).

---

## Status Saat Ini — v2.1 (Mei 2026)

✅ **Fitur core lengkap:**
- 12 modul admin aktif (dashboard, siswa, program, keuangan, jadwal, nilai, tutor, pembayaran, attendance, laporan, pendaftaran, settings)
- Portal siswa & tutor mobile-first
- 2FA TOTP (settings)
- Webhook CAT psikotes
- WhatsApp Fonnte
- Multi-program per siswa + paket bundel + harga custom per siswa
- SPP redesign: pilih banyak program, banyak bulan, auto-extend & auto-kas
- Audit log + soft delete + cache file-based

⚠️ **Yang masih jadi PR:**
- Belum ada fitur push notif WA otomatis (cron)
- Laporan keuangan masih sederhana (belum ada P&L atau cashflow statement)
- Belum ada module barang/inventory
- Belum ada portal orang tua

---

## Skala Prioritas

| Prioritas | Indikator |
|---|---|
| **P0** | Bug fix produksi, security patch — kerjakan **segera** |
| **P1** | Fitur dengan ROI tinggi & scope kecil — 1–2 minggu |
| **P2** | Fitur penting tapi scope sedang — 2–4 minggu |
| **P3** | Nice-to-have / future — backlog |

---

## Phase 5 — Stabilisasi Pasca v2.1 (P0–P1, **2 minggu**)

Fokus: pastikan v2.1 stabil di produksi sebelum pindah ke fitur baru.

### 5.1 Migration & Data Integrity
- [ ] **Run migration 011 + 012 di produksi** — wajib sebelum upload code v2.1
- [ ] **Backfill harga membership** — jalankan UPDATE batch agar `biaya_per_bulan` terisi untuk membership lama yang masih NULL
- [ ] **Set `tipe_program`** untuk program existing (Akademik/Jasmani Intensif/Reguler) lewat SQL one-shot
- [ ] **Verifikasi `paket_komponen`** terisi untuk Paket Intensif & Paket Reguler

### 5.2 Smoke Test Real-World
Skenario yang harus diuji manual setelah deploy:
- [ ] Tambah siswa baru → daftar 2 program (1 Program + 1 Fasilitas) → bayar 3 bulan
- [ ] Verifikasi `tanggal_selesai_aktif` di membership terupdate +93 hari
- [ ] Verifikasi entry baru di `transaksi_keuangan` kategori `Bayar Program`
- [ ] Edit program: ubah tipe dari `Program` ke `Paket` → centang komponen
- [ ] Buka rekap nilai → confirm Shuttle/Lunges/Renang muncul untuk semua siswa

### 5.3 Logging & Error Handling
- [ ] **Centralized error log** di `storage/logs/error.log` (rotate harian)
- [ ] **Try/catch lebih agresif** di controller pembayaran, program, nilai
- [ ] **Health check endpoint** `?page=settings&action=health` (cek DB, cache, disk space)

---

## Phase 6 — Notifikasi Otomatis (P1, **2 minggu**)

Membership otomatis hilang dari kontrol kalau tidak ada reminder. Saat ini WA dikirim manual.

### 6.1 Cron Reminder Job
- [ ] Buat `cron/check_membership.php` — dijalankan harian via cron Hostinger
- [ ] Logic:
  - Membership berakhir dalam 7 hari → kirim WA reminder ke siswa & ortu
  - Membership berakhir dalam 1 hari → reminder kedua
  - Sudah expired → notif ke admin
- [ ] Template WA baru:
  - `wa_template('reminder_h7')` — "Membership Anda akan berakhir 7 hari lagi"
  - `wa_template('reminder_h1')` — "Bayar hari ini untuk lanjut latihan besok"
  - `wa_template('expired_admin')` — internal alert ke admin

### 6.2 Notif In-App Lengkap
- [ ] Bell di topbar sudah ada — tambah preferensi mute per kategori
- [ ] Persisted di `notifikasi.is_read`, mark-as-read otomatis saat dibuka
- [ ] Notif kategori: pembayaran, jadwal, nilai, sistem

### 6.3 Konfirmasi WA Otomatis
- [ ] Setiap pembayaran sukses → kirim resi WA otomatis
- [ ] Setiap nilai baru di-input → kirim recap mingguan ke siswa Sabtu malam

---

## Phase 7 — Laporan Keuangan Lebih Dalam (P1, **3 minggu**)

Owner butuh visibilitas lebih untuk decision making.

### 7.1 Profit & Loss
- [ ] Modul baru `app/modules/laporan/pnl.view.php`
- [ ] Periode: bulan / triwulan / tahun
- [ ] Pemasukan dipecah per kategori (SPP, daftar ulang, lain-lain)
- [ ] Pengeluaran dipecah per kategori (gaji, sewa, ATK, marketing, dll)
- [ ] Net income per periode + trend chart 12 bulan

### 7.2 Cashflow Statement
- [ ] Operasional vs investasi vs pendanaan
- [ ] Saldo awal → saldo akhir per bulan
- [ ] Eksport PDF + Excel

### 7.3 Per-Program Profitability
- [ ] Pendapatan per program (dari `pembayaran_siswa_detail`)
- [ ] Cost allocation per program (gaji tutor pro-rata sesi mengajar)
- [ ] Margin per program → bantu putuskan program mana yang dipertahankan

### 7.4 Aging Receivables
- [ ] Tagihan tertunggak lebih dari 7/14/30 hari
- [ ] Auto-followup WA tiap level

---

## Phase 8 — Inventory & Asset (P2, **3 minggu**)

Bimbel punya asset (matras, dumbbell, alat tes) dan consumable (modul cetak).

### 8.1 Tabel Baru
```sql
CREATE TABLE inventory (
    id, nama, kategori ENUM('asset','consumable','seragam'),
    stok_awal, stok_kini, harga_beli, lokasi, kondisi, dibeli_pada
);
CREATE TABLE inventory_movement (
    id, inventory_id, jenis ENUM('masuk','keluar','rusak','transfer'),
    qty, ket, oleh INT, tgl
);
```

### 8.2 Modul `inventory/`
- [ ] List dengan filter kategori/lokasi
- [ ] Mutasi (in/out)
- [ ] Stock opname per kuartal
- [ ] Alert stok minimum

---

## Phase 9 — Portal Orang Tua (P2, **3 minggu**)

Banyak ortu siswa SMA ingin pantau perkembangan anak.

### 9.1 Login Ortu
- [ ] Tabel baru `orangtua_siswa` (parent_id, siswa_id, hubungan)
- [ ] Akun pakai nomor WA (login OTP via Fonnte)
- [ ] Role baru di `users.role` ENUM: tambah `'ortu'`

### 9.2 Halaman Portal Ortu
- [ ] Dashboard anak (rangkuman nilai, kehadiran, tagihan)
- [ ] Notif WA: nilai baru, tagihan, reminder
- [ ] Bayar online (link ke Tripay/Midtrans — Phase 10)

---

## Phase 10 — Pembayaran Online (P2, **4 minggu**)

Ortu mau bayar via QRIS / VA tanpa harus transfer manual.

### 10.1 Integrasi Payment Gateway
- [ ] Pilih: Tripay (lokal, simpel) atau Midtrans
- [ ] Endpoint baru `app/api/v1/payment-callback.php` — webhook dari gateway
- [ ] Status pembayaran auto-sync ke `pembayaran_siswa.status_bayar`

### 10.2 Tripay Flow
- [ ] Form bayar → generate `merchant_ref` → redirect ke Tripay → webhook ke server
- [ ] Validasi signature webhook (HMAC SHA256)
- [ ] Tambah kolom `payment_gateway`, `gateway_ref` di `pembayaran_siswa`

---

## Phase 11 — Mobile App / PWA (P3, **6 minggu**)

Portal siswa/tutor sudah mobile-first, tapi belum installable.

### 11.1 PWA Setup
- [ ] `manifest.json` — name, icons, start_url, display
- [ ] `service-worker.js` — cache static, offline fallback
- [ ] Push notification (Firebase Cloud Messaging) — alternatif WA gratis

### 11.2 Native-like UX
- [ ] Bottom-sheet modal (sudah Alpine, tinggal styling)
- [ ] Pull-to-refresh
- [ ] Skeleton loader untuk tab data

---

## Phase 12 — Analytics & Insights (P3, **4 minggu**)

Owner ingin lihat tren panjang & forecasting.

### 12.1 Cohort Analysis
- [ ] Cohort retention: % siswa angkatan X yang masih aktif setelah N bulan
- [ ] Conversion calon siswa → siswa aktif → lulus tes seleksi

### 12.2 Tutor Performance
- [ ] Rata-rata nilai siswa per tutor
- [ ] Attendance rate tutor
- [ ] Score improvement (delta nilai sebelum/sesudah ajar)

### 12.3 Forecasting Sederhana
- [ ] Linear regression revenue 12 bulan
- [ ] Prediksi cashflow 3 bulan ke depan berdasar tagihan outstanding

---

## Phase 13 — Multi-Cabang (P3, **6 minggu**)

Sudah ada tabel `cabang` tapi belum dipakai serius.

### 13.1 Scope Per-Cabang
- [ ] Filter siswa, jadwal, keuangan per cabang
- [ ] Tutor bisa multi-cabang
- [ ] Owner: lihat agregat semua cabang
- [ ] Admin cabang: scope ke 1 cabang only

### 13.2 Branding Per-Cabang
- [ ] Logo, alamat, nomor WA per cabang
- [ ] Rapor cetak otomatis pakai branding cabang

---

## Quick Wins (kerjakan kapan saja, scope <1 hari)

| Item | Effort | Benefit |
|---|---|---|
| Bulk action di siswa list (deactivate, kirim WA) | 4j | High — admin sering kerja batch |
| Search global di topbar (sudah ada UI, perlu logic) | 4j | High — saat ini cuma search siswa |
| Export rapor multi-siswa ke 1 PDF | 4j | Medium — bagi-bagi rapor saat ortu meeting |
| Dark mode toggle | 6j | Low — cosmetic |
| Filter rekap nilai by program | 2j | Medium — sudah hampir ada |
| Backup otomatis ke Google Drive | 8j | High — risk mitigation |
| Reset password via email | 6j | High — user friendly |
| Import siswa dari Excel | 8j | Medium — onboarding cepat |
| Print struk pembayaran 58mm | 6j | Medium — kasir |
| Snippet template SOP per program | 3j | Low — admin training |

---

## Anti-Goals — Tidak Perlu Dikerjakan

Hal yang sengaja **tidak** dimasukkan roadmap karena overkill atau bertentangan dengan kondisi shared hosting:

- ❌ **Microservices** — sistem masih kecil, monolith justru lebih maintainable
- ❌ **Docker / Kubernetes** — Hostinger shared hosting tidak support
- ❌ **GraphQL API** — REST cukup; tidak ada client kompleks
- ❌ **Migrasi ke framework (Laravel/Symfony)** — biaya migrasi >> benefit; sistem stabil
- ❌ **Real-time WebSocket** — ⏰ shared hosting tidak support long polling
- ❌ **AI auto-grading nilai** — terlalu spekulatif, ROI tidak jelas

---

## Risiko Teknikal

| Risiko | Mitigasi |
|---|---|
| Kolom GENERATED tidak konsisten antar MariaDB versi | Test di staging dulu sebelum deploy ke produksi |
| Migration di produksi gagal sebagian → schema mixed | Selalu pakai pola schema-safe (lihat CLAUDE.md) |
| Quota Fonnte habis → notif tidak terkirim | Monitor saldo via API + fallback ke email |
| Disk penuh karena audit_log + cache | Cron prune log >90 hari, cache >7 hari |
| Database corrupt | Backup harian otomatis (Phase 5.3 / Quick Wins) |
| Session timeout terlalu cepat | Sudah ada `session_bootstrap` — tinggal config TTL |

---

## Catatan Migrasi v2.0 → v2.1

**Wajib jalankan urutannya:**
1. Backup DB lama (`mysqldump u741010203_adminperkasa > backup_v2.sql`)
2. Run migration: `011_add_binjas_skor_columns.sql`
3. Run migration: `012_program_paket_spp_redesign.sql`
4. Verifikasi: query `SHOW COLUMNS FROM program` → harus ada `tipe_program`
5. Verifikasi: query `SHOW TABLES LIKE 'paket_komponen'` → harus ada
6. Upload code v2.1 (sidebar baru, modul `program/`, dll)
7. Test login, cek modul Manajemen Program tampil di sidebar
8. Test pembayaran SPP — coba dengan 1 siswa dummy

**Rollback plan:**
- Jika code v2.1 error parah → revert via Hostinger File Manager (folder backup)
- Jika migration error → schema lama tetap kompatibel karena pola schema-safe (kolom baru NULL = behavior lama)

---

## Estimasi Timeline Agregat

| Phase | Effort | Selesai (estimasi) |
|---|---|---|
| 5. Stabilisasi v2.1 | 2 minggu | Mei 2026 |
| 6. Notifikasi otomatis | 2 minggu | Juni 2026 |
| 7. Laporan keuangan | 3 minggu | Juli 2026 |
| 8. Inventory | 3 minggu | Agt 2026 |
| 9. Portal ortu | 3 minggu | Sep 2026 |
| 10. Payment gateway | 4 minggu | Okt 2026 |
| 11. PWA | 6 minggu | Des 2026 |
| 12. Analytics | 4 minggu | Q1 2027 |
| 13. Multi-cabang | 6 minggu | Q2 2027 |

**Total: ~9 bulan** untuk Phase 5-13 sequensial. Beberapa bisa parallel (misalnya P1 + Quick Wins).

---

## Owner Decision Points

Hal yang butuh persetujuan owner sebelum dikerjakan:
- 🟡 **Phase 10** — biaya gateway 0.7%-2.5% per transaksi (hitung break-even)
- 🟡 **Phase 11** — perlu Firebase setup + biaya hosting jika notif > 1k/bulan
- 🟡 **Phase 13** — buka cabang baru harus diputuskan dulu sebagai bisnis decision
- 🟢 **Phase 5-9** — pure improvement, bisa langsung jalan

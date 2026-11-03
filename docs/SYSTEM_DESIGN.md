# System Design Document (SDD)
## Gym Management System (Admin Web & Member API)

---

## 1. System Architecture Overview

Sistem menggunakan arsitektur modular yang membagi fungsi menjadi dua layer utama:
1. **Admin Backoffice (Web Application)**: Dibangun menggunakan **Laravel 13**, **Livewire 4**, dan **Flux UI** dengan autentikasi sesi berbasis cookie (Laravel Fortify).
2. **Member Service (REST API)**: Menyediakan endpoint stateless untuk aplikasi native member menggunakan **Laravel Sanctum** (Bearer Token) dengan autentikasi No. HP dan PIN/Password.

```
┌────────────────────────────────┐         ┌─────────────────────────────────┐
│     Admin / Resepsionis        │         │      Member (Mobile Native)     │
│   (Web Browser / Desktop / UI) │         │     (iOS / Android Client)      │
└───────────────┬────────────────┘         └────────────────┬────────────────┘
                │ HTTP / Session                            │ REST API / Bearer Token
                ▼                                           ▼
┌────────────────────────────────────────────────────────────────────────────┐
│                           LARAVEL APPLICATION                              │
│                                                                            │
│  ┌───────────────────────────────┐     ┌────────────────────────────────┐  │
│  │   Livewire 4 + Flux UI        │     │   API Controllers (v1)         │  │
│  │   - Dashboard & Reporting     │     │   - Sanctum Auth (Phone + PIN) │  │
│  │   - Master Data (CRUD + Modal)│     │   - Member Profile & QR Code   │  │
│  │   - AJAX Table Pagination     │     │   - Subscription & PT Quota    │  │
│  │   - Billing & Invoicing       │     │   - History (Presensi/Invoice) │  │
│  │   - Fast Check-in Terminal    │     │                                │  │
│  └───────────────┬───────────────┘     └───────────────┬────────────────┘  │
│                  │                                     │                   │
│                  └──────────────────┬──────────────────┘                   │
│                                     ▼                                      │
│                         Service / Domain Layer                             │
│                  (SubscriptionService, PTQuotaService)                     │
│                                     │                                      │
│                                     ▼                                      │
│                            Eloquent Models                                 │
└─────────────────────────────────────┬──────────────────────────────────────┘
                                      ▼
                        ┌───────────────────────────┐
                        │   Relational Database     │
                        │ (MySQL / SQLite / PgSQL)  │
                        └───────────────────────────┘
```

---

## 2. Database Schema (Entity-Relationship Design)

### 2.1. Tabel `users` (Staff & Admin)
Digunakan hanya untuk staf internal yang mengakses web admin.
* `id`: BIGINT (PK)
* `name`: VARCHAR(100)
* `email`: VARCHAR(100) (UNIQUE)
* `password`: VARCHAR(255)
* `role`: ENUM (`admin`, `receptionist`) - default: `receptionist`
* `is_active`: BOOLEAN - default: `true`
* `created_at`, `updated_at`: TIMESTAMP

### 2.2. Tabel `members` (Data Anggota Gym)
Terpisah dari staf internal, menampung seluruh member dan autentikasi aplikasi native.
* `id`: BIGINT (PK)
* `member_code`: VARCHAR(30) (UNIQUE) — format: `GYM-YYMM-XXXX` (dapat di-override nomor kartu fisik)
* `name`: VARCHAR(150)
* `phone`: VARCHAR(20) (UNIQUE) — kredensial login utama member
* `password`: VARCHAR(255) — hash PIN 6-digit / password sederhana untuk app native
* `gender`: ENUM (`male`, `female`) (nullable)
* `birth_date`: DATE (nullable)
* `address`: TEXT (nullable)
* `emergency_contact`: VARCHAR(20) (nullable)
* `notes`: TEXT (nullable)
* `join_date`: DATE
* `is_active`: BOOLEAN - default: `true`
* `created_at`, `updated_at`: TIMESTAMP

### 2.3. Tabel `trainers` (Instruktur / Personal Trainer)
* `id`: BIGINT (PK)
* `name`: VARCHAR(150)
* `phone`: VARCHAR(20)
* `specialty`: VARCHAR(100) (misal: "Weight Loss", "Hypertrophy", "Functional Training")
* `is_active`: BOOLEAN - default: `true`
* `created_at`, `updated_at`: TIMESTAMP

### 2.4. Tabel `plans` (Paket Layanan)
Menampung opsi paket langganan membership maupun paket kuota sesi PT.
* `id`: BIGINT (PK)
* `name`: VARCHAR(100) (misal: "Gym Regular 1 Bulan", "Paket PT 10 Sesi", "Bundling 3 Bulan + 5 PT")
* `type`: ENUM (`membership`, `pt_session`, `bundle`)
* `duration_days`: INT (nullable, jumlah hari aktif membership)
* `pt_sessions_count`: INT (nullable, kuota sesi PT bawaan)
* `price`: DECIMAL(12, 2)
* `description`: TEXT (nullable)
* `is_active`: BOOLEAN - default: `true`
* `created_at`, `updated_at`: TIMESTAMP

### 2.5. Tabel `invoices` (Transaksi Pembayaran)
* `id`: BIGINT (PK)
* `invoice_number`: VARCHAR(50) (UNIQUE) — format: `INV-YYYYMMDD-XXXX`
* `member_id`: BIGINT (FK -> `members.id`)
* `plan_id`: BIGINT (FK -> `plans.id`, nullable jika custom/manual)
* `amount`: DECIMAL(12, 2)
* `payment_method`: ENUM (`cash`, `transfer`, `qris`)
* `payment_status`: ENUM (`paid`, `unpaid`, `cancelled`) - default: `paid`
* `paid_at`: TIMESTAMP
* `received_by_user_id`: BIGINT (FK -> `users.id`) — staf kasir yang menerima
* `notes`: TEXT (nullable)
* `created_at`, `updated_at`: TIMESTAMP

### 2.6. Tabel `member_subscriptions` (Histori Masa Aktif Membership)
Mencatat rentang masa aktif keanggotaan gym.
* `id`: BIGINT (PK)
* `member_id`: BIGINT (FK -> `members.id`)
* `invoice_id`: BIGINT (FK -> `invoices.id`, nullable)
* `plan_id`: BIGINT (FK -> `plans.id`, nullable)
* `start_date`: DATE
* `end_date`: DATE
* `status`: ENUM (`active`, `expired`, `cancelled`)
* `created_at`, `updated_at`: TIMESTAMP

### 2.7. Tabel `member_pt_quotas` (Saldo Kuota Sesi PT Member)
Saldo kuota sesi privat member yang fleksibel (dapat diinput admin saat pembelian).
* `id`: BIGINT (PK)
* `member_id`: BIGINT (FK -> `members.id`)
* `invoice_id`: BIGINT (FK -> `invoices.id`, nullable)
* `total_sessions`: INT — total kuota yang dibeli
* `used_sessions`: INT - default: `0`
* `remaining_sessions`: INT — dihitung otomatis (`total_sessions - used_sessions`)
* `is_active`: BOOLEAN - default: `true`
* `created_at`, `updated_at`: TIMESTAMP

### 2.8. Tabel `pt_session_logs` (Histori Penggunaan Sesi PT)
Mencatat setiap kali member menyelesaikan sesi latihan dengan trainer.
* `id`: BIGINT (PK)
* `member_pt_quota_id`: BIGINT (FK -> `member_pt_quotas.id`)
* `member_id`: BIGINT (FK -> `members.id`)
* `trainer_id`: BIGINT (FK -> `trainers.id`) — admin memilih trainer yang mendampingi
* `recorded_by_user_id`: BIGINT (FK -> `users.id`) — admin/staf yang memverifikasi
* `used_at`: TIMESTAMP
* `notes`: VARCHAR(255) (nullable, misal: "Chest day, bench press 60kg")
* `created_at`, `updated_at`: TIMESTAMP

### 2.9. Tabel `attendances` (Presensi / Check-in)
* `id`: BIGINT (PK)
* `member_id`: BIGINT (FK -> `members.id`)
* `check_in_time`: TIMESTAMP
* `verified_by_user_id`: BIGINT (FK -> `users.id`)
* `notes`: VARCHAR(255) (nullable)
* `created_at`, `updated_at`: TIMESTAMP

---

## 3. Business Logic & Core Workflows

### 3.1. Logika Perpanjangan Membership (Akumulatif)
Saat transaksi paket membership baru dibayar:
1. Cari subscription terakhir member yang aktif (`status = 'active'` dan `end_date >= TODAY`).
2. **Kondisi A (Masih Aktif)**:
   * `start_date` baru = `subscription_terakhir.end_date + 1 hari`.
   * `end_date` baru = `start_date_baru + plan.duration_days`.
   * Member tidak rugi hari yang tersisa.
3. **Kondisi B (Sudah Expired / Member Baru)**:
   * `start_date` baru = `TODAY`.
   * `end_date` baru = `TODAY + plan.duration_days`.

### 3.2. Penambahan & Pengurangan Kuota Sesi PT
* **Beli Kuota**:
  Admin menginput jumlah sesi yang dibeli (bisa melalui paket atau manual input saat invoice dibuat). Dibuat record baru di `member_pt_quotas`.
* **Pakai Sesi (Deduct)**:
  1. Admin memilih member -> Sistem memeriksa record `member_pt_quotas` yang memiliki `remaining_sessions > 0`.
  2. Admin memilih Trainer yang mendampingi sesi tersebut.
  3. Sistem membuat baris baru di `pt_session_logs`.
  4. Sistem menginkrementasi `used_sessions + 1` dan mengurangi `remaining_sessions - 1`.
  5. Jika `remaining_sessions == 0`, status kuota ditandai selesai/nonaktif.

### 3.3. Check-in & Terminal Presensi
1. Resepsionis mengetik nama / ID kartu atau scan QR barcode (`member_code`).
2. Query cepat mengambil data member beserta subscription aktif dan saldo PT.
3. **Validasi Layar**:
   * Jika `end_date >= TODAY`: Status **HIJAU (Member Aktif)** -> Izinkan tombol *Check-in*.
   * Jika `end_date < TODAY` atau tidak ada paket aktif: Status **MERAH (Expired)** -> Peringatan untuk bayar perpanjangan.
   * Jika `end_date <= TODAY + 7`: Status **KUNING (Masa Aktif Segera Habis)** -> Notifikasi reminder untuk admin.
4. Klik *Check-in*: Menyimpan log ke tabel `attendances` dengan waktu saat ini.

### 3.4. Auto-Generate Kode Member
* Format default: `GYM-YYMM-XXXX` (contoh: `GYM-2611-0001`).
* Jika gym memiliki kartu fisik dengan barcode pabrikan bawaan, sistem menyediakan kolom teks untuk input manual yang menimpa (override) kode auto-generate.

### 3.5. Interaksi Antarmuka: AJAX Pagination & Modal Detail Data
1. **Paginasi Berbasis AJAX**:
   - Seluruh daftar tabel data pada modul backoffice (Member, Trainer, Paket, Transaksi, Presensi) menggunakan `Livewire\WithPagination`.
   - Pergantian halaman data berjalan asinkron via AJAX tanpa me-reload browser, memberikan pengalaman single-page application (SPA) yang responsif bagi staf kasir.
2. **Detail Data Menggunakan Modal / Pop-up**:
   - Tampilan rincian (detail) setiap record data (kartu member lengkap, riwayat paket/presensi, spesialisasi trainer, rincian benefit paket) dibuka langsung menggunakan komponen `<flux:modal>`.
   - Staf tidak perlu berpindah halaman (no page redirect) untuk melihat detail maupun melakukan edit kilat.

---

## 4. API Specification for Member Native App

Semua endpoint API beralamat di prefix `/api/v1` dan mengembalikan JSON dengan format standar:
```json
{
  "success": true,
  "message": "Deskripsi respon",
  "data": { ... }
}
```

### 4.1. Authentication (Sanctum)
* `POST /api/v1/auth/login`
  * Body: `{ "phone": "08123456789", "pin": "123456" }`
  * Response: Token Sanctum & basic profile info.
* `POST /api/v1/auth/logout` (Header: `Authorization: Bearer <TOKEN>`)
  * Membatalkan token akses saat ini.

### 4.2. Member Profile & Digital Card
* `GET /api/v1/member/profile`
  * Menampilkan nama, No. HP, tanggal bergabung, dan status aktif.
* `GET /api/v1/member/card`
  * Menampilkan `member_code`, nama lengkap, payload string QR Code, dan status membership ringkas (untuk ditampilkan di layar ponsel saat check-in).

### 4.3. Status Keanggotaan & Kuota
* `GET /api/v1/member/subscription`
  * Paket yang sedang aktif, tanggal mulai, tanggal kedaluwarsa, dan sisa hari.
* `GET /api/v1/member/pt-quota`
  * Total kuota sesi, sesi terpakai, sisa sesi, dan riwayat pemakaian sesi terakhir (nama trainer, tanggal, catatan).

### 4.4. Histori Kehadiran & Pembayaran
* `GET /api/v1/member/attendances?page=1`
  * Daftar log tanggal & jam check-in member (paginated).
* `GET /api/v1/member/invoices?page=1`
  * Riwayat tagihan, nominal yang dibayar, metode pembayaran, dan status lunas.

---

## 5. Security & Simplicity Guidelines
1. **Password Hashing**: PIN/Password member dienkripsi menggunakan Argon2id / Bcrypt (`Hash::make()`).
2. **Rate Limiting**: Endpoint login dibatasi maksimal 5 percobaan per menit untuk mencegah brute-force.
3. **No Overengineering**:
   * Tidak ada microservices atau queueing yang berlebihan; queries Eloquent teroptimasi dengan index pada `member_code`, `phone`, dan `start_date`/`end_date`.
   * Pencatatan pembayaran berfokus pada status kasir manual tanpa webhook payment gateway di MVP.

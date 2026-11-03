# Product Requirements Document (PRD)
## Gym Management System (Admin Gym & Member MVP)

---

## 1. Executive Summary
Sistem Manajemen Gym adalah platform operasional berbasis web untuk admin/pengelola gym dan penyedia data monitoring untuk member (mendukung rencana aplikasi native). Tujuan utama sistem ini adalah menyediakan solusi **Minimum Viable Product (MVP)** yang efisien, mudah digunakan oleh staf gym, dan bebas dari fitur overengineering, namun siap diintegrasikan dengan aplikasi mobile member.

---

## 2. User Roles & Personas
1. **Admin / Resepsionis (Web Admin)**:
   - Mengelola master data member dan trainer.
   - Menginput transaksi tagihan dan perpanjangan paket.
   - Melakukan check-in kehadiran member & trainer harian.
   - Memantau member yang akan/telah kedaluwarsa.
   - Mengurangi kuota sesi PT setelah sesi latihan selesai.
2. **Member (Target Aplikasi Native / Portal Monitoring)**:
   - Melihat status keaktifan paket membership.
   - Menampilkan kartu member digital (QR Code/Barcode) untuk check-in.
   - Memantau sisa kuota sesi Personal Trainer (PT).
   - Melihat riwayat kehadiran dan pembayaran.

---

## 3. Scope of Work (Ruang Lingkup)

### ✅ In-Scope (MVP)
* **Master Member**: Pendaftaran, edit profil, status (Aktif/Non-Aktif), nomor kontak (WhatsApp), riwayat paket.
* **Master Trainer**: Profil personal trainer, spesialisasi, kontak, status aktif.
* **Master Paket**: Jenis langganan (durasi: visit, bulanan, tahunan) dan paket kuota PT (misal: 10 sesi).
* **Transaksi & Tagihan**: Pembuatan invoice, pencatatan pembayaran manual (Cash, Transfer Bank, QRIS manual), aktivasi/perpanjangan masa aktif otomatis.
* **Tracking Sesi PT**: Pencatatan kuota sesi per member, log pemakaian sesi bersama trainer.
* **Check-in / Presensi**: Validasi status aktif member saat datang dan log waktu masuk.
* **Dashboard Ringkas**: Total member aktif, daftar member jatuh tempo (H-7 s/d expired), revenue bulanan.
* **API Readiness for Member App**: Endpoint read-only untuk profil member, kartu digital, status kuota, dan history.

### ❌ Out-of-Scope (Fase Lanjutan / Non-MVP)
* Integrasi otomatis turnstile gate / RFID hardware.
* Otomatisasi Payment Gateway (Midtrans, Xendit, dll.) — pencatatan manual cukup untuk MVP.
* Booking jadwal kelas online mandiri oleh member.
* Point of Sales (POS) suplemen/F&B dan inventaris alat gym.
* Sistem penggajian / perhitungan komisi trainer yang kompleks.

---

## 4. Rincian Modul & Fitur (Feature Specifications)

### Modul 1: Master Data Member
* **Fields**: Nomor Member (auto-generated / custom ID), Nama Lengkap, No. HP / WhatsApp, Email (opsional), Tanggal Bergabung, Catatan Medis/Kebugaran (opsional).
* **Fitur**:
  * Form tambah/edit data member.
  * Pencarian cepat berdasarkan Nama, No. HP, atau ID Member.
  * Indikator status keaktifan membership (Active, Expiring Soon, Expired, Inactive).

### Modul 2: Master Data Trainer
* **Fields**: Nama Trainer, No. HP / WhatsApp, Spesialisasi, Status (Aktif/Non-Aktif).
* **Fitur**:
  * Tambah/edit data trainer.
  * Daftar member yang sedang didampingi (aktif).

### Modul 3: Master Paket (Membership & PT Plans)
* **Kategori Paket**:
  1. *Membership Gym*: Durasi (Harian/Visit, 1 Bulan, 3 Bulan, 6 Bulan, 12 Bulan).
  2. *Paket Personal Trainer*: Jumlah sesi (misal: 5 sesi, 10 sesi, 20 sesi) dengan masa berlaku sesi.
* **Fields**: Nama Paket, Tipe (Membership / PT), Durasi (hari/bulan), Kuota Sesi (jika PT), Harga, Status Aktif.

### Modul 4: Transaksi & Tagihan (Billing & Subscriptions)
* **Alur Transaksi**:
  1. Admin memilih Member.
  2. Memilih Paket yang dibeli (bisa bundling: Membership + Sesi PT).
  3. Memasukkan metode pembayaran (Cash, Transfer, QRIS) dan nominal bayar.
  4. Simpan -> Invoice otomatis berstatus `Lunas`.
  5. Masa berlaku membership member otomatis bertambah sesuai durasi paket.
  6. Jika paket menyertakan sesi PT, saldo kuota sesi otomatis bertambah ke akun member.
* **Fitur**:
  * Daftar invoice / riwayat transaksi.
  * Cetak atau preview kwitansi / struk digital.

### Modul 5: Tracking Sesi PT
* **Alur Penggunaan Sesi**:
  1. Member datang latihan dengan Trainer.
  2. Admin/Trainer memilih Member & Paket PT aktif yang dimiliki.
  3. Klik tombol *Pakai Sesi* (Deduct 1 sesi).
  4. Log otomatis mencatat: Tanggal/Jam, Trainer pendamping, Catatan latihan singkat.
  5. Sistem menampilkan sisa kuota (misal: "Sisa 3 dari 10 sesi").

### Modul 6: Check-in & Presensi Harian
* **Alur Presensi**:
  1. Resepsionis memasukkan No. Member atau scan QR Code member.
  2. Layar menampilkan kartu status:
     - 🟢 **Hijau (Valid)**: Membership aktif, tampilkan info s/d tanggal berapa.
     - 🔴 **Merah (Expired/Blocked)**: Membership kedaluwarsa atau belum lunas.
  3. Tombol *Check-in*: Menyimpan log kehadiran (Waktu & Tanggal Masuk).
  4. Tabel Log Kehadiran harian untuk rekap kunjungan.

### Modul 7: Dashboard Admin
* **Kartu Metrik Kunci**:
  * Total Member Aktif.
  * Member Jatuh Tempo Minggu Ini (H-7).
  * Check-in Hari Ini.
  * Total Pendapatan Bulan Ini.
* **Tabel Perhatian Cepat (Quick Action)**:
  * Daftar member yang akan habis masa berlakunya dalam 7 hari ke depan (dilengkapi tombol cepat kirim WhatsApp reminder).

### Modul 8: Member Monitoring (Persiapan Native App / API Scope)
* **Kebutuhan Data (Read-only)**:
  * `GET /api/v1/member/profile`: Info profil member dan ID.
  * `GET /api/v1/member/card`: Payload untuk render QR code member digital.
  * `GET /api/v1/member/subscription`: Paket aktif, tanggal mulai, tanggal kedaluwarsa, status.
  * `GET /api/v1/member/pt-sessions`: Sisa kuota sesi PT, nama trainer, riwayat sesi terpakai.
  * `GET /api/v1/member/attendance`: Riwayat check-in member.
  * `GET /api/v1/member/invoices`: Riwayat pembayaran dan nominal.

---

## 5. Non-Functional Requirements
1. **Performance & Responsiveness**: Halaman check-in dan pencarian member harus instan (< 1 detik respons).
2. **Mobile Readiness**: Web admin harus responsif jika diakses melalui tablet/smartphone oleh staf gym.
3. **API-First for Member**: Endpoint data member dibuat bersih dan standar JSON REST API untuk mempermudah developer aplikasi native nantinya.
4. **Keamanan & Validasi**: Role-based access (hanya staf yang memiliki akun yang bisa mengakses panel admin).

---

## 6. Milestones Pengembangan (MVP)
* **Milestone 1**: Database Schema & Master Data (Member, Trainer, Paket).
* **Milestone 2**: Transaksi, Tagihan, & Manajemen Subscription (Masa Aktif & Kuota PT).
* **Milestone 3**: Presensi / Check-in & Dashboard Admin.
* **Milestone 4**: REST API Read-Only untuk Integrasi Aplikasi Native Member.

# UI/UX Design & Frontend Specification
## Admin Gym Web Application

---

## 1. Skill Integration & Standard Guidelines

Desain sistem ini mengacu pada standar spesialisasi skill berikut:
* **`better-typography`**: Hierarki type scale, unitless line-height, tabular-nums untuk nominal uang/kuota, dan penerapan font *Times New Roman*.
* **`better-layout`**: Sistem grouping berbasis spacing (space-first, lines-last), logical properties, penanganan visual order, dan adaptasi layout.
* **`better-ui`**: Surface elevation, border radius, styling state interaktif (hover, active, focus-visible), dan durasi animasi transisi.
* **`better-accessibility`**: Kontras warna rasio WCAG 2.2 Level AA (minimal 4.5:1 untuk teks normal, 3:1 untuk komponen UI/teks besar), touch target minimum 44×44px, dan keyboard navigation.
* **`antislop` & `antislop-ui` (Mode: DURING)**: Guardrail kualitas anti-AI slop diaplikasikan langsung selama pengerjaan seluruh komponen antarmuka, forms, dan layout.

---

## 2. Visual Theme & Palette (Luxury Athletic - Dark & Gold)

Kombinasi latar gelap obsidian dengan aksen emas memberikan impresi eksklusif, bertenaga, dan nyaman bagi staf resepsionis yang menatap layar berjam-jam.

```
┌────────────────────────────────────────────────────────────────────────┐
│                              PALET WARNA                               │
├───────────────────┬───────────────────┬────────────────────────────────┤
│ Token             │ Hex / HSL         │ Fungsi Penggunaan              │
├───────────────────┼───────────────────┼────────────────────────────────┤
│ `bg-canvas`       │ `#090D14`         │ Background dasar aplikasi      │
│ `bg-surface`      │ `#131B28`         │ Card, panel sidebar, modal     │
│ `bg-surface-elev` │ `#1A2436`         │ Dropdown, popover, hover state │
│ `border-subtle`   │ `#222D42`         │ Border card dan divider halus  │
│ `border-focus`    │ `#D4AF37`         │ Ring focus input & active state│
│                   │                   │                                │
│ `gold-primary`    │ `#D4AF37`         │ Brand CTA, button utama, badge │
│ `gold-hover`      │ `#E5C158`         │ State hover tombol utama       │
│ `gold-subtle`     │ `rgba(212,175,55, │ Background pill/badge emas,    │
│                   │  0.12)`           │ highlight row aktif            │
│                   │                   │                                │
│ `text-primary`    │ `#F8FAFC`         │ Teks utama, judul, data        │
│ `text-secondary`  │ `#94A3B8`         │ Label form, deskripsi, muted   │
│ `text-gold`       │ `#F3C644`         │ Highlight angka, link aksen    │
│                   │                   │                                │
│ `status-active`   │ `#10B981` (Green) │ Membership valid / check-in ok │
│ `status-warning`  │ `#F59E0B` (Amber) │ Jatuh tempo H-7                │
│ `status-danger`   │ `#EF4444` (Red)   │ Expired / belum lunas          │
└───────────────────┴───────────────────┴────────────────────────────────┘
```

---

## 3. Typography System: Times New Roman Serif Stack

Sesuai arahan, sistem tipografi menggunakan **Times New Roman** sebagai font display/heading utama yang dipadukan secara harmonis dengan font interface:

### 3.1. Font Family Stacks
* **Display & Headings (Serif Elegance)**:
  ```css
  font-family: "Times New Roman", Times, "Baskerville", "Georgia", serif;
  ```
  *Karakter:* Memberikan wibawa klasik, formal, dan kesan klub kebugaran premium (high-end fitness club).
* **Interface, Form, & Table Data (Sans Precision)**:
  ```css
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  ```
  *Karakter:* Keterbacaan optimal pada ukuran kecil, formulir input, dan label data.
* **Numbers, Currency, & Timestamps**:
  ```css
  font-variant-numeric: tabular-nums;
  ```
  *Karakter:* Lebar angka seragam sehingga tabel invoice, saldo sesi PT, dan nominal harga sejajar lurus ke bawah.

### 3.2. Type Scale & Spacing Ratios

| Role Token | Font Family | Size (px / rem) | Weight | Line-Height | Tracking / Spacing | Contoh Penggunaan |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| `display-lg` | Times New Roman | `36px` (2.25rem) | 700 (Bold) | `1.15` | `-0.02em` | Nama Gym di Header, Hero Stat |
| `heading-1` | Times New Roman | `28px` (1.75rem) | 700 (Bold) | `1.2` | `-0.015em` | Judul Halaman Utama |
| `heading-2` | Times New Roman | `22px` (1.375rem)| 600 (Semibold)| `1.25`| `-0.01em` | Header Card / Judul Section |
| `heading-3` | Times New Roman | `18px` (1.125rem)| 600 (Semibold)| `1.3` | `0` | Sub-header, Nama Member di Card |
| `body-base` | System Sans | `15px` (0.9375rem)| 400 (Regular) | `1.5` | `0` | Paragraf, nilai input form |
| `body-sm` | System Sans | `13px` (0.8125rem)| 400 (Regular) | `1.4` | `0` | Teks tabel, deskripsi bantuan |
| `label-caps`| System Sans | `11px` (0.6875rem)| 600 (Semibold)| `1.2` | `+0.06em` (Uppercase) | Label kolom tabel, badge kategori |

---

## 4. Spacing, Padding, & Density Scale

Menerapkan aturan `better-layout`: **Group with space, not lines**. Spasi antar-kelompok selalu minimal 2× lipat dibanding spasi di dalam elemen kelompok.

### 4.1. Base Spacing Scale (Kelipatan 4px / 8px)
* `space-1` (`4px` / `0.25rem`): Gap micro antar-icon dan teks kecil.
* `space-2` (`8px` / `0.5rem`): Padding internal button kecil, gap item dropdown.
* `space-3` (`12px` / `0.75rem`): Padding vertikal form input, padding baris tabel padat.
* `space-4` (`16px` / `1rem`): Padding horizontal button, margin elemen form standar.
* `space-5` (`20px` / `1.25rem`): Padding internal card standar.
* `space-6` (`24px` / `1.5rem`): Padding container halaman tablet, gap antar-card grid.
* `space-8` (`32px` / `2rem`): Padding container halaman desktop, jarak antar-section besar.
* `space-12` (`48px` / `3rem`): Jarak layout macro (header ke konten).

### 4.2. Component Padding Specifics
* **Input Field & Select Box**:
  * Vertical: `12px` (`py-3`) — mencegah zoom otomatis di iOS (font min 16px).
  * Horizontal: `16px` (`px-4`).
  * Radius: `8px` (`rounded-lg`).
* **Buttons (Primary / Secondary)**:
  * Minimum Hit Area: `44px` tinggi (`min-h-[44px]`) untuk kepatuhan accessibility.
  * Padding: `px-6 py-3`.
  * Radius: `8px` (`rounded-lg`).
* **Data Table & AJAX Pagination**:
  * Header cell: `py-3 px-4` (uppercase, tracking +0.06em, text-secondary).
  * Body cell: `py-3.5 px-4` (border bottom 1px `#222D42`).
  * **Pagination Berbasis AJAX**: Seluruh daftar data (Member, Trainer, Paket, dsb.) wajib menerapkan pagination reaktif AJAX menggunakan Livewire `WithPagination` tanpa full page reload.
  * Navigasi paginasi menyertakan info range record (`Menampilkan 1-10 dari 50 data`) dengan angka monospace/tabular-nums.
* **Detail Data via Modal / Pop-up**:
  * Setiap aksi melihat rincian/detail data (profil member lengkap, histori paket, kuota trainer, log pemakaian) dibuka dalam bentuk dialog modal / pop-up interaktif (`<flux:modal>`).
  * Pengguna tidak dipindahkan ke halaman terpisah (no page redirect), menjaga flow kerja meja depan tetap cepat dan fokus.
* **Card Container**:
  * Padding: `p-6` (Desktop), `p-4` (Mobile).
  * Border: `1px solid #222D42`.
  * Radius: `12px` (`rounded-xl`).

---

## 5. Overflow Architecture & Viewport Protection

Untuk memastikan aplikasi tidak pernah mengalami clipping atau layout glitch saat data memanjang:

### 5.1. Global Viewport Rules
* `html, body`:
  ```css
  overflow-x: hidden;
  max-width: 100vw;
  min-height: 100vh;
  ```
  Mencegah horizontal scrollbar liar di seluruh aplikasi.

### 5.2. Halaman Terminal Check-in (Zero Scroll)
* Layar resepsionis didesain **Fixed Full-Viewport** (`h-screen overflow-hidden` di desktop):
  * Sisi Kiri (Scanner & Status Card): Tetap statis di posisi tengah pandangan mata, tidak bergerak saat data masuk.
  * Sisi Kanan (Log Kehadiran Hari Ini): Memiliki kontainer mandiri dengan `overflow-y-auto` dan scrollbar tipis (custom dark scrollbar).

### 5.3. Tabel Data (Horizontal Overflow Handling)
* Setiap tabel dibungkus kontainer:
  ```html
  <div class="w-full overflow-x-auto scrollbar-thin">
    <table class="w-full min-w-[720px] text-left"> ... </table>
  </div>
  ```
* Kolom paling kanan (**Aksi / Tombol**) dapat diset `sticky right-0` dengan latar `#131B28` agar tombol aksi selalu terlihat saat tabel digeser di layar kecil.

### 5.4. Text Truncation & Wrapping Rules
* Nama member atau email panjang: Gunakan `truncate max-w-[200px]` dipadukan dengan HTML `title="Full Name"` atau tooltip agar nama lengkap tetap dapat dibaca tanpa merusak layout tabel.

---

## 6. Responsive Strategy & Breakpoints

Sistem mengadopsi prinsip `better-layout`: **Hold structure until it breaks** (tahan layout sampai konten tidak muat, lalu runtuhkan secara teratur).

```
┌────────────────────────────────────────────────────────────────────────┐
│                        BREAKPOINTS BREAKDOWN                           │
├───────────────┬─────────────────┬──────────────────────────────────────┤
│ Layar         │ Target Device   │ Perilaku Layout                      │
├───────────────┼─────────────────┼──────────────────────────────────────┤
│ `< 640px`     │ Mobile Phone    │ Single column, bottom bar navigation,│
│ (sm)          │                 │ modal berubah jadi bottom sheet      │
├───────────────┼─────────────────┼──────────────────────────────────────┤
│ `640px-1023px`│ Tablet (Kasir / │ Sidebar collapse jadi Icon-Rail,     │
│ (md - lg)     │ iPad Resepsionis│ grid 2 kolom, tabel scroll horizontal│
├───────────────┼─────────────────┼──────────────────────────────────────┤
│ `>= 1024px`   │ Desktop / PC Meja│ Sidebar penuh (260px), layout multi- │
│ (xl)          │ Resepsionis     │ kolom, zero-scroll terminal check-in │
└───────────────┴─────────────────┴──────────────────────────────────────┘
```

### 6.1. Adaptasi Antarmuka Spesifik

#### A. Terminal Check-in
* **Desktop (`>= 1024px`)**:
  * 2 Kolom berdampingan: Kiri (60% lebar untuk Card Verifikasi Hijau/Merah) + Kanan (40% untuk daftar 10 orang terakhir yang masuk).
* **Tablet / Mobile (`< 1024px`)**:
  * 1 Kolom bertumpuk: Card Scanner di atas, riwayat check-in di bawahnya.

#### B. Navigasi Sidebar
* **Desktop**: Lebar tetap `260px` dengan teks menu lengkap dan logo gym.
* **Tablet**: Menyusut menjadi `72px` (hanya menampilkan ikon dengan tooltip saat hover).
* **Mobile**: Tersembunyi di balik tombol Hamburger (`drawer slide-over` dari kiri).

#### C. Modal & Formulir
* **Desktop & Tablet**: Dialog pop-up di tengah layar dengan backdrop blur (`backdrop-blur-sm bg-black/60`).
* **Mobile**: Bottom sheet menempel di bagian bawah layar agar mudah dijangkau satu jempol.

---

## 7. Keyboard Shortcuts & Interaksi Meja Depan

* `Ctrl + K` atau `/`: Auto-focus ke input pencarian member dari halaman manapun.
* `Enter`:
  * Saat kartu member muncul -> Langsung eksekusi **Check-in**.
  * Saat di form modal transaksi -> Eksekusi **Simpan Transaksi**.
* `Esc`: Menutup modal aktif / membersihkan kartu pencarian.
* `Tab` / `Shift+Tab`: Urutan fokus logis mengikuti alur visual (DOM order sesuai kaidah `better-accessibility`).

---

## 8. Quality Audit Gate (Anti-Slop Mode: DURING)

Sepanjang pengerjaan antarmuka, filter anti-slop diterapkan secara langsung dengan checklist guardrail berikut:
1. **Pemeriksaan Kontras**: Semua teks putih di atas latar obsidian dan teks emas memiliki kontras >= 4.5:1.
2. **Eliminasi AI Clichés**: Tidak ada gradient ungu generic, tidak ada ikon roket/kilat hiasan tanpa fungsi, tidak ada shadow glow berlebihan.
3. **Integritas Times New Roman**: Render judul serif tampak tajam, tidak ada font-weight sintetis yang merusak proporsi huruf serif.
4. **Verifikasi Responsif & State**: Tidak ada horizontal overflow di breakpoint 375px (iPhone SE) hingga 1920px (Desktop Full HD), seluruh modal detail dan form dapat ditutup dengan `Esc`, dan paginasi AJAX berjalan mulus.

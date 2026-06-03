<div align="center">

# 🏥 MefaSafe — Platform Asuransi Kesehatan Digital

[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![React](https://img.shields.io/badge/React-19.x-61DAFB?style=for-the-badge&logo=react&logoColor=black)](https://react.dev)
[![Vite](https://img.shields.io/badge/Vite-8.x-646CFF?style=for-the-badge&logo=vite&logoColor=white)](https://vitejs.dev)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Sanctum](https://img.shields.io/badge/Sanctum-Auth-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/docs/sanctum)

**Kelompok 4 · Kelas T2D**  
D3 Teknologi Informasi — Fakultas Vokasi, Universitas Brawijaya  
Tahun Akademik 2025/2026

</div>

---

## 📋 Deskripsi Proyek

**MefaSafe** adalah platform asuransi kesehatan digital berbasis web yang memungkinkan pengguna mengelola polis asuransi, mengajukan klaim, mendaftar ke rumah sakit, berkonsultasi dengan dokter, memantau kesehatan, memanfaatkan promo & referral, serta mendapatkan pengingat jadwal kontrol — semuanya dalam satu aplikasi yang modern dan responsif.

Dibangun menggunakan **Laravel 13** sebagai backend REST API dan **React 19** sebagai Single Page Application (SPA) di frontend, dengan desain glassmorphism, carousel banner ala aplikasi kesehatan modern, dan panel admin terpusat.

---

## ✨ Fitur Utama

### 🔐 Autentikasi & Profil
- Registrasi akun baru dengan syarat & ketentuan
- Login dengan token berbasis **Laravel Sanctum**
- Manajemen profil lengkap (foto, KTP, tanda tangan digital)
- Avatar inisial nama otomatis jika foto belum diunggah

### 🏠 Dashboard Utama
- Saldo asuransi real-time dengan toggle visibilitas
- Status polis aktif/tidak aktif + tanggal kadaluarsa
- Statistik: perlindungan, klaim disetujui, member aktif, rating
- **Banner carousel** — gabungan promo & informasi (gaya Halodoc)
- **Testimonial** — "Kata Mereka tentang MefaSafe" dari feedback yang disetujui admin
- Akses cepat ke semua layanan
- Notifikasi pengingat dengan badge counter

### 🎁 Promo, Referral & Kode Diskon
- **Program referral** — kode unik per pengguna, input kode teman, kupon diskon otomatis
- Pengaturan promo oleh admin: persentase diskon, minimal referral, upload gambar banner
- Halaman **Promo** (`/promo`) — info program, carousel, form apply referral
- **Kode promo pembayaran** — admin buat kode untuk fitur tertentu (asuransi, konsultasi, pendaftaran layanan, dll.)
- Input kode promo di alur pembayaran: Health Service, Konsultasi, Pendaftaran Layanan
- Validasi kode via API sebelum checkout

### 📢 Informasi & Banner
- Admin kelola **pengumuman/informasi** terpisah dari promo (judul, gambar, urutan tampil)
- API `/banners/active` menggabungkan promo + informasi aktif untuk carousel dashboard

### 🛡️ Manajemen Asuransi
- Lihat semua polis aktif milik pengguna
- Paket asuransi tersedia (aktif/nonaktif dikelola admin)
- Detail coverage, premi, dan periode perlindungan
- Pembelian polis dengan diskon kode promo
- Siklus pembayaran premi bulanan/tahunan
- Tanggal jatuh tempo premi berikutnya dan masa tenggang
- Upload bukti pembayaran premi lanjutan dari halaman polis
- Auto status tagihan: pending saat jatuh tempo, inactive setelah lewat masa tenggang

### 💰 Klaim Asuransi
- Pengajuan klaim dengan upload dokumen pendukung
- Tracking status klaim (menunggu, diproses, disetujui, ditolak)
- Riwayat klaim lengkap

### 🏨 Daftar & Pendaftaran Rumah Sakit
- Peta interaktif berbasis **Leaflet.js** dengan marker RS mitra
- Filter RS mitra vs umum
- Pendaftaran online dengan pilihan dokter & jadwal
- Barcode otomatis sebagai tiket pendaftaran
- QR Code untuk verifikasi di lokasi

### 👨‍⚕️ Konsultasi Dokter
- Daftar dokter spesialis dari database (avatar inisial nama)
- Pencarian dokter berdasarkan nama / spesialisasi
- Status ketersediaan dokter real-time
- Chat konsultasi dengan sistem pesan
- Riwayat konsultasi + hapus konsultasi
- Pembayaran konsultasi dengan kode promo

### 📋 Pendaftaran Layanan Kesehatan
- Layanan dinamis dari database (MCU, Lab, Fisioterapi, dll.)
- Pilih jadwal, waktu, dan catatan tambahan
- Nomor antrian dan barcode otomatis
- Input kode promo saat pembayaran

### 📊 Monitor Asuransi
- Grafik statistik polis dan klaim dengan **Recharts**
- Ringkasan penggunaan limit asuransi

### 🗓️ Kalender Pengingat
- Tambah, edit, hapus pengingat jadwal kontrol
- Kategori: kontrol, minum obat, vaksin, dll.
- Pengulangan: harian, mingguan, bulanan
- **Popup notifikasi real-time** di semua halaman
- Badge counter di menu navigasi

### 🤖 MefaBot (AI Chatbot)
- Chatbot berbasis **Google Gemini AI**
- Menjawab pertanyaan seputar kesehatan & asuransi
- Fallback responses jika API tidak tersedia
- Quick reply suggestions

### 📜 Riwayat Lengkap
- Riwayat pendaftaran RS, konsultasi, klaim, dan transaksi
- Filter dan tampilan per kategori

### 🔔 Sistem Notifikasi
- Notifikasi terintegrasi dari semua modul
- Ringkasan notifikasi belum dibaca
- Notifikasi premi: segera jatuh tempo, menunggu verifikasi, dan lewat masa tenggang

### 💬 Feedback & Rating
- Formulir masukan pengguna dengan kategori dan rating bintang
- Admin menyetujui feedback untuk ditampilkan di homepage (`is_featured`)

### ℹ️ Halaman Informasi & Dukungan
- **Tentang Kami** — fitur platform, tim, tech stack, CTA
- **Pusat Bantuan**, **FAQ**, **Syarat & Ketentuan**, **Kebijakan Privasi**
- **Peta Situs** — navigasi cepat ke semua halaman aplikasi
- **Kebijakan Cookie** — penjelasan cookie & local storage
- **Aksesibilitas** — panel ukuran teks, kontras tinggi, kurangi animasi (tersimpan di perangkat)

### 🛠️ Panel Admin
- Dashboard statistik ringkas
- CRUD: pengguna, klaim, polis, transaksi, RS, dokter, paket asuransi
- Kelola konsultasi & verifikasi pembayaran
- **Promo banner** — upload gambar, diskon %, syarat referral
- **Informasi/pengumuman** — konten non-promo untuk carousel
- **Kode promo** — kode diskon per fitur, limit penggunaan, masa berlaku
- **Feedback** — toggle tampilkan di homepage
- Login admin terpisah (`/admin`)

---

## 🛠️ Tech Stack

| Lapisan | Teknologi |
|---------|-----------|
| **Backend** | Laravel 13, PHP 8.3+, Laravel Sanctum |
| **Frontend** | React 19, React Router DOM v7, Axios |
| **Build Tool** | Vite 8 |
| **Styling** | Tailwind CSS 4 (utility-first) |
| **Database** | MySQL 8.0 |
| **Peta** | Leaflet.js + React-Leaflet |
| **Grafik** | Recharts |
| **Icon** | Lucide React |
| **AI** | Google Gemini AI API |
| **Auth** | Laravel Sanctum (SPA token) |

---

## 📁 Struktur Proyek

```
MefaSafe/
├── app/
│   ├── Http/Controllers/Api/
│   │   ├── AuthController.php
│   │   ├── UserController.php
│   │   ├── HomeDashboardController.php
│   │   ├── InsurancePolicyController.php
│   │   ├── InsurancePackageController.php
│   │   ├── ClaimController.php
│   │   ├── TransactionController.php
│   │   ├── HospitalController.php
│   │   ├── HospitalRegistrationController.php
│   │   ├── DoctorConsultationController.php
│   │   ├── HealthServiceController.php
│   │   ├── ServiceRegistrationController.php
│   │   ├── MonitorController.php
│   │   ├── ReminderController.php
│   │   ├── NotificationController.php
│   │   ├── RiwayatController.php
│   │   ├── FeedbackController.php
│   │   ├── ChatBotController.php
│   │   ├── PromotionController.php        # Promo aktif
│   │   ├── AnnouncementController.php       # Informasi aktif
│   │   ├── BannerController.php             # Gabungan banner carousel
│   │   ├── ReferralController.php           # Referral & kupon
│   │   ├── PromoCodeController.php          # Validasi kode promo
│   │   └── AdminController.php              # Panel admin lengkap
│   ├── Models/
│   │   ├── User, Profile, InsurancePolicy, InsurancePackage
│   │   ├── Claim, Transaction, Hospital, Doctor
│   │   ├── HospitalRegistration, DoctorConsultation, ConsultationMessage
│   │   ├── HealthService, ServiceRegistration, Reminder, Feedback
│   │   ├── Promotion, Announcement
│   │   ├── Referral, DiscountCoupon, PromoCode, PromoCodeUsage
│   │   └── ...
│   └── Services/
│       ├── PromoCodeService.php             # Logika validasi kode promo
│       └── InsuranceBillingService.php      # Logika jatuh tempo, masa tenggang, dan billing premi
├── app/Console/Commands/
│   └── CheckInsurancePremiums.php          # Command pengecekan tagihan premi otomatis
│
├── resources/js/components/
│   ├── App.jsx, Dashboard.jsx
│   ├── Login.jsx, Register.jsx, TermAgreement.jsx
│   ├── Profile.jsx, Asuransi.jsx, Klaim.jsx, Monitor.jsx
│   ├── DaftarRS.jsx, PendaftaranRS.jsx, PendaftaranLayanan.jsx
│   ├── Konsultasi.jsx, HealthService.jsx, Riwayat.jsx
│   ├── KalenderPengingat.jsx, ReminderPopup.jsx, Notifikasi.jsx
│   ├── ChatBot.jsx, Feedback.jsx, TentangKami.jsx, SupportPage.jsx
│   ├── PromoPage.jsx, BannerCarousel.jsx, UserTestimonials.jsx
│   ├── PromoCodeInput.jsx                     # Komponen input kode promo
│   ├── AccessibilityPanel.jsx, useAccessibility.js
│   └── admin/
│       ├── AdminDashboard.jsx, AdminLogin.jsx
│       └── sections/
│           ├── AdminPromotions.jsx, AdminAnnouncements.jsx
│           ├── AdminPromoCodes.jsx, AdminFeedbacks.jsx
│           └── ... (users, claims, hospitals, dll.)
│
├── database/migrations/                     # 20+ migrasi termasuk promo & referral
├── public/
│   ├── promotions/                          # Upload banner promo
│   ├── announcements/                       # Upload gambar informasi
│   └── profiles/
│
└── routes/api.php
```

---

## 🗄️ Database Schema

### Tabel Utama

| Tabel | Deskripsi | Kolom Penting |
|-------|-----------|---------------|
| `users` | Data pengguna | `name`, `email`, `password`, `role`, `referral_code` |
| `profiles` | Profil lengkap | `full_name`, `birth_info`, `address`, `identity_card_path`, `digital_signature_path`, `profile_picture` |
| `insurance_policies` | Polis asuransi | `policy_number`, `premium_amount`, `coverage_limit`, `status`, `payment_status`, `billing_cycle`, `next_payment_due_date`, `grace_period_days`, `last_payment_at`, `promo_code`, `discount_amount` |
| `insurance_packages` | Paket tersedia | `name`, `type`, `premium_amount`, `coverage_limit`, `is_active` |
| `claims` | Pengajuan klaim | `claim_amount`, `description`, `document_path`, `status` |
| `transactions` | Transaksi keuangan | `transaction_type`, `amount`, `transaction_date`, `status` |
| `hospitals` | Data rumah sakit | `name`, `address`, `city`, `latitude`, `longitude`, `is_partner` |
| `doctors` | Data dokter | `hospital_id`, `name`, `specialist`, `availability` |
| `hospital_registrations` | Pendaftaran RS | `hospital_name`, `doctor_name`, `schedule_date`, `queue_number`, `barcode_data` |
| `doctor_consultations` | Konsultasi dokter | `doctor_name`, `specialist_type`, `status`, `promo_code`, `discount_amount` |
| `consultation_messages` | Pesan konsultasi | `consultation_id`, `sender`, `message` |
| `health_services` | Layanan kesehatan | `name`, `type`, `description`, `price`, `duration_minutes` |
| `service_registrations` | Pendaftaran layanan | `health_service_id`, `schedule_date`, `queue_number`, `promo_code`, `discount_amount` |
| `feedbacks` | Masukan pengguna | `category`, `content`, `rating`, `is_featured` |
| `reminders` | Kalender pengingat | `title`, `reminder_date`, `reminder_time`, `category`, `repeat`, `is_done` |
| `promotions` | Banner promo | `title`, `image_path`, `discount_percent`, `required_referrals`, `is_active`, `sort_order` |
| `announcements` | Informasi/pengumuman | `title`, `image_path`, `is_active`, `sort_order` |
| `referrals` | Relasi referral | `referrer_id`, `referred_id`, `referral_code` |
| `discount_coupons` | Kupon dari referral | `user_id`, `code`, `discount_percent`, `is_used` |
| `promo_codes` | Kode promo admin | `code`, `discount_percent`, `features`, `max_uses`, `expires_at` |
| `promo_code_usages` | Riwayat pemakaian kode | `promo_code_id`, `user_id`, `feature` |

### Relasi Utama

```
users ──< insurance_policies ──< claims
users ──< transactions
users ──< hospital_registrations >── hospitals
users ──< doctor_consultations ──< consultation_messages
users ──< service_registrations >── health_services
users ──< feedbacks
users ──< reminders
users ── profiles
users ──< referrals (sebagai referrer / referred)
users ──< discount_coupons
users ──< promo_code_usages >── promo_codes
hospitals ──< doctors
```

---

## 🚀 Instalasi & Setup

### Prasyarat

- PHP >= 8.3
- Composer
- Node.js >= 18.x & npm
- MySQL >= 8.0
- Git

### 1. Clone Repository

```bash
git clone https://github.com/Raihanhidayah12/Mefasefa.git
cd Mefasefa
```

### 2. Setup Backend (Laravel)

```bash
composer install
cp .env.example .env
php artisan key:generate

# Konfigurasi database di .env
# DB_DATABASE=mefasafe
# DB_USERNAME=root
# DB_PASSWORD=

php artisan migrate --seed
php artisan serve
# → http://127.0.0.1:8000
```

### Scheduler Premi Berkala

Fitur premi berkala memakai Laravel Scheduler untuk mengecek polis yang sudah jatuh tempo setiap hari jam 08:00.

```bash
# Jalankan manual untuk testing
php artisan insurance:check-premiums

# Production: pastikan scheduler Laravel berjalan
php artisan schedule:work
```

Alur otomatis:

1. Polis baru menyimpan `billing_cycle`, `next_payment_due_date`, dan `grace_period_days`.
2. Saat jatuh tempo, `payment_status` berubah menjadi `pending`.
3. User upload bukti pembayaran premi lanjutan.
4. Admin verifikasi pembayaran.
5. Sistem mencatat transaksi `premi_masuk`, mengaktifkan polis, dan memajukan jatuh tempo berikutnya.
6. Jika melewati masa tenggang, polis berubah menjadi `inactive`.

### 3. Setup Frontend (React + Vite)

```bash
npm install
npm run dev
# → http://localhost:5173

# Production build
npm run build
```

### 4. Konfigurasi Gemini AI (Opsional)

```env
GEMINI_API_KEY=your_api_key_here
```

> Tanpa API key, chatbot tetap berfungsi menggunakan fallback responses bawaan.

### 5. Akses Aplikasi

| Peran | URL | Catatan |
|-------|-----|---------|
| Pengguna | `/` → Login | Registrasi akun baru tersedia |
| Admin | `/admin` | Login dengan akun role `admin` |

---

## 🔌 API Endpoints

### Base URL

```
http://127.0.0.1:8000/api
```

### Autentikasi (Publik)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `POST` | `/register` | Registrasi pengguna baru |
| `POST` | `/login` | Login dan dapatkan token |

### Endpoints Utama (`/api/v1/`) — *Butuh Auth Token*

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/home-dashboard` | Data dashboard utama |
| `GET` | `/banners/active` | Banner carousel (promo + informasi) |
| `GET` | `/promotions/active` | Promo aktif |
| `GET` | `/announcements/active` | Informasi aktif |
| `GET` | `/referrals/me` | Kode referral & status program |
| `POST` | `/referrals/apply` | Terapkan kode referral teman |
| `POST` | `/promo-codes/validate` | Validasi kode promo pembayaran |
| `POST` | `/discount-coupons/validate` | Validasi kupon referral |
| `GET` | `/feedbacks/featured` | Testimonial untuk homepage |
| `GET` | `/my-policies` | Polis asuransi milik user |
| `GET` | `/insurance-packages` | Daftar paket asuransi |
| `POST` | `/insurance-policies/{id}/premium-payment` | Upload bukti pembayaran premi lanjutan |
| `GET/POST` | `/claims` | Daftar & ajukan klaim |
| `GET/POST` | `/transactions` | Riwayat & tambah transaksi |
| `GET` | `/hospitals` | Daftar rumah sakit |
| `GET` | `/hospitals/{id}/doctors` | Dokter di RS tertentu |
| `GET/POST` | `/hospital-registrations` | Pendaftaran RS online |
| `GET` | `/doctors` | Semua dokter |
| `GET/POST` | `/doctor-consultations` | Konsultasi dokter |
| `GET/POST` | `/doctor-consultations/{id}/messages` | Chat konsultasi |
| `GET` | `/health-services` | Layanan kesehatan |
| `GET/POST` | `/service-registrations` | Pendaftaran layanan |
| `GET` | `/monitor/saldo-summary` | Ringkasan saldo polis |
| `GET` | `/monitor/claims-history` | Riwayat klaim untuk grafik |
| `GET` | `/monitor/saldo-chart` | Data chart saldo |
| `GET` | `/riwayat` | Riwayat lengkap user |
| `GET` | `/notifications` | Semua notifikasi |
| `GET` | `/notifications/summary` | Ringkasan belum dibaca |
| `GET/POST/PUT/DELETE` | `/reminders` | CRUD pengingat |
| `GET` | `/reminders/today` | Pengingat hari ini |
| `GET` | `/reminders/upcoming` | Pengingat 7 hari ke depan |
| `GET/POST` | `/feedbacks` | Lihat & kirim feedback |
| `POST` | `/chatbot/chat` | Chat MefaBot |
| `GET` | `/chatbot/quick-replies` | Saran pertanyaan chatbot |
| `GET/PUT` | `/users/{id}` | Profil user & update |

### Admin (`/api/v1/admin/`) — *Butuh Auth Token Admin*

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/stats` | Statistik dashboard admin |
| `GET/PUT/DELETE` | `/users` | Kelola pengguna |
| `GET/PUT` | `/claims` | Kelola klaim |
| `GET/PUT` | `/policies` | Kelola polis |
| `GET` | `/transactions` | Daftar transaksi |
| `GET/POST/PUT/DELETE` | `/hospitals` | CRUD rumah sakit |
| `GET/POST/PUT/DELETE` | `/doctors` | CRUD dokter |
| `GET/PUT` | `/consultations` | Kelola konsultasi |
| `PUT` | `/feedbacks/{id}/featured` | Tampilkan/sembunyikan testimonial |
| `GET/POST/PUT/DELETE` | `/packages` | CRUD paket asuransi |
| `GET/POST/PUT/DELETE` | `/promotions` | CRUD promo banner |
| `GET/POST/PUT/DELETE` | `/announcements` | CRUD informasi/pengumuman |
| `GET/POST/PUT/DELETE` | `/promo-codes` | CRUD kode promo pembayaran |

---

## 📝 Changelog

### v2.3.0 · Premi Berkala & Auto Reminder (Juni 2026)
- ✅ **Billing premi berkala** — dukungan siklus bulanan/tahunan
- ✅ **Jatuh tempo premi berikutnya** — tersimpan di polis dan tampil di halaman Asuransi
- ✅ **Masa tenggang** — default 30 hari, dapat dikelola dari backend/admin
- ✅ **Auto pending/inactive** — command `insurance:check-premiums` menandai tagihan jatuh tempo dan polis lewat masa tenggang
- ✅ **Upload bukti premi lanjutan** — user dapat mengirim bukti pembayaran dari detail polis
- ✅ **Verifikasi admin** — admin melihat siklus, jatuh tempo, bukti bayar, lalu memverifikasi pembayaran
- ✅ **Notifikasi premi** — notifikasi segera jatuh tempo, menunggu verifikasi, dan lewat masa tenggang
- ✅ **Backfill data lama** — polis existing ikut mendapat jadwal tagihan awal

### v2.2.0 · Promo, Admin & UX (Mei 2026)
- ✅ **Program Referral** — kode unik, apply kode teman, kupon diskon otomatis
- ✅ **Banner Carousel** — gabungan promo + informasi di dashboard (Halodoc-style)
- ✅ **Kode Promo Pembayaran** — admin CRUD, validasi per fitur, input di checkout
- ✅ **Admin: Promo, Informasi, Kode Promo** — upload gambar, atur diskon & syarat
- ✅ **Testimonial Homepage** — feedback `is_featured` disetujui admin
- ✅ **Halaman Promo** (`/promo`) — referral, carousel, info program
- ✅ **Tentang Kami** — diperbarui dengan fitur, tim, dan tech stack terkini
- ✅ **Footer fungsional** — Peta Situs, Aksesibilitas, Kebijakan Cookie
- ✅ **Paket asuransi** — status aktif/nonaktif dikelola admin

### v2.1.0 · Penyempurnaan (Mei 2026)
- ✅ **Pendaftaran Layanan Kesehatan** — modul baru (MCU, Lab, Fisioterapi, dll.)
- ✅ **Monitor Asuransi** — grafik statistik dengan Recharts
- ✅ **Avatar Inisial** — foto dokter & profil diganti inisial nama jika null
- ✅ **Modal Portal** — modal konsultasi tampil di atas navbar & footer
- ✅ **20 Data RS** di-seed untuk testing
- ✅ Data layanan kesehatan dinamis dari database

### v2.0.0 · UAS (Mei 2026)
- ✅ Full-stack implementation (Laravel 13 + React 19)
- ✅ Autentikasi dengan Laravel Sanctum
- ✅ Dashboard dengan data real-time
- ✅ Manajemen polis, klaim, dan transaksi
- ✅ Konsultasi dokter (chat)
- ✅ Daftar rumah sakit dengan peta Leaflet
- ✅ Pendaftaran RS online dengan barcode
- ✅ Riwayat transaksi lengkap
- ✅ **Kalender Pengingat** dengan popup notifikasi otomatis
- ✅ Sistem notifikasi terintegrasi
- ✅ MefaBot — chatbot AI berbasis Gemini
- ✅ Health Tracking
- ✅ Feedback & rating sistem
- ✅ Manajemen profil dengan foto dan tanda tangan digital
- ✅ Halaman Tentang Kami & Pusat Bantuan
- ✅ Admin: CRUD user & manajemen konsultasi

### v1.0.0 · UTS (Mei 2026)
- ✅ Database design & normalisasi
- ✅ Use case & activity diagrams
- ✅ UI/UX wireframes & prototype Figma
- ✅ User persona & journey mapping

---

## 📄 License

Proyek ini dilisensikan di bawah [MIT License](LICENSE).

---

<div align="center">
  <strong>Made with ❤️ by Kelompok 4 · MefaSafe</strong><br>
  <sub>D3 Teknologi Informasi · Universitas Brawijaya · 2025/2026</sub>
</div>

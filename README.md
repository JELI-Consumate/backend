<p align="center">
  <img src="public/logo/logo-aplikasi-consumate-id.png" alt="Consumate Logo" width="180">
</p>

<h1 align="center">Consumate Backend API</h1>

<p align="center">
  REST API untuk aplikasi edukasi dan pemberdayaan konsumen Consumate, dibangun di atas Laravel 13.
</p>

## Daftar Isi

- [Tentang Proyek](#tentang-proyek)
- [Tech Stack](#tech-stack)
- [Struktur Modul](#struktur-modul)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Instalasi](#instalasi)
- [Konfigurasi Environment](#konfigurasi-environment)
- [Menjalankan Aplikasi](#menjalankan-aplikasi)
- [Dokumentasi API](#dokumentasi-api)
- [Panel Admin](#panel-admin)
- [Testing](#testing)
- [Code Style](#code-style)
- [Struktur Direktori](#struktur-direktori)
- [Lisensi](#lisensi)

## Tentang Proyek

Consumate adalah platform edukasi digital yang membantu masyarakat memahami hak dan kewajibannya sebagai konsumen di berbagai sektor (jasa keuangan, e-commerce, kesehatan, dan lainnya). Repositori ini adalah backend API yang menangani:

- Autentikasi pengguna (register, login, verifikasi email dengan OTP, login Google, reset password)
- Konten pembelajaran berjenjang (sector, journey, module, module page)
- Kuis (pretest dan posttest) dengan penilaian otomatis
- Simulasi interaktif bergaya latihan bertahap (matching dan ordering)
- Jurnal refleksi (reflection entry) per pengguna
- Pelacakan progres belajar (progress tracking) per sector dan journey
- Sistem badge dan gamifikasi
- Indeks pemberdayaan konsumen (empowerment index)
- Notifikasi push melalui Firebase Cloud Messaging (FCM)
- Survei eksternal (Google Form) sebagai pelengkap kuis in-app

## Tech Stack

| Komponen | Teknologi |
|---|---|
| Framework | Laravel 13 (PHP 8.3+) |
| Autentikasi API | Laravel Sanctum |
| Login Sosial | Laravel Socialite (Google) |
| Panel Admin | Filament 5 |
| Dokumentasi API | L5 Swagger (OpenAPI) |
| Notifikasi Push | Firebase Cloud Messaging (Kreait Firebase, laravel-notification-channels/fcm) |
| Storage | Local / Cloudflare R2 (S3-compatible, via Flysystem) |
| Testing | PHPUnit |
| Code Style | Laravel Pint |

## Struktur Modul

Alur belajar mengikuti hierarki berikut:

```
Sector -> Journey -> Module -> Module Page
```

Setiap sector memiliki pretest dan posttest, serta dapat memiliki survei eksternal terpisah. Progres pengguna dilacak per module page, kemudian diagregasi ke tingkat journey dan sector.

## Persyaratan Sistem

- PHP 8.3 atau lebih tinggi
- Composer 2.x
- Database (SQLite untuk lokal, MySQL/PostgreSQL untuk staging/produksi)
- Node.js dan npm (untuk build asset Filament)
- Ekstensi PHP yang dibutuhkan Laravel 13 (mbstring, pdo, openssl, tokenizer, xml, ctype, json, bcmath)

## Instalasi

1. Clone repositori dan masuk ke direktori proyek.

   ```bash
   git clone <url-repositori>
   cd backend
   ```

2. Install dependency PHP dan Node.

   ```bash
   composer install
   npm install
   ```

3. Salin file environment dan generate application key.

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Siapkan database (default menggunakan SQLite).

   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   ```

5. Buat symbolic link storage agar file upload bisa diakses publik.

   ```bash
   php artisan storage:link
   ```

6. Build asset frontend (dibutuhkan oleh panel Filament).

   ```bash
   npm run build
   ```

## Konfigurasi Environment

Variabel environment penting yang perlu disesuaikan di `.env`:

| Variabel | Keterangan |
|---|---|
| `APP_URL` | URL dasar aplikasi, dipakai untuk generate link (verifikasi email, reset password, dsb) |
| `DB_CONNECTION`, `DB_DATABASE` | Koneksi database |
| `SESSION_DRIVER`, `QUEUE_CONNECTION`, `CACHE_STORE` | Driver session, queue, dan cache |
| `FILESYSTEM_DISK` | Disk penyimpanan file (`local` atau `r2`) |
| `FILAMENT_FILESYSTEM_DISK` | Disk untuk upload media di panel admin |
| `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY` | Kredensial API token Cloudflare R2 |
| `R2_BUCKET` | Nama bucket R2 |
| `R2_ACCOUNT_ID` | Account ID Cloudflare, dipakai membentuk endpoint S3-compatible |
| `R2_PUBLIC_URL` | URL publik bucket (custom domain atau r2.dev) untuk akses file |
| `L5_SWAGGER_GENERATE_ALWAYS` | Regenerasi dokumentasi OpenAPI otomatis saat request |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | Kredensial OAuth untuk login Google |
| Kredensial Firebase (lihat `config/firebase.php`) | Diperlukan untuk mengirim notifikasi push FCM |

Jangan pernah commit file `.env` ke repositori. File ini berisi kredensial dan harus tetap berada di `.gitignore`.

## Menjalankan Aplikasi

Untuk development, gunakan skrip `dev` yang menjalankan server, queue listener, dan log viewer sekaligus.

```bash
composer run dev
```

Atau jalankan server secara manual.

```bash
php artisan serve
```

Aplikasi akan berjalan di `http://localhost:8000` (menyesuaikan `APP_URL`).

## Dokumentasi API

Dokumentasi API dibangkitkan otomatis menggunakan L5 Swagger (OpenAPI) dari anotasi di `app/OpenApi` dan controller.

Generate ulang dokumentasi (jika `L5_SWAGGER_GENERATE_ALWAYS=false`):

```bash
php artisan l5-swagger:generate
```

Akses dokumentasi interaktif di:

```
http://localhost:8000/api/docs
```

Semua endpoint API berada di bawah prefix `/api/v1`, contoh: `/api/v1/auth/login`, `/api/v1/sectors`, `/api/v1/quizzes/{id}`.

## Panel Admin

Panel admin dibangun dengan Filament dan dapat diakses melalui:

```
http://localhost:8000/admin
```

Buat akun admin dengan perintah:

```bash
php artisan make:filament-user
```

## Testing

Test suite menggunakan PHPUnit dan mencakup Feature test serta Unit test.

```bash
composer test
```

atau langsung:

```bash
php artisan test
```

## Code Style

Proyek menggunakan Laravel Pint untuk menjaga konsistensi gaya penulisan kode.

```bash
./vendor/bin/pint
```

Jalankan sebelum membuat commit untuk memastikan kode sesuai standar proyek.

## Struktur Direktori

Ringkasan direktori penting di dalam `app/`:

```
app/
├── Console/
│   └── Commands/          Perintah artisan kustom
├── Data/                  Data transfer object (DTO)
├── Enums/                 Enum domain aplikasi
├── Events/                Event Laravel
├── Exceptions/            Exception handler kustom
├── Filament/
│   ├── Resources/         Resource CRUD panel admin
│   ├── Pages/             Halaman kustom panel admin
│   └── Exports/           Konfigurasi export data
├── Http/
│   ├── Controllers/Api/V1/  Controller REST API
│   ├── Requests/          Form request untuk validasi input
│   ├── Resources/         API resource untuk transformasi response
│   └── Middleware/        Middleware HTTP kustom
├── Listeners/             Event listener
├── Models/                Eloquent model
├── Notifications/         Notifikasi email dan push (FCM)
├── Observers/             Model observer
├── OpenApi/               Anotasi dokumentasi Swagger/OpenAPI
├── Policies/              Otorisasi akses resource
├── Providers/             Service provider
├── Services/              Logika bisnis, dikelompokkan per domain
│   ├── Auth/
│   ├── Content/
│   ├── Learning/
│   ├── Quiz/
│   ├── Simulation/
│   ├── Reflection/
│   ├── Progress/
│   ├── Gamification/
│   ├── Analytics/
│   └── Notification/
└── Support/               Helper dan utilitas pendukung
```

## Lisensi

Proyek ini dikembangkan untuk keperluan internal dan akademik. Hubungi pemilik repositori untuk informasi lisensi dan penggunaan lebih lanjut.

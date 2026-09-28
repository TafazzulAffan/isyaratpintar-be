# 🤟 IsyaratPintar — Backend API

Backend API untuk platform IsyaratPintar (Deaf LMS), dibangun menggunakan **Laravel 10** dengan **PostgreSQL**.

---

## 📋 Daftar Isi

- [Prasyarat](#-prasyarat)
- [Struktur Proyek](#-struktur-proyek)
- [Instalasi & Setup](#-instalasi--setup)
- [Menjalankan Server](#-menjalankan-server)
- [API Documentation](#-api-documentation)
- [Testing](#-testing)
- [Troubleshooting](#-troubleshooting)

---

## 🔧 Prasyarat

| Software | Versi Minimum | Cara Cek |
|----------|--------------|----------|
| **PHP** | 8.1+ | `php -v` |
| **Composer** | 2.x | `composer -V` |
| **PostgreSQL** | 14+ | `psql --version` |
| **Git** | 2.x | `git --version` |

> [!TIP]
> Untuk Windows, Anda bisa menggunakan [Laragon](https://laragon.org/) yang sudah menyertakan PHP, Composer, dan database server.

---

## 🏗 Struktur Proyek

```
isyaratpintar-be/
├── app/
│   ├── Http/Controllers/        ← Controller API
│   ├── Models/                  ← Eloquent Models
│   ├── Services/                ← Business logic layer
│   └── Console/Commands/        ← Artisan commands
├── database/
│   ├── migrations/              ← Database schema
│   └── seeders/                 ← Data seeder
├── routes/
│   └── api/                     ← API route files
│       ├── auth.php             ← Autentikasi
│       ├── learning.php         ← Materi & pelajaran
│       ├── assessments.php      ← Ujian & asesmen
│       ├── pbl.php              ← Problem-Based Learning
│       ├── tracking.php         ← Tracking aktivitas siswa
│       ├── spk.php              ← SPK WASPAS
│       ├── kelas.php            ← Manajemen kelas
│       ├── users.php            ← Manajemen pengguna
│       └── file.php             ← Upload & manajemen file
├── tests/                       ← Unit & Feature tests
├── storage/                     ← File uploads & logs
├── POSTMAN_COLLECTION.json      ← Koleksi Postman
└── .env.example                 ← Template environment
```

---

## 🚀 Instalasi & Setup

### 1. Clone Repository

```bash
git clone <url-repo-backend> isyaratpintar-be
cd isyaratpintar-be
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Konfigurasi Environment

Salin file `.env.example` menjadi `.env`:

```bash
cp .env.example .env
```

> Di Windows (Command Prompt):
> ```cmd
> copy .env.example .env
> ```

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Konfigurasi Database

Buka file `.env` dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=isyaratpintar
DB_USERNAME=postgres
DB_PASSWORD=password_anda
```

> [!IMPORTANT]
> Pastikan Anda sudah membuat database `isyaratpintar` di PostgreSQL sebelum melanjutkan.

Buat database melalui **psql**:

```sql
CREATE DATABASE isyaratpintar;
```

### 6. Konfigurasi CORS & Sanctum

Tambahkan konfigurasi berikut di `.env` agar frontend dapat terhubung:

```env
# Frontend Configuration (Next.js)
FRONTEND_URL=http://localhost:3000
SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:8000,127.0.0.1:3000
SESSION_DOMAIN=localhost

# CORS Configuration
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:8000,http://127.0.0.1:3000,http://127.0.0.1:8000
```

### 7. Jalankan Migrasi Database

```bash
php artisan migrate
```

### 8. Jalankan Seeder (Opsional)

Untuk mengisi data awal:

```bash
php artisan db:seed
```

Seeder yang tersedia:

| Seeder | Deskripsi |
|--------|-----------|
| `LessonSeeder` | Data pelajaran |
| `AssessmentSeeder` | Data ujian/asesmen |
| `AssessmentResultSeeder` | Hasil ujian |
| `SPKDummyDataSeeder` | Data dummy SPK WASPAS |

### 9. Buat Symbolic Link untuk Storage

```bash
php artisan storage:link
```

---

## ▶ Menjalankan Server

```bash
php artisan serve
```

Server akan berjalan di **http://localhost:8000**

> [!NOTE]
> Jika port 8000 sudah digunakan, Anda bisa mengubahnya:
> ```bash
> php artisan serve --port=8001
> ```
> Jangan lupa update `NEXT_PUBLIC_API_URL` di sisi frontend.

---

## 📖 API Documentation

Dokumentasi API interaktif tersedia melalui:

| Dokumentasi | URL |
|-------------|-----|
| Swagger UI | `http://localhost:8000/api/documentation` |
| Scramble Docs | `http://localhost:8000/docs/api` |

### Modul API

| Modul | Endpoint Prefix | Deskripsi |
|-------|----------------|-----------|
| Auth | `/api/auth/*` | Login, register, logout |
| Learning | `/api/learning/*` | Materi & pelajaran |
| Assessments | `/api/assessments/*` | Ujian, soal & jawaban |
| PBL | `/api/pbl/*` | Problem-Based Learning |
| Tracking | `/api/tracking/*` | Tracking aktivitas siswa |
| SPK | `/api/spk/*` | Sistem Pendukung Keputusan (WASPAS) |
| Kelas | `/api/kelas/*` | Manajemen kelas |
| Users | `/api/users/*` | Manajemen pengguna |
| File | `/api/file/*` | Upload & manajemen file |

> [!TIP]
> Import file `POSTMAN_COLLECTION.json` ke Postman untuk testing API secara langsung.

---

## 🧪 Testing

```bash
# Jalankan semua test
php artisan test

# Unit test saja
php artisan test --testsuite=Unit

# Feature test saja
php artisan test --testsuite=Feature

# Dengan coverage report
php artisan test --coverage
```

---

## ❗ Troubleshooting

| Masalah | Solusi |
|---------|--------|
| `SQLSTATE[08006] Connection refused` | Pastikan PostgreSQL sudah berjalan dan kredensial di `.env` benar |
| `Class not found` | Jalankan `composer dump-autoload` |
| `Permission denied (storage)` | Jalankan `chmod -R 775 storage bootstrap/cache` (Linux/Mac) |
| `Key not generated` | Jalankan `php artisan key:generate` |
| Port 8000 sudah digunakan | Gunakan `php artisan serve --port=8001` |

### Membersihkan Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## 🔗 Repository Terkait

| Komponen | Repository |
|----------|------------|
| **Frontend** (Next.js) | [isyaratpintar-fe](https://github.com/TafazzulAffan/isyaratpintar-fe) |

---

<p align="center">
  Dibuat dengan ❤️ untuk komunitas tunarungu Indonesia
</p>

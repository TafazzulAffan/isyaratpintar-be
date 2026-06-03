# PBL (Problem-Based Learning) API Documentation

## 📋 Overview

Backend API untuk fitur Problem-Based Learning (PBL) menggunakan Laravel dengan dokumentasi Swagger/OpenAPI. Sistem ini memungkinkan admin mengelola PBL cases dan user mengerjakan serta submit jawaban.

---

## 🚀 Setup & Installation

### 1. Database Migration

Jalankan migrations untuk membuat semua tabel yang diperlukan:

```bash
php artisan migrate
```

Tabel yang dibuat:
- `mata_pelajarans` - Mata pelajaran (Subjects/Courses)
- `pbl_cases` - Kasus PBL utama
- `case_sections` - Bagian-bagian dalam case
- `case_section_items` - Item detail dalam section
- `user_case_progress` - Progress user mengerjakan case
- `case_submissions` - Submisi jawaban user

### 2. Seed Data (Optional)

Buat seed data untuk mata pelajarans:

```bash
php artisan tinker
```

Dalam tinker:
```php
App\Models\MataPelajaran::create(['level_number' => 1, 'name' => 'Mata Pelajaran 1']);
App\Models\MataPelajaran::create(['level_number' => 2, 'name' => 'Mata Pelajaran 2']);
App\Models\MataPelajaran::create(['level_number' => 3, 'name' => 'Mata Pelajaran 3']);
```

### 3. Generate Swagger Documentation

Generate dokumentasi Swagger setelah menambah controller baru:

```bash
php artisan l5-swagger:generate
```

Akses dokumentasi di: `http://localhost:8000/api/documentation`

### 4. Storage Configuration

Ensure storage symlink is created untuk public file serving:

```bash
php artisan storage:link
```

---

## 📚 API Structure

### Base URL
```
http://localhost:8000/api
```

### Authentication
Semua endpoint memerlukan **Bearer Token** dari Laravel Sanctum:

```
Authorization: Bearer {token}
```

---

## 🔐 Admin Endpoints

### 1. PBL Cases Management

#### Create PBL Case
```http
POST /api/admin/pbl-cases
Content-Type: application/json
Authorization: Bearer {token}

{
  "case_number": 1,
  "title": "System Login Bermasalah",
  "level_id": 1,
  "description": "Anda diminta untuk menyelesaikan masalah...",
  "image_url": "/storage/pbl/case1.jpg",
  "time_limit": 120,
  "start_date": "2024-01-15 09:00:00",
  "deadline": "2024-01-20 17:00:00"
}
```

**Response (201):**
```json
{
  "message": "PBL case created successfully",
  "data": {
    "id": 1,
    "slug": "system-login-bermasalah-a1b2c3d4",
    "case_number": 1,
    "title": "System Login Bermasalah",
    "description": "...",
    "image_url": "/storage/pbl/case1.jpg",
    "time_limit": 120,
    "start_date": "2024-01-15T09:00:00.000000Z",
    "deadline": "2024-01-20T17:00:00.000000Z",
    "level": {
      "id": 1,
      "name": "Beginner"
    },
    "sections": [],
    "created_at": "2024-01-10T10:30:00.000000Z",
    "updated_at": "2024-01-10T10:30:00.000000Z"
  }
}
```

#### Get All Cases
```http
GET /api/admin/pbl-cases?page=1
Authorization: Bearer {token}
```

#### Get Case by ID
```http
GET /api/admin/pbl-cases/{id}
Authorization: Bearer {token}
```

#### Update Case
```http
PUT /api/admin/pbl-cases/{id}
Content-Type: application/json
Authorization: Bearer {token}

{
  "title": "Updated Title",
  "deadline": "2024-01-25 17:00:00"
}
```

#### Delete Case
```http
DELETE /api/admin/pbl-cases/{id}
Authorization: Bearer {token}
```

---

### 2. Case Sections Management

#### Create Section
```http
POST /api/admin/pbl-cases/{caseId}/sections
Content-Type: application/json
Authorization: Bearer {token}

{
  "title": "Problem Description",
  "order": 1
}
```

**Response (201):**
```json
{
  "message": "Section created successfully",
  "data": {
    "id": 1,
    "title": "Problem Description",
    "order": 1,
    "items": []
  }
}
```

#### Get Sections
```http
GET /api/admin/pbl-cases/{caseId}/sections
Authorization: Bearer {token}
```

#### Update Section
```http
PUT /api/admin/sections/{sectionId}
Content-Type: application/json
Authorization: Bearer {token}

{
  "title": "Updated Title",
  "order": 2
}
```

#### Delete Section
```http
DELETE /api/admin/sections/{sectionId}
Authorization: Bearer {token}
```

---

### 3. Section Items Management

#### Create Section Item
```http
POST /api/admin/sections/{sectionId}/items
Content-Type: application/json
Authorization: Bearer {token}

{
  "type": "text",
  "content": "Deskripsi masalah yang detail...",
  "order": 1
}
```

Tipe item tersedia: `heading`, `text`, `list`, `image`

**Response (201):**
```json
{
  "message": "Section item created successfully",
  "data": {
    "id": 1,
    "type": "text",
    "content": "Deskripsi masalah...",
    "image_url": null,
    "order": 1
  }
}
```

#### Create Image Item
```http
POST /api/admin/sections/{sectionId}/items
Content-Type: application/json
Authorization: Bearer {token}

{
  "type": "image",
  "image_url": "/storage/pbl/problem-image.jpg",
  "order": 2
}
```

#### Update Item
```http
PUT /api/admin/items/{itemId}
Content-Type: application/json
Authorization: Bearer {token}

{
  "content": "Updated content",
  "order": 2
}
```

#### Delete Item
```http
DELETE /api/admin/items/{itemId}
Authorization: Bearer {token}
```

---

### 4. Image Upload

#### Upload Image
```http
POST /api/admin/upload-image
Content-Type: multipart/form-data
Authorization: Bearer {token}

Form Data:
- image: [binary file]
```

**Response (200):**
```json
{
  "message": "Image uploaded successfully",
  "url": "/storage/pbl/1704884400_a1b2c3d4.jpg",
  "filename": "1704884400_a1b2c3d4.jpg"
}
```

File disimpan di: `storage/app/public/pbl/`

---

## 👤 User Endpoints

### 1. Get All Cases (dengan Status)

```http
GET /api/pbl-cases?page=1&level_id=1
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "slug": "system-login-bermasalah-a1b2c3d4",
      "case_number": 1,
      "title": "System Login Bermasalah",
      "description": "Deskripsi case...",
      "image_url": "/storage/pbl/case1.jpg",
      "time_limit": 120,
      "start_date": "2024-01-15T09:00:00.000000Z",
      "deadline": "2024-01-20T17:00:00.000000Z",
      "status": "in-progress",
      "level": {
        "id": 1,
        "name": "Beginner"
      }
    }
  ],
  "links": {...},
  "meta": {...}
}
```

**Status Values:**
- `not-started`: Case belum mulai (before start_date)
- `in-progress`: Sedang mengerjakan (between start_date dan deadline, belum submit)
- `completed`: Sudah di-submit
- `late`: Melewati deadline tanpa submit

### 2. Get Case Detail (dengan Sections)

```http
GET /api/pbl-cases/{slug}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "id": 1,
  "slug": "system-login-bermasalah-a1b2c3d4",
  "case_number": 1,
  "title": "System Login Bermasalah",
  "description": "...",
  "image_url": "/storage/pbl/case1.jpg",
  "time_limit": 120,
  "start_date": "2024-01-15T09:00:00.000000Z",
  "deadline": "2024-01-20T17:00:00.000000Z",
  "status": "in-progress",
  "level": {
    "id": 1,
    "name": "Beginner"
  },
  "sections": [
    {
      "id": 1,
      "title": "Problem Description",
      "order": 1,
      "items": [
        {
          "id": 1,
          "type": "text",
          "content": "Deskripsi masalah...",
          "image_url": null,
          "order": 1
        },
        {
          "id": 2,
          "type": "image",
          "content": null,
          "image_url": "/storage/pbl/problem.jpg",
          "order": 2
        }
      ]
    }
  ],
  "created_at": "2024-01-10T10:30:00.000000Z",
  "updated_at": "2024-01-10T10:30:00.000000Z"
}
```

---

### 3. Submit Case Answer

```http
POST /api/pbl-submissions
Content-Type: application/json
Authorization: Bearer {token}

{
  "case_id": 1,
  "answer": "Solusi saya adalah dengan melakukan refactoring pada bagian authentication module. Saya menemukan bug pada validasi password yang tidak case-sensitive..."
}
```

**Response (201):**
```json
{
  "message": "Answer submitted successfully",
  "data": {
    "id": 1,
    "case_id": 1,
    "answer": "Solusi saya adalah...",
    "submitted_at": "2024-01-18T14:30:00.000000Z",
    "score": null,
    "feedback": null,
    "created_at": "2024-01-18T14:30:00.000000Z",
    "updated_at": "2024-01-18T14:30:00.000000Z"
  }
}
```

**Error (400) - Already Submitted:**
```json
{
  "message": "You have already submitted an answer for this case"
}
```

### 4. Get User's Submissions

```http
GET /api/pbl-submissions?page=1
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "case_id": 1,
      "answer": "Solusi saya...",
      "submitted_at": "2024-01-18T14:30:00.000000Z",
      "score": null,
      "feedback": null,
      "created_at": "2024-01-18T14:30:00.000000Z",
      "updated_at": "2024-01-18T14:30:00.000000Z"
    }
  ],
  "links": {...},
  "meta": {...}
}
```

### 5. Get Submission Detail

```http
GET /api/pbl-submissions/{submissionId}
Authorization: Bearer {token}
```

---

## 📊 Status Logic (Dinamis)

Status case dihitung secara **real-time** berdasarkan:

```php
// Pseudocode
if (user has submission) {
    status = "completed"
} else if (now < case.start_date) {
    status = "not-started"
} else if (now > case.deadline) {
    status = "late"
} else {
    status = "in-progress"
}
```

**Implementasi:** `App\Services\PblCaseStatusService`

---

## 🔄 Database Relasi

```
Level (1) ─── (M) PblCase
                    │
                    ├─ (M) CaseSection
                    │        └─ (M) CaseSectionItem
                    │
                    └─ (M) UserCaseProgress ─ User
                    
User (1) ──────── (M) CaseSubmission ─ (M) PblCase
```

---

## ✅ Validation Rules

### PBL Case
- `case_number`: required, integer, unique
- `title`: required, string, max 255
- `level_id`: required, exists in levels
- `description`: required, string
- `start_date`: required, after or equal now
- `deadline`: required, after start_date

### Case Section
- `title`: required, string, max 255
- `order`: optional, integer

### Section Item
- `type`: required, enum (heading, text, list, image)
- `content`: optional, string
- `image_url`: optional, URL format

### Case Submission
- `case_id`: required, exists in pbl_cases
- `answer`: required, string, min 10 characters

### Image Upload
- `image`: required, image, max 5MB, allowed formats: jpeg, png, jpg, gif

---

## 🔒 Authorization

- **Unauthenticated:** 401 Unauthorized
- **Authenticated (User):** Akses user endpoints
- **Admin Role:** Akses semua admin endpoints

---

## 📖 Swagger Documentation

Akses dokumentasi interaktif di:
```
http://localhost:8000/api/documentation
```

Features:
- ✅ Daftar semua endpoint
- ✅ Contoh request/response
- ✅ Coba langsung (Try it out)
- ✅ Parameter validation
- ✅ Authentication bearer token

---

## 🧪 Testing Examples

### cURL

**Create Case:**
```bash
curl -X POST http://localhost:8000/api/admin/pbl-cases \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "case_number": 1,
    "title": "Test Case",
    "level_id": 1,
    "description": "Test description",
    "start_date": "2024-01-15 09:00:00",
    "deadline": "2024-01-20 17:00:00"
  }'
```

**Submit Answer:**
```bash
curl -X POST http://localhost:8000/api/pbl-submissions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "case_id": 1,
    "answer": "My solution is..."
  }'
```

**Get Cases:**
```bash
curl -X GET http://localhost:8000/api/pbl-cases \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 🛠️ Key Files

- **Models:** `app/Models/PblCase.php`, `CaseSection.php`, `CaseSectionItem.php`, `UserCaseProgress.php`, `CaseSubmission.php`
- **Controllers:** `app/Http/Controllers/Api/Admin/`, `app/Http/Controllers/Api/User/`
- **Services:** `app/Services/PblCaseStatusService.php`
- **Requests:** `app/Http/Requests/`
- **Resources:** `app/Http/Resources/`
- **Routes:** `routes/api/pbl.php`
- **Migrations:** `database/migrations/2024_01_01_*.php`

---

## 🐛 Troubleshooting

### Swagger Documentation tidak tampil
```bash
php artisan l5-swagger:generate
php artisan cache:clear
```

### Image upload tidak bekerja
```bash
php artisan storage:link
chmod -R 775 storage/app/public
```

### Role middleware error
Pastikan user memiliki role 'admin' untuk akses admin endpoints:
```php
// Check in database
SELECT id, role FROM users WHERE id = 1;
```

---

## 📝 Notes

- Status case dihitung secara dinamis, tidak disimpan di database
- File upload tersimpan di `storage/app/public/pbl/`
- Semua timestamp menggunakan UTC
- API menggunakan pagination untuk list endpoints


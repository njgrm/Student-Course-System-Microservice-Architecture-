# Student Course System — Microservices Architecture

## Overview

This project implements a **Simple Student Course System** in two architectures side-by-side:

| Architecture | Stack | Location |
|---|---|---|
| **Monolith** | Node.js · Express 4 · In-memory store | `SAR2/` |
| **Microservices** | Laravel 12 · Livewire 4 · SQLite | `services/` |

The microservices version decomposes the monolith into **three independently deployable services** that communicate over HTTP REST APIs:

| Service | Responsibility | Port | Directory |
|---|---|---|---|
| **Student Service** | CRUD student records | `8001` | `services/student-service/` |
| **Course Service** | CRUD course catalog | `8002` | `services/course-service/` |
| **Enrollment Service** | Student ↔ Course enrollments | `8003` | `services/enrollment-service/` |

---

## Prerequisites

- **PHP 8.2+** with `sqlite3`, `mbstring`, `xml` extensions
- **Composer** (latest)
- **Node.js 20.19+** (for Vite asset bundling)
- **npm** (comes with Node.js)
- **Git**

---

## Quick Start

### 1. Clone & install

```bash
git clone https://github.com/njgrm/Student-Course-System-Microservice-Architecture-.git
cd Student-Course-System-Microservice-Architecture-
```

### 2. Install all dependencies (root + 3 services)

```bash
npm run install:services
```

This runs `composer install` and `npm install` inside each service.

### 3. Migrate & seed databases

```bash
npm run fresh:services
```

This runs `php artisan migrate:fresh --seed` in each service, creating SQLite databases with sample data.

### 4. Build frontend assets

```bash
npm run build
npm run build:services
```

This runs Vite in the root gateway app and all three services to generate CSS/JS bundles.

### 5. Serve all services (single command)

```bash
npm run serve:all
```

This uses **concurrently** to start all three microservices in one terminal with color-coded output:

- 🟣 **Student Service** → [http://localhost:8001](http://localhost:8001)
- 🟢 **Course Service** → [http://localhost:8002](http://localhost:8002)
- 🟡 **Enrollment Service** → [http://localhost:8003](http://localhost:8003)

> **Alternative:** Run `.\serve-all.ps1` to open each service in its own PowerShell window.

### 6. Start the gateway dashboard

In a separate terminal:

```bash
php artisan serve --port=8000
```

- 🌐 **Dashboard** → [http://localhost:8000](http://localhost:8000)

This is the **unified single-page dashboard** (like the SAR2 monolith UI) that shows all three sections — Students, Courses, and Enrollments — on one page. It calls the three microservice APIs via Axios with graceful degradation if any service is down.

---

## Running Individual Services

If you prefer manual control, open three terminals:

```bash
# Terminal 1
cd services/student-service && php artisan serve --port=8001

# Terminal 2
cd services/course-service && php artisan serve --port=8002

# Terminal 3
cd services/enrollment-service && php artisan serve --port=8003
```

---

## API Endpoints

Each service exposes a REST API under `/api`:

### Student Service (`:8001`)

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/students` | List all students |
| `POST` | `/api/students` | Create a student |
| `GET` | `/api/students/{id}` | Get a student |
| `PUT` | `/api/students/{id}` | Update a student |
| `DELETE` | `/api/students/{id}` | Delete a student (cascades enrollments) |

### Course Service (`:8002`)

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/courses` | List all courses |
| `POST` | `/api/courses` | Create a course |
| `GET` | `/api/courses/{id}` | Get a course |
| `PUT` | `/api/courses/{id}` | Update a course |
| `DELETE` | `/api/courses/{id}` | Delete a course (cascades enrollments) |

### Enrollment Service (`:8003`)

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/enrollments` | List all enrollments (enriched with names) |
| `POST` | `/api/enrollments` | Create an enrollment |
| `GET` | `/api/enrollments/{id}` | Get an enrollment |
| `DELETE` | `/api/enrollments/{id}` | Remove an enrollment |
| `DELETE` | `/api/enrollments/student/{studentId}` | Remove enrollments by student |
| `DELETE` | `/api/enrollments/course/{courseId}` | Remove enrollments by course |

---

## Running the Monolith (Reference)

```bash
cd SAR2
npm install
npm start
```

Opens at [http://localhost:3000](http://localhost:3000)

---

## Running Tests

Each service has its own PHPUnit test suite (34 tests total):

```bash
# All services
cd services/student-service && php artisan test
cd services/course-service && php artisan test
cd services/enrollment-service && php artisan test
```

---

## Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | PHP 8.2+, Laravel 12, Eloquent ORM |
| **Reactive UI** | Livewire 4 (class-based components) |
| **Templates** | Blade + Tailwind CSS 4 |
| **Assets** | Vite 7 |
| **Database** | SQLite (one per service) |
| **Gateway UI** | Blade + Axios + Tailwind CSS 4 (single-page dashboard) |
| **Inter-service** | Laravel HTTP Client (`Http` facade) |
| **Monolith** | Node.js, Express 4, vanilla JS |

---

## Lab 2 — Edge Case Testing & Error Handling

### What was added

All three services now return **consistent JSON error responses** for every failure mode:

```json
{
  "error": "ERROR_CODE",
  "message": "Human readable explanation"
}
```

### Supported HTTP Error Codes

| Code | Error Key | When It Triggers |
|------|-----------|-----------------|
| `400` | `VALIDATION_ERROR` | Missing or invalid input on POST/PUT |
| `404` | `NOT_FOUND` | Resource doesn't exist (student, course, enrollment) |
| `409` | `DUPLICATE_ENROLLMENT` | Same student + course enrollment already exists |
| `503` | `SERVICE_UNAVAILABLE` | Dependency service is stopped/unreachable |
| `504` | `GATEWAY_TIMEOUT` | Dependency service is too slow (>5s timeout) |

### How to run curl tests

1. Start all services (see Quick Start above)
2. Open a separate terminal for curl commands
3. Follow the commands in [`tests/curl-tests.md`](tests/curl-tests.md)
4. Save evidence: `curl -i ... > docs/evidence/filename.txt 2>&1`

### Testing dependency failures (503/504)

```bash
# Stop the student service, then test enrollment:
curl -i -X POST http://localhost:8003/api/enrollments ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{\"student_id\": 1, \"course_id\": 1}"
# Expected: 503 SERVICE_UNAVAILABLE

# Test timeout via the slow-test route (student service must be running):
curl -i --max-time 3 http://localhost:8001/api/students/slow-test -H "Accept: application/json"
# Expected: curl times out (proves the route is slow)
```

### Lab 2 file structure

```
tests/
  curl-tests.md              ← All curl commands with expected responses
docs/
  report.md                  ← Edge case explanations and reflections
  evidence/                  ← Saved curl output .txt files
    01-happy-create-student.txt
    02-happy-create-course.txt
    ...
```

### Key files changed for Lab 2

| File | Change |
|------|--------|
| `services/*/bootstrap/app.php` | Added exception renderers (400, 404 JSON format) |
| `services/enrollment-service/app/Http/Controllers/EnrollmentController.php` | Added `Http::timeout(5)`, `ConnectionException` catch for 503/504, standardized error format |
| `services/student-service/routes/api.php` | Added `/api/students/slow-test` route for timeout testing |

---

## Project Structure

```
├── resources/
│   ├── views/dashboard.blade.php   # Gateway single-page dashboard
│   └── js/dashboard.js             # Axios API controller for dashboard
├── services/
│   ├── student-service/            # Laravel app → :8001
│   ├── course-service/             # Laravel app → :8002
│   └── enrollment-service/         # Laravel app → :8003
├── SAR2/                           # Node.js monolith (reference)
├── tests/
│   └── curl-tests.md              # Lab 2 curl test commands
├── docs/
│   ├── report.md                  # Lab 2 edge case report
│   └── evidence/                  # Saved curl output files
├── serve-all.ps1                   # PowerShell multi-window launcher
├── package.json                    # Root scripts (serve:all, build:services, etc.)
└── CHANGELOG.md                    # Project changelog
```

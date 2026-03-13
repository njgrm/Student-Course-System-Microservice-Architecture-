# Lab 2 — Curl Test Commands

> All commands use `curl -i` to display HTTP status codes.
> Always include `-H "Accept: application/json"` so Laravel returns JSON (not HTML).
> Save each output to `docs/evidence/` as a `.txt` file for grading.

---

## How to Save Evidence

Append `> docs/evidence/<filename>.txt 2>&1` to any curl command to capture the full output (headers + body):

```bash
curl -i ... > docs/evidence/01-happy-create-student.txt 2>&1
```

---

## 1. Happy Path Tests (15 pts)

### 1a. Create a Student — `POST /api/students` → 201

```bash
curl -i -X POST http://localhost:8001/api/students ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{\"full_name\": \"John Doe\", \"email\": \"john@example.com\", \"age\": 21}"
```

**Expected:** `HTTP 201 Created` with `{"id": 1, "full_name": "John Doe", ...}`

### 1b. Create a Course — `POST /api/courses` → 201

```bash
curl -i -X POST http://localhost:8002/api/courses ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{\"name\": \"Web Development\", \"description\": \"Learn Laravel microservices\", \"credits\": 3}"
```

**Expected:** `HTTP 201 Created`

### 1c. Create an Enrollment — `POST /api/enrollments` → 201

```bash
curl -i -X POST http://localhost:8003/api/enrollments ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{\"student_id\": 1, \"course_id\": 1}"
```

**Expected:** `HTTP 201 Created` with enriched enrollment (includes student_name, course_name)

### 1d. List All Students — `GET /api/students` → 200

```bash
curl -i http://localhost:8001/api/students -H "Accept: application/json"
```

**Expected:** `HTTP 200 OK` with JSON array

### 1e. Get Single Student — `GET /api/students/1` → 200

```bash
curl -i http://localhost:8001/api/students/1 -H "Accept: application/json"
```

**Expected:** `HTTP 200 OK` with single student object

### 1f. Update a Student — `PUT /api/students/1` → 200

```bash
curl -i -X PUT http://localhost:8001/api/students/7 ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{\"full_name\": \"John Updated\"}"
```

**Expected:** `HTTP 200 OK` with updated student

### 1g. Delete a Student — `DELETE /api/students/1` → 200

```bash
curl -i -X DELETE http://localhost:8001/api/students/1 -H "Accept: application/json"
```

**Expected:** `HTTP 200 OK` with `{"message": "Student deleted successfully."}`

---

## 2. Validation Errors — 400 Bad Request (15 pts)

### 2a. Student — Missing All Fields

```bash
curl -i -X POST http://localhost:8001/api/students ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{}"
```

**Expected:** `HTTP 400` with `{"error": "400 VALIDATION_ERROR", "message": "..."}`

### 2b. Student — Invalid Email Format

```bash
curl -i -X POST http://localhost:8001/api/students ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{\"full_name\": \"Jane Doe\", \"email\": \"not-an-email\", \"age\": 20}"
```

**Expected:** `HTTP 400` with validation error on email field

### 2c. Student — Negative Age

```bash
curl -i -X POST http://localhost:8001/api/students ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{\"full_name\": \"Jane Doe\", \"email\": \"jane@test.com\", \"age\": -5}"
```

**Expected:** `HTTP 400` with validation error on age field

### 2d. Course — Missing Fields

```bash
curl -i -X POST http://localhost:8002/api/courses ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{}"
```

**Expected:** `HTTP 400` with validation errors for name, description, credits

### 2e. Course — Invalid Credits (zero)

```bash
curl -i -X POST http://localhost:8002/api/courses ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{\"name\": \"Test\", \"description\": \"Desc\", \"credits\": 0}"
```

**Expected:** `HTTP 400` with validation error on credits (min:1)

### 2f. Enrollment — Missing student_id

```bash
curl -i -X POST http://localhost:8003/api/enrollments ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{\"course_id\": 1}"
```

**Expected:** `HTTP 400` with validation error on student_id

### 2g. Enrollment — Missing All Fields

```bash
curl -i -X POST http://localhost:8003/api/enrollments ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{}"
```

**Expected:** `HTTP 400` with validation errors for student_id and course_id

---

## 3. Not Found — 404 (15 pts)

### 3a. Student Not Found

```bash
curl -i http://localhost:8001/api/students/9999 -H "Accept: application/json"
```

**Expected:** `HTTP 404` with `{"error": "404 NOT_FOUND", "message": "Student not found."}`

### 3b. Course Not Found

```bash
curl -i http://localhost:8002/api/courses/9999 -H "Accept: application/json"
```

**Expected:** `HTTP 404` with `{"error": "404 NOT_FOUND", "message": "Course not found."}`

### 3c. Enrollment Not Found

```bash
curl -i http://localhost:8003/api/enrollments/9999 -H "Accept: application/json"
```

**Expected:** `HTTP 404` with `{"error": "404 NOT_FOUND", "message": "Enrollment not found."}`

### 3d. Enroll Non-Existent Student

```bash
curl -i -X POST http://localhost:8003/api/enrollments ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{\"student_id\": 9999, \"course_id\": 1}"
```

**Expected:** `HTTP 404` with `{"error": "404 NOT_FOUND", "message": "Student not found in Student Service."}`

### 3e. Enroll in Non-Existent Course

```bash
curl -i -X POST http://localhost:8003/api/enrollments ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{\"student_id\": 1, \"course_id\": 9999}"
```

**Expected:** `HTTP 404` with `{"error": "404 NOT_FOUND", "message": "Course not found in Course Service."}`

---

## 4. Duplicate Handling — 409 Conflict (15 pts)

### 4a. First Enrollment (should succeed)

```bash
curl -i -X POST http://localhost:8003/api/enrollments ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{\"student_id\": 1, \"course_id\": 1}"
```

**Expected:** `HTTP 201 Created`

### 4b. Duplicate Enrollment (same student + course again)

```bash
curl -i -X POST http://localhost:8003/api/enrollments ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{\"student_id\": 1, \"course_id\": 1}"
```

**Expected:** `HTTP 409 Conflict` with `{"error": "409 DUPLICATE_ENROLLMENT", "message": "Student is already enrolled in this course."}`

---

## 5. Dependency Down — 503 Service Unavailable (20 pts)

> **Setup:** Stop the student service (kill the `php artisan serve --port=8001` process) before running these.

### 5a. Enrollment When Student Service is Down

```bash
curl -i -X POST http://localhost:8003/api/enrollments ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{\"student_id\": 1, \"course_id\": 1}"
```

**Expected:** `HTTP 503` with `{"error": "503 SERVICE_UNAVAILABLE", "message": "Student Service is unavailable."}`

> **Setup:** Restart student service, then stop the course service (kill `--port=8002`).

### 5b. Enrollment When Course Service is Down

```bash
curl -i -X POST http://localhost:8003/api/enrollments ^
  -H "Content-Type: application/json" ^
  -H "Accept: application/json" ^
  -d "{\"student_id\": 1, \"course_id\": 1}"
```

**Expected:** `HTTP 503` with `{"error": "503 SERVICE_UNAVAILABLE", "message": "Course Service is unavailable."}`

---

## 6. Timeout Handling — 504 Gateway Timeout (10 pts)

> **Setup:** Both the student service (port 8001) and enrollment service (port 8003) must be running.
> The student service has a `GET /api/students/slow-test` route that sleeps for 10 seconds.
> The enrollment service has a dedicated `GET /api/enrollments/timeout-test` route that calls it
> through a 5-second timeout, reliably producing a 504.

### 6a. Confirm Slow Route is Active (Student Service)

```bash
curl -i --max-time 3 http://localhost:8001/api/students/slow-test -H "Accept: application/json"
```

**Expected:** curl itself times out after 3 seconds, confirming the slow route is working.

### 6b. Trigger 504 via Enrollment Service Timeout Test Route

```bash
curl -i http://localhost:8003/api/enrollments/timeout-test -H "Accept: application/json"
```

**Expected:** `HTTP 504` with `{"error": "504 GATEWAY_TIMEOUT", "message": "Student Service timed out."}`

> The enrollment service calls the slow-test endpoint with a 5-second timeout. After 5 seconds the
> `ConnectionException` is caught and the 504 response is returned.

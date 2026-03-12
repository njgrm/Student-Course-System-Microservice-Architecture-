# Lab 2 — Edge Case Testing Report

## Student Course System — Microservices Error Handling

---

## 1. Overview

This report documents the implementation and testing of HTTP error handling across three independent Laravel microservices: Student Service (port 8001), Course Service (port 8002), and Enrollment Service (port 8003). Each service returns consistent JSON error responses following the format:

```json
{
  "error": "ERROR_CODE",
  "message": "Human readable explanation"
}
```

---

## 2. Implemented Edge Cases

### 2.1 — 400 Bad Request (Validation Errors)

**What it handles:** Missing or invalid input data on POST/PUT requests.

**Why it matters in a real system:**
Every API endpoint that accepts user input is an attack surface. Without proper validation, a user could submit an empty form, inject malicious data, or send fields with wrong types. In a distributed system this is especially critical because bad data that passes through one service could cascade into corrupted state across multiple databases. A payment system that doesn't validate amounts could process negative charges. A registration system that doesn't validate emails creates accounts that can never receive notifications.

**How it was implemented:**
Each service uses Laravel **Form Request** classes (e.g., `StoreStudentRequest`, `StoreCourseRequest`) that define validation rules. When validation fails, Laravel throws a `ValidationException`. We customized the exception handler in each service's `bootstrap/app.php` to catch this exception and convert it from Laravel's default 422 response into a standardized 400 response:

```php
$exceptions->render(function (ValidationException $e, $request) {
    if ($request->expectsJson() || $request->is('api/*')) {
        return response()->json([
            'error'   => 'VALIDATION_ERROR',
            'message' => $e->getMessage(),
            'details' => $e->errors(),
        ], 400);
    }
});
```

**Validation rules per service:**
- **Student:** `full_name` (required, string), `email` (required, valid email, unique), `age` (required, integer, min 1)
- **Course:** `name` (required, string), `description` (required, string), `credits` (required, integer, min 1)
- **Enrollment:** `student_id` (required, integer, min 1), `course_id` (required, integer, min 1)

---

### 2.2 — 404 Not Found

**What it handles:** Requests for resources that don't exist in the database.

**Why it matters in a real system:**
APIs must clearly distinguish between "this endpoint exists but the resource doesn't" (404) and "everything is fine" (200 with empty data). Without proper 404 handling, a client might assume a student exists because the API returned 200 with an empty object, then attempt to enroll that non-existent student — causing data integrity issues. In microservices specifically, the Enrollment Service needs to verify that a student and course actually exist in their respective services before creating an enrollment record. A clear 404 tells the calling service "stop — this entity doesn't exist."

**How it was implemented:**
Two layers handle 404s:

1. **Route Model Binding:** Laravel automatically resolves `{student}`, `{course}`, `{enrollment}` URL parameters to Eloquent models. When the ID doesn't exist, Laravel throws a `ModelNotFoundException`. We catch this in the exception handler:

```php
$exceptions->render(function (ModelNotFoundException $e, $request) {
    if ($request->expectsJson() || $request->is('api/*')) {
        $model = class_basename($e->getModel());
        return response()->json([
            'error'   => 'NOT_FOUND',
            'message' => "{$model} not found.",
        ], 404);
    }
});
```

2. **Inter-Service 404:** When the Enrollment Service calls the Student/Course Service and gets a failed response, it returns its own 404 with a descriptive message explaining which dependency returned the failure.

---

### 2.3 — 409 Conflict (Duplicate Enrollment)

**What it handles:** Attempting to enroll a student in a course they're already enrolled in.

**Why it matters in a real system:**
Duplicate records are a data integrity problem. In an e-commerce system, duplicate orders mean double charges. In a school system, duplicate enrollments could mean double-counted credits or billing errors. The 409 status code tells the client "your request is valid but conflicts with existing state" — which is semantically different from 400 (bad input) or 500 (server error). This distinction helps frontend applications show appropriate error messages: "You're already enrolled" versus "Something went wrong."

**How it was implemented:**
Before creating an enrollment, the controller queries the database for an existing record with the same student_id + course_id combination:

```php
$exists = Enrollment::where('student_id', $validated['student_id'])
    ->where('course_id', $validated['course_id'])
    ->exists();

if ($exists) {
    return response()->json([
        'error'   => 'DUPLICATE_ENROLLMENT',
        'message' => 'Student is already enrolled in this course.',
    ], 409);
}
```

---

### 2.4 — 503 Service Unavailable (Dependency Down)

**What it handles:** The Enrollment Service cannot reach the Student or Course Service because it's stopped/crashed.

**Why it matters in a real system:**
This is the defining challenge of microservices. In a monolith, if the enrollment module needs to check if a student exists, it's a function call — it either works or the whole app is down. In microservices, Service A depends on Service B over the network, and Service B might be down while Service A is perfectly healthy. Without proper 503 handling, the enrollment service would return a confusing 500 Internal Server Error (or worse, hang indefinitely). A clear 503 tells the client "I'm fine, but my dependency isn't — try again later."

**How it was implemented:**
Every HTTP call to another service is wrapped in a try/catch that specifically catches `ConnectionException` (thrown when the TCP connection is refused):

```php
try {
    $studentResponse = Http::timeout(5)->get(...);
} catch (ConnectionException $e) {
    return response()->json([
        'error'   => 'SERVICE_UNAVAILABLE',
        'message' => 'Student Service is unavailable.',
    ], 503);
}
```

---

### 2.5 — 504 Gateway Timeout (Dependency Too Slow)

**What it handles:** The Student or Course Service accepts the connection but responds too slowly.

**Why it matters in a real system:**
A service being "slow" is different from being "down." A slow database query, a memory leak causing GC pauses, or network congestion can all cause a service to accept connections but take 30+ seconds to respond. Without timeouts, the calling service's thread/process blocks indefinitely, and if enough requests pile up, the calling service itself becomes unresponsive — this is called **cascading failure**. The 504 status code tells the client "the service is reachable but didn't respond in time," which is important for retry logic (a 503 might mean "wait and retry," while a 504 might mean "the service is overloaded, back off more aggressively").

**How it was implemented:**
All inter-service HTTP calls use `Http::timeout(5)` to set a 5-second deadline. When the timeout expires, Laravel throws a `ConnectionException` whose message contains "timed out." We inspect the exception message to distinguish timeout from connection refused:

```php
catch (ConnectionException $e) {
    $code = str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'Timeout') ? 504 : 503;

    return response()->json([
        'error'   => $code === 504 ? 'GATEWAY_TIMEOUT' : 'SERVICE_UNAVAILABLE',
        'message' => $code === 504
            ? 'Student Service timed out.'
            : 'Student Service is unavailable.',
    ], $code);
}
```

To test this, a temporary `/api/students/slow-test` route was added to the Student Service that sleeps for 10 seconds — longer than the 5-second timeout.

---

## 3. Testing Methodology

All tests were performed using `curl -i` from the command line. The `-i` flag displays HTTP response headers (including the status code) alongside the response body.

Key headers used in every request:
- `-H "Content-Type: application/json"` — tells the server we're sending JSON
- `-H "Accept: application/json"` — tells Laravel we want JSON responses (prevents HTML error pages)

Evidence was saved by redirecting curl output to text files:
```bash
curl -i ... > docs/evidence/filename.txt 2>&1
```

The `2>&1` ensures both stdout (response) and stderr (curl progress info) are captured.

See `tests/curl-tests.md` for the complete list of curl commands used.

---

## 4. Evidence Files

| # | File | Edge Case | Expected Status |
|---|------|-----------|-----------------|
| 01 | `01-happy-create-student.txt` | Create student | 201 |
| 02 | `02-happy-create-course.txt` | Create course | 201 |
| 03 | `03-happy-create-enrollment.txt` | Create enrollment | 201 |
| 04 | `04-validation-student-missing-fields.txt` | Empty student body | 400 |
| 05 | `05-validation-student-invalid-email.txt` | Bad email format | 400 |
| 06 | `06-validation-course-missing-fields.txt` | Empty course body | 400 |
| 07 | `07-validation-enrollment-missing-fields.txt` | Empty enrollment body | 400 |
| 08 | `08-not-found-student.txt` | GET student 9999 | 404 |
| 09 | `09-not-found-course.txt` | GET course 9999 | 404 |
| 10 | `10-not-found-enrollment.txt` | GET enrollment 9999 | 404 |
| 11 | `11-duplicate-enrollment.txt` | Same enrollment twice | 409 |
| 12 | `12-dependency-student-service-down.txt` | Enroll while student service stopped | 503 |
| 13 | `13-dependency-course-service-down.txt` | Enroll while course service stopped | 503 |
| 14 | `14-timeout-slow-service.txt` | Enroll while student service slow | 504 |

---

## 5. Reflection

The most significant learning from this lab was understanding the **spectrum of failure** in distributed systems. In the monolith, error handling was binary: the app is either running (handle validation/not-found) or it's completely crashed (nothing works). In microservices, there's a rich taxonomy of partial failures:

- **Down** (503): The dependency's process isn't running at all — connection refused immediately.
- **Slow** (504): The dependency accepted the TCP connection but isn't responding — could be a deadlock, overloaded queue, or GC pause.
- **Partially degraded**: The dependency returns unexpected errors but is reachable — the enrollment service needs to interpret what happened.

The timeout implementation was particularly instructive. Without `Http::timeout(5)`, a single slow dependency could cause the enrollment service to hang indefinitely on every request that tries to verify a student or course. In production, this would cause thread exhaustion and make the enrollment service itself appear down to its clients — a classic **cascading failure** that takes down the entire system even though only one service had the original problem.

The standardized error format (`{error, message}`) seems like a small detail but it's critical for API consumers. Without it, clients need to parse different error shapes depending on what went wrong (Laravel's default validation errors look different from 404 errors, which look different from custom errors). A consistent format means the frontend can have one error-handling function that works for every failure mode.

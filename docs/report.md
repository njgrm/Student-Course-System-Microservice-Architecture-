# Lab 2 — Microservices Edge Case Testing Report

**Course:** System Architecture and Integration 2
**Laboratory:** Lab 2 — Microservices Edge Case Testing (Curl-Based)

---

## Abstract

This laboratory focused on testing the robustness and reliability of a microservices-based Student Course System built in Laravel 12. The system consists of three independent services — Student Service (port 8001), Course Service (port 8002), and Enrollment Service (port 8003) — each running its own SQLite database and communicating with the others over HTTP. The objective was to verify that each service correctly handles distributed system edge cases: validation failures, missing resources, duplicate requests, dependency outages, and network timeouts. All edge cases were tested using curl from the command line, with each service returning standardized JSON error responses in the format `{"error": "STATUS_CODE ERROR_TYPE", "message": "Human readable explanation"}`. Testing confirmed that all five required HTTP error status codes (400, 404, 409, 503, 504) were correctly triggered, properly formatted, and consistently returned across all services. This lab demonstrated that proper error handling in microservices requires deliberate implementation at multiple layers — input validation, route resolution, cross-service verification, and network fault tolerance — and that the consequences of omitting any one layer extend beyond individual requests to the stability of the entire system.

---

## Introduction

Microservices architecture decomposes a system into small, independently deployable services that each own a specific domain and communicate over the network. Unlike a monolithic application where function calls between modules are synchronous and always succeed or fail atomically, microservices introduce **partial failure** — a condition where one service may be healthy while another is down, slow, or returning unexpected results.

This lab required implementing and validating five categories of failure handling that are fundamental to any production-grade distributed system:

**1. Input Validation (HTTP 400 Bad Request)**
Clients submit HTTP requests with JSON bodies. Those bodies may be empty, missing required fields, or contain values that violate business rules (e.g., a negative age, an invalid email format, or a zero-credit course). Without server-side validation, bad data enters the database and corrupts downstream operations. HTTP 400 signals to the client that the problem is in the request itself, not the server.

**2. Resource Not Found (HTTP 404 Not Found)**
REST APIs address resources by ID. When a client requests `/api/students/9999` and no student with ID 9999 exists, the server must distinguish this from a successful empty response. HTTP 404 creates this distinction. In a microservices context, this also applies when the Enrollment Service must verify that a student or course exists in another service before creating an enrollment record.

**3. Duplicate Resource Conflict (HTTP 409 Conflict)**
Some operations are idempotent (repeating them has no effect); others are not. Creating an enrollment is a non-idempotent operation — if a student is already enrolled in a course, a second request to enroll them should be rejected. HTTP 409 communicates that the request was valid but conflicts with current server state, which is semantically distinct from a bad request (400) or a server fault (500).

**4. Dependency Unavailable (HTTP 503 Service Unavailable)**
The Enrollment Service depends on Student Service and Course Service to verify entities before creating records. If either dependency is stopped or crashed, the TCP connection is refused immediately. Without explicit handling, this would surface as an unhandled PHP exception returning HTTP 500. HTTP 503 communicates that the current service is healthy but one of its dependencies is not.

**5. Dependency Timeout (HTTP 504 Gateway Timeout)**
A service accepting connections but responding slowly (due to database locks, memory pressure, or CPU saturation) creates a different failure mode from a service that is outright down. Without a timeout, the calling service's request-handling thread blocks indefinitely, eventually exhausting the thread pool and making the calling service appear down as well — a **cascading failure**. HTTP 504 communicates that the dependency was reached but did not respond within the allowed time window.

The standard error response format used throughout the lab:

```json
{
    "error": "STATUS_CODE ERROR_TYPE",
    "message": "Human readable explanation"
}
```

This consistent shape ensures that any API consumer — whether a frontend application or another service — can handle errors with a single, uniform parsing strategy.

---

## Methodology / Implementation

### System Setup

Three Laravel 12 microservices were each started on their designated ports using `php artisan serve`:

```
php artisan serve --port=8001   # Student Service
php artisan serve --port=8002   # Course Service
php artisan serve --port=8003   # Enrollment Service
```

Each service maintained its own isolated SQLite database (`database/database.sqlite`), with no shared tables between services.

### Standardized Error Handler

Each service's `bootstrap/app.php` was configured with a custom exception renderer registered via `withExceptions()`. This intercepts exceptions before Laravel's default handler and formats them into the standard JSON shape:

```php
->withExceptions(function (Exceptions $exceptions): void {

    // 400 — Validation errors
    $exceptions->render(function (ValidationException $e, $request) {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error'   => '400 VALIDATION_ERROR',
                'message' => collect($e->errors())->flatten()->implode(' '),
            ], 400);
        }
    });

    // 404 — Model not found (route model binding)
    $exceptions->render(function (ModelNotFoundException $e, $request) {
        if ($request->expectsJson() || $request->is('api/*')) {
            $model = class_basename($e->getModel());
            return response()->json([
                'error'   => '404 NOT_FOUND',
                'message' => "{$model} not found.",
            ], 404);
        }
    });

    // 404 — Route or model not found (handles Laravel's internal conversion)
    $exceptions->render(function (NotFoundHttpException $e, $request) {
        if ($request->expectsJson() || $request->is('api/*')) {
            $previous = $e->getPrevious();
            if ($previous instanceof ModelNotFoundException) {
                $model = class_basename($previous->getModel());
                return response()->json([
                    'error'   => '404 NOT_FOUND',
                    'message' => "{$model} not found.",
                ], 404);
            }
            return response()->json([
                'error'   => '404 NOT_FOUND',
                'message' => 'The requested resource was not found.',
            ], 404);
        }
    });
})
```

A `PrettyJson` middleware was also applied globally to all three services to format JSON responses with line breaks and indentation for readability:

```php
// app/Http/Middleware/PrettyJson.php
public function handle(Request $request, Closure $next)
{
    $response = $next($request);
    if ($response instanceof JsonResponse) {
        $response->setEncodingOptions(
            $response->getEncodingOptions() | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }
    return $response;
}
```

### Validation (400)

Each service uses a dedicated **Form Request** class to define validation rules, keeping validation logic out of controllers:

| Service | Request Class | Rules |
|---|---|---|
| Student | `StoreStudentRequest` | `full_name`: required, string; `email`: required, valid email, unique; `age`: required, integer, min:1 |
| Course | `StoreCourseRequest` | `name`: required, string; `description`: required, string; `credits`: required, integer, min:1 |
| Enrollment | `StoreEnrollmentRequest` | `student_id`: required, integer, min:1; `course_id`: required, integer, min:1 |

When validation fails, the `ValidationException` is caught and all error messages are flattened into a single string using `collect($e->errors())->flatten()->implode(' ')`, ensuring the full list of failures is returned rather than a truncated summary.

### Not Found (404)

Laravel's route model binding was used in all controllers (`Student $student`, `Course $course`, `Enrollment $enrollment`). When the bound model does not exist, Laravel throws `ModelNotFoundException` which is caught by the custom exception handler and converted to a 404 response with the model name embedded in the message.

An additional layer in the `NotFoundHttpException` handler inspects `$e->getPrevious()` to handle the case where Laravel internally converts `ModelNotFoundException` to `NotFoundHttpException` before the renderer runs.

In the Enrollment Service, cross-service 404s are handled inline in the controller: if the HTTP call to Student Service or Course Service returns a non-2xx response, a 404 is returned with a specific message identifying which dependency reported the missing resource.

### Duplicate Enrollment (409)

Before creating an enrollment, the controller checks for an existing record with the same `student_id` + `course_id` pair:

```php
$exists = Enrollment::where('student_id', $validated['student_id'])
    ->where('course_id', $validated['course_id'])
    ->exists();

if ($exists) {
    return response()->json([
        'error'   => '409 DUPLICATE_ENROLLMENT',
        'message' => 'Student is already enrolled in this course.',
    ], 409);
}
```

### Dependency Down (503) and Timeout (504)

All inter-service HTTP calls in the Enrollment Service are wrapped in `try/catch` blocks targeting `ConnectionException`. The exception message is inspected to distinguish a timeout from a refused connection:

```php
try {
    $response = Http::timeout(5)->get('http://localhost:8001/api/students/...');
} catch (ConnectionException $e) {
    $code = str_contains($e->getMessage(), 'timed out') ||
            str_contains($e->getMessage(), 'Timeout') ? 504 : 503;

    return response()->json([
        'error'   => $code === 504 ? '504 GATEWAY_TIMEOUT' : '503 SERVICE_UNAVAILABLE',
        'message' => $code === 504
            ? 'Student Service timed out.'
            : 'Student Service is unavailable.',
    ], $code);
}
```

To test the timeout (504), a dedicated route was added to the Student Service that sleeps for 10 seconds — longer than the 5-second `Http::timeout(5)` used by the Enrollment Service:

```php
// student-service routes/api.php
Route::get('students/slow-test', function () {
    sleep(10);
    return response()->json(['id' => 1, 'full_name' => 'Slow Response']);
});
```

A corresponding test route was added to the Enrollment Service that calls this slow route directly, triggering the timeout without requiring any code modifications during testing:

```
GET /api/enrollments/timeout-test
```

### Test Execution

All tests were performed using curl with the `-i` flag to display HTTP status headers. Each test output was saved to `docs/evidence/` as a `.txt` file:

```bash
curl -i -X POST http://localhost:8001/api/students \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"full_name\": \"John Doe\", \"email\": \"john@example.com\", \"age\": 21}" \
  > docs/evidence/01-create-student.txt 2>&1
```

The full list of curl commands is documented in `tests/curl-tests.md`.

---

## Experimental Findings / Observations

### Happy Path (HTTP 200 / 201)

All CRUD operations across all three services returned the expected success responses. Student creation returned 201 with the new record's ID. Course creation returned 201. Enrollment creation returned 201 with the enriched enrollment object (including `student_name` and `course_name` fetched live from the respective services). Read operations (index, show) returned 200 with correct data.

*See evidence files: `01-create-student.txt` through `10-update-course.txt`*

### Validation Errors (HTTP 400)

When fields were missing or invalid, the service returned 400 with a detailed message listing every failing rule. For example, submitting an empty body to `POST /api/courses` returned:

```json
{
    "error": "400 VALIDATION_ERROR",
    "message": "The name field is required. The description field is required. The credits field is required."
}
```

Submitting an invalid email to `POST /api/students` returned:

```json
{
    "error": "400 VALIDATION_ERROR",
    "message": "The email field must be a valid email address."
}
```

*See evidence files: `11-missing-field.txt`, `12-invalid-email.txt`, `14-negative-age.txt`, `15-empty-body.txt`, `16-invalid-credits.txt`*

### Not Found (HTTP 404)

Requesting a non-existent student (`GET /api/students/9999`) returned a specific message using the model name extracted from the exception:

```json
{
    "error": "404 NOT_FOUND",
    "message": "Student not found."
}
```

Cross-service 404s from the Enrollment Service identified the specific dependency:

```json
{
    "error": "404 NOT_FOUND",
    "message": "Student not found in Student Service."
}
```

*See evidence files: `17-student-not-found.txt`, `18-course-not-found.txt`, `19-enroll-bad-student.txt`, `20-enroll-bad-course.txt`*

### Duplicate Enrollment (HTTP 409)

Re-enrolling the same student in the same course returned:

```json
{
    "error": "409 DUPLICATE_ENROLLMENT",
    "message": "Student is already enrolled in this course."
}
```

*See evidence file: `22-duplicate-enrollment.txt`*

### Dependency Down (HTTP 503)

With the Student Service stopped, attempting to create an enrollment returned immediately with:

```json
{
    "error": "503 SERVICE_UNAVAILABLE",
    "message": "Student Service is unavailable."
}
```

The response was near-instantaneous — Laravel's HTTP client detected the refused connection without waiting. The same pattern held when the Course Service was stopped.

*See evidence files: `27-service-unavailable-503.txt`, `28-dependency-course-down.txt`*

### Timeout (HTTP 504)

Hitting `GET /api/enrollments/timeout-test` with both services running caused the enrollment service to wait exactly 5 seconds (the configured timeout) before returning:

```json
{
    "error": "504 GATEWAY_TIMEOUT",
    "message": "Student Service timed out."
}
```

The 5-second delay was observable in the curl output timestamp. The student service's `slow-test` route (10-second sleep) was confirmed separately:

```bash
curl -i --max-time 3 http://localhost:8001/api/students/slow-test
# Result: curl itself timed out after 3 seconds
```

*See evidence file: `29-timeout-504.txt`*

---

## Lab Discussions / Computations

### HTTP Status Code Mapping

| Scenario | Trigger | Status Code | Error Field |
|---|---|---|---|
| Missing or invalid input | `ValidationException` in Form Request | 400 | `400 VALIDATION_ERROR` |
| Resource ID not in database | Route model binding failure | 404 | `404 NOT_FOUND` |
| Cross-service resource missing | Remote service returns non-2xx | 404 | `404 NOT_FOUND` |
| Enrollment already exists | `Enrollment::where()->exists()` check | 409 | `409 DUPLICATE_ENROLLMENT` |
| Dependency service stopped | `ConnectionException` (connection refused) | 503 | `503 SERVICE_UNAVAILABLE` |
| Dependency service too slow | `ConnectionException` (timed out, 5s) | 504 | `504 GATEWAY_TIMEOUT` |

### 503 vs 504 Distinction

The distinction between 503 and 504 is detected by parsing the exception message string from Laravel's HTTP client:

```php
$isTimeout = str_contains($e->getMessage(), 'timed out')
          || str_contains($e->getMessage(), 'Timeout');
```

- If `true` → 504 (the service was reachable but slow)
- If `false` → 503 (the connection was refused outright)

This string-based detection is functional for the purposes of this lab. A production system would use more structured exception types or HTTP client configuration flags.

### Cascading Failure Prevention

Without `Http::timeout(5)`, a single slow dependency blocks the enrollment service thread for the full duration of the slow response. Under load, with multiple concurrent requests all waiting on the same slow dependency, the enrollment service thread pool exhausts and the service becomes unresponsive to all requests — not just those involving the slow service. The timeout breaks this feedback loop.

| Scenario | Without Timeout | With Timeout (5s) |
|---|---|---|
| 1 slow request | 1 thread blocked | Freed after 5s, 504 returned |
| 100 concurrent slow requests | All 100 threads blocked → service down | All freed after 5s, 100× 504 returned |
| Other (unrelated) requests | Also blocked or queued | Handled normally |

### Validation Message Completeness

Laravel's default `$e->getMessage()` returns only the first error plus a count: `"The name field is required. (and 2 more errors)"`. By replacing it with `collect($e->errors())->flatten()->implode(' ')`, all messages are included in a single readable string, giving the API consumer complete information about what needs to be corrected in a single request.

---

## Conclusions

This laboratory demonstrated that error handling in a microservices architecture is not a single concern but a multi-layered discipline. Each service must independently validate its own inputs, handle its own missing-resource cases, and protect itself against failures in its dependencies — because no other service will do this on its behalf.

The five HTTP status codes tested (400, 404, 409, 503, 504) represent a minimum set of failure modes that any distributed system must address. Each carries a distinct semantic meaning that guides client behavior: 400 means "fix your request," 404 means "this resource does not exist," 409 means "your request conflicts with existing state," 503 means "retry later when the service recovers," and 504 means "the upstream is reachable but overloaded — back off."

The standardized `{"error": "STATUS_CODE ERROR_TYPE", "message": "..."}` response format, enforced globally through custom exception renderers in each service's `bootstrap/app.php`, ensures that all failure modes — regardless of where they originate — produce a predictable, machine-parseable response shape. This consistency is essential for any client that integrates with multiple services and needs uniform error handling logic.

The timeout implementation was particularly significant. Without explicit timeouts, a microservice's availability becomes dependent on the availability and performance of every service it calls — effectively collapsing the independence that microservices architecture is designed to provide. The 5-second `Http::timeout(5)` setting enforces a strict contract: if a dependency cannot respond within 5 seconds, the current service refuses to wait indefinitely and returns a controlled failure instead of propagating the slowness to its own callers.

---

## Evidence

All curl output files are in `docs/evidence/`. Screenshots of terminal output should also be saved in `docs/evidence/screenshots/` — create that folder and save `.png` files there, named to match the corresponding `.txt` file (e.g., `01-create-student.png`).

| File | Test Case | Expected Status |
|---|---|---|
| `01-create-student.txt` | Create student | 201 |
| `02-create-course.txt` | Create course | 201 |
| `03-create-enrollment.txt` | Create enrollment (enriched) | 201 |
| `04-list-students.txt` | List all students | 200 |
| `05-list-courses.txt` | List all courses | 200 |
| `06-list-enrollments.txt` | List all enrollments | 200 |
| `07-get-student.txt` | Get single student | 200 |
| `08-get-course.txt` | Get single course | 200 |
| `09-update-student.txt` | Update student | 200 |
| `10-update-course.txt` | Update course | 200 |
| `11-missing-field.txt` | Student — missing fields | 400 |
| `12-invalid-email.txt` | Student — invalid email | 400 |
| `14-negative-age.txt` | Student — negative age | 400 |
| `15-empty-body.txt` | Course — empty body | 400 |
| `16-invalid-credits.txt` | Course — zero credits | 400 |
| `17-student-not-found.txt` | GET student 9999 | 404 |
| `18-course-not-found.txt` | GET course 9999 | 404 |
| `19-enroll-bad-student.txt` | Enroll non-existent student | 404 |
| `20-enroll-bad-course.txt` | Enroll into non-existent course | 404 |
| `22-duplicate-enrollment.txt` | Same enrollment twice | 409 |
| `27-service-unavailable-503.txt` | Enroll while student service down | 503 |
| `28-dependency-course-down.txt` | Enroll while course service down | 503 |
| `29-timeout-504.txt` | Timeout test route | 504 |

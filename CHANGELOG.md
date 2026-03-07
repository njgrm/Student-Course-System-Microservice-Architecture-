# Changelog

## [2026-03-07] — Gateway Single-Page Dashboard (SAR2-Style Unified UI)

### Added
- `resources/views/dashboard.blade.php` — Self-contained Blade template with three stacked sections (Students, Courses, Enrollments) on one page. Colored header bars with service status badges (🟢 Online / 🔴 Unavailable), inline add forms, data tables with count badges, toast notifications, and graceful degradation per section.
- `resources/js/dashboard.js` — Axios-based frontend controller mirroring SAR2/public/script.js. Uses `Promise.allSettled` for parallel independent loading, per-section try/catch, dynamic enrollment dropdowns, service status tracking object, and HTML escaping utility.
- `services/student-service/config/cors.php` — CORS config with `allowed_origins` set to `['http://localhost:8000', 'http://127.0.0.1:8000']`
- `services/course-service/config/cors.php` — Same CORS config
- `services/enrollment-service/config/cors.php` — Same CORS config

### Changed
- `routes/web.php` — Root route now serves `dashboard` view instead of `welcome`
- `vite.config.js` — Added `resources/js/dashboard.js` as a Vite input alongside `app.js`

### Fixed
- CORS was silently blocked: `HandleCors` middleware was active by default in Laravel 12, but without a `config/cors.php` file, `config('cors')` returned `[]` and `hasMatchingPath()` always returned false — no CORS headers were ever sent. Creating the config file with `paths => ['api/*']` fixed it.

### Learnings & Mistakes
- Laravel 12 bundles `fruitcake/cors` inside `laravel/framework` — no separate Composer package needed, but the config file must exist for headers to be sent
- Axios wraps responses in `{ data: <body> }` — when the Laravel API returns a plain array, `response.data` IS the array. The pattern `data.data ?? data` gracefully handles both wrapped `{data: [...]}` and plain array responses
- `Promise.allSettled` (not `Promise.all`) is essential for independent section loading — if one service is down, the others still render
- Enrollment dropdowns depend on Student + Course APIs independently — when one is down, only that dropdown shows "unavailable" while the other still populates
- The gateway app runs on port 8000 (default `php artisan serve`) — separate from the three microservices on 8001-8003

## [2026-03-07] — Add CORS Configuration to All Microservices

### Added
- `services/student-service/config/cors.php` — CORS config enabling API cross-origin access
- `services/course-service/config/cors.php` — same CORS config for course service
- `services/enrollment-service/config/cors.php` — same CORS config for enrollment service

### Changed
- Nothing modified — three new files only

### Fixed
- CORS headers were never sent despite `HandleCors` middleware being active by default — the middleware reads from `config('cors')` which resolved to `[]` (empty paths), causing `hasMatchingPath()` to always return `false`

### Learnings & Mistakes
- In Laravel 12, the `fruitcake/cors` library is bundled INSIDE `laravel/framework` — no need to install `fruitcake/laravel-cors` separately
- `HandleCors` is in the default global middleware stack (`Illuminate\Foundation\Configuration\Middleware::getGlobalMiddleware()`) — it runs automatically without any `bootstrap/app.php` registration
- However, **without `config/cors.php`**, the middleware does nothing — `config('cors')` returns `[]`, so `paths` is empty and no request matches
- Laravel 12 dropped the auto-published `config/cors.php` from `laravel/laravel` skeleton — it must be created manually
- The config key `'paths' => ['api/*']` is critical — it tells the middleware WHICH routes get CORS headers. Without it, all CORS is silently disabled

## [2026-03-06] — UI Modernization, README Rewrite, Agent File Cleanup

### Added
- Full README.md rewrite with architecture overview, quick-start guide, `npm run serve:all` instructions, API endpoint docs, tech stack table, and project structure tree

### Changed
- **All 3 layout templates** (`layouts/app.blade.php`): Dark `bg-slate-800` header with SVG service icons, Inter font via bunny.net, `font-mono` port badges, Tailwind-styled body
- **student-manager.blade.php**: SAR2-inspired design — white card sections with `border-b-2 border-indigo-500` accent, inline grid form, count badge, SVG Heroicon action buttons, empty state illustration, removed ID column
- **course-manager.blade.php**: Same SAR2 card pattern with emerald accent, inline 3-col grid form, credits badge pill, SVG icons, empty state with book illustration
- **enrollment-manager.blade.php**: Amber accent, side-by-side select dropdowns, avatar initial circles for student names, emerald course pills, SVG delete icons, error + success toast notifications
- `.gitignore`: Added `AGENTS.md`, `.cursor/`, `.github/copilot-instructions.md` to prevent agent tool files from being committed

### Fixed
- Vite assets rebuilt in all 3 services after template changes

### Learnings & Mistakes
- Agent tool files (AGENTS.md, .cursor/) were already not tracked in git — `git rm --cached` confirmed they hadn't been committed, so `.gitignore` entries are purely preventive
- Tailwind CSS 4 (via Vite) processes all Blade files automatically — no need to update `content` paths when adding new Tailwind classes to templates
- SAR2 design language translates well to Tailwind: `#2c3e50` ≈ `slate-800`, `#3498db` ≈ `indigo-500`/`blue-500`, card sections with colored border-bottom headings

## [2026-03-06] — Add Laravel Specialist Cursor Rule (Skills Research)

### Added
- `.cursor/rules/laravel-specialist.mdc` — Curated Cursor rule file with 8 project-specific patterns:
  1. `Http::fake()` for inter-service testing (enrollment-service isolation)
  2. Feature test structure (RefreshDatabase, assertDatabaseHas, assertJsonValidationErrors)
  3. API Resources (JsonResource with enrichment data from other services)
  4. Eloquent Query Scopes (scopeForStudent, scopeForCourse, scopeEnrolledAfter)
  5. Model Observers for cascade cleanup (student/course delete → notify enrollment-service)
  6. Eager loading & N+1 prevention (with(), withCount(), chunk())
  7. JSON response standards (201 created, 422 validation, 404 not found)
  8. Artisan test command reference

### Changed
- Nothing modified — new file only

### Fixed
- Nothing

### Learnings & Mistakes
- **Skills are NOT installed as npm packages in a Laravel project** — community skills (jeffallan/claude-skills, jezweb/claude-skills) are designed for Claude Code CLI (`npx skills add`), which installs SKILL.md files to `~/.claude/skills/`
- **For Cursor IDE**: skills translate to `.cursor/rules/*.mdc` files (identical pattern to the existing `laravel-boost.mdc` — `alwaysApply: true` frontmatter)
- **For GitHub Copilot in VS Code**: skills go in `AGENTS.md` or `.github/copilot-instructions.md`
- **Don't install the full skill**: The `/jeffallan/claude-skills` laravel-specialist skill is too generic — it covers queues/Horizon/JWT/auth irrelevant to this project. Curate only relevant patterns
- **Most impactful pattern**: `Http::fake()` for enrollment-service tests — without it, tests fail whenever student/course services aren't running
- Validated: `.cursor/rules/` already existed with `laravel-boost.mdc` — adding `laravel-specialist.mdc` follows the same convention

## [2026-03-06] — Fix Vite Manifest Error & Add Serve-All Dev Tooling

### Added
- `npm run serve:all` — Single command to start all 3 services (ports 8001/8002/8003) using `concurrently` with color-coded, labeled output
- `npm run build:services` — Build Vite assets for all 3 services in parallel
- `npm run install:services` — Install npm dependencies across all 3 services
- `npm run fresh:services` — Reset and re-seed all 3 SQLite databases in parallel
- `serve-all.ps1` — PowerShell script that launches each service in its own terminal window
- `.vscode/tasks.json` — VS Code task definitions:
  - **Serve All Services** (compound task, Ctrl+Shift+B) — launches 3 dedicated terminals grouped together
  - **Build All Vite Assets** — runs `npm run build:services`
  - **Fresh Seed All Services** — runs `npm run fresh:services`
  - **Test All Services** — runs `php artisan test` sequentially across all 3 services
- `public/build/manifest.json` in all 3 services — generated via `npm run build` (Vite production build)

### Changed
- `package.json` (root) — Added `serve:all`, `build:services`, `install:services`, `fresh:services` scripts

### Fixed
- **Vite manifest not found** (`ViteManifestNotFoundException`) — All 3 services had `@vite()` in Blade layouts but no built assets. Fixed by running `npm install && npm run build` in each service to generate `public/build/manifest.json`

### Learnings & Mistakes
- **Vite assets must be built before serving** — Laravel's `@vite()` directive requires `public/build/manifest.json` which is only created by `npm run build` (production) or served by `npm run dev` (dev server). Without either, every Blade page throws `ViteManifestNotFoundException`.
- **`concurrently` is already a root dependency** — No new installs needed; the root `package.json` had it at `^9.0.1` from the Laravel Boost scaffold.
- **VS Code compound tasks** are the best DX — `dependsOn` with `dependsOrder: parallel` + `presentation.group: "services"` groups all 3 terminals together with dedicated panels. Run via `Ctrl+Shift+B` or `Tasks: Run Build Task`.
- **Node version warning** — Vite 7.3.1 requires Node `^20.19.0 || >=22.12.0` but builds fine on `v20.18.0`. Not blocking.

## [2026-03-06] — Scaffold Three Laravel Microservices with SQLite + Livewire

### Added
- `services/student-service/` — Fresh Laravel 12 project (port 8001), `APP_NAME=StudentService`
- `services/course-service/` — Fresh Laravel 12 project (port 8002), `APP_NAME=CourseService`
- `services/enrollment-service/` — Fresh Laravel 12 project (port 8003), `APP_NAME=EnrollmentService`
- Livewire v4.2.1 installed in all three services via `composer require livewire/livewire`
- SQLite database created and default migrations run in each service (`database/database.sqlite`)
- Git repository initialized at project root with remote `origin` pointing to `https://github.com/njgrm/Student-Course-System-Microservice-Architecture-.git`
- Local `test` branch created, tracking `origin/test`
- `AGENTS.md` — Full agent instructions with Livewire 4 in tech stack, Context7 library IDs (`/websites/livewire_laravel_4_x`), commit message protocol, and CHANGELOG protocol

### Changed
- `.gitignore` — Added `services/*/vendor/`, `services/*/node_modules/`, `services/*/.env`, `services/*/database/database.sqlite`, and other service-level ignores to prevent bloated commits
- `.gitignore` — Removed `AGENTS.md` from ignore list (it should be tracked)
- Each service `.env` — Updated `APP_NAME` and `APP_URL` with correct service name and port (8001/8002/8003); MySQL vars already commented out by default in Laravel 12's fresh scaffold

### Fixed
- SAR2 had an embedded `.git` directory that caused `git add` warnings about embedded repositories — removed `SAR2/.git` so the monolith reference is tracked as normal files
- Composer scaffolded each service with `DB_CONNECTION=sqlite` already set (Laravel 12 default) — no need to manually switch from MySQL in the service `.env` files
- First `composer create-project` failed with "directory not empty" because the `services/` dir was pre-created — fixed by removing and re-running

### Learnings & Mistakes
- **Laravel 12 defaults to SQLite** — Unlike older versions, fresh Laravel 12 scaffolds set `DB_CONNECTION=sqlite` with MySQL lines commented out in `.env`. No manual switch needed for services.
- **Root project `.env` still uses MySQL** — The root gateway project at `d:\Lab1_ITSAR2\.env` has `DB_CONNECTION=mysql` / `DB_DATABASE=example_app`. This is separate from the services and will need updating if we use the root as a gateway.
- **`composer create-project` needs an empty target** — Running it when the target directory already exists (even if empty) fails. Must ensure the path doesn't exist beforehand.
- **Embedded `.git` in SAR2** — The monolith reference had its own git history. Removing `SAR2/.git` before staging prevents submodule confusion and ensures all SAR2 files are tracked normally.
- **Livewire v4.2.1** is the current latest compatible with Laravel 12 — confirmed via `composer show livewire/livewire`.
- **Context7 confirmed** — Resolved Livewire 4 docs at `/websites/livewire_laravel_4_x` (1664 snippets, trust 9.9). This should be used before writing any Livewire component code.

## [2026-03-06] — Full Implementation of All Three Microservices

### Added

**Student Service (port 8001):**
- `app/Models/Student.php` — Eloquent model with `$fillable` (full_name, email, age) and `casts()` (age → integer)
- `database/migrations/2026_03_06_034555_create_students_table.php` — Schema: full_name string, email string unique, age integer, timestamps
- `database/factories/StudentFactory.php` — Fake name, email, age 18–30
- `database/seeders/StudentSeeder.php` — 4 sample students (Alice Johnson, Bob Smith, Charlie Brown, Diana Prince)
- `app/Http/Requests/StoreStudentRequest.php` — Validates full_name (required|string), email (required|email|unique), age (required|integer|min:1)
- `app/Http/Requests/UpdateStudentRequest.php` — Same with `sometimes` + unique-ignore-current
- `app/Http/Controllers/StudentController.php` — Full CRUD (index/store/show/update/destroy) + cascade delete notification to Enrollment Service
- `routes/api.php` — `Route::apiResource('students', StudentController::class)`
- `app/Livewire/StudentManager.php` — Full CRUD Livewire component with form state, validation, flash messages
- `resources/views/livewire/student-manager.blade.php` — Tailwind-styled table + form with indigo theme
- `resources/views/layouts/app.blade.php` — Base layout with nav bar (📚 Student Service)
- `resources/views/students/index.blade.php` — Extends layout, renders `<livewire:student-manager />`
- `tests/Feature/StudentApiTest.php` — 9 feature tests covering full CRUD, validation, duplicate email, 404s, homepage

**Course Service (port 8002):**
- `app/Models/Course.php` — Eloquent model with `$fillable` (name, description, credits) and `casts()` (credits → integer)
- `database/migrations/2026_03_06_035209_create_courses_table.php` — Schema: name string, description text, credits integer, timestamps
- `database/factories/CourseFactory.php` — Fake words, sentence, numberBetween 1–6
- `database/seeders/CourseSeeder.php` — 4 sample courses (Intro to CS, Data Structures, Web Dev, Database Systems)
- `app/Http/Requests/StoreCourseRequest.php` — Validates name, description, credits (all required)
- `app/Http/Requests/UpdateCourseRequest.php` — Same with `sometimes`
- `app/Http/Controllers/CourseController.php` — Full CRUD + cascade delete notification to Enrollment Service
- `routes/api.php` — `Route::apiResource('courses', CourseController::class)`
- `app/Livewire/CourseManager.php` — Full CRUD Livewire component with emerald theme
- `resources/views/livewire/course-manager.blade.php` — Table + form with textarea for description
- `resources/views/layouts/app.blade.php` — Base layout (📖 Course Service)
- `resources/views/courses/index.blade.php` — Extends layout, renders `<livewire:course-manager />`
- `tests/Feature/CourseApiTest.php` — 8 feature tests covering full CRUD, validation, 404s, homepage

**Enrollment Service (port 8003):**
- `app/Models/Enrollment.php` — Eloquent model with `$fillable` (student_id, course_id, enrolled_at) and casts (integers, datetime)
- `database/migrations/2026_03_06_035750_create_enrollments_table.php` — Schema: student_id unsignedBigInteger, course_id unsignedBigInteger, enrolled_at timestamp, unique composite [student_id, course_id]
- `database/factories/EnrollmentFactory.php` — Random student_id/course_id 1–4, enrolled_at now()
- `database/seeders/EnrollmentSeeder.php` — 5 sample enrollments linking students to courses
- `app/Http/Requests/StoreEnrollmentRequest.php` — Validates student_id and course_id (required|integer|min:1)
- `app/Http/Controllers/EnrollmentController.php` — Full CRUD with:
  - Inter-service HTTP calls to verify student/course existence before enrollment
  - Duplicate enrollment check (returns 409)
  - Response enrichment with student_name and course_name from other services
  - `destroyByStudent()` and `destroyByCourse()` cascade endpoints
  - Graceful error handling when services are unavailable (503)
- `routes/api.php` — Custom routes (enrollments/student/{id}, enrollments/course/{id}) + CRUD routes
- `app/Livewire/EnrollmentManager.php` — CRUD component that fetches students/courses from other services for dropdown selects, amber theme
- `resources/views/livewire/enrollment-manager.blade.php` — Table + form with select dropdowns, shows enriched student/course names
- `resources/views/layouts/app.blade.php` — Base layout (🎓 Enrollment Service)
- `resources/views/enrollments/index.blade.php` — Extends layout
- `tests/Feature/EnrollmentApiTest.php` — 11 feature tests covering CRUD, duplicate enrollment (409), nonexistent student/course (404), cascade delete by student/course, Http::fake for inter-service calls

**Testing Infrastructure:**
- `tests/TestCase.php` in all 3 services — Added `withoutVite()` in `setUp()` to prevent Vite manifest errors during testing
- `tests/Feature/ExampleTest.php` in all 3 services — Enabled `RefreshDatabase` trait
- All 3 services' `bootstrap/app.php` — Updated with `api: __DIR__.'/../routes/api.php'` for API route registration

### Changed
- `AGENTS.md` — Added Laravel Boost guidelines section under Tools referencing `.cursor/rules/laravel-boost.mdc`
- `database/seeders/DatabaseSeeder.php` in each service — Updated to call respective entity seeders (StudentSeeder, CourseSeeder, EnrollmentSeeder)

### Fixed
- PHPUnit tests failed with "no such table" because `:memory:` SQLite has no schema — fixed by adding `RefreshDatabase` trait to all feature tests
- PHPUnit tests failed with "Vite manifest not found" because `@vite()` in Blade layouts requires built assets — fixed by adding `$this->withoutVite()` to base `TestCase::setUp()`
- API route ordering in Enrollment Service — Specific routes (`enrollments/student/{id}`, `enrollments/course/{id}`) must come BEFORE wildcard `enrollments/{enrollment}` to prevent route conflicts

### Learnings & Mistakes
- **RefreshDatabase trait is mandatory** for any feature test touching Eloquent — PHPUnit uses `:memory:` SQLite by default and the in-memory DB has no tables until migrations run
- **`withoutVite()` in tests** — Any Blade view using `@vite()` will crash in tests unless you call `$this->withoutVite()` or build assets before running tests. Adding it to the base TestCase is the cleanest fix.
- **API route registration in Laravel 12** — No `install:api` needed (it bundles Sanctum). Instead, manually create `routes/api.php` and add `api: __DIR__.'/../routes/api.php'` to `bootstrap/app.php` `withRouting()`
- **Form Request `authorize()` defaults to `false`** — Scaffolded Form Requests deny all requests by default. Must change to `return true;` for each request class.
- **Http::fake() for testing inter-service calls** — Enrollment Service tests use `Http::fake()` to mock responses from Student and Course services, avoiding dependency on running services during testing
- **Route ordering matters** — Specific routes like `/enrollments/student/{id}` must be registered before `/enrollments/{enrollment}` or the wildcard catches the request first
- **Cascade delete is best-effort** — Student/Course controllers wrap enrollment-cleanup HTTP calls in try/catch so a service outage doesn't block the primary delete operation
- **`vendor/bin/pint --dirty`** should be run before every commit — it caught 13 style issues across all services (class_attributes_separation, concat_space, line_ending, blank_line_between_methods)
- **34/34 tests passing** across all three services after all fixes applied

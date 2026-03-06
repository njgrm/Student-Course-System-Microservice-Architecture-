# Changelog

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

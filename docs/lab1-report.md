# Lab 1 — Monolithic vs Microservices Architecture
## Student Course System

---

## Abstract

This laboratory exercise involved building the same Student Course System twice — first as a monolithic application using Node.js and Express, and then as a microservices architecture using Laravel 12. The monolithic version, referred to as SAR2, consolidates all three domains (students, courses, and enrollments) into a single application with shared in-memory data stores and a unified frontend. The microservices version separates each domain into its own independent Laravel service with a dedicated SQLite database, communicating over HTTP using Laravel's HTTP Client and Axios. The exercise highlights the practical tradeoffs between the two architectures in terms of development complexity, scalability, failure resilience, and performance — and provides a foundation for understanding when each approach is most appropriate in real-world systems.

---

## Introduction

Modern web applications are commonly built using one of two architectural patterns: monolithic or microservices. A monolithic architecture packages all features of an application into a single deployable unit, with all modules sharing the same codebase, process, and data store. A microservices architecture, on the other hand, decomposes the application into small, independently deployable services, each responsible for a single domain and communicating with others through well-defined APIs.

To understand this distinction practically, this lab implements a Student Course System — a simple CRUD application managing students, courses, and enrollments — in both architectures. The monolithic version uses Node.js with Express and in-memory data models, reflecting a rapid-prototyping approach. The microservices version uses three separate Laravel 12 applications following strict MVC architecture with Eloquent ORM, Form Request validation, Blade views with Livewire components, and SQLite databases. A fourth Laravel application serves as a gateway dashboard that aggregates data from all three services into a single-page interface using Axios.

Key concepts required for this exercise include:

- **MVC Architecture** — separating data (Model), presentation (View), and logic (Controller) into distinct layers for maintainability and testability
- **RESTful API Design** — using standard HTTP verbs (GET, POST, PUT, DELETE) and status codes to define service interfaces
- **Inter-Service Communication** — using HTTP calls between services to retrieve or verify data owned by another domain
- **Eloquent ORM** — Laravel's object-relational mapper that abstracts database queries into expressive PHP model methods
- **Livewire** — a Laravel full-stack framework for building reactive UI components using server-side PHP, eliminating the need for a separate JavaScript frontend framework in the individual service views
- **Axios** — a JavaScript HTTP client used in the gateway dashboard to call each service's REST API independently and update the DOM without page reloads

---

## Methodology / Implementation

### Monolithic Application (SAR2)

The monolithic application was built using Node.js and Express. Three in-memory model files (`studentModel.js`, `courseModel.js`, `enrollmentModel.js`) serve as the data layer, each storing records in a JavaScript array. Controllers import these models directly and handle business logic such as duplicate email checks and cascade-delete of enrollments when a student or course is removed. Routes map HTTP endpoints to controller functions, and a single vanilla HTML/CSS/JS frontend at `public/index.html` consumes the API via the Fetch API.

The entire application runs on a single `npm start` command from the `SAR2/` directory, serving all three domains on port 3000.

### Microservices Architecture (Laravel)

Three independent Laravel 12 services were scaffolded:

- **Student Service** (port 8001) — manages student records (`full_name`, `email`, `age`)
- **Course Service** (port 8002) — manages course records (`name`, `description`, `credits`)
- **Enrollment Service** (port 8003) — manages enrollments (`student_id`, `course_id`, `enrolled_at`)

Each service follows the same internal MVC pattern:

- **Model** — Eloquent model with `$fillable` and `$casts`, backed by a dedicated `database/database.sqlite`
- **Controller** — thin resourceful controller delegating to Form Request validation and Eloquent methods
- **View** — Blade templates with a Livewire component handling CRUD interactions reactively

The Enrollment Service does not store student or course names in its own database — it only stores foreign IDs, maintaining data ownership. When displaying the enrollment list, it calls the Student and Course services via `Http::get()` to retrieve the display names. When creating an enrollment, it also calls both services to verify the referenced records exist before writing to its own database.

A fourth Laravel gateway application at port 8000 serves a single-page dashboard. `dashboard.js` uses Axios to call all three service APIs in parallel using `Promise.allSettled()`, allowing each section to load and fail independently. If a service is unreachable, only that section shows an offline card — the rest of the page continues functioning normally.

### UI/UX Implementation

The dashboard was built to degrade gracefully under partial downtime. Each section tracks its own service status with a pulsing dot indicator, shows skeleton loading rows while data is being fetched, and presents an offline card with a Retry button when the service cannot be reached. Delete confirmations use a custom modal overlay instead of the browser's native `window.confirm()`. Notifications are delivered through a toast system with icons, titles, a dismiss button, and an animated progress bar.

The individual service UIs (Livewire components) were similarly upgraded to use Alpine.js modals for delete confirmations and dispatch toast events (`$this->dispatch('toast', ...)`) instead of static inline banners.

---

## Experimental Findings / Observations

### Failure Resilience

When the student service was stopped, the gateway dashboard correctly displayed an offline card in the Students section while the Courses and Enrollments sections continued loading normally. The Enrollment form's student dropdown was automatically disabled with a warning indicating the student service was unreachable, preventing a user from attempting an enrollment that would fail validation.

When all three services were stopped simultaneously, all three sections rendered offline cards. The page itself remained fully functional — the header, layout, and retry buttons were all accessible. No JavaScript exceptions were thrown in the browser console.

### Inter-Service HTTP Calls

The Enrollment Service's dependency on the Student and Course Services became observable during testing. When both dependency services were online, enrollment creation was seamless. When either was down, the enrollment form degraded appropriately. This confirmed that the HTTP call structure in `EnrollmentController.php` — using `Http::get()` with failure checks before writing — was working correctly.

### Performance Observation

A noticeable difference in data loading was observed between the monolithic and microservices implementations. In SAR2, the enrollment list renders instantly because all data is in-process. In the microservices version, the enrollment list has a slight delay because it must make HTTP calls to the student and course services to retrieve display names — even though all services are on localhost. This delay, while small in development, would compound at scale when enrollment rows number in the thousands.

### Development Complexity

The microservices architecture required significantly more scaffolding. Three separate Laravel projects each needed their own migrations, seeders, Form Request classes, Eloquent models, API controllers, Livewire components, Blade views, CORS configuration, and Vite asset builds. The same functionality in the monolith was implemented in a fraction of the time with fewer files. This disparity in development effort is the clearest practical argument for using a monolith in small-scale or early-stage projects.

---

## Lab Discussions

### Architecture Comparison

| Criteria | Monolithic (SAR2) | Microservices (Laravel) |
|---|---|---|
| **Ease of Development** | Single codebase, direct function calls, fast to build | Three separate apps, HTTP communication, longer setup |
| **Deployment Difficulty** | One `npm start` command | Four services, four ports, individual builds required |
| **Scalability** | Entire app scales as one unit | Each service scales independently |
| **Failure Impact** | One crash takes down everything | Failure isolated to the affected service |
| **Performance** | In-process data access, no network overhead | Cross-service HTTP calls add latency per operation |

### MVC in the Microservices Architecture

The MVC pattern provided clear separation of responsibilities across each service. Models handled all database interaction through Eloquent — no raw SQL was used. Controllers remained thin, only orchestrating Form Request validation and Eloquent calls. Views and Livewire components handled presentation without containing any business logic. This made each layer independently testable: the 34 PHPUnit feature tests across all three services (`StudentApiTest`, `CourseApiTest`, `EnrollmentApiTest`) tested controllers in isolation using `Http::fake()` to mock inter-service calls, without needing the other services to be running.

### The N+1 HTTP Problem

The enrollment list load reveals a design tension inherent to microservices. Because the Enrollment Service owns only IDs — not names — loading a list of 50 enrollments would require up to 100 additional HTTP calls to retrieve student and course names if done naively. The Livewire `EnrollmentManager` mitigates this by fetching all students and all courses once and building a lookup map in memory. The API controller, however, still enriches per-row. In production this would be addressed with event-driven caching or a read-optimized local replica — a known limitation of strict data ownership in microservices.

---

## Conclusions

This exercise demonstrated that neither monolithic nor microservices architecture is universally superior — the right choice depends on the context and scale of the system. For a small CRUD application with three domains and a handful of users, the monolithic architecture is the more practical choice. It is faster to develop, simpler to deploy, and performs better for the in-process data lookups this type of system relies on.

However, if this system were deployed in a real institution where hundreds or thousands of students are enrolling across multiple courses per semester, the microservices architecture becomes clearly justified. The ability to scale the enrollment service independently — without touching the student or course databases — directly translates to cost savings in cloud infrastructure. The failure isolation means a bug in the enrollment feature does not take the entire student record system offline. And the clean API boundaries between services allow different teams to develop, test, and deploy each domain independently without coordination overhead.

The exercise also revealed that microservices introduce new categories of problems that monoliths do not have: inter-service latency, the N+1 HTTP problem, distributed failure handling, and significantly more complex deployment. These are costs a team must be willing to pay, and they only become worthwhile when the scalability and resilience benefits are actually needed.

In conclusion, for early-stage or small-scale systems, a well-structured monolith remains the better starting point. When the system grows to the point where independent scaling, team separation, or failure isolation become real requirements, the transition to microservices is worth the added complexity.

---

## Reflection

To answer which architecture we prefer between monolithic and microservice architecture, it would have to depend on the context and scope of the system. Because making a simple app like this lab assignment would be small enough to warrant a monolithic architecture. But if this same app were to be actually deployed and be production ready, then our answer would shift to the microservice architecture.

The key difference between the two being that with a microservice architecture, a system becomes easily scalable and production ready. Ready to handle a large base amount or a growing user base. For example, in the enrollment service, a student and their enrolled subject would count as one row of data. If we take into account that each student would have multiple subjects enrolled for a semester, and the fact that the enrollment service will have to call both services to load the enrollment list since it needs both dynamic name and course data, it becomes a lot for the system to handle. Traffic like that would need to have more instances of databases to support that service that demands more storage and resources. And with a microservice architecture, the separation of an enrollment database makes that easier, because the student and course database don't have to scale along with the enrollment service database, which is what we would have to do using a monolithic app that would only have one database to store all services.

Another key difference between the two architectures is the impact of failure with the website. A downed system in any part of the monolithic app means that the entire app becomes unusable, even if just one of the services are affected. This makes it frustrating for both the user and developer experience as urgent calls would have to be made in order to get the system back up and running as soon as possible because the entire system becomes useless when down. On the other hand, having a microservice architecture means that if one of the services are down, the others can still function, or at the very least, still be read and the system would still be online. That's because each service is separated from each other, and only linked to one another via API calls and endpoints. The only service that would really be affected in the case of a downed service is the enrollment service since it requires both student and course services to work in order to add enrollment data. Otherwise, both the student and course services function on their own regardless of what happens to the other services respectively.

Microservice architecture does come with a price, though. Developing it is a harder task since three different systems with their respective databases have to be made and connected to each other through HTTP calls. Contrast that to the monolithic app that only needs its functions to call to one another in one codebase. This leads to longer development time and more resources consumed. Deploying microservices is harder too since multiple server ports need to be active at the same time to support each service, compared to the monolithic app that only needs one npm command to start everything. Performance is also one aspect that separates both architectures, where the microservices app needs to contact other ports for cross-service operations in the case of the enrollment service that needs to consult both student and course services for their data which adds latency to the process. Whereas, the monolithic app does not need to worry about any of that and can retrieve and gain data within its own system and shared database.

Overall, we can say that the microservice architecture is worth the development and deployment difficulty because it brings a lot of great practices to the table, only at the cost of development time and performance. This architecture supports a production ready app and makes it future proof to accommodate a possible growing population of users. And now in a world of online databases that will charge by how much resources your system needs, being able to isolate and independently scale a busy service will save a business a lot of money and hassle. But for a system that would never see more than a couple or a dozen users, then the monolithic app is very much the better choice, as it would mean a lot of resources, such as development time, would be saved with the added bonus of better performance. In conclusion, we learned that both architectures have their respective pros and cons. Ultimately, the better architecture is the one that suits the system better.

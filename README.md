# Student-Course-System-Microservice-Architecture

Laboratory 1: Monolithic vs Microservices Architecture (SAR2)

## Overview

This project implements a **Simple Student Course System** in two architectures:

1. **Monolithic** (Node.js/Express) - in `SAR2/`
2. **Microservices** (Laravel 12 + Livewire 4 + SQLite) - in `services/`

## Microservices

| Service | Port | Directory |
|---------|------|-----------|
| Student Service | 8001 | `services/student-service/` |
| Course Service | 8002 | `services/course-service/` |
| Enrollment Service | 8003 | `services/enrollment-service/` |

### Running the Services

Terminal 1 - Student Service:
  cd services/student-service && php artisan serve --port=8001

Terminal 2 - Course Service:
  cd services/course-service && php artisan serve --port=8002

Terminal 3 - Enrollment Service:
  cd services/enrollment-service && php artisan serve --port=8003

### Running the Monolith

  cd SAR2 && npm install && npm start
  Runs on http://localhost:3000

## Tech Stack

- **Microservices:** PHP 8.2+, Laravel 12, Livewire 4, Blade, Tailwind CSS 4, SQLite, Eloquent ORM
- **Monolith:** Node.js, Express 4, in-memory data store, vanilla HTML/CSS/JS

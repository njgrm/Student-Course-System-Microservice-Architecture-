# Product Ordering API — Business Logic Architecture

> **Laboratory 3:** SYSTEMS ON BUSINESS LOGIC (CURL-BASED TESTING)
> *System Architecture and Integration 2*

---

## Members

| Name |
| ---- |
| Curio, Josh Nathan |
| Gilera, Rowena |
| Gromea, Nehje John |
| Guanzon, Jurriel |
| Sildora, Jegrick |

**Lab 3 Narrative Report:** [LAB3_REPORT.md](LAB3_REPORT.md)

---

## Overview

This project implements a **Product Ordering API** using a layered architecture focused on business logic validation.

| Architecture | Stack | Location |
| --- | --- | --- |
| **Layered REST API** | Node.js · Express 4 · Prisma ORM · SQLite | `lab3-business-logic/` |

The implementation separates HTTP handling, business rules, and persistence into dedicated layers:

| Layer | Responsibility | Directory/File |
| --- | --- | --- |
| **Routes** | Endpoint and method mapping | `routes/` |
| **Controllers** | Request/response orchestration | `controllers/` |
| **Business Logic** | Rule validation and order processing | `logic/orderLogic.js` |
| **Data Layer** | Prisma queries and updates | `models/productModel.js` |
| **Schema/Migrations** | Database model + migration history | `prisma/` |

Request flow: **Client (`curl`) → Express API → Business Logic → Prisma/SQLite**

---

## Prerequisites

- **Node.js 18+**
- **npm** (bundled with Node.js)
- **curl** (Windows `cmd` supported)
- **Git**

Optional:

- **Postman** or **Insomnia**

---

## Quick Start

### 1. Open project folder

```bash
cd lab3-business-logic
```

### 2. Install dependencies

```bash
npm install
```

### 3. Run migration and seed

```bash
npx prisma migrate dev --name init
npx prisma db seed
```

This project uses **Prisma seeding** (`prisma/seed.js`) to insert the default product dataset.

### 4. Start API server

```bash
npm run dev
```

- 🌐 **API Base URL** → [http://localhost:3000](http://localhost:3000)

### 5. Reset database to default seeded state

Use this when you want to return to a clean lab state:

```bash
npx prisma migrate reset --force
```

This command will:

- Drop and recreate the SQLite database
- Re-apply migrations
- Run the Prisma seed script automatically

---

## Database Seed Data

Seed data is inserted through Prisma (`prisma/seed.js`) and includes:

| ID | Name | Description | Price | Stock |
| --- | --- | --- | ---: | ---: |
| 1 | Laptop | High-performance | 999.99 | 5 |
| 2 | Mouse | Wireless optical | 29.99 | 20 |
| 3 | Keyboard | Mechanical RGB | 79.99 | 10 |
| 4 | Monitor | 27-inch 4K display | 399.99 | 3 |
| 5 | Headset | Noise-cancelling | 149.99 | 0 |

If needed, you can run seed only (without reset):

```bash
npx prisma db seed
```

---

## Running the Service Manually

If you prefer explicit commands in sequence:

```bash
npm install
npx prisma migrate dev --name init
npx prisma db seed
node server.js
```

---

## 5. Activity: Implement Business Logic API

This repository implements a product ordering API with these endpoints:

- `GET /api/products` — List all products
- `GET /api/products/:id` — Get one product by ID
- `POST /api/orders` — Place an order with rule validation

Business rules are enforced in order:

1. Product must exist
2. Quantity is required
3. Quantity must be positive
4. Quantity must be a whole number
5. Product must not be out of stock
6. Quantity must not exceed stock

---

## 6. Testing Using curl

### Setup and Run

```bash
npm install
npx prisma migrate dev --name init
npx prisma db seed
npm run dev
```

Server URL: `http://localhost:3000`

### API Functionality curl Commands

Use these to verify normal API behavior only.

```bat
# 1) List all products (should return 200 with product array)
curl -s http://localhost:3000/api/products

# 2) Get one existing product (should return 200)
curl -s http://localhost:3000/api/products/1

# 3) Place a valid order (should return 200 Order successful)
curl -s -X POST http://localhost:3000/api/orders -H "Content-Type: application/json" -d "{\"productId\":1,\"quantity\":2}"

# 4) Verify stock changed after valid order (should reflect reduced stock)
curl -s http://localhost:3000/api/products/1
```

## 7. Edge Case Testing

| Test Case            | HTTP Code | Expected Result |
| -------------------- | --------- | --------------- |
| Invalid product ID   | `404`     | Error response  |
| Quantity missing     | `400`     | Invalid request |
| Quantity = 0         | `400`     | Invalid request |
| Negative quantity    | `400`     | Error           |
| Non-integer quantity | `400`     | Error           |
| Stock exceeded       | `400`     | Order rejected  |
| Out-of-stock product | `400`     | Order rejected  |
| Valid order          | `200`     | Stock updated   |

### Edge Case curl commands

Use these only for business-rule violation testing (error handling).

```bat
# Invalid product ID -> 404 NOT_FOUND
curl -s -X POST http://localhost:3000/api/orders -H "Content-Type: application/json" -d "{\"productId\":999,\"quantity\":1}"

# Quantity missing -> 400 VALIDATION_ERROR
curl -s -X POST http://localhost:3000/api/orders -H "Content-Type: application/json" -d "{\"productId\":1}"

# Quantity = 0 -> 400 VALIDATION_ERROR
curl -s -X POST http://localhost:3000/api/orders -H "Content-Type: application/json" -d "{\"productId\":1,\"quantity\":0}"

# Negative quantity -> 400 VALIDATION_ERROR
curl -s -X POST http://localhost:3000/api/orders -H "Content-Type: application/json" -d "{\"productId\":1,\"quantity\":-3}"

# Non-integer quantity -> 400 VALIDATION_ERROR
curl -s -X POST http://localhost:3000/api/orders -H "Content-Type: application/json" -d "{\"productId\":1,\"quantity\":2.5}"

# Quantity exceeds stock -> 400 VALIDATION_ERROR
curl -s -X POST http://localhost:3000/api/orders -H "Content-Type: application/json" -d "{\"productId\":1,\"quantity\":9999}"

# Out-of-stock product -> 400 VALIDATION_ERROR
curl -s -X POST http://localhost:3000/api/orders -H "Content-Type: application/json" -d "{\"productId\":5,\"quantity\":1}"
```

---

## Project Structure

```text
lab3-business-logic/
├── controllers/
├── logic/
├── middleware/
├── models/
├── prisma/
│   ├── migrations/
│   ├── schema.prisma
│   └── seed.js
├── routes/
├── package.json
├── prisma.config.ts
├── server.js
└── README.md
```

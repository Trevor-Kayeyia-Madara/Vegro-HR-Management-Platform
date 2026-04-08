# Vegro HR

Portfolio-grade HR platform built for long-term maintainability: a Laravel API backend, a (WIP) Vite frontend workspace, and a codebase structure that keeps business logic testable and easy to extend.

![License](https://img.shields.io/badge/License-MIT-black)
![Backend](https://img.shields.io/badge/Backend-Laravel%2012-red)
![PHP](https://img.shields.io/badge/PHP-8.2-blue)
![Frontend](https://img.shields.io/badge/Frontend-Vite%20%2B%20Vue-informational)

## Table of Contents

- [Why this repo exists](#why-this-repo-exists)
- [Architecture](#architecture)
  - [System diagram (Mermaid)](#system-diagram-mermaid)
  - [Maintainability principles](#maintainability-principles)
- [Project structure](#project-structure)
- [Developer runbook](#developer-runbook)
  - [Backend (Laravel API)](#backend-laravel-api)
  - [Frontend (Vite workspace)](#frontend-vite-workspace)
  - [Secrets & environment management](#secrets--environment-management)
- [Scaling & performance](#scaling--performance)
- [Contributing](#contributing)
- [Roadmap](#roadmap)
- [License](#license)
- [Author](#author)

## Why this repo exists

Vegro HR is a learning + portfolio system that is intentionally structured like a production app:

- Clear boundaries between HTTP/controllers, services, repositories, and persistence models
- A multi-tenant API surface with role/permission checks and audit-friendly activity logs
- Room for growth: reporting, compliance workflows, ATS modules, dashboards, and a dedicated frontend workspace

## Architecture

### System diagram (Mermaid)

```mermaid
flowchart LR
  U((User)) -->|Browser| FE[Frontend\nVite workspace]

  FE -->|HTTPS / JSON| API[Vegro HR API\nLaravel]

  API -->|Eloquent ORM| DB[(MySQL)]
  API -->|Events / jobs| Q[Queue]
  Q --> W[Queue workers]
  API --> LOG[Activity logs\n(audit trail)]

  subgraph Backend
    API
    Q
    W
  end
```

### Maintainability principles

- **Layered flow:** `Controllers -> Services -> Repositories/Models` to keep business rules out of HTTP glue.
- **Single-responsibility services:** domain operations live in `vegro-hr/app/Services/`.
- **Testability:** business logic is structured to be callable without a UI, and verified via `vegro-hr/tests/`.
- **API-first:** REST-ish endpoints in `vegro-hr/routes/api.php`, designed to serve both server-rendered and SPA clients.

## Project structure

High-level map (trimmed to the pieces contributors need most):

```text
.
├─ vegro-hr/                         # Laravel backend (API + core domain)
│  ├─ app/
│  │  ├─ Http/Controllers/           # Request/response layer (thin)
│  │  ├─ Services/                   # Business workflows (core logic)
│  │  ├─ Repositories/               # Data access patterns
│  │  └─ Models/                     # Eloquent models
│  ├─ routes/                        # API routes and middleware wiring
│  ├─ database/
│  │  ├─ migrations/                 # Schema evolution
│  │  └─ seeders/                    # Sample/dev data
│  ├─ tests/                         # PHPUnit tests
│  ├─ docker/                        # Container tooling (optional)
│  ├─ docker-compose.yml
│  └─ vegro-hr-frontend/             # UI workspace (separate app)
├─ ROADMAP.md
├─ LICENSE
└─ README.md
```

## Developer runbook

### Backend (Laravel API)

From repo root:

```bash
cd vegro-hr
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

On Windows (PowerShell), the copy step can be:

```bash
copy .env.example .env
```

Run the dev stack (API server + queue listener + logs + Vite):

```bash
composer run dev
```

Run tests:

```bash
composer test
```

Lint/format (PHP):

```bash
./vendor/bin/pint
```

Optional: generate OpenAPI/Swagger docs (if configured for your env):

```bash
php artisan l5-swagger:generate
```

### Frontend (Vite workspace)

```bash
cd vegro-hr/vegro-hr-frontend
npm install
npm run dev
```

Run frontend quality checks:

```bash
npm run lint
npm run test:unit
npm run test:e2e
```

### Secrets & environment management

- **Never commit secrets**: local overrides belong in `vegro-hr/.env` and `vegro-hr/vegro-hr-frontend/.env`.
- **Use the templates**:
  - Backend: `vegro-hr/.env.example`, plus optional `vegro-hr/.env.demo.example` and `vegro-hr/.env.live.example`
  - Docker profile: `vegro-hr/.env.docker`
- **Rotation-friendly config**: prefer environment variables for credentials (DB, mail, queue, API keys).

## Scaling & performance

This project is intentionally shaped for “real app” scaling patterns:

- **Stateless API scaling**: run multiple API instances behind a load balancer; keep auth tokens client-side.
- **Queues for heavy work**: offload email, report generation, and long-running workflows to queue workers (scale workers horizontally).
- **Database hygiene**: paginate list endpoints, avoid N+1 queries, and add indexes as data volume grows.
- **Rate limiting**: use throttles on auth and public endpoints to reduce abuse.
- **Observability**: keep audit logs for sensitive actions; emit structured logs for debugging and tracing.

## Contributing

Contributions are welcome. Please read `CONTRIBUTING.md` for:

- Local setup expectations
- Coding standards
- PR process and checks to run before opening a pull request

## Roadmap

See `ROADMAP.md`.

## License

MIT License — free for personal and commercial use.

## Author

Trevor Madara

- LinkedIn: `https://www.linkedin.com/in/trevor-madara/`
- GitHub: `https://github.com/Trevor-Kayeyia-Madara`

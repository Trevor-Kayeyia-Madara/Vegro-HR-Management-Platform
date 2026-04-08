# Contributing to Vegro HR

Thanks for taking the time to contribute. This repository is built as a maintainable, production-style portfolio project, so consistency and developer experience matter.

## Before you start
- Search existing issues/PRs (if any) to avoid duplicating work.
- Prefer small, focused PRs: one feature/fix per PR.
- Do not commit secrets (`.env` files, API keys, tokens).

## Local development

### Backend (Laravel API)
```bash
cd vegro-hr
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
composer run dev
```

On Windows (PowerShell), the copy step can be:
```bash
copy .env.example .env
```

### Frontend (Vite workspace)
```bash
cd vegro-hr/vegro-hr-frontend
npm install
npm run dev
```

## Quality gates (run these before opening a PR)

### Backend checks
```bash
cd vegro-hr
composer test
./vendor/bin/pint
```

### Frontend checks
```bash
cd vegro-hr/vegro-hr-frontend
npm run lint
npm run test:unit
npm run test:e2e
```

## Coding standards
- Keep controllers thin; put business workflows in `vegro-hr/app/Services/`.
- Prefer explicit, readable code over “clever” abstractions.
- Keep API responses consistent and paginated for list endpoints.
- Match existing naming and folder conventions; avoid drive-by refactors.

## Pull request process
1. Create a branch from `main` (or the default branch).
2. Ensure tests and linters pass locally.
3. Open a PR with:
   - What changed (bullets)
   - Why it changed (context / issue link)
   - How to test (exact commands)
4. Be responsive to review feedback and keep commits tidy.

## Reporting security issues
If you believe you’ve found a security issue, avoid opening a public issue with exploit details. Instead, contact the repository owner directly.

# Docker Setup

## Services

- Backend API (Laravel + Nginx): `http://localhost:8000`
- Frontend (Vite dev server): `http://localhost:5173`
- MySQL: `localhost:3307`

## First run

```bash
docker compose --env-file .env.docker up --build -d
```

Run migrations and seeders:

```bash
docker compose exec app php artisan migrate --seed
```

## Stop

```bash
docker compose down
```

## Useful commands

```bash
# View logs
docker compose logs -f app

# Run tests
docker compose exec app php artisan test

# Rebuild containers
docker compose --env-file .env.docker up --build -d
```

## Notes

- `app`, `queue`, and `scheduler` share the same code and vendor volume.
- Frontend API base URL is set to `http://localhost:8000` in compose.
- If you already run MySQL locally on `3306`, the container uses `3307` to avoid conflicts.

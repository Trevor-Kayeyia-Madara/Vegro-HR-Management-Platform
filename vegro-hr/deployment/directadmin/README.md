# DirectAdmin deployment packages

This package builder produces two ZIP files for standard DirectAdmin shared hosting:

- `vegro-hr-backend-directadmin.zip` contains `laravel/` and `public_html/`. Laravel source, `vendor`, `storage`, and `.env` remain outside the web root.
- `vegro-hr-frontend-directadmin.zip` contains the compiled Vue application in `public_html/` with an SPA rewrite rule.

## Build the packages

Run from the repository root. The API URL is embedded in the frontend bundle at build time.

```sh
./make-deployment-archives.sh https://api.example.com
```

The command builds the frontend first, then creates both archives. It requires Node/npm, the frontend dependencies, PHP/Composer dependencies already present in the project, and the `zip` command. The archives are written to `deployment/directadmin/release/`.

The ZIP files are placed in `deployment/directadmin/release/`. Do not commit or distribute a generated backend package as a substitute for secret management: it intentionally contains application dependencies, but no `.env` file.

## Backend: API subdomain

Create an API domain/subdomain in DirectAdmin, for example `api.example.com`. Extract `vegro-hr-backend-directadmin.zip` in that domain's directory so it has this exact shape:

```text
domains/api.example.com/
├── laravel/
└── public_html/
```

Copy `laravel/.env.example` to `laravel/.env`, then set the production URL/database credentials in DirectAdmin File Manager. The packaged entry point also reuses an existing `.env` one level above `public_html` if that is where your current deployment stores it. Ensure `storage/` and `bootstrap/cache/` are writable by PHP (usually permissions `775`; use the host's recommended ownership/permissions if that is insufficient).

No SSH is required. Open the temporary helper directly:

```text
https://api.example.com/deploy.php
```

Use the buttons in this order: **Key** (new installations only), **Migrate**, **Seed** (new installations only), **Storage**, then **Clear**. Use the **Clear** action after any later file upload to discard Laravel's cached configuration, routes, and views. Finally, delete `public_html/deploy.php` using DirectAdmin File Manager. This helper has no authentication by design, so leaving it online allows anyone to run deployment actions.

The bundled `public_html/index.php` is intentionally different from Laravel's normal index: it boots `../laravel`. Do not replace it with the repository's `public/index.php`.

Your hosting account must provide PHP 8.2+ with `pdo_mysql`, `mbstring`, `bcmath`, `fileinfo`, `openssl`, `tokenizer`, `xml`, `ctype`, and `zip`. Configure a DirectAdmin cron job for Laravel scheduling if it is used:

```text
* * * * * cd /home/ACCOUNT/domains/api.example.com/laravel && php artisan schedule:run >> /dev/null 2>&1
```

If the queue driver is changed from `sync`, run a supported worker process or use a cron-based queue worker as allowed by the host.

## Frontend: app subdomain

Create a separate frontend domain/subdomain such as `app.example.com`, then extract `vegro-hr-frontend-directadmin.zip` in that domain's directory:

```text
domains/app.example.com/
└── public_html/
```

The frontend archive is static; it needs no Node.js or build tools on DirectAdmin. Its `.htaccess` serves `index.html` for Vue client-side routes.

## Before going live

- Point DNS and HTTPS certificates at both domains.
- Set `APP_URL` to the API origin and `FRONTEND_URL` to the app origin in the backend `.env`.
- Configure the application's CORS/session settings to allow the frontend origin.
- Never upload `.env`, `storage/logs`, or database backups to `public_html`.

# Laragon installation

## Requirements

- PHP 8.2 or newer compatible with the locked Composer dependencies.
- PHP extensions: PDO, pdo_mysql, mbstring, openssl, tokenizer, XML, ctype, fileinfo, curl, intl and GD. GD safely decodes/re-encodes uploaded logos. pdo_sqlite is used for the default test suite.
- Composer 2; MySQL 8; a current browser.
- Apache/Nginx for a persistent Laragon virtual host, or `php artisan serve` for local testing.

No Redis, Node, npm, cloud service or frontend build is needed for this MVP. CSS, JavaScript and the sample SVG are in `public/`.

## Fresh database setup

1. Start Laragon’s MySQL service.
2. Open a Laragon terminal in the project folder.
3. Create an empty `carwash_management` database with `utf8mb4` character encoding through HeidiSQL, phpMyAdmin or MySQL CLI. If that name is already used, choose a new name; do not reuse a database holding unrelated data.
4. Run:

```powershell
composer install
Copy-Item .env.mysql.example .env
php artisan key:generate
```

5. Edit `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=carwash_management
DB_USERNAME=root
DB_PASSWORD=
APP_URL=http://127.0.0.1:8017
APP_ENV=local
APP_DEBUG=false
```

Laragon often uses `root` with a blank password for local testing. Use your actual configured credentials. Use a dedicated restricted database user and password on shared or production installations.

6. Run:

```powershell
php artisan migrate --seed
php artisan carwash:admin owner@example.com --name="Business Owner"
php artisan serve --host=127.0.0.1 --port=8017
```

The admin command prompts privately for a password of at least 12 characters. Sign in at `http://127.0.0.1:8017`. Before your first real order, open Settings and select your currency, timezone, branding and tax rate. Currency and timezone lock after the first order or expense.

## Optional local demo

On a new local installation, use `php artisan carwash:demo` to add fictional customers, vehicles, wash orders, payments and inventory. If no administrator exists, it generates a unique demo password in `storage/app/private/local-access.txt`. Use the credentials in that file, then change the password in My account and delete the file. No universal default password is included.

For a clean real installation after exploring the demo, create a **new empty database**, update `.env`, run `php artisan config:clear`, migrate and seed, and create your own admin. Retain the demo database if you want to revisit it. Never use `migrate:fresh` on real business data.

## Laragon virtual host

Configure Apache/Nginx’s document root to this project’s **`public` folder**, never the project root. Enable URL rewriting. If using Laragon auto virtual hosts, inspect the generated virtual host and correct its document root. Match `APP_URL` to your chosen local hostname, clear cached configuration, then restart the web server.

The app serves validated logo files through a rate-limited route limited to the current business logo, so `storage:link` is not required. Keep the entire `storage` folder private from direct web requests.

## Separate MySQL test database

Create an empty `carwash_management_test` database. `phpunit.mysql.xml` forces MySQL and this database name; it inherits the host/user/password from `.env`. The test database user must have permission to create and drop tables **in the test database**.

```powershell
php vendor/phpunit/phpunit/phpunit -c phpunit.mysql.xml --testdox
```

This suite uses `RefreshDatabase`: it recreates the test schema and rolls test transactions back. Do not point the test configuration at any business database. The standard `php artisan test --compact` uses an isolated in-memory SQLite database instead.

## Troubleshooting

| Symptom | Action |
|---|---|
| Connection refused / SQLSTATE 2002 | Start MySQL; verify DB_HOST and DB_PORT. |
| Access denied | Correct DB_USERNAME / DB_PASSWORD and permissions. |
| Unknown database | Create the configured database first. |
| Missing table | Run `php artisan migrate --seed`. |
| Missing application key | Run `php artisan key:generate` once for a new installation. Preserve the key afterward. |
| Old settings/environment | Run `php artisan optimize:clear`. |
| Login returns 419 | Refresh the login page; use a consistent hostname; allow cookies. On HTTP localhost set SESSION_SECURE_COOKIE=false. |
| Login succeeds then signs out | Check SESSION_DRIVER=database, the sessions table, and the configured APP_KEY. |
| Logo upload fails | Check GD and fileinfo; use PNG/JPG/WebP under 2 MB and 2000 × 2000 px. |
| Unexpected old page at localhost | Use port 8017 or a new origin; an older application/service worker may be cached at port 8000. |
| Port occupied | Choose another port and update APP_URL. |

## Routine backup and restore

Use MySQL Workbench/HeidiSQL or `mysqldump` to back up all application database tables. Also back up `storage/app/private` (logos) and `.env` (application key and configuration) to encrypted, access-controlled storage. Avoid putting database passwords directly in shell arguments; use the client’s password prompt or secure option file.

Before upgrades, back up and test restoration into a new empty database. Restore a SQL dump through MySQL tooling, copy private logo files and the original application key, update `.env`, clear configuration caches, and verify login, balances and receipts. Restore to a new database first so current records remain available for recovery. Retain backups outside the web root and follow your business’s data-retention policy.

# AquaFlow · Carwash Management System

A local-first, single-branch carwash MVP built with Laravel 12, PHP 8.2+, MySQL 8 and Blade. Responsive screens, a small vanilla JavaScript layer and local assets keep deployment simple. **No Node build or internet connection is required to use the installed application.**

## Start here

- [Laragon installation](docs/INSTALLATION.md)
- [User manual and role permissions](docs/USER-MANUAL.md)
- [Architecture and developer guide](docs/DEVELOPER.md)
- [HTTP/API reference](docs/API.md)
- [Security and deployment](docs/SECURITY.md)
- [Verification and MVP scope](docs/VERIFICATION.md)

## Included

Dashboard; customer and vehicle records; services and pricing; future arrival bookings and walk-ins; wash bays; staff assignment; waiting → washing → ready → completed workflow; partial payments; refunds; printable receipts; inventory with stock history and low-stock indicators; expenses; cash reports and CSV download; staff account administration; four server-enforced roles; activity log; password changes; configurable business name, logo, tagline, address, contact information, receipt footer, currency, timezone and tax rate.

Money is stored in integer minor units. Orders retain their service names, prices, discounts and tax amounts. Payments and stock adjustments run in database transactions with row locks. Financial records are retained instead of silently deleted.

## Quick setup

1. Install PHP 8.2+, Composer and MySQL 8 (Laragon provides PHP/MySQL).
2. Run `composer install` from this folder.
3. Copy `.env.mysql.example` to `.env`, configure a new MySQL database and credentials.
4. Run `php artisan key:generate` and `php artisan migrate --seed`.
5. Run `php artisan carwash:admin you@example.com` and choose a password when prompted.
6. Run `php artisan serve --host=127.0.0.1 --port=8017` and open `http://127.0.0.1:8017`.

Alternatively, for a fictional demonstration, run `php artisan carwash:demo` instead of creating your own administrator. It writes randomly generated login details to `storage/app/private/local-access.txt` if it creates a demo administrator. It runs only in `APP_ENV=local`; it skips demo records if customers already exist. Do not add demo records to a real business database.

The workspace prepared during development uses the MySQL database `carwash_management`. The separate `carwash_management_test` database is disposable and is used only by the explicit MySQL test configuration.

## Testing

```powershell
php artisan test --compact
php vendor/phpunit/phpunit/phpunit -c phpunit.mysql.xml --testdox
composer audit --locked
php artisan view:cache
```

The default suite uses in-memory SQLite and does not erase the application database. The MySQL suite requires a **separate empty test database**, and recreates its tables. See installation instructions before running it.

## Boundaries

This MVP serves one business branch. It records payments collected externally; it does not process bank cards, store card details, send SMS, integrate payroll/accounting, offer customer self-service, implement memberships, or manage multiple branches. Bookings are arrival times, not automatic capacity reservations. Stock consumption is manual. No claim is made that software is immune to every attack or that a generic receipt meets every jurisdiction’s invoicing rules. Review the security and verification documents before real deployment.

The original AquaFlow SVG logo is a replaceable sample asset created for this project. No third-party logo license is needed.

# MVP scope and verification

Verified on 23 October 2026 with Windows, PHP 8.2.28, Laravel 12.70.1 and Laragon MySQL 8.4.3.

## Feature coverage

| Area | Included behavior |
|---|---|
| Business identity | Name, original sample logo, raster-logo upload/removal, tagline, address, phone/email, receipt footer |
| Regional setup | Supported two-decimal currencies, business timezone, configurable tax; currency/timezone lock after transactions |
| Authentication | Login/logout, hashed passwords, throttling, active accounts, own password change, admin-assisted reset |
| Authorization | Admin, manager, cashier and washer; server-side gates and assigned-order restrictions |
| Customers | Create, edit, search, pagination, contact details and notes |
| Vehicles | Owner association, unique uppercase plate, make/model, size and color |
| Service catalog | Prices, descriptions, estimated durations, activation/deactivation |
| Wash floor | Bay configuration, staff assignment, occupancy validation, forward status workflow |
| Orders | Multiple service snapshots, discount/tax, notes, walk-ins and future arrival bookings |
| Payments | Partial collections, four payment methods, references, duplicate-submit protection, manager/admin refunds |
| Receipts | Branded print layout and browser Print / Save PDF |
| Inventory | Item/unit/threshold, signed stock adjustments, negative-balance prevention, movement history, low-stock notice |
| Expenses | Description, category, amount/date, create/edit/search |
| Reports | Date filters, collections/refunds/expenses, method breakdown, daily totals, all-time balances, CSV export |
| Accountability | Searchable operational records and paginated key-action log |
| Errors | Validation summaries, status flashes, access/expired-session/not-found/rate-limit/server screens |
| Documentation | Setup, user manual, architecture, HTTP/API, security/deployment and this scope report |

## Checks performed

- **Automated feature suite:** role restrictions, all main pages, authentication, inactive accounts, administrator self-protection, server-computed prices, snapshot stability, invalid discounts/services, order transitions, bay/staff conflicts, completion balance requirements, payment replay, overpayments, refunds, negative stock, stock history, logo validation, escaped output, response headers, reports/CSV, JSON summary, configuration locks, future booking/check-in and login throttling.
- **SQLite suite:** 22 tests, 160 assertions, isolated in-memory test database.
- **MySQL suite:** 22 tests, 160 assertions, separate `carwash_management_test` database; real migrations, foreign keys and transactional workflows.
- **Dependency audit:** Composer audit reported no known vulnerability advisories at verification time.
- **Template and route checks:** compiled Blade views and listed application routes.
- **Real HTTP CSRF check:** unauthenticated login POST without a token returned 419.
- **Browser workflow:** sign-in → dashboard → create two-service order with discount → assign active bay and staff → record fictional cash payment → ready → completed. Saved order and ledger showed the expected PHP 550.00 total and zero balance. Receipt content was inspected in the browser.
- **Visual check:** desktop dashboard and receipt screens inspected. Responsive CSS is implemented, but there is no claim of exhaustive cross-device/browser or accessibility certification.

The dashboard screenshot in `docs/images/dashboard.png` uses fictional local data. The browser verification created fictional order CW-00013 in the local demo database.

## Intentionally outside this MVP

Multi-branch tenancy, customer-facing booking portal, automatic scheduling/capacity reservation, SMS/email reminders, memberships/loyalty/subscriptions, gift cards, payment gateway integration, automatic inventory recipes, payroll/commissions/attendance, vendor procurement, bank reconciliation, accounting integration, immutable fiscal invoices/credit notes, custom role builders, MFA and public integration tokens.

This is an operational single-branch MVP. No jurisdiction, compliance standard or accounting integration was specified; this project makes no certification claim. A production deployment still needs server/TLS hardening, backup/restore practice, business-specific invoice review, security review and load testing.

# Security and deployment

## Implemented controls

- **Authentication:** hashed passwords, no public registration, minimum 12-character passwords on account administration and password changes, current-password verification for self-service changes.
- **Session safety:** login session regeneration; logout invalidation and CSRF-token regeneration; encrypted database-backed sessions in the supplied environment template; HttpOnly and SameSite=Lax cookies. Authenticated session middleware checks password-hash changes and invalidates previous sessions on their next request. Disabled accounts are signed out on their next request.
- **RBAC:** fixed, default-deny permission map enforced by Gates and controllers; administrator-only account/role and settings management. Wash team accounts can access/update only their assigned washes. Self-demotion/disable of the active administrator is blocked.
- **CSRF:** Laravel’s web CSRF protection covers state-changing routes. A real HTTP POST without a token was checked and returned 419.
- **Login abuse:** generic invalid-login messages, per-email/IP failed-attempt throttling, additional route throttling and authenticated request limits.
- **Injection protection:** Eloquent/query-builder parameter binding for user values, fixed module/field whitelists and explicit request validation. No user input is interpolated into raw SQL expressions.
- **XSS protection:** Blade escaped output; local scripts and styles; Content Security Policy disallows inline scripts, third-party resources, framing and foreign form targets.
- **Uploads:** only raster logos, file/MIME/size/dimension validation, GD decoding and PNG re-encoding, UUID filenames, private storage and restricted current-logo image serving. User-controlled SVG, HTML, executable names and arbitrary storage paths are not accepted.
- **Money and stock:** integer cents, server-side pricing, validated two-decimal input, database transactions and MySQL row locks around balance checks and shared resources. Payment UUIDs prevent duplicate form replay. Negative stock, overpayment and excessive refund amounts are rejected.
- **History:** financial entries retained; refunds use negative ledger entries. Key operational/settings/account actions are logged with staff identity and source IP. There is no application route to edit audit logs or delete payment entries.
- **Response headers:** Content-Security-Policy, X-Frame-Options=DENY, X-Content-Type-Options=nosniff, Referrer-Policy, restrictive Permissions-Policy, no-store caching and HSTS on HTTPS responses.
- **Errors:** production-safe environment template with APP_DEBUG=false; friendly 403/404/419/429/500 screens; validation messages preserve non-sensitive input.

These controls reduce common risks. They do not establish immunity to all attacks, a penetration-test certification, regulatory compliance or secure server configuration by themselves.

## Production preparation

Use a maintained PHP/web server/MySQL installation and TLS. Serve only `public/`, keep the project root and storage private, restrict filesystem permissions, and use a dedicated database user with permissions only on the application database. The local Laragon root/blank-password example must not be used on a shared or internet-facing server.

Set appropriate production values:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-business.example
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
LOG_LEVEL=warning
```

Install the locked production dependencies with `composer install --no-dev --optimize-autoloader`. Back up before running `php artisan migrate --force`. Run `php artisan config:cache` and `php artisan view:cache`. If deploying behind a proxy/load balancer, configure only trusted proxy addresses so HTTPS detection and source IPs are reliable. Do not expose the PHP development server publicly.

Use strong individual passwords and least-privilege roles. Change demo credentials and remove `storage/app/private/local-access.txt` before sharing or deploying. Prefer a fresh database for real operation. Keep `.env` and private data out of Git and web-accessible backups. Preserve the application key: rotating it without planning can invalidate encrypted sessions and other encrypted data.

Schedule encrypted backups and verify restoration. Monitor application/web-server logs and database health. Regularly run dependency security audits and test dependency updates on a separate installation. Establish business-specific data retention, user access review and incident response procedures.

## Known security and operational boundaries

- No MFA, SSO, recovery emails, device/session management UI, IP allowlist or external security monitoring integration is implemented. Password recovery is administrator-assisted.
- The audit log records action/subject/actor/time/IP, not a full before/after journal or tamper-evident external ledger. Database administrators can alter underlying records.
- Rate limits are application-level and do not replace network DDoS protection or web-server request-size/time limits.
- The system stores customer contact data. Access is restricted, but database-at-rest encryption, backup protection and retention remain deployment responsibilities.
- The application records payment methods/references only. It does not store card data or act as a payment processor. Free-text fields must not be used for card numbers, passwords or other secrets.
- Localhost HTTP is for local testing. Secure cookies and HTTPS are needed for a network deployment.
- Simultaneous contention/load has not been benchmarked. Locks and database invariants have functional coverage, but an independent security assessment and concurrency/load test remain recommended before significant production use.
- Tax amounts are configurable operational calculations. Receipt and accounting requirements must be adapted to the business’s jurisdiction and practices before formal fiscal use.

## If an account is compromised

An administrator should disable the account immediately, review Activity log and relevant orders/payments, reset credentials, and investigate server/database access. Preserve logs and backups before making corrective changes. Restore from a verified backup only after identifying the incident scope. There is no one-click destructive “reset” function in the application.

# HTTP and API reference

## Authentication and conventions

This MVP uses first-party browser sessions. It does **not** expose a bearer-token or third-party integration API. The small JSON reporting endpoint uses the same session and permissions. All paths are relative to your configured APP_URL. Send `Accept: application/json` for JSON validation/authorization responses; browser forms receive redirects and readable flash errors.

Fetch `/login`, submit its CSRF token and credentials to `POST /login`, then retain the encrypted session cookie. All mutation requests need `_token` or an `X-CSRF-TOKEN` header from the current session. Laravel also provides the normal XSRF cookie convention. Do not disable CSRF middleware for integrations. Build a separate versioned/token-authenticated API if external clients are added.

Successful browser writes redirect; invalid input returns validation errors (JSON: 422). Unauthenticated JSON requests return 401; forbidden requests return 403; missing records return 404; expired CSRF/session returns 419; rate limits return 429. General authenticated traffic is limited to 120 requests/minute per user. Login additionally uses route throttling and a 5-failure email/IP lockout window of 60 seconds.

## JSON summary

`GET /api/v1/summary` — Admin/Manager only, authenticated session required.

Example response (integer monetary minor units):

```json
{
  "date": "2026-10-23",
  "orders_today": 6,
  "net_collected_cents": 155000,
  "queue": 4
}
```

The date follows the configured business timezone. Queue counts Waiting, Washing and Ready orders. Net collected means payments less refunds received today.

## Browser workflow endpoints

| Method/path | Purpose | Required permission |
|---|---|---|
| GET `/login` | Login form | Guest |
| POST `/login` | Sign in | Guest |
| POST `/logout` | Invalidate session | Authenticated |
| GET `/` | Dashboard (wash team redirects to own queue) | Authenticated |
| GET `/orders` | Paginated orders; `status`, `q`, `page` | queue |
| GET `/orders/new` | Order form | operations |
| POST `/orders` | Create order/booking | operations |
| GET `/orders/{id}` | Order details | queue + assignment restriction for washer |
| POST `/orders/{id}/status` | Forward status transition | queue + assignment/transition rules |
| POST `/orders/{id}/payments` | Payment/refund entry | finance; refund requires admin/manager |
| GET `/orders/{id}/receipt` | Print view | finance |
| GET `/manage/{module}` | Paginated/searchable master records | Module permission |
| GET `/manage/{module}/new` | Add form | Module permission |
| POST `/manage/{module}` | Create master record | Module permission |
| GET `/manage/{module}/{id}/edit` | Edit form | Module permission |
| PUT `/manage/{module}/{id}` | Update permitted fields | Module permission |
| POST `/inventory/{id}/stock` | Signed quantity adjustment | inventory |
| GET `/inventory/{id}/movements` | Paginated stock history | inventory |
| GET `/reports` | Cash reports; `from`, `to`, `export=csv` | reports |
| GET `/audit` | Paginated activity log | audit |
| GET `/settings` / PUT `/settings` | Business settings | settings |
| GET `/branding/logo` | Current uploaded business logo only | Public, rate-limited |
| GET `/account` | Own profile/password form | Authenticated |
| PUT `/account/password` | Change own password | Authenticated + current password |

Modules: `customers`, `vehicles`, `services`, `bays`, `inventory`, `expenses`, `users`. Their fields and exact validation rules are defined in `config/carwash.php`. Unknown module names return 404. There are no financial hard-delete endpoints.

### Create order fields

`vehicle_id` (existing ID), `services[]` (1–20 distinct active service IDs), `discount` (optional nonnegative amount, at most subtotal, ≤2 decimal places), `scheduled_at` (optional future datetime in business timezone), `notes` (optional, ≤2000 chars). Server computes totals and snapshots service details. No client-supplied total is accepted.

### Status fields

`status`: `waiting`, `washing`, `ready`, `completed` or `cancelled`, subject to the state machine. Starting Washing additionally requires `bay_id` and `assigned_to`. Wash team accounts can only mark their assigned wash Ready/Completed. Bay and staff must be active and not currently Washing another order.

### Payment fields

`amount` (positive, at most balance, ≤2 decimal places), `method` (`cash`, `card`, `bank`, `ewallet`), `reference` (optional ≤255 chars), `idempotency_key` (UUID). Optional `refund=1` requests a refund: requires manager/admin, a reference/reason and amount no greater than net collected. Same-key/same-details replay is a no-op; changed order/amount/method with the same key is rejected. The application does not execute an external charge or refund.

### Stock fields

`quantity` (signed, nonzero, ≤2 decimal places), `reason` (required, ≤255 chars). The resulting balance cannot be negative.

### Password fields

`current_password`, `password` (12–128 chars, different from current password), `password_confirmation`. Administrator account edits accept an optional new password plus confirmation. Roles and active flags are writable only through the administrator-controlled users module.

### Settings upload

Use multipart form data with `_method=PUT`, CSRF token, business fields and optional `logo` (PNG/JPG/WebP, ≤2 MB, dimensions ≤2000 × 2000). `remove_logo=1` restores the static sample when no new upload is provided. Currency and timezone are locked after the first order or expense.

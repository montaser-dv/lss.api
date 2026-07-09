# Trakmile API Reference

**Base URL:** `https://trakmile.com`  
**API Version:** `2.0`  
**Updated:** 2026-07-09

---

## Public API (`/api/`)

### `GET /api/ping.php`

Health check.

**Response 200:**
```json
{
  "ok": true,
  "service": "trakmile-api",
  "php": "8.2.x",
  "api_version": "2.0",
  "timestamp": "2026-07-09T12:00:00+00:00",
  "endpoints": {
    "platform": "/api/platform.php",
    "submit_quote": "/api/submit_quote.php"
  }
}
```

---

### `GET /api/platform.php`

Platform metadata: features, integrations, stats, endpoint catalog.

**Query params:**

| Param | Values | Default |
|-------|--------|---------|
| `lang` | `ar`, `en` | `ar` |

**Response 200:** JSON with `platform`, `features`, `integrations`, `stats`, `endpoints`, `seo`, `docs`.

**Example:**
```
GET /api/platform.php?lang=en
```

---

### `POST /api/submit_quote.php`

Submit a **consultation request** (طلب استشارة).

**Headers:** `Content-Type: application/json` or `multipart/form-data`

**Body fields:**

| Field | Required | Description |
|-------|----------|-------------|
| `name` | Yes | Min 2 characters |
| `phone` | Yes | Saudi mobile: `5xxxxxxxx`, optional `+966` / `966` / `0` prefix |
| `email` | Yes | Valid email |
| `description` | No | Free text |
| `lang` | No | `ar` or `en` — UI language when submitted (default `ar`) |

**Success 200:**
```json
{ "success": true, "message": "submitted" }
```

**Validation error 422:**
```json
{ "success": false, "message": "validation_error", "fields": ["phone", "email"] }
```

**Server error 500:**
```json
{ "success": false, "message": "server_error", "error": "..." }
```

**CORS:** `Access-Control-Allow-Origin: *`  
**Preflight:** `OPTIONS` → `204`

---

### `GET /api/test_db.php` *(development)*

Database diagnostic JSON. Do not expose publicly in production.

---

### `GET /api/setup_db.php` *(development only)*

Runs `docs/DB/quote_tables.sql`. **Disable on production.**

---

## Admin Panel (`/admin/`)

Session-based HTML interface (not REST JSON).

| Route | Description |
|-------|-------------|
| `GET/POST /admin/login.php` | Admin authentication |
| `GET/POST /admin/index.php` | List/filter consultation requests |
| `GET /admin/logout.php` | End session |

**Quote status values:** `new`, `read`, `contacted`

---

## Mobile Courier API (`/mobile-api/`)

Multi-tenant: resolves `domain` → client database via `domains` table in `trak_db`.

### Authentication flow

1. `GET check_domain.php?mobile_domain={subdomain}`
2. `GET login.php?mobile_email=&mobile_pass=&mobile_domain=&mobile_token=` (FCM)

### Operations

| Endpoint | Method | Key params | Returns |
|----------|--------|------------|---------|
| `home.php` | GET | `ccode`, `domain`, `token`, `lat`, `lng` | Dashboard stats + GPS update |
| `getOrders.php` | POST | `ccode`, `domain`, `token`, `lang` | HTML table (active orders) |
| `getOrdershistory.php` | POST | same | HTML table (archived) |
| `orders.php` | GET | shell WebView, polls `getOrders.php` | HTML page |
| `history.php` | GET | shell WebView | HTML page |
| `openorder.php` | GET | `awb`, `ccode`, `domain`, `token` | Order detail + map |
| `confirmOrder.php` | POST | `awb`, `domain`, `token` | `1` or error code |
| `confirmOrderApi.php` | GET | `barcode`, `domain`, `token` | WebView postMessage |
| `orderAction.php` | POST | `awb`, `otype`, `comment` | Deliver / fail delivery |
| `update_password.php` | GET | `code`, `pass`, `mobile_domain` | JSON via WebView |

### Mobile login error codes

| Code | Meaning |
|------|---------|
| `4` | Invalid credentials |
| `9` | Account inactive |
| `500` | Server error |

---

## Database

### Marketing site (`trak_db`)

- `quote_requests` — consultation submissions
- `admin_users` — admin panel users
- `domains` — tenant registry for mobile-api

### Setup

```bash
mysql -u trak_user -p trak_db < docs/DB/quote_tables.sql
mysql -u trak_user -p trak_db < docs/DB/migrate_quote_lang.sql   # existing installs
```

---

## SEO & Static

| File | URL |
|------|-----|
| `robots.txt` | `/robots.txt` |
| `sitemap.xml` | `/sitemap.xml` |
| Arabic profile | `/docs/Trakmile-Overview.pdf` |
| English profile | `/docs/Trakmile-Overview-en.pdf` |

---

## Platform Features (marketing / product)

These are advertised on the landing page and returned by `platform.php`:

- Shipment management
- Driver & fleet tracking (live map)
- Multi-hub warehouses
- Built-in accounting
- AI assistant
- Driver mobile app + barcode scanner
- COD management & proof of delivery
- Integrations: Salla, Zid, Shopify, WooCommerce, REST API, SMS, WhatsApp

Full product operations run on **demo.trakmile.com** and per-client databases.

---

## Changelog

### v2.0 (2026-07-09)
- Added `GET /api/platform.php`
- Enhanced `GET /api/ping.php` with version info
- `submit_quote.php` accepts `lang` field
- Admin renamed to consultation requests (استشارة)
- Full API documentation
- Landing: AI, accounting, warehouses, SEO, live map mockup, sticky quote bar

### v1.0
- Initial quote form, admin panel, basic mobile-api

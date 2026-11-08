# AffiliateCore SaaS — White-Label Multi-Tenant Affiliate Management Platform

**Version:** 1.0.0  
**Stack:** Laravel 11 · PHP 8.2+ · Vue 3 · Inertia.js · Tailwind CSS  
**Architecture:** Zero-Dependency, Single-Database Multi-Tenant  

**Suggested GitHub topics:** `laravel`, `saas`, `affiliate-marketing`, `multi-tenant`, `vue3`, `inertiajs`, `tailwindcss`, `php`

---

## What / for whom

**What it is:** A white-label, multi-tenant affiliate management platform (Laravel + Vue) with click tracking, commissions, and PayPal payouts.

**Who it is for:** Agencies and SaaS resellers selling affiliate tooling to clients on shared hosting — not teams that already run a custom Node/Redis stack.

AffiliateCore SaaS is a production-ready, white-label affiliate marketing platform designed for B2B resale. It provides your clients with a complete affiliate management system — tracking links, click analytics, automated commission calculations, and PayPal mass payouts — all within a stunning dark-themed Enterprise UI.

**Built to survive extreme low-resource cPanel shared hosting.** No WebSockets. No Node.js daemons. No Redis. No external API dependencies. Just PHP + MySQL + Cron.

---

## Decisions / trade-offs

| Choice | Why | Trade-off |
|--------|-----|-----------|
| Single DB + tenant scope | Works on cheap cPanel MySQL | Not row-level security isolation for regulated tenants |
| Flat-file click buffer → cron ingest | Zero DB connections per click | Up to ~60s lag before clicks appear in analytics |
| No WebSockets / Redis | Survives 30s PHP limits | Dashboard is poll/cron fresh, not live streaming |

---

## Key Features

### Multi-Tenant Architecture
- Single database, global scope isolation per tenant
- Automatic `tenant_id` injection on record creation
- Tenant-aware global scopes on every query
- Compound indexes for performance at scale

### Zero-DB Click Tracking Pipeline
- JavaScript tracking pixel with First-Party LocalStorage Relay (bypasses ITP/ETP)
- Clicks logged to flat file — **zero MySQL connections** on click events
- Cron-driven batch ingestion (every 60s) with bulk INSERT
- Atomic file swap prevents data loss during processing

### Financial Core with Pessimistic Locking
- `SELECT ... FOR UPDATE` on balance mutations
- DECIMAL-safe math via `bcmath` — no floating point errors
- PayPal Mass Payout CSV export with atomic status transitions
- Configurable minimum payout thresholds

### Enterprise Dark UI Dashboard
- Vue 3 Composition API + Inertia.js SPA
- Chart.js revenue visualization with 30-day growth curves
- Real-time stat cards, conversion tables, status badges
- Fully Tailwind CSS — zero custom CSS

### Micro-Chunked Web Installer
- Browser-based installation wizard (no SSH required)
- AJAX-driven step-by-step: DB config → Migration → Chunked seeding → Admin creation
- **Bypasses 30-second `max_execution_time`** on shared hosting
- `installed.lock` prevents re-installation

---

## Package Contents

```
affiliate-core-saas/
├── app/                        # Laravel application code
│   ├── Console/Commands/       # Artisan commands (buffer processor)
│   ├── Http/Controllers/       # API + Web controllers
│   ├── Http/Middleware/         # InstallLock middleware
│   └── Models/                 # Eloquent models + TenantScoped trait
├── bootstrap/                  # Laravel 11 app bootstrap
├── database/
│   ├── migrations/             # 8 migration files
│   └── seeders/                # Optional demo data (tenant, affiliates, sample metrics)
├── public/js/                  # Vanilla JS tracking pixel
├── resources/
│   ├── css/                    # Tailwind entry point
│   ├── js/                     # Vue 3 SPA (Layouts, Pages, Components)
│   └── views/                  # Blade templates (Inertia root + Installer)
├── routes/                     # API, Web, Console routes
├── composer.json               # PHP dependencies
├── package.json                # Node.js dependencies
├── vite.config.js              # Vite build configuration
├── tailwind.config.js          # Tailwind configuration
├── .env.example                # Environment template
├── LICENSE.md                  # License terms
└── CHANGELOG.md                # Version history
```

---

## Quick Start

### 1. Web Installer (Recommended)
Upload files to your hosting, navigate to `https://yourdomain.com/install`, and follow the 4-step wizard.

### 2. Manual Installation
```bash
composer install
cp .env.example .env
php artisan key:generate
# Configure database in .env
php artisan migrate --force
php artisan db:seed
npm install && npm run build
```

### 3. Cron Setup (Required)
Add to your cPanel cron jobs:
```
* * * * * cd /home/user/public_html && php artisan schedule:run >> /dev/null 2>&1
```

---

## Demo Credentials

Seeded demo users use `@example.com` addresses only (safe for public repos).

| Role          | Email                         | Password |
|---------------|-------------------------------|----------|
| Super Admin   | admin@example.com             | password |
| Tenant Admin  | jordan@example.com            | password |
| Affiliate     | olivia-harper@example.com     | password |

---

## Tracking Pixel Integration

Add this to your client's e-commerce store:
```html
<script src="https://yourdomain.com/js/tracker.js" data-tenant="apex-digital" data-endpoint="https://yourdomain.com"></script>
```

Track conversions on the thank-you page:
```javascript
AffiliateCore.trackConversion('ORDER-123', 299.99);
```

---

## Requirements

- PHP 8.2+ with `bcmath`, `cURL`, `mbstring`, `openssl`
- MySQL 5.7+ / MariaDB 10.3+
- Composer 2.x
- Node.js 18+ & npm (for frontend build only)
- cPanel cron access (or any cron scheduler)

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

## Tests

```bash
make verify
```

Runs `php -l` across all PHP sources under `app/`, `bootstrap/`, `database/`, and `routes/` (no database required).

## License

See [LICENSE.md](LICENSE.md) for license terms.

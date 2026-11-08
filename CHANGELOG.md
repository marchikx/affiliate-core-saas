# Changelog — AffiliateCore SaaS

All notable changes to this project will be documented in this file.

---

## [1.0.0] — 2026-03-15

### Initial Release

#### Core Platform
- Multi-tenant architecture with single-database isolation
- TenantScoped global scope trait with automatic tenant injection
- 8 database migrations (tenants, users, campaigns, affiliate_links, clicks, conversions, payouts, jobs)
- 7 Eloquent models with explicit relationships

#### Tracking Pipeline
- First-Party JavaScript tracking pixel (`tracker.js`)
- LocalStorage Relay for ITP/ETP bypass
- Zero-DB click buffering via flat file logging
- Cron-driven batch ingestion with chunked bulk INSERT
- Atomic file swap for zero data loss

#### Campaign Management
- Full CRUD API for campaigns (percentage & flat commission types)
- Affiliate link generation with unique 6-char alphanumeric hashes
- Campaign status management (active/inactive)

#### Financial Core
- Payout request with pessimistic locking (`SELECT ... FOR UPDATE`)
- DECIMAL-safe arithmetic via `bcmath`
- PayPal Mass Payout CSV export
- Payout rejection with automatic balance refund
- Configurable $50 minimum payout threshold

#### Enterprise Dashboard
- Vue 3 + Inertia.js dark-themed SPA
- Chart.js 30-day revenue visualization
- 4 real-time stat cards (Revenue, Affiliates, Clicks, Payouts)
- Recent conversions table with status badges

#### Web Installer
- 4-step browser-based installation wizard
- Micro-chunked seeding bypasses 30s execution timeout
- Demo seeder: tenant, 50 affiliates, campaigns, synthetic 30-day click/conversion timeline
- Automatic `installed.lock` security
- PDO connection validation before .env write

#### Infrastructure
- Database-driven queue (jobs table)
- Cron scheduling via `routes/console.php`
- Zero external dependencies (no Redis, no WebSockets, no Node daemons)

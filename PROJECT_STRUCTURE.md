# Project Structure — AffiliateCore SaaS v1.0.0

```
affiliate-core-saas/
│
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── ProcessClicksBuffer.php      # Cron: drains click buffer → bulk INSERT
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── TrackingController.php    # Zero-DB ping + DB convert
│   │   │   │   ├── CampaignController.php    # CRUD for campaigns
│   │   │   │   ├── AffiliateLinkController.php # Link generation (6-char hash)
│   │   │   │   └── PayoutController.php      # Pessimistic locking + CSV export
│   │   │   │
│   │   │   ├── DashboardController.php       # Inertia dashboard data provider
│   │   │   └── InstallController.php         # 4-step chunked web installer
│   │   │
│   │   └── Middleware/
│   │       └── InstallLock.php               # Blocks /install after installation
│   │
│   └── Models/
│       ├── Traits/
│       │   └── TenantScoped.php              # Multi-tenancy global scope + auto tenant_id
│       ├── Tenant.php
│       ├── User.php                          # Custom tenant logic (not trait)
│       ├── Campaign.php                      # TenantScoped
│       ├── AffiliateLink.php                 # TenantScoped
│       ├── Click.php                         # TenantScoped
│       ├── Conversion.php                    # TenantScoped
│       └── Payout.php                        # TenantScoped
│
├── bootstrap/
│   └── app.php                               # Laravel 11 app configuration
│
├── database/
│   ├── migrations/
│   │   ├── 000000_create_tenants_table.php
│   │   ├── 000001_create_users_table.php     # + password_reset_tokens, sessions
│   │   ├── 000002_create_campaigns_table.php
│   │   ├── 000003_create_affiliate_links_table.php
│   │   ├── 000004_create_clicks_table.php
│   │   ├── 000005_create_conversions_table.php
│   │   ├── 000006_create_jobs_table.php      # + job_batches, failed_jobs
│   │   └── 000007_create_payouts_table.php
│   │
│   └── seeders/
│       └── DatabaseSeeder.php                # Demo tenant, users, campaigns, synthetic metrics
│
├── public/
│   └── js/
│       └── tracker.js                        # First-Party JS tracking pixel
│
├── resources/
│   ├── css/
│   │   └── app.css                           # Tailwind entry point
│   ├── js/
│   │   ├── app.js                            # Inertia + Vue 3 bootstrap
│   │   ├── Layouts/
│   │   │   └── EnterpriseLayout.vue          # Dark sidebar layout
│   │   ├── Pages/
│   │   │   └── Dashboard.vue                 # Stat cards + chart + conversions table
│   │   └── Components/
│   │       └── RevenueChart.vue              # Chart.js 30-day revenue visualization
│   │
│   └── views/
│       ├── app.blade.php                     # Inertia root template
│       └── installer.blade.php               # Standalone dark wizard UI
│
├── routes/
│   ├── api.php                               # Pixel + Sanctum API routes
│   ├── web.php                               # Installer + Dashboard routes
│   └── console.php                           # Cron scheduling
│
├── storage/
│   └── logs/
│       └── tracking/                         # Click buffer + cron logs
│
├── .env.example                              # Environment template
├── .gitignore                                # Git ignore rules
├── composer.json                             # PHP dependencies
├── package.json                              # Node.js dependencies
├── vite.config.js                            # Vite build configuration
├── tailwind.config.js                        # Tailwind configuration
├── README.md                                 # Main product README
├── LICENSE.md                                # License terms
├── CHANGELOG.md                              # Version history
└── PROJECT_STRUCTURE.md                      # This file
```

## File Count Summary

| Category | Files |
|----------|-------|
| PHP (Models, Controllers, Migrations, etc.) | 24 |
| JavaScript / Vue | 5 |
| Blade Templates | 2 |
| Config (Vite, Tailwind, Composer, npm) | 4 |
| Root Files (README, LICENSE, etc.) | 5 |
| **Total** | **40** |

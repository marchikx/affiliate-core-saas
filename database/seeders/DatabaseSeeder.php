<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AffiliateLink;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\Conversion;
use App\Models\Payout;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds a demo tenant, users, campaigns, links, and a 30-day synthetic metrics timeline.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Payout::query()->truncate();
        Conversion::query()->truncate();
        Click::query()->truncate();
        AffiliateLink::query()->truncate();
        Campaign::query()->truncate();
        User::query()->truncate();
        Tenant::query()->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ─── 1. Super Admin ──────────────────────────────────
        $superAdmin = User::create([
            'tenant_id'         => null,
            'name'              => 'System Administrator',
            'email'             => 'admin@example.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
            'role'              => 'super_admin',
            'balance'           => 0,
        ]);

        // ─── 2. Demo Tenant ─────────────────────────────────
        $tenant = Tenant::create([
            'name'                => 'Apex Digital Marketing',
            'slug'                => 'apex-digital',
            'domain'              => 'demo.affiliatecore.io',
            'subscription_status' => 'active',
            'settings'            => [
                'currency'       => 'USD',
                'timezone'       => 'America/New_York',
                'payout_minimum' => 50.00,
            ],
        ]);

        // ─── 3. Tenant Admin ────────────────────────────────
        $tenantAdmin = User::create([
            'tenant_id'         => $tenant->id,
            'name'              => 'Jordan Mitchell',
            'email'             => 'jordan@example.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('password'),
            'role'              => 'tenant_admin',
            'balance'           => 0,
        ]);

        // ─── 4. 50 Affiliate Users ─────────────────────────
        $affiliateNames = [
            'Olivia Harper', 'Liam Chen', 'Sophia Rodriguez', 'Noah Patel', 'Emma Johansson',
            'Jackson Lee', 'Ava Müller', 'Aiden Kowalski', 'Isabella Santos', 'Lucas Dubois',
            'Mia Nakamura', 'Ethan O\'Brien', 'Charlotte Kim', 'Mason Al-Farsi', 'Amelia Petrov',
            'Logan Gupta', 'Harper Fitzgerald', 'Sebastian Torres', 'Evelyn Okafor', 'Mateo Silva',
            'Aria Washington', 'Jack Lindström', 'Scarlett Huang', 'Owen De Vries', 'Chloe Bergman',
            'Elijah Rossi', 'Penelope Tanaka', 'James Moreau', 'Layla Ivanova', 'Benjamin Cruz',
            'Riley Andersen', 'Henry Yamamoto', 'Zoey Ferreira', 'Alexander Kozlov', 'Nora Bianchi',
            'William Choi', 'Lily Svensson', 'Daniel Mwangi', 'Grace Papadopoulos', 'Michael Brennan',
            'Hannah Larsson', 'Samuel Ishikawa', 'Addison Volkov', 'David Moreira', 'Eleanor Jansen',
            'Joseph Fischer', 'Victoria Rahim', 'Carter Magnusson', 'Stella Novak', 'Wyatt Beaumont',
        ];

        $affiliates = collect();

        foreach ($affiliateNames as $index => $fullName) {
            $slug = Str::slug($fullName);

            $affiliates->push(User::create([
                'tenant_id'         => $tenant->id,
                'name'              => $fullName,
                'email'             => $slug . '@example.com',
                'email_verified_at' => now()->subDays(rand(5, 60)),
                'password'          => Hash::make('password'),
                'role'              => 'affiliate',
                'balance'           => 0,
            ]));
        }

        // ─── 5. Campaigns ───────────────────────────────────
        $campaignDefinitions = [
            ['name' => 'Premium SaaS Suite',       'type' => 'percentage', 'value' => 25.00],
            ['name' => 'Enterprise Cloud Hosting',  'type' => 'percentage', 'value' => 20.00],
            ['name' => 'CyberShield VPN Pro',       'type' => 'flat',       'value' => 45.00],
            ['name' => 'AI Analytics Dashboard',    'type' => 'percentage', 'value' => 30.00],
            ['name' => 'DataVault Backup Solution', 'type' => 'flat',       'value' => 35.00],
        ];

        $campaigns = collect();

        foreach ($campaignDefinitions as $def) {
            $campaigns->push(Campaign::create([
                'tenant_id'           => $tenant->id,
                'name'                => $def['name'],
                'commission_type'     => $def['type'],
                'commission_value'    => $def['value'],
                'cookie_duration_days' => 30,
                'status'              => 'active',
            ]));
        }

        // ─── 6. Affiliate Links ─────────────────────────────
        $links = collect();
        $destinations = [
            'https://apexdigital.io/products/saas-suite',
            'https://apexdigital.io/products/cloud-hosting',
            'https://apexdigital.io/products/vpn-pro',
            'https://apexdigital.io/products/ai-analytics',
            'https://apexdigital.io/products/datavault',
        ];

        foreach ($affiliates as $affiliate) {
            $assignedCampaigns = $campaigns->random(rand(2, 4));

            foreach ($assignedCampaigns as $idx => $campaign) {
                $links->push(AffiliateLink::create([
                    'tenant_id'       => $tenant->id,
                    'user_id'         => $affiliate->id,
                    'campaign_id'     => $campaign->id,
                    'identifier_hash' => md5($affiliate->id . '-' . $campaign->id . '-' . Str::random(8)),
                    'destination_url' => $destinations[$campaigns->search($campaign)] ?? $destinations[0],
                ]));
            }
        }

        // ─── 7. Clicks & conversions (demo timeline) ─────
        $this->generateDemoTimeline($tenant, $links, $campaigns);
    }

    /**
     * Builds a 30-day synthetic click/conversion series for dashboard charts (demo only).
     */
    private function generateDemoTimeline(
        Tenant $tenant,
        \Illuminate\Support\Collection $links,
        \Illuminate\Support\Collection $campaigns,
    ): void {
        $targetMRR          = 45000;
        $totalDays          = 30;
        $avgSaleAmount      = 180.00;
        $avgConversionRate  = 0.035;
        $referers           = [
            'https://google.com/search?q=best+saas+tools',
            'https://youtube.com/watch?v=demo_review',
            'https://twitter.com/techinfluencer/status/123',
            'https://reddit.com/r/SaaS/comments/abc',
            'https://linkedin.com/posts/marketer_123',
            'https://facebook.com/groups/digitalmarketers',
            'https://producthunt.com/posts/apex-suite',
            'https://medium.com/@techblog/top-saas-2026',
            null,
        ];

        $totalConversionsNeeded = (int) ceil($targetMRR / $avgSaleAmount);

        $rawWeights = [];
        for ($day = 0; $day < $totalDays; $day++) {
            $ascendingFactor = 0.4 + (0.6 * ($day / ($totalDays - 1)));
            $sineComponent   = 1 + 0.3 * sin($day * M_PI / $totalDays);
            $jitter          = 1 + (mt_rand(-50, 50) / 1000);

            $rawWeights[$day] = $ascendingFactor * $sineComponent * $jitter;
        }

        $weightSum = array_sum($rawWeights);
        $dailyConversions = [];

        foreach ($rawWeights as $day => $weight) {
            $dailyConversions[$day] = max(1, (int) round(($weight / $weightSum) * $totalConversionsNeeded));
        }

        $clickBatchInsert      = [];
        $conversionBatchInsert = [];
        $affiliateBalances     = [];
        $clickIdCounter        = (int) (DB::table('clicks')->max('id') ?? 0);

        for ($day = 0; $day < $totalDays; $day++) {
            $date              = Carbon::now()->subDays($totalDays - $day)->startOfDay();
            $conversionsToday  = $dailyConversions[$day];
            $clicksToday       = (int) ceil($conversionsToday / $avgConversionRate);

            $convertedIndices = collect(range(0, $clicksToday - 1))
                ->shuffle()
                ->take($conversionsToday)
                ->flip();

            for ($c = 0; $c < $clicksToday; $c++) {
                $link    = $links->random();
                $hour    = mt_rand(6, 23);
                $minute  = mt_rand(0, 59);
                $second  = mt_rand(0, 59);
                $clickAt = $date->copy()->addHours($hour)->addMinutes($minute)->addSeconds($second);

                $clickIdCounter++;
                $visitorId = Str::uuid()->toString();

                $clickBatchInsert[] = [
                    'id'                => $clickIdCounter,
                    'tenant_id'         => $tenant->id,
                    'affiliate_link_id' => $link->id,
                    'visitor_id'        => substr($visitorId, 0, 64),
                    'ip_address'        => mt_rand(10, 223) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(1, 254),
                    'referer_url'       => $referers[array_rand($referers)],
                    'created_at'        => $clickAt,
                    'updated_at'        => $clickAt,
                ];

                if ($convertedIndices->has($c)) {
                    $campaign = $campaigns->firstWhere('id', $link->campaign_id);

                    $saleBase   = $avgSaleAmount * (0.5 + mt_rand(0, 100) / 100);
                    $saleAmount = round($saleBase, 4);

                    if ($campaign->commission_type === 'percentage') {
                        $commission = round($saleAmount * ($campaign->commission_value / 100), 4);
                    } else {
                        $commission = round((float) $campaign->commission_value, 4);
                    }

                    $statuses  = ['approved', 'approved', 'approved', 'approved', 'pending', 'paid'];
                    $convStatus = $statuses[array_rand($statuses)];
                    $convAt    = $clickAt->copy()->addMinutes(mt_rand(2, 180));

                    $conversionBatchInsert[] = [
                        'tenant_id'          => $tenant->id,
                        'click_id'           => $clickIdCounter,
                        'external_reference' => 'TXN-' . strtoupper(Str::random(10)),
                        'sale_amount'        => $saleAmount,
                        'commission_amount'  => $commission,
                        'status'             => $convStatus,
                        'created_at'         => $convAt,
                        'updated_at'         => $convAt,
                    ];

                    $userId = $link->user_id;
                    $affiliateBalances[$userId] = ($affiliateBalances[$userId] ?? 0) + $commission;
                }
            }
        }

        foreach (array_chunk($clickBatchInsert, 500) as $chunk) {
            DB::table('clicks')->insert($chunk);
        }

        foreach (array_chunk($conversionBatchInsert, 500) as $chunk) {
            DB::table('conversions')->insert($chunk);
        }

        foreach ($affiliateBalances as $userId => $balance) {
            User::withoutGlobalScopes()
                ->where('id', $userId)
                ->increment('balance', $balance);
        }
    }
}

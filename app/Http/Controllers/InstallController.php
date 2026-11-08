<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AffiliateLink;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\Conversion;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InstallController extends Controller
{
    private const LOCK_FILE  = 'installed.lock';
    private const TOTAL_SEED_CHUNKS = 10;
    private const TARGET_MRR = 45000;

    public function showInstaller(): View
    {
        return view('installer');
    }

    /**
     * Step 1: Validate & write database credentials to .env
     */
    public function step1_database(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'db_host'     => ['required', 'string', 'max:255'],
            'db_port'     => ['required', 'integer', 'min:1', 'max:65535'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $pdo = new \PDO(
                "mysql:host={$validated['db_host']};port={$validated['db_port']};dbname={$validated['db_database']}",
                $validated['db_username'],
                $validated['db_password'] ?? '',
                [\PDO::ATTR_TIMEOUT => 5],
            );
            $pdo = null;
        } catch (\PDOException $e) {
            return response()->json([
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ], 422);
        }

        $this->writeEnvValues([
            'DB_HOST'     => $validated['db_host'],
            'DB_PORT'     => (string) $validated['db_port'],
            'DB_DATABASE' => $validated['db_database'],
            'DB_USERNAME' => $validated['db_username'],
            'DB_PASSWORD' => $validated['db_password'] ?? '',
        ]);

        Artisan::call('config:clear');

        return response()->json(['message' => 'Database configured successfully.']);
    }

    /**
     * Step 2: Run all migrations (~2-5 seconds)
     */
    public function step2_migrate(): JsonResponse
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Migration failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json(['message' => 'Database schema created.']);
    }

    /**
     * Step 3: Chunked seeding — bypasses max_execution_time.
     *
     * chunk=0  → infrastructure (tenant, users, campaigns, links)
     * chunk=1-10 → 3 days of click/conversion data each
     */
    public function step3_seed(Request $request): JsonResponse
    {
        $chunk = (int) $request->input('chunk', 0);

        if ($chunk === 0) {
            return $this->seedInfrastructure();
        }

        if ($chunk > self::TOTAL_SEED_CHUNKS) {
            return response()->json([
                'message'    => 'Seeding complete.',
                'next_chunk' => null,
                'progress'   => 100,
            ]);
        }

        return $this->seedDataChunk($chunk);
    }

    /**
     * Step 4: Create the super admin account & lock the installer.
     */
    public function step4_admin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::withoutGlobalScopes()->updateOrCreate(
            ['email' => $validated['email'], 'tenant_id' => null],
            [
                'name'              => $validated['name'],
                'password'          => Hash::make($validated['password']),
                'role'              => 'super_admin',
                'email_verified_at' => now(),
                'balance'           => 0,
            ],
        );

        $lockPath = storage_path(self::LOCK_FILE);
        file_put_contents($lockPath, json_encode([
            'installed_at' => now()->toIso8601String(),
            'admin_email'  => $validated['email'],
            'php_version'  => PHP_VERSION,
        ]));

        Artisan::call('config:cache');

        return response()->json(['message' => 'Installation complete. Welcome to AffiliateCore!']);
    }

    // ─── Private: Seed Infrastructure (Chunk 0) ───────────────────────────

    private function seedInfrastructure(): JsonResponse
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'apex-digital'],
            [
                'name'                => 'Apex Digital Marketing',
                'domain'              => request()->getHost(),
                'subscription_status' => 'active',
                'settings'            => ['currency' => 'USD', 'timezone' => 'UTC'],
            ],
        );

        User::withoutGlobalScopes()->firstOrCreate(
            ['email' => 'admin@example.com', 'tenant_id' => $tenant->id],
            [
                'name'              => 'Jordan Mitchell',
                'password'          => Hash::make('password'),
                'role'              => 'tenant_admin',
                'email_verified_at' => now(),
                'balance'           => 0,
            ],
        );

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

        foreach ($affiliateNames as $name) {
            User::withoutGlobalScopes()->firstOrCreate(
                ['email' => Str::slug($name) . '@example.com', 'tenant_id' => $tenant->id],
                [
                    'name'              => $name,
                    'password'          => Hash::make('password'),
                    'role'              => 'affiliate',
                    'email_verified_at' => now()->subDays(rand(5, 60)),
                    'balance'           => 0,
                ],
            );
        }

        $campaignDefs = [
            ['name' => 'Premium SaaS Suite',       'type' => 'percentage', 'value' => 25.00],
            ['name' => 'Enterprise Cloud Hosting',  'type' => 'percentage', 'value' => 20.00],
            ['name' => 'CyberShield VPN Pro',       'type' => 'flat',       'value' => 45.00],
            ['name' => 'AI Analytics Dashboard',    'type' => 'percentage', 'value' => 30.00],
            ['name' => 'DataVault Backup Solution', 'type' => 'flat',       'value' => 35.00],
        ];

        $campaigns = collect();
        foreach ($campaignDefs as $def) {
            $campaigns->push(Campaign::withoutGlobalScopes()->firstOrCreate(
                ['name' => $def['name'], 'tenant_id' => $tenant->id],
                [
                    'commission_type'      => $def['type'],
                    'commission_value'     => $def['value'],
                    'cookie_duration_days' => 30,
                    'status'               => 'active',
                ],
            ));
        }

        $affiliates = User::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('role', 'affiliate')
            ->get();

        $destinations = [
            'https://apexdigital.io/products/saas-suite',
            'https://apexdigital.io/products/cloud-hosting',
            'https://apexdigital.io/products/vpn-pro',
            'https://apexdigital.io/products/ai-analytics',
            'https://apexdigital.io/products/datavault',
        ];

        foreach ($affiliates as $affiliate) {
            $assigned = $campaigns->random(rand(2, 4));
            foreach ($assigned as $campaign) {
                $existing = AffiliateLink::withoutGlobalScopes()
                    ->where('user_id', $affiliate->id)
                    ->where('campaign_id', $campaign->id)
                    ->exists();

                if (! $existing) {
                    AffiliateLink::withoutGlobalScopes()->create([
                        'tenant_id'       => $tenant->id,
                        'user_id'         => $affiliate->id,
                        'campaign_id'     => $campaign->id,
                        'identifier_hash' => substr(md5($affiliate->id . '-' . $campaign->id . '-' . Str::random(4)), 0, 32),
                        'destination_url' => $destinations[$campaigns->search($campaign)] ?? $destinations[0],
                    ]);
                }
            }
        }

        return response()->json([
            'message'    => 'Infrastructure created.',
            'next_chunk' => 1,
            'progress'   => 8,
        ]);
    }

    // ─── Private: Seed Data Chunk (Chunks 1-10) ──────────────────────────

    private function seedDataChunk(int $chunk): JsonResponse
    {
        $tenant = Tenant::where('slug', 'apex-digital')->firstOrFail();
        $links  = AffiliateLink::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->get();

        $campaigns = Campaign::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->get()
            ->keyBy('id');

        $totalDays       = 30;
        $daysPerChunk    = (int) ceil($totalDays / self::TOTAL_SEED_CHUNKS);
        $dayStart        = ($chunk - 1) * $daysPerChunk;
        $dayEnd          = min($chunk * $daysPerChunk, $totalDays);
        $avgSale         = 180.0;
        $conversionRate  = 0.035;
        $totalConversions = (int) ceil(self::TARGET_MRR / $avgSale);

        $referers = [
            'https://google.com/search?q=best+saas', 'https://youtube.com/watch?v=review',
            'https://twitter.com/techinfluencer', 'https://reddit.com/r/SaaS',
            'https://linkedin.com/posts/marketer', null,
        ];

        $rawWeights = [];
        for ($d = 0; $d < $totalDays; $d++) {
            $asc  = 0.4 + 0.6 * ($d / ($totalDays - 1));
            $wave = 1 + 0.3 * sin($d * M_PI / $totalDays);
            $rawWeights[$d] = $asc * $wave;
        }
        $wSum = array_sum($rawWeights);

        $clickRows      = [];
        $conversionRows = [];
        $balances       = [];
        $nextClickId    = (int) (DB::table('clicks')->max('id') ?? 0) + 1;

        for ($day = $dayStart; $day < $dayEnd; $day++) {
            $date           = Carbon::now()->subDays($totalDays - $day)->startOfDay();
            $conversionsDay = max(1, (int) round(($rawWeights[$day] / $wSum) * $totalConversions));
            $clicksDay      = (int) ceil($conversionsDay / $conversionRate);

            $convertSet = collect(range(0, $clicksDay - 1))->shuffle()->take($conversionsDay)->flip();

            for ($c = 0; $c < $clicksDay; $c++) {
                $link = $links->random();
                $at   = $date->copy()->addHours(mt_rand(6, 23))->addMinutes(mt_rand(0, 59))->addSeconds(mt_rand(0, 59));

                $clickRows[] = [
                    'id'                => $nextClickId,
                    'tenant_id'         => $tenant->id,
                    'affiliate_link_id' => $link->id,
                    'visitor_id'        => substr(Str::uuid()->toString(), 0, 64),
                    'ip_address'        => mt_rand(10, 223) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(1, 254),
                    'referer_url'       => $referers[array_rand($referers)],
                    'created_at'        => $at,
                    'updated_at'        => $at,
                ];

                if ($convertSet->has($c)) {
                    $camp       = $campaigns[$link->campaign_id] ?? null;
                    $saleAmount = round($avgSale * (0.5 + mt_rand(0, 100) / 100), 4);
                    $commission = $camp && $camp->commission_type === 'percentage'
                        ? round($saleAmount * ((float) $camp->commission_value / 100), 4)
                        : round((float) ($camp->commission_value ?? 35), 4);

                    $convAt = $at->copy()->addMinutes(mt_rand(2, 120));

                    $conversionRows[] = [
                        'tenant_id'          => $tenant->id,
                        'click_id'           => $nextClickId,
                        'external_reference' => 'TXN-' . strtoupper(Str::random(10)),
                        'sale_amount'        => $saleAmount,
                        'commission_amount'  => $commission,
                        'status'             => ['approved', 'approved', 'approved', 'pending', 'paid'][array_rand([0, 1, 2, 3, 4])],
                        'created_at'         => $convAt,
                        'updated_at'         => $convAt,
                    ];

                    $balances[$link->user_id] = ($balances[$link->user_id] ?? 0) + $commission;
                }

                $nextClickId++;
            }
        }

        foreach (array_chunk($clickRows, 500) as $batch) {
            DB::table('clicks')->insert($batch);
        }
        foreach (array_chunk($conversionRows, 500) as $batch) {
            DB::table('conversions')->insert($batch);
        }
        foreach ($balances as $uid => $bal) {
            DB::table('users')->where('id', $uid)->increment('balance', $bal);
        }

        $isLast   = $chunk >= self::TOTAL_SEED_CHUNKS;
        $progress = 8 + (int) round(($chunk / self::TOTAL_SEED_CHUNKS) * 82);

        return response()->json([
            'message'    => "Chunk {$chunk}/" . self::TOTAL_SEED_CHUNKS . " seeded.",
            'next_chunk' => $isLast ? null : $chunk + 1,
            'progress'   => $isLast ? 95 : $progress,
        ]);
    }

    // ─── Private: .env Writer ─────────────────────────────────────────────

    private function writeEnvValues(array $values): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            $examplePath = base_path('.env.example');
            if (file_exists($examplePath)) {
                copy($examplePath, $envPath);
            } else {
                file_put_contents($envPath, '');
            }
        }

        $content = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $escaped = $this->escapeEnvValue($value);

            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$escaped}", $content);
            } else {
                $content .= PHP_EOL . "{$key}={$escaped}";
            }
        }

        file_put_contents($envPath, $content);
    }

    private function escapeEnvValue(string $value): string
    {
        if ($value === '' || preg_match('/[\s"\'#]/', $value)) {
            return '"' . addcslashes($value, '"\\') . '"';
        }

        return $value;
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AffiliateLink;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessClicksBuffer extends Command
{
    protected $signature = 'tracking:process-buffer';

    protected $description = 'Drain the flat-file click buffer into the clicks table via optimized bulk insert';

    private const BUFFER_PATH = 'logs/tracking/clicks_buffer.log';
    private const CHUNK_SIZE  = 1000;

    /** In-memory lookup caches to eliminate N+1 during the loop. */
    private array $tenantCache = [];
    private array $linkCache   = [];

    public function handle(): int
    {
        $path = storage_path(self::BUFFER_PATH);

        if (! file_exists($path) || filesize($path) === 0) {
            $this->components->info('Click buffer is empty — nothing to process.');

            return self::SUCCESS;
        }

        // ── Atomic swap: rename the live file so new writes go to a fresh log ──
        $processingPath = $path . '.' . time() . '.processing';

        if (! @rename($path, $processingPath)) {
            $this->components->error('Failed to acquire buffer lock (rename). Aborting.');
            Log::error('[ProcessClicksBuffer] Could not rename buffer file for processing.');

            return self::FAILURE;
        }

        $handle = @fopen($processingPath, 'rb');

        if (! $handle) {
            @rename($processingPath, $path);
            $this->components->error('Failed to open processing file.');

            return self::FAILURE;
        }

        $rows        = [];
        $skipped     = 0;
        $totalParsed = 0;

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $data = json_decode($line, true);

            if (! is_array($data) || empty($data['tenant_slug']) || empty($data['hash']) || empty($data['visitor_id'])) {
                $skipped++;
                continue;
            }

            $tenantId = $this->resolveTenantId($data['tenant_slug']);

            if ($tenantId === null) {
                $skipped++;
                continue;
            }

            $linkId = $this->resolveLinkId($tenantId, $data['hash']);

            if ($linkId === null) {
                $skipped++;
                continue;
            }

            $timestamp = isset($data['ts'])
                ? date('Y-m-d H:i:s', (int) $data['ts'])
                : now()->toDateTimeString();

            $rows[] = [
                'tenant_id'         => $tenantId,
                'affiliate_link_id' => $linkId,
                'visitor_id'        => mb_substr((string) $data['visitor_id'], 0, 64),
                'ip_address'        => mb_substr((string) ($data['ip'] ?? '0.0.0.0'), 0, 45),
                'referer_url'       => isset($data['referrer']) ? mb_substr((string) $data['referrer'], 0, 512) : null,
                'created_at'        => $timestamp,
                'updated_at'        => $timestamp,
            ];

            $totalParsed++;

            if (count($rows) >= self::CHUNK_SIZE) {
                $this->flushChunk($rows);
                $rows = [];
            }
        }

        fclose($handle);

        if (count($rows) > 0) {
            $this->flushChunk($rows);
        }

        @unlink($processingPath);

        $this->components->info(sprintf(
            'Buffer drained: %s clicks inserted, %s lines skipped.',
            number_format($totalParsed),
            number_format($skipped),
        ));

        if ($skipped > 0) {
            Log::warning('[ProcessClicksBuffer] Skipped lines during processing.', [
                'skipped' => $skipped,
            ]);
        }

        return self::SUCCESS;
    }

    private function flushChunk(array $rows): void
    {
        try {
            DB::table('clicks')->insert($rows);
        } catch (\Throwable $e) {
            Log::error('[ProcessClicksBuffer] Bulk insert failed, falling back to row-by-row.', [
                'error' => $e->getMessage(),
                'chunk' => count($rows),
            ]);

            foreach ($rows as $row) {
                try {
                    DB::table('clicks')->insert($row);
                } catch (\Throwable $inner) {
                    Log::warning('[ProcessClicksBuffer] Single row insert failed.', [
                        'error' => $inner->getMessage(),
                        'row'   => $row,
                    ]);
                }
            }
        }
    }

    private function resolveTenantId(string $slug): ?int
    {
        if (isset($this->tenantCache[$slug])) {
            return $this->tenantCache[$slug];
        }

        $tenant = Tenant::where('slug', $slug)->first(['id']);

        $this->tenantCache[$slug] = $tenant?->id;

        return $this->tenantCache[$slug];
    }

    private function resolveLinkId(int $tenantId, string $hash): ?int
    {
        $cacheKey = $tenantId . ':' . $hash;

        if (isset($this->linkCache[$cacheKey])) {
            return $this->linkCache[$cacheKey];
        }

        $link = AffiliateLink::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('identifier_hash', $hash)
            ->first(['id']);

        $this->linkCache[$cacheKey] = $link?->id;

        return $this->linkCache[$cacheKey];
    }
}

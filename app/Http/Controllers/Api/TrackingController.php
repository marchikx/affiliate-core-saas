<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\AffiliateLink;
use App\Models\Click;
use App\Models\Conversion;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrackingController extends Controller
{
    /**
     * Zero-DB click registration.
     *
     * Serializes the payload to a flat log file — no MySQL connection opened.
     * The buffer is drained every minute by `tracking:process-buffer`.
     */
    public function ping(Request $request): Response
    {
        $tenantSlug = $this->sanitizeString($request->input('tenant_slug'), 64);
        $hash       = $this->sanitizeString($request->input('hash'), 32);
        $visitorId  = $this->sanitizeString($request->input('visitor_id'), 64);
        $url        = $this->sanitizeString($request->input('url'), 2048);
        $referrer   = $this->sanitizeString($request->input('referrer'), 512);

        if (! $tenantSlug || ! $hash || ! $visitorId) {
            return response()->noContent(422);
        }

        $entry = json_encode([
            'tenant_slug' => $tenantSlug,
            'hash'        => $hash,
            'visitor_id'  => $visitorId,
            'url'         => $url,
            'referrer'    => $referrer,
            'ip'          => $this->resolveIp($request),
            'ts'          => time(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $logPath = storage_path('logs/tracking/clicks_buffer.log');

        $written = @file_put_contents(
            $logPath,
            $entry . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        if ($written === false) {
            Log::error('[TrackingController::ping] Failed to write click buffer', [
                'path' => $logPath,
            ]);
        }

        return response()->noContent();
    }

    /**
     * Conversion tracking — connects to DB.
     *
     * Resolves the original click via visitor_id, calculates commission,
     * and inserts a conversion record atomically.
     */
    public function convert(Request $request): Response|JsonResponse
    {
        $tenantSlug = $this->sanitizeString($request->input('tenant_slug'), 64);
        $hash       = $this->sanitizeString($request->input('hash'), 32);
        $visitorId  = $this->sanitizeString($request->input('visitor_id'), 64);
        $orderId    = $this->sanitizeString($request->input('order_id'), 255);
        $amount     = (float) $request->input('amount', 0);

        if (! $tenantSlug || ! $hash || ! $visitorId || $amount <= 0) {
            return response()->noContent(422);
        }

        $tenant = Tenant::where('slug', $tenantSlug)->first();

        if (! $tenant) {
            return response()->noContent(404);
        }

        $link = AffiliateLink::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('identifier_hash', $hash)
            ->first();

        if (! $link) {
            return response()->noContent(404);
        }

        $click = Click::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('affiliate_link_id', $link->id)
            ->where('visitor_id', $visitorId)
            ->orderByDesc('id')
            ->first();

        if (! $click) {
            return response()->noContent(404);
        }

        $duplicate = Conversion::withoutGlobalScopes()
            ->where('click_id', $click->id)
            ->exists();

        if ($duplicate) {
            return response()->json(['status' => 'duplicate'], 409);
        }

        $campaign = $link->campaign()->withoutGlobalScopes()->first();

        if (! $campaign) {
            return response()->noContent(404);
        }

        $saleAmount = round($amount, 4);

        $commissionAmount = $campaign->commission_type === 'percentage'
            ? round($saleAmount * ((float) $campaign->commission_value / 100), 4)
            : round((float) $campaign->commission_value, 4);

        DB::transaction(function () use ($tenant, $click, $orderId, $saleAmount, $commissionAmount, $link): void {
            Conversion::withoutGlobalScopes()->create([
                'tenant_id'          => $tenant->id,
                'click_id'           => $click->id,
                'external_reference' => $orderId,
                'sale_amount'        => $saleAmount,
                'commission_amount'  => $commissionAmount,
                'status'             => 'pending',
            ]);

            DB::table('users')
                ->where('id', $link->user_id)
                ->increment('balance', $commissionAmount);
        });

        return response()->noContent();
    }

    private function sanitizeString(?string $value, int $maxLength): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = strip_tags($value);
        $value = mb_substr($value, 0, $maxLength);

        return $value;
    }

    private function resolveIp(Request $request): string
    {
        $forwarded = $request->header('X-Forwarded-For');

        if ($forwarded) {
            $ips = array_map('trim', explode(',', $forwarded));

            return filter_var($ips[0], FILTER_VALIDATE_IP) ?: $request->ip();
        }

        return $request->ip() ?? '0.0.0.0';
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\AffiliateLink;
use App\Models\Campaign;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class AffiliateLinkController extends Controller
{
    public function index(): JsonResponse
    {
        $links = AffiliateLink::with(['campaign:id,name,commission_type,commission_value'])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return response()->json(['data' => $links]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaign_id'     => ['required', 'integer'],
            'destination_url' => ['required', 'url', 'max:2048'],
        ]);

        $campaign = Campaign::where('id', $validated['campaign_id'])
            ->where('status', 'active')
            ->firstOrFail();

        $hash = $this->generateUniqueHash();

        $link = AffiliateLink::create([
            'user_id'         => auth()->id(),
            'campaign_id'     => $campaign->id,
            'identifier_hash' => $hash,
            'destination_url' => $validated['destination_url'],
        ]);

        $tenant = Tenant::find(auth()->user()->tenant_id);
        $domain = $tenant?->domain ?? request()->getHost();

        return response()->json([
            'data' => [
                'link'             => $link,
                'tracking_url'     => "https://{$domain}/?ref={$hash}",
                'identifier_hash'  => $hash,
            ],
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $link = AffiliateLink::where('user_id', auth()->id())
            ->findOrFail($id);

        $link->delete();

        return response()->json(null, 204);
    }

    /**
     * Generate a unique 6-character alphanumeric hash.
     * Uses a retry loop with collision detection.
     */
    private function generateUniqueHash(): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $max   = strlen($chars) - 1;

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $hash = '';
            for ($i = 0; $i < 6; $i++) {
                $hash .= $chars[random_int(0, $max)];
            }

            $exists = AffiliateLink::withoutGlobalScopes()
                ->where('identifier_hash', $hash)
                ->exists();

            if (! $exists) {
                return $hash;
            }
        }

        return substr(md5(uniqid((string) mt_rand(), true)), 0, 6);
    }
}

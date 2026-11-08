<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    public function index(): JsonResponse
    {
        $campaigns = Campaign::latest()->get();

        return response()->json(['data' => $campaigns]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'commission_type'     => ['required', Rule::in(['percentage', 'flat'])],
            'commission_value'    => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'cookie_duration_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'status'              => ['sometimes', Rule::in(['active', 'inactive'])],
        ]);

        $campaign = Campaign::create($validated);

        return response()->json(['data' => $campaign], 201);
    }

    public function show(int $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        return response()->json(['data' => $campaign]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);

        $validated = $request->validate([
            'name'                => ['sometimes', 'string', 'max:255'],
            'commission_type'     => ['sometimes', Rule::in(['percentage', 'flat'])],
            'commission_value'    => ['sometimes', 'numeric', 'min:0.01', 'max:99999.99'],
            'cookie_duration_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'status'              => ['sometimes', Rule::in(['active', 'inactive'])],
        ]);

        $campaign->update($validated);

        return response()->json(['data' => $campaign->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);
        $campaign->delete();

        return response()->json(null, 204);
    }
}

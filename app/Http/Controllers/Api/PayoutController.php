<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Payout;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayoutController extends Controller
{
    private const MINIMUM_PAYOUT = '50.0000';

    /**
     * List payouts for the authenticated user (affiliate view)
     * or all payouts for the tenant (admin view).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Payout::latest();

        if ($user->role === 'affiliate') {
            $query->where('user_id', $user->id);
        }

        return response()->json(['data' => $query->paginate(25)]);
    }

    /**
     * Affiliate requests a payout.
     *
     * Uses pessimistic locking (SELECT ... FOR UPDATE) to prevent
     * race conditions on the balance column.
     */
    public function requestPayout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount'        => ['required', 'numeric', 'min:0.01', 'max:9999999.9999'],
            'payment_email' => ['required', 'email', 'max:255'],
        ]);

        $requestedAmount = number_format((float) $validated['amount'], 4, '.', '');

        if (bccomp($requestedAmount, self::MINIMUM_PAYOUT, 4) < 0) {
            return response()->json([
                'message' => 'Minimum payout amount is $' . rtrim(rtrim(self::MINIMUM_PAYOUT, '0'), '.') . '.',
                'errors'  => ['amount' => ['Amount is below the minimum payout threshold.']],
            ], 422);
        }

        $payout = DB::transaction(function () use ($request, $requestedAmount, $validated): Payout {
            /** @var User $user */
            $user = User::withoutGlobalScopes()
                ->where('id', auth()->id())
                ->lockForUpdate()
                ->firstOrFail();

            $currentBalance = number_format((float) $user->balance, 4, '.', '');

            if (bccomp($currentBalance, $requestedAmount, 4) < 0) {
                abort(422, 'Insufficient balance. Current: $' . $currentBalance);
            }

            $user->balance = bcsub($currentBalance, $requestedAmount, 4);
            $user->save();

            return Payout::create([
                'tenant_id'     => $user->tenant_id,
                'user_id'       => $user->id,
                'amount'        => $requestedAmount,
                'payment_email' => $validated['payment_email'],
                'status'        => 'pending',
            ]);
        });

        return response()->json([
            'message' => 'Payout request submitted.',
            'data'    => $payout,
        ], 201);
    }

    /**
     * Tenant Admin: export all pending payouts as PayPal Mass Payout CSV
     * and atomically mark them as paid.
     */
    public function exportMassPayouts(Request $request): StreamedResponse|JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'tenant_admin' && $user->role !== 'super_admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $payouts = Payout::where('status', 'pending')
            ->with('user:id,name,email')
            ->get();

        if ($payouts->isEmpty()) {
            return response()->json(['message' => 'No pending payouts to export.'], 404);
        }

        $payoutIds = $payouts->pluck('id')->all();

        DB::transaction(function () use ($payoutIds): void {
            Payout::whereIn('id', $payoutIds)
                ->lockForUpdate()
                ->update([
                    'status'     => 'paid',
                    'updated_at' => now(),
                ]);
        });

        $filename = 'paypal_mass_payout_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($payouts): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, [
                'Email',
                'Amount',
                'Currency',
                'Note',
            ]);

            foreach ($payouts as $payout) {
                fputcsv($handle, [
                    $payout->payment_email,
                    number_format((float) $payout->amount, 2, '.', ''),
                    'USD',
                    'AffiliateCore Commission — ' . ($payout->user?->name ?? 'Affiliate #' . $payout->user_id),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Tenant Admin: reject a pending payout and refund balance.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'tenant_admin' && $user->role !== 'super_admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $payout = Payout::where('status', 'pending')->findOrFail($id);

        DB::transaction(function () use ($payout): void {
            $payout->update(['status' => 'rejected']);

            User::withoutGlobalScopes()
                ->where('id', $payout->user_id)
                ->lockForUpdate()
                ->increment('balance', (float) $payout->amount);
        });

        return response()->json([
            'message' => 'Payout rejected and balance refunded.',
            'data'    => $payout->fresh(),
        ]);
    }
}

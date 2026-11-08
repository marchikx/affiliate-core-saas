<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Click;
use App\Models\Conversion;
use App\Models\Payout;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'stats'             => fn () => $this->getStats(),
            'chartData'         => fn () => $this->getChartData(),
            'recentConversions' => fn () => $this->getRecentConversions(),
        ]);
    }

    private function getStats(): array
    {
        return [
            'total_revenue'      => round((float) Conversion::sum('sale_amount'), 2),
            'active_affiliates'  => User::where('role', 'affiliate')->count(),
            'total_clicks'       => Click::count(),
            'pending_payouts'    => round((float) Payout::where('status', 'pending')->sum('amount'), 2),
        ];
    }

    private function getChartData(): array
    {
        $start = Carbon::now()->subDays(29)->startOfDay();

        $daily = Conversion::where('created_at', '>=', $start)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(sale_amount) as revenue'),
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $result = [];
        for ($i = 0; $i < 30; $i++) {
            $date = $start->copy()->addDays($i)->format('Y-m-d');
            $result[] = [
                'date'    => Carbon::parse($date)->format('M d'),
                'revenue' => round((float) ($daily[$date]->revenue ?? 0), 2),
            ];
        }

        return $result;
    }

    private function getRecentConversions(): array
    {
        return Conversion::with([
            'click.affiliateLink.user:id,name',
            'click.affiliateLink.campaign:id,name',
        ])
            ->latest()
            ->take(15)
            ->get()
            ->map(fn (Conversion $c) => [
                'id'                => $c->id,
                'date'              => $c->created_at->format('M d, H:i'),
                'affiliate_name'    => $c->click?->affiliateLink?->user?->name ?? 'Unknown',
                'campaign_name'     => $c->click?->affiliateLink?->campaign?->name ?? 'Unknown',
                'sale_amount'       => round((float) $c->sale_amount, 2),
                'commission_amount' => round((float) $c->commission_amount, 2),
                'status'            => $c->status,
            ])
            ->all();
    }
}

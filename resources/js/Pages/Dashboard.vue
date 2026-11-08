<script setup>
import { computed } from 'vue';
import EnterpriseLayout from '@/Layouts/EnterpriseLayout.vue';
import RevenueChart from '@/Components/RevenueChart.vue';

defineOptions({ layout: EnterpriseLayout });

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      total_revenue: 0,
      active_affiliates: 0,
      total_clicks: 0,
      pending_payouts: 0,
    }),
  },
  chartData: { type: Array, default: () => [] },
  recentConversions: { type: Array, default: () => [] },
});

function formatCurrency(value) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(value);
}

function formatNumber(value) {
  return new Intl.NumberFormat('en-US').format(value);
}

const statCards = computed(() => [
  {
    label: 'Total Revenue',
    value: formatCurrency(props.stats.total_revenue),
    colorClass: 'text-emerald-400',
    bgClass: 'bg-emerald-500/10',
    icon: 'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
  },
  {
    label: 'Active Affiliates',
    value: formatNumber(props.stats.active_affiliates),
    colorClass: 'text-indigo-400',
    bgClass: 'bg-indigo-500/10',
    icon: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
  },
  {
    label: 'Total Clicks',
    value: formatNumber(props.stats.total_clicks),
    colorClass: 'text-sky-400',
    bgClass: 'bg-sky-500/10',
    icon: 'M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59',
  },
  {
    label: 'Pending Payouts',
    value: formatCurrency(props.stats.pending_payouts),
    colorClass: 'text-amber-400',
    bgClass: 'bg-amber-500/10',
    icon: 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z',
  },
]);

const statusBadge = {
  approved: 'bg-emerald-500/10 text-emerald-400',
  paid:     'bg-emerald-500/10 text-emerald-400',
  pending:  'bg-amber-500/10 text-amber-400',
  rejected: 'bg-red-500/10 text-red-400',
};
</script>

<template>
  <div>
    <!-- Page Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-white tracking-tight">Dashboard</h1>
      <p class="mt-1 text-sm text-slate-500">Real-time affiliate performance metrics</p>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="relative overflow-hidden rounded-xl bg-slate-800 shadow-lg shadow-black/50 p-5"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-400">{{ card.label }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight" :class="card.colorClass">
              {{ card.value }}
            </p>
          </div>
          <div class="rounded-xl p-3" :class="card.bgClass">
            <svg class="h-6 w-6" :class="card.colorClass" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" :d="card.icon" />
            </svg>
          </div>
        </div>
        <div class="absolute -bottom-4 -right-4 h-24 w-24 rounded-full opacity-5" :class="card.bgClass"></div>
      </div>
    </div>

    <!-- Revenue Chart -->
    <div class="mb-8">
      <RevenueChart :data="chartData" />
    </div>

    <!-- Recent Conversions Table -->
    <div class="rounded-xl bg-slate-800 shadow-lg shadow-black/50 overflow-hidden">
      <div class="px-6 py-5 border-b border-slate-700/60">
        <h3 class="text-lg font-semibold text-slate-100">Recent Conversions</h3>
        <p class="mt-1 text-sm text-slate-500">Latest affiliate-driven transactions</p>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-slate-700/40">
              <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
              <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Affiliate</th>
              <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Campaign</th>
              <th class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Sale</th>
              <th class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Commission</th>
              <th class="px-6 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-700/40">
            <tr
              v-for="conversion in recentConversions"
              :key="conversion.id"
              class="transition-colors duration-150 hover:bg-slate-700/20"
            >
              <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-400">
                {{ conversion.date }}
              </td>
              <td class="whitespace-nowrap px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-500/20 text-xs font-bold text-indigo-400">
                    {{ conversion.affiliate_name?.charAt(0) ?? '?' }}
                  </div>
                  <span class="text-sm font-medium text-slate-300">{{ conversion.affiliate_name }}</span>
                </div>
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-400">
                {{ conversion.campaign_name }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-slate-200">
                {{ formatCurrency(conversion.sale_amount) }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-semibold text-emerald-400">
                {{ formatCurrency(conversion.commission_amount) }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-center">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize"
                  :class="statusBadge[conversion.status] ?? 'bg-slate-500/10 text-slate-400'"
                >
                  {{ conversion.status }}
                </span>
              </td>
            </tr>
            <tr v-if="!recentConversions.length">
              <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-500">
                No conversions recorded yet.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

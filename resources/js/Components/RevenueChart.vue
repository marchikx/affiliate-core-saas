<script setup>
import { ref, onMounted, watch, computed } from 'vue';

const props = defineProps({
  data: { type: Array, default: () => [] },
});

const canvas = ref(null);
let chartInstance = null;

const chartData = computed(() => ({
  labels: props.data.map(d => d.date),
  values: props.data.map(d => d.revenue),
}));

async function renderChart() {
  if (!canvas.value || !props.data.length) return;

  const { Chart, registerables } = await import('chart.js');
  Chart.register(...registerables);

  if (chartInstance) chartInstance.destroy();

  const ctx = canvas.value.getContext('2d');

  const gradient = ctx.createLinearGradient(0, 0, 0, canvas.value.height);
  gradient.addColorStop(0, 'rgba(52, 211, 153, 0.25)');
  gradient.addColorStop(1, 'rgba(52, 211, 153, 0.0)');

  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: chartData.value.labels,
      datasets: [{
        label: 'Revenue ($)',
        data: chartData.value.values,
        borderColor: '#34d399',
        backgroundColor: gradient,
        borderWidth: 2.5,
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#34d399',
        pointBorderColor: '#1e293b',
        pointBorderWidth: 2,
        pointRadius: 0,
        pointHoverRadius: 6,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { intersect: false, mode: 'index' },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1e293b',
          titleColor: '#94a3b8',
          bodyColor: '#f1f5f9',
          borderColor: '#334155',
          borderWidth: 1,
          padding: 12,
          titleFont: { size: 12 },
          bodyFont: { size: 14, weight: 'bold' },
          callbacks: {
            label: (ctx) => '$ ' + ctx.parsed.y.toLocaleString('en-US', { minimumFractionDigits: 2 }),
          },
        },
      },
      scales: {
        x: {
          grid: { color: 'rgba(51, 65, 85, 0.3)', drawBorder: false },
          ticks: { color: '#64748b', font: { size: 11 }, maxTicksLimit: 10 },
        },
        y: {
          grid: { color: 'rgba(51, 65, 85, 0.3)', drawBorder: false },
          ticks: {
            color: '#64748b',
            font: { size: 11 },
            callback: (v) => '$' + (v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v),
          },
        },
      },
    },
  });
}

onMounted(renderChart);
watch(() => props.data, renderChart, { deep: true });
</script>

<template>
  <div class="relative rounded-xl bg-slate-800 shadow-lg shadow-black/50 p-6">
    <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-emerald-500/10 via-transparent to-indigo-500/10 pointer-events-none"></div>
    <div class="relative">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg font-semibold text-slate-100">Revenue Overview</h3>
          <p class="text-sm text-slate-500">Last 30 days performance</p>
        </div>
        <div class="flex items-center gap-2 rounded-lg bg-emerald-500/10 px-3 py-1.5">
          <svg class="h-4 w-4 text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M12.577 4.878a.75.75 0 01.919-.53l4.78 1.281a.75.75 0 01.531.919l-1.281 4.78a.75.75 0 01-1.449-.387l.81-3.022a19.407 19.407 0 00-5.594 5.203.75.75 0 01-1.139.093L7.4 10.463 2.78 15.08a.75.75 0 01-1.06-1.06l5.25-5.25a.75.75 0 011.06 0l2.409 2.408a20.923 20.923 0 015.218-4.87l-2.61.699a.75.75 0 01-.388-1.45z" clip-rule="evenodd" />
          </svg>
          <span class="text-sm font-semibold text-emerald-400">+23.4%</span>
        </div>
      </div>
      <div class="h-72">
        <canvas ref="canvas"></canvas>
      </div>
    </div>
  </div>
</template>

<template>
  <AppLayout>
    <PageHeader
      title="Profit Report"
      subtitle="Business profitability analysis and financial insights"
    />

    <!-- Filters -->
    <ReportFilters
      :filters="filters"
      :loading="loading"
      @apply="applyFilters"
    />

    <!-- Stats Cards -->
    <StatsCards
      :stats="statsCards"
      :loading="loading"
    />

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <!-- Profit Trend Chart -->
      <ReportChart
        title="Profit Trend"
        type="line"
        :data="profitChartData"
        :loading="loading"
      />

      <!-- Sales vs COGS Chart -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Sales vs Cost of Goods Sold</h3>
        <div class="relative" style="height: 400px;">
          <canvas ref="comparisonChart" class="w-full h-full"></canvas>
        </div>
      </div>
    </div>

    <!-- Profit Analysis -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <!-- Profit Breakdown -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Profit Breakdown</h3>
        <div class="space-y-4">
          <div class="flex justify-between items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
            <span class="font-medium text-green-700 dark:text-green-300">Total Sales</span>
            <span class="font-bold text-green-700 dark:text-green-300">{{ formatCurrency(summary.total_sales) }}</span>
          </div>
          <div class="flex justify-between items-center p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
            <span class="font-medium text-red-700 dark:text-red-300">Cost of Goods Sold</span>
            <span class="font-bold text-red-700 dark:text-red-300">{{ formatCurrency(summary.total_cogs) }}</span>
          </div>
          <div class="flex justify-between items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
            <span class="font-medium text-blue-700 dark:text-blue-300">Gross Profit</span>
            <span class="font-bold text-blue-700 dark:text-blue-300">{{ formatCurrency(summary.gross_profit) }}</span>
          </div>
          <div class="flex justify-between items-center p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
            <span class="font-medium text-orange-700 dark:text-orange-300">Total Expenses</span>
            <span class="font-bold text-orange-700 dark:text-orange-300">-{{ formatCurrency(summary.total_expenses || 0) }}</span>
          </div>
          <div class="flex justify-between items-center p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
            <span class="font-medium text-emerald-700 dark:text-emerald-300">Net Profit</span>
            <span class="font-bold text-emerald-700 dark:text-emerald-300">{{ formatCurrency(summary.net_profit || 0) }}</span>
          </div>
          <div class="flex justify-between items-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
            <span class="font-medium text-purple-700 dark:text-purple-300">Net Profit Margin</span>
            <span class="font-bold text-purple-700 dark:text-purple-300">{{ summary.profit_margin }}%</span>
          </div>
        </div>
      </div>

      <!-- Performance Indicators -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Performance Indicators</h3>
        <div class="space-y-4">
          <!-- Profit Margin Indicator -->
          <div>
            <div class="flex justify-between mb-2">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Profit Margin</span>
              <span class="text-sm text-gray-700 dark:text-gray-300">{{ summary.profit_margin }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div 
                class="bg-blue-600 h-2 rounded-full"
                :style="{ width: Math.min(summary.profit_margin, 100) + '%' }"
              ></div>
            </div>
          </div>

          <!-- Sales Growth Indicator -->
          <div>
            <div class="flex justify-between mb-2">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Sales Performance</span>
              <span class="text-sm text-gray-700 dark:text-gray-300">{{ getSalesPerformance() }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div 
                class="bg-green-600 h-2 rounded-full"
                :style="{ width: Math.min(getSalesPerformance(), 100) + '%' }"
              ></div>
            </div>
          </div>

          <!-- Cost Control Indicator -->
          <div>
            <div class="flex justify-between mb-2">
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Cost Control</span>
              <span class="text-sm text-gray-700 dark:text-gray-300">{{ getCostControl() }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
              <div 
                class="bg-yellow-600 h-2 rounded-full"
                :style="{ width: Math.min(getCostControl(), 100) + '%' }"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Profit Details Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Daily Profit Analysis</h3>
        <div class="flex gap-2">
          <button
            @click="exportProfit('pdf')"
            :disabled="loading"
            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            PDF
          </button>
          <button
            @click="exportProfit('excel')"
            :disabled="loading"
            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Excel
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sales</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">COGS</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Expenses</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Net Profit</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Margin %</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr
              v-for="item in profitByDate"
              :key="item.date"
              class="hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                {{ formatDate(item.date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400">
                {{ formatCurrency(item.sales) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400">
                {{ formatCurrency(item.cogs) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-orange-600 dark:text-orange-400">
                {{ formatCurrency(item.expenses || 0) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" :class="item.profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                {{ formatCurrency(item.profit) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm" :class="getMarginClass(item.sales, item.profit)">
                {{ calculateMargin(item.sales, item.profit) }}%
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import Chart from 'chart.js/auto'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import ReportFilters from '@/components/Reports/ReportFilters.vue'
import StatsCards from '@/components/Reports/StatsCards.vue'
import ReportChart from '@/components/Reports/ReportChart.vue'

const props = defineProps({
  summary: {
    type: Object,
    default: () => ({})
  },
  profitByDate: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

const loading = ref(false)
const comparisonChart = ref(null)
let chartInstance = null

// Stats Cards Configuration
const statsCards = computed(() => [
  {
    key: 'net_profit',
    label: 'Net Profit',
    value: props.summary.net_profit || 0,
    type: 'currency',
    icon: 'CurrencyIcon',
    borderColor: props.summary.net_profit >= 0 ? 'border-green-500' : 'border-red-500',
    iconBg: props.summary.net_profit >= 0 ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900',
    iconColor: props.summary.net_profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
  },
  {
    key: 'total_expenses',
    label: 'Total Expenses',
    value: props.summary.total_expenses || 0,
    type: 'currency',
    icon: 'CurrencyIcon',
    borderColor: 'border-orange-500',
    iconBg: 'bg-orange-100 dark:bg-orange-900',
    iconColor: 'text-orange-600 dark:text-orange-400'
  },
  {
    key: 'profit_margin',
    label: 'Net Margin',
    value: props.summary.profit_margin || 0,
    type: 'percentage',
    icon: 'ChartIcon',
    borderColor: 'border-blue-500',
    iconBg: 'bg-blue-100 dark:bg-blue-900',
    iconColor: 'text-blue-600 dark:text-blue-400'
  },
  {
    key: 'total_sales',
    label: 'Total Sales',
    value: props.summary.total_sales || 0,
    type: 'currency',
    icon: 'ShoppingCartIcon',
    borderColor: 'border-green-500',
    iconBg: 'bg-green-100 dark:bg-green-900',
    iconColor: 'text-green-600 dark:text-green-400'
  },
  {
    key: 'sales_count',
    label: 'Transactions',
    value: props.summary.sales_count || 0,
    type: 'number',
    icon: 'UsersIcon',
    borderColor: 'border-purple-500',
    iconBg: 'bg-purple-100 dark:bg-purple-900',
    iconColor: 'text-purple-600 dark:text-purple-400'
  },
  {
    key: 'total_receivables',
    label: 'Receivables (Credit Given)',
    value: props.summary.total_receivables || 0,
    type: 'currency',
    icon: 'ArrowUpIcon',
    borderColor: 'border-orange-500',
    iconBg: 'bg-orange-100 dark:bg-orange-900',
    iconColor: 'text-orange-600 dark:text-orange-400'
  },
  {
    key: 'total_payables',
    label: 'Payables (Credit Taken)',
    value: props.summary.total_payables || 0,
    type: 'currency',
    icon: 'ArrowDownIcon',
    borderColor: 'border-red-500',
    iconBg: 'bg-red-100 dark:bg-red-900',
    iconColor: 'text-red-600 dark:text-red-400'
  }
])

// Chart Data
const profitChartData = computed(() => {
  return props.profitByDate.map(item => ({
    label: item.date,
    value: item.profit
  }))
})

const formatCurrency = (value) => {
  return 'Rs ' + new Intl.NumberFormat('en-PK', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value || 0)
}

const formatDate = (value) => {
  if (!value) return ''
  return new Date(value).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const calculateMargin = (sales, profit) => {
  if (!sales || sales === 0) return 0
  return ((profit / sales) * 100).toFixed(1)
}

const getMarginClass = (sales, profit) => {
  const margin = calculateMargin(sales, profit)
  if (margin >= 20) return 'text-green-600 dark:text-green-400'
  if (margin >= 10) return 'text-yellow-600 dark:text-yellow-400'
  return 'text-red-600 dark:text-red-400'
}

const getSalesPerformance = () => {
  // Simple performance calculation based on sales count
  const performance = Math.min((props.summary.sales_count / 100) * 100, 100)
  return Math.round(performance)
}

const getCostControl = () => {
  // Cost control based on profit margin
  return Math.max(100 - (props.summary.profit_margin || 0), 0)
}

const createComparisonChart = () => {
  if (!comparisonChart.value || !props.profitByDate?.length) return
  
  if (chartInstance) {
    chartInstance.destroy()
  }

  const ctx = comparisonChart.value.getContext('2d')
  
  chartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: props.profitByDate.map(item => formatDate(item.date)),
      datasets: [
        {
          label: 'Sales',
          data: props.profitByDate.map(item => item.sales),
          backgroundColor: 'rgba(34, 197, 94, 0.8)',
          borderColor: 'rgba(34, 197, 94, 1)',
          borderWidth: 1
        },
        {
          label: 'Cost of Goods Sold',
          data: props.profitByDate.map(item => item.cogs),
          backgroundColor: 'rgba(239, 68, 68, 0.8)',
          borderColor: 'rgba(239, 68, 68, 1)',
          borderWidth: 1
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
        },
        tooltip: {
          mode: 'index',
          intersect: false,
          callbacks: {
            label: function(context) {
              let label = context.dataset.label || '';
              if (label) {
                label += ': ';
              }
              if (context.parsed.y !== null) {
                label += 'Rs ' + new Intl.NumberFormat('en-PK').format(context.parsed.y);
              }
              return label;
            }
          }
        }
      },
      scales: {
        x: {
          display: true,
          title: {
            display: true,
            text: 'Date'
          }
        },
        y: {
          display: true,
          title: {
            display: true,
            text: 'Amount (Rs)'
          },
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return 'Rs ' + new Intl.NumberFormat('en-PK').format(value);
            }
          }
        }
      }
    }
  })
}

const applyFilters = (newFilters) => {
  loading.value = true
  
  router.get('/reports/profit', newFilters, {
    preserveState: true,
    onFinish: () => {
      loading.value = false
      nextTick(() => {
        createComparisonChart()
      })
    }
  })
}

const exportProfit = (format) => {
  const params = new URLSearchParams({
    ...props.filters,
    format
  })
  
  let exportUrl = '/reports/profit/export-pdf'
  if (format === 'excel') {
    exportUrl = '/reports/profit/export-excel'
  } else if (format === 'csv') {
    exportUrl = '/reports/profit/export-csv'
  }
  
  window.open(`${exportUrl}?${params.toString()}`, '_blank')
}

onMounted(() => {
  nextTick(() => {
    createComparisonChart()
  })

  // Auto-refresh every 5 minutes
  // setInterval(() => {
  //   if (!loading.value) {
  //     applyFilters(props.filters)
  //   }
  // }, 300000)
})
</script>
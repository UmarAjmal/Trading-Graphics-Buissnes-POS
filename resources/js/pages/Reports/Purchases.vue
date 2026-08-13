<template>
  <AppLayout>
    <PageHeader
      title="Purchase Report"
      subtitle="Comprehensive purchase analytics and supplier insights"
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
      <!-- Purchase Trend Chart -->
      <ReportChart
        title="Purchase Trend"
        type="line"
        :data="chartData"
        :loading="loading"
      />

      <!-- Top Suppliers Chart -->
      <ReportChart
        title="Top Suppliers"
        type="bar"
        :data="topSuppliersChart"
        :loading="loading"
      />
    </div>

    <!-- Top Suppliers List -->
    <div class="grid grid-cols-1 lg:grid-cols-1 gap-6 mb-6">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Top Suppliers</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="supplier in topSuppliers"
            :key="supplier.id"
            class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg"
          >
            <div>
              <p class="font-medium text-gray-900 dark:text-gray-100">{{ supplier.supplier?.name || 'Unknown Supplier' }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ supplier.supplier?.email || 'No email' }}</p>
            </div>
            <div class="text-right">
              <p class="font-medium text-gray-900 dark:text-gray-100">{{ supplier.purchases }} orders</p>
              <p class="text-sm text-blue-600 dark:text-blue-400">Rs {{ Number(supplier.total_cost).toLocaleString('en-PK') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Purchase Table -->
    <ReportTable
      title="Purchase Details"
      :columns="tableColumns"
      :data="purchases.data"
      :pagination="purchases"
      :loading="loading"
      export-route="/reports/purchases/export-pdf"
      :filters="filters"
      @page-change="changePage"
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import ReportFilters from '@/components/Reports/ReportFilters.vue'
import StatsCards from '@/components/Reports/StatsCards.vue'
import ReportChart from '@/components/Reports/ReportChart.vue'
import ReportTable from '@/components/Reports/ReportTable.vue'

const props = defineProps({
  summary: {
    type: Object,
    default: () => ({})
  },
  purchasesByDate: {
    type: Array,
    default: () => []
  },
  topSuppliers: {
    type: Array,
    default: () => []
  },
  purchases: {
    type: Object,
    default: () => ({ data: [] })
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

const loading = ref(false)

// Stats Cards Configuration
const statsCards = computed(() => [
  {
    key: 'total_cost',
    label: 'Total Purchases',
    value: props.summary.total_cost || 0,
    type: 'currency',
    icon: 'ShoppingCartIcon',
    borderColor: 'border-blue-500',
    iconBg: 'bg-blue-100 dark:bg-blue-900',
    iconColor: 'text-blue-600 dark:text-blue-400'
  },
  {
    key: 'total_purchases',
    label: 'Total Orders',
    value: props.summary.total_purchases || 0,
    type: 'number',
    icon: 'ChartIcon',
    borderColor: 'border-green-500',
    iconBg: 'bg-green-100 dark:bg-green-900',
    iconColor: 'text-green-600 dark:text-green-400'
  },
  {
    key: 'avg_purchase',
    label: 'Avg. Purchase',
    value: props.summary.avg_purchase || 0,
    type: 'currency',
    icon: 'CurrencyIcon',
    borderColor: 'border-purple-500',
    iconBg: 'bg-purple-100 dark:bg-purple-900',
    iconColor: 'text-purple-600 dark:text-purple-400'
  },
  {
    key: 'unique_suppliers',
    label: 'Suppliers',
    value: props.summary.unique_suppliers || 0,
    type: 'number',
    icon: 'UsersIcon',
    borderColor: 'border-orange-500',
    iconBg: 'bg-orange-100 dark:bg-orange-900',
    iconColor: 'text-orange-600 dark:text-orange-400'
  }
])

// Chart Data
const chartData = computed(() => {
  return props.purchasesByDate.map(item => ({
    label: item.date,
    value: item.total
  }))
})

const topSuppliersChart = computed(() => {
  return props.topSuppliers.slice(0, 5).map(supplier => ({
    label: supplier.supplier?.name || 'Unknown',
    value: supplier.total_cost
  }))
})

// Table Configuration
const tableColumns = [
  { key: 'purchase_no', label: 'Purchase No', type: 'text' },
  { key: 'date', label: 'Date', type: 'date' },
  { key: 'supplier.name', label: 'Supplier', type: 'text' },
  { key: 'grand_total', label: 'Total Cost', type: 'currency' },
  { key: 'status', label: 'Status', type: 'text' },
  { key: 'created_at', label: 'Created', type: 'date' }
]

const applyFilters = (newFilters) => {
  loading.value = true
  
  router.get('/reports/purchases', newFilters, {
    preserveState: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

const changePage = (page) => {
  loading.value = true
  
  router.get('/reports/purchases', {
    ...props.filters,
    page
  }, {
    preserveState: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

onMounted(() => {
  // Auto-refresh every 5 minutes
  // setInterval(() => {
  //   if (!loading.value) {
  //     applyFilters(props.filters)
  //   }
  // }, 300000)
})
</script>
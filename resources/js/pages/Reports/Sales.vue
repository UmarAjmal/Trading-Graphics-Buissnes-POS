<template>
  <AppLayout>
    <PageHeader
      title="Sales Report"
      subtitle="Comprehensive sales analytics and performance insights"
    />

    <!-- Filters -->
    <ReportFilters
      :filters="filters"
      :loading="loading"
      @apply="applyFilters"
    >
      <div class="min-w-[200px]">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Payment Method
        </label>
        <select
          v-model="paymentMethod"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
        >
          <option value="">All Methods</option>
          <option value="cash">Cash</option>
          <option value="credit">Credit</option>
          <option value="bank">Bank Transfer</option>
        </select>
      </div>
    </ReportFilters>

    <!-- Stats Cards -->
    <StatsCards
      :stats="statsCards"
      :loading="loading"
    />

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <!-- Sales Trend Chart -->
      <ReportChart
        title="Sales Trend"
        type="line"
        :data="chartData"
        :loading="loading"
      />

      <!-- Top Products Chart -->
      <ReportChart
        title="Top Products"
        type="doughnut"
        :data="topProductsChart"
        :loading="loading"
      />
    </div>

    <!-- Top Lists -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <!-- Top Products -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Top Products</h3>
        <div class="space-y-3">
          <div
            v-for="product in topProducts"
            :key="product.id"
            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
          >
            <div>
              <p class="font-medium text-gray-900 dark:text-gray-100">{{ product.name }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">SKU: {{ product.sku }}</p>
            </div>
            <div class="text-right">
              <p class="font-medium text-gray-900 dark:text-gray-100">{{ product.quantity_sold }} sold</p>
              <p class="text-sm text-green-600 dark:text-green-400">Rs {{ Number(product.total_sales).toLocaleString('en-PK') }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Customers -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Top Customers</h3>
        <div class="space-y-3">
          <div
            v-for="customer in topCustomers"
            :key="customer.id"
            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
          >
            <div>
              <p class="font-medium text-gray-900 dark:text-gray-100">{{ customer.name }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ customer.email }}</p>
            </div>
            <div class="text-right">
              <p class="font-medium text-gray-900 dark:text-gray-100">{{ customer.transactions }} orders</p>
              <p class="text-sm text-green-600 dark:text-green-400">Rs {{ Number(customer.total_spent).toLocaleString('en-PK') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Sales Table -->
    <ReportTable
      title="Sales Details"
      :columns="tableColumns"
      :data="sales.data"
      :pagination="sales"
      :loading="loading"
      export-route="/reports/sales/export-pdf"
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
  salesByDate: {
    type: Array,
    default: () => []
  },
  topProducts: {
    type: Array,
    default: () => []
  },
  topCustomers: {
    type: Array,
    default: () => []
  },
  sales: {
    type: Object,
    default: () => ({ data: [] })
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

const loading = ref(false)
const paymentMethod = ref(props.filters.payment_method || '')

// Stats Cards Configuration
const statsCards = computed(() => [
  {
    key: 'total_sales',
    label: 'Total Sales',
    value: props.summary.total_sales || 0,
    type: 'currency',
    icon: 'CurrencyIcon',
    borderColor: 'border-green-500',
    iconBg: 'bg-green-100 dark:bg-green-900',
    iconColor: 'text-green-600 dark:text-green-400'
  },
  {
    key: 'total_transactions',
    label: 'Transactions',
    value: props.summary.total_transactions || 0,
    type: 'number',
    icon: 'ShoppingCartIcon',
    borderColor: 'border-blue-500',
    iconBg: 'bg-blue-100 dark:bg-blue-900',
    iconColor: 'text-blue-600 dark:text-blue-400'
  },
  {
    key: 'bank_sales', 
    label: 'Bank Sales',
    value: props.summary.bank_sales || 0,
    type: 'currency',
    icon: 'BriefcaseIcon',
    borderColor: 'border-purple-500',
    iconBg: 'bg-purple-100 dark:bg-purple-900',
    iconColor: 'text-purple-600 dark:text-purple-400'
  },
  {
    key: 'cash_sales',
    label: 'Cash Sales',
    value: props.summary.cash_sales || 0,
    type: 'currency',
    icon: 'CurrencyIcon',
    borderColor: 'border-yellow-500',
    iconBg: 'bg-yellow-100 dark:bg-yellow-900',
    iconColor: 'text-yellow-600 dark:text-yellow-400'
  },
  {
    key: 'credit_sales',
    label: 'Credit Sales',
    value: props.summary.credit_sales || 0,
    type: 'currency',
    icon: 'CreditCardIcon',
    borderColor: 'border-red-500',
    iconBg: 'bg-red-100 dark:bg-red-900',
    iconColor: 'text-red-600 dark:text-red-400'
  }
])

// Chart Data
const chartData = computed(() => {
  return props.salesByDate.map(item => ({
    label: item.date,
    value: item.total
  }))
})

const topProductsChart = computed(() => {
  return props.topProducts.slice(0, 5).map(product => ({
    label: product.product?.name || 'Unknown',
    value: product.quantity_sold
  }))
})

// Table Configuration
const tableColumns = [
  { key: 'invoice_no', label: 'Invoice No', type: 'text' },
  { key: 'created_at', label: 'Date', type: 'date' },
  { key: 'customer.name', label: 'Customer', type: 'text' },
  { key: 'bill_total', label: 'Total', type: 'currency' },
  { key: 'payment_type', label: 'Payment Method', type: 'text' },
  { key: 'user.name', label: 'Cashier', type: 'text' }
]

const applyFilters = (newFilters) => {
  loading.value = true
  
  router.get('/reports/sales', {
    ...newFilters,
    payment_method: paymentMethod.value
  }, {
    preserveState: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

const changePage = (page) => {
  loading.value = true
  
  router.get('/reports/sales', {
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
  // Auto-refresh removed as per user request to prevent interruption
  // setInterval(() => {
  //   if (!loading.value) {
  //     applyFilters(props.filters)
  //   }
  // }, 300000)
})
</script>
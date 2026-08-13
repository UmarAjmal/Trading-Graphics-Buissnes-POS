<template>
  <AppLayout>
    <PageHeader
      title="Stock Report"
      subtitle="Detailed analysis of current inventory and stock value"
    />

    <!-- Custom Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6 print:hidden">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search Product</label>
                <input 
                    type="text" 
                    v-model="filters.search" 
                    placeholder="Search by name or SKU..."
                    class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                <select v-model="filters.category_id" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Categories</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sold Period (Start)</label>
                <input type="date" v-model="filters.start_date" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sold Period (End)</label>
                <input type="date" v-model="filters.end_date" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
        </div>
        <div class="flex justify-end mt-4">
            <button 
                @click="applyFilters" 
                :disabled="loading"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center"
            >
                <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ loading ? 'Loading...' : 'Apply Filters' }}
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <StatsCards :stats="statsCards" :loading="loading" />

    <!-- Stock Table -->
    <ReportTable
      title="Stock Details"
      :columns="tableColumns"
      :data="products"
      :pagination="null"
      :loading="loading"
      :export-route="route('reports.stock')"
      :filters="filters"
    >
        <template #cell-product="{ item }">
            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ item.name }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ item.sku }}</div>
        </template>

        <template #cell-category="{ item }">
            {{ item.category?.name || 'N/A' }}
        </template>

        <template #cell-current_stock="{ item }">
            <div v-if="item.type === 'panaflex_roll'">
                {{ formatNumber(item.stock_meters) }} m
                <div class="text-xs text-gray-500">({{ formatNumber(item.current_stock) }} sq.ft)</div>
            </div>
            <div v-else>
                {{ formatNumber(item.stock_quantity) }} {{ item.unit?.symbol }}
            </div>
        </template>

        <template #cell-sold_qty_period="{ item }">
            <span v-if="item.type === 'panaflex_roll'">
                {{ formatNumber(item.sold_qty_period) }} sq.ft
            </span>
            <span v-else>
                {{ formatNumber(item.sold_qty_period) }} {{ item.unit?.symbol }}
            </span>
        </template>
    </ReportTable>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import StatsCards from '@/components/Reports/StatsCards.vue'
import ReportTable from '@/components/Reports/ReportTable.vue'

const props = defineProps({
  products: Array,
  filters: Object,
  categories: Array,
  totals: Object
})

const filters = ref({
  start_date: props.filters.start_date,
  end_date: props.filters.end_date,
  category_id: props.filters.category_id || '',
  search: props.filters.search || ''
})

const loading = ref(false)

const formatNumber = (num) => {
    return parseFloat(num || 0).toFixed(2)
}

const applyFilters = () => {
  loading.value = true
  router.get(route('reports.stock'), filters.value, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

const statsCards = computed(() => [
  {
    key: 'total_items',
    label: 'Total Products',
    value: props.totals.total_items,
    type: 'number',
    icon: 'ChartIcon',
    borderColor: 'border-blue-500',
    iconBg: 'bg-blue-100 dark:bg-blue-900',
    iconColor: 'text-blue-600 dark:text-blue-400'
  },
  {
    key: 'total_cost_value',
    label: 'Total Stock Value (Cost)',
    value: props.totals.total_cost_value,
    type: 'currency',
    icon: 'CurrencyIcon',
    borderColor: 'border-yellow-500',
    iconBg: 'bg-yellow-100 dark:bg-yellow-900',
    iconColor: 'text-yellow-600 dark:text-yellow-400'
  },
  {
    key: 'total_sale_value',
    label: 'Total Stock Value (Sale)',
    value: props.totals.total_sale_value,
    type: 'currency',
    icon: 'CurrencyIcon',
    borderColor: 'border-green-500',
    iconBg: 'bg-green-100 dark:bg-green-900',
    iconColor: 'text-green-600 dark:text-green-400'
  }
])

const tableColumns = [
    { key: 'product', label: 'Product' },
    { key: 'category', label: 'Category' },
    { key: 'current_stock', label: 'Current Stock' },
    { key: 'purchase_rate', label: 'Cost Price', type: 'currency' },
    { key: 'sale_rate', label: 'Sale Price', type: 'currency' },
    { key: 'stock_value_cost', label: 'Stock Value (Cost)', type: 'currency' },
    { key: 'sold_qty_period', label: 'Sold (Period)' }
]
</script>

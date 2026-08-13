<template>
  <AppLayout>
    <PageHeader
      title="Expense Report"
      subtitle="Detailed analysis of business expenses"
    />

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Period</label>
                <select v-model="filters.period" @change="handlePeriodChange" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="daily">Today</option>
                    <option value="weekly">This Week</option>
                    <option value="monthly">This Month</option>
                    <option value="yearly">This Year</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>
            <div v-if="filters.period === 'custom'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                <input type="date" v-model="filters.start_date" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div v-if="filters.period === 'custom'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                <input type="date" v-model="filters.end_date" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                <select v-model="filters.category_id" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Categories</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
            </div>
            <div class="flex items-end">
                <button @click="applyFilters" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    Apply Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Expenses</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(summary.total_expenses) }}</p>
                </div>
                <div class="p-3 bg-red-100 dark:bg-red-900 rounded-full">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Average Expense</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(summary.avg_expense) }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Transactions</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ summary.count }}</p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Expense Trend -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Expense Trend</h3>
            <div class="relative" style="height: 300px;">
                <canvas ref="trendChart" class="w-full h-full"></canvas>
            </div>
        </div>

        <!-- Expenses by Category -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Expenses by Category</h3>
            <div class="relative" style="height: 300px;">
                <canvas ref="categoryChart" class="w-full h-full"></canvas>
            </div>
        </div>
    </div>

    <!-- Expense List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Expense Details</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Source</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="expense in expenses.data" :key="expense.id">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ formatDate(expense.date) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{{ expense.category?.name || 'Uncategorized' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ expense.description || '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span :class="expense.payment_source === 'drawer' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                {{ expense.payment_source === 'drawer' ? 'Cash Drawer' : 'External' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(expense.amount) }}</td>
                    </tr>
                    <tr v-if="expenses.data.length === 0">
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No expenses found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="mt-4 flex justify-between items-center" v-if="expenses.links">
             <div class="flex space-x-1">
                <Link v-for="(link, k) in expenses.links" :key="k" :href="link.url" v-html="link.label" :class="['px-3 py-1 border rounded text-sm', link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700']" :disabled="!link.url" />
            </div>
        </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import Chart from 'chart.js/auto'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import { formatCurrency } from '@/utils/currency'

const props = defineProps({
  expenses: Object,
  categories: Array,
  summary: Object,
  filters: Object
})

const filters = reactive({
    period: props.filters.period || 'daily',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    category_id: props.filters.category_id || ''
})

const trendChart = ref(null)
const categoryChart = ref(null)
let trendChartInstance = null
let categoryChartInstance = null

const handlePeriodChange = () => {
    if (filters.period !== 'custom') {
        applyFilters()
    }
}

const applyFilters = () => {
    router.get(route('reports.expenses'), filters, { preserveState: true, preserveScroll: true })
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString()
}

const initCharts = () => {
    // Trend Chart
    if (trendChart.value) {
        if (trendChartInstance) trendChartInstance.destroy()
        const ctx = trendChart.value.getContext('2d')
        trendChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: Object.keys(props.summary.by_date),
                datasets: [{
                    label: 'Expenses',
                    data: Object.values(props.summary.by_date),
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        })
    }

    // Category Chart
    if (categoryChart.value) {
        if (categoryChartInstance) categoryChartInstance.destroy()
        const ctx = categoryChart.value.getContext('2d')
        categoryChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(props.summary.by_category),
                datasets: [{
                    data: Object.values(props.summary.by_category),
                    backgroundColor: [
                        '#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#6366F1', '#8B5CF6', '#EC4899'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        })
    }
}

onMounted(() => {
    initCharts()
})

watch(() => props.summary, () => {
    initCharts()
}, { deep: true })

</script>
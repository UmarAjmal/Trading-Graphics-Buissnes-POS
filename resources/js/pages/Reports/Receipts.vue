<template>
  <AppLayout>
    <PageHeader
      title="Receipt Report (Cash In)"
      subtitle="Comprehensive customer collections, payments received, and cash inflow audit"
    >
      <template #actions>
        <div class="flex flex-wrap gap-2">
          <a
            :href="route('dashboard')"
            class="ghost-btn"
          >
            <ModernIcon name="arrow-left" size="sm" />
            <span>Back to Dashboard</span>
          </a>
          <a
            :href="route('reports.receipts.export-pdf', { 
                start_date: filters.start_date, 
                end_date: filters.end_date,
                payment_method: filters.payment_method,
                customer_id: filters.customer_id
            })"
            class="primary-soft-btn"
            target="_blank"
          >
            <ModernIcon name="file-text" size="sm" />
            <span>Export PDF</span>
          </a>
        </div>
      </template>
    </PageHeader>

    <!-- Filters Section -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 mb-6 border border-gray-100 dark:border-gray-700">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
            Payment Method
          </label>
          <select
            v-model="filters.payment_method"
            @change="applyFilters"
            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="all">All Payment Methods</option>
            <option value="cash">Cash (Galla Only)</option>
            <option value="bank_all">Bank (All Transfers)</option>
            <option value="bank">Bank</option>
            <option value="bank_transfer">Bank Transfer</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
            Filter by Customer
          </label>
          <select
            v-model="filters.customer_id"
            @change="applyFilters"
            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="all">All Customers</option>
            <option v-for="c in customers" :key="c.id" :value="c.id">
              {{ c.name }} {{ c.phone ? `(${c.phone})` : '' }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
            From Date
          </label>
          <input 
            type="date" 
            v-model="filters.start_date"
            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white"
          >
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
            To Date
          </label>
          <input 
            type="date" 
            v-model="filters.end_date"
            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white"
          >
        </div>

        <div class="flex gap-2">
          <button 
            @click="applyFilters" 
            class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors"
          >
            Apply Filter
          </button>
          <button 
            @click="resetFilters" 
            class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-colors"
            title="Reset Filters"
          >
            Reset
          </button>
        </div>
      </div>

      <!-- Search & Quick Presets -->
      <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-1.5">
          <span class="text-xs text-gray-400 font-medium mr-1">Quick Filters:</span>
          <button 
            @click="setPreset('today')" 
            class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/30 text-gray-600 dark:text-gray-300 transition-colors"
          >
            Today
          </button>
          <button 
            @click="setPreset('yesterday')" 
            class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/30 text-gray-600 dark:text-gray-300 transition-colors"
          >
            Yesterday
          </button>
          <button 
            @click="setPreset('this_week')" 
            class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/30 text-gray-600 dark:text-gray-300 transition-colors"
          >
            This Week
          </button>
          <button 
            @click="setPreset('this_month')" 
            class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/30 text-gray-600 dark:text-gray-300 transition-colors"
          >
            This Month
          </button>
          <button 
            @click="setPreset('all_time')" 
            class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-emerald-50 hover:text-emerald-700 dark:hover:bg-emerald-900/30 text-gray-600 dark:text-gray-300 transition-colors"
          >
            All Time
          </button>
        </div>

        <div class="w-full md:w-72">
          <input 
            type="text" 
            v-model="search" 
            placeholder="Search by customer, phone, voucher, note..." 
            class="w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:bg-gray-700 dark:text-white placeholder-gray-400"
          >
        </div>
      </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
      <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border-t-4 border-emerald-500 hover:-translate-y-0.5 transition-transform">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Receipts (Cash In)</span>
          <span class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600">
            <ModernIcon name="dollar" size="sm" />
          </span>
        </div>
        <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
          Rs {{ formatNumber(summary.total_amount) }}
        </div>
        <div class="text-xs text-gray-400 mt-1">Total customer inflow</div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border-t-4 border-green-500 hover:-translate-y-0.5 transition-transform">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Cash (Galla Receipts)</span>
          <span class="w-8 h-8 rounded-lg bg-green-50 dark:bg-green-900/30 flex items-center justify-center text-green-600">
            <ModernIcon name="cash" size="sm" />
          </span>
        </div>
        <div class="text-2xl font-black text-green-700 dark:text-green-300">
          Rs {{ formatNumber(summary.cash_amount) }}
        </div>
        <div class="text-xs text-gray-400 mt-1">Physical drawer receipts</div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border-t-4 border-blue-500 hover:-translate-y-0.5 transition-transform">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Bank Transfers</span>
          <span class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
            <ModernIcon name="currency-rupee" size="sm" />
          </span>
        </div>
        <div class="text-2xl font-black text-blue-600 dark:text-blue-400">
          Rs {{ formatNumber(summary.bank_amount) }}
        </div>
        <div class="text-xs text-gray-400 mt-1">Direct bank deposits</div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border-t-4 border-purple-500 hover:-translate-y-0.5 transition-transform">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Vouchers</span>
          <span class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-600">
            <ModernIcon name="clipboard-document-list" size="sm" />
          </span>
        </div>
        <div class="text-2xl font-black text-purple-600 dark:text-purple-400">
          {{ summary.total_count }}
        </div>
        <div class="text-xs text-gray-400 mt-1">Receipt entries recorded</div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border-t-4 border-indigo-500 hover:-translate-y-0.5 transition-transform">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Avg Receipt Size</span>
          <span class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600">
            <ModernIcon name="trending-up" size="sm" />
          </span>
        </div>
        <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400">
          Rs {{ formatNumber(summary.avg_amount) }}
        </div>
        <div class="text-xs text-gray-400 mt-1">Per transaction average</div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
      <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50/50 dark:bg-gray-700/50">
        <h3 class="font-bold text-gray-900 dark:text-gray-100 flex items-center">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 mr-2"></span>
          Detailed Receipts List (Cash In Flow)
        </h3>
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
          Showing {{ filteredReceipts.length }} of {{ receipts.length }} vouchers
        </span>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase font-semibold text-gray-500 dark:text-gray-300">
            <tr>
              <th scope="col" class="px-5 py-3 text-left">Voucher #</th>
              <th scope="col" class="px-5 py-3 text-left">Date</th>
              <th scope="col" class="px-5 py-3 text-left">Customer / Party Name</th>
              <th scope="col" class="px-5 py-3 text-left">Phone</th>
              <th scope="col" class="px-5 py-3 text-center">Payment Method</th>
              <th scope="col" class="px-5 py-3 text-left">Note / Reference</th>
              <th scope="col" class="px-5 py-3 text-right">Amount Received (Rs)</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700 text-sm">
            <tr v-if="filteredReceipts.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                <ModernIcon name="file-text" size="lg" class="mx-auto mb-2 text-gray-300" />
                <p>No receipt records found matching your filters.</p>
              </td>
            </tr>
            <tr 
              v-for="r in filteredReceipts" 
              :key="r.id" 
              class="hover:bg-gray-50/80 dark:hover:bg-gray-700/50 transition-colors"
            >
              <td class="px-5 py-3.5 whitespace-nowrap font-mono text-xs font-semibold text-gray-600 dark:text-gray-300">
                VCH-{{ String(r.id).padStart(5, '0') }}
              </td>
              <td class="px-5 py-3.5 whitespace-nowrap text-gray-700 dark:text-gray-300">
                {{ formatDate(r.payment_date) }}
              </td>
              <td class="px-5 py-3.5 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                {{ r.customer?.name || 'Walk-in Customer' }}
              </td>
              <td class="px-5 py-3.5 whitespace-nowrap text-gray-500 dark:text-gray-400 text-xs">
                {{ r.customer?.phone || '-' }}
              </td>
              <td class="px-5 py-3.5 whitespace-nowrap text-center">
                <span 
                  v-if="r.payment_method === 'cash'"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300"
                >
                  💵 Cash
                </span>
                <span 
                  v-else-if="['bank', 'bank_transfer'].includes(r.payment_method)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300"
                >
                  🏦 Bank
                </span>
                <span 
                  v-else
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 capitalize"
                >
                  {{ r.payment_method }}
                </span>
              </td>
              <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 text-xs max-w-xs truncate">
                {{ r.note || '-' }}
              </td>
              <td class="px-5 py-3.5 whitespace-nowrap text-right font-bold text-emerald-600 dark:text-emerald-400">
                Rs {{ formatNumber(r.amount) }}
              </td>
            </tr>
          </tbody>
          <tfoot v-if="filteredReceipts.length > 0" class="bg-gray-50 dark:bg-gray-700/80 font-bold text-sm">
            <tr>
              <td colspan="6" class="px-5 py-3 text-right uppercase tracking-wider text-gray-600 dark:text-gray-300">
                Filtered Total:
              </td>
              <td class="px-5 py-3 text-right text-emerald-600 dark:text-emerald-400 text-base">
                Rs {{ formatNumber(filteredTotal) }}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import ModernIcon from '@/Components/ModernIcon.vue'

const props = defineProps({
  receipts: {
    type: Array,
    default: () => []
  },
  customers: {
    type: Array,
    default: () => []
  },
  summary: {
    type: Object,
    default: () => ({
      total_amount: 0,
      cash_amount: 0,
      bank_amount: 0,
      other_amount: 0,
      total_count: 0,
      avg_amount: 0
    })
  },
  filters: {
    type: Object,
    default: () => ({
      start_date: '',
      end_date: '',
      payment_method: 'all',
      customer_id: 'all',
      search: ''
    })
  }
})

const search = ref('')
const filters = ref({ ...props.filters })

const formatNumber = (val) => {
  return new Intl.NumberFormat('en-PK', { maximumFractionDigits: 2, minimumFractionDigits: 0 }).format(val || 0)
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

const filteredReceipts = computed(() => {
  if (!search.value) return props.receipts
  const q = search.value.toLowerCase().trim()
  return props.receipts.filter(r => {
    const custName = (r.customer?.name || '').toLowerCase()
    const phone = (r.customer?.phone || '').toLowerCase()
    const note = (r.note || '').toLowerCase()
    const vch = `vch-${String(r.id).padStart(5, '0')}`.toLowerCase()
    return custName.includes(q) || phone.includes(q) || note.includes(q) || vch.includes(q)
  })
})

const filteredTotal = computed(() => {
  return filteredReceipts.value.reduce((acc, r) => acc + Number(r.amount || 0), 0)
})

const applyFilters = () => {
  router.get(route('reports.receipts'), {
    start_date: filters.value.start_date || undefined,
    end_date: filters.value.end_date || undefined,
    payment_method: filters.value.payment_method !== 'all' ? filters.value.payment_method : undefined,
    customer_id: filters.value.customer_id !== 'all' ? filters.value.customer_id : undefined,
  }, {
    preserveState: true,
    preserveScroll: true
  })
}

const resetFilters = () => {
  filters.value.start_date = ''
  filters.value.end_date = ''
  filters.value.payment_method = 'all'
  filters.value.customer_id = 'all'
  search.value = ''
  applyFilters()
}

const setPreset = (preset) => {
  const now = new Date()
  const toDateStr = (d) => d.toISOString().split('T')[0]

  if (preset === 'today') {
    filters.value.start_date = toDateStr(now)
    filters.value.end_date = toDateStr(now)
  } else if (preset === 'yesterday') {
    const yest = new Date()
    yest.setDate(now.getDate() - 1)
    filters.value.start_date = toDateStr(yest)
    filters.value.end_date = toDateStr(yest)
  } else if (preset === 'this_week') {
    const firstDay = new Date(now.setDate(now.getDate() - now.getDay() + 1))
    filters.value.start_date = toDateStr(firstDay)
    filters.value.end_date = toDateStr(new Date())
  } else if (preset === 'this_month') {
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1)
    filters.value.start_date = toDateStr(firstDay)
    filters.value.end_date = toDateStr(now)
  } else if (preset === 'all_time') {
    filters.value.start_date = ''
    filters.value.end_date = ''
  }
  applyFilters()
}
</script>

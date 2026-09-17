<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <span class="p-2 bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-lg">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
              </svg>
            </span>
            Supplier Purchase Returns
          </h1>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Track goods returned to suppliers, deducted inventory stock, and supplier debit adjustments
          </p>
        </div>

        <div class="flex items-center gap-3">
          <Link
            :href="route('purchases.index')"
            class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-sm font-medium transition"
          >
            Purchases List
          </Link>
          <Link
            :href="route('returns.purchases.create')"
            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium flex items-center gap-2 shadow-sm transition"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Process Purchase Return
          </Link>
        </div>
      </div>

      <!-- KPI Summary Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Total Returns -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Returns</span>
            <span class="p-2 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-lg text-xs font-bold">
              {{ summary.total_count }} Returns
            </span>
          </div>
          <div class="text-xl font-bold text-gray-900 dark:text-white mt-2">
            PKR {{ formatNumber(summary.total_amount) }}
          </div>
          <div class="text-xs text-amber-600 dark:text-amber-400 font-medium mt-1">
            Deducted from Inventory
          </div>
        </div>

        <!-- Cash Refunded -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cash Received</span>
            <span class="p-1.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-md text-xs font-medium">Cash In</span>
          </div>
          <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">
            PKR {{ formatNumber(summary.cash_refunded) }}
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Cash collected from supplier
          </div>
        </div>

        <!-- Credit / Ledger Adjusted -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Payables Reduced</span>
            <span class="p-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-md text-xs font-medium">Ledger</span>
          </div>
          <div class="text-xl font-bold text-blue-600 dark:text-blue-400 mt-2">
            PKR {{ formatNumber(summary.credit_adjusted) }}
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Supplier debt reduced
          </div>
        </div>

        <!-- Bank Received -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bank Received</span>
            <span class="p-1.5 bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-md text-xs font-medium">Bank Transfer</span>
          </div>
          <div class="text-xl font-bold text-purple-600 dark:text-purple-400 mt-2">
            PKR {{ formatNumber(summary.bank_refunded) }}
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Online bank receipt
          </div>
        </div>

        <!-- Avg Return -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Average Return</span>
            <span class="p-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md text-xs font-medium">Avg Value</span>
          </div>
          <div class="text-xl font-bold text-gray-900 dark:text-white mt-2">
            PKR {{ summary.total_count ? formatNumber(summary.total_amount / summary.total_count) : '0.00' }}
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Per supplier return
          </div>
        </div>
      </div>

      <!-- Filters Section -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm space-y-4">
        <div class="flex flex-wrap items-center gap-2">
          <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mr-1">Quick Dates:</span>
          <button
            v-for="p in periods"
            :key="p.key"
            @click="setPeriod(p.key)"
            :class="[
              'px-3 py-1.5 text-xs font-medium rounded-lg transition',
              activePeriod === p.key
                ? 'bg-amber-600 text-white shadow-sm'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
            ]"
          >
            {{ p.label }}
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3">
          <!-- Search -->
          <div class="lg:col-span-2">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
            <div class="relative">
              <input
                v-model="formFilters.search"
                @input="debouncedSearch"
                type="text"
                placeholder="Search return #, PO bill #, supplier..."
                class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-white"
              />
              <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
            </div>
          </div>

          <!-- Supplier Filter -->
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Supplier</label>
            <select
              v-model="formFilters.supplier_id"
              @change="applyFilters"
              class="w-full py-2 px-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-white"
            >
              <option value="all">All Suppliers</option>
              <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
          </div>

          <!-- Refund Type Filter -->
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Refund Method</label>
            <select
              v-model="formFilters.refund_type"
              @change="applyFilters"
              class="w-full py-2 px-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-white"
            >
              <option value="all">All Refund Methods</option>
              <option value="cash">Cash Received</option>
              <option value="credit">Credit / Payable Adjusted</option>
              <option value="bank">Bank Transfer</option>
            </select>
          </div>

          <!-- Reset Button -->
          <div class="flex items-end">
            <button
              @click="resetFilters"
              class="w-full py-2 px-3 text-sm border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
            >
              Reset Filters
            </button>
          </div>
        </div>

        <!-- Custom Date Range -->
        <div v-if="activePeriod === 'custom'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">From Date</label>
            <input
              v-model="formFilters.start_date"
              @change="applyFilters"
              type="date"
              class="w-full py-2 px-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-white"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">To Date</label>
            <input
              v-model="formFilters.end_date"
              @change="applyFilters"
              type="date"
              class="w-full py-2 px-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-amber-500 dark:bg-gray-700 dark:text-white"
            />
          </div>
        </div>
      </div>

      <!-- Purchase Returns Table -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
              <tr>
                <th scope="col" class="py-3 px-4 text-left">Return #</th>
                <th scope="col" class="py-3 px-4 text-left">Purchase Bill</th>
                <th scope="col" class="py-3 px-4 text-left">Supplier</th>
                <th scope="col" class="py-3 px-4 text-left">Date & Time</th>
                <th scope="col" class="py-3 px-4 text-center">Items Returned</th>
                <th scope="col" class="py-3 px-4 text-center">Refund Method</th>
                <th scope="col" class="py-3 px-4 text-right">Grand Total</th>
                <th scope="col" class="py-3 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-sm">
              <tr
                v-for="ret in returns.data"
                :key="ret.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition"
              >
                <!-- Return # -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <Link
                    :href="route('returns.purchases.show', ret.id)"
                    class="font-semibold text-amber-600 dark:text-amber-400 hover:underline flex items-center gap-1.5"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    {{ ret.return_no }}
                  </Link>
                  <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs" :title="ret.reason">
                    {{ ret.reason }}
                  </div>
                </td>

                <!-- Original Purchase -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <div class="font-medium text-gray-900 dark:text-white">
                    {{ ret.purchase?.purchase_no || 'Direct Return' }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    Orig Bill: PKR {{ ret.purchase?.grand_total ? formatNumber(ret.purchase.grand_total) : '0.00' }}
                  </div>
                </td>

                <!-- Supplier -->
                <td class="py-3 px-4 whitespace-nowrap">
                  <div class="font-medium text-gray-900 dark:text-white">
                    {{ ret.supplier?.name || 'Direct Supplier' }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ ret.supplier?.phone || '-' }}
                  </div>
                </td>

                <!-- Date & Time -->
                <td class="py-3 px-4 whitespace-nowrap text-gray-600 dark:text-gray-300">
                  <div>{{ formatDate(ret.returned_at) }}</div>
                  <div class="text-xs text-gray-400">{{ formatTime(ret.returned_at) }}</div>
                </td>

                <!-- Items Returned -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                    {{ ret.items?.length || 0 }} Items
                  </span>
                </td>

                <!-- Refund Method -->
                <td class="py-3 px-4 text-center whitespace-nowrap">
                  <span
                    :class="[
                      'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold uppercase',
                      ret.refund_type === 'cash' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' :
                      ret.refund_type === 'credit' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' :
                      'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300'
                    ]"
                  >
                    {{ ret.refund_type || 'credit' }}
                  </span>
                </td>

                <!-- Grand Total -->
                <td class="py-3 px-4 text-right whitespace-nowrap">
                  <div class="font-bold text-amber-600 dark:text-amber-400">
                    PKR {{ formatNumber(ret.grand_total) }}
                  </div>
                </td>

                <!-- Actions -->
                <td class="py-3 px-4 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1.5">
                    <Link
                      :href="route('returns.purchases.show', ret.id)"
                      class="p-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                      title="View Details"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                      </svg>
                    </Link>

                    <a
                      :href="route('prints.purchase-return.a4', ret.id)"
                      target="_blank"
                      class="p-1.5 text-blue-600 hover:text-blue-800 dark:text-blue-400 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30"
                      title="Print A4"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                      </svg>
                    </a>

                    <a
                      :href="route('prints.purchase-return.80mm', ret.id)"
                      target="_blank"
                      class="p-1.5 text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-900/30"
                      title="Print Thermal 80mm"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                      </svg>
                    </a>

                    <button
                      type="button"
                      @click="deleteReturn(ret)"
                      class="p-1.5 text-red-500 hover:text-red-700 dark:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30 transition"
                      title="Delete & Reverse Purchase Return"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="returns.data.length === 0">
                <td colspan="8" class="text-center py-12 text-gray-500 dark:text-gray-400">
                  <div class="flex flex-col items-center justify-center">
                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="font-medium text-base">No purchase returns found.</p>
                    <p class="text-xs text-gray-400 mt-1">Try adjusting your date filters or search keyword.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="returns.links && returns.links.length > 3" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
          <div class="text-xs text-gray-500 dark:text-gray-400">
            Showing {{ returns.from || 0 }} to {{ returns.to || 0 }} of {{ returns.total }} returns
          </div>
          <div class="flex gap-1">
            <Link
              v-for="(link, i) in returns.links"
              :key="i"
              :href="link.url || '#'"
              v-html="link.label"
              :class="[
                'px-3 py-1 text-xs rounded-md transition',
                link.active ? 'bg-amber-600 text-white font-semibold' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200',
                !link.url ? 'opacity-50 cursor-not-allowed' : ''
              ]"
            />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
  returns: Object,
  suppliers: Array,
  summary: Object,
  filters: Object,
})

const activePeriod = ref(props.filters?.period || 'all')

const periods = [
  { key: 'all', label: 'All Time' },
  { key: 'today', label: 'Today' },
  { key: 'yesterday', label: 'Yesterday' },
  { key: '7days', label: 'Last 7 Days' },
  { key: 'month', label: 'This Month' },
  { key: 'custom', label: 'Custom Range' },
]

const formFilters = reactive({
  search: props.filters?.search || '',
  period: props.filters?.period || '',
  start_date: props.filters?.start_date || '',
  end_date: props.filters?.end_date || '',
  supplier_id: props.filters?.supplier_id || 'all',
  refund_type: props.filters?.refund_type || 'all',
})

let debounceTimeout = null
const debouncedSearch = () => {
  clearTimeout(debounceTimeout)
  debounceTimeout = setTimeout(() => {
    applyFilters()
  }, 400)
}

const setPeriod = (period) => {
  activePeriod.value = period
  if (period === 'custom') {
    formFilters.period = ''
  } else {
    formFilters.period = period === 'all' ? '' : period
    formFilters.start_date = ''
    formFilters.end_date = ''
    applyFilters()
  }
}

const applyFilters = () => {
  router.get(route('returns.purchases.index'), formFilters, {
    preserveState: true,
    replace: true,
  })
}

const resetFilters = () => {
  formFilters.search = ''
  formFilters.period = ''
  formFilters.start_date = ''
  formFilters.end_date = ''
  formFilters.supplier_id = 'all'
  formFilters.refund_type = 'all'
  activePeriod.value = 'all'
  applyFilters()
}

const deleteReturn = (ret) => {
  if (confirm(`Are you sure you want to delete and reverse Purchase Return #${ret.return_no}? This will restock the returned goods back to inventory and restore our payable liability in the supplier ledger.`)) {
    router.delete(route('returns.purchases.destroy', ret.id))
  }
}

const formatNumber = (num) => {
  const val = Number(num) || 0
  return val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatDate = (d) => {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatTime = (d) => {
  if (!d) return ''
  return new Date(d).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}
</script>

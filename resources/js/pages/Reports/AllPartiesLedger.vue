<template>
  <AppLayout>
    <PageHeader
      title="All Parties Ledger"
      subtitle="Combined ledger summary for all Customers and Suppliers"
    />

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6 print:hidden">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                <input type="date" v-model="filters.start_date" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                <input type="date" v-model="filters.end_date" class="w-full rounded-md border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div class="flex items-end">
                <button 
                    @click="applyFilters" 
                    :disabled="loading"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center w-full justify-center"
                >
                    <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ loading ? 'Loading...' : 'Apply Filters' }}
                </button>
            </div>
        </div>
    </div>

    <!-- Ledger Table -->
    <ReportTable
      title="General Ledger"
      :columns="tableColumns"
      :data="transactions"
      :pagination="null"
      :loading="loading"
      :export-route="route('reports.all-parties-ledger')"
      :filters="filters"
    >
        <template #cell-date="{ item }">
            <span class="whitespace-nowrap">{{ item.date }}</span>
        </template>

        <template #cell-voucher_no="{ item }">
            <span class="font-mono text-xs">{{ item.voucher_no }}</span>
        </template>

        <template #cell-party_name="{ item }">
            <div class="font-medium text-gray-900 dark:text-white">{{ item.party_name }}</div>
            <div class="text-xs text-gray-500 capitalize">{{ item.party_type }}</div>
        </template>

        <template #cell-description="{ item }">
            {{ item.description }}
        </template>

        <template #cell-debit="{ item }">
            <span v-if="item.debit > 0" class="text-gray-900 dark:text-gray-100">
                {{ formatCurrency(item.debit) }}
            </span>
            <span v-else class="text-gray-400">-</span>
        </template>

        <template #cell-credit="{ item }">
            <span v-if="item.credit > 0" class="text-gray-900 dark:text-gray-100">
                {{ formatCurrency(item.credit) }}
            </span>
            <span v-else class="text-gray-400">-</span>
        </template>

        <template #cell-balance="{ item }">
            <span :class="item.balance >= 0 ? 'text-red-600' : 'text-green-600'">
                {{ formatCurrency(item.balance) }}
            </span>
        </template>
    </ReportTable>

    <!-- Totals Summary -->
    <div v-if="totals" class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Grand Totals</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="text-sm text-gray-500 dark:text-gray-400">Opening Balance</div>
                <div class="text-xl font-bold" :class="totals.opening_balance >= 0 ? 'text-red-600' : 'text-green-600'">
                    {{ formatCurrency(totals.opening_balance) }}
                </div>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Debit</div>
                <div class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ formatCurrency(totals.total_debit) }}
                </div>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Credit</div>
                <div class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ formatCurrency(totals.total_credit) }}
                </div>
            </div>
             <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-100 dark:border-green-800">
                <div class="text-sm text-green-700 dark:text-green-400">Total Received</div>
                <div class="text-xl font-bold text-green-700 dark:text-green-400">
                    {{ formatCurrency(totals.total_received) }}
                </div>
            </div>
            <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-100 dark:border-red-800">
                <div class="text-sm text-red-700 dark:text-red-400">Total Paid</div>
                <div class="text-xl font-bold text-red-700 dark:text-red-400">
                    {{ formatCurrency(totals.total_paid) }}
                </div>
            </div>
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="text-sm text-gray-500 dark:text-gray-400">Closing Balance</div>
                <div class="text-xl font-bold" :class="totals.closing_balance >= 0 ? 'text-red-600' : 'text-green-600'">
                    {{ formatCurrency(totals.closing_balance) }}
                </div>
            </div>
        </div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import ReportTable from '@/components/Reports/ReportTable.vue'

const props = defineProps({
  transactions: Array,
  filters: Object,
  totals: Object,
})

const loading = ref(false)
const filters = reactive({
  start_date: props.filters.start_date,
  end_date: props.filters.end_date,
})

const tableColumns = [
  { key: 'date', label: 'Date' },
  { key: 'voucher_no', label: 'Voucher No' },
  { key: 'party_name', label: 'Party Name' },
  { key: 'description', label: 'Description' },
  { key: 'debit', label: 'Debit (Dr)' },
  { key: 'credit', label: 'Credit (Cr)' },
  { key: 'balance', label: 'Balance' },
]

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-PK', {
    style: 'currency',
    currency: 'PKR',
    minimumFractionDigits: 2
  }).format(value)
}

const applyFilters = () => {
  loading.value = true
  router.get(route('reports.all-parties-ledger'), filters, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => loading.value = false
  })
}
</script>

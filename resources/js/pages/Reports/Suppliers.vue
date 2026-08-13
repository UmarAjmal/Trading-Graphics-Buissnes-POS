<template>
  <AppLayout>
    <PageHeader
      title="Supplier Reports"
      subtitle="View detailed supplier ledger and transaction history"
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
          <button
            class="primary-soft-btn"
            :disabled="!reportData || loading"
            @click="exportSupplierCsv"
          >
            <ModernIcon name="download" size="sm" />
            <span>Export CSV</span>
          </button>
          <button
            class="primary-soft-btn"
            :disabled="!reportData || loading"
            @click="exportSupplierPdf"
          >
            <ModernIcon name="file-text" size="sm" />
            <span>Export PDF</span>
          </button>
        </div>
      </template>
    </PageHeader>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Supplier Selection -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Select Supplier
          </label>
          <select
            v-model="selectedSupplierId"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="">-- Select Supplier --</option>
            <option 
              v-for="supplier in suppliers" 
              :key="supplier.id" 
              :value="supplier.id"
            >
              {{ supplier.name }} ({{ supplier.phone || 'No phone' }})
            </option>
          </select>
        </div>

        <!-- Date From -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            From Date
          </label>
          <input
            v-model="dateFrom"
            type="date"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
          />
        </div>

        <!-- Date To -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            To Date
          </label>
          <input
            v-model="dateTo"
            type="date"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
          />
        </div>
      </div>

      <!-- Generate Button -->
      <div class="mt-4">
        <button
          @click="generateReport"
          :disabled="!selectedSupplierId || loading"
          class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors duration-200 flex items-center gap-2"
        >
          <ModernIcon v-if="!loading" name="chart-bar" size="sm" />
          <svg v-else class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ loading ? 'Generating...' : 'Generate Report' }}
        </button>
      </div>
    </div>

    <!-- Supplier Info & Summary -->
    <div v-if="reportData" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Supplier Name</h3>
          <p class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ reportData.supplier?.name }}</p>
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</h3>
          <p class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ reportData.supplier?.phone || 'N/A' }}</p>
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Purchases</h3>
          <p class="text-lg font-semibold text-blue-600 dark:text-blue-400 mt-1">Rs {{ formatCurrency(reportData.summary?.total_purchases || 0) }}</p>
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Payable</h3>
          <p class="text-lg font-semibold mt-1" :class="reportData.summary?.balance >= 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">

            Rs {{ formatCurrency(Math.abs(reportData.summary?.balance || 0)) }} {{ reportData.summary?.balance >= 0 ? '(Payable)' : '(Prepayment)' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Ledger Table -->
    <div v-if="reportData" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Supplier Ledger</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Prepayment shows as negative payable (company's asset: Prepaid Expense / Supplier Advance)
        </p>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sr</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Description</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Debit</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Credit</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Balance</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="(entry, index) in reportData.ledger" :key="index" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ index + 1 }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                {{ formatDate(entry.date) }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                {{ entry.description }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-100">
                {{ (entry.payment > 0 || entry.prepayment !== 0) ? formatCurrency(entry.payment + Math.abs(entry.prepayment)) : '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-100">
                {{ entry.purchase_amount > 0 ? formatCurrency(entry.purchase_amount) : '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium" :class="entry.balance >= 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
                {{ formatCurrency(Math.abs(entry.balance)) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- No Data Message -->
    <div v-if="!reportData && !loading" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-12 text-center">
      <ModernIcon name="chart-bar" size="lg" class="mx-auto text-gray-400 mb-4" />
      <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No Report Generated</h3>
      <p class="text-gray-500 dark:text-gray-400">Select a supplier and date range, then click "Generate Report"</p>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import ModernIcon from '@/components/ModernIcon.vue'

const props = defineProps({
  suppliers: {
    type: Array,
    default: () => []
  },
  report: {
    type: Object,
    default: null
  }
})

const selectedSupplierId = ref('')
const dateFrom = ref('')
const dateTo = ref('')
const loading = ref(false)
const reportData = ref(props.report)

// Set default dates (last 30 days)
onMounted(() => {
  const today = new Date()
  const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate())
  
  dateTo.value = today.toISOString().split('T')[0]
  dateFrom.value = lastMonth.toISOString().split('T')[0]
})

const generateReport = () => {
  if (!selectedSupplierId.value) return
  
  loading.value = true
  
  router.get(route('reports.suppliers'), {
    supplier_id: selectedSupplierId.value,
    date_from: dateFrom.value,
    date_to: dateTo.value
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page) => {
      reportData.value = page.props.report
      loading.value = false
    },
    onError: () => {
      loading.value = false
    }
  })
}

const formatCurrency = (amount) => {
  return Number(amount).toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

const exportSupplierCsv = () => {
  if (!reportData.value) return

  const rows = []
  // Add company name and header
  rows.push(['Narmer POS - Supplier Report'])
  rows.push(['Supplier', reportData.value.supplier?.name || ''])
  rows.push(['Phone', reportData.value.supplier?.phone || ''])
  rows.push(['Period', `${dateFrom.value} to ${dateTo.value}`])
  rows.push(['Generated', new Date().toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })])
  rows.push([])
  
  // Add summary
  rows.push(['SUMMARY'])
  rows.push(['Total Purchases', `Rs ${formatCurrency(reportData.value.summary?.total_purchases || 0)}`])
  const balance = reportData.value.summary?.balance || 0
  rows.push(['Current Payable', `Rs ${formatCurrency(Math.abs(balance))} ${balance >= 0 ? '(Payable)' : '(Prepayment)'}`])
  rows.push([])
  
  // Add ledger header
  rows.push(['SUPPLIER LEDGER'])
  rows.push(['Sr', 'Date', 'Description', 'Debit', 'Credit', 'Balance'])

  reportData.value.ledger.forEach((entry, index) => {
    rows.push([
      index + 1,
      formatDate(entry.date),
      entry.description,
      (entry.payment > 0 || entry.prepayment !== 0) ? formatCurrency(entry.payment + Math.abs(entry.prepayment)) : '0.00',
      entry.purchase_amount > 0 ? formatCurrency(entry.purchase_amount) : '0.00',
      formatCurrency(Math.abs(entry.balance))
    ])
  })

  const csvContent = rows.map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\n')
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `supplier-report-${reportData.value.supplier?.name || 'supplier'}.csv`
  link.click()
  URL.revokeObjectURL(url)
}

const exportSupplierPdf = () => {
  if (!reportData.value || !selectedSupplierId.value) return
  
  const params = new URLSearchParams({
    supplier_id: selectedSupplierId.value,
    date_from: dateFrom.value,
    date_to: dateTo.value
  })
  
  // Open in new window/tab - auto-print dialog will open
  window.open(route('reports.suppliers.export.pdf') + '?' + params.toString(), '_blank')
}
</script>

<style scoped>
.primary-soft-btn,
.ghost-btn {
  @apply inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all;
}
.primary-soft-btn {
  background: linear-gradient(135deg, #eef2ff, #e0e7ff);
  color: #1d4ed8;
  border: 1px solid #c7d2fe;
}
.primary-soft-btn:hover { box-shadow: 0 8px 18px rgba(59, 130, 246, 0.18); transform: translateY(-1px); }
.primary-soft-btn:disabled { opacity: 0.5; cursor: not-allowed; box-shadow: none; transform: none; }
.ghost-btn {
  background: transparent;
  color: #111827;
  border: 1px solid #e5e7eb;
}
.ghost-btn:hover { background: #f3f4f6; }
.dark .ghost-btn { color: #e5e7eb; border-color: #374151; }
.dark .ghost-btn:hover { background: #1f2937; }
</style>

<template>
  <AppLayout>
    <PageHeader
      title="Customer Reports"
      subtitle="View detailed customer ledger and transaction history"
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
            @click="exportCustomerCsv"
          >
            <ModernIcon name="download" size="sm" />
            <span>Export CSV</span>
          </button>
          <button
            class="primary-soft-btn"
            :disabled="!reportData || loading"
            @click="exportCustomerPdf"
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
        <!-- Customer Selection -->
        <div class="md:col-span-2 relative">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Select Customer
          </label>
          <Combobox v-model="selectedCustomer" nullable>
            <div class="relative mt-1">
              <div
                class="relative w-full cursor-default overflow-hidden rounded-lg bg-white dark:bg-gray-700 text-left border border-gray-300 dark:border-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/75 focus-visible:ring-offset-2 focus-visible:ring-offset-teal-300 sm:text-sm"
              >
                <ComboboxInput
                  class="w-full border-none py-2 pl-3 pr-10 text-sm leading-5 text-gray-900 dark:text-gray-100 dark:bg-gray-700 focus:ring-0"
                  :displayValue="(customer) => customer ? `${customer.name} (${customer.phone || 'No phone'})` : ''"
                  @change="query = $event.target.value"
                  placeholder="Search customer..."
                />
                <ComboboxButton
                  class="absolute inset-y-0 right-0 flex items-center pr-2"
                >
                  <ChevronUpDownIcon
                    class="h-5 w-5 text-gray-400"
                    aria-hidden="true"
                  />
                </ComboboxButton>
              </div>
              <TransitionRoot
                leave="transition ease-in duration-100"
                leaveFrom="opacity-100"
                leaveTo="opacity-0"
                @after-leave="query = ''"
              >
                <ComboboxOptions
                  class="absolute mt-1 max-h-60 w-full overflow-auto rounded-md bg-white dark:bg-gray-700 py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm z-50"
                >
                  <div
                    v-if="filteredCustomers.length === 0 && query !== ''"
                    class="relative cursor-default select-none px-4 py-2 text-gray-700 dark:text-gray-200"
                  >
                    Nothing found.
                  </div>

                  <ComboboxOption
                    v-for="customer in filteredCustomers"
                    as="template"
                    :key="customer.id"
                    :value="customer"
                    v-slot="{ selected, active }"
                  >
                    <li
                      class="relative cursor-default select-none py-2 pl-10 pr-4"
                      :class="{
                        'bg-primary-600 text-white': active,
                        'text-gray-900 dark:text-gray-100': !active,
                      }"
                    >
                      <span
                        class="block truncate"
                        :class="{ 'font-medium': selected, 'font-normal': !selected }"
                      >
                        {{ customer.name }} ({{ customer.phone || 'No phone' }})
                      </span>
                      <span
                        v-if="selected"
                        class="absolute inset-y-0 left-0 flex items-center pl-3"
                        :class="{ 'text-white': active, 'text-primary-600': !active }"
                      >
                        <CheckIcon class="h-5 w-5" aria-hidden="true" />
                      </span>
                    </li>
                  </ComboboxOption>
                </ComboboxOptions>
              </TransitionRoot>
            </div>
          </Combobox>
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
          :disabled="!selectedCustomerId || loading"
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

    <!-- Customer Info & Summary -->
    <div v-if="reportData" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Customer Name</h3>
          <p class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ reportData.customer?.name }}</p>
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</h3>
          <p class="text-lg font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ reportData.customer?.phone || 'N/A' }}</p>
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Debit (Sales)</h3>
          <p class="text-lg font-semibold text-green-600 dark:text-green-400 mt-1">Rs {{ formatCurrency(reportData.totals?.debit || 0) }}</p>
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Credit (Received)</h3>
          <p class="text-lg font-semibold text-blue-600 dark:text-blue-400 mt-1">Rs {{ formatCurrency(reportData.totals?.credit || 0) }}</p>
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Balance</h3>
          <p class="text-lg font-semibold mt-1" :class="reportData.totals?.closing_balance >= 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
            Rs {{ formatCurrency(Math.abs(reportData.totals?.closing_balance || 0)) }} {{ reportData.totals?.closing_balance >= 0 ? '(Receivable)' : '(Advance)' }}
          </p>
        </div>
      </div>
    </div>

    <!-- Ledger Table -->
    <div v-if="reportData" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Customer Ledger</h3>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sr</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Description</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Voucher No</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Debit</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Credit</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Balance</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="(entry, index) in reportData.transactions" :key="index" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ index + 1 }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                {{ entry.formatted_date }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                {{ entry.description }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                {{ entry.voucher_no }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-100">
                {{ entry.debit > 0 ? formatCurrency(entry.debit) : '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-100">
                {{ entry.credit > 0 ? formatCurrency(entry.credit) : '-' }}
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
      <p class="text-gray-500 dark:text-gray-400">Select a customer and date range, then click "Generate Report"</p>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import ModernIcon from '@/components/ModernIcon.vue'
import {
  Combobox,
  ComboboxInput,
  ComboboxButton,
  ComboboxOptions,
  ComboboxOption,
  TransitionRoot,
} from '@headlessui/vue'
import { CheckIcon, ChevronUpDownIcon } from '@heroicons/vue/20/solid'

const props = defineProps({
  customers: {
    type: Array,
    default: () => []
  },
  report: {
    type: Object,
    default: null
  }
})

const selectedCustomerId = ref('')
const selectedCustomer = ref(null)
const query = ref('')

// Initialize selectedCustomer if report is present or we have a selectedCustomerId
onMounted(() => {
  if (props.report && props.report.customer) {
    selectedCustomer.value = props.report.customer
    selectedCustomerId.value = props.report.customer.id
  }
})

// Update selectedCustomerId when selectedCustomer changes
watch(selectedCustomer, (newVal) => {
  selectedCustomerId.value = newVal ? newVal.id : ''
})

const filteredCustomers = computed(() =>
  query.value === ''
    ? props.customers
    : props.customers.filter((customer) =>
        customer.name
          .toLowerCase()
          .replace(/\s+/g, '')
          .includes(query.value.toLowerCase().replace(/\s+/g, '')) ||
        (customer.phone && customer.phone.includes(query.value))
      )
)
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
  if (!selectedCustomerId.value) return
  
  loading.value = true
  
  router.get(route('reports.customers'), {
    customer_id: selectedCustomerId.value,
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

const exportCustomerCsv = () => {
  if (!reportData.value) return

  const rows = []
  // Add company name and header
  rows.push(['Narmer POS - Customer Report'])
  rows.push(['Customer', reportData.value.customer?.name || ''])
  rows.push(['Phone', reportData.value.customer?.phone || ''])
  rows.push(['Period', `${dateFrom.value} to ${dateTo.value}`])
  rows.push(['Generated', new Date().toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })])
  rows.push([])
  
  // Add summary
  rows.push(['SUMMARY'])
  rows.push(['Total Sales', `Rs ${formatCurrency(reportData.value.summary?.total_sales || 0)}`])
  const balance = reportData.value.summary?.balance || 0
  rows.push(['Current Balance', `Rs ${formatCurrency(Math.abs(balance))} ${balance >= 0 ? '(Receivable)' : '(Advance)'}`])
  rows.push([])
  
  // Add ledger header
  rows.push(['CUSTOMER LEDGER'])
  rows.push(['Sr', 'Date', 'Description', 'Debit', 'Credit', 'Balance'])

  reportData.value.ledger.forEach((entry, index) => {
    rows.push([
      index + 1,
      formatDate(entry.date),
      entry.description,
      entry.sale_amount > 0 ? formatCurrency(entry.sale_amount) : '0.00',
      (entry.payment > 0 || entry.advance > 0 || entry.return_amount > 0) ? formatCurrency(entry.payment + entry.advance + (entry.return_amount || 0)) : '0.00',
      formatCurrency(Math.abs(entry.balance))
    ])
  })

  const csvContent = rows.map(r => r.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',')).join('\n')
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `customer-report-${reportData.value.customer?.name || 'customer'}.csv`
  link.click()
  URL.revokeObjectURL(url)
}

const exportCustomerPdf = () => {
  if (!reportData.value || !selectedCustomerId.value) return
  
  const params = new URLSearchParams({
    customer_id: selectedCustomerId.value,
    date_from: dateFrom.value,
    date_to: dateTo.value
  })
  
  // Open in new window/tab - user can print to PDF using Ctrl+P or browser print
  window.open(route('reports.customers.export.pdf') + '?' + params.toString(), '_blank')
}
</script>

<style scoped>
.primary-soft-btn,
.ghost-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding-left: 1rem;
  padding-right: 1rem;
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  transition-property: all;
  transition-duration: 150ms;
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

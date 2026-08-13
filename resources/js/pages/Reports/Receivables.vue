<template>
  <AppLayout>
    <PageHeader
      title="Receivables Report"
      subtitle="Overview of all customer balances"
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
            :href="route('reports.receivables.export-pdf', { 
                type: filters.type, 
                start_date: filters.start_date, 
                end_date: filters.end_date 
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

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
      <div class="flex flex-wrap items-end gap-4">
        <div class="w-full md:w-64">
           <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Filter by Valid Balance
          </label>
          <select
            v-model="filters.type"
            @change="applyFilters"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
          >
            <option value="all">All Balances (Non-Zero)</option>
            <option value="receivable">Receivables Only (Amount Due)</option>
            <option value="advance">Advances Only (Negative Balance)</option>
          </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From Date</label>
            <input 
                type="date" 
                v-model="filters.start_date"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
            >
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To Date</label>
            <input 
                type="date" 
                v-model="filters.end_date"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
            >
        </div>

        <div class="pb-1">
            <button 
                @click="applyFilters" 
                class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
            >
                Filter
            </button>
        </div>
        
        <div class="flex-grow">
             <!-- Search -->
             <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Search Customer
              </label>
             <input 
                type="text" 
                v-model="search" 
                placeholder="Search by name or phone..." 
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
             >
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border-l-4 border-blue-500">
         <div class="text-sm text-gray-500 mb-1">Total Receivables</div>
         <div class="text-2xl font-bold text-red-600">
             Rs {{ formatNumber(totalReceivables) }}
         </div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border-l-4 border-green-500">
         <div class="text-sm text-gray-500 mb-1">Total Advances</div>
         <div class="text-2xl font-bold text-green-600">
             Rs {{ formatNumber(Math.abs(totalAdvances)) }}
         </div>
      </div>
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border-l-4 border-gray-500">
         <div class="text-sm text-gray-500 mb-1">Net Balance</div>
         <div class="text-2xl font-bold" :class="netBalance >= 0 ? 'text-red-600' : 'text-green-600'">
             Rs {{ formatNumber(Math.abs(netBalance)) }} 
             <span class="text-sm font-normal text-gray-500">
                {{ netBalance >= 0 ? '(Receivable)' : '(Payable)' }}
             </span>
         </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Customer
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Phone
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Balance
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Status
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-if="filteredCustomers.length === 0">
                    <td colspan="4" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                        No customers found matching criteria
                    </td>
                </tr>
                <tr v-for="customer in filteredCustomers" :key="customer.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ customer.name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ customer.phone || '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold" :class="customer.balance >= 0 ? 'text-red-600' : 'text-green-600'">
                        Rs {{ formatNumber(Math.abs(customer.balance)) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <span 
                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                            :class="customer.balance >= 0 ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'"
                        >
                            {{ customer.balance >= 0 ? 'Receivable' : 'Advance' }}
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import ModernIcon from '@/Components/ModernIcon.vue'

const props = defineProps({
  customers: Array,
  filters: Object
})

const filters = ref({
    type: props.filters.type || 'all',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || new Date().toISOString().split('T')[0]
})

const search = ref('')

const applyFilters = () => {
    router.get(route('reports.receivables'), {
        type: filters.value.type,
        start_date: filters.value.start_date,
        end_date: filters.value.end_date
    }, {
        preserveState: true,
        replace: true
    })
}

const filteredCustomers = computed(() => {
    if (!search.value) return props.customers;
    const q = search.value.toLowerCase();
    return props.customers.filter(c => 
        c.name.toLowerCase().includes(q) || 
        (c.phone && c.phone.includes(q))
    );
})

const totalReceivables = computed(() => {
    return filteredCustomers.value
        .filter(c => c.balance > 0)
        .reduce((sum, c) => sum + parseFloat(c.balance), 0);
})

const totalAdvances = computed(() => {
    return filteredCustomers.value
        .filter(c => c.balance < 0)
        .reduce((sum, c) => sum + parseFloat(c.balance), 0);
})

const netBalance = computed(() => {
    return filteredCustomers.value
        .reduce((sum, c) => sum + parseFloat(c.balance), 0);
})


const formatNumber = (num) => {
    return new Intl.NumberFormat('en-PK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(num);
}
</script>

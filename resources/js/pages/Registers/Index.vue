<template>
  <AppLayout>
    <PageHeader
      title="Cash Registers"
      subtitle="Manage POS terminals and register operations"
    />

    <!-- Flash Messages -->
    <div v-if="$page.props.flash?.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
      <span class="block sm:inline">{{ $page.props.flash.success }}</span>
    </div>
    <div v-if="$page.props.flash?.error" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
      <span class="block sm:inline">{{ $page.props.flash.error }}</span>
    </div>

    <!-- Register Status Banner -->
    <div v-if="registerStatus === 'open'" class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-green-700">
            <strong>Register is OPEN</strong> - Started at {{ activeSession ? new Date(activeSession.opened_at).toLocaleTimeString() : 'N/A' }} with {{ formatCurrency(activeSession?.opening_cash || 0) }}
          </p>
        </div>
      </div>
    </div>
    <div v-else class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-yellow-700">
            <strong>Register is CLOSED</strong> - Please open the register to start taking sales
          </p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
      <!-- Today's Stats -->
      <UiCard>
        <div class="p-6">
          <div class="flex items-center">
            <div class="p-2 bg-green-100 rounded-lg">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Today's Sales</h3>
              <p class="text-2xl font-bold text-green-600">{{ formatCurrency(stats.today_sales_total || 0) }}</p>
              <p class="text-sm text-gray-500">{{ stats.today_sales_count || 0 }} transactions</p>
            </div>
          </div>
        </div>
      </UiCard>

      <UiCard>
        <div class="p-6">
          <div class="flex items-center">
            <div class="p-2 bg-blue-100 rounded-lg">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2-4h10a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Cash Sales</h3>
              <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(stats.today_cash_sales || 0) }}</p>
              <p class="text-sm text-gray-500">Cash transactions</p>
            </div>
          </div>
        </div>
      </UiCard>

      <UiCard>
        <div class="p-6">
          <div class="flex items-center">
            <div class="p-2 bg-purple-100 rounded-lg">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Credit Sales</h3>
              <p class="text-2xl font-bold text-purple-600">{{ formatCurrency(stats.today_credit_sales || 0) }}</p>
              <p class="text-sm text-gray-500">Credit transactions</p>
            </div>
          </div>
        </div>
      </UiCard>

      <UiCard>
        <div class="p-6">
          <div class="flex items-center">
            <div class="p-2 bg-red-100 rounded-lg">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Expenses</h3>
              <p class="text-2xl font-bold text-red-600">{{ formatCurrency(stats.session_expenses || 0) }}</p>
              <p class="text-sm text-gray-500">Paid from Drawer</p>
            </div>
          </div>
        </div>
      </UiCard>
    </div>

    <!-- Register Operations -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <UiCard>
        <div class="p-6">
          <h3 class="text-lg font-semibold mb-4">Register Operations</h3>
          <div class="space-y-3">
            <button
              v-if="registerStatus === 'closed'"
              @click="showOpenRegisterModal = true"
              :disabled="isLoading"
              class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-medium"
            >
              <span v-if="!isLoading">Open Register</span>
              <span v-else>Processing...</span>
            </button>

            <button
              v-if="registerStatus === 'open'"
              @click="showCloseRegisterModal = true"
              :disabled="isLoading"
              class="w-full bg-red-600 text-white py-3 px-4 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-medium"
            >
              <span v-if="!isLoading">Close Register</span>
              <span v-else>Processing...</span>
            </button>
            <Link
              :href="route('pos.index')"
              :class="[
                'w-full py-3 px-4 rounded-lg transition-colors text-center block font-medium',
                registerStatus === 'open' 
                  ? 'bg-blue-600 text-white hover:bg-blue-700' 
                  : 'bg-gray-300 text-gray-500 cursor-not-allowed pointer-events-none'
              ]"
            >
              Go to POS
            </Link>
            <p v-if="registerStatus === 'closed'" class="text-sm text-gray-500 text-center mt-2">
              Please open register to access POS
            </p>
          </div>
        </div>
      </UiCard>

      <UiCard>
        <div class="p-6">
          <h3 class="text-lg font-semibold mb-4">Current User</h3>
          <div class="space-y-2">
            <p><strong>Name:</strong> {{ user.name }}</p>
            <p><strong>Email:</strong> {{ user.email }}</p>
            <p><strong>Role:</strong> {{ user.role || 'admin' }}</p>
            <p v-if="activeSession"><strong>Shift Start:</strong> {{ new Date(activeSession.opened_at).toLocaleTimeString() }}</p>
            <p v-else><strong>Status:</strong> <span class="text-red-600">Not on shift</span></p>
          </div>
        </div>
      </UiCard>
    </div>

    <!-- Today's Transactions -->
    <UiCard>
      <div class="p-6">
        <h3 class="text-lg font-semibold mb-4">Today's Transactions</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full table-auto">
            <thead>
              <tr class="bg-gray-50 dark:bg-gray-700">
                <th class="px-4 py-2 text-left text-sm font-medium text-gray-900 dark:text-gray-100">Invoice</th>
                <th class="px-4 py-2 text-left text-sm font-medium text-gray-900 dark:text-gray-100">Customer</th>
                <th class="px-4 py-2 text-left text-sm font-medium text-gray-900 dark:text-gray-100">Items</th>
                <th class="px-4 py-2 text-left text-sm font-medium text-gray-900 dark:text-gray-100">Bill Total</th>
                <th class="px-4 py-2 text-left text-sm font-medium text-gray-900 dark:text-gray-100">Payment</th>
                <th class="px-4 py-2 text-left text-sm font-medium text-gray-900 dark:text-gray-100">Time</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
              <tr v-for="sale in todaySales" :key="sale.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ sale.invoice_no }}</td>
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                  {{ sale.customer ? sale.customer.name : 'Walk-in Customer' }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ sale.sale_items ? sale.sale_items.length : (sale.sale_items_count || 0) }}</td>
                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency(sale.bill_total || 0) }}</td>
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                  <span :class="sale.payment_type === 'cash' ? 'text-green-600' : 'text-blue-600'">
                    {{ sale.payment_type || 'Cash' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                  {{ sale.created_at ? new Date(sale.created_at).toLocaleTimeString() : 'N/A' }}
                </td>
              </tr>
              <tr v-if="todaySales.length === 0">
                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                  No transactions yet today
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </UiCard>

    <!-- Open Register Modal -->
    <div v-if="showOpenRegisterModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="showOpenRegisterModal = false">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-96 max-w-full mx-4">
        <h3 class="text-lg font-semibold mb-4 dark:text-gray-100">Open Register</h3>
        <form @submit.prevent="openRegister">
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2 dark:text-gray-200">Opening Cash Amount <span class="text-red-500">*</span></label>
            <input
              v-model="openForm.opening_cash"
              type="number"
              step="0.01"
              min="0"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="0.00"
              :disabled="isLoading"
            />
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Enter the amount of cash in the drawer</p>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2 dark:text-gray-200">Notes (Optional)</label>
            <textarea
              v-model="openForm.notes"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              rows="3"
              placeholder="Any opening notes..."
              :disabled="isLoading"
            ></textarea>
          </div>
          <div class="flex space-x-3">
            <button
              type="submit"
              :disabled="isLoading"
              class="flex-1 bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-medium"
            >
              <span v-if="!isLoading">Open Register</span>
              <span v-else>Opening...</span>
            </button>
            <button
              type="button"
              @click="showOpenRegisterModal = false"
              :disabled="isLoading"
              class="flex-1 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 py-2 px-4 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors disabled:opacity-50"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Close Register Modal -->
    <div v-if="showCloseRegisterModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="showCloseRegisterModal = false">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-96 max-w-full mx-4">
        <h3 class="text-lg font-semibold mb-4 dark:text-gray-100">Close Register</h3>
        <form @submit.prevent="closeRegister">
          <div class="mb-4 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 p-3 rounded text-xs space-y-1">
            <p class="text-sm font-bold text-blue-900 dark:text-blue-100 border-b border-blue-200 dark:border-blue-700 pb-1 mb-2">Session Summary</p>
            
            <div class="flex justify-between">
               <span>Opening Cash:</span>
               <span>{{ formatCurrency(activeSession?.opening_cash || 0) }}</span>
            </div>
            
            <div class="flex justify-between text-green-700 dark:text-green-400">
               <span>+ Cash Sales:</span>
               <span>{{ formatCurrency(stats.today_cash_sales || 0) }}</span>
            </div>

            <div class="flex justify-between text-red-700 dark:text-red-400 border-b border-blue-200 dark:border-blue-700 pb-1">
               <span>- Expenses (Drawer):</span>
               <span>{{ formatCurrency(stats.session_expenses || 0) }}</span>
            </div>
            
            <div class="flex justify-between font-bold text-lg text-blue-900 dark:text-blue-100 pt-1">
               <span>Expected Cash:</span>
               <span>{{ formatCurrency((parseFloat(activeSession?.opening_cash || 0) + parseFloat(stats.today_cash_sales || 0) - parseFloat(stats.session_expenses || 0))) }}</span>
            </div>

            <div class="mt-3 pt-2 border-t border-blue-200 dark:border-blue-700">
                <p class="font-semibold mb-1">Other Transactions</p>
                <div class="flex justify-between text-gray-600 dark:text-gray-400">
                   <span>Expenses (Owner/External):</span>
                   <span>{{ formatCurrency(stats.external_expenses || 0) }}</span>
                </div>
                <div class="flex justify-between text-gray-600 dark:text-gray-400">
                   <span>Credit Sales:</span>
                   <span>{{ formatCurrency(stats.today_credit_sales || 0) }}</span>
                </div>
                <div class="flex justify-between text-gray-600 dark:text-gray-400">
                   <span>Bank Transfers (Verified):</span>
                   <span>{{ formatCurrency(stats.today_bank_sales || 0) }}</span>
                </div>
            </div>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2 dark:text-gray-200">Cash Count Amount <span class="text-red-500">*</span></label>
            <input
              v-model="closeForm.cash_amount"
              type="number"
              step="0.01"
              min="0"
              required
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="0.00"
              :disabled="isLoading"
            />
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Count all cash in the drawer and enter the total</p>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2 dark:text-gray-200">Notes (Optional)</label>
            <textarea
              v-model="closeForm.notes"
              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              rows="3"
              placeholder="Any closing notes..."
              :disabled="isLoading"
            ></textarea>
          </div>
          <div class="flex space-x-3">
            <button
              type="submit"
              :disabled="isLoading"
              class="flex-1 bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-medium"
            >
              <span v-if="!isLoading">Close Register</span>
              <span v-else>Closing...</span>
            </button>
            <button
              type="button"
              @click="showCloseRegisterModal = false"
              :disabled="isLoading"
              class="flex-1 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 py-2 px-4 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors disabled:opacity-50"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiCard from '@/components/UiCard.vue'
import { formatCurrency } from '@/utils/currency'

// Props from controller
const props = defineProps({
  todaySales: {
    type: Array,
    default: () => []
  },
  stats: {
    type: Object,
    default: () => ({
      today_sales_total: 0,
      today_sales_count: 0,
      today_cash_sales: 0,
      today_credit_sales: 0,
      today_bank_sales: 0,
      session_expenses: 0
    })
  },
  user: {
    type: Object,
    default: () => ({
      name: '',
      email: '',
      role: 'admin'
    })
  },
  activeSession: {
    type: Object,
    default: null
  },
  registerStatus: {
    type: String,
    default: 'closed'
  }
})

// Reactive data
const showOpenRegisterModal = ref(false)
const showCloseRegisterModal = ref(false)
const isLoading = ref(false)

const openForm = ref({
  opening_cash: '',
  notes: ''
})

const closeForm = ref({
  cash_amount: '',
  notes: ''
})

// Methods
const openRegister = () => {
  isLoading.value = true
  
  router.post(route('registers.open'), openForm.value, {
    onSuccess: () => {
      showOpenRegisterModal.value = false
      openForm.value = { opening_cash: '', notes: '' }
      isLoading.value = false
    },
    onError: (errors) => {
      isLoading.value = false
      console.error('Failed to open register:', errors)
    },
    onFinish: () => {
      isLoading.value = false
    }
  })
}

const closeRegister = () => {
  isLoading.value = true
  
  router.post(route('registers.close'), closeForm.value, {
    onSuccess: () => {
      showCloseRegisterModal.value = false
      closeForm.value = { cash_amount: '', notes: '' }
      isLoading.value = false
    },
    onError: (errors) => {
      isLoading.value = false
      console.error('Failed to close register:', errors)
    },
    onFinish: () => {
      isLoading.value = false
    }
  })
}
</script>

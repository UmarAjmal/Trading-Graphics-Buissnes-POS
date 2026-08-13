<template>
  <div>
    <!-- Customer Info Summary -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6 p-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Customer</h3>
          <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ customer.name }}</p>
          <p class="text-sm text-gray-600 dark:text-gray-400">{{ customer.phone }}</p>
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Advance</h3>
          <p class="text-lg font-semibold" :class="totalAdvance >= 0 ? 'text-green-600' : 'text-red-600'">
            PKR {{ Math.abs(totalAdvance).toFixed(2) }}
            <span class="text-sm">{{ totalAdvance >= 0 ? '(Credit Balance)' : '(Used)' }}</span>
          </p>
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Credited</h3>
          <p class="text-lg font-semibold text-red-600 dark:text-red-400">
            PKR {{ totalCredited.toFixed(2) }}
          </p>
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Net Balance</h3>
          <p class="text-lg font-semibold" :class="netBalance >= 0 ? 'text-green-600' : 'text-red-600'">
            PKR {{ Math.abs(netBalance).toFixed(2) }}
            <span class="text-sm">{{ netBalance >= 0 ? '(Customer Owes)' : '(We Owe)' }}</span>
          </p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Part 1: Advance Management -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Advance Management</h2>
            <button
              @click="showAdvanceModal = true"
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 text-sm"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Add Advance
            </button>
          </div>
        </div>

        <!-- Advance History Table -->
        <div class="p-6">
          <div v-if="advances.length === 0" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No advance payments recorded</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Note</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="advance in advances" :key="advance.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    {{ formatDate(advance.date) }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400">
                    PKR {{ parseFloat(advance.amount || 0).toFixed(2) }}
                  </td>
                  <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ advance.note || '-' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Part 2: Credit Section -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white">Credit History</h2>
          <p class="text-sm text-gray-600 dark:text-gray-400">Credit sales from POS transactions</p>
        </div>

        <!-- Credit History Table -->
        <div class="p-6">
          <div v-if="creditHistory.length === 0" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No credit sales recorded</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sale ID</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Credit</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Remaining</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Payment</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <template v-for="credit in creditHistory" :key="credit.id">
                  <tr v-if="credit" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-blue-600 dark:text-blue-400">
                      #{{ credit.sale_id || 'N/A' }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                      {{ credit.date ? formatDate(credit.date) : 'N/A' }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                      PKR {{ parseFloat(credit.total_credit || 0).toFixed(2) }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-red-600 dark:text-red-400">
                      PKR {{ parseFloat(credit.remaining_credit || 0).toFixed(2) }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">
                      <span 
                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                        :class="{
                          'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': credit.status === 'paid',
                          'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': credit.status === 'unpaid',
                          'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': credit.status === 'partial'
                        }"
                      >
                        {{ credit.status ? credit.status.charAt(0).toUpperCase() + credit.status.slice(1) : 'Unknown' }}
                      </span>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">
                      <button
                        v-if="credit.remaining_credit && credit.remaining_credit > 0"
                        @click="showPaymentModal(credit)"
                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium"
                      >
                        Add Payment
                      </button>
                      <span v-else class="text-gray-400 text-sm">Paid</span>
                    </td>
                  </tr>
                  <!-- Payment History Row -->
                  <tr v-if="credit && credit.payments && credit.payments.length > 0" class="bg-gray-50 dark:bg-gray-700">
                    <td colspan="6" class="px-4 py-2">
                      <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <strong>Payment History:</strong>
                      </div>
                      <div class="space-y-1">
                        <div v-for="payment in credit.payments" :key="payment.id" class="flex justify-between items-center text-sm">
                          <span class="text-gray-700 dark:text-gray-300">
                            {{ formatDate(payment.payment_date) }} - PKR {{ parseFloat(payment.amount).toFixed(2) }}
                            <span v-if="payment.note" class="text-gray-500 italic"> ({{ payment.note }})</span>
                          </span>
                        </div>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Advance Modal -->
    <div v-if="showAdvanceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Add Advance Payment</h3>
          
          <form @submit.prevent="submitAdvance">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Date *
              </label>
              <input
                v-model="advanceForm.date"
                type="date"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
              />
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Advance Amount *
              </label>
              <input
                v-model="advanceForm.amount"
                type="number"
                step="0.01"
                min="0.01"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="0.00"
              />
            </div>

            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Note
              </label>
              <textarea
                v-model="advanceForm.note"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Optional note about this advance"
              ></textarea>
            </div>

            <div class="flex justify-end gap-3">
              <button
                type="button"
                @click="closeAdvanceModal"
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="advanceProcessing"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ advanceProcessing ? 'Adding...' : 'Add Advance' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Payment Modal -->
    <div v-if="showPaymentModalData" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            Add Payment - Sale #{{ showPaymentModalData.sale_id }}
          </h3>
          
          <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">Remaining Credit: 
              <span class="font-semibold text-red-600 dark:text-red-400">
                PKR {{ parseFloat(showPaymentModalData.remaining_credit || 0).toFixed(2) }}
              </span>
            </p>
          </div>
          
          <form @submit.prevent="submitPayment">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Payment Amount *
              </label>
              <input
                v-model="paymentForm.amount"
                type="number"
                step="0.01"
                :max="showPaymentModalData.remaining_credit"
                min="0.01"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="0.00"
              />
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Payment Date *
              </label>
              <input
                v-model="paymentForm.payment_date"
                type="date"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
              />
            </div>

            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Note
              </label>
              <textarea
                v-model="paymentForm.note"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Optional payment note"
              ></textarea>
            </div>

            <div class="flex justify-end gap-3">
              <button
                type="button"
                @click="closePaymentModal"
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="paymentProcessing"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ paymentProcessing ? 'Processing...' : 'Add Payment' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'

// Route helper
const route = window.route

export default {
  name: 'CustomerAccountDetails',
  props: {
    customer: {
      type: Object,
      required: true
    },
    advances: {
      type: Array,
      default: () => []
    },
    creditHistory: {
      type: Array,
      default: () => []
    }
  },
  emits: ['refresh'],
  setup(props, { emit }) {
    const showAdvanceModal = ref(false)
    const showPaymentModalData = ref(null)
    const advanceProcessing = ref(false)
    const paymentProcessing = ref(false)

    const advanceForm = reactive({
      date: new Date().toISOString().split('T')[0],
      amount: '',
      note: ''
    })

    const paymentForm = reactive({
      amount: '',
      payment_date: new Date().toISOString().split('T')[0],
      note: ''
    })

    // Computed totals
    const totalAdvance = computed(() => {
      return (props.advances || []).reduce((sum, advance) => sum + parseFloat(advance?.amount || 0), 0)
    })

    const totalCredited = computed(() => {
      return (props.creditHistory || []).reduce((sum, credit) => sum + parseFloat(credit?.remaining_credit || 0), 0)
    })

    const netBalance = computed(() => {
      return totalCredited.value - totalAdvance.value
    })

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString()
    }

    const closeAdvanceModal = () => {
      showAdvanceModal.value = false
      advanceForm.date = new Date().toISOString().split('T')[0]
      advanceForm.amount = ''
      advanceForm.note = ''
    }

    const submitAdvance = () => {
      advanceProcessing.value = true
      
      router.post(route('customers.advances.store', props.customer.id), advanceForm, {
        onSuccess: () => {
          closeAdvanceModal()
          emit('refresh')
        },
        onFinish: () => {
          advanceProcessing.value = false
        }
      })
    }

    const showPaymentModal = (credit) => {
      showPaymentModalData.value = credit
      paymentForm.amount = parseFloat(credit.remaining_credit || 0).toFixed(2)
    }

    const closePaymentModal = () => {
      showPaymentModalData.value = null
      paymentForm.amount = ''
      paymentForm.payment_date = new Date().toISOString().split('T')[0]
      paymentForm.note = ''
    }

    const submitPayment = () => {
      paymentProcessing.value = true
      
      const url = route('customers.credit.payment', {
        customerId: props.customer.id,
        pendingPaymentId: showPaymentModalData.value.id
      });

      router.post(url, paymentForm, {
        onSuccess: () => {
          closePaymentModal()
          emit('refresh')
        },
        onFinish: () => {
          paymentProcessing.value = false
        }
      })
    }

    return {
      showAdvanceModal,
      showPaymentModalData,
      advanceProcessing,
      paymentProcessing,
      advanceForm,
      paymentForm,
      totalAdvance,
      totalCredited,
      netBalance,
      formatDate,
      closeAdvanceModal,
      submitAdvance,
      showPaymentModal,
      closePaymentModal,
      submitPayment
    }
  }
}
</script>

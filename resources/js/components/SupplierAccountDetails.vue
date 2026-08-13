<template>
  <div>
    <!-- Supplier Info Summary -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6 p-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Supplier</h3>
          <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ supplier.name }}</p>
          <p class="text-sm text-gray-600 dark:text-gray-400">{{ supplier.phone }}</p>
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Prepayment</h3>
          <p class="text-lg font-semibold" :class="totalPrepayment >= 0 ? 'text-green-600' : 'text-red-600'">
            PKR {{ Math.abs(totalPrepayment).toFixed(2) }}
            <span class="text-sm">{{ totalPrepayment >= 0 ? '(Credit Balance)' : '(Used)' }}</span>
          </p>
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Payable</h3>
          <p class="text-lg font-semibold text-red-600 dark:text-red-400">
            PKR {{ totalPayable.toFixed(2) }}
          </p>
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Net Balance</h3>
          <p class="text-lg font-semibold" :class="netBalance >= 0 ? 'text-red-600' : 'text-green-600'">
            PKR {{ Math.abs(netBalance).toFixed(2) }}
            <span class="text-sm">{{ netBalance >= 0 ? '(We Owe)' : '(Supplier Owes)' }}</span>
          </p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Part 1: Prepayment Management -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex justify-between items-center">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Prepayment Management</h2>
            <button
              @click="showPrepaymentModal = true"
              class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 text-sm"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Add Prepayment
            </button>
          </div>
        </div>

        <!-- Prepayment History Table -->
        <div class="p-6">
          <div v-if="prepayments.length === 0" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No prepayments recorded</p>
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
                <tr v-for="prepayment in prepayments" :key="prepayment.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    {{ formatDate(prepayment.created_at) }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400">
                    PKR {{ parseFloat(prepayment.amount || 0).toFixed(2) }}
                  </td>
                  <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ prepayment.note || '-' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Part 2: Payable Section -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white">Payable Invoices</h2>
          <p class="text-sm text-gray-600 dark:text-gray-400">Unpaid purchase invoices</p>
        </div>

        <!-- Payable History Table -->
        <div class="p-6">
          <div v-if="pendingInvoices.length === 0" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No pending invoices</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Purchase #</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Remaining</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Action</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <template v-for="invoice in pendingInvoices" :key="invoice.id">
                  <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-purple-600 dark:text-purple-400">
                      {{ invoice.purchase_no || 'N/A' }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                      {{ invoice.purchased_at ? formatDate(invoice.purchased_at) : 'N/A' }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                      PKR {{ parseFloat(invoice.grand_total || 0).toFixed(2) }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-red-600 dark:text-red-400">
                      PKR {{ parseFloat(invoice.remaining_amount || 0).toFixed(2) }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">
                      <span 
                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                        :class="{
                          'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': invoice.payment_status === 'paid',
                          'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': invoice.payment_status === 'unpaid',
                          'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': invoice.payment_status === 'partial'
                        }"
                      >
                        {{ invoice.payment_status ? invoice.payment_status.charAt(0).toUpperCase() + invoice.payment_status.slice(1) : 'Unknown' }}
                      </span>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">
                      <button
                        v-if="invoice.remaining_amount && invoice.remaining_amount > 0"
                        @click="showPaymentModal(invoice)"
                        class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300 text-sm font-medium"
                      >
                        Pay Now
                      </button>
                      <span v-else class="text-gray-400 text-sm">Paid</span>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Prepayment Modal -->
    <div v-if="showPrepaymentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Add Prepayment</h3>
          
          <form @submit.prevent="submitPrepayment">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Date *
              </label>
              <input
                v-model="prepaymentForm.date"
                type="date"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
              />
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Amount *
              </label>
              <input
                v-model="prepaymentForm.amount"
                type="number"
                step="0.01"
                min="0.01"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
                placeholder="0.00"
              />
            </div>

            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Note
              </label>
              <textarea
                v-model="prepaymentForm.note"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
                placeholder="Optional note"
              ></textarea>
            </div>

            <div class="flex justify-end gap-3">
              <button
                type="button"
                @click="closePrepaymentModal"
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="prepaymentProcessing"
                class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ prepaymentProcessing ? 'Adding...' : 'Add Prepayment' }}
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
            Pay Invoice #{{ showPaymentModalData.purchase_no }}
          </h3>
          
          <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <p class="text-sm text-gray-600 dark:text-gray-400">Remaining Payable: 
              <span class="font-semibold text-red-600 dark:text-red-400">
                PKR {{ parseFloat(showPaymentModalData.remaining_amount).toFixed(2) }}
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
                :max="showPaymentModalData.remaining_amount"
                min="0.01"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
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
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
              />
            </div>

            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Note
              </label>
              <textarea
                v-model="paymentForm.note"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"
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
                class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ paymentProcessing ? 'Processing...' : 'Pay Now' }}
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
  name: 'SupplierAccountDetails',
  props: {
    supplier: {
      type: Object,
      required: true
    },
    prepayments: {
      type: Array,
      default: () => []
    },
    pendingInvoices: {
      type: Array,
      default: () => []
    }
  },
  emits: ['refresh'],
  setup(props, { emit }) {
    const showPrepaymentModal = ref(false)
    const showPaymentModalData = ref(null)
    const prepaymentProcessing = ref(false)
    const paymentProcessing = ref(false)

    const prepaymentForm = reactive({
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
    const totalPrepayment = computed(() => {
      return (props.prepayments || []).reduce((sum, p) => sum + parseFloat(p?.amount || 0), 0)
    })

    const totalPayable = computed(() => {
      return (props.pendingInvoices || []).reduce((sum, inv) => sum + parseFloat(inv?.remaining_amount || 0), 0)
    })

    const netBalance = computed(() => {
      return totalPayable.value - totalPrepayment.value
    })

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString()
    }

    const closePrepaymentModal = () => {
      showPrepaymentModal.value = false
      prepaymentForm.date = new Date().toISOString().split('T')[0]
      prepaymentForm.amount = ''
      prepaymentForm.note = ''
    }

    const submitPrepayment = () => {
      prepaymentProcessing.value = true
      
      router.post(route('suppliers.prepayments.store', props.supplier.id), prepaymentForm, {
        onSuccess: () => {
          closePrepaymentModal()
          emit('refresh')
        },
        onFinish: () => {
          prepaymentProcessing.value = false
        }
      })
    }

    const showPaymentModal = (invoice) => {
      showPaymentModalData.value = invoice
      paymentForm.amount = parseFloat(invoice.remaining_amount || 0).toFixed(2)
    }

    const closePaymentModal = () => {
      showPaymentModalData.value = null
      paymentForm.amount = ''
      paymentForm.payment_date = new Date().toISOString().split('T')[0]
      paymentForm.note = ''
    }

    const submitPayment = () => {
      paymentProcessing.value = true
      
      router.post(route('suppliers.payments.store', [props.supplier.id, showPaymentModalData.value.id]), paymentForm, {
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
      showPrepaymentModal,
      showPaymentModalData,
      prepaymentProcessing,
      paymentProcessing,
      prepaymentForm,
      paymentForm,
      totalPrepayment,
      totalPayable,
      netBalance,
      formatDate,
      closePrepaymentModal,
      submitPrepayment,
      showPaymentModal,
      closePaymentModal,
      submitPayment
    }
  }
}
</script>

<template>
  <AppLayout>
    <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 sm:gap-0">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ isEditing ? 'Edit Transaction' : 'Cash Voucher' }}</h1>
          <p class="text-sm text-gray-500">Record cash received from customers or paid to suppliers</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Voucher Form -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Tabs -->
            <div class="flex border-b border-gray-200">
              <button
                @click="setVoucherType('received')"
                class="flex-1 py-4 text-center font-medium text-sm transition-colors duration-200"
                :class="form.type === 'received' ? 'bg-green-50 text-green-700 border-b-2 border-green-500' : 'text-gray-500 hover:text-gray-700'"
              >
                Cash Received (In)
              </button>
              <button
                @click="setVoucherType('paid')"
                class="flex-1 py-4 text-center font-medium text-sm transition-colors duration-200"
                :class="form.type === 'paid' ? 'bg-red-50 text-red-700 border-b-2 border-red-500' : 'text-gray-500 hover:text-gray-700'"
              >
                Cash Payment (Out)
              </button>
            </div>

            <form @submit.prevent="submitPayment" class="p-6 space-y-4">
              <!-- Party Selection -->
              <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  {{ form.type === 'received' ? 'Select Customer' : 'Select Supplier' }}
                </label>
                <Combobox v-model="selectedParty" nullable>
                  <div class="relative mt-1">
                    <div
                      class="relative w-full cursor-default overflow-hidden rounded-lg bg-white text-left border border-gray-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/75 focus-visible:ring-offset-2 focus-visible:ring-offset-indigo-300 sm:text-sm"
                    >
                      <ComboboxInput
                        class="w-full border-none py-2 pl-3 pr-10 text-sm leading-5 text-gray-900 focus:ring-0"
                        :displayValue="(party) => party ? `${party.name} ${party.phone ? '('+party.phone+')' : ''}` : ''"
                        @change="query = $event.target.value"
                        placeholder="Search name or phone..."
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
                        class="absolute mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm z-50"
                      >
                        <div
                          v-if="filteredParties.length === 0 && query !== ''"
                          class="relative cursor-default select-none px-4 py-2 text-gray-700"
                        >
                          Nothing found.
                        </div>

                        <ComboboxOption
                          v-for="party in filteredParties"
                          as="template"
                          :key="party.id"
                          :value="party"
                          v-slot="{ selected, active }"
                        >
                          <li
                            class="relative cursor-default select-none py-2 pl-10 pr-4"
                            :class="{
                              'bg-indigo-600 text-white': active,
                              'text-gray-900': !active,
                            }"
                          >
                            <span
                              class="block truncate"
                              :class="{ 'font-medium': selected, 'font-normal': !selected }"
                            >
                              {{ party.name }}
                              <span v-if="party.phone" :class="active ? 'text-indigo-200' : 'text-gray-500'">({{ party.phone }})</span>
                            </span>
                            <span
                              v-if="selected"
                              class="absolute inset-y-0 left-0 flex items-center pl-3"
                              :class="{ 'text-white': active, 'text-indigo-600': !active }"
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

              <!-- Balance Display -->
              <div v-if="form.party_id" class="p-4 rounded-lg transition-colors duration-200" :class="balanceClass">
                <p class="text-sm font-medium opacity-80">Current Balance</p>
                <div v-if="loadingBalance" class="animate-pulse h-8 w-32 bg-gray-200/50 rounded my-1"></div>
                <p v-else class="text-2xl font-bold">{{ formatCurrency(currentBalance) }}</p>
                <p class="text-xs mt-1">
                  {{ balanceText }}
                </p>
              </div>

              <!-- Amount -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                <div class="relative rounded-md shadow-sm">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">Rs.</span>
                  </div>
                  <input
                    v-model="form.amount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    class="w-full pl-10 rounded-lg border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-lg font-semibold"
                    placeholder="0.00"
                    required
                  />
                </div>
              </div>

              <!-- Date -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input
                  v-model="form.payment_date"
                  type="date"
                  class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  required
                />
              </div>

              <!-- Payment Method -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                <select
                  v-model="form.payment_method"
                  class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                >
                  <option value="cash">Cash</option>
                  <option value="bank_transfer">Bank Transfer</option>
                  <option value="check">Check</option>
                  <!-- <option value="online">Online</option> -->
                </select>
              </div>

              <!-- Note -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Note / Description</label>
                <textarea
                  v-model="form.note"
                  rows="3"
                  class="w-full rounded-lg border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="Optional note..."
                ></textarea>
              </div>

              <!-- Submit Button -->
              <div class="flex gap-2">
                  <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex-1 py-3 px-4 rounded-lg text-white font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 transition-colors"
                    :class="form.type === 'received' ? 'bg-green-600 hover:bg-green-700 focus:ring-green-500' : 'bg-red-600 hover:bg-red-700 focus:ring-red-500'"
                  >
                    <span v-if="isEditing">Update Transaction</span>
                    <span v-else>{{ form.processing ? 'Processing...' : (form.type === 'received' ? 'Receive Payment' : 'Make Payment') }}</span>
                  </button>
                  
                  <button
                    v-if="isEditing"
                    type="button"
                    @click="cancelEdit"
                    class="py-3 px-4 rounded-lg bg-gray-200 text-gray-800 font-medium hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
                  >
                    Cancel
                  </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Recent Transactions -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
              <h3 class="text-lg font-semibold text-gray-900">Recent Transactions</h3>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Party</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="payment in payments.data" :key="payment.id" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ formatDate(payment.payment_date) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ payment.customer ? payment.customer.name : (payment.supplier ? payment.supplier.name : '-') }}
                      <span class="text-xs text-gray-500 block">
                        {{ payment.customer ? 'Customer' : 'Supplier' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                      <span
                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                        :class="payment.type === 'received' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                      >
                        {{ payment.type === 'received' ? 'Received' : 'Paid' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                      {{ formatPaymentMethod(payment.payment_method) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold" :class="payment.type === 'received' ? 'text-green-600' : 'text-red-600'">
                      {{ formatCurrency(payment.amount) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500">
                      {{ payment.current_balance ? formatCurrency(payment.current_balance) : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center space-x-2">
                      <button 
                        @click="editPayment(payment)" 
                        class="text-indigo-600 hover:text-indigo-900 font-medium"
                      >
                        Edit
                      </button>
                      <button 
                        @click="deletePayment(payment.id)" 
                        class="text-red-600 hover:text-red-900 font-medium"
                      >
                        Delete
                      </button>
                    </td>
                  </tr>
                  <tr v-if="payments.data.length === 0">
                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                      No transactions found.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <!-- Pagination -->
            <div v-if="payments.links.length > 3" class="px-6 py-4 border-t border-gray-200">
              <!-- Add pagination component here if needed -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import axios from 'axios'
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
  customers: Array,
  suppliers: Array,
  payments: Object
})

const currentBalance = ref(0)
const loadingBalance = ref(false)
const isEditing = ref(false)
const editingId = ref(null)

// Searchable Combobox state
const query = ref('')
const selectedParty = ref(null)

const form = useForm({
  type: 'received', // received or paid
  party_type: 'customer', // customer or supplier
  party_id: '',
  amount: '',
  payment_date: new Date().toISOString().split('T')[0],
  payment_method: 'cash',
  note: ''
})

const currentParties = computed(() => {
  return form.type === 'received' ? props.customers : props.suppliers
})

const filteredParties = computed(() =>
  query.value === ''
    ? currentParties.value
    : currentParties.value.filter((party) =>
        party.name
          .toLowerCase()
          .replace(/\s+/g, '')
          .includes(query.value.toLowerCase().replace(/\s+/g, '')) ||
        (party.phone && party.phone.includes(query.value))
      )
)

// Sync selectedParty with form.party_id
watch(selectedParty, (newVal) => {
  if (newVal) {
    form.party_id = newVal.id
  } else {
    // Only clear if it was previously set, to avoid loops if needed, 
    // but here we want strict sync.
    // However, we must be careful not to clear it if it's being set from the form side
    // Actually, Combobox v-model works with the object.
    if (form.party_id !== '') {
       form.party_id = ''
    }
  }
})

// When form.party_id changes (e.g. edit mode or type switch), update selectedParty
watch(() => form.party_id, (newVal) => {
  if (newVal) {
    const party = currentParties.value.find(p => p.id === newVal)
    if (party && (!selectedParty.value || selectedParty.value.id !== party.id)) {
      selectedParty.value = party
    }
    fetchBalance()
  } else {
    selectedParty.value = null
    currentBalance.value = 0
  }
})

// Ensure selectedParty is cleared/updated when switching types
watch(() => form.type, () => {
    query.value = ''
    selectedParty.value = null
})

const balanceClass = computed(() => {
  if (loadingBalance.value) return 'bg-gray-100 text-gray-500'
  if (currentBalance.value > 0) return 'bg-red-50 text-red-700' // They owe us (Debit)
  if (currentBalance.value < 0) return 'bg-green-50 text-green-700' // We owe them (Credit)
  return 'bg-gray-100 text-gray-700'
})

const balanceText = computed(() => {
  if (loadingBalance.value) return 'Loading...'
  if (form.type === 'received') {
    // Customer context
    if (currentBalance.value > 0) return 'Customer owes you (Debit)'
    if (currentBalance.value < 0) return 'Advance / You owe customer (Credit)'
    return 'Account Settled'
  } else {
    // Supplier context
    if (currentBalance.value > 0) return 'You owe supplier (Credit)'
    if (currentBalance.value < 0) return 'Advance / Supplier owes you (Debit)'
    return 'Account Settled'
  }
})

const setVoucherType = (type) => {
  form.type = type
  form.party_type = type === 'received' ? 'customer' : 'supplier'
  form.party_id = ''
  currentBalance.value = 0
  form.clearErrors()
}

// Watch party_id to fetch balance automatically
watch(() => form.party_id, (newVal) => {
  if (newVal) {
    fetchBalance()
  } else {
    currentBalance.value = 0
  }
})

const fetchBalance = async () => {
  if (!form.party_id) return
  
  loadingBalance.value = true
  try {
    const response = await axios.get(route('api.party.balance'), {
      params: {
        type: form.party_type,
        id: form.party_id
      }
    })
    // Ensure we parse whatever comes back as a float
    currentBalance.value = typeof response.data.balance === 'string' 
      ? parseFloat(response.data.balance) 
      : response.data.balance
  } catch (error) {
    console.error('Error fetching balance:', error)
    currentBalance.value = 0
  } finally {
    loadingBalance.value = false
  }
}

const cancelEdit = () => {
    isEditing.value = false
    editingId.value = null
    form.reset()
    form.clearErrors()
    currentBalance.value = 0
}

const editPayment = (payment) => {
    isEditing.value = true
    editingId.value = payment.id
    
    form.type = payment.type
    // Determine party type based on ID
    if (payment.customer_id) {
        form.party_type = 'customer'
        form.party_id = payment.customer_id
    } else {
        form.party_type = 'supplier'
        form.party_id = payment.supplier_id
    }
    
    form.amount = payment.amount
    form.payment_date = payment.payment_date
    form.payment_method = payment.payment_method
    form.note = payment.note
    
    fetchBalance()
    
    // Scroll to form (Optional)
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

const deletePayment = (id) => {
    if (confirm('Are you sure you want to delete this transaction? The account balance will be reversed.')) {
        router.delete(route('payments.destroy', id), {
            preserveScroll: true,
            onSuccess: () => {
                 if (isEditing.value && editingId.value === id) {
                    cancelEdit()
                }
            }
        })
    }
}

const submitPayment = () => {
  if (isEditing.value) {
      form.put(route('payments.update', editingId.value), {
          onSuccess: () => {
              cancelEdit()
          }
      })
  } else {
      form.post(route('payments.store'), {
        onSuccess: () => {
          form.reset('amount', 'note')
          // Refresh balance
          fetchBalance()
        }
      })
  }
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-PK', {
    style: 'currency',
    currency: 'PKR'
  }).format(value)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-PK', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatPaymentMethod = (method) => {
  if (!method) return '-';
  return method.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}
</script>

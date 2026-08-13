<template>
  <AppLayout>
    <div class="p-4 sm:p-6">
      <div class="max-w-7xl mx-auto">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 sm:gap-0">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <Link :href="route('payments.index')" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
              </Link>
              <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Customer Payments</h1>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Select a customer to view and manage payments</p>
          </div>
        </div>

        <!-- Customer Selection -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6 p-6">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Select Customer
          </label>
          
          <div class="relative">
            <div class="relative">
              <input
                type="text"
                v-model="searchQuery"
                @input="onSearch"
                @focus="showDropdown = true"
                placeholder="Search customer by name or phone..."
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white pl-10"
              />
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <button 
                v-if="selectedCustomer"
                @click="clearSelection"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Dropdown Results -->
            <div 
              v-if="showDropdown && (searchResults.length > 0 || loadingSearch)"
              class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-y-auto"
            >
              <div v-if="loadingSearch" class="p-3 text-center text-gray-500">
                Searching...
              </div>
              <ul v-else>
                <li 
                  v-for="customer in searchResults" 
                  :key="customer.id"
                  @click="selectCustomer(customer)"
                  class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-0"
                >
                  <div class="font-medium text-gray-900 dark:text-white">{{ customer.name }}</div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">{{ customer.phone }}</div>
                </li>
              </ul>
            </div>
            <div v-if="showDropdown && searchQuery && !loadingSearch && searchResults.length === 0" class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg p-3 text-center text-gray-500">
              No customers found
            </div>
          </div>
        </div>

        <!-- Account Details -->
        <div v-if="accountData" class="mt-6">
          <CustomerAccountDetails
            :customer="accountData.customer"
            :advances="accountData.advances"
            :credit-history="accountData.creditHistory"
            @refresh="fetchAccountData(selectedCustomer)"
          />
        </div>
        
        <div v-else-if="loadingAccount" class="text-center py-12">
           <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
           <p class="mt-4 text-gray-500">Loading account details...</p>
        </div>

        <div v-else class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg shadow">
          <div class="bg-blue-50 dark:bg-blue-900/20 rounded-full h-20 w-20 flex items-center justify-center mx-auto mb-4">
            <svg class="h-10 w-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">No Customer Selected</h3>
          <p class="mt-2 text-gray-500">Search and select a customer above to view their payment history and manage their account.</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import CustomerAccountDetails from '@/components/CustomerAccountDetails.vue'

// Route helper
const route = window.route

const searchQuery = ref('')
const searchResults = ref([])
const showDropdown = ref(false)
const loadingSearch = ref(false)
const selectedCustomer = ref(null)
const accountData = ref(null)
const loadingAccount = ref(false)

// Simple debounce implementation if lodash is missing
const debounceFn = (fn, delay) => {
  let timeoutId
  return (...args) => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => fn(...args), delay)
  }
}

const performSearch = async () => {
  if (!searchQuery.value || searchQuery.value.length < 2) {
    searchResults.value = []
    return
  }

  loadingSearch.value = true
  try {
    const response = await fetch(route('api.customers.search') + `?q=${encodeURIComponent(searchQuery.value)}`)
    searchResults.value = await response.json()
  } catch (error) {
    console.error('Error searching customers:', error)
  } finally {
    loadingSearch.value = false
  }
}

const onSearch = debounceFn(performSearch, 300)

const selectCustomer = (customer) => {
  selectedCustomer.value = customer
  searchQuery.value = customer.name
  showDropdown.value = false
  fetchAccountData(customer)
}

const clearSelection = () => {
  selectedCustomer.value = null
  searchQuery.value = ''
  accountData.value = null
  searchResults.value = []
  showDropdown.value = false
}

const fetchAccountData = async (customer) => {
  if (!customer) return
  
  loadingAccount.value = true
  try {
    const response = await fetch(route('api.customers.account', customer.id))
    accountData.value = await response.json()
  } catch (error) {
    console.error('Error fetching account details:', error)
  } finally {
    loadingAccount.value = false
  }
}

// Close dropdown when clicking outside
const closeDropdown = (e) => {
  if (!e.target.closest('.relative')) {
    showDropdown.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', closeDropdown)
})

onUnmounted(() => {
  document.removeEventListener('click', closeDropdown)
})
</script>

<template>
  <AppLayout>
    <div class="p-6">
      <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Add New Supplier</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Create a new supplier for your business</p>
          </div>
          <Link 
            :href="route('suppliers.index')" 
            class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 flex items-center gap-2"
          >
            <ModernIcon name="arrow-left" class="w-4 h-4" />
            Back to Supplier List
          </Link>
        </div>

        <!-- Supplier Form -->
        <form @submit.prevent="submitForm" class="space-y-6">
          <!-- Basic Information Card -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Basic Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Supplier Name -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Supplier Name *
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="Enter supplier name"
                />
                <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
              </div>

              <!-- Phone -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Phone Number *
                </label>
                <input
                  v-model="form.phone"
                  type="tel"
                  required
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="Enter phone number"
                />
                <div v-if="form.errors.phone" class="text-red-500 text-sm mt-1">{{ form.errors.phone }}</div>
              </div>

              <!-- Email -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Email Address
                </label>
                <input
                  v-model="form.email"
                  type="email"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="Enter email address"
                />
                <div v-if="errors.email" class="text-red-500 text-sm mt-1">{{ errors.email }}</div>
              </div>

              <!-- Contact Person -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Contact Person
                </label>
                <input
                  v-model="form.contact_person"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="Enter contact person name"
                />
                <div v-if="errors.contact_person" class="text-red-500 text-sm mt-1">{{ errors.contact_person }}</div>
              </div>
            </div>

            <!-- Address -->
            <div class="mt-6">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Address
              </label>
              <textarea
                v-model="form.address"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Enter complete address"
              ></textarea>
              <div v-if="errors.address" class="text-red-500 text-sm mt-1">{{ errors.address }}</div>
            </div>
          </div>

          <!-- Business Information Card -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Business Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Company Registration -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Company Registration No.
                </label>
                <input
                  v-model="form.company_registration"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="Enter registration number"
                />
                <div v-if="errors.company_registration" class="text-red-500 text-sm mt-1">{{ errors.company_registration }}</div>
              </div>

              <!-- Tax Number -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Tax Number
                </label>
                <input
                  v-model="form.tax_number"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="Enter tax number"
                />
                <div v-if="errors.tax_number" class="text-red-500 text-sm mt-1">{{ errors.tax_number }}</div>
              </div>

              <!-- Supplier Type -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Supplier Type
                </label>
                <select
                  v-model="form.supplier_type"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                >
                  <option value="manufacturer">Manufacturer</option>
                  <option value="wholesaler">Wholesaler</option>
                  <option value="distributor">Distributor</option>
                  <option value="service_provider">Service Provider</option>
                  <option value="other">Other</option>
                </select>
                <div v-if="errors.supplier_type" class="text-red-500 text-sm mt-1">{{ errors.supplier_type }}</div>
              </div>

              <!-- Payment Terms -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Payment Terms (Days)
                </label>
                <input
                  v-model="form.payment_terms"
                  type="number"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="30"
                />
                <div v-if="errors.payment_terms" class="text-red-500 text-sm mt-1">{{ errors.payment_terms }}</div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Default payment terms in days</p>
              </div>
            </div>
          </div>

          <!-- Financial Information Card -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Financial Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Credit Limit -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Credit Limit
                </label>
                <input
                  v-model="form.credit_limit"
                  type="number"
                  step="0.01"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="0.00"
                />
                <div v-if="errors.credit_limit" class="text-red-500 text-sm mt-1">{{ errors.credit_limit }}</div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Maximum credit amount for purchases</p>
              </div>

              <!-- Opening Balance -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Opening Balance
                </label>
                <input
                  v-model="form.opening_balance"
                  type="number"
                  step="0.01"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="0.00"
                />
                <div v-if="errors.opening_balance" class="text-red-500 text-sm mt-1">{{ errors.opening_balance }}</div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Positive for prepaid, negative for owed amount</p>
              </div>
            </div>
          </div>

          <!-- Additional Information Card -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Additional Information</h3>
            
            <!-- Bank Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Bank Name
                </label>
                <input
                  v-model="form.bank_name"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="Enter bank name"
                />
                <div v-if="errors.bank_name" class="text-red-500 text-sm mt-1">{{ errors.bank_name }}</div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Account Number
                </label>
                <input
                  v-model="form.account_number"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  placeholder="Enter account number"
                />
                <div v-if="errors.account_number" class="text-red-500 text-sm mt-1">{{ errors.account_number }}</div>
              </div>
            </div>

            <!-- Notes -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Notes
              </label>
              <textarea
                v-model="form.notes"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Any additional notes about the supplier"
              ></textarea>
              <div v-if="errors.notes" class="text-red-500 text-sm mt-1">{{ errors.notes }}</div>
            </div>
          </div>

          <!-- Supplier Status Card -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Status</h3>
            
            <div class="space-y-4">
              <!-- Active Status -->
              <div class="flex items-center">
                <input
                  v-model="form.is_active"
                  type="checkbox"
                  id="is_active"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-white">
                  Supplier is active
                </label>
              </div>

              <!-- Preferred Supplier -->
              <div class="flex items-center">
                <input
                  v-model="form.is_preferred"
                  type="checkbox"
                  id="is_preferred"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label for="is_preferred" class="ml-2 block text-sm text-gray-900 dark:text-white">
                  Mark as preferred supplier
                </label>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-end gap-3">
            <Link
              :href="route('suppliers.index')"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              Cancel
            </Link>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <ModernIcon v-if="form.processing" name="spinner" class="animate-spin h-4 w-4" />
              {{ form.processing ? 'Creating...' : 'Create Supplier' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../layouts/AppLayout.vue'
import ModernIcon from '../../components/ModernIcon.vue'

export default {
  name: 'SupplierCreate',
  components: {
    AppLayout,
    Link
  },
  props: {
    errors: {
      type: Object,
      default: () => ({})
    }
  },
  setup() {
    const form = useForm({
      name: '',
      phone: '',
      email: '',
      contact_person: '',
      address: '',
      company_registration: '',
      tax_number: '',
      supplier_type: 'wholesaler',
      payment_terms: 30,
      credit_limit: 0,
      opening_balance: 0,
      bank_name: '',
      account_number: '',
      notes: '',
      is_active: true,
      is_preferred: false
    })

    const submitForm = () => {
      form.post(route('suppliers.store'))
    }

    return {
      form,
      submitForm
    }
  }
}
</script>
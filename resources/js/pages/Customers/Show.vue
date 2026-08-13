<template>
  <AppLayout>
    <PageHeader
      :title="customer.name"
      subtitle="Customer Details"
    >
      <div class="flex gap-3">
        <UiButton
          variant="outline"
          @click="$inertia.visit(route('customers.index'))"
        >
          Back to Customers
        </UiButton>
        <UiButton
          variant="primary"
          @click="$inertia.visit(route('customers.edit', customer.id))"
        >
          Edit Customer
        </UiButton>
      </div>
    </PageHeader>

    <div class="max-w-4xl mx-auto">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Basic Information -->
        <UiCard title="Basic Information" padding="lg">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Customer Name</label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ customer.name }}</p>
            </div>
            <div v-if="customer.email">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Address</label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ customer.email }}</p>
            </div>
            <div v-if="customer.phone">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone Number</label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ customer.phone }}</p>
            </div>
            <div v-if="customer.whatsapp">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">WhatsApp</label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ customer.whatsapp }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Customer Type</label>
              <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="customer.customer_type === 'business' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'">
                {{ customer.customer_type === 'business' ? 'Business' : 'Individual' }}
              </span>
            </div>
          </div>
        </UiCard>

        <!-- Address Information -->
        <UiCard title="Address Information" padding="lg">
          <div class="space-y-4">
            <div v-if="customer.address">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ customer.address }}</p>
            </div>
            <div v-if="customer.city">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">City</label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ customer.city }}</p>
            </div>
            <div v-if="customer.postal_code">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Postal Code</label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ customer.postal_code }}</p>
            </div>
            <div v-if="!customer.address && !customer.city && !customer.postal_code">
              <p class="text-sm text-gray-500 dark:text-gray-400 italic">No address information available</p>
            </div>
          </div>
        </UiCard>

        <!-- Financial Information -->
        <UiCard title="Financial Information" padding="lg">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Opening Balance</label>
              <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
                PKR {{ formatNumber(customer.opening_balance) }}
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Advance Payment</label>
              <p class="mt-1 text-lg font-semibold text-green-600 dark:text-green-400">
                PKR {{ formatNumber(customer.advance || 0) }}
              </p>
              <p class="text-xs text-gray-500 mt-1">Pre-paid amount available for purchases</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Credit Limit</label>
              <p class="mt-1 text-lg font-semibold text-blue-600 dark:text-blue-400">
                PKR {{ formatNumber(customer.credit_limit) }}
              </p>
              <p class="text-xs text-gray-500 mt-1">Maximum credit allowed for this customer</p>
            </div>
          </div>
        </UiCard>

        <!-- Additional Information -->
        <UiCard title="Additional Information" padding="lg">
          <div class="space-y-4">
            <div v-if="customer.notes">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ customer.notes }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Customer Since</label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(customer.created_at) }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Updated</label>
              <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(customer.updated_at) }}</p>
            </div>
            <div v-if="!customer.notes">
              <p class="text-sm text-gray-500 dark:text-gray-400 italic">No additional notes</p>
            </div>
          </div>
        </UiCard>
      </div>

      <!-- Sales History Section -->
      <div class="mt-8">
        <UiCard title="Recent Sales" padding="lg">
          <div v-if="customer.sales && customer.sales.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Sale ID
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Date
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Total Amount
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Status
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="sale in customer.sales.slice(0, 5)" :key="sale.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    #{{ sale.id }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    {{ formatDate(sale.created_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    PKR {{ formatNumber(sale.total_amount) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="sale.status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'">
                      {{ sale.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="text-center py-8">
            <p class="text-gray-500 dark:text-gray-400">No sales history available</p>
          </div>
        </UiCard>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiButton from '@/components/UiButton.vue'
import UiCard from '@/components/UiCard.vue'

// Props
const props = defineProps({
  customer: {
    type: Object,
    required: true
  }
})

// Helper functions
const formatNumber = (value) => {
  if (value === null || value === undefined) return '0'
  return Number(value).toLocaleString('en-PK', { 
    minimumFractionDigits: 0,
    maximumFractionDigits: 2 
  })
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-PK', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}
</script>
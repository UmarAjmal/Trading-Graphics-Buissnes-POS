<template>
  <AppLayout>
    <div class="p-6">
      <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ customer.name }} - Account</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage customer payments and credit history</p>
          </div>
          <Link 
            :href="route('customers.index')" 
            class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Customer List
          </Link>
        </div>

        <CustomerAccountDetails 
          :customer="customer" 
          :advances="advances" 
          :credit-history="creditHistory" 
          @refresh="refreshData"
        />
      </div>
    </div>
  </AppLayout>
</template>

<script>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '../../layouts/AppLayout.vue'
import CustomerAccountDetails from '../../components/CustomerAccountDetails.vue'

export default {
  name: 'CustomerAccount',
  components: {
    AppLayout,
    Link,
    CustomerAccountDetails
  },
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
  setup() {
    const refreshData = () => {
      router.reload()
    }

    return {
      refreshData
    }
  }
}
</script>
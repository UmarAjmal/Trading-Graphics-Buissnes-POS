<template>
  <AppLayout>
    <PageHeader
      title="Customers"
      subtitle="Manage your customer database and contact information"
    >
      <template #actions>
        <div class="flex flex-col sm:flex-row gap-2">
          <button
            @click="showImportModal = true"
            class="bg-green-500 text-white px-2 sm:px-3 py-2 rounded-lg hover:bg-green-600 flex items-center justify-center gap-2 text-sm"
          >
            <Upload class="w-4 h-4" />
            <span class="hidden sm:inline">Import</span>
          </button>
          <a
            :href="route('customers.export')"
            class="bg-blue-500 text-white px-2 sm:px-3 py-2 rounded-lg hover:bg-blue-600 flex items-center justify-center gap-2 text-sm"
          >
            <Download class="w-4 h-4" />
            <span class="hidden sm:inline">Export</span>
          </a>
          <Link
            :href="route('customers.create')"
            class="bg-primary-600 text-white px-3 py-2 rounded-lg hover:bg-primary-700 flex items-center justify-center gap-2 text-sm"
          >
            <Plus class="w-4 h-4" />
            <span class="hidden sm:inline">Add Customer</span>
          </Link>
        </div>
      </template>
    </PageHeader>

    <!-- Credit Alerts -->
    <div v-if="creditAlerts && creditAlerts.length > 0" class="mb-6">
      <UiCard title="Credit Limit Alerts" icon="triangle-alert" padding="lg">
        <div class="space-y-3">
          <div
            v-for="customer in creditAlerts"
            :key="customer.id"
            :class="[
              'flex items-center justify-between p-3 rounded-lg border',
              customer.credit_status === 'exceeded' 
                ? 'bg-red-50 border-red-200 text-red-800' 
                : 'bg-yellow-50 border-yellow-200 text-yellow-800'
            ]"
          >
            <div class="flex items-center">
              <div 
                :class="[
                  'w-2 h-2 rounded-full mr-3',
                  customer.credit_status === 'exceeded' ? 'bg-red-500' : 'bg-yellow-500'
                ]"
              ></div>
              <div>
                <p class="font-medium">{{ customer.name }}</p>
                <p class="text-sm">
                  Credit Used: PKR {{ customer.credit_used.toFixed(2) }} / PKR {{ customer.credit_limit }}
                  <span v-if="customer.credit_status === 'exceeded'" class="font-medium"> (EXCEEDED)</span>
                  <span v-else class="font-medium"> (Near Limit)</span>
                </p>
              </div>
            </div>
            <Link
              :href="route('customers.show', customer.id)"
              class="text-sm underline hover:no-underline"
            >
              View Details
            </Link>
          </div>
        </div>
      </UiCard>
    </div>

    <UiCard>
      <DataTable
        ref="tableRef"
        :url="route('customers.datatable')"
        :columns="columns"
        :filters="filters"
      >
        <template #filter>
          <div class="grid grid-cols-1 gap-4 mb-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
              <input
                v-model="filters.search"
                type="text"
                placeholder="Search customers..."
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
              />
            </div>
            <div class="flex items-end">
              <button
                @click="resetFilters"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
              >
                Reset Filters
              </button>
            </div>
          </div>
        </template>

        <!-- Name Column -->
        <template #column.name="{ item }">
          <div class="flex items-center">
            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
              <span class="text-blue-600 font-medium text-sm">
                {{ item.name.charAt(0).toUpperCase() }}
              </span>
            </div>
            <div>
              <div class="font-medium text-gray-900">{{ item.name }}</div>
              <div class="text-sm text-gray-500" v-if="item.email">{{ item.email }}</div>
            </div>
          </div>
        </template>

        <!-- Phone Column -->
        <template #column.phone="{ item }">
          <span v-if="item.phone" class="text-gray-900">{{ item.phone }}</span>
          <span v-else class="text-gray-400">-</span>
        </template>

        <!-- Credit Status Column -->
        <template #column.credit_status="{ item }">
          <div v-if="item.credit_limit > 0" class="flex items-center">
            <span 
              :class="[
                'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                item.credit_status === 'exceeded' 
                  ? 'bg-red-100 text-red-800' 
                  : item.credit_status === 'warning' 
                    ? 'bg-yellow-100 text-yellow-800' 
                    : 'bg-green-100 text-green-800'
              ]"
            >
              <span 
                :class="[
                  'w-1.5 h-1.5 rounded-full mr-1',
                  item.credit_status === 'exceeded' 
                    ? 'bg-red-500' 
                    : item.credit_status === 'warning' 
                      ? 'bg-yellow-500' 
                      : 'bg-green-500'
                ]"
              ></span>
              {{
                item.credit_status === 'exceeded' 
                  ? 'Exceeded' 
                  : item.credit_status === 'warning' 
                    ? 'Near Limit' 
                    : 'Good'
              }}
            </span>
            <div class="text-xs text-gray-500 ml-2">
              PKR {{ (item.credit_used || 0).toFixed(2) }} / PKR {{ item.credit_limit || 0 }}
            </div>
          </div>
          <span v-else class="text-gray-400 text-sm">No Limit</span>
        </template>

        <!-- Opening Balance Column -->
        <template #column.opening_balance="{ item }">
          <span v-if="item.opening_balance > 0" class="text-red-600 font-medium">
            PKR {{ item.opening_balance }}
          </span>
          <span v-else class="text-gray-400">-</span>
        </template>

        <!-- Address Column -->
        <template #column.address="{ item }">
          <span v-if="item.address" class="text-gray-900">{{ item.address }}</span>
          <span v-else class="text-gray-400">-</span>
        </template>

        <!-- Actions Column -->
        <template #actions="{ item }">
          <div class="flex items-center space-x-2">
            <Link
              :href="route('customers.show', item.id)"
              class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 p-1"
              title="View Customer"
            >
              <Eye class="w-4 h-4" />
            </Link>
            <Link
              :href="route('customers.edit', item.id)"
              class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300 p-1"
              title="Edit Customer"
            >
              <Edit class="w-4 h-4" />
            </Link>
            <Link
              :href="route('customers.account', item.id)"
              class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 p-1"
              title="Payment Management"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
              </svg>
            </Link>
            <button
              @click="deleteCustomer(item.id)"
              class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 p-1"
              title="Delete Customer"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </template>
      </DataTable>
    </UiCard>

    <!-- Import Modal -->
    <UiModal v-model="showImportModal">
      <div class="p-6">
        <h3 class="text-lg font-medium mb-4">Import Customers</h3>
        <p class="text-sm text-gray-500 mb-4">Upload an Excel (.xlsx) or CSV file with columns: name, email, phone, address</p>
        <div class="mb-4">
          <input ref="fileInput" type="file" accept=".xlsx,.csv" class="w-full" />
        </div>
        <div class="flex justify-end gap-3">
          <button @click="showImportModal = false" class="px-4 py-2 border border-gray-300 rounded-lg">Cancel</button>
          <button @click="importFile" :disabled="importing" class="px-4 py-2 bg-primary text-white rounded-lg disabled:opacity-50">
            {{ importing ? 'Importing...' : 'Import' }}
          </button>
        </div>
      </div>
    </UiModal>
  </AppLayout>
</template>

<script>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiCard from '@/components/UiCard.vue'
import DataTable from '@/components/DataTable.vue'
import { Plus, Eye, Edit, Trash2, Upload, Download } from 'lucide-vue-next'
import UiModal from '@/components/UiModal.vue'

// Route helper
const route = window.route

export default {
  name: 'CustomersIndex',
  components: {
    AppLayout,
    PageHeader,
    UiCard,
    DataTable,
    UiModal,
    Link,
    Plus,
    Eye,
    Edit,
    Trash2,
    Upload,
    Download
  },
  props: {
    customers: {
      type: Object,
      default: () => ({})
    },
    creditAlerts: {
      type: Array,
      default: () => []
    }
  },
  setup() {
    const tableRef = ref(null)
    const fileInput = ref(null)
    const showImportModal = ref(false)
    const importing = ref(false)
    
    const columns = [
      {
        key: 'name',
        label: 'Customer',
        sortable: true
      },
      {
        key: 'phone',
        label: 'Phone',
        sortable: true
      },
      {
        key: 'credit_status',
        label: 'Credit Status',
        sortable: false
      },
      {
        key: 'opening_balance',
        label: 'Opening Balance',
        sortable: true
      },
      {
        key: 'actions',
        label: 'Actions',
        sortable: false
      }
    ]
    
    const filters = ref({
      search: '',
    })
    
    // Watch filters and refresh table
    watch(filters, () => {
      if (tableRef.value) {
        tableRef.value.refresh()
      }
    }, { deep: true })
    
    const resetFilters = () => {
      filters.value = {
        search: '',
        customer_type: '',
      }
    }
    
    const deleteCustomer = (id) => {
      if (confirm('Are you sure you want to delete this customer?')) {
        router.delete(route('customers.destroy', id), {
          preserveScroll: true,
          onSuccess: () => {
            if (tableRef.value) {
              tableRef.value.refresh()
            }
          }
        })
      }
    }

    const importFile = () => {
      if (!fileInput.value?.files[0]) return
      importing.value = true

      const formData = new FormData()
      formData.append('file', fileInput.value.files[0])

      router.post(route('customers.import'), formData, {
        onSuccess: () => {
          showImportModal.value = false
          importing.value = false
          if (tableRef.value) tableRef.value.refresh()
        },
        onError: () => { importing.value = false },
        onFinish: () => { importing.value = false }
      })
    }
    
    return {
      tableRef,
      columns,
      filters,
      resetFilters,
      deleteCustomer,
      fileInput,
      showImportModal,
      importing,
      importFile
    }
  }
}
</script>
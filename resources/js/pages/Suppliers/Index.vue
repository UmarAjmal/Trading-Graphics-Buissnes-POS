<template>
  <AppLayout>
    <PageHeader
      title="Suppliers"
      subtitle="Manage suppliers and vendor relationships"
    >
      <template #actions>
        <div class="flex flex-col sm:flex-row gap-2">
          <button
            @click="showImportModal = true"
            class="bg-green-500 text-white px-2 sm:px-4 py-2 rounded-lg hover:bg-green-600 flex items-center justify-center gap-2 text-sm"
          >
            <Upload class="w-4 h-4" />
            <span class="hidden sm:inline">Import</span>
          </button>
          <a
            :href="route('suppliers.export')"
            class="bg-blue-500 text-white px-2 sm:px-4 py-2 rounded-lg hover:bg-blue-600 flex items-center justify-center gap-2 text-sm"
          >
            <Download class="w-4 h-4" />
            <span class="hidden sm:inline">Export</span>
          </a>
          <Link
            :href="route('suppliers.create')"
            class="bg-primary-600 text-white px-3 py-2 rounded-lg hover:bg-primary-700 flex items-center justify-center gap-2 text-sm"
          >
            <Plus class="w-4 h-4" />
            <span class="hidden sm:inline">Add Supplier</span>
          </Link>
        </div>
      </template>
    </PageHeader>

    <UiCard>
      <DataTable
        ref="tableRef"
        :url="route('suppliers.datatable')"
        :columns="columns"
        :filters="filters"
      >
        <template #filter>
          <div class="grid grid-cols-1 gap-4 mb-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
              <input
                v-model="filters.search"
                type="text"
                placeholder="Search by name, email, phone..."
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
              <select
                v-model="filters.status"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
              <input
                v-model="filters.address"
                type="text"
                placeholder="Filter by address..."
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
              />
            </div>
          </div>
        </template>

        <!-- Opening Balance Column -->
        <template #column.opening_balance="{ item }">
          <span v-if="item.opening_balance > 0" class="text-orange-600 font-medium">
            PKR {{ item.opening_balance }}
          </span>
          <span v-else class="text-gray-400">-</span>
        </template>

        <template #actions="{ item }">
          <div class="flex items-center gap-2">
            <Link
              :href="route('suppliers.show', item.id)"
              class="text-blue-600 hover:text-blue-800"
              title="View Details"
            >
              <Eye class="w-4 h-4" />
            </Link>
            <Link
              :href="route('suppliers.edit', item.id)"
              class="text-green-600 hover:text-green-800"
              title="Edit"
            >
              <Edit class="w-4 h-4" />
            </Link>
            <Link
              :href="route('purchases.create', { supplier_id: item.id })"
              class="text-purple-600 hover:text-purple-800"
              title="Create Purchase Order"
            >
              <ShoppingCart class="w-4 h-4" />
            </Link>
            <button
              @click="toggleStatus(item)"
              :class="item.is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800'"
              :title="item.is_active ? 'Deactivate' : 'Activate'"
            >
              <Power class="w-4 h-4" />
            </button>
          </div>
        </template>
      </DataTable>
    </UiCard>

    <!-- Import Modal -->
    <Modal :show="showImportModal" @close="showImportModal = false">
      <div class="p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Import Suppliers</h3>
          <button @click="showImportModal = false" class="text-gray-400 hover:text-gray-600">
            <X class="w-5 h-5" />
          </button>
        </div>
        
        <form @submit.prevent="importFile" enctype="multipart/form-data">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Select Excel/CSV File
            </label>
            <input
              type="file"
              ref="fileInput"
              accept=".xlsx,.csv"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
            />
            <p class="text-sm text-gray-500 mt-1">
              Supported formats: Excel (.xlsx), CSV (.csv)
            </p>
          </div>
          
          <div class="flex justify-end gap-3">
            <button
              type="button"
              @click="showImportModal = false"
              class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="importing"
              class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 disabled:opacity-50"
            >
              {{ importing ? 'Importing...' : 'Import' }}
            </button>
          </div>
        </form>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiCard from '@/components/UiCard.vue'
import DataTable from '@/components/DataTable.vue'
import Modal from '@/components/Modal.vue'
import { Plus, Eye, Edit, ShoppingCart, Power, Upload, Download, X } from 'lucide-vue-next'

// Route helper
const route = window.route

const tableRef = ref(null)
const fileInput = ref(null)
const showImportModal = ref(false)
const importing = ref(false)

const filters = ref({
  search: '',
  status: '',
  address: ''
})

const columns = [
  {
    key: 'name',
    label: 'Supplier Name',
    sortable: true
  },
  {
    key: 'phone',
    label: 'Phone',
    sortable: false
  },
  {
    key: 'address',
    label: 'Address',
    sortable: true
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

// Watch filters and refresh table
watch(filters, () => {
  if (tableRef.value) {
    tableRef.value.refresh()
  }
}, { deep: true })

const toggleStatus = (supplier) => {
  const action = supplier.is_active ? 'deactivate' : 'activate'
  const message = supplier.is_active 
    ? `Are you sure you want to deactivate ${supplier.name}?`
    : `Are you sure you want to activate ${supplier.name}?`
  
  if (confirm(message)) {
    router.patch(route('suppliers.toggle-status', supplier.id), {}, {
      onSuccess: () => {
        tableRef.value.refresh()
      }
    })
  }
}

const importFile = () => {
  if (!fileInput.value?.files[0]) return
  
  importing.value = true
  const formData = new FormData()
  formData.append('file', fileInput.value.files[0])
  
  router.post(route('suppliers.import'), formData, {
    onSuccess: () => {
      showImportModal.value = false
      importing.value = false
      if (tableRef.value) {
        tableRef.value.refresh()
      }
    },
    onError: () => {
      importing.value = false
    },
    onFinish: () => {
      importing.value = false
    }
  })
}
</script>
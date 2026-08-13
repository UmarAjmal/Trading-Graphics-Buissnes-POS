<template>
  <AppLayout>
    <PageHeader
      title="Units"
      subtitle="Manage product units of measurement"
    >
      <template #actions>
        <div class="flex gap-2">
          <a
            :href="route('units.export')"
            class="bg-blue-500 text-white px-3 py-2 rounded-lg hover:bg-blue-600 flex items-center justify-center gap-2 text-sm"
          >
            <Download class="w-4 h-4" />
            <span class="hidden sm:inline">Export</span>
          </a>
          <Link
            :href="route('units.create')"
            class="bg-primary-600 text-white px-3 py-2 rounded-lg hover:bg-primary-700 flex items-center justify-center gap-2 text-sm"
          >
            <Plus class="w-4 h-4" />
            <span class="hidden sm:inline">Add Unit</span>
          </Link>
        </div>
      </template>
    </PageHeader>

    <UiCard>
      <DataTable
        ref="tableRef"
        :url="route('units.datatable')"
        :columns="columns"
        :filters="filters"
      >
        <template #filter>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
              <input
                v-model="filters.search"
                type="text"
                placeholder="Search units..."
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

        <!-- Actions Column -->
        <template #actions="{ item }">
          <div class="flex items-center space-x-2">
            <Link
              :href="route('units.show', item.id)"
              class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 p-1"
              title="View Unit"
            >
              <Eye class="w-4 h-4" />
            </Link>
            <Link
              :href="route('units.edit', item.id)"
              class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300 p-1"
              title="Edit Unit"
            >
              <Edit class="w-4 h-4" />
            </Link>
            <button
              @click="deleteUnit(item.id)"
              class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 p-1"
              title="Delete Unit"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </template>
      </DataTable>
    </UiCard>
  </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiCard from '@/components/UiCard.vue'
import DataTable from '@/components/DataTable.vue'
import { Plus, Eye, Edit, Trash2, Download } from 'lucide-vue-next'

// Route helper
const route = window.route

const tableRef = ref(null)

const columns = [
  {
    key: 'name',
    label: 'Unit Name',
    sortable: true
  },
  {
    key: 'code',
    label: 'Code',
    sortable: true
  },
  {
    key: 'symbol',
    label: 'Symbol',
    sortable: true
  },
  {
    key: 'products_count',
    label: 'Products',
    sortable: true
  },
  {
    key: 'created_at',
    label: 'Created',
    sortable: true,
    format: (value) => new Date(value).toLocaleDateString()
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
  }
}

const deleteUnit = (id) => {
  if (confirm('Are you sure you want to delete this unit?')) {
    router.delete(route('units.destroy', id), {
      preserveScroll: true,
      onSuccess: () => {
        if (tableRef.value) {
          tableRef.value.refresh()
        }
      }
    })
  }
}
</script>
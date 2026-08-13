<template>
  <div class="data-table">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-3">
      <div class="flex items-center space-x-2">
        <label for="per-page" class="text-sm font-medium text-gray-700">Show:</label>
        <select
          id="per-page"
          v-model="perPage"
          @change="updatePerPage"
          class="border border-gray-300 rounded px-2 py-1 text-sm"
        >
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <span class="text-sm text-gray-700">entries</span>
      </div>
      
      <div class="flex items-center space-x-2 w-full sm:w-auto">
        <label for="search" class="text-sm font-medium text-gray-700 whitespace-nowrap">Search:</label>
        <input
          id="search"
          v-model="searchTerm"
          @input="handleSearch"
          type="text"
          placeholder="Search..."
          class="border border-gray-300 rounded px-3 py-1 text-sm flex-1 sm:w-64"
        />
      </div>
    </div>

    <div class="overflow-x-auto -mx-4 sm:mx-0">
      <table class="min-w-full table-auto border-collapse">
        <thead>
          <tr class="bg-gray-50">
            <th
              v-for="column in columns"
              :key="column.key"
              @click="column.sortable ? sort(column.key) : null"
              :class="[
                'px-2 sm:px-4 py-2 text-left text-xs sm:text-sm font-medium text-gray-700 border-b whitespace-nowrap',
                column.sortable ? 'cursor-pointer hover:bg-gray-100' : ''
              ]"
            >
              <div class="flex items-center space-x-1">
                <span>{{ column.label }}</span>
                <span v-if="column.sortable && sortBy === column.key" class="text-xs">
                  {{ sortDirection === 'asc' ? '↑' : '↓' }}
                </span>
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(item, index) in paginatedData"
            :key="item.id || index"
            class="hover:bg-gray-50"
          >
            <td
              v-for="column in columns"
              :key="column.key"
              class="px-2 sm:px-4 py-2 text-xs sm:text-sm text-gray-900 border-b"
            >
              <slot
                v-if="column.key === 'actions'"
                name="actions"
                :item="item"
              >
                <!-- Default actions content -->
              </slot>
              <slot
                v-else
                :name="`column.${column.key}`"
                :item="item"
                :value="getNestedValue(item, column.key)"
              >
                <span v-if="column.format">{{ column.format(getNestedValue(item, column.key)) }}</span>
                <span v-else>{{ getNestedValue(item, column.key) }}</span>
              </slot>
            </td>
          </tr>
          <tr v-if="!paginatedData || paginatedData.length === 0">
            <td :colspan="columns.length" class="px-4 py-8 text-center text-gray-500">
              <div v-if="loading" class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Loading...
              </div>
              <div v-else>
                No data available
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="flex justify-between items-center mt-4">
      <div class="text-sm text-gray-700">
        Showing {{ startRecord }} to {{ endRecord }} of {{ totalRecords }} entries
      </div>
      
      <div class="flex space-x-1">
        <button
          @click="goToPage(currentPageDisplay - 1)"
          :disabled="currentPageDisplay === 1"
          class="px-3 py-1 text-sm border border-gray-300 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
        >
          Previous
        </button>
        
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="goToPage(page)"
          :class="[
            'px-3 py-1 text-sm border border-gray-300 rounded',
            page === currentPageDisplay 
              ? 'bg-blue-500 text-white border-blue-500' 
              : 'hover:bg-gray-50'
          ]"
        >
          {{ page }}
        </button>
        
        <button
          @click="goToPage(currentPageDisplay + 1)"
          :disabled="currentPageDisplay === totalPages"
          class="px-3 py-1 text-sm border border-gray-300 rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'DataTable',
  props: {
    data: {
      type: Array,
      default: () => []
    },
    url: {
      type: String,
      default: null
    },
    columns: {
      type: Array,
      required: true
    },
    filters: {
      type: Object,
      default: () => ({})
    },
    searchable: {
      type: Boolean,
      default: true
    },
    sortable: {
      type: Boolean,
      default: true
    },
    itemsPerPage: {
      type: Number,
      default: 10
    }
  },
  setup(props, { expose }) {
    const searchTerm = ref('')
    const sortBy = ref('')
    const sortDirection = ref('asc')
    const currentPage = ref(1)
    const perPage = ref(props.itemsPerPage)
    const loading = ref(false)
    const serverData = ref({
      data: [],
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
      from: 0,
      to: 0
    })

    // If URL is provided, use server-side data
    const isServerSide = computed(() => !!props.url)
    
    const actualData = computed(() => {
      return isServerSide.value ? (serverData.value?.data || []) : (props.data || [])
    })

    const filteredData = computed(() => {
      if (isServerSide.value) {
        return actualData.value || []
      }

      let filtered = props.data || []

      if (searchTerm.value && props.searchable) {
        const term = searchTerm.value.toLowerCase()
        filtered = filtered.filter(item => {
          return props.columns.some(column => {
            const value = getNestedValue(item, column.key)
            return String(value).toLowerCase().includes(term)
          })
        })
      }

      if (sortBy.value && props.sortable) {
        filtered = [...filtered].sort((a, b) => {
          const aVal = getNestedValue(a, sortBy.value)
          const bVal = getNestedValue(b, sortBy.value)
          
          if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1
          if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1
          return 0
        })
      }

      return filtered
    })

    const paginatedData = computed(() => {
      if (isServerSide.value) {
        return filteredData.value || []
      }
      
      const data = filteredData.value || []
      const start = (currentPage.value - 1) * perPage.value
      const end = start + perPage.value
      return data.slice(start, end)
    })

    const totalPages = computed(() => {
      if (isServerSide.value) {
        return serverData.value?.last_page || 1
      }
      return Math.ceil((filteredData.value?.length || 0) / perPage.value)
    })

    const currentPageDisplay = computed(() => {
      if (isServerSide.value) {
        return serverData.value?.current_page || 1
      }
      return currentPage.value
    })

    const startRecord = computed(() => {
      if (isServerSide.value) {
        return serverData.value?.from || 0
      }
      return (currentPage.value - 1) * perPage.value + 1
    })

    const endRecord = computed(() => {
      if (isServerSide.value) {
        return serverData.value?.to || 0
      }
      return Math.min(currentPage.value * perPage.value, filteredData.value?.length || 0)
    })

    const totalRecords = computed(() => {
      if (isServerSide.value) {
        return serverData.value?.total || 0
      }
      return filteredData.value?.length || 0
    })

    const visiblePages = computed(() => {
      const pages = []
      const maxVisible = 5
      const current = currentPageDisplay.value
      let start = Math.max(1, current - Math.floor(maxVisible / 2))
      let end = Math.min(totalPages.value, start + maxVisible - 1)
      
      if (end - start + 1 < maxVisible) {
        start = Math.max(1, end - maxVisible + 1)
      }
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      return pages
    })

    const getNestedValue = (obj, key) => {
      return key.split('.').reduce((o, k) => (o || {})[k], obj)
    }

    const loadServerData = async () => {
      if (!props.url) return
      
      loading.value = true
      
      try {
        const params = new URLSearchParams()
        
        // Add search
        if (searchTerm.value) {
          params.append('search', searchTerm.value)
        }
        
        // Add filters
        Object.keys(props.filters).forEach(key => {
          if (props.filters[key] !== '' && props.filters[key] !== null && props.filters[key] !== undefined) {
            params.append(key, props.filters[key])
          }
        })
        
        // Add sorting
        if (sortBy.value) {
          params.append('sort_by', sortBy.value)
          params.append('sort_order', sortDirection.value)
        }
        
        // Add pagination
        params.append('page', currentPage.value)
        params.append('per_page', perPage.value)
        
        const response = await axios.get(`${props.url}?${params}`)
        
        let responseData = null
        
        if (response.data.success) {
          responseData = response.data.data
        } else {
          // Handle case where response doesn't have success wrapper
          responseData = response.data
        }
        
        // Ensure we have a valid data structure
        if (responseData) {
          serverData.value = {
            data: responseData.data || [],
            current_page: responseData.current_page || 1,
            last_page: responseData.last_page || 1,
            per_page: responseData.per_page || perPage.value,
            total: responseData.total || 0,
            from: responseData.from || 0,
            to: responseData.to || 0
          }
          
          if (isServerSide.value) {
            currentPage.value = responseData.current_page || 1
          }
        }
      } catch (error) {
        console.error('Failed to load data:', error)
        // Ensure serverData is in a valid state even on error
        serverData.value = {
          data: [],
          current_page: 1,
          last_page: 1,
          per_page: perPage.value,
          total: 0,
          from: 0,
          to: 0
        }
      } finally {
        loading.value = false
      }
    }

    const sort = (column) => {
      if (sortBy.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
      } else {
        sortBy.value = column
        sortDirection.value = 'asc'
      }
      currentPage.value = 1
      
      if (isServerSide.value) {
        loadServerData()
      }
    }

    const handleSearch = () => {
      currentPage.value = 1
      if (isServerSide.value) {
        loadServerData()
      }
    }

    const updatePerPage = () => {
      currentPage.value = 1
      if (isServerSide.value) {
        loadServerData()
      }
    }

    const goToPage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
        if (isServerSide.value) {
          loadServerData()
        }
      }
    }

    // Watch for filter changes
    watch(() => props.filters, () => {
      if (isServerSide.value) {
        currentPage.value = 1
        loadServerData()
      }
    }, { deep: true })

    const refresh = () => {
      if (isServerSide.value) {
        loadServerData()
      }
    }

    expose({ refresh })

    // Load data on mount
    onMounted(() => {
      if (isServerSide.value) {
        loadServerData()
      }
    })

    return {
      searchTerm,
      sortBy,
      sortDirection,
      currentPage,
      perPage,
      loading,
      paginatedData,
      totalPages,
      currentPageDisplay,
      startRecord,
      endRecord,
      totalRecords,
      visiblePages,
      getNestedValue,
      sort,
      handleSearch,
      updatePerPage,
      goToPage,
      refresh
    }
  }
}
</script>

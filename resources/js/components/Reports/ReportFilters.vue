<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
    <div class="flex flex-wrap items-center gap-4">
      <!-- Period Filter -->
      <div class="flex-1 min-w-[200px]">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Time Period
        </label>
        <select
          v-model="selectedPeriod"
          @change="handlePeriodChange"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
        >
          <option value="daily">Daily</option>
          <option value="weekly">Weekly</option>
          <option value="monthly">Monthly</option>
          <option value="yearly">Yearly</option>
          <option value="custom">Custom Range</option>
        </select>
      </div>

      <!-- Custom Date Range -->
      <div v-if="selectedPeriod === 'custom'" class="flex gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Start Date
          </label>
          <input
            type="date"
            v-model="startDate"
            @change="handleDateChange"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            End Date
          </label>
          <input
            type="date"
            v-model="endDate"
            @change="handleDateChange"
            class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
          />
        </div>
      </div>

      <!-- Extra Filters Slot -->
      <slot />

      <!-- Apply Button -->
      <div class="flex items-end">
        <button
          @click="applyFilters"
          :disabled="loading"
          class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <div v-if="loading" class="flex items-center">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading...
          </div>
          <span v-else>Apply Filters</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({})
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:filters', 'apply'])

const selectedPeriod = ref(props.filters.period || 'daily')
const startDate = ref(props.filters.start_date || '')
const endDate = ref(props.filters.end_date || '')

const handlePeriodChange = () => {
  if (selectedPeriod.value !== 'custom') {
    startDate.value = ''
    endDate.value = ''
    applyFilters()
  }
}

const handleDateChange = () => {
  if (startDate.value && endDate.value) {
    applyFilters()
  }
}

const applyFilters = () => {
  const filters = {
    period: selectedPeriod.value,
    start_date: startDate.value,
    end_date: endDate.value
  }
  
  emit('update:filters', filters)
  emit('apply', filters)
}

// Watch for external filter changes
watch(() => props.filters, (newFilters) => {
  selectedPeriod.value = newFilters.period || 'daily'
  startDate.value = newFilters.start_date || ''
  endDate.value = newFilters.end_date || ''
}, { deep: true })
</script>
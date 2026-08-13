<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div
      v-for="stat in stats"
      :key="stat.key"
      class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border-l-4"
      :class="stat.borderColor"
    >
      <div class="flex items-center">
        <div class="flex-1">
          <div class="flex items-center">
            <div
              class="p-2 rounded-lg mr-3"
              :class="stat.iconBg"
            >
              <component :is="stat.icon" class="w-6 h-6" :class="stat.iconColor" />
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                {{ stat.label }}
              </p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                <span v-if="stat.type === 'currency'">
                  {{ formatCurrency(stat.value) }}
                </span>
                <span v-else-if="stat.type === 'percentage'">
                  {{ stat.value }}%
                </span>
                <span v-else-if="stat.type === 'number'">
                  {{ formatNumber(stat.value) }}
                </span>
                <span v-else>
                  {{ stat.value }}
                </span>
              </p>
            </div>
          </div>
          
          <!-- Trend Indicator -->
          <div v-if="stat.trend" class="mt-2 flex items-center text-sm">
            <svg
              v-if="stat.trend.direction === 'up'"
              class="w-4 h-4 mr-1 text-green-500"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
            </svg>
            <svg
              v-else-if="stat.trend.direction === 'down'"
              class="w-4 h-4 mr-1 text-red-500"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10"></path>
            </svg>
            <svg
              v-else
              class="w-4 h-4 mr-1 text-gray-500"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path>
            </svg>
            <span
              :class="{
                'text-green-600': stat.trend.direction === 'up',
                'text-red-600': stat.trend.direction === 'down',
                'text-gray-600': stat.trend.direction === 'neutral'
              }"
            >
              {{ stat.trend.value }}% {{ stat.trend.label }}
            </span>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center rounded-lg">
        <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  stats: {
    type: Array,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  }
})

const formatCurrency = (value) => {
  return 'Rs ' + new Intl.NumberFormat('en-PK', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value || 0)
}

const formatNumber = (value) => {
  return new Intl.NumberFormat('en-US').format(value || 0)
}

// Default icons as SVG components
const CurrencyIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
  `
}

const ShoppingCartIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5-5M7 13l-2.5 5M17 13v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6m8 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01"></path>
    </svg>
  `
}

const UsersIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
    </svg>
  `
}

const ChartIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4"></path>
    </svg>
  `
}

const CreditCardIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
    </svg>
  `
}

const BriefcaseIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
    </svg>
  `
}

// Map icon names to components
const iconComponents = {
  DollarIcon: CurrencyIcon,
  CurrencyIcon,
  ShoppingCartIcon,
  UsersIcon,
  ChartIcon,
  CreditCardIcon,
  BriefcaseIcon
}

// Process stats to include default values
const processedStats = computed(() => {
  return props.stats.map(stat => ({
    ...stat,
    icon: iconComponents[stat.icon] || ChartIcon,
    borderColor: stat.borderColor || 'border-blue-500',
    iconBg: stat.iconBg || 'bg-blue-100 dark:bg-blue-900',
    iconColor: stat.iconColor || 'text-blue-600 dark:text-blue-400'
  }))
})
</script>
<template>
  <AppLayout>
    <PageHeader 
      title="Dashboard" 
      :subtitle="company?.company_name ? `Welcome to ${company.company_name}` : 'Welcome to your POS system overview'"
      :breadcrumbs="breadcrumbs"
    >
      <template #actions>
        <div class="icon-container icon-btn icon-glass" @click="fetchDashboardData" :class="{ 'icon-loading': loading }">
          <ModernIcon 
            name="refresh" 
            class="icon icon--primary transition-all duration-300" 
            :class="{ 'icon--spin': loading }" 
          />
          <span class="ml-2 text-sm font-medium">{{ loading ? 'Refreshing...' : 'Refresh' }}</span>
        </div>
      </template>
    </PageHeader>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <UiCard v-if="loading" v-for="n in 4" :key="n" class="transform transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
        <div class="flex items-center animate-pulse">
          <div class="flex-shrink-0">
            <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
          </div>
          <div class="ml-4">
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24 mb-2"></div>
            <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-16 mb-1"></div>
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-20"></div>
          </div>
        </div>
      </UiCard>
      
      <UiCard 
        v-else 
        v-for="kpi in (dashboardData.kpis || [])" 
        :key="kpi.title" 
        class="transform transition-all duration-300 hover:-translate-y-1 hover:shadow-xl border-t-4"
        :class="kpi.trend === 'up' ? 'border-t-green-500' : 'border-t-red-500'"
      >
        <div class="flex items-center">
          <div class="flex-shrink-0 p-3 rounded-xl bg-gray-50 dark:bg-gray-700">
            <ModernIcon
              :name="getKpiIconName(kpi.icon)"
              variant="gradient-blue"
              size="lg"
            />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ kpi.title }}</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ kpi.value }}</p>
            <div class="flex items-center text-sm mt-1">
              <span 
                class="flex items-center font-medium"
                :class="kpi.trend === 'up' ? 'text-green-600 bg-green-100 px-2 py-0.5 rounded-full' : 'text-red-600 bg-red-100 px-2 py-0.5 rounded-full'"
              >
                <ModernIcon :name="kpi.trend === 'up' ? 'trending-up' : 'trending-down'" class="w-3 h-3 mr-1" />
                {{ kpi.change }}
              </span>
              <span class="text-gray-400 text-xs ml-2">vs yesterday</span>
            </div>
          </div>
        </div>
      </UiCard>
    </div>

    <!-- Charts and Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <UiCard title="Sales Overview" class="transform transition-all duration-300 hover:shadow-xl">
        <div v-if="loading" class="h-64 flex items-center justify-center bg-gray-50 dark:bg-gray-700 rounded-lg animate-pulse">
          <p class="text-gray-500 dark:text-gray-400">Loading chart...</p>
        </div>
        <div v-else-if="dashboardData.salesChartData" class="h-64">
          <Chart 
            type="line" 
            :data="dashboardData.salesChartData"
            :options="chartOptions"
            :height="256"
          />
        </div>
        <div v-else class="h-64 flex items-center justify-center bg-gray-50 dark:bg-gray-700 rounded-lg">
          <p class="text-gray-500 dark:text-gray-400">No sales data available</p>
        </div>
      </UiCard>
      
      <UiCard title="Recent Transactions" class="h-full flex flex-col">
        <div v-if="loading" class="flex items-center justify-center h-64">
          <p class="text-gray-500 dark:text-gray-400">Loading...</p>
        </div>
        <div v-else-if="error" class="flex items-center justify-center h-64">
          <p class="text-red-500">{{ error }}</p>
        </div>
        <div v-else-if="dashboardData.recentTransactions.length === 0" class="flex items-center justify-center h-64">
          <p class="text-gray-500 dark:text-gray-400">No recent transactions</p>
        </div>
        <div v-else class="space-y-2 overflow-y-auto max-h-[300px] pr-2 custom-scrollbar">
          <div 
            v-for="transaction in dashboardData.recentTransactions" 
            :key="transaction.id" 
            class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200 border border-transparent hover:border-gray-200 dark:hover:border-gray-600 group"
          >
            <div class="flex items-center">
              <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform duration-200">
                <ModernIcon name="shopping-bag" size="sm" />
              </div>
              <div class="ml-3">
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ transaction.customer }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center">
                  <ModernIcon name="clock" class="w-3 h-3 mr-1" />
                  {{ transaction.time }}
                </p>
              </div>
            </div>
            <div class="text-right">
              <p class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ formatCurrency(transaction.amount) }}</p>
              <span 
                class="text-xs px-2 py-0.5 rounded-full font-medium"
                :class="transaction.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
              >
                {{ transaction.status }}
              </span>
            </div>
          </div>
        </div>
        
        <template #footer>
          <div class="icon-container icon-btn icon-btn--gradient w-full rounded-lg p-3 cursor-pointer" @click="handleViewAllTransactions">
            <ModernIcon name="arrow-right" class="icon icon--sm text-white mr-2" />
            <span class="text-white font-medium">View All Transactions</span>
          </div>
        </template>
      </UiCard>
    </div>

    <!-- Low Stock Alert -->
    <UiCard title="Inventory Alerts" class="border-l-4 border-warning-400">
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <ModernIcon name="warning" size="sm" class="text-warning-400" />
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-warning-800 dark:text-warning-200">Low Stock Items</h3>
          <div class="mt-2 text-sm text-warning-700 dark:text-warning-300">
            <p v-if="loading">Loading inventory data...</p>
            <p v-else-if="dashboardData.lowStockCount > 0">
              {{ dashboardData.lowStockCount }} items are running low on stock. Review inventory levels.
            </p>
            <p v-else>All items are well stocked!</p>
          </div>
          <div class="mt-4">
            <UiButton variant="secondary" size="xs" @click="router.visit('/inventory')">Review Inventory</UiButton>
          </div>
        </div>
      </div>
    </UiCard>
  </AppLayout>
</template>

<script>
import { ref, reactive, onMounted, markRaw } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '../layouts/AppLayout.vue'
import PageHeader from '../components/PageHeader.vue'
import UiCard from '../components/UiCard.vue'
import UiButton from '../components/UiButton.vue'
import Chart from '../components/Chart.vue'
import ModernIcon from '../components/ModernIcon.vue'
import { formatCurrency } from '../utils/currency.js'
import { useCompanyStore } from '../stores/company.js'
import axios from 'axios'

// Icon components
const MoneyIcon = {
  template: `<ModernIcon name="registers" />`
}

const ShoppingBagIcon = {
  template: `<ModernIcon name="shopping-bag" />`
}

const UsersIcon = {
  template: `<ModernIcon name="users" />`
}

const TrendingUpIcon = {
  template: `<ModernIcon name="chart" />`
}

const CurrencyIcon = {
  template: `<ModernIcon name="registers" />`
}

export default {
  name: 'Dashboard',
  components: {
    AppLayout,
    PageHeader,
    UiCard,
    UiButton,
    Chart,
    ModernIcon
  },
  setup() {
    const { company } = useCompanyStore()
    const loading = ref(true)
    const error = ref(null)
    
    // Route helper
    const route = window.route
    
    const breadcrumbs = [
      { name: 'Home', href: '/', icon: 'HomeIcon' },
      { name: 'Dashboard' }
    ]
    
    // Icon mapping (mark as raw to prevent reactivity)
    const iconComponents = {
      DollarIcon: markRaw(CurrencyIcon),
      CurrencyIcon: markRaw(CurrencyIcon),
      ShoppingBagIcon: markRaw(ShoppingBagIcon),
      UsersIcon: markRaw(UsersIcon),
      TrendingUpIcon: markRaw(TrendingUpIcon)
    }

    // Map icon names to ModernIcon names
    const getKpiIconName = (iconName) => {
      const iconMap = {
        'DollarIcon': 'dollar',
        'CurrencyIcon': 'dollar',
        'ShoppingBagIcon': 'shopping-bag',
        'UsersIcon': 'users',
        'TrendingUpIcon': 'trending-up'
      }
      return iconMap[iconName] || 'dollar'
    }
    
    // Reactive data
    const dashboardData = reactive({
      kpis: [],
      recentTransactions: [],
      lowStockCount: 0,
      salesData: {},
      salesChartData: null
    })

    // Chart options
    const chartOptions = {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          mode: 'index',
          intersect: false,
          callbacks: {
            label: function(context) {
              return 'Sales: PKR ' + new Intl.NumberFormat().format(context.parsed.y);
            }
          }
        }
      },
      scales: {
        x: {
          grid: {
            display: false
          }
        },
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return 'PKR ' + new Intl.NumberFormat().format(value);
            }
          }
        }
      },
      elements: {
        point: {
          radius: 4,
          hoverRadius: 6
        }
      }
    }
    
    // Fetch dashboard data
    const fetchDashboardData = async () => {
      try {
        loading.value = true
        error.value = null
        
        const response = await axios.get(route('api.dashboard.data'))
        
        if (response.data) {
          // Map icon names to components (mark as raw to prevent reactivity)
          dashboardData.kpis = response.data.kpis.map(kpi => ({
            ...kpi,
            icon: iconComponents[kpi.icon] || markRaw(CurrencyIcon)
          }))
          
          dashboardData.recentTransactions = response.data.recentTransactions
          dashboardData.lowStockCount = response.data.lowStockCount
          dashboardData.salesData = response.data.salesData
          dashboardData.salesChartData = response.data.salesChartData
        }
      } catch (err) {
        console.error('Failed to fetch dashboard data:', err)
        error.value = 'Failed to load dashboard data'
        
        // Fallback to default data (mark icons as raw to prevent reactivity)
        dashboardData.kpis = [
          {
            title: 'Total Sales Today',
            value: 'PKR 0',
            change: '+0%',
            trend: 'up',
            icon: markRaw(CurrencyIcon)
          },
          {
            title: 'Bank Transactions',
            value: 'PKR 0',
            change: '+0%',
            trend: 'up',
            icon: markRaw(CurrencyIcon)
          },
          {
            title: 'Active Customers',
            value: '0',
            change: '+0%',
            trend: 'up',
            icon: markRaw(UsersIcon)
          },
          {
            title: 'Growth Rate',
            value: '0%',
            change: '+0%',
            trend: 'up',
            icon: markRaw(TrendingUpIcon)
          }
        ]
        dashboardData.recentTransactions = []
        dashboardData.lowStockCount = 0
      } finally {
        loading.value = false
      }
    }
    
    // Auto-refresh data every 30 seconds
    let refreshInterval = null
    
    onMounted(() => {
      fetchDashboardData()
      
      // Set up auto-refresh
      refreshInterval = setInterval(fetchDashboardData, 30000) // 30 seconds
    })
    
    // Clean up interval on unmount
    const cleanup = () => {
      if (refreshInterval) {
        clearInterval(refreshInterval)
      }
    }
    
    // Handle View All Transactions click with proper error handling
    const handleViewAllTransactions = () => {
      try {
        // Use Inertia router for SPA navigation
        router.visit('/sales')
      } catch (error) {
        console.error('Failed to navigate to sales:', error)
        // Fallback to direct navigation
        window.location.href = '/sales'
      }
    }
    

    
    return {
      breadcrumbs,
      company,
      loading,
      error,
      dashboardData,
      chartOptions,
      fetchDashboardData,
      cleanup,
      router,
      route,
      formatCurrency,
      handleViewAllTransactions,
      getKpiIconName
    }
  },
  beforeUnmount() {
    this.cleanup()
  }
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
  border-radius: 20px;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #475569;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: #94a3b8;
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: #64748b;
}
</style>
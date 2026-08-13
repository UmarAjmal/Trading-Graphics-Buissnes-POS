<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200" :dir="direction">
    <!-- Sidebar -->
    <div
      class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 shadow-lg transform transition-transform duration-300 ease-in-out flex flex-col print:hidden"
      :class="{
        '-translate-x-full': !sidebarOpen,
        'translate-x-0': sidebarOpen
      }"
    >
      <div class="flex items-center justify-between h-16 px-4 bg-primary-600 shrink-0">
        <div class="flex items-center space-x-3">
          <img v-if="company?.logo_url" 
               :src="company.logo_url" 
               :alt="company.company_name || 'Company Logo'"
               class="w-8 h-8 object-cover rounded-lg"
          />
          <div v-else class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
            <span class="text-white font-bold text-sm">{{ (company?.company_name || 'P').charAt(0) }}</span>
          </div>
          <h1 class="text-xl font-bold text-white">{{ company?.company_name || 'POS System' }}</h1>
        </div>
        
        <!-- Close button -->
        <button
          v-if="!isDesktop"
          @click="closeSidebar"
          class="text-white hover:text-gray-200 transition-colors duration-200 p-1 z-50"
          aria-label="Close sidebar"
        >
          <ModernIcon name="x" size="md" class="text-white" />
        </button>
      </div>
      
      <nav class="mt-5 px-2 space-y-1 flex-1 overflow-y-auto">
        <SidebarItem
          v-for="item in navigation"
          :key="item.name"
          :item="item"
          :current-route="currentRoute"
          @navigate="handleNavigation"
        />
      </nav>
    </div>

    <!-- Mobile sidebar overlay -->
    <div
      v-if="!isDesktop && sidebarOpen"
      class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"
      @click="closeSidebar"
    ></div>

    <!-- Main content -->
    <div class="flex-1 transition-all duration-300" :class="{ 'lg:ml-64': sidebarOpen && isDesktop }">
      <!-- Top navigation -->
      <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 print:hidden">
        <div class="flex items-center justify-between h-16 px-4">
          <div class="flex items-center">
            <!-- Modern Menu toggle button -->
            <button
              @click="sidebarOpen = !sidebarOpen"
              class="p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 transition-all duration-200"
            >
              <ModernIcon 
                :name="(!isDesktop && sidebarOpen) ? 'x' : 'menu'"
                size="md"
                variant="simple"
              />
            </button>
          </div>

          <div class="flex items-center space-x-4">
            <!-- Modern Theme toggle -->
            <button
              @click="toggleTheme"
              class="p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 transition-all duration-200"
              title="Toggle Theme"
            >
              <ModernIcon 
                :name="isDark ? 'moon' : 'sun'"
                size="md"
                variant="simple"
              />
            </button>

            <!-- RTL toggle -->
            <button
              @click="toggleDirection"
              class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
              title="Toggle RTL"
            >
              <span class="text-sm font-medium">RTL</span>
            </button>

            <!-- User menu -->
            <div class="relative">
              <button
                @click="userMenuOpen = !userMenuOpen"
                class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
              >
                <img class="h-8 w-8 rounded-full" src="https://ui-avatars.com/api/?name=User&background=4f46e5&color=fff" alt="User avatar" />
              </button>
              
              <div
                v-if="userMenuOpen"
                @click="userMenuOpen = false"
                class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5"
              >
                <div class="py-1">
                  <div class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-600">
                    <div class="font-medium">{{ $page.props.auth?.user?.name || 'User' }}</div>
                    <div class="text-xs text-gray-500">{{ $page.props.auth?.user?.role || 'Role' }}</div>
                  </div>
                  <Link 
                    :href="route('settings')" 
                    class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                  >
                    Settings
                  </Link>
                  <button
                    @click="logout"
                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                  >
                    Sign out
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="py-4 lg:py-6">
        <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8">
          <slot />
        </div>
      </main>
    </div>

    <!-- Session Warning Component -->
    <SessionWarning
      :show="showSessionWarning"
      :time-remaining="sessionTimeRemaining"
      :message="sessionWarningMessage"
      @dismiss="dismissSessionWarning"
      @extend="handleSessionExtended"
    />
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import SidebarItem from '../components/SidebarItem.vue'
import SessionWarning from '../components/SessionWarning.vue'
import ModernIcon from '../components/ModernIcon.vue'
import { useCompanyStore } from '../stores/company.js'
import { setGlobalCurrency } from '../utils/currency.js'
import { useTheme } from '../stores/theme.js'
import { useSessionManagement } from '../composables/useSessionManagement.js'

export default {
  name: 'AppLayout',
  components: {
    SidebarItem,
    SessionWarning,
    ModernIcon,
    Link
  },
  setup() {
    const { company, loadCompany } = useCompanyStore()
    const { isDark, toggleTheme, initializeTheme } = useTheme()
    const { 
      sessionData, 
      showWarning, 
      timeRemaining, 
      formatTimeRemaining, 
      dismissWarning,
      extendSession
    } = useSessionManagement()
    
    const sidebarOpen = ref(false) // Will be set based on screen size
    const userMenuOpen = ref(false)
    const isDesktop = ref(true) // Default to true, will be updated in onMounted
    
    // Session warning state
    const showSessionWarning = computed(() => showWarning.value)
    const sessionTimeRemaining = computed(() => timeRemaining.value)
    const sessionWarningMessage = computed(() => 
      `Your session will expire in ${formatTimeRemaining.value}. Click to extend.`
    )
    
    // Get current route from Inertia
    const currentRoute = computed(() => {
      if (typeof window !== 'undefined' && window.location) {
        const path = window.location.pathname
        
        // Map paths to route names
        if (path === '/' || path === '/dashboard') return 'dashboard'
        if (path === '/pos') return 'pos'
        if (path === '/products' || path === '/products/') return 'products.index'
        if (path === '/products/create') return 'products.create'
        if (path.startsWith('/products/')) return 'products.index'
        if (path.startsWith('/categories')) return 'categories.index'
        if (path.startsWith('/units')) return 'units.index'
        if (path === '/customers' || path === '/customers/') return 'customers.index'
        if (path === '/customers/create') return 'customers.create'
        if (path.startsWith('/customers/')) return 'customers.index'
        if (path === '/suppliers' || path === '/suppliers/') return 'suppliers.index'
        if (path === '/suppliers/create') return 'suppliers.create'
        if (path.startsWith('/suppliers/')) return 'suppliers.index'
        if (path.startsWith('/purchases')) return 'purchases.index'
        if (path.startsWith('/inventory')) return 'inventory.index'
        if (path === '/sales/create') return 'sales.create'
        if (path.startsWith('/sales')) return 'sales.index'
        if (path.startsWith('/registers')) return 'registers.index'
        if (path.startsWith('/reports/register')) return 'reports.register.index'
        if (path.startsWith('/reports/sales')) return 'reports.sales'
        if (path.startsWith('/reports/purchases')) return 'reports.purchases'
        if (path.startsWith('/reports/profit')) return 'reports.profit'
        if (path.startsWith('/reports/stock')) return 'reports.stock'
        if (path.startsWith('/reports/expenses')) return 'reports.expenses'
        if (path.startsWith('/reports/customers')) return 'reports.customers'
        if (path.startsWith('/reports/receivables')) return 'reports.receivables'
        if (path.startsWith('/reports/suppliers')) return 'reports.suppliers'
        if (path.startsWith('/reports/all-parties-ledger')) return 'reports.all-parties-ledger'
        if (path.startsWith('/reports')) return 'reports.index'
        if (path.startsWith('/expenses')) return 'expenses.index'
        if (path.startsWith('/expense-categories')) return 'expense-categories.index'
        if (path === '/settings') return 'settings'
        if (path === '/payments') return 'payments.index'
      }
      return 'dashboard' // fallback
    })
    
    // Direction management (keep separate from theme store)
    const direction = ref('ltr')
    
    const navigation = [
      { name: 'Dashboard', route: 'dashboard', icon: 'home' },
      // { name: 'Generate Sale', route: 'pos', icon: 'calculator' },
      { name: 'Add Sale', route: 'sales.create', icon: 'plus-circle' },
      { 
        name: 'Products', 
        icon: 'cube',
        children: [
          { name: 'Product List', route: 'products.index', icon: 'list' },
          { name: 'Add New Product', route: 'products.create', icon: 'plus' },
          { name: 'Categories', route: 'categories.index', icon: 'tag' },
          { name: 'Units', route: 'units.index', icon: 'scale' }
        ]
      },
      { 
        name: 'People', 
        icon: 'users',
        children: [
          { name: 'Add Customer', route: 'customers.create', icon: 'user-plus' },
          { name: 'Customer List', route: 'customers.index', icon: 'users' },
          { name: 'Add Supplier', route: 'suppliers.create', icon: 'truck-plus' },
          { name: 'Supplier List', route: 'suppliers.index', icon: 'truck' }
        ]
      },
      {
        name: 'Expenses',
        icon: 'cash',
        children: [
          { name: 'Expense List', route: 'expenses.index', icon: 'list' },
          { name: 'Categories', route: 'expense-categories.index', icon: 'tag' }
        ]
      },
      { name: 'Purchases', route: 'purchases.index', icon: 'shopping-cart' },
      { name: 'Inventory', route: 'inventory.index', icon: 'archive' },
      { name: 'Sale History', route: 'sales.index', icon: 'arrow-uturn-left' },
      { name: 'Payments', route: 'payments.index', icon: 'credit-card' },
      { name: 'Registers', route: 'registers.index', icon: 'cash' },
      { 
        name: 'Reports', 
        icon: 'chart-bar',
        children: [
          { name: 'Sales Report', route: 'reports.sales', icon: 'trending-up' },
          { name: 'Purchase Report', route: 'reports.purchases', icon: 'shopping-cart' },
          { name: 'Profit Report', route: 'reports.profit', icon: 'currency-rupee' },
          { name: 'Stock Report', route: 'reports.stock', icon: 'archive' },
          { name: 'Register Report', route: 'reports.register.index', icon: 'clipboard-document-list' },
          { name: 'Expense Report', route: 'reports.expenses', icon: 'cash' },
          { name: 'Customer Reports', route: 'reports.customers', icon: 'users' },
          { name: 'Receivables Report', route: 'reports.receivables', icon: 'dollar' },
          { name: 'Supplier Reports', route: 'reports.suppliers', icon: 'truck' },
          { name: 'All Parties Ledger', route: 'reports.all-parties-ledger', icon: 'book-open' }
        ]
      },
      { name: 'Settings', route: 'settings', icon: 'cog' }
    ]
    
    const toggleDirection = () => {
      direction.value = direction.value === 'ltr' ? 'rtl' : 'ltr'
      document.documentElement.setAttribute('dir', direction.value)
      localStorage.setItem('direction', direction.value)
    }
    
    const handleResize = () => {
      const wasDesktop = isDesktop.value
      isDesktop.value = window.innerWidth >= 1024
      
      // On mobile, close sidebar by default
      if (!isDesktop.value && wasDesktop) {
        sidebarOpen.value = false
      }
      // On desktop, open sidebar by default
      else if (isDesktop.value && !wasDesktop) {
        sidebarOpen.value = true
      }
    }
    
    onMounted(async () => {
      // Set initial desktop state
      isDesktop.value = window.innerWidth >= 1024
      
      // Set initial sidebar state based on screen size
      sidebarOpen.value = isDesktop.value
      
      // Initialize theme using global theme store
      initializeTheme()
      
      // Load direction preference
      const savedDirection = localStorage.getItem('direction')
      if (savedDirection) {
        direction.value = savedDirection
        document.documentElement.setAttribute('dir', direction.value)
      }
      
      // Load company settings
      await loadCompany()
      
      // Set global currency after company settings are loaded
      if (company.value?.currency) {
        setGlobalCurrency(company.value.currency)
      } else {
        setGlobalCurrency('PKR') // Default to PKR
      }
      
      window.addEventListener('resize', handleResize)
    })
    
    const logout = () => {
      router.post(route('logout'))
    }
    
    const closeSidebar = () => {
      console.log('Closing sidebar...')
      sidebarOpen.value = false
    }
    
    const handleNavigation = () => {
      // Close mobile sidebar when navigating
      if (!isDesktop.value) {
        closeSidebar()
      }
    }
    
    const dismissSessionWarning = () => {
      dismissWarning()
    }
    
    const handleSessionExtended = () => {
      // Session was extended successfully
      console.log('Session extended from AppLayout')
    }
    
    return {
      sidebarOpen,
      userMenuOpen,
      isDesktop,
      currentRoute,
      isDark,
      direction,
      navigation,
      company,
      toggleTheme,
      toggleDirection,
      logout,
      closeSidebar,
      handleNavigation,
      // Session management
      showSessionWarning,
      sessionTimeRemaining,
      sessionWarningMessage,
      dismissSessionWarning,
      handleSessionExtended
    }
  }
}
</script>
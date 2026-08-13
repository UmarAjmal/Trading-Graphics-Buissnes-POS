<template>
  <div class="mb-1">
    <!-- Modern Main Item -->
    <div
      @click="handleClick"
      :class="[
        'modern-sidebar-item group cursor-pointer',
        {
          'modern-sidebar-item--active': active || isExpanded,
          'hover:bg-gray-50 dark:hover:bg-gray-800/50': !active && !isExpanded
        }
      ]"
    >
      <ModernIcon
        :name="getIconName(item.icon)"
        size="sm"
        class="modern-sidebar-item__icon flex-shrink-0"
      />
      
      <span class="flex-1 text-left">{{ item.name }}</span>
      
      <!-- Modern Expand Arrow -->
      <ModernIcon
        v-if="item.children && item.children.length > 0"
        name="chevron"
        size="xs"
        :class="[
          'transition-transform duration-200 text-gray-400',
          { 'rotate-90': isExpanded }
        ]"
      />
    </div>
    
    <!-- Modern Submenu -->
    <div 
      v-if="item.children && item.children.length > 0" 
      class="overflow-hidden transition-all duration-300 ease-out"
      :class="isExpanded ? 'max-h-[800px] opacity-100' : 'max-h-0 opacity-0'"
    >
      <div class="ml-6 space-y-1 py-2">
        <div
          v-for="child in item.children"
          :key="child.name"
          @click="handleChildClick(child)"
          :class="[
            'modern-sidebar-item cursor-pointer text-sm',
            {
              'modern-sidebar-item--active': currentRoute === child.route,
              'text-gray-500 hover:text-gray-700 dark:text-gray-400': currentRoute !== child.route
            }
          ]"
        >
          <div class="w-3 h-3 rounded-full bg-gray-300 dark:bg-gray-600" :class="{ 'bg-primary-500': currentRoute === child.route }"></div>
          <span>{{ child.name }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, inject } from 'vue'
import { router } from '@inertiajs/vue3'
import ModernIcon from './ModernIcon.vue'

const props = defineProps({
  item: {
    type: Object,
    required: true
  },
  currentRoute: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['navigate'])

// Icon components (simplified inline SVGs)
const HomeIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
    </svg>
  `
}

const CalculatorIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
    </svg>
  `
}

const CubeIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
    </svg>
  `
}

const UsersIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a4 4 0 11-8 0 4 4 0 018 0z" />
    </svg>
  `
}

const TruckIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
    </svg>
  `
}

const ShoppingCartIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.293 2.293A1 1 0 005 16v0a1 1 0 001 1h11M9 19a2 2 0 11-4 0 2 2 0 014 0zM20 19a2 2 0 11-4 0 2 2 0 014 0z" />
    </svg>
  `
}

const ArchiveIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
    </svg>
  `
}

const CashIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2-4h10a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6z" />
    </svg>
  `
}

const ChartBarIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
    </svg>
  `
}

const CogIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>
  `
}

const BuildingOfficeIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
    </svg>
  `
}

const iconMap = {
  'home': HomeIcon,
  'calculator': CalculatorIcon,
  'cube': CubeIcon,
  'users': UsersIcon,
  'truck': TruckIcon,
  'shopping-cart': ShoppingCartIcon,
  'archive': ArchiveIcon,
  'cash': CashIcon,
  'chart-bar': ChartBarIcon,
  'cog': CogIcon,
  'building-office': BuildingOfficeIcon,
}

// Add new icons for child items
const ListIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
    </svg>
  `
}

const PlusIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M12 4v16m8-8H4" />
    </svg>
  `
}

const TagIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
    </svg>
  `
}

const ScaleIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
    </svg>
  `
}

const childIconMap = {
  'list': ListIcon,
  'plus': PlusIcon,
  'tag': TagIcon,
  'scale': ScaleIcon,
  ...iconMap
}

const isExpanded = ref(false)
const currentRoute = inject('currentRoute', '')

const iconComponent = computed(() => {
  return iconMap[props.item.icon] || HomeIcon
})

const getChildIcon = (iconName) => {
  return childIconMap[iconName] || ListIcon
}

// Check if any child is active to keep parent expanded
const hasActiveChild = computed(() => {
  if (!props.item.children) return false
  return props.item.children.some(child => currentRoute.value === child.route)
})


// Auto-expand if has active child
if (hasActiveChild.value) {
  isExpanded.value = true
}

const routeMap = {
  'company.settings': '/settings/company',
  'products.index': '/products',
  'products.create': '/products/create',
  'categories.index': '/categories',
  'units.index': '/units',
  'customers.index': '/customers',
  'customers.create': '/customers/create',
  'suppliers.index': '/suppliers',
  'suppliers.create': '/suppliers/create',
  'purchases.index': '/purchases',
  'inventory.index': '/inventory',
  'registers.index': '/registers',
  'sales.index': '/sales',
  'sales.create': '/sales/create',
  'payments.index': '/payments',
  'reports.index': '/reports',
  'reports.sales': '/reports/sales',
  'reports.purchases': '/reports/purchases',
  'reports.profit': '/reports/profit',
  'reports.stock': '/reports/stock',
  'reports.expenses': '/reports/expenses',
  'reports.customers': '/reports/customers',
  'reports.receivables': '/reports/receivables',
  'reports.suppliers': '/reports/suppliers',
  'reports.all-parties-ledger': '/reports/all-parties-ledger',
  'reports.register.index': '/reports/register',
  'expenses.index': '/expenses',
  'expense-categories.index': '/expense-categories',
  'dashboard': '/dashboard',
  'pos': '/pos',
  'settings': '/settings'
}

const navigateToRoute = (route) => {
  try {
    emit('navigate')
    const url = routeMap[route] || `/${route}`
    console.log('Navigating to:', url)
        
        router.visit(url, {
          method: 'get',
          preserveState: false,
          preserveScroll: false,
          replace: false,
          onError: (errors) => {
            console.error('Navigation errors:', errors)
            window.location.href = url
          }
        })
      } catch (error) {
        console.error('Navigation error:', error)
        const url = routeMap[route] || `/${route}`
        window.location.href = url
      }
    }
    
    const handleClick = () => {
      // If item has children, toggle expansion
      if (props.item.children && props.item.children.length > 0) {
        isExpanded.value = !isExpanded.value
      }
      // If item has a route and no children, navigate
      else if (props.item.route) {
        navigateToRoute(props.item.route)
      }
    }
    
    const handleChildClick = (child) => {
      if (child.route) {
        navigateToRoute(child.route)
      }
    }
    
    // Map icon names for ModernIcon component
const getIconName = (iconName) => {
  // Direct mapping for icon names from AppLayout
  const iconMap = {
    // Main navigation icons
    'home': 'home',
    'calculator': 'pos',
    'cube': 'products',
    'users': 'users',
    'shopping-cart': 'shopping',
    'archive': 'inventory',
    'cash': 'dollar',
    'chart-bar': 'reports',
    'cog': 'settings',
    
    // Legacy component names (keep for compatibility)
    'HomeIcon': 'home',
    'CalculatorIcon': 'pos',
    'CubeIcon': 'products',
    'UsersIcon': 'users',
    'ShoppingBagIcon': 'shopping',
    'PackageIcon': 'inventory',
    'CashIcon': 'dollar',
    'ChartBarIcon': 'reports',
    'CogIcon': 'settings'
  }
  
  // Return the mapped icon name, or the original name if it exists in ModernIcon, or default to 'home'
  return iconMap[iconName] || iconName || 'home'
}
</script>
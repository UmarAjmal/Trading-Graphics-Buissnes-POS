<template>
  <AppLayout>
    <PageHeader
      title="System Settings"
      subtitle="Configure application preferences and system options"
    />

    <!-- Settings Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- Company Settings -->
      <UiCard class="hover:shadow-md transition-shadow">
        <div class="p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ModernIcon 
                name="building" 
                size="lg" 
                variant="gradient-blue"
                class="w-12 h-12"
              />
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                Company Settings
              </h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Business information and branding
              </p>
            </div>
          </div>
          <div class="mt-4">
            <UiButton 
              @click="$inertia.visit(route('company.settings'))" 
              variant="primary" 
              size="sm"
              class="w-full"
            >
              Configure
            </UiButton>
          </div>
        </div>
      </UiCard>

      <!-- User Management -->
      <UiCard class="hover:shadow-md transition-shadow">
        <div class="p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ModernIcon 
                name="users" 
                size="lg" 
                variant="gradient-blue"
                class="w-12 h-12"
              />
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                User Management
              </h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Manage users and permissions
              </p>
            </div>
          </div>
          <div class="mt-4">
            <UiButton 
              @click="$inertia.get(route('users.index'))" 
              variant="primary" 
              size="sm"
              class="w-full"
            >
              Configure
            </UiButton>
          </div>
        </div>
      </UiCard>

      <!-- Tax Settings -->
      <UiCard class="hover:shadow-md transition-shadow">
        <div class="p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ModernIcon 
                name="calculator" 
                size="lg" 
                variant="gradient-green"
                class="w-12 h-12"
              />
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                Tax Configuration
              </h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Setup tax rates and rules
              </p>
            </div>
          </div>
          <div class="mt-4">
            <UiButton 
              @click="$inertia.get(route('settings.tax'))" 
              variant="primary" 
              size="sm"
              class="w-full"
            >
              Configure
            </UiButton>
          </div>
        </div>
      </UiCard>

      <!-- Backup & Restore -->
      <UiCard class="hover:shadow-md transition-shadow">
        <div class="p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ModernIcon 
                name="backup" 
                size="lg" 
                variant="gradient-amber"
                class="w-12 h-12"
              />
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                Backup & Restore
              </h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Data backup and recovery
              </p>
            </div>
          </div>
          <div class="mt-4">
            <UiButton 
              @click="$inertia.get(route('settings.backup'))" 
              variant="primary" 
              size="sm"
              class="w-full"
            >
              Configure
            </UiButton>
          </div>
        </div>
      </UiCard>

      <!-- System Information -->
      <UiCard class="hover:shadow-md transition-shadow">
        <div class="p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ModernIcon 
                name="info" 
                size="lg" 
                variant="gradient-blue"
                class="w-12 h-12"
              />
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                System Information
              </h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                View system details
              </p>
            </div>
          </div>
          <div class="mt-4">
            <UiButton 
              @click="$inertia.get(route('settings.system-info'))" 
              variant="primary" 
              size="sm"
              class="w-full"
            >
              View Details
            </UiButton>
          </div>
        </div>
      </UiCard>

      <!-- Database Cleanup -->
      <UiCard class="hover:shadow-md transition-shadow">
        <div class="p-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ModernIcon 
                name="trash" 
                size="lg" 
                variant="gradient-red"
                class="w-12 h-12"
              />
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                Database Cleanup
              </h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                Clear database records safely
              </p>
            </div>
          </div>
          <div class="mt-4">
            <UiButton 
              @click="showDatabaseCleanup = true" 
              variant="danger" 
              size="sm"
              class="w-full"
            >
              Manage Data
            </UiButton>
          </div>
        </div>
      </UiCard>
    </div>

    <!-- Database Cleanup Modal -->
    <UiModal v-model="showDatabaseCleanup" title="Database Cleanup" size="lg">
      <div class="space-y-4">
        <div class="bg-red-50 border border-red-200 rounded-md p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">
                Warning: Destructive Operations
              </h3>
              <p class="mt-2 text-sm text-red-700">
                These operations will permanently delete data from your database. This action cannot be undone. 
                Please ensure you have a backup before proceeding.
              </p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="option in cleanupOptions"
            :key="option.id"
            class="border rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            :class="option.danger ? 'border-red-300 bg-red-50 dark:bg-red-900/20' : 'border-gray-200 dark:border-gray-700'"
          >
            <h4 class="font-medium text-gray-900 dark:text-white">
              {{ option.label }}
            </h4>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
              {{ option.description }}
            </p>
            <div class="mt-3">
              <UiButton
                :variant="option.danger ? 'danger' : 'secondary'"
                size="sm"
                :loading="processing"
                @click="cleanupDatabase(option.id)"
                class="w-full"
              >
                {{ option.danger ? 'Clear All' : 'Clear' }}
              </UiButton>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end space-x-3">
          <UiButton variant="secondary" @click="showDatabaseCleanup = false" :disabled="processing">
            Cancel
          </UiButton>
        </div>
      </template>
    </UiModal>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiCard from '@/components/UiCard.vue'
import UiButton from '@/components/UiButton.vue'
import UiModal from '@/components/UiModal.vue'
import ModernIcon from '@/components/ModernIcon.vue'

const showDatabaseCleanup = ref(false)
const processing = ref(false)

const cleanupOptions = [
  { id: 'all', label: 'Clear All Data', description: 'Remove all data from database', danger: true },
  { id: 'customers', label: 'Clear Customers', description: 'Remove all customer records', danger: false },
  { id: 'suppliers', label: 'Clear Suppliers', description: 'Remove all supplier records', danger: false },
  { id: 'products', label: 'Clear Products', description: 'Remove all product records', danger: false },
  { id: 'sales', label: 'Clear Sales', description: 'Remove all sales records', danger: false },
  { id: 'purchases', label: 'Clear Purchases', description: 'Remove all purchase records', danger: false },
  { id: 'categories', label: 'Clear Categories', description: 'Remove all category records', danger: false },
  { id: 'units', label: 'Clear Units', description: 'Remove all unit records', danger: false }
]

const cleanupDatabase = async (type) => {
  if (!confirm(`Are you sure you want to clear ${type === 'all' ? 'ALL DATA' : type}? This action cannot be undone!`)) {
    return
  }
  
  processing.value = true
  
  try {
    await router.post(route(`settings.database.clear-${type}`), {}, {
      onSuccess: () => {
        alert(`Successfully cleared ${type === 'all' ? 'all data' : type}`)
        showDatabaseCleanup.value = false
      },
      onError: (errors) => {
        console.error('Cleanup error:', errors)
        alert('An error occurred during cleanup')
      }
    })
  } catch (error) {
    console.error('Cleanup error:', error)
    alert('An error occurred during cleanup')
  } finally {
    processing.value = false
  }
}
</script>
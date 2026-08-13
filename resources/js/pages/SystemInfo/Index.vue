<template>
  <AppLayout>
    <PageHeader
      title="System Information"
      subtitle="View system details, version information, and performance metrics"
    />

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Application Information -->
      <UiCard>
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
              Application Information
            </h3>
            <div class="flex items-center gap-2">
              <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                {{ systemInfo.app?.status || 'Running' }}
              </span>
            </div>
          </div>

          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Application Name</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ systemInfo.app?.name || 'POS System' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Version</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ systemInfo.app?.version || '1.0.0' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Environment</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                  <span 
                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                    :class="getEnvironmentBadgeClass(systemInfo.app?.environment)"
                  >
                    {{ systemInfo.app?.environment || 'local' }}
                  </span>
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Debug Mode</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                  {{ systemInfo.app?.debug ? 'Enabled' : 'Disabled' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Timezone</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ systemInfo.app?.timezone || 'UTC' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Locale</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ systemInfo.app?.locale || 'en' }}</dd>
              </div>
            </div>
          </div>
        </div>
      </UiCard>

      <!-- Server Information -->
      <UiCard>
        <div class="p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">
            Server Information
          </h3>

          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Server Software</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ systemInfo.server?.software || 'Unknown' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">PHP Version</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ systemInfo.server?.php_version || 'Unknown' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Operating System</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ systemInfo.server?.os || 'Unknown' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Server Name</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ systemInfo.server?.name || 'localhost' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Document Root</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white text-xs truncate">{{ systemInfo.server?.document_root || '/var/www/html' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Server IP</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ systemInfo.server?.server_ip || '127.0.0.1' }}</dd>
              </div>
            </div>
          </div>
        </div>
      </UiCard>

      <!-- Database Information -->
      <UiCard>
        <div class="p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">
            Database Information
          </h3>

          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Database Type</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ systemInfo.database?.type || 'SQLite' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Database Version</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ systemInfo.database?.version || 'Unknown' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Database Name</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ systemInfo.database?.name || 'database.sqlite' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Database Size</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatFileSize(systemInfo.database?.size) || 'Unknown' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Tables</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ systemInfo.database?.tables || 0 }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Connection Status</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                  <span 
                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                    :class="systemInfo.database?.connected ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'"
                  >
                    {{ systemInfo.database?.connected ? 'Connected' : 'Disconnected' }}
                  </span>
                </dd>
              </div>
            </div>
          </div>
        </div>
      </UiCard>

      <!-- Laravel Framework -->
      <UiCard>
        <div class="p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">
            Laravel Framework
          </h3>

          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Laravel Version</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ systemInfo.laravel?.version || '11.x' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">PHP Version</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ systemInfo.laravel?.php_version || '8.2+' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Composer Version</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ systemInfo.laravel?.composer_version || '2.x' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cache Driver</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ systemInfo.laravel?.cache_driver || 'file' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Session Driver</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ systemInfo.laravel?.session_driver || 'file' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Queue Driver</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ systemInfo.laravel?.queue_driver || 'sync' }}</dd>
              </div>
            </div>
          </div>
        </div>
      </UiCard>

      <!-- Performance Metrics -->
      <UiCard class="lg:col-span-2">
        <div class="p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">
            Performance Metrics
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Memory Usage -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
              <div class="flex items-center">
                <ModernIcon 
                  name="cpu" 
                  size="lg" 
                  variant="gradient-blue"
                  class="w-12 h-12"
                />
                <div class="ml-3">
                  <p class="text-sm font-medium text-blue-900 dark:text-blue-200">Memory Usage</p>
                  <p class="text-lg font-semibold text-blue-900 dark:text-blue-200">
                    {{ formatFileSize(systemInfo.performance?.memory_usage) || '0 MB' }}
                  </p>
                  <p class="text-xs text-blue-800 dark:text-blue-300">
                    Limit: {{ formatFileSize(systemInfo.performance?.memory_limit) || '128 MB' }}
                  </p>
                </div>
              </div>
            </div>

            <!-- PHP Extensions -->
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
              <div class="flex items-center">
                <ModernIcon 
                  name="check" 
                  size="lg" 
                  variant="gradient-green"
                  class="w-12 h-12"
                />
                <div class="ml-3">
                  <p class="text-sm font-medium text-green-900 dark:text-green-200">PHP Extensions</p>
                  <p class="text-lg font-semibold text-green-900 dark:text-green-200">
                    {{ systemInfo.performance?.php_extensions || 0 }}
                  </p>
                  <p class="text-xs text-green-800 dark:text-green-300">Loaded</p>
                </div>
              </div>
            </div>

            <!-- Disk Space -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
              <div class="flex items-center">
                <ModernIcon 
                  name="database" 
                  size="lg" 
                  variant="gradient-amber"
                  class="w-12 h-12"
                />
                <div class="ml-3">
                  <p class="text-sm font-medium text-yellow-900 dark:text-yellow-200">Disk Usage</p>
                  <p class="text-lg font-semibold text-yellow-900 dark:text-yellow-200">
                    {{ formatFileSize(systemInfo.performance?.disk_usage) || '0 GB' }}
                  </p>
                  <p class="text-xs text-yellow-800 dark:text-yellow-300">
                    Free: {{ formatFileSize(systemInfo.performance?.disk_free) || '0 GB' }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Uptime -->
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
              <div class="flex items-center">
                <ModernIcon 
                  name="server" 
                  size="lg" 
                  variant="gradient-blue"
                  class="w-12 h-12"
                />
                <div class="ml-3">
                  <p class="text-sm font-medium text-purple-900 dark:text-purple-200">Server Uptime</p>
                  <p class="text-lg font-semibold text-purple-900 dark:text-purple-200">
                    {{ formatUptime(systemInfo.performance?.uptime) || '0 days' }}
                  </p>
                  <p class="text-xs text-purple-800 dark:text-purple-300">Running</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </UiCard>

      <!-- System Health Check -->
      <UiCard class="lg:col-span-2">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
              System Health Check
            </h3>
            <UiButton
              @click="runHealthCheck"
              variant="outline"
              :loading="healthCheckLoading"
            >
              <ModernIcon 
                name="refresh" 
                size="sm" 
                class="mr-2"
              />
              Run Check
            </UiButton>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="check in healthChecks"
              :key="check.name"
              class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg"
            >
              <div class="flex items-center">
                <div 
                  class="w-3 h-3 rounded-full mr-3"
                  :class="getHealthStatusColor(check.status)"
                ></div>
                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ check.name }}</span>
              </div>
              <span 
                class="text-xs px-2 py-1 rounded-full font-medium"
                :class="getHealthStatusBadge(check.status)"
              >
                {{ check.status }}
              </span>
            </div>
          </div>

          <div v-if="healthCheckError" class="mt-4 bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
            <div class="flex">
              <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
              </svg>
              <div class="ml-3">
                <h4 class="text-sm font-medium text-red-800 dark:text-red-200">Health Check Error</h4>
                <p class="text-sm text-red-700 dark:text-red-300 mt-1">{{ healthCheckError }}</p>
              </div>
            </div>
          </div>
        </div>
      </UiCard>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiCard from '@/components/UiCard.vue'
import UiButton from '@/components/UiButton.vue'
import ModernIcon from '@/components/ModernIcon.vue'

// Props
const props = defineProps({
  systemInfo: {
    type: Object,
    default: () => ({})
  },
  healthChecks: {
    type: Array,
    default: () => [
      { name: 'Database Connection', status: 'healthy' },
      { name: 'File Permissions', status: 'healthy' },
      { name: 'PHP Extensions', status: 'healthy' },
      { name: 'Storage Directory', status: 'healthy' },
      { name: 'Memory Limit', status: 'healthy' },
      { name: 'Environment Config', status: 'healthy' }
    ]
  }
})

// State
const healthCheckLoading = ref(false)
const healthCheckError = ref(null)

// Methods
const getEnvironmentBadgeClass = (environment) => {
  const classes = {
    production: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    staging: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    local: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    development: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
  }
  return classes[environment] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
}

const getHealthStatusColor = (status) => {
  const colors = {
    healthy: 'bg-green-500',
    warning: 'bg-yellow-500',
    error: 'bg-red-500',
    unknown: 'bg-gray-500'
  }
  return colors[status] || 'bg-gray-500'
}

const getHealthStatusBadge = (status) => {
  const classes = {
    healthy: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    error: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    unknown: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
  }
  return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 Bytes'
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}

const formatUptime = (seconds) => {
  if (!seconds) return '0 days'
  const days = Math.floor(seconds / 86400)
  const hours = Math.floor((seconds % 86400) / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  return `${days}d ${hours}h ${minutes}m`
}

const runHealthCheck = async () => {
  healthCheckLoading.value = true
  healthCheckError.value = null
  
  try {
    router.reload({ only: ['healthChecks'] })
  } catch (error) {
    healthCheckError.value = 'Failed to run health check. Please try again.'
  } finally {
    healthCheckLoading.value = false
  }
}
</script>
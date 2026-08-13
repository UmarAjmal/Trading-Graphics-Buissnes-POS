<template>
  <AppLayout>
    <PageHeader
      title="Backup & Restore"
      subtitle="Backup your data and restore from previous backups"
    />

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
      <!-- Database Backup -->
      <UiCard>
        <div class="p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">
            Database Backup
          </h3>

          <div class="space-y-4">
            <!-- Quick Backup -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="font-medium text-blue-900 dark:text-blue-200">Quick Backup</h4>
                  <p class="text-sm text-blue-800 dark:text-blue-300 mt-1">
                    Download complete database backup
                  </p>
                </div>
                <UiButton
                  @click="createBackup"
                  variant="primary"
                  :loading="backupForm.processing"
                >
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                  </svg>
                  Create Backup
                </UiButton>
                
                <UiButton
                  @click="emptyDatabase"
                  variant="danger"
                  class="ml-3"
                >
                  Empty Database
                </UiButton>
              </div>
            </div>

            <!-- Backup Options -->
            <div class="space-y-3">
              <h4 class="font-medium text-gray-900 dark:text-white">Backup Options</h4>
              
              <div class="space-y-2">
                <label class="flex items-center">
                  <input
                    v-model="backupOptions.includeSales"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  >
                  <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Include Sales Data</span>
                </label>
                
                <label class="flex items-center">
                  <input
                    v-model="backupOptions.includeProducts"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  >
                  <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Include Products</span>
                </label>
                
                <label class="flex items-center">
                  <input
                    v-model="backupOptions.includeCustomers"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  >
                  <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Include Customers</span>
                </label>
                
                <label class="flex items-center">
                  <input
                    v-model="backupOptions.includeSettings"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  >
                  <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Include Settings</span>
                </label>
              </div>
            </div>

            <!-- Database Statistics -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
              <h4 class="font-medium text-gray-900 dark:text-white mb-3">Database Statistics</h4>
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <span class="text-gray-500 dark:text-gray-400">Sales Records:</span>
                  <span class="font-medium ml-2">{{ stats.salesCount || 0 }}</span>
                </div>
                <div>
                  <span class="text-gray-500 dark:text-gray-400">Products:</span>
                  <span class="font-medium ml-2">{{ stats.productsCount || 0 }}</span>
                </div>
                <div>
                  <span class="text-gray-500 dark:text-gray-400">Customers:</span>
                  <span class="font-medium ml-2">{{ stats.customersCount || 0 }}</span>
                </div>
                <div>
                  <span class="text-gray-500 dark:text-gray-400">Users:</span>
                  <span class="font-medium ml-2">{{ stats.usersCount || 0 }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </UiCard>

      <!-- Database Restore -->
      <UiCard>
        <div class="p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">
            Database Restore
          </h3>

          <div class="space-y-4">
            <!-- File Upload -->
            <div>
              <UiLabel>Select Backup File</UiLabel>
              <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md dark:border-gray-600">
                <div class="space-y-1 text-center">
                  <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <div class="flex text-sm text-gray-600 dark:text-gray-400">
                    <label for="backup-file" class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                      <span>Upload a backup file</span>
                      <input
                        id="backup-file"
                        ref="fileInput"
                        @change="handleFileSelect"
                        type="file"
                        accept=".sql,.zip,.gz"
                        class="sr-only"
                      >
                    </label>
                    <p class="pl-1">or drag and drop</p>
                  </div>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    SQL, ZIP, or GZ files up to 100MB
                  </p>
                </div>
              </div>
              
              <div v-if="selectedFile" class="mt-2 p-2 bg-gray-50 dark:bg-gray-800 rounded border">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-700 dark:text-gray-300">{{ selectedFile.name }}</span>
                  <button @click="selectedFile = null" class="text-red-500 hover:text-red-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Restore Options -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
              <div class="flex">
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div class="ml-3">
                  <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                    Warning: Data Restore
                  </h4>
                  <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                    Restoring will overwrite all existing data. Make sure to create a backup first.
                  </p>
                </div>
              </div>
            </div>

            <div class="space-y-2">
              <label class="flex items-center">
                <input
                  v-model="restoreOptions.confirmOverwrite"
                  type="checkbox"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                >
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                  I understand this will overwrite existing data
                </span>
              </label>
            </div>

            <UiButton
              @click="restoreBackup"
              variant="danger"
              :disabled="!selectedFile || !restoreOptions.confirmOverwrite"
              :loading="restoreForm.processing"
              class="w-full"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
              </svg>
              Restore Database
            </UiButton>
          </div>
        </div>
      </UiCard>

      <!-- Backup History -->
      <UiCard class="lg:col-span-2">
        <div class="p-4 lg:p-6">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
              Recent Backups
            </h3>
            <UiButton
              @click="refreshBackupHistory"
              variant="outline"
              size="sm"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
              </svg>
              Refresh
            </UiButton>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Backup Date
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Size
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Type
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Records
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="backup in backupHistory" :key="backup.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    {{ formatDate(backup.created_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                    {{ formatFileSize(backup.size) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                    {{ backup.type }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                    {{ backup.records_count || 'N/A' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex items-center justify-end gap-2">
                      <UiButton
                        @click="downloadBackup(backup)"
                        variant="outline"
                        size="sm"
                      >
                        Download
                      </UiButton>
                      <UiButton
                        @click="deleteBackup(backup)"
                        variant="danger"
                        size="sm"
                      >
                        Delete
                      </UiButton>
                    </div>
                  </td>
                </tr>
                <tr v-if="backupHistory.length === 0">
                  <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
                    No backup history available
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </UiCard>

      <!-- Automated Backup Settings -->
      <UiCard class="lg:col-span-2">
        <div class="p-4 lg:p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">
            Automated Backup Settings
          </h3>

          <form @submit.prevent="saveAutomatedSettings" class="grid grid-cols-1 gap-4 lg:gap-6">
            <div>
              <div class="flex items-center justify-between mb-4">
                <UiLabel>Enable Automated Backups</UiLabel>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input
                    v-model="automatedSettings.enabled"
                    type="checkbox"
                    class="sr-only peer"
                  >
                  <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                </label>
              </div>

              <div v-if="automatedSettings.enabled" class="space-y-4">
                <div>
                  <UiLabel for="backup_frequency">Backup Frequency</UiLabel>
                  <select
                    id="backup_frequency"
                    v-model="automatedSettings.frequency"
                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  >
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                  </select>
                </div>

                <div>
                  <UiLabel for="backup_time">Backup Time</UiLabel>
                  <UiInput
                    id="backup_time"
                    v-model="automatedSettings.time"
                    type="time"
                  />
                </div>

                <div>
                  <UiLabel for="retention_days">Keep Backups (days)</UiLabel>
                  <UiInput
                    id="retention_days"
                    v-model.number="automatedSettings.retention_days"
                    type="number"
                    min="1"
                    max="365"
                  />
                </div>
              </div>
            </div>

            <div v-if="automatedSettings.enabled">
              <h4 class="font-medium text-gray-900 dark:text-white mb-3">Next Scheduled Backup</h4>
              <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                <div class="flex items-center">
                  <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-green-900 dark:text-green-200">
                      {{ nextBackupDate }}
                    </p>
                    <p class="text-xs text-green-800 dark:text-green-300">
                      {{ automatedSettings.frequency }} at {{ automatedSettings.time }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div class="md:col-span-2">
              <UiButton
                type="submit"
                variant="primary"
                :loading="automatedForm.processing"
              >
                Save Automated Settings
              </UiButton>
            </div>
          </form>
        </div>
      </UiCard>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiCard from '@/components/UiCard.vue'
import UiButton from '@/components/UiButton.vue'
import UiInput from '@/components/UiInput.vue'
import UiLabel from '@/components/UiLabel.vue'

// Props
const props = defineProps({
  stats: {
    type: Object,
    default: () => ({})
  },
  backupHistory: {
    type: Array,
    default: () => []
  },
  automatedConfig: {
    type: Object,
    default: () => ({
      enabled: false,
      frequency: 'daily',
      time: '02:00',
      retention_days: 30
    })
  }
})

// State
const selectedFile = ref(null)
const fileInput = ref(null)

const backupOptions = reactive({
  includeSales: true,
  includeProducts: true,
  includeCustomers: true,
  includeSettings: true
})

const restoreOptions = reactive({
  confirmOverwrite: false
})

const automatedSettings = reactive({
  ...props.automatedConfig
})

// Forms
const backupForm = useForm({
  options: backupOptions
})

const restoreForm = useForm({
  file: null,
  options: restoreOptions
})

const automatedForm = useForm({
  settings: automatedSettings
})

// Computed
const nextBackupDate = computed(() => {
  if (!automatedSettings.enabled) return 'Not scheduled'
  
  const now = new Date()
  const [hours, minutes] = automatedSettings.time.split(':')
  
  let nextDate = new Date()
  nextDate.setHours(parseInt(hours), parseInt(minutes), 0, 0)
  
  if (nextDate <= now) {
    if (automatedSettings.frequency === 'daily') {
      nextDate.setDate(nextDate.getDate() + 1)
    } else if (automatedSettings.frequency === 'weekly') {
      nextDate.setDate(nextDate.getDate() + 7)
    } else if (automatedSettings.frequency === 'monthly') {
      nextDate.setMonth(nextDate.getMonth() + 1)
    }
  }
  
  return nextDate.toLocaleString()
})

// Methods
const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    selectedFile.value = file
  }
}

const createBackup = () => {
  backupForm.options = backupOptions
  backupForm.post(route('settings.backup.create'))
}

const emptyDatabase = () => {
  if (!confirm('This will permanently delete selected data (sales, purchases, products, customers, suppliers, stock records).\nAre you SURE you want to continue?')) return

  // post to empty endpoint
  const form = new FormData()
  form.append('confirm', '1')

  fetch(route('settings.backup.empty'), {
    method: 'POST',
    body: form,
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    }
  }).then(res => {
    if (res.redirected) {
      window.location = res.url
    } else {
      return res.text()
    }
  }).catch(err => {
    console.error('Failed to empty database:', err)
    alert('Failed to empty database. Check server logs for details.')
  })
}

const restoreBackup = () => {
  if (!selectedFile.value) return
  
  restoreForm.file = selectedFile.value
  restoreForm.options = restoreOptions
  restoreForm.post(route('settings.backup.restore'), {
    onSuccess: () => {
      selectedFile.value = null
      restoreOptions.confirmOverwrite = false
    }
  })
}

const downloadBackup = (backup) => {
  window.open(route('settings.backup.download', backup.id), '_blank')
}

const deleteBackup = (backup) => {
  if (confirm('Are you sure you want to delete this backup?')) {
    router.delete(route('settings.backup.delete', backup.id))
  }
}

const refreshBackupHistory = () => {
  router.reload({ only: ['backupHistory'] })
}

const saveAutomatedSettings = () => {
  automatedForm.settings = automatedSettings
  automatedForm.post(route('settings.backup.automated.update'))
}

const formatDate = (date) => {
  return new Date(date).toLocaleString()
}

const formatFileSize = (bytes) => {
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  if (bytes === 0) return '0 Bytes'
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}
</script>
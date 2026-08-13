<template>
  <AppLayout>
    <template #header>
      <PageHeader title="Roles & Permissions" subtitle="Manage user roles and system permissions">
        <template #actions>
          <UiButton @click="$inertia.get(route('roles.create'))" variant="primary">
            <ModernIcon name="user-plus" class="w-4 h-4 mr-2" />
            Create Role
          </UiButton>
        </template>
      </PageHeader>
    </template>

    <div class="space-y-6">
      <!-- Roles Section -->
      <UiCard>
        <template #header>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">System Roles</h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">Manage user roles and their associated permissions</p>
        </template>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Role
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Description
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Permissions
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Users
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="role in roles" :key="role.id">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-8 w-8">
                      <div class="h-8 w-8 rounded-full flex items-center justify-center" :class="getRoleColor(role.slug)">
                        <span class="text-xs font-medium text-white">{{ role.name.charAt(0) }}</span>
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ role.name }}
                      </div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ role.slug }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900 dark:text-white">
                    {{ role.description }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    {{ role.permissions_count }} permissions
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                    {{ role.users_count }} users
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span 
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :class="role.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'"
                  >
                    {{ role.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center justify-end space-x-2">
                    <UiButton 
                      @click="$inertia.get(route('roles.show', role.id))" 
                      variant="secondary" 
                      size="sm"
                    >
                      View
                    </UiButton>
                    <UiButton 
                      @click="$inertia.get(route('roles.edit', role.id))" 
                      variant="secondary" 
                      size="sm"
                    >
                      Edit
                    </UiButton>
                    <UiButton 
                      v-if="!isSystemRole(role.slug)"
                      @click="confirmDelete(role)" 
                      variant="danger" 
                      size="sm"
                    >
                      Delete
                    </UiButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </UiCard>

      <!-- Quick Actions -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <UiCard>
          <div class="flex items-center">
            <div class="flex-shrink-0 h-12 w-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
              <ModernIcon name="users" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ roles.length }}</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">Total Roles</p>
            </div>
          </div>
        </UiCard>

        <UiCard>
          <div class="flex items-center">
            <div class="flex-shrink-0 h-12 w-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
              <ModernIcon name="check" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ activeRoles }}</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">Active Roles</p>
            </div>
          </div>
        </UiCard>

        <UiCard class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800" @click="$inertia.get(route('permissions.index'))">
          <div class="flex items-center">
            <div class="flex-shrink-0 h-12 w-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">Manage</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">Permissions</p>
            </div>
          </div>
        </UiCard>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <UiModal v-model:open="deleteModal.show" title="Delete Role" size="md">
      <div class="text-sm text-gray-500 dark:text-gray-400 mb-6">
        <p>Are you sure you want to delete the role <strong>{{ deleteModal.role?.name }}</strong>?</p>
        <p class="mt-2">This action cannot be undone.</p>
      </div>

      <div class="flex justify-end space-x-3">
        <UiButton @click="deleteModal.show = false" variant="secondary">
          Cancel
        </UiButton>
        <UiButton @click="deleteRole" variant="danger" :loading="deleteModal.loading">
          Delete Role
        </UiButton>
      </div>
    </UiModal>
  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiCard from '@/components/UiCard.vue'
import UiButton from '@/components/UiButton.vue'
import UiModal from '@/components/UiModal.vue'
import ModernIcon from '@/components/ModernIcon.vue'

// Props
const props = defineProps({
  roles: Array
})

// Delete modal state
const deleteModal = reactive({
  show: false,
  role: null,
  loading: false
})

// Computed properties
const activeRoles = computed(() => {
  return props.roles.filter(role => role.is_active).length
})

// Helper functions
const getRoleColor = (slug) => {
  const colors = {
    admin: 'bg-purple-500',
    sales: 'bg-blue-500',
    accountant: 'bg-green-500'
  }
  return colors[slug] || 'bg-gray-500'
}

const isSystemRole = (slug) => {
  return ['admin', 'sales', 'accountant'].includes(slug)
}

const confirmDelete = (role) => {
  deleteModal.role = role
  deleteModal.show = true
}

const deleteRole = () => {
  deleteModal.loading = true
  
  router.delete(route('roles.destroy', deleteModal.role.id), {
    onSuccess: () => {
      deleteModal.show = false
      deleteModal.role = null
    },
    onFinish: () => {
      deleteModal.loading = false
    }
  })
}
</script>
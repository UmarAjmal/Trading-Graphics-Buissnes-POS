<template>
  <AppLayout>
    <PageHeader
      title="User Management"
      subtitle="Manage system users and their permissions"
    >
      <template #actions>
        <UiButton
          @click="$inertia.get(route('users.create'))"
          variant="primary"
          class="flex items-center gap-2"
        >
          <ModernIcon 
            name="plus" 
            size="sm" 
            class="mr-1"
          />
          Add User
        </UiButton>
      </template>
    </PageHeader>

    <!-- Users Table -->
    <UiCard>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                User
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Role
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Created
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                      <span class="text-white font-medium">{{ user.name.charAt(0).toUpperCase() }}</span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ user.name }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ user.email }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span 
                  class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                  :class="getRoleBadgeClass(user.role)"
                >
                  {{ formatRole(user.role) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ formatDate(user.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-2">
                  <UiButton
                    @click="$inertia.get(route('users.edit', user.id))"
                    variant="outline"
                    size="sm"
                  >
                    Edit
                  </UiButton>
                  <UiButton
                    @click="confirmDelete(user)"
                    variant="danger"
                    size="sm"
                    :disabled="user.id === $page.props.auth.user.id"
                  >
                    Delete
                  </UiButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="users.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        <nav class="flex items-center justify-between">
          <div class="flex-1 flex justify-between sm:hidden">
            <UiButton
              v-if="users.prev_page_url"
              @click="$inertia.get(users.prev_page_url)"
              variant="outline"
            >
              Previous
            </UiButton>
            <UiButton
              v-if="users.next_page_url"
              @click="$inertia.get(users.next_page_url)"
              variant="outline"
            >
              Next
            </UiButton>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700 dark:text-gray-300">
                Showing
                <span class="font-medium">{{ users.from }}</span>
                to
                <span class="font-medium">{{ users.to }}</span>
                of
                <span class="font-medium">{{ users.total }}</span>
                results
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                <UiButton
                  v-if="users.prev_page_url"
                  @click="$inertia.get(users.prev_page_url)"
                  variant="outline"
                  class="rounded-l-md"
                >
                  Previous
                </UiButton>
                <UiButton
                  v-if="users.next_page_url"
                  @click="$inertia.get(users.next_page_url)"
                  variant="outline"
                  class="rounded-r-md"
                >
                  Next
                </UiButton>
              </nav>
            </div>
          </div>
        </nav>
      </div>
    </UiCard>

    <!-- Delete Confirmation Modal -->
    <UiModal v-model="deleteModal.show" title="Delete User">
      <div class="space-y-4">
        <p class="text-gray-600 dark:text-gray-400">
          Are you sure you want to delete <strong>{{ deleteModal.user?.name }}</strong>? 
          This action cannot be undone.
        </p>
        
        <div class="flex justify-end gap-3">
          <UiButton @click="deleteModal.show = false" variant="outline">
            Cancel
          </UiButton>
          <UiButton @click="deleteUser" variant="danger" :loading="deleteModal.loading">
            Delete User
          </UiButton>
        </div>
      </div>
    </UiModal>
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
import UiModal from '@/components/UiModal.vue'

// Props
const props = defineProps({
  users: Object
})

// Delete modal state
const deleteModal = reactive({
  show: false,
  user: null,
  loading: false
})

// Helper functions
const formatRole = (role) => {
  const roles = {
    admin: 'Administrator',
    sales: 'Sales Staff',
    accountant: 'Accountant'
  }
  return roles[role] || role
}

const getRoleBadgeClass = (role) => {
  const classes = {
    admin: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
    sales: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    accountant: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
  }
  return classes[role] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const confirmDelete = (user) => {
  deleteModal.user = user
  deleteModal.show = true
}

const deleteUser = () => {
  deleteModal.loading = true
  
  router.delete(route('users.destroy', deleteModal.user.id), {
    onSuccess: () => {
      deleteModal.show = false
      deleteModal.user = null
    },
    onFinish: () => {
      deleteModal.loading = false
    }
  })
}
</script>
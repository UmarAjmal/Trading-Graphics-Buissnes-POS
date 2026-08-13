<template>
  <AppLayout>
    <PageHeader
      title="Create User"
      subtitle="Add a new user to the system"
    >
      <template #actions>
        <UiButton
          @click="$inertia.get(route('users.index'))"
          variant="outline"
        >
          Back to Users
        </UiButton>
      </template>
    </PageHeader>

    <!-- Create User Form -->
    <UiCard>
      <form @submit.prevent="submit" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Name -->
          <div>
            <UiLabel for="name" required>Full Name</UiLabel>
            <UiInput
              id="name"
              v-model="form.name"
              type="text"
              placeholder="Enter full name"
              :error="form.errors.name"
              required
            />
          </div>

          <!-- Email -->
          <div>
            <UiLabel for="email" required>Email Address</UiLabel>
            <UiInput
              id="email"
              v-model="form.email"
              type="email"
              placeholder="Enter email address"
              :error="form.errors.email"
              required
            />
          </div>

          <!-- Role -->
          <div>
            <UiLabel for="role" required>User Role</UiLabel>
            <select
              id="role"
              v-model="form.role"
              class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              :class="form.errors.role ? 'border-red-500' : ''"
              required
            >
              <option value="">Select a role</option>
              <option value="admin">Administrator</option>
              <option value="sales">Sales Staff</option>
              <option value="accountant">Accountant</option>
            </select>
            <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">{{ form.errors.role }}</p>
          </div>

          <!-- Status Placeholder for symmetry -->
          <div></div>

          <!-- Password -->
          <div>
            <UiLabel for="password" required>Password</UiLabel>
            <UiInput
              id="password"
              v-model="form.password"
              type="password"
              placeholder="Enter password"
              :error="form.errors.password"
              required
            />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              Minimum 8 characters required
            </p>
          </div>

          <!-- Confirm Password -->
          <div>
            <UiLabel for="password_confirmation" required>Confirm Password</UiLabel>
            <UiInput
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              placeholder="Confirm password"
              :error="form.errors.password_confirmation"
              required
            />
          </div>
        </div>

        <!-- Role Permissions Info -->
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
          <h4 class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-2">Role Permissions</h4>
          <div class="space-y-2 text-sm text-blue-800 dark:text-blue-300">
            <div v-if="form.role === 'admin'">
              <strong>Administrator:</strong> Full system access including user management, settings, reports, and all POS functions.
            </div>
            <div v-else-if="form.role === 'sales'">
              <strong>Sales Staff:</strong> Access to POS system, customer management, and basic inventory viewing.
            </div>
            <div v-else-if="form.role === 'accountant'">
              <strong>Accountant:</strong> Access to reports, sales history, financial data, and inventory management.
            </div>
            <div v-else class="text-gray-500 dark:text-gray-400">
              Select a role to see permissions
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
          <UiButton
            @click="$inertia.get(route('users.index'))"
            type="button"
            variant="outline"
          >
            Cancel
          </UiButton>
          <UiButton
            type="submit"
            variant="primary"
            :loading="form.processing"
          >
            Create User
          </UiButton>
        </div>
      </form>
    </UiCard>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiCard from '@/components/UiCard.vue'
import UiButton from '@/components/UiButton.vue'
import UiInput from '@/components/UiInput.vue'
import UiLabel from '@/components/UiLabel.vue'

// Form state
const form = useForm({
  name: '',
  email: '',
  role: '',
  password: '',
  password_confirmation: ''
})

// Submit form
const submit = () => {
  form.post(route('users.store'), {
    onSuccess: () => {
      // Redirect handled by controller
    }
  })
}
</script>
<template>
  <AppLayout title="Create Category">
    <div class="max-w-3xl mx-auto py-8 px-4">
      <PageHeader
        title="Create Category"
        description="Add a new category to keep your catalog organized."
      />

      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <form @submit.prevent="submit">
          <div class="space-y-5">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                Name
                <span class="text-red-500">*</span>
              </label>
              <UiInput
                v-model="form.name"
                type="text"
                placeholder="e.g. Panaflex Rolls"
                required
                class="mt-1"
              />
              <p v-if="form.errors.name" class="text-sm text-red-600 mt-1">
                {{ form.errors.name }}
              </p>
            </div>

            <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                Description
              </label>
              <UiTextarea
                v-model="form.description"
                :rows="4"
                placeholder="Optional details about this category"
                class="mt-1"
              />
              <p v-if="form.errors.description" class="text-sm text-red-600 mt-1">
                {{ form.errors.description }}
              </p>
            </div>
          </div>

          <div class="mt-8 flex items-center gap-3">
            <UiButton type="submit" :disabled="form.processing">
              {{ form.processing ? 'Saving...' : 'Save Category' }}
            </UiButton>

            <UiButton
              type="button"
              variant="secondary"
              :disabled="form.processing"
              @click="goBack"
            >
              Cancel
            </UiButton>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiCard from '@/components/UiCard.vue'
import UiInput from '@/components/UiInput.vue'
import UiTextarea from '@/components/UiTextarea.vue'
import UiButton from '@/components/UiButton.vue'

// Route helper
const route = window.route

const form = useForm({
  name: '',
  description: ''
})

const submit = () => {
  form.post(route('categories.store'), {
    onSuccess: () => form.reset()
  })
}

const goBack = () => {
  router.visit(route('categories.index'))
}
</script>

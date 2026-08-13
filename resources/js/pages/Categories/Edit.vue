<template>
  <AppLayout title="Edit Category">
    <div class="max-w-3xl mx-auto py-8 px-4">
      <PageHeader
        title="Edit Category"
        subtitle="Update the category details"
      />

      <UiCard v-if="category">
        <form @submit.prevent="submit" class="space-y-6">
          <UiInput
            v-model="form.name"
            label="Category Name"
            placeholder="Electronics"
            required
            :error="form.errors.name"
          />

          <UiTextarea
            v-model="form.description"
            label="Description"
            placeholder="Category description (optional)"
            :rows="3"
            :error="form.errors.description"
          />

          <div class="flex gap-3">
            <UiButton type="submit" :disabled="form.processing">
              {{ form.processing ? 'Saving...' : 'Update Category' }}
            </UiButton>
            <UiButton type="button" variant="secondary" @click="goBack" :disabled="form.processing">
              Cancel
            </UiButton>
          </div>
        </form>
      </UiCard>
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

const props = defineProps({
  category: {
    type: Object,
    required: true
  }
})

const form = useForm({
  name: props.category.name || '',
  description: props.category.description || ''
})

const submit = () => {
  form.put(route('categories.update', props.category.id))
}

const goBack = () => {
  router.visit(route('categories.index'))
}
</script>
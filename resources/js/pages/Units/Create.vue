<template>
  <AppLayout title="Create Unit">
    <div class="max-w-3xl mx-auto py-8 px-4">
      <PageHeader
        title="Add Unit"
        subtitle="Create a measurement unit for your products"
      />

      <UiCard>
        <form @submit.prevent="submit" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <UiInput
              v-model="form.code"
              label="Code"
              placeholder="e.g. PCS"
              required
              :error="form.errors.code"
            />

            <UiInput
              v-model="form.name"
              label="Name"
              placeholder="Pieces"
              required
              :error="form.errors.name"
            />
          </div>

          <UiInput
            v-model="form.symbol"
            label="Symbol"
            placeholder="pcs"
            :error="form.errors.symbol"
          />

          <div class="flex gap-3">
            <UiButton type="submit" :disabled="form.processing">
              {{ form.processing ? 'Saving...' : 'Save Unit' }}
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
import UiButton from '@/components/UiButton.vue'

// Route helper
const route = window.route

const form = useForm({
  code: '',
  name: '',
  symbol: ''
})

const submit = () => {
  form.post(route('units.store'))
}

const goBack = () => {
  router.visit(route('units.index'))
}
</script>

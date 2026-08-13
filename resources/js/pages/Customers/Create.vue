<template>
  <AppLayout>
    <PageHeader
      :title="isEdit ? 'Edit Customer' : 'Add New Customer'"
      :subtitle="isEdit ? 'Update customer information' : 'Create a new customer in your database'"
    >
      <div class="flex gap-3">
        <UiButton
          variant="outline"
          @click="$inertia.visit(route('customers.index'))"
        >
          Cancel
        </UiButton>
        <UiButton
          variant="primary"
          @click="submitForm"
          :loading="form.processing"
          :disabled="form.processing"
        >
          {{ isEdit ? 'Update' : 'Create' }} Customer
        </UiButton>
      </div>
    </PageHeader>

    <form @submit.prevent="submitForm" class="space-y-6">
      <!-- Basic Information -->
      <UiCard title="Customer Information" padding="lg">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Customer Name *
            </label>
            <UiInput
              v-model="form.name"
              placeholder="Enter customer name"
              :error="form.errors.name"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Email Address
            </label>
            <UiInput
              v-model="form.email"
              type="email"
              placeholder="customer@example.com"
              :error="form.errors.email"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Phone Number
            </label>
            <UiInput
              v-model="form.phone"
              placeholder="e.g., +92 300 1234567"
              :error="form.errors.phone"
            />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Address
            </label>
            <UiTextarea
              v-model="form.address"
              placeholder="Enter full address"
              :error="form.errors.address"
              :rows="3"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              City
            </label>
            <UiInput
              v-model="form.city"
              placeholder="Enter city"
              :error="form.errors.city"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Postal Code
            </label>
            <UiInput
              v-model="form.postal_code"
              placeholder="e.g., 12345"
              :error="form.errors.postal_code"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Credit Limit
            </label>
            <UiInput
              v-model="form.credit_limit"
              type="number"
              placeholder="e.g., 1000.00"
              :error="form.errors.credit_limit"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Opening Balance
            </label>
            <UiInput
              v-model="form.opening_balance"
              type="number"
              placeholder="e.g., 500.00"
              :error="form.errors.opening_balance"
            />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Notes
            </label>
            <UiTextarea
              v-model="form.notes"
              placeholder="Additional notes about the customer"
              :error="form.errors.notes"
              :rows="3"
            />
          </div>
        </div>
      </UiCard>

      <!-- Bottom Action Buttons -->
      <UiCard padding="lg">
        <div class="flex justify-end gap-3">
          <UiButton
            variant="outline"
            @click="$inertia.visit(route('customers.index'))"
          >
            Cancel
          </UiButton>
          <UiButton
            variant="primary"
            type="submit"
            :loading="form.processing"
            :disabled="form.processing"
          >
            {{ isEdit ? 'Update Customer' : 'Save Customer' }}
          </UiButton>
        </div>
      </UiCard>
    </form>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import PageHeader from '@/components/PageHeader.vue'
import UiButton from '@/components/UiButton.vue'
import UiInput from '@/components/UiInput.vue'
import UiTextarea from '@/components/UiTextarea.vue'
import UiCard from '@/components/UiCard.vue'

// Props
const props = defineProps({
  customer: {
    type: Object,
    default: () => null
  }
})

// Computed
const isEdit = computed(() => !!props.customer)

// Form
const form = useForm({
  name: props.customer?.name || '',
  email: props.customer?.email || '',
  phone: props.customer?.phone || '',
  address: props.customer?.address || '',
  city: props.customer?.city || '',
  postal_code: props.customer?.postal_code || '',
  credit_limit: props.customer?.credit_limit || '',
  opening_balance: props.customer?.opening_balance || '',
  notes: props.customer?.notes || ''
})

// Methods
const submitForm = () => {
  // Prevent double submission
  if (form.processing) {
    return
  }
  
  if (isEdit.value) {
    form.put(route('customers.update', props.customer.id), {
      preserveScroll: true,
      onSuccess: () => {
        // Success handled by redirect
      }
    })
  } else {
    form.post(route('customers.store'), {
      preserveScroll: true,
      onSuccess: () => {
        // Success handled by redirect
      }
    })
  }
}
</script>
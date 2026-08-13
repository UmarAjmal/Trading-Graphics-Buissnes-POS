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
              placeholder="Enter postal code"
              :error="form.errors.postal_code"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Credit Limit (PKR)
            </label>
            <UiInput
              v-model="form.credit_limit"
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
              :error="form.errors.credit_limit"
            />
            <p class="text-sm text-gray-500 mt-1">Maximum credit amount customer can take on account</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Opening Balance (PKR)
            </label>
            <UiInput
              v-model="form.opening_balance"
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
              :error="form.errors.opening_balance"
            />
            <p class="text-sm text-gray-500 mt-1">Initial debt/credit balance (Positive = Customer owes you)</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Advance Payment (PKR)
            </label>
            <UiInput
              v-model="form.advance"
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
              :error="form.errors.advance"
              disabled
            />
            <p class="text-sm text-gray-500 mt-1">Current advance balance (Read-only)</p>
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
            @click="submitForm"
            :loading="form.processing"
            type="submit"
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
  advance: props.customer?.advance || '',
  notes: props.customer?.notes || ''
})

// Methods
const submitForm = () => {
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
<template>
  <AppLayout>
    <PageHeader
      :title="isEdit ? 'Edit Supplier' : 'Add New Supplier'"
      :subtitle="isEdit ? 'Update supplier information' : 'Create a new supplier in your system'"
    >
      <div class="flex gap-3">
        <UiButton
          variant="outline"
          @click="$inertia.visit(route('suppliers.index'))"
        >
          Cancel
        </UiButton>
        <UiButton
          variant="primary"
          @click="submitForm"
          :loading="form.processing"
        >
          {{ isEdit ? 'Update' :  'Create' }} Supplier
        </UiButton>
      </div>
    </PageHeader>

    <form @submit.prevent="submitForm" class="space-y-6">
      <!-- Basic Information -->
      <UiCard title="Supplier Information" padding="lg">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Supplier Name *
            </label>
            <UiInput
              v-model="form.name"
              placeholder="Enter supplier name"
              :error="form.errors.name"
              required
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

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              WhatsApp Number
            </label>
            <UiInput
              v-model="form.whatsapp"
              placeholder="e.g., +92 300 1234567"
              :error="form.errors.whatsapp"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Email Address
            </label>
            <UiInput
              v-model="form.email"
              type="email"
              placeholder="supplier@example.com"
              :error="form.errors.email"
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
              rows="3"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Contact Person
            </label>
            <UiInput
              v-model="form.contact_person"
              placeholder="Enter contact person name"
              :error="form.errors.contact_person"
            />
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
            <p class="text-sm text-gray-500 mt-1">Initial payable amount (Business owes supplier)</p>
          </div>
        </div>
      </UiCard>

      <!-- Bottom Action Buttons -->
      <UiCard padding="lg">
        <div class="flex justify-end gap-3">
          <UiButton
            variant="outline"
            @click="$inertia.visit(route('suppliers.index'))"
          >
            Cancel
          </UiButton>
          <UiButton
            variant="primary"
            @click="submitForm"
            :loading="form.processing"
            type="submit"
          >
            {{ isEdit ? 'Update Supplier' : 'Save Supplier' }}
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
  supplier: {
    type: Object,
    default: () => null
  }
})

// Computed
const isEdit = computed(() => !!props.supplier)

// Form
const form = useForm({
  name: props.supplier?.name || '',
  phone: props.supplier?.phone || '',
  whatsapp: props.supplier?.whatsapp || '',
  email: props.supplier?.email || '',
  address: props.supplier?.address || '',
  contact_person: props.supplier?.contact_person || '',
  opening_balance: props.supplier?.opening_balance || ''
})

// Methods
const submitForm = () => {
  if (isEdit.value) {
    form.put(route('suppliers.update', props.supplier.id), {
      preserveScroll: true,
      onSuccess: () => {
        // Success handled by redirect
      },
      onError: (errors) => {
        // Validation errors are automatically handled by useForm
        console.log('Update validation errors:', errors);
      }
    })
  } else {
    form.post(route('suppliers.store'), {
      preserveScroll: true,
      onSuccess: () => {
        // Success handled by redirect
      },
      onError: (errors) => {
        // Validation errors are automatically handled by useForm
        console.log('Create validation errors:', errors);
      }
    })
  }
}
</script>
<template>
  <AppLayout>
    <PageHeader 
      title="Company Settings" 
      subtitle="Manage your company profile and branding information"
      :breadcrumbs="breadcrumbs"
    />

    <form @submit.prevent="submitForm" class="space-y-6">
      <!-- General Information Card -->
      <UiCard title="General Information" padding="lg">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Logo Upload -->
          <div class="lg:col-span-2">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-100 mb-3">
              Company Logo
            </label>
            <div class="flex items-start space-x-6">
              <!-- Logo Preview -->
              <div class="flex-shrink-0">
                <div class="w-24 h-24 rounded-lg border-2 border-slate-200 dark:border-slate-700 flex items-center justify-center bg-slate-50 dark:bg-slate-800 overflow-hidden">
                  <img 
                    v-if="logoPreview" 
                    :src="logoPreview" 
                    alt="Company Logo"
                    class="w-full h-full object-cover"
                  />
                  <ModernIcon 
                    v-else 
                    name="camera" 
                    size="lg" 
                    variant="soft"
                    class="text-slate-400"
                  />
                </div>
              </div>
              
              <!-- Upload Area -->
              <div class="flex-1">
                <div 
                  @drop.prevent="handleFileDrop"
                  @dragover.prevent
                  @dragenter.prevent
                  class="border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-lg p-6 text-center hover:border-primary-500 transition-colors cursor-pointer"
                  @click="$refs.logoInput.click()"
                >
                  <ModernIcon 
                    name="upload" 
                    size="xl" 
                    variant="soft"
                    class="mx-auto mb-4 text-slate-400"
                  />
                  <p class="text-sm text-slate-600 dark:text-slate-400">
                    <span class="font-medium text-primary-600">Click to upload</span> or drag and drop
                  </p>
                  <p class="text-xs text-slate-500 dark:text-slate-500">PNG, JPG up to 1MB</p>
                </div>
                <input 
                  ref="logoInput"
                  type="file"
                  accept="image/*"
                  @change="handleLogoChange"
                  class="hidden"
                />
              </div>
            </div>
          </div>

          <!-- Company Name -->
          <UiInput
            v-model="form.company_name"
            label="Company Name"
            required
            :error="errors.company_name"
            placeholder="Enter company name"
          />

          <!-- Tagline -->
          <UiInput
            v-model="form.tagline"
            label="Tagline"
            :error="errors.tagline"
            placeholder="Enter company tagline"
          />

          <!-- Currency -->
          <UiSelect
            v-model="form.currency"
            label="Currency"
            required
            :error="errors.currency"
            :options="currencyOptions"
          />
        </div>
      </UiCard>

      <!-- Contact Details Card -->
      <UiCard title="Contact Details" padding="lg">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Phone 1 -->
          <UiInput
            v-model="form.phone_1"
            label="Primary Phone"
            :error="errors.phone_1"
            placeholder="e.g., 062-2720822"
          />

          <!-- Phone 2 -->
          <UiInput
            v-model="form.phone_2"
            label="Secondary Phone"
            :error="errors.phone_2"
            placeholder="e.g., 0301-8647887"
          />

          <!-- WhatsApp -->
          <UiInput
            v-model="form.whatsapp_number"
            label="WhatsApp Number"
            :error="errors.whatsapp_number"
            placeholder="e.g., 03067288442"
          />

          <!-- Email -->
          <UiInput
            v-model="form.email"
            type="email"
            label="Email Address"
            :error="errors.email"
            placeholder="info@company.com"
          />

          <!-- Website -->
          <UiInput
            v-model="form.website"
            type="url"
            label="Website"
            :error="errors.website"
            placeholder="https://company.com"
          />

          <!-- Address (Full Width) -->
          <div class="lg:col-span-2">
            <UiTextarea
              v-model="form.address"
              label="Address"
              :error="errors.address"
              :rows="3"
              placeholder="Complete business address"
            />
          </div>

          <!-- NTN -->
          <UiInput
            v-model="form.ntn"
            label="NTN (National Tax Number)"
            :error="errors.ntn"
            placeholder="1234567-8"
          />

          <!-- Sales Tax -->
          <UiInput
            v-model="form.sales_tax_no"
            label="Sales Tax Number"
            :error="errors.sales_tax_no"
            placeholder="1234567890123"
          />
        </div>
      </UiCard>

      <!-- Print & Branding Card -->
      <UiCard title="Print & Branding" padding="lg">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Invoice Prefix -->
          <UiInput
            v-model="form.invoice_prefix"
            label="Invoice Prefix"
            required
            :error="errors.invoice_prefix"
            placeholder="INV-"
            class="lg:max-w-xs"
          />

          <!-- Footer Note (Full Width) -->
          <div class="lg:col-span-2">
            <UiTextarea
              v-model="form.footer_note"
              label="Footer Note (Reports)"
              :error="errors.footer_note"
              :rows="2"
              placeholder="Additional note for reports and documents"
            />
          </div>

          <!-- Print Footer Message (Full Width) -->
          <div class="lg:col-span-2">
            <UiTextarea
              v-model="form.print_footer_message"
              label="Print Footer Message"
              :error="errors.print_footer_message"
              :rows="2"
              placeholder="Thank you message for invoices"
            />
          </div>
        </div>
      </UiCard>
    </form>

    <!-- Sticky Bottom Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-white dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 p-4 z-40">
      <div class="max-w-7xl mx-auto flex justify-end">
        <UiButton 
          variant="primary" 
          @click="submitForm"
          :disabled="form.processing"
          class="min-w-[140px]"
        >
          <template #icon-left>
            <ModernIcon 
              v-if="!loading" 
              name="save" 
              size="sm" 
              class="mr-2"
            />
            <ModernIcon 
              v-else 
              name="refresh" 
              size="sm" 
              class="animate-spin mr-2"
            />
          </template>
          {{ form.processing ? 'Saving...' : 'Save Changes' }}
        </UiButton>
      </div>
    </div>

    <!-- Add bottom padding to prevent content overlap with sticky bar -->
    <div class="h-20"></div>
  </AppLayout>
</template>

<script>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useCompanyStore } from '../../stores/company.js'
import AppLayout from '../../layouts/AppLayout.vue'
import PageHeader from '../../components/PageHeader.vue'
import UiCard from '../../components/UiCard.vue'
import UiButton from '../../components/UiButton.vue'
import UiInput from '../../components/UiInput.vue'
import UiSelect from '../../components/UiSelect.vue'
import UiTextarea from '../../components/UiTextarea.vue'
import ModernIcon from '../../components/ModernIcon.vue'

export default {
  name: 'CompanySettings',
  components: {
    AppLayout,
    PageHeader,
    UiCard,
    UiButton,
    UiInput,
    UiSelect,
    UiTextarea,
    ModernIcon
  },
  props: {
    settings: {
      type: Object,
      default: () => ({})
    }
  },
  setup(props) {
    const { updateCompany } = useCompanyStore()
    const errors = ref({})
    const loading = ref(false)
    
    const breadcrumbs = [
      { name: 'Dashboard', href: '/dashboard' },
      { name: 'Settings', href: '/settings' },
      { name: 'Company Settings' }
    ]
    
    const currencyOptions = [
      { value: 'PKR', label: 'Pakistani Rupee (PKR)' },
      { value: 'PKR', label: 'Pakistani Rupee (Rs)' },
      { value: 'PKR', label: 'Pakistan Rupee (PKR)' }
    ]
    
    // Form state using Inertia form
    const form = useForm({
      company_name: props.settings.company_name || '',
      tagline: props.settings.tagline || '',
      phone_1: props.settings.phone_1 || '',
      phone_2: props.settings.phone_2 || '',
      whatsapp_number: props.settings.whatsapp_number || '',
      email: props.settings.email || '',
      address: props.settings.address || '',
      website: props.settings.website || '',
      ntn: props.settings.ntn || '',
      sales_tax_no: props.settings.sales_tax_no || '',
      currency: props.settings.currency || 'PKR',
      invoice_prefix: props.settings.invoice_prefix || 'INV-',
      footer_note: props.settings.footer_note || '',
      print_footer_message: props.settings.print_footer_message || '',
      logo: null,
    })

    const logoPreview = ref(props.settings.logo_url || null)
    // Handle logo file change
    const handleLogoChange = (event) => {
      const file = event.target.files[0]
      if (file) {
        form.logo = file
        
        // Create preview
        const reader = new FileReader()
        reader.onload = (e) => {
          logoPreview.value = e.target.result
        }
        reader.readAsDataURL(file)
      }
    }
    
    // Handle drag and drop
    const handleFileDrop = (event) => {
      const files = event.dataTransfer.files
      if (files.length > 0) {
        const file = files[0]
        if (file.type.startsWith('image/')) {
          form.logo = file
          
          const reader = new FileReader()
          reader.onload = (e) => {
            logoPreview.value = e.target.result
          }
          reader.readAsDataURL(file)
        }
      }
    }
    
    // Submit form using API
    const submitForm = async () => {
      loading.value = true
      form.processing = true
      
      try {
        const formData = new FormData()
        
        Object.entries(form.data()).forEach(([key, value]) => {
          if (key !== 'logo' && value !== null && value !== undefined) {
            formData.append(key, value)
          }
        })
        
        if (form.logo) {
          formData.append('logo', form.logo)
        }
        
        formData.append('_method', 'PUT')
        
        const result = await updateCompany(formData)
        
        if (result.success) {
          alert('Company settings updated successfully!')
          errors.value = {}
          form.logo = null
          
          if (result.data?.logo_url) {
            logoPreview.value = result.data.logo_url
          }
        } else {
          errors.value = result.errors || {}
          alert('Failed to update company settings: ' + (result.message || 'Validation failed'))
        }
      } catch (error) {
        console.error('Update failed:', error)
        alert('Failed to update company settings: ' + (error.response?.data?.message || error.message))
      } finally {
        loading.value = false
        form.processing = false
      }
    }

    return {
      form,
      logoPreview,
      errors,
      loading,
      breadcrumbs,
      currencyOptions,
      handleLogoChange,
      handleFileDrop,
      submitForm
    }
  }
}
</script>

import { ref, reactive } from 'vue'
import axios from 'axios'

// Global company state
const company = ref({
  company_name: 'POS System',
  tagline: '',
  logo_url: null,
  phone_1: '',
  phone_2: '',
  whatsapp_number: '',
  email: '',
  address: '',
  website: '',
  ntn: '',
  sales_tax_no: '',
  currency: 'PKR',
  invoice_prefix: 'INV-',
  footer_note: '',
  print_footer_message: '',
})

const loading = ref(false)
const error = ref(null)

export function useCompanyStore() {
  /**
   * Load company settings from API
   */
  const loadCompany = async () => {
    loading.value = true
    error.value = null
    
    try {
      const { data } = await axios.get('/api/settings/company')
      
      if (data && data.success && data.data) {
        company.value = { ...company.value, ...data.data }
      } else if (data) {
        company.value = { ...company.value, ...data }
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to load company settings'
      console.error('Error loading company settings:', err)
    } finally {
      loading.value = false
    }
  }

  /**
   * Update company settings
   */
  const updateCompany = async (payload) => {
    loading.value = true
    error.value = null
    
    try {
      let response
      
      if (payload instanceof FormData) {
        if (!payload.has('_method')) {
          payload.append('_method', 'PUT')
        }
        response = await axios.post('/api/settings/company', payload)
      } else {
        response = await axios.put('/api/settings/company', payload)
      }
      
      const { data } = response
      
      if (data.success) {
        company.value = { ...company.value, ...data.data }
        return { success: true, message: data.message, data: data.data }
      }
      
      return { 
        success: false, 
        message: data.message || 'Update failed',
        errors: data.errors || {}
      }
    } catch (err) {
      const message = err.response?.data?.message || 'Failed to update company settings'
      error.value = message
      
      return { 
        success: false, 
        message,
        errors: err.response?.data?.errors || {}
      }
    } finally {
      loading.value = false
    }
  }

  /**
   * Get formatted company address for invoices
   */
  const getFormattedAddress = () => {
    const parts = []
    
    if (company.value.address) parts.push(company.value.address)
    
    const contact = []
    if (company.value.phone_1) contact.push(`Phone: ${company.value.phone_1}`)
    if (company.value.phone_2) contact.push(company.value.phone_2)
    if (contact.length > 0) parts.push(contact.join(' | '))
    
    if (company.value.whatsapp_number) {
      parts.push(`WhatsApp: ${company.value.whatsapp_number}`)
    }
    
    if (company.value.email) {
      parts.push(`Email: ${company.value.email}`)
    }
    
    const tax = []
    if (company.value.ntn) tax.push(`NTN: ${company.value.ntn}`)
    if (company.value.sales_tax_no) tax.push(`STN: ${company.value.sales_tax_no}`)
    if (tax.length > 0) parts.push(tax.join(' | '))
    
    return parts.join('\n')
  }

  /**
   * Get invoice header data
   */
  const getInvoiceHeader = () => {
    return {
      logo: company.value.logo_url,
      name: company.value.company_name,
      tagline: company.value.tagline,
      address: getFormattedAddress(),
      footer: company.value.print_footer_message || `Thank you for your business! — ${company.value.company_name}`
    }
  }

  return {
    // State
    company,
    loading,
    error,
    
    // Actions
    loadCompany,
    updateCompany,
    
    // Getters
    getFormattedAddress,
    getInvoiceHeader,
  }
}

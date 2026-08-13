/**
 * Currency formatting utilities
 */

// Currency symbols mapping
const currencySymbols = {
  'PKR': 'Rs. ',
  'USD': 'Rs. ',
  'AED': 'Rs. ',
  'EUR': 'Rs. ',
  'GBP': 'Rs. '
}

// Get currency from company settings (stored globally)
export function getCurrency() {
  // Try to get from window.companyCurrency if set
  if (window.companyCurrency) {
    return window.companyCurrency
  }
  
  // Default to PKR
  return 'PKR'
}

// Get currency symbol
export function getCurrencySymbol(currency = null) {
  const curr = currency || getCurrency()
  return currencySymbols[curr] || curr + ' '
}

// Format amount with currency
export function formatCurrency(amount, currency = null) {
  const curr = currency || getCurrency()
  const symbol = getCurrencySymbol(curr)
  const numAmount = Number(amount || 0)
  
  // Format with 2 decimal places
  const formatted = numAmount.toFixed(2)
  
  // For PKR, place symbol before amount
  if (curr === 'PKR') {
    return `${symbol}${formatted}`
  }
  
  // For other currencies, follow their convention
  return `${symbol}${formatted}`
}

// Set global currency (called from app initialization)
export function setGlobalCurrency(currency) {
  window.companyCurrency = currency
}
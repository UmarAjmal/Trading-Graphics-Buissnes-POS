import { ref, reactive } from 'vue'

const toasts = ref([])
let toastId = 0

export function useToasts() {
  const addToast = (toast) => {
    const id = ++toastId
    const newToast = {
      id,
      type: 'info',
      title: '',
      message: '',
      duration: 5000,
      ...toast
    }
    
    toasts.value.push(newToast)
    
    // Auto remove toast after duration
    if (newToast.duration > 0) {
      setTimeout(() => {
        removeToast(id)
      }, newToast.duration)
    }
    
    return id
  }
  
  const removeToast = (id) => {
    const index = toasts.value.findIndex(toast => toast.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }
  
  const clearAllToasts = () => {
    toasts.value = []
  }
  
  // Convenience methods
  const success = (message, options = {}) => {
    return addToast({
      type: 'success',
      message,
      ...options
    })
  }
  
  const error = (message, options = {}) => {
    return addToast({
      type: 'error',
      message,
      duration: 7000, // Error messages stay longer
      ...options
    })
  }
  
  const warning = (message, options = {}) => {
    return addToast({
      type: 'warning',
      message,
      ...options
    })
  }
  
  const info = (message, options = {}) => {
    return addToast({
      type: 'info',
      message,
      ...options
    })
  }
  
  return {
    toasts: toasts.value,
    addToast,
    removeToast,
    clearAllToasts,
    success,
    error,
    warning,
    info
  }
}